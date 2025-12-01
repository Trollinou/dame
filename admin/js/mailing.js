
document.addEventListener('DOMContentLoaded', function() {
    // Radio button logic for selection method
    const methodRadios = document.querySelectorAll('input[name="dame_selection_method"]');
    const groupFilters = document.querySelector('.dame-group-filters');
    const manualFilters = document.querySelector('.dame-manual-filters');

    function toggleMethod() {
        if (document.querySelector('input[name="dame_selection_method"]:checked').value === 'group') {
            groupFilters.style.display = 'table-row';
            manualFilters.style.display = 'none';
        } else {
            groupFilters.style.display = 'none';
            manualFilters.style.display = 'table-row';
        }
    }
    methodRadios.forEach(radio => radio.addEventListener('change', toggleMethod));
    toggleMethod(); // Initial state

    // Logic to disable submit button based on message status
    const messageSelect = document.getElementById('dame_message_to_send');
    const submitButton = document.querySelector('input[type="submit"]');
    let statusNotice = document.getElementById('dame-status-notice');

    // Create the notice element if it doesn't exist
    if ( ! statusNotice ) {
        statusNotice = document.createElement('span');
        statusNotice.id = 'dame-status-notice';
        statusNotice.style.marginLeft = '10px';
        statusNotice.style.color = '#d63638';
        submitButton.parentNode.insertBefore(statusNotice, submitButton.nextSibling);
    }


    function checkMessageStatus() {
        if ( ! messageSelect.options.length ) {
            submitButton.disabled = true;
            return;
        }

        const selectedOption = messageSelect.options[messageSelect.selectedIndex];
        const status = selectedOption.getAttribute('data-status');
        const statuses = ['scheduled', 'sending', 'sent'];

        if (statuses.includes(status)) {
            submitButton.disabled = true;
            let statusText;
            switch (status) {
                case 'scheduled':
                    statusText = "Programmé";
                    break;
                case 'sending':
                    statusText = "Envoi en cours...";
                    break;
                case 'sent':
                    statusText = "Déjà envoyé";
                    break;
            }
            statusNotice.textContent = '(' + statusText + ')';
        } else {
            submitButton.disabled = false;
            statusNotice.textContent = '';
        }
    }

    messageSelect.addEventListener('change', checkMessageStatus);
    checkMessageStatus(); // Initial check
});
