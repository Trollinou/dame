document.addEventListener('DOMContentLoaded', function () {
    const postalCodeInput = document.getElementById('dame_postal_code');
    const departmentSelect = document.getElementById('dame_department');

    if (postalCodeInput && departmentSelect) {
        postalCodeInput.addEventListener('keyup', function () {
            const postalCode = this.value;
            if (postalCode.length >= 2) {
                let departmentCode = postalCode.substring(0, 2);

                // Handle Corsica edge case
                if (departmentCode === '20') {
                    // Cannot distinguish between 2A and 2B from '20'
                    // We can clear the selection or just let the user choose.
                    // For now, we do nothing and let the user select manually.
                    return;
                }

                for (let i = 0; i < departmentSelect.options.length; i++) {
                    const option = departmentSelect.options[i];
                    // Check if the option value (e.g., "75") matches the department code
                    if (option.value === departmentCode) {
                        departmentSelect.value = departmentCode;
                        break;
                    }
                }
            }
        });
    }
});
