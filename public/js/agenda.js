jQuery(document).ready(function($) {
    const wrapper = $('#dame-agenda-wrapper');
    if (wrapper.length === 0) return;

    const calendarGrid = $('#dame-calendar-grid');
    const weekdaysContainer = $('.dame-calendar-weekdays');
    const currentMonthDisplay = $('#dame-agenda-current-month');
    const prevMonthBtn = $('#dame-agenda-prev-month');
    const nextMonthBtn = $('#dame-agenda-next-month');
    const todayBtn = $('#dame-agenda-today');
    const filterToggleBtn = $('#dame-agenda-filter-toggle');
    const filterPanel = $('#dame-agenda-filter-panel');
    const searchInput = $('#dame-agenda-search-input');
    const tooltip = $('#dame-event-tooltip');
    const monthYearPicker = $('#dame-month-year-selector');
    const monthPickerToggle = $('.dame-agenda-month-picker-toggle');

    let currentDate = new Date();
    let searchTimeout;

    function formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function fetchAndRenderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

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

        const categories = $('.dame-agenda-cat-filter:checked').map(function() { return $(this).val(); }).get();
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

        dame_agenda_ajax.i18n.weekdays_short.forEach(day => {
            weekdaysContainer.append(`<div>${day}</div>`);
        });

        const firstDayOfMonth = new Date(year, month, 1);
        const lastDayOfMonth = new Date(year, month + 1, 0);
        const daysInMonth = lastDayOfMonth.getDate();
        const startDayOfWeek = (firstDayOfMonth.getDay() - dame_agenda_ajax.start_of_week + 7) % 7;

        const prevMonthDate = new Date(year, month, 0);
        const prevYear = prevMonthDate.getFullYear();
        const prevMonth = prevMonthDate.getMonth();
        const prevLastDay = prevMonthDate.getDate();
        for (let i = startDayOfWeek; i > 0; i--) {
            const day = prevLastDay - i + 1;
            const dateStr = `${prevYear}-${String(prevMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            calendarGrid.append(`<div class="dame-calendar-day other-month" data-date="${dateStr}"><div class="day-number">${day}</div><div class="events-container"></div></div>`);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();
            calendarGrid.append(`<div class="dame-calendar-day ${isToday ? 'today' : ''}" data-date="${dateStr}"><div class="day-number">${day}</div><div class="events-container"></div></div>`);
        }

        const nextMonthDate = new Date(year, month + 1, 1);
        const nextYear = nextMonthDate.getFullYear();
        const nextMonth = nextMonthDate.getMonth();
        const totalCells = startDayOfWeek + daysInMonth;
        const remainingCells = (totalCells % 7 === 0) ? 0 : 7 - (totalCells % 7);
        for (let day = 1; day <= remainingCells; day++) {
            const dateStr = `${nextYear}-${String(nextMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            calendarGrid.append(`<div class="dame-calendar-day other-month" data-date="${dateStr}"><div class="day-number">${day}</div><div class="events-container"></div></div>`);
        }

        renderEvents(events);
    }

    function parseDateAsLocal(dateStr) {
        const [year, month, day] = dateStr.split('-').map(Number);
        return new Date(year, month - 1, day);
    }

    function adjustRowHeights() {
        const dayCells = calendarGrid.find('.dame-calendar-day');
        if (dayCells.length === 0) return;

        const isMobile = window.innerWidth < 768;
        const weekCount = Math.ceil(dayCells.length / 7);
        const DAY_NUMBER_HEIGHT = isMobile ? 30 : 40;
        const MIN_CELL_HEIGHT = isMobile ? 40 : 120;

        for (let i = 0; i < weekCount; i++) {
            const weekCells = dayCells.slice(i * 7, (i + 1) * 7);
            let maxContentHeight = 0;

            weekCells.each(function() {
                const dayCell = $(this);
                let requiredContentHeight = 0;
                const ponctuelContainer = dayCell.find('.ponctuel-events-container');

                if (ponctuelContainer.length > 0) {
                    requiredContentHeight = ponctuelContainer.position().top + ponctuelContainer.outerHeight(true);
                } else {
                    let maxMultiDayBottom = 0;
                    dayCell.find('.dame-event-duree').each(function() {
                        const eventEl = $(this);
                        const eventBottom = eventEl.position().top + eventEl.outerHeight(true);
                        maxMultiDayBottom = Math.max(maxMultiDayBottom, eventBottom);
                    });
                    requiredContentHeight = maxMultiDayBottom;
                }
                maxContentHeight = Math.max(maxContentHeight, requiredContentHeight);
            });

            const finalHeight = Math.max(MIN_CELL_HEIGHT, maxContentHeight + DAY_NUMBER_HEIGHT);
            weekCells.css('height', `${finalHeight}px`);
        }
    }

    function renderEvents(events) {
        // Reset heights before rendering to ensure accurate calculations
        calendarGrid.find('.dame-calendar-day').css('height', '');

        const isMobile = window.innerWidth < 768;
        // On mobile, event height is dot (12px) + top/bottom margin (2*2px) = 16px
        const EVENT_HEIGHT = isMobile ? 16 : 32;
        const EVENT_SPACING = 2;
        const wp_sow = parseInt(dame_agenda_ajax.start_of_week, 10);

        events.sort((a, b) => {
            const aStart = parseDateAsLocal(a.start_date);
            const bStart = parseDateAsLocal(b.start_date);
            const aEnd = parseDateAsLocal(a.end_date);
            const bEnd = parseDateAsLocal(b.end_date);
            if (aStart < bStart) return -1;
            if (aStart > bStart) return 1;
            const aDuration = aEnd - aStart;
            const bDuration = bEnd - bStart;
            if (aDuration > bDuration) return -1;
            if (aDuration < bDuration) return 1;
            if (a.all_day && !b.all_day) return -1;
            if (!a.all_day && b.all_day) return 1;
            if (a.start_time < b.start_time) return -1;
            if (a.start_time > b.start_time) return 1;
            return a.title.localeCompare(b.title);
        });

        const dayLanes = new Map();

        events.forEach(event => {
            const startDate = parseDateAsLocal(event.start_date);
            const endDate = parseDateAsLocal(event.end_date);
            const isMultiDay = (endDate.getTime() > startDate.getTime());

            if (isMultiDay) {
                let currentDatePointer = new Date(startDate);
                while (currentDatePointer <= endDate) {
                    const isEventStart = currentDatePointer.getTime() === startDate.getTime();
                    const isWeekStart = currentDatePointer.getDay() === wp_sow;
                    if (isEventStart || isWeekStart) {
                        const segmentStartDate = new Date(currentDatePointer);
                        let span = 1;
                        let lookahead = new Date(segmentStartDate);
                        lookahead.setDate(lookahead.getDate() + 1);
                        while (lookahead <= endDate && lookahead.getDay() !== wp_sow) {
                            span++;
                            lookahead.setDate(lookahead.getDate() + 1);
                        }
                        let laneIndex = 0;
                        while (true) {
                            let isLaneOccupied = false;
                            for (let i = 0; i < span; i++) {
                                const checkDate = new Date(segmentStartDate);
                                checkDate.setDate(checkDate.getDate() + i);
                                const checkDateStr = formatDate(checkDate);
                                if (dayLanes.has(checkDateStr) && dayLanes.get(checkDateStr).has(laneIndex)) {
                                    isLaneOccupied = true;
                                    break;
                                }
                            }
                            if (!isLaneOccupied) break;
                            laneIndex++;
                        }
                        const segmentDateStr = formatDate(segmentStartDate);
                        const dayCell = $(`.dame-calendar-day[data-date="${segmentDateStr}"]`);
                        if (dayCell.length) {
                            const width = `calc(${span * 100}% + ${span - 1}px)`;
                            const top = laneIndex * (EVENT_HEIGHT + EVENT_SPACING);
                            const isSegmentEnd = (new Date(segmentStartDate.getTime() + (span - 1) * 86400000)).getTime() >= endDate.getTime();
                            let classList = 'dame-event dame-event-duree';
                            if (isEventStart) classList += ' start';
                            if (isSegmentEnd) classList += ' end';
                            const eventHtml = `<a href="${event.url}" class="dame-event-link"><div class="${classList}" style="background-color: ${event.color}; width: ${width}; top: ${top}px;">${event.title}</div></a>`;
                            dayCell.find('.events-container').append($(eventHtml).data('event', event));
                            for (let i = 0; i < span; i++) {
                                const occupiedDate = new Date(segmentStartDate);
                                occupiedDate.setDate(occupiedDate.getDate() + i);
                                const occupiedDateStr = formatDate(occupiedDate);
                                if (!dayLanes.has(occupiedDateStr)) dayLanes.set(occupiedDateStr, new Set());
                                dayLanes.get(occupiedDateStr).add(laneIndex);
                            }
                        }
                        currentDatePointer.setDate(currentDatePointer.getDate() + span);
                    } else {
                        currentDatePointer.setDate(currentDatePointer.getDate() + 1);
                    }
                }
            } else {
                const dateStr = formatDate(startDate);
                const dayCell = $(`.dame-calendar-day[data-date="${dateStr}"]`);
                if (dayCell.length) {
                    let occupiedLanesCount = dayLanes.has(dateStr) ? dayLanes.get(dateStr).size : 0;
                    const topPosition = occupiedLanesCount * (EVENT_HEIGHT + EVENT_SPACING);
                    let ponctuelContainer = dayCell.find('.ponctuel-events-container');
                    if (ponctuelContainer.length === 0) {
                        ponctuelContainer = $('<div class="ponctuel-events-container"></div>').css({
                            'position': 'absolute',
                            'top': `${topPosition}px`,
                            'left': '5px',
                            'right': '5px'
                        });
                        dayCell.find('.events-container').append(ponctuelContainer);
                    }
                    const timeText = event.all_day == '1' ? dame_agenda_ajax.i18n.all_day : `${event.start_time} - ${event.end_time}`;
                    const eventHtml = `<a href="${event.url}" class="dame-event-link">
                        <div class="dame-event dame-event-ponctuel" style="--event-color: ${event.color}; border-left-color: ${event.color};">
                            <div class="event-time">${timeText}</div>
                            <div class="event-title">${event.title}</div>
                        </div>
                    </a>`;
                    ponctuelContainer.append($(eventHtml).data('event', event));
                }
            }
        });

        setTimeout(adjustRowHeights, 0);
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

    todayBtn.on('click', function() {
        currentDate = new Date();
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
        if (window.innerWidth < 768) {
            return;
        }
        const eventData = $(this).closest('.dame-event-link').data('event');
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
        monthYearPicker.toggle();
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

    // Handler for the "Open in GPS" button
    $(document).on('click', '#dame-open-gps', function() {
        const button = $(this);
        const lat = button.data('lat');
        const lng = button.data('lng');

        if (!lat || !lng) {
            return;
        }

        // Check for iOS
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

        let url;
        if (isIOS) {
            // Apple Maps URL scheme
            url = `https://maps.apple.com/?q=${lat},${lng}`;
        } else {
            // Google Maps URL scheme for all other platforms
            url = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
        }

        window.open(url, '_blank');
    });
});
