document.addEventListener('DOMContentLoaded', function () {

    function initAutocomplete(addressId, postalCodeId, cityId) {
        const addressInput = document.getElementById(addressId);
        if (!addressInput) {
            return;
        }

        const postalCodeInput = document.getElementById(postalCodeId);
        const cityInput = document.getElementById(cityId);
        const wrapper = addressInput.closest('.dame-autocomplete-wrapper');

        if (wrapper) {
            const resultsContainer = document.createElement('div');
            resultsContainer.className = 'dame-address-suggestions';
            resultsContainer.style.display = 'none';
            wrapper.appendChild(resultsContainer);

            let debounceTimer;

            addressInput.addEventListener('keyup', function () {
                clearTimeout(debounceTimer);
                const query = this.value;

                if (query.length < 5) {
                    resultsContainer.innerHTML = '';
                    resultsContainer.style.display = 'none';
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`https://data.geopf.fr/geocodage/completion?text=${encodeURIComponent(query)}&type=StreetAddress`)
                        .then(response => response.json())
                        .then(data => {
                            resultsContainer.innerHTML = '';
                            if (data.results && data.results.length > 0) {
                                resultsContainer.style.display = 'block';
                                const results = data.results;
                                results.forEach(result => {
                                    const suggestionDiv = document.createElement('div');
                                    suggestionDiv.classList.add('dame-suggestion-item');
                                    suggestionDiv.textContent = result.fulltext;
                                    suggestionDiv.dataset.feature = JSON.stringify(result);
                                    resultsContainer.appendChild(suggestionDiv);
                                });
                            } else {
                                resultsContainer.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching address suggestions:', error);
                            resultsContainer.style.display = 'none';
                        });
                }, 250);
            });

            resultsContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('dame-suggestion-item')) {
                    const featureProperties = JSON.parse(e.target.dataset.feature);

                    let streetAddress = featureProperties.fulltext.split(',')[0];
                    addressInput.value = streetAddress.trim();

                    if (postalCodeInput) {
                        postalCodeInput.value = featureProperties.zipcode;
                        postalCodeInput.dispatchEvent(new Event('keyup'));
                    }
                    if (cityInput) {
                        cityInput.value = featureProperties.city;
                    }

                    // If the global pre-fill function exists (on the public form), call it.
                    if (typeof prefillRep1 === 'function') {
                        prefillRep1();
                    }

                    resultsContainer.innerHTML = '';
                    resultsContainer.style.display = 'none';
                }
            });

            document.addEventListener('click', function(e) {
                if (e.target !== addressInput) {
                    resultsContainer.style.display = 'none';
                }
            });
        }
    }

    // Initialize for all address fields
    initAutocomplete('dame_address_1', 'dame_postal_code', 'dame_city');
    initAutocomplete('dame_legal_rep_1_address_1', 'dame_legal_rep_1_postal_code', 'dame_legal_rep_1_city');
    initAutocomplete('dame_legal_rep_2_address_1', 'dame_legal_rep_2_postal_code', 'dame_legal_rep_2_city');


    /**
     * Postal Code -> Department Link (Only for main address)
     */
    const mainPostalCodeField = document.getElementById('dame_postal_code');
    const departmentSelect = document.getElementById('dame_department');
    if (mainPostalCodeField && departmentSelect) {
        mainPostalCodeField.addEventListener('keyup', function () {
            const postalCode = this.value;
            if (postalCode.length >= 2) {
                let departmentCode = postalCode.substring(0, 2);
                if (departmentCode === '20') { return; }
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

    /**
     * Membership Date -> Membership Status Link
     */
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
