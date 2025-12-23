document.addEventListener('DOMContentLoaded', function () {

    // --- Usage Name Fallback Logic (Specific to Adherent CPT) ---
    // This logic copies the birth name to the usage name if the usage name is empty.
    const birthNameInput = document.getElementById('dame_birth_name');
    const lastNameInput = document.getElementById('dame_last_name');

    if (birthNameInput && lastNameInput) {
        birthNameInput.addEventListener('blur', function() {
            if (this.value && !lastNameInput.value) {
                lastNameInput.value = this.value;
            }
        });
    }

});
