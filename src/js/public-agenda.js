document.addEventListener('DOMContentLoaded', () => {
	const wrapper = document.getElementById('dame-agenda-wrapper');
	if (!wrapper) {
		return;
	}

	const calendarGrid = document.getElementById('dame-calendar-grid');
	const weekdaysContainer = wrapper.querySelector('.dame-calendar-weekdays');
	const currentMonthDisplay = document.getElementById(
		'dame-agenda-current-month'
	);
	const prevMonthBtn = document.getElementById('dame-agenda-prev-month');
	const nextMonthBtn = document.getElementById('dame-agenda-next-month');
	const todayBtn = document.getElementById('dame-agenda-today');
	const filterToggleBtn = document.getElementById(
		'dame-agenda-filter-toggle'
	);
	const filterPanel = document.getElementById('dame-agenda-filter-panel');
	const searchInput = document.getElementById('dame-agenda-search-input');
	const tooltip = document.getElementById('dame-event-tooltip');
	const monthYearPicker = document.getElementById('dame-month-year-selector');
	const monthPickerToggle = wrapper.querySelector(
		'.dame-agenda-month-picker-toggle'
	);

	let currentDate = new Date();
	let searchTimeout;
	const eventsMap = new Map();

	function updateURL(date) {
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const newUrl = new URL(window.location);
		newUrl.searchParams.set('month', `${year}-${month}`);
		history.pushState({ month: `${year}-${month}` }, '', newUrl);
	}

	function formatDate(date) {
		const y = date.getFullYear();
		const m = String(date.getMonth() + 1).padStart(2, '0');
		const d = String(date.getDate()).padStart(2, '0');
		return `${y}-${m}-${d}`;
	}

	async function fetchAndRenderCalendar() {
		const year = currentDate.getFullYear();
		const month = currentDate.getMonth();

		const firstDayOfMonth = new Date(year, month, 1);
		const lastDayOfMonth = new Date(year, month + 1, 0);
		const daysInMonth = lastDayOfMonth.getDate();
		const startDayOfWeek =
			(firstDayOfMonth.getDay() -
				Number(dame_agenda_ajax.start_of_week) +
				7) %
			7;

		const gridStartDate = new Date(firstDayOfMonth);
		gridStartDate.setDate(gridStartDate.getDate() - startDayOfWeek);

		const totalCellsBeforeNextMonth = startDayOfWeek + daysInMonth;
		const remainingCells =
			totalCellsBeforeNextMonth % 7 === 0
				? 0
				: 7 - (totalCellsBeforeNextMonth % 7);
		const totalGridDays = totalCellsBeforeNextMonth + remainingCells;

		const gridEndDate = new Date(gridStartDate);
		gridEndDate.setDate(gridEndDate.getDate() + totalGridDays - 1);

		const checkedCategories = Array.from(
			wrapper.querySelectorAll('.dame-agenda-cat-filter:checked')
		).map((el) => el.value);

		const uncheckedCategories = Array.from(
			wrapper.querySelectorAll('.dame-agenda-cat-filter:not(:checked)')
		).map((el) => el.value);

		const searchTerm = searchInput ? searchInput.value : '';

		if (calendarGrid) {
			calendarGrid.style.opacity = '0.5';
		}

		const formData = new FormData();
		formData.append('action', 'dame_get_agenda_events');
		formData.append('nonce', dame_agenda_ajax.nonce);
		formData.append('start_date', formatDate(gridStartDate));
		formData.append('end_date', formatDate(gridEndDate));
		formData.append('search', searchTerm);

		checkedCategories.forEach((cat) => {
			formData.append('categories[]', cat);
		});
		uncheckedCategories.forEach((cat) => {
			formData.append('unchecked_categories[]', cat);
		});

		try {
			const response = await fetch(dame_agenda_ajax.ajax_url, {
				method: 'POST',
				body: formData,
			});
			const result = await response.json();

			if (result.success) {
				renderCalendar(year, month, result.data);
			} else if (calendarGrid) {
				calendarGrid.innerHTML = '<p>Error loading events.</p>';
			}
		} catch {
			if (calendarGrid) {
				calendarGrid.innerHTML = '<p>Error loading events.</p>';
			}
		} finally {
			if (calendarGrid) {
				calendarGrid.style.opacity = '1';
			}
		}
	}

	function renderCalendar(year, month, events) {
		if (currentMonthDisplay) {
			currentMonthDisplay.textContent = `${dame_agenda_ajax.i18n.months[month]} ${year}`;
		}
		if (calendarGrid) {
			calendarGrid.innerHTML = '';
		}
		if (weekdaysContainer) {
			weekdaysContainer.innerHTML = '';
			dame_agenda_ajax.i18n.weekdays_short.forEach((day) => {
				const div = document.createElement('div');
				div.textContent = day;
				weekdaysContainer.appendChild(div);
			});
		}

		const firstDayOfMonth = new Date(year, month, 1);
		const lastDayOfMonth = new Date(year, month + 1, 0);
		const daysInMonth = lastDayOfMonth.getDate();
		const startDayOfWeek =
			(firstDayOfMonth.getDay() -
				Number(dame_agenda_ajax.start_of_week) +
				7) %
			7;

		const prevMonthDate = new Date(year, month, 0);
		const prevYear = prevMonthDate.getFullYear();
		const prevMonth = prevMonthDate.getMonth();
		const prevLastDay = prevMonthDate.getDate();

		for (let i = startDayOfWeek; i > 0; i--) {
			const day = prevLastDay - i + 1;
			const dateStr = `${prevYear}-${String(prevMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
			if (calendarGrid) {
				const cell = document.createElement('div');
				cell.className = 'dame-calendar-day other-month';
				cell.dataset.date = dateStr;
				cell.innerHTML = `<div class="day-number">${day}</div><div class="events-container"></div>`;
				calendarGrid.appendChild(cell);
			}
		}

		for (let day = 1; day <= daysInMonth; day++) {
			const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
			const isToday =
				new Date().toDateString() ===
				new Date(year, month, day).toDateString();
			if (calendarGrid) {
				const cell = document.createElement('div');
				cell.className = `dame-calendar-day ${isToday ? 'today' : ''}`;
				cell.dataset.date = dateStr;
				cell.innerHTML = `<div class="day-number">${day}</div><div class="events-container"></div>`;
				calendarGrid.appendChild(cell);
			}
		}

		const nextMonthDate = new Date(year, month + 1, 1);
		const nextYear = nextMonthDate.getFullYear();
		const nextMonth = nextMonthDate.getMonth();
		const totalCells = startDayOfWeek + daysInMonth;
		const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);

		for (let day = 1; day <= remainingCells; day++) {
			const dateStr = `${nextYear}-${String(nextMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
			if (calendarGrid) {
				const cell = document.createElement('div');
				cell.className = 'dame-calendar-day other-month';
				cell.dataset.date = dateStr;
				cell.innerHTML = `<div class="day-number">${day}</div><div class="events-container"></div>`;
				calendarGrid.appendChild(cell);
			}
		}

		renderEvents(events);
	}

	function parseDateAsLocal(dateStr) {
		const [year, month, day] = dateStr.split('-').map(Number);
		return new Date(year, month - 1, day);
	}

	function adjustRowHeights() {
		if (!calendarGrid) {
			return;
		}
		const dayCells = Array.from(
			calendarGrid.querySelectorAll('.dame-calendar-day')
		);
		if (dayCells.length === 0) {
			return;
		}

		const isMobile = window.innerWidth < 768;
		const weekCount = Math.ceil(dayCells.length / 7);
		const DAY_NUMBER_HEIGHT = isMobile ? 30 : 40;
		const MIN_CELL_HEIGHT = isMobile ? 40 : 120;

		for (let i = 0; i < weekCount; i++) {
			const weekCells = dayCells.slice(i * 7, (i + 1) * 7);
			let maxContentHeight = 0;

			weekCells.forEach((dayCell) => {
				let requiredContentHeight = 0;
				const ponctuelContainer = dayCell.querySelector(
					'.ponctuel-events-container'
				);

				if (ponctuelContainer) {
					requiredContentHeight =
						ponctuelContainer.offsetTop +
						ponctuelContainer.offsetHeight;
				} else {
					let maxMultiDayBottom = 0;
					dayCell
						.querySelectorAll('.dame-event-duree')
						.forEach((eventEl) => {
							const eventBottom =
								eventEl.offsetTop + eventEl.offsetHeight;
							maxMultiDayBottom = Math.max(
								maxMultiDayBottom,
								eventBottom
							);
						});
					requiredContentHeight = maxMultiDayBottom;
				}
				maxContentHeight = Math.max(
					maxContentHeight,
					requiredContentHeight
				);
			});

			const finalHeight = Math.max(
				MIN_CELL_HEIGHT,
				maxContentHeight + DAY_NUMBER_HEIGHT
			);
			weekCells.forEach((cell) => {
				cell.style.height = `${finalHeight}px`;
			});
		}
	}

	function renderEvents(events) {
		eventsMap.clear();

		if (calendarGrid) {
			calendarGrid
				.querySelectorAll('.dame-calendar-day')
				.forEach((cell) => {
					cell.style.height = '';
				});
		}

		const isMobile = window.innerWidth < 768;
		const EVENT_HEIGHT = isMobile ? 16 : 32;
		const EVENT_SPACING = 2;
		const wp_sow = parseInt(dame_agenda_ajax.start_of_week, 10);

		events.sort((a, b) => {
			const aStart = parseDateAsLocal(a.start_date);
			const bStart = parseDateAsLocal(b.start_date);

			if (aStart < bStart) {
				return -1;
			}
			if (aStart > bStart) {
				return 1;
			}

			const aEnd = parseDateAsLocal(a.end_date);
			const bEnd = parseDateAsLocal(b.end_date);
			const aDuration = aEnd - aStart;
			const bDuration = bEnd - bStart;
			if (aDuration > bDuration) {
				return -1;
			}
			if (aDuration < bDuration) {
				return 1;
			}
			if (a.all_day && !b.all_day) {
				return -1;
			}
			if (!a.all_day && b.all_day) {
				return 1;
			}
			if (a.start_time < b.start_time) {
				return -1;
			}
			if (a.start_time > b.start_time) {
				return 1;
			}
			return a.title.localeCompare(b.title);
		});

		const dayLanes = new Map();

		events.forEach((event, eventIdx) => {
			const eventId = event.id || `evt_${eventIdx}`;
			eventsMap.set(String(eventId), event);

			const startDate = parseDateAsLocal(event.start_date);
			const endDate = parseDateAsLocal(event.end_date);
			const isMultiDay = endDate.getTime() > startDate.getTime();

			if (isMultiDay) {
				const currentDatePointer = new Date(startDate);
				while (currentDatePointer <= endDate) {
					const isEventStart =
						currentDatePointer.getTime() === startDate.getTime();
					const isWeekStart = currentDatePointer.getDay() === wp_sow;
					if (isEventStart || isWeekStart) {
						const segmentStartDate = new Date(currentDatePointer);
						let span = 1;
						const lookahead = new Date(segmentStartDate);
						lookahead.setDate(lookahead.getDate() + 1);
						while (
							lookahead <= endDate &&
							lookahead.getDay() !== wp_sow
						) {
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
								if (
									dayLanes.has(checkDateStr) &&
									dayLanes.get(checkDateStr).has(laneIndex)
								) {
									isLaneOccupied = true;
									break;
								}
							}
							if (!isLaneOccupied) {
								break;
							}
							laneIndex++;
						}
						const segmentDateStr = formatDate(segmentStartDate);
						const dayCell = calendarGrid?.querySelector(
							`.dame-calendar-day[data-date="${segmentDateStr}"]`
						);
						if (dayCell) {
							const width = `calc(${span * 100}% + ${span - 1}px)`;
							const top =
								laneIndex * (EVENT_HEIGHT + EVENT_SPACING);
							const isSegmentEnd =
								new Date(
									segmentStartDate.getTime() +
										(span - 1) * 86400000
								).getTime() >= endDate.getTime();
							let classList = 'dame-event dame-event-duree';
							if (isEventStart) {
								classList += ' start';
							}
							if (isSegmentEnd) {
								classList += ' end';
							}

							const bgColor =
								event.status === 'private'
									? '#c9a0dc'
									: event.color;
							let styleAttr = `background-color: ${bgColor}; width: ${width}; top: ${top}px;`;
							if (event.text_color) {
								styleAttr += ` color: ${event.text_color};`;
							}

							const container =
								dayCell.querySelector('.events-container');
							if (container) {
								const link = document.createElement('a');
								link.href = event.url;
								link.className = 'dame-event-link';
								link.dataset.eventId = String(eventId);
								link.innerHTML = `<div class="${classList}" style="${styleAttr}">${event.title}</div>`;
								container.appendChild(link);
							}

							for (let i = 0; i < span; i++) {
								const occupiedDate = new Date(segmentStartDate);
								occupiedDate.setDate(
									occupiedDate.getDate() + i
								);
								const occupiedDateStr =
									formatDate(occupiedDate);
								if (!dayLanes.has(occupiedDateStr)) {
									dayLanes.set(occupiedDateStr, new Set());
								}
								dayLanes.get(occupiedDateStr).add(laneIndex);
							}
						}
						currentDatePointer.setDate(
							currentDatePointer.getDate() + span
						);
					} else {
						currentDatePointer.setDate(
							currentDatePointer.getDate() + 1
						);
					}
				}
			} else {
				const dateStr = formatDate(startDate);
				const dayCell = calendarGrid?.querySelector(
					`.dame-calendar-day[data-date="${dateStr}"]`
				);
				if (dayCell) {
					const occupiedLanesCount = dayLanes.has(dateStr)
						? dayLanes.get(dateStr).size
						: 0;
					const topPosition =
						occupiedLanesCount * (EVENT_HEIGHT + EVENT_SPACING);
					let ponctuelContainer = dayCell.querySelector(
						'.ponctuel-events-container'
					);
					if (!ponctuelContainer) {
						ponctuelContainer = document.createElement('div');
						ponctuelContainer.className =
							'ponctuel-events-container';
						ponctuelContainer.style.position = 'absolute';
						ponctuelContainer.style.top = `${topPosition}px`;
						ponctuelContainer.style.left = '5px';
						ponctuelContainer.style.right = '5px';
						dayCell
							.querySelector('.events-container')
							?.appendChild(ponctuelContainer);
					}
					const timeText =
						event.all_day === '1'
							? dame_agenda_ajax.i18n.all_day
							: `${event.start_time} - ${event.end_time}`;
					let styleAttr = `--event-color: ${event.color}; border-left-color: ${event.color};`;
					if (event.status === 'private') {
						styleAttr += ` background-color: #c9a0dc;`;
					} else if (event.background_color) {
						styleAttr += ` background-color: ${event.background_color};`;
					}

					const link = document.createElement('a');
					link.href = event.url;
					link.className = 'dame-event-link';
					link.dataset.eventId = String(eventId);
					link.innerHTML = `
						<div class="dame-event dame-event-ponctuel" style="${styleAttr}">
							<div class="event-time">${timeText}</div>
							<div class="event-title">${event.title}</div>
						</div>
					`;
					ponctuelContainer.appendChild(link);
				}
			}
		});

		setTimeout(adjustRowHeights, 0);
	}

	// Event Handlers
	if (prevMonthBtn) {
		prevMonthBtn.addEventListener('click', () => {
			currentDate.setMonth(currentDate.getMonth() - 1);
			updateURL(currentDate);
			fetchAndRenderCalendar();
		});
	}

	if (nextMonthBtn) {
		nextMonthBtn.addEventListener('click', () => {
			currentDate.setMonth(currentDate.getMonth() + 1);
			updateURL(currentDate);
			fetchAndRenderCalendar();
		});
	}

	if (todayBtn) {
		todayBtn.addEventListener('click', () => {
			currentDate = new Date();
			updateURL(currentDate);
			fetchAndRenderCalendar();
		});
	}

	if (filterToggleBtn && filterPanel) {
		filterToggleBtn.addEventListener('click', (e) => {
			e.stopPropagation();
			filterPanel.style.display =
				filterPanel.style.display === 'none' ||
				!filterPanel.style.display
					? 'block'
					: 'none';
		});
	}

	document.addEventListener('click', (e) => {
		if (
			filterPanel &&
			filterToggleBtn &&
			!filterPanel.contains(e.target) &&
			!filterToggleBtn.contains(e.target)
		) {
			filterPanel.style.display = 'none';
		}
	});

	if (filterPanel) {
		filterPanel.addEventListener('change', (e) => {
			if (e.target.classList.contains('dame-agenda-cat-filter')) {
				const isChecked = e.target.checked;
				const parentLi = e.target.closest('li');
				if (parentLi) {
					parentLi
						.querySelectorAll('ul input.dame-agenda-cat-filter')
						.forEach((childInput) => {
							childInput.checked = isChecked;
						});
				}
				fetchAndRenderCalendar();
			}
		});
	}

	if (searchInput) {
		const onSearch = () => {
			clearTimeout(searchTimeout);
			searchTimeout = setTimeout(fetchAndRenderCalendar, 500);
		};
		searchInput.addEventListener('keyup', onSearch);
		searchInput.addEventListener('input', onSearch);
	}

	// Tooltip
	if (calendarGrid && tooltip) {
		calendarGrid.addEventListener('mouseover', (e) => {
			if (window.innerWidth < 768) {
				return;
			}
			const eventEl = e.target.closest('.dame-event');
			if (!eventEl) {
				return;
			}
			const link = eventEl.closest('.dame-event-link');
			const eventId = link?.dataset.eventId;
			const eventData = eventId ? eventsMap.get(eventId) : null;
			if (!eventData) {
				return;
			}

			const timeText =
				eventData.all_day === '1'
					? dame_agenda_ajax.i18n.all_day
					: `${eventData.start_time} - ${eventData.end_time}`;

			let tooltipHtml = `<h4>${eventData.title}</h4>`;
			tooltipHtml += `<p>${timeText}</p>`;
			if (eventData.location) {
				tooltipHtml += `<p>${eventData.location}</p>`;
			}
			tooltipHtml += `<div class="tooltip-description">${eventData.description}</div>`;

			tooltip.innerHTML = tooltipHtml;
			tooltip.style.display = 'block';
			tooltip.style.top = `${e.pageY + 10}px`;
			tooltip.style.left = `${e.pageX + 10}px`;
		});

		calendarGrid.addEventListener('mousemove', (e) => {
			if (window.innerWidth < 768 || tooltip.style.display === 'none') {
				return;
			}
			tooltip.style.top = `${e.pageY + 10}px`;
			tooltip.style.left = `${e.pageX + 10}px`;
		});

		calendarGrid.addEventListener('mouseout', (e) => {
			const eventEl = e.target.closest('.dame-event');
			if (eventEl && !eventEl.contains(e.relatedTarget)) {
				tooltip.style.display = 'none';
			}
		});
	}

	// Month/Year Picker
	function renderMonthPicker() {
		const year = currentDate.getFullYear();
		const yearEl = document.getElementById('dame-selector-year');
		if (yearEl) {
			yearEl.textContent = String(year);
		}
		const monthGrid = wrapper.querySelector('.dame-month-grid');
		if (!monthGrid) {
			return;
		}
		monthGrid.innerHTML = '';
		const currentMonth = currentDate.getMonth();

		dame_agenda_ajax.i18n.months.forEach((monthName, index) => {
			const monthEl = document.createElement('span');
			monthEl.textContent = monthName;
			if (index === currentMonth) {
				monthEl.classList.add('selected');
			}
			monthEl.addEventListener('click', () => {
				currentDate.setMonth(index);
				fetchAndRenderCalendar();
				if (monthYearPicker) {
					monthYearPicker.style.display = 'none';
				}
			});
			monthGrid.appendChild(monthEl);
		});
	}

	if (monthPickerToggle && monthYearPicker) {
		monthPickerToggle.addEventListener('click', (e) => {
			e.stopPropagation();
			renderMonthPicker();
			monthYearPicker.style.display =
				monthYearPicker.style.display === 'none' ||
				!monthYearPicker.style.display
					? 'block'
					: 'none';
		});
	}

	const prevYearBtn = document.getElementById('dame-selector-prev-year');
	if (prevYearBtn) {
		prevYearBtn.addEventListener('click', () => {
			currentDate.setFullYear(currentDate.getFullYear() - 1);
			renderMonthPicker();
		});
	}

	const nextYearBtn = document.getElementById('dame-selector-next-year');
	if (nextYearBtn) {
		nextYearBtn.addEventListener('click', () => {
			currentDate.setFullYear(currentDate.getFullYear() + 1);
			renderMonthPicker();
		});
	}

	document.addEventListener('click', (e) => {
		if (
			monthYearPicker &&
			monthPickerToggle &&
			!monthYearPicker.contains(e.target) &&
			!monthPickerToggle.contains(e.target)
		) {
			monthYearPicker.style.display = 'none';
		}
	});

	// Initial Load & History Navigation
	function handleHistoryChange() {
		const params = new URLSearchParams(window.location.search);
		const monthParam = params.get('month');
		if (monthParam) {
			const [year, month] = monthParam.split('-').map(Number);
			currentDate = new Date(year, month - 1, 1);
		} else {
			currentDate = new Date();
		}
		fetchAndRenderCalendar();
	}

	window.addEventListener('popstate', handleHistoryChange);
	handleHistoryChange();
});
