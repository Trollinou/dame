document.addEventListener('DOMContentLoaded', function () {
    // Auto-select department from postal code
    const postalCodeInput = document.getElementById('dame_postal_code');
    const departmentSelect = document.getElementById('dame_department');

    if (postalCodeInput && departmentSelect) {
        postalCodeInput.addEventListener('keyup', function () {
            const postalCode = this.value;
            if (postalCode.length >= 2) {
                let departmentCode = postalCode.substring(0, 2);

                if (departmentCode === '20') {
                    return;
                }

                for (let i = 0; i < departmentSelect.options.length; i++) {
                    const option = departmentSelect.options[i];
                    if (option.value === departmentCode) {
                        departmentSelect.value = departmentCode;
                        break;
                    }
                }
            }
        });
    }

    // Auto-set membership status to 'Active' when date is entered
    const membershipDateInput = document.getElementById('dame_membership_date');
    const membershipStatusSelect = document.getElementById('dame_membership_status');

    if (membershipDateInput && membershipStatusSelect) {
        membershipDateInput.addEventListener('change', function() {
            if (this.value) {
                membershipStatusSelect.value = 'A';
            }
        });
    }
});
