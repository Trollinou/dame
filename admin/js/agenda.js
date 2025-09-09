document.addEventListener('DOMContentLoaded', function() {
    const allDayCheckbox = document.getElementById('dame_event_allday');
    const startTimeField = document.getElementById('dame_event_start_time').parentElement;
    const endTimeField = document.getElementById('dame_event_end_time').parentElement;

    function toggleTimeFields() {
        if (allDayCheckbox.checked) {
            startTimeField.style.display = 'none';
            endTimeField.style.display = 'none';
        } else {
            startTimeField.style.display = '';
            endTimeField.style.display = '';
        }
    }

    if (allDayCheckbox) {
        // Initial check on page load
        toggleTimeFields();

        // Add event listener for changes
        allDayCheckbox.addEventListener('change', toggleTimeFields);
    }
});
