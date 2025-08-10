document.addEventListener('DOMContentLoaded', function () {
    const isNewPost = document.body.classList.contains('post-new-php');
    const postType = document.getElementById('post_type');

    if (isNewPost && postType && postType.value === 'adherent') {
        // Mettre le focus sur le champ Prénom
        const firstNameInput = document.getElementById('dame_first_name');
        if (firstNameInput) {
            firstNameInput.focus();
        }

        // Changer le placeholder du titre
        const titleInput = document.getElementById('title');
        if (titleInput) {
            titleInput.placeholder = 'Ne pas remplir';
        }
    }

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

    // Show/hide membership details based on status
    const membershipStatusSelect = document.getElementById('dame_membership_status');
    const membershipDetailsWrapper = document.getElementById('dame_membership_details_wrapper');

    function toggleMembershipDetails() {
        if (membershipStatusSelect && membershipDetailsWrapper) {
            if (membershipStatusSelect.value === 'A') {
                membershipDetailsWrapper.style.display = '';
            } else {
                membershipDetailsWrapper.style.display = 'none';
            }
        }
    }

    if (membershipStatusSelect) {
        membershipStatusSelect.addEventListener('change', toggleMembershipDetails);
        // Initial check
        toggleMembershipDetails();
    }

    // Auto-set membership status to 'Active' when date is entered
    const membershipDateInput = document.getElementById('dame_membership_date');
    if (membershipDateInput && membershipStatusSelect) {
        membershipDateInput.addEventListener('change', function() {
            if (this.value && membershipStatusSelect.value !== 'A') {
                membershipStatusSelect.value = 'A';
                toggleMembershipDetails(); // Update visibility
            }
        });
    }

    // Minor auto-population logic
    const birthDateInput = document.getElementById('dame_birth_date');
    const adherentPhone = document.getElementById('dame_phone_number');
    const adherentEmail = document.getElementById('dame_email');
    const adherentAddress1 = document.getElementById('dame_address_1');
    const adherentAddress2 = document.getElementById('dame_address_2');
    const adherentPostalCode = document.getElementById('dame_postal_code');
    const adherentCity = document.getElementById('dame_city');

    const rep1Phone = document.getElementById('dame_legal_rep_1_phone');
    const rep1Email = document.getElementById('dame_legal_rep_1_email');
    const rep1Address1 = document.getElementById('dame_legal_rep_1_address_1');
    const rep1Address2 = document.getElementById('dame_legal_rep_1_address_2');
    const rep1PostalCode = document.getElementById('dame_legal_rep_1_postal_code');
    const rep1City = document.getElementById('dame_legal_rep_1_city');

    function isMinor() {
        if (!birthDateInput.value) return false;
        const birthDate = new Date(birthDateInput.value);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age < 18;
    }

    function copyAdherentDataToRep1() {
        if (isNewPost && isMinor()) {
            if (adherentPhone && rep1Phone) rep1Phone.value = adherentPhone.value;
            if (adherentEmail && rep1Email) rep1Email.value = adherentEmail.value;
            if (adherentAddress1 && rep1Address1) rep1Address1.value = adherentAddress1.value;
            if (adherentAddress2 && rep1Address2) rep1Address2.value = adherentAddress2.value;
            if (adherentPostalCode && rep1PostalCode) rep1PostalCode.value = adherentPostalCode.value;
            if (adherentCity && rep1City) rep1City.value = adherentCity.value;
        }
    }

    if (isNewPost) {
        if (birthDateInput) {
            birthDateInput.addEventListener('change', copyAdherentDataToRep1);
        }
        [adherentPhone, adherentEmail, adherentAddress1, adherentAddress2, adherentPostalCode, adherentCity].forEach(field => {
            if (field) {
                field.addEventListener('input', copyAdherentDataToRep1);
            }
        });
    }
});
