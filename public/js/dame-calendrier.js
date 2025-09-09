document.addEventListener('DOMContentLoaded', function() {
    const calendarWrapper = document.getElementById('dame-calendar-wrapper');

    if (!calendarWrapper) {
        return;
    }

    // --- Tooltip Handling ---
    let tooltip = null;

    function showTooltip(e) {
        const eventEl = e.target.closest('.dame-event');
        if (!eventEl) return;

        // Create tooltip element
        tooltip = document.createElement('div');
        tooltip.id = 'dame-event-tooltip';

        const title = eventEl.dataset.title || '';
        const time = eventEl.dataset.time || '';
        const location = eventEl.dataset.location || '';
        const description = eventEl.dataset.description || '';

        tooltip.innerHTML = `
            <h4>${title}</h4>
            <p class="tooltip-meta">${time}</p>
            <p class="tooltip-meta">${location}</p>
            <p class="tooltip-desc">${description}</p>
        `;

        document.body.appendChild(tooltip);

        // Position tooltip near cursor
        positionTooltip(e);
    }

    function hideTooltip() {
        if (tooltip) {
            tooltip.remove();
            tooltip = null;
        }
    }

    function positionTooltip(e) {
        if (!tooltip) return;
        // Position tooltip to the bottom-right of the cursor
        const x = e.clientX + 15;
        const y = e.clientY + 15;

        // Adjust if tooltip goes off-screen
        const tooltipRect = tooltip.getBoundingClientRect();
        const bodyRect = document.body.getBoundingClientRect();

        let finalX = x;
        let finalY = y;

        if (x + tooltipRect.width > bodyRect.width) {
            finalX = e.clientX - tooltipRect.width - 15;
        }
        if (y + tooltipRect.height > window.innerHeight) {
            finalY = e.clientY - tooltipRect.height - 15;
        }

        tooltip.style.left = `${finalX}px`;
        tooltip.style.top = `${finalY}px`;
    }

    // Use event delegation for tooltips
    calendarWrapper.addEventListener('mouseover', showTooltip);
    calendarWrapper.addEventListener('mouseout', hideTooltip);
    calendarWrapper.addEventListener('mousemove', positionTooltip);


    // --- AJAX Navigation ---
    function handleNavClick(e) {
        const navButton = e.target.closest('.dame-calendar-nav');
        if (!navButton) return;

        const month = navButton.dataset.month;
        const year = navButton.dataset.year;

        fetchCalendar(month, year);
    }

    async function fetchCalendar(month, year) {
        calendarWrapper.style.opacity = '0.5';

        const formData = new FormData();
        formData.append('action', 'dame_get_calendar_month');
        formData.append('month', month);
        formData.append('year', year);
        formData.append('nonce', dame_calendar_ajax.nonce);

        try {
            const response = await fetch(dame_calendar_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Network response was not ok.');
            }

            const data = await response.json();

            if (data.success) {
                calendarWrapper.innerHTML = data.data.html;
            } else {
                throw new Error(data.data.message || 'Unknown error occurred.');
            }

        } catch (error) {
            console.error('Error fetching calendar:', error);
            // Optionally, display an error message to the user
        } finally {
            calendarWrapper.style.opacity = '1';
        }
    }

    // Use event delegation for navigation
    calendarWrapper.addEventListener('click', handleNavClick);
});
