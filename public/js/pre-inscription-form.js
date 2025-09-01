document.addEventListener('DOMContentLoaded', function () {
    // We call the autocomplete initializers here, as this script is loaded
    // after dame-geo-autocomplete.js, ensuring the function is available.
    if (typeof initBirthCityAutocomplete === 'function') {
        initBirthCityAutocomplete('dame_birth_city');
        initBirthCityAutocomplete('dame_legal_rep_1_commune_naissance');
        initBirthCityAutocomplete('dame_legal_rep_2_commune_naissance');
    }


    const birthDateInput = document.getElementById('dame_birth_date');
    if (!birthDateInput) {
        return;
    }

    const dynamicFields = document.getElementById('dame-dynamic-fields');
    const majeurFields = document.getElementById('dame-adherent-majeur-fields');
    const mineurFields = document.getElementById('dame-adherent-mineur-fields');

    const healthQuestionnaireLinkContainer = document.getElementById('health-questionnaire-link-container');
    const healthQuestionnaireLink = document.getElementById('health-questionnaire-link');
    // We assume the plugin is in wp-content/plugins/dame. This is the most common setup.
    const pdfBaseUrl = '/wp-content/plugins/dame/public/pdf/';
    const mineurPDF = pdfBaseUrl + 'questionnaire_sante_mineur.pdf';
    const majeurPDF = pdfBaseUrl + 'questionnaire_sante_majeur.pdf';

    // Adherent fields
    const emailInput = document.getElementById('dame_email');
    const phoneInput = document.getElementById('dame_phone_number');
    const address1Input = document.getElementById('dame_address_1');
    const address2Input = document.getElementById('dame_address_2');
    const postalCodeInput = document.getElementById('dame_postal_code');
    const cityInput = document.getElementById('dame_city');
    const lastNameInput = document.getElementById('dame_last_name');

    // Rep 1 fields
    const rep1FirstNameInput = document.getElementById('dame_legal_rep_1_first_name');
    const rep1LastNameInput = document.getElementById('dame_legal_rep_1_last_name');
    const rep1EmailInput = document.getElementById('dame_legal_rep_1_email');
    const rep1PhoneInput = document.getElementById('dame_legal_rep_1_phone');
    const rep1Address1Input = document.getElementById('dame_legal_rep_1_address_1');
    const rep1CityInput = document.getElementById('dame_legal_rep_1_city');
    const rep1RequiredInputs = [rep1FirstNameInput, rep1LastNameInput, rep1EmailInput, rep1PhoneInput, rep1Address1Input, rep1CityInput];


    birthDateInput.addEventListener('change', function () {
        const birthDate = new Date(this.value);
        if (isNaN(birthDate.getTime())) {
            dynamicFields.style.display = 'none';
            if (healthQuestionnaireLinkContainer) {
                healthQuestionnaireLinkContainer.style.display = 'none';
            }
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
            // Make rep 1 fields not required
            rep1RequiredInputs.forEach(input => input.required = false);

            if (healthQuestionnaireLink) {
                healthQuestionnaireLink.href = majeurPDF;
                healthQuestionnaireLink.textContent = 'Consulter le questionnaire pour Majeur';
                healthQuestionnaireLinkContainer.style.display = 'inline';
            }
        } else {
            majeurFields.style.display = 'none';
            mineurFields.style.display = 'block';
            prefillRep1();
            // Make rep 1 fields required
            rep1RequiredInputs.forEach(input => input.required = true);

            if (healthQuestionnaireLink) {
                healthQuestionnaireLink.href = mineurPDF;
                healthQuestionnaireLink.textContent = 'Consulter le questionnaire pour Mineur';
                healthQuestionnaireLinkContainer.style.display = 'inline';
            }
        }
    });

    // The keyup listeners are removed in favor of a direct call from the autocomplete script.

    // Handle Form Submission
    const form = document.getElementById('dame-pre-inscription-form');
    const messagesDiv = document.getElementById('dame-form-messages');

    if (form) {
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
                    messagesDiv.style.color = 'green';
                    messagesDiv.innerHTML = `<p>${data.data.message}</p>`; // Wrap initial message in a paragraph

                    if (data.data.health_questionnaire === 'oui') {
                        const medicalCertMessage = document.createElement('p');
                        medicalCertMessage.style.fontWeight = 'bold';
                        medicalCertMessage.style.color = 'red';
                        medicalCertMessage.innerHTML = `Afin de valider votre inscription auprès de la FFE, vous devez nous remettre un certificat médical, daté de moins de 6 mois, déclarant <strong>${data.data.full_name}</strong> apte à la pratique des échecs en et hors compétition.`;
                        messagesDiv.appendChild(medicalCertMessage);
                    } else if (data.data.health_questionnaire === 'non') {
                        const downloadButton = document.createElement('a');
                        downloadButton.href = `${dame_pre_inscription_ajax.ajax_url}?action=dame_generate_health_form&post_id=${data.data.post_id}&_wpnonce=${data.data.nonce}`;
                        downloadButton.className = 'button dame-button';
                        downloadButton.textContent = 'Télécharger mon attestation de santé à remettre signé';
                        downloadButton.style.marginTop = '1em';
                        downloadButton.style.display = 'inline-block';
                        messagesDiv.appendChild(downloadButton);

                        // Add parental authorization download button for minors
                        if (data.data.is_minor) {
                            const parentalAuthButton = document.createElement('a');
                            parentalAuthButton.href = `${dame_pre_inscription_ajax.ajax_url}?action=dame_generate_parental_auth&post_id=${data.data.post_id}&_wpnonce=${data.data.parental_auth_nonce}`;
                            parentalAuthButton.className = 'button dame-button';
                            parentalAuthButton.textContent = "Télécharger l'autorisation parentale a remettre signé";
                            parentalAuthButton.style.marginTop = '1em';
                            parentalAuthButton.style.marginLeft = '1em'; // Add some space between buttons
                            parentalAuthButton.style.display = 'inline-block';
                            messagesDiv.appendChild(parentalAuthButton);
                        }
                    }

                    // Instead of resetting, hide the form to show the success message and new options
                    form.style.display = 'none';
                    dynamicFields.style.display = 'none';

                    // Create a container for the new action buttons
                    const actionButtonsContainer = document.createElement('div');
                    actionButtonsContainer.style.marginTop = '1em';

                    // 1. Add PayAsso button if the URL is provided in the settings
                    if (data.data.payment_url) {
                        const paymentButton = document.createElement('a');
                        paymentButton.href = data.data.payment_url;
                        paymentButton.className = 'button dame-button';
                        paymentButton.textContent = 'Aller sur PayAsso pour votre règlement';
                        paymentButton.target = '_blank'; // Open in a new tab
                        paymentButton.style.textDecoration = 'none';
                        paymentButton.style.marginRight = '1em';
                        actionButtonsContainer.appendChild(paymentButton);
                    }

                    // 2. Add "New Adhesion" button
                    const newAdhesionButton = document.createElement('button');
                    newAdhesionButton.id = 'dame-new-adhesion-button';
                    newAdhesionButton.type = 'button'; // Important to prevent form submission
                    newAdhesionButton.className = 'button dame-button';
                    newAdhesionButton.textContent = 'Saisir une nouvelle adhésion';
                    actionButtonsContainer.appendChild(newAdhesionButton);

                    // Add the buttons container after all other messages
                    messagesDiv.appendChild(actionButtonsContainer);

                } else {
                    messagesDiv.style.color = 'red';
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

        // Event delegation for the "Saisir une nouvelle adhésion" button
        messagesDiv.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'dame-new-adhesion-button') {
                e.preventDefault();

                // Fields to clear for the new adhesion
                document.getElementById('dame_first_name').value = '';
                document.getElementById('dame_last_name').value = '';
                document.getElementById('dame_birth_date').value = '';
                document.getElementById('dame_birth_city').value = '';

                // Also clear radio buttons for health questionnaire
                const healthRadios = form.querySelectorAll('input[name="dame_health_questionnaire"]');
                healthRadios.forEach(radio => radio.checked = false);

                // Hide the dynamic fields section until a new birth date is entered
                const dynamicFields = document.getElementById('dame-dynamic-fields');
                if (dynamicFields) {
                    dynamicFields.style.display = 'none';
                    // Clear all inputs within the dynamic sections as well
                    const dynamicInputs = dynamicFields.querySelectorAll('input');
                    dynamicInputs.forEach(input => input.value = '');
                }

                // Hide the success message area
                messagesDiv.style.display = 'none';
                messagesDiv.innerHTML = '';

                // Show the form again
                form.style.display = 'block';

                // Scroll back to the top of the form
                form.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
});

function prefillRep1() {
    // Define all elements here again to ensure they are available in the global scope
    // and to avoid errors if the function is called before DOMContentLoaded.
    const lastNameInput = document.getElementById('dame_last_name');
    const emailInput = document.getElementById('dame_email');
    const phoneInput = document.getElementById('dame_phone_number');
    const address1Input = document.getElementById('dame_address_1');
    const address2Input = document.getElementById('dame_address_2');
    const postalCodeInput = document.getElementById('dame_postal_code');
    const cityInput = document.getElementById('dame_city');

    const rep1LastNameInput = document.getElementById('dame_legal_rep_1_last_name');
    const rep1EmailInput = document.getElementById('dame_legal_rep_1_email');
    const rep1PhoneInput = document.getElementById('dame_legal_rep_1_phone');
    const rep1Address1Input = document.getElementById('dame_legal_rep_1_address_1');
    const rep1Address2Input = document.getElementById('dame_legal_rep_1_address_2');
    const rep1PostalCodeInput = document.getElementById('dame_legal_rep_1_postal_code');
    const rep1CityInput = document.getElementById('dame_legal_rep_1_city');

    // Check if all elements exist before trying to copy values
    if (lastNameInput && emailInput && phoneInput && address1Input && address2Input && postalCodeInput && cityInput &&
        rep1LastNameInput && rep1EmailInput && rep1PhoneInput && rep1Address1Input && rep1Address2Input && rep1PostalCodeInput && rep1CityInput) {

        rep1LastNameInput.value = lastNameInput.value;
        rep1EmailInput.value = emailInput.value;
        rep1PhoneInput.value = phoneInput.value;
        rep1Address1Input.value = address1Input.value;
        rep1Address2Input.value = address2Input.value;
        rep1PostalCodeInput.value = postalCodeInput.value;
        rep1CityInput.value = cityInput.value;
    }
}
