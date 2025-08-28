document.addEventListener('DOMContentLoaded', function () {
    const birthDateInput = document.getElementById('dame_birth_date');
    if (!birthDateInput) {
        return;
    }

    const dynamicFields = document.getElementById('dame-dynamic-fields');
    const majeurFields = document.getElementById('dame-adherent-majeur-fields');
    const mineurFields = document.getElementById('dame-adherent-mineur-fields');

    // Adherent fields
    const emailInput = document.getElementById('dame_email');
    const phoneInput = document.getElementById('dame_phone_number');
    const address1Input = document.getElementById('dame_address_1');
    const address2Input = document.getElementById('dame_address_2');
    const postalCodeInput = document.getElementById('dame_postal_code');
    const cityInput = document.getElementById('dame_city');
    const lastNameInput = document.getElementById('dame_last_name');

    // Rep 1 fields
    const rep1LastNameInput = document.getElementById('dame_legal_rep_1_last_name');
    const rep1EmailInput = document.getElementById('dame_legal_rep_1_email');
    const rep1PhoneInput = document.getElementById('dame_legal_rep_1_phone');
    const rep1Address1Input = document.getElementById('dame_legal_rep_1_address_1');
    const rep1Address2Input = document.getElementById('dame_legal_rep_1_address_2');
    const rep1PostalCodeInput = document.getElementById('dame_legal_rep_1_postal_code');
    const rep1CityInput = document.getElementById('dame_legal_rep_1_city');


    birthDateInput.addEventListener('change', function () {
        const birthDate = new Date(this.value);
        if (isNaN(birthDate.getTime())) {
            dynamicFields.style.display = 'none';
            return;
        }

        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        dynamicFields.style.display = 'block';

        if (age >= 18) {
            majeurFields.style.display = 'block';
            mineurFields.style.display = 'none';
            // Clear all inputs within the minor fields container to prevent submission of hidden data
            const minorInputs = mineurFields.querySelectorAll('input');
            minorInputs.forEach(input => {
                input.value = '';
            });
        } else {
            majeurFields.style.display = 'none';
            mineurFields.style.display = 'block';
            prefillRep1();
        }
    });

    function prefillRep1() {
        rep1LastNameInput.value = lastNameInput.value;
        rep1EmailInput.value = emailInput.value;
        rep1PhoneInput.value = phoneInput.value;
        rep1Address1Input.value = address1Input.value;
        rep1Address2Input.value = address2Input.value;
        rep1PostalCodeInput.value = postalCodeInput.value;
        rep1CityInput.value = cityInput.value;
    }

    // Add listeners to adherent fields to update rep1 fields in real-time if minor form is visible
    [lastNameInput, emailInput, phoneInput, address1Input, address2Input, postalCodeInput, cityInput].forEach(input => {
        input.addEventListener('keyup', function() {
            if (mineurFields.style.display === 'block') {
                prefillRep1();
            }
        });
    });


    // Handle Form Submission
    const form = document.getElementById('dame-pre-inscription-form');
    const messagesDiv = document.getElementById('dame-form-messages');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        messagesDiv.style.display = 'none';
        messagesDiv.innerHTML = '';
        messagesDiv.style.color = 'red'; // Default to red for errors

        const formData = new FormData(form);
        formData.append('action', 'dame_submit_pre_inscription');

        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Envoi en cours...';

        // Assumes `dame_pre_inscription_ajax.ajax_url` is localized
        fetch(dame_pre_inscription_ajax.ajax_url, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messagesDiv.style.color = 'red'; // As requested for success message
                messagesDiv.innerHTML = data.data.message;
                form.reset();
                dynamicFields.style.display = 'none';
            } else {
                messagesDiv.innerHTML = data.data.message;
            }
            messagesDiv.style.display = 'block';
            // Scroll to top of the page to make the message visible
            window.scrollTo({ top: 0, behavior: 'smooth' });
        })
        .catch(error => {
            console.error('Error:', error);
            messagesDiv.innerHTML = 'Une erreur inattendue est survenue.';
            messagesDiv.style.display = 'block';
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = 'Valider ma préinscription';
        });
    });
});
