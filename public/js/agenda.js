jQuery(document).ready(function($) {
    const wrapper = $('#dame-agenda-wrapper');
    if (wrapper.length === 0) return;

    const calendarGrid = $('#dame-calendar-grid');
    const weekdaysContainer = $('.dame-calendar-weekdays');
    const currentMonthDisplay = $('#dame-agenda-current-month');
    const prevMonthBtn = $('#dame-agenda-prev-month');
    const nextMonthBtn = $('#dame-agenda-next-month');
    const filterToggleBtn = $('#dame-agenda-filter-toggle');
    const filterPanel = $('#dame-agenda-filter-panel');
    const searchInput = $('#dame-agenda-search-input');
    const tooltip = $('#dame-event-tooltip');
    const monthYearPicker = $('#dame-month-year-selector');
    const monthPickerToggle = $('.dame-agenda-month-picker-toggle');

    let currentDate = new Date();
    let searchTimeout;

    function fetchAndRenderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth(); // 0-indexed

        const categories = $('.dame-agenda-cat-filter:checked').map(function() {
            return $(this).val();
        }).get();

        const searchTerm = searchInput.val();

        calendarGrid.css('opacity', 0.5);

        $.ajax({
            url: dame_agenda_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dame_get_agenda_events',
                nonce: dame_agenda_ajax.nonce,
                year: year,
                month: month + 1, // WP uses 1-indexed month
                categories: categories,
                search: searchTerm,
            },
            success: function(response) {
                if (response.success) {
                    renderCalendar(year, month, response.data);
                } else {
                    calendarGrid.html('<p>Error loading events.</p>');
                }
                calendarGrid.css('opacity', 1);
            },
            error: function() {
                calendarGrid.html('<p>Error loading events.</p>');
                calendarGrid.css('opacity', 1);
            }
        });
    }

    function renderCalendar(year, month, events) {
        currentMonthDisplay.text(dame_agenda_ajax.i18n.months[month] + ' ' + year);
        calendarGrid.empty();
        weekdaysContainer.empty();

        // Render weekdays
        dame_agenda_ajax.i18n.weekdays_short.forEach(day => {
            weekdaysContainer.append(`<div>${day}</div>`);
        });

        const firstDayOfMonth = new Date(year, month, 1);
        const lastDayOfMonth = new Date(year, month + 1, 0);
        const daysInMonth = lastDayOfMonth.getDate();
        const startDayOfWeek = (firstDayOfMonth.getDay() - dame_agenda_ajax.start_of_week + 7) % 7;

        // Previous month's days
        const prevLastDay = new Date(year, month, 0).getDate();
        for (let i = startDayOfWeek; i > 0; i--) {
            calendarGrid.append(`<div class="dame-calendar-day other-month">
                <div class="day-number">${prevLastDay - i + 1}</div>
            </div>`);
        }

        // Current month's days
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();
            const dayCell = $(`<div class="dame-calendar-day ${isToday ? 'today' : ''}" data-date="${dateStr}">
                <div class="day-number">${day}</div>
                <div class="events-container"></div>
            </div>`);
            calendarGrid.append(dayCell);
        }

        // Next month's days
        const totalCells = startDayOfWeek + daysInMonth;
        const remainingCells = (totalCells % 7 === 0) ? 0 : 7 - (totalCells % 7);
        for (let i = 1; i <= remainingCells; i++) {
            calendarGrid.append(`<div class="dame-calendar-day other-month">
                <div class="day-number">${i}</div>
            </div>`);
        }

        renderEvents(events);
    }

    function renderEvents(events) {
        // Sort events: multi-day first, then by start time
        events.sort((a, b) => {
            const aDuration = new Date(a.end_date) - new Date(a.start_date);
            const bDuration = new Date(b.end_date) - new Date(b.start_date);
            if (aDuration > 0 && bDuration === 0) return -1;
            if (bDuration > 0 && aDuration === 0) return 1;
            if (a.all_day && !b.all_day) return -1;
            if (b.all_day && !a.all_day) return 1;
            if (a.start_time < b.start_time) return -1;
            if (a.start_time > b.start_time) return 1;
            return 0;
        });

        const wp_sow = parseInt(dame_agenda_ajax.start_of_week, 10);

        events.forEach(event => {
            const startDate = new Date(event.start_date + 'T00:00:00');
            const endDate = new Date(event.end_date + 'T00:00:00');
            const isMultiDay = startDate.toDateString() !== endDate.toDateString();

            if (!isMultiDay) {
                const dateStr = `${startDate.getFullYear()}-${String(startDate.getMonth() + 1).padStart(2, '0')}-${String(startDate.getDate()).padStart(2, '0')}`;
                const dayCell = $(`.dame-calendar-day[data-date="${dateStr}"]`);
                if (dayCell.length) {
                    const timeText = event.all_day == '1' ? dame_agenda_ajax.i18n.all_day : `${event.start_time} - ${event.end_time}`;
                    const eventHtml = `<div class="dame-event dame-event-ponctuel" style="border-left-color: ${event.color};">
                        <div class="event-time">${timeText}</div>
                        <div class="event-title">${event.title}</div>
                    </div>`;
                    const eventEl = $(eventHtml).data('event', event);
                    dayCell.find('.events-container').append(eventEl);
                }
                return; // continue to next event
            }

            // --- Logic for multi-day events ---
            let currentDatePointer = new Date(startDate);
            while (currentDatePointer <= endDate) {
                const dayOfWeek = currentDatePointer.getDay();
                const isEventStart = currentDatePointer.getTime() === startDate.getTime();
                const isWeekStart = dayOfWeek === wp_sow;

                if (isEventStart || isWeekStart) {
                    const year = currentDatePointer.getFullYear();
                    const month = String(currentDatePointer.getMonth() + 1).padStart(2, '0');
                    const day = String(currentDatePointer.getDate()).padStart(2, '0');
                    const dateStr = `${year}-${month}-${day}`;
                    const dayCell = $(`.dame-calendar-day[data-date="${dateStr}"]`);

                    if (dayCell.length) {
                        // Calculate how many days this segment spans
                        let span = 1;
                        let lookahead = new Date(currentDatePointer);
                        lookahead.setDate(lookahead.getDate() + 1);
                        while (lookahead <= endDate && lookahead.getDay() !== wp_sow) {
                            span++;
                            lookahead.setDate(lookahead.getDate() + 1);
                        }

                        const width = `calc(${span * 100}% + ${span - 1}px)`;

                        let classList = 'dame-event dame-event-duree';
                        if (isEventStart) classList += ' start';
                        if (lookahead > endDate) classList += ' end';

                        const eventHtml = `<div class="${classList}" style="background-color: ${event.color}; width: ${width};">
                            ${event.title}
                        </div>`;

                        const eventEl = $(eventHtml).data('event', event);
                        dayCell.find('.events-container').append(eventEl);

                        // Advance pointer by the number of days we just spanned
                        currentDatePointer.setDate(currentDatePointer.getDate() + span);
                    } else {
                        // Day cell not visible, just advance one day
                        currentDatePointer.setDate(currentDatePointer.getDate() + 1);
                    }
                } else {
                    currentDatePointer.setDate(currentDatePointer.getDate() + 1);
                }
            }
        });
    }

    // Event Handlers
    prevMonthBtn.on('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        fetchAndRenderCalendar();
    });

    nextMonthBtn.on('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        fetchAndRenderCalendar();
    });

    filterToggleBtn.on('click', function(e) {
        e.stopPropagation();
        filterPanel.toggle();
    });

    $(document).on('click', function(e) {
        if (!filterPanel.is(e.target) && filterPanel.has(e.target).length === 0 && !filterToggleBtn.is(e.target)) {
            filterPanel.hide();
        }
    });

    filterPanel.on('change', '.dame-agenda-cat-filter', fetchAndRenderCalendar);

    searchInput.on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchAndRenderCalendar, 500);
    });

    calendarGrid.on('mouseenter', '.dame-event', function(e) {
        const eventData = $(this).data('event');
        if (!eventData) return;

        const timeText = eventData.all_day == '1' ? dame_agenda_ajax.i18n.all_day : `${eventData.start_time} - ${eventData.end_time}`;

        let tooltipHtml = `<h4>${eventData.title}</h4>`;
        tooltipHtml += `<p>${timeText}</p>`;
        if (eventData.location) {
            tooltipHtml += `<p>${eventData.location}</p>`;
        }
        tooltipHtml += `<div class="tooltip-description">${eventData.description}</div>`;

        tooltip.html(tooltipHtml).show();

        // Position tooltip
        const top = e.pageY + 10;
        const left = e.pageX + 10;
        tooltip.css({ top: top + 'px', left: left + 'px' });

    }).on('mouseleave', '.dame-event', function() {
        tooltip.hide();
    }).on('click', '.dame-event', function() {
        const eventData = $(this).data('event');
        if (eventData && eventData.url) {
            window.location.href = eventData.url;
        }
    });

    // Month/Year Picker
    function renderMonthPicker() {
        const year = currentDate.getFullYear();
        $('#dame-selector-year').text(year);
        const monthGrid = $('.dame-month-grid');
        monthGrid.empty();
        const currentMonth = currentDate.getMonth();

        dame_agenda_ajax.i18n.months.forEach((monthName, index) => {
            const monthEl = $(`<span>${monthName}</span>`);
            if (index === currentMonth) {
                monthEl.addClass('selected');
            }
            monthEl.on('click', function() {
                currentDate.setMonth(index);
                fetchAndRenderCalendar();
                monthYearPicker.hide();
            });
            monthGrid.append(monthEl);
        });
    }

    monthPickerToggle.on('click', function(e) {
        e.stopPropagation();
        renderMonthPicker();
        monthYearPicker.css({
            top: $(this).offset().top + $(this).outerHeight() + 5,
            left: $(this).offset().left
        }).toggle();
    });

    $('#dame-selector-prev-year').on('click', function() {
        currentDate.setFullYear(currentDate.getFullYear() - 1);
        renderMonthPicker();
    });

    $('#dame-selector-next-year').on('click', function() {
        currentDate.setFullYear(currentDate.getFullYear() + 1);
        renderMonthPicker();
    });

    $(document).on('click', function(e) {
        if (!monthYearPicker.is(e.target) && monthYearPicker.has(e.target).length === 0 && !monthPickerToggle.is(e.target)) {
            monthYearPicker.hide();
        }
    });


    // Initial Load
    fetchAndRenderCalendar();
});
