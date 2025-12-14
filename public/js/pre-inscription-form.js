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
    const birthCityInput = document.getElementById('dame_birth_city');
    const birthCityRequiredIndicator = document.getElementById('dame_birth_city_required_indicator');
    const emailInput = document.getElementById('dame_email');
    const phoneInput = document.getElementById('dame_phone_number');
    const address1Input = document.getElementById('dame_address_1');
    const address2Input = document.getElementById('dame_address_2');
    const postalCodeInput = document.getElementById('dame_postal_code');
    const cityInput = document.getElementById('dame_city');
    const lastNameInput = document.getElementById('dame_last_name');

    // Rep 1 fields
    const rep1RequiredIndicators = document.querySelectorAll('.dame-rep1-required-indicator');
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

            // For adults, birth city is required.
            if (birthCityInput) birthCityInput.required = true;
            if (birthCityRequiredIndicator) birthCityRequiredIndicator.style.display = 'inline';

            // Clear all inputs within the minor fields container to prevent submission of hidden data
            const minorInputs = mineurFields.querySelectorAll('input');
            minorInputs.forEach(input => {
                input.value = '';
            });
            // Make rep 1 fields not required and hide indicators
            rep1RequiredInputs.forEach(input => input.required = false);
            rep1RequiredIndicators.forEach(indicator => indicator.style.display = 'none');

            if (healthQuestionnaireLink) {
                healthQuestionnaireLink.href = majeurPDF;
                healthQuestionnaireLink.textContent = 'Consulter le questionnaire pour Majeur';
                healthQuestionnaireLinkContainer.style.display = 'inline';
            }
        } else {
            majeurFields.style.display = 'none';
            mineurFields.style.display = 'block';

            // For minors, birth city is not required.
            if (birthCityInput) birthCityInput.required = false;
            if (birthCityRequiredIndicator) birthCityRequiredIndicator.style.display = 'none';

            // Make rep 1 fields required and show indicators
            rep1RequiredInputs.forEach(input => input.required = true);
            rep1RequiredIndicators.forEach(indicator => indicator.style.display = 'inline');

            if (healthQuestionnaireLink) {
                healthQuestionnaireLink.href = mineurPDF;
                healthQuestionnaireLink.textContent = 'Consulter le questionnaire pour Mineur';
                healthQuestionnaireLinkContainer.style.display = 'inline';
            }
        }
    });

    // The keyup listeners are removed in favor of a direct call from the autocomplete script.

    // Add live formatting for name fields
    const firstNameInput = document.getElementById('dame_first_name');
    const birthNameInput = document.getElementById('dame_birth_name');
    const rep2FirstNameInput = document.getElementById('dame_legal_rep_2_first_name');
    const rep2LastNameInput = document.getElementById('dame_legal_rep_2_last_name');

    if (firstNameInput) {
        firstNameInput.addEventListener('input', formatFirstNameInput);
    }
    if (birthNameInput) {
        birthNameInput.addEventListener('input', formatLastNameInput);
    }
    if (lastNameInput) {
        lastNameInput.addEventListener('input', formatLastNameInput);
    }
    if (rep1FirstNameInput) {
        rep1FirstNameInput.addEventListener('input', formatFirstNameInput);
    }
    if (rep1LastNameInput) {
        rep1LastNameInput.addEventListener('input', formatLastNameInput);
    }
    if (rep2FirstNameInput) {
        rep2FirstNameInput.addEventListener('input', formatFirstNameInput);
    }
    if (rep2LastNameInput) {
        rep2LastNameInput.addEventListener('input', formatLastNameInput);
    }

    // Handle Form Submission
    const form = document.getElementById('dame-pre-inscription-form');
    const messagesDiv = document.getElementById('dame-form-messages');
    const consentCheckbox = document.getElementById('dame_consent_checkbox');
    const submitButton = document.getElementById('dame_submit_button');

    if (consentCheckbox && submitButton) {
        consentCheckbox.addEventListener('change', function() {
            submitButton.disabled = !this.checked;
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (consentCheckbox && !consentCheckbox.checked) {
                messagesDiv.innerHTML = "Vous devez accepter le règlement intérieur pour continuer.";
                messagesDiv.style.display = 'block';
                messagesDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            messagesDiv.style.display = 'none';
            messagesDiv.innerHTML = '';
            messagesDiv.style.color = 'red'; // Default to red for errors

            // Custom client-side validation
            let firstInvalidField = null;
            const requiredFields = form.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                // Check if the field is visible
                if (field.offsetParent !== null) {
                    if ((field.type === 'radio' || field.type === 'checkbox')) {
                        const fieldName = field.name;
                        if (!form.querySelector(`input[name="${fieldName}"]:checked`)) {
                            if (!firstInvalidField) firstInvalidField = field;
                        }
                    } else if (!field.value.trim()) {
                        if (!firstInvalidField) firstInvalidField = field;
                    }
                }
            });

            if (firstInvalidField) {
                messagesDiv.innerHTML = "Veuillez remplir tous les champs obligatoires. Ils sont marqués d'un astérisque (*).";
                messagesDiv.style.display = 'block';
                firstInvalidField.focus();
                // Scroll to the message to make sure it's visible
                messagesDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return; // Stop form submission
            }

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
                    let successHtml = `<p>${data.data.message}</p>`;

                    // 1. Handle health-related message
                    if (data.data.health_questionnaire === 'oui') {
                        successHtml += `
                            <p style="font-weight: bold; color: red; margin-top: 1em;">
                                Afin de valider votre inscription auprès de la FFE, vous devez nous remettre un certificat médical, daté de moins de 6 mois, déclarant <strong>${data.data.full_name}</strong> apte à la pratique des échecs en et hors compétition.
                            </p>`;
                    }

                    // 2. Check if any download links are needed
                    const needsHealthAttestation = data.data.health_questionnaire === 'non';
                    const needsParentalAuth = data.data.is_minor;
                    const hasDownloadLinks = needsHealthAttestation || needsParentalAuth;

                    // 3. Add the informational message if there are any download links
                    if (hasDownloadLinks) {
                        const senderEmail = data.data.sender_email;
                        const emailLink = senderEmail ? `<a href="mailto:${senderEmail}">${senderEmail}</a>` : 'l\'email du club';
                        const messageText = `Vous trouverez ci-après le(s) document(s) à signer, puis à nous remettre en main propre ou à nous renvoyer à l’adresse ${emailLink}`;
                        successHtml += `<p style="margin-top: 1.5em;">${messageText}</p>`;
                    }

                    // 4. Add the actual download links
                    if (hasDownloadLinks) {
                        successHtml += `<div style="margin-bottom: 1.5em;">`;
                        if (needsHealthAttestation) {
                            successHtml += `
                                <a href="${dame_pre_inscription_ajax.ajax_url}?action=dame_generate_health_form&post_id=${data.data.post_id}&_wpnonce=${data.data.nonce}" style="display: block; color: blue; text-decoration: underline; margin-bottom: 0.5em; margin-left: 1.5em;">
                                    &#x1F4E5; Télécharger mon attestation de santé à remettre signé
                                </a>`;
                        }
                        if (needsParentalAuth) {
                            successHtml += `
                                <a href="${dame_pre_inscription_ajax.ajax_url}?action=dame_generate_parental_auth&post_id=${data.data.post_id}&_wpnonce=${data.data.parental_auth_nonce}" style="display: block; color: blue; text-decoration: underline; margin-left: 1.5em;">
                                    &#x1F4E5; Télécharger l'autorisation parentale a remettre signé
                                </a>`;
                        }
                        successHtml += `</div>`;
                    }

                    // 5. Add the action buttons
                    successHtml += `<div style="margin-top: 1em;">`;
                    successHtml += `
                        <button id="dame-new-adhesion-button" type="button" class="button dame-button" style="background-color: #fe0007; color: white; border: none; border-radius: 8px; padding: 8px 12px; margin-bottom: 10px; display: block;">
                            &#x1F501; Saisir une nouvelle adhésion
                        </button>`;
                    if (data.data.payment_url) {
                        successHtml += `
                            <a href="${data.data.payment_url}" target="_blank" class="button dame-button" style="text-decoration: none; padding: 10px 15px; font-size: 1.1em; border-radius: 8px; display: inline-block;">
                                &#x1F4B3; Aller sur PayAsso pour votre règlement &#x1F4B3;
                            </a>`;
                    }
                    successHtml += `</div>`;

                    // Set the content once
                    messagesDiv.innerHTML = successHtml;

                    // Hide the form
                    form.style.display = 'none';
                    dynamicFields.style.display = 'none';
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
                document.getElementById('dame_birth_name').value = '';
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

    // Add event listeners for copy buttons
    const copyButtons = document.querySelectorAll('.dame-copy-button');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const repId = this.getAttribute('data-rep-id');
            copyAdherentData(repId);
        });
    });
});

function copyAdherentData(repId) {
    // Adherent fields
    const birthNameInput = document.getElementById('dame_birth_name');
    const emailInput = document.getElementById('dame_email');
    const phoneInput = document.getElementById('dame_phone_number');
    const address1Input = document.getElementById('dame_address_1');
    const address2Input = document.getElementById('dame_address_2');
    const postalCodeInput = document.getElementById('dame_postal_code');
    const cityInput = document.getElementById('dame_city');

    // Rep fields
    const repLastNameInput = document.getElementById('dame_legal_rep_' + repId + '_last_name');
    const repEmailInput = document.getElementById('dame_legal_rep_' + repId + '_email');
    const repPhoneInput = document.getElementById('dame_legal_rep_' + repId + '_phone');
    const repAddress1Input = document.getElementById('dame_legal_rep_' + repId + '_address_1');
    const repAddress2Input = document.getElementById('dame_legal_rep_' + repId + '_address_2');
    const repPostalCodeInput = document.getElementById('dame_legal_rep_' + repId + '_postal_code');
    const repCityInput = document.getElementById('dame_legal_rep_' + repId + '_city');

    if (birthNameInput && emailInput && phoneInput && address1Input && postalCodeInput && cityInput &&
        repLastNameInput && repEmailInput && repPhoneInput && repAddress1Input && repPostalCodeInput && repCityInput) {

        repLastNameInput.value = birthNameInput.value;
        repEmailInput.value = emailInput.value;
        repPhoneInput.value = phoneInput.value;
        repAddress1Input.value = address1Input.value;
        repAddress2Input.value = address2Input.value;
        repPostalCodeInput.value = postalCodeInput.value;
        repCityInput.value = cityInput.value;
    }
}

/**
 * Formats a string to Mixed Case.
 * Capitalizes the first letter of each word separated by a space or a hyphen.
 * @param {string} str The string to format.
 * @returns {string} The formatted string.
 */
function formatToMixedCase(str) {
    if (!str) return '';
    // This will capitalize the first letter of each word, where words are separated by spaces or hyphens.
    let formattedStr = str.toLowerCase().replace(/(^|[\s-])\S/g, function(match) {
        return match.toUpperCase();
    });
    return formattedStr;
}

/**
 * Formats the input value of a first name field to Mixed Case.
 * @param {Event} event The input event.
 */
function formatFirstNameInput(event) {
    const input = event.target;
    const value = input.value;
    const formattedValue = formatToMixedCase(value);
    const cursorPosition = input.selectionStart;

    input.value = formattedValue;
    // Restore cursor position
    input.setSelectionRange(cursorPosition, cursorPosition);
}

/**
 * Formats the input value of a last name field to uppercase.
 * @param {Event} event The input event.
 */
function formatLastNameInput(event) {
    const input = event.target;
    const value = input.value;
    const formattedValue = value.toUpperCase();
    const cursorPosition = input.selectionStart;

    input.value = formattedValue;
    // Restore cursor position
    input.setSelectionRange(cursorPosition, cursorPosition);
}
