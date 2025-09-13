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

    /**
     * Formats a Date object into 'YYYY-MM-DD' string.
     * @param {Date} date The date to format.
     * @returns {string}
     */
    function formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function fetchAndRenderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth(); // 0-indexed

        // Calculate the exact start and end dates of the visible grid
        const firstDayOfMonth = new Date(year, month, 1);
        const lastDayOfMonth = new Date(year, month + 1, 0);
        const daysInMonth = lastDayOfMonth.getDate();
        const startDayOfWeek = (firstDayOfMonth.getDay() - dame_agenda_ajax.start_of_week + 7) % 7;

        const gridStartDate = new Date(firstDayOfMonth);
        gridStartDate.setDate(gridStartDate.getDate() - startDayOfWeek);

        const totalCellsBeforeNextMonth = startDayOfWeek + daysInMonth;
        const remainingCells = (totalCellsBeforeNextMonth % 7 === 0) ? 0 : 7 - (totalCellsBeforeNextMonth % 7);
        const totalGridDays = totalCellsBeforeNextMonth + remainingCells;

        const gridEndDate = new Date(gridStartDate);
        gridEndDate.setDate(gridEndDate.getDate() + totalGridDays - 1);


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
                start_date: formatDate(gridStartDate),
                end_date: formatDate(gridEndDate),
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
        // Previous month's days
        const prevMonthDate = new Date(year, month, 0);
        const prevYear = prevMonthDate.getFullYear();
        const prevMonth = prevMonthDate.getMonth();
        const prevLastDay = prevMonthDate.getDate();
        for (let i = startDayOfWeek; i > 0; i--) {
            const day = prevLastDay - i + 1;
            const dateStr = `${prevYear}-${String(prevMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            calendarGrid.append(`<div class="dame-calendar-day other-month" data-date="${dateStr}">
                <div class="day-number">${day}</div>
                <div class="events-container"></div>
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
        const nextMonthDate = new Date(year, month + 1, 1);
        const nextYear = nextMonthDate.getFullYear();
        const nextMonth = nextMonthDate.getMonth();
        const totalCells = startDayOfWeek + daysInMonth;
        const remainingCells = (totalCells % 7 === 0) ? 0 : 7 - (totalCells % 7);
        for (let day = 1; day <= remainingCells; day++) {
            const dateStr = `${nextYear}-${String(nextMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            calendarGrid.append(`<div class="dame-calendar-day other-month" data-date="${dateStr}">
                <div class="day-number">${day}</div>
                <div class="events-container"></div>
            </div>`);
        }

        renderEvents(events);
    }

    /**
     * Safely parses a 'YYYY-MM-DD' string into a local Date object.
     * @param {string} dateStr The date string to parse.
     * @returns {Date}
     */
    function parseDateAsLocal(dateStr) {
        const [year, month, day] = dateStr.split('-').map(Number);
        return new Date(year, month - 1, day);
    }

    function renderEvents(events) {
        const EVENT_HEIGHT = 32; // px
        const EVENT_SPACING = 2; // px
        const wp_sow = parseInt(dame_agenda_ajax.start_of_week, 10);

        // Sort events: multi-day first, then by start time
        events.sort((a, b) => {
            const aIsMulti = (parseDateAsLocal(a.end_date) - parseDateAsLocal(a.start_date)) > 0;
            const bIsMulti = (parseDateAsLocal(b.end_date) - parseDateAsLocal(b.start_date)) > 0;
            if (aIsMulti && !bIsMulti) return -1;
            if (!aIsMulti && bIsMulti) return 1;
            if (a.all_day && !b.all_day) return -1;
            if (!a.all_day && b.all_day) return 1;
            if (a.start_time < b.start_time) return -1;
            if (a.start_time > b.start_time) return 1;
            return 0;
        });

        const multiDayEvents = events.filter(e => (parseDateAsLocal(e.end_date) - parseDateAsLocal(e.start_date)) > 0);
        const singleDayEvents = events.filter(e => (parseDateAsLocal(e.end_date) - parseDateAsLocal(e.start_date)) <= 0);
        const dayLanes = new Map(); // K: 'YYYY-MM-DD', V: Set of occupied lanes [0, 1, 2...]

        // --- 1. Render Multi-day Events ---
        multiDayEvents.forEach(event => {
            let currentDatePointer = parseDateAsLocal(event.start_date);
            const endDate = parseDateAsLocal(event.end_date);
            const eventStartDate = parseDateAsLocal(event.start_date);

            while (currentDatePointer <= endDate) {
                const isEventStart = currentDatePointer.getTime() === eventStartDate.getTime();
                const isWeekStart = currentDatePointer.getDay() === wp_sow;

                if (isEventStart || isWeekStart) {
                    const segmentStartDate = new Date(currentDatePointer);

                    // Calculate span for the current segment
                    let span = 1;
                    let lookahead = new Date(segmentStartDate);
                    lookahead.setDate(lookahead.getDate() + 1);
                    while (lookahead <= endDate && lookahead.getDay() !== wp_sow) {
                        span++;
                        lookahead.setDate(lookahead.getDate() + 1);
                    }

                    // Find an available lane for this segment using the calculated span
                    let laneIndex = 0;
                    while (true) {
                        let isLaneOccupied = false;
                        for (let i = 0; i < span; i++) {
                            let tempDate = new Date(segmentStartDate);
                            tempDate.setDate(tempDate.getDate() + i);
                            const dateStr = `${tempDate.getFullYear()}-${String(tempDate.getMonth() + 1).padStart(2, '0')}-${String(tempDate.getDate()).padStart(2, '0')}`;
                            if (dayLanes.has(dateStr) && dayLanes.get(dateStr).has(laneIndex)) {
                                isLaneOccupied = true;
                                break;
                            }
                        }
                        if (!isLaneOccupied) break; // Found a free lane
                        laneIndex++; // Try the next lane
                    }

                    const start_date_str = `${segmentStartDate.getFullYear()}-${String(segmentStartDate.getMonth() + 1).padStart(2, '0')}-${String(segmentStartDate.getDate()).padStart(2, '0')}`;
                    const dayCell = $(`.dame-calendar-day[data-date="${start_date_str}"]`);

                    if (dayCell.length) {
                        const width = `calc(${span * 100}% + ${span - 1}px)`;
                        const top = laneIndex * (EVENT_HEIGHT + EVENT_SPACING);
                        let classList = 'dame-event dame-event-duree';
                        if (isEventStart) classList += ' start';
                        if (lookahead > endDate) classList += ' end';

                        const eventHtml = `<div class="${classList}" style="background-color: ${event.color}; width: ${width}; top: ${top}px;">${event.title}</div>`;
                        dayCell.find('.events-container').append($(eventHtml).data('event', event));

                        // Mark lanes as occupied for the entire span
                        for (let i = 0; i < span; i++) {
                            let occupiedDate = new Date(segmentStartDate);
                            occupiedDate.setDate(occupiedDate.getDate() + i);
                            const occupiedDateStr = `${occupiedDate.getFullYear()}-${String(occupiedDate.getMonth() + 1).padStart(2, '0')}-${String(occupiedDate.getDate()).padStart(2, '0')}`;
                            if (!dayLanes.has(occupiedDateStr)) dayLanes.set(occupiedDateStr, new Set());
                            dayLanes.get(occupiedDateStr).add(laneIndex);
                        }
                    }
                    currentDatePointer.setDate(currentDatePointer.getDate() + span);
                } else {
                    currentDatePointer.setDate(currentDatePointer.getDate() + 1);
                }
            }
        });

        // --- 2. Render Single-day Events ---
        singleDayEvents.forEach(event => {
            const startDate = parseDateAsLocal(event.start_date);
            const dateStr = `${startDate.getFullYear()}-${String(startDate.getMonth() + 1).padStart(2, '0')}-${String(startDate.getDate()).padStart(2, '0')}`;
            const dayCell = $(`.dame-calendar-day[data-date="${dateStr}"]`);

            if (dayCell.length) {
                let occupiedLanes = 0;
                if (dayLanes.has(dateStr)) {
                    occupiedLanes = Math.max(...dayLanes.get(dateStr)) + 1;
                }
                const marginTop = occupiedLanes * (EVENT_HEIGHT + EVENT_SPACING);

                let ponctuelContainer = dayCell.find('.ponctuel-events-container');
                if (ponctuelContainer.length === 0) {
                    ponctuelContainer = $('<div class="ponctuel-events-container"></div>');
                    dayCell.find('.events-container').append(ponctuelContainer);
                }
                ponctuelContainer.css('margin-top', `${marginTop}px`);

                const timeText = event.all_day == '1' ? dame_agenda_ajax.i18n.all_day : `${event.start_time} - ${event.end_time}`;
                const eventHtml = `<div class="dame-event dame-event-ponctuel" style="border-left-color: ${event.color};">
                    <div class="event-time">${timeText}</div>
                    <div class="event-title">${event.title}</div>
                </div>`;
                ponctuelContainer.append($(eventHtml).data('event', event));
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
