document.addEventListener('DOMContentLoaded', function () {
    /**
     * IGN Address Autocompletion
     */
    const addressInput = document.getElementById('dame_address_1');
    if (addressInput) {
        const postalCodeInput = document.getElementById('dame_postal_code');
        const cityInput = document.getElementById('dame_city');
        const wrapper = addressInput.closest('.dame-autocomplete-wrapper');

        if (wrapper) {
            const resultsContainer = document.createElement('div');
            resultsContainer.id = 'dame-address-suggestions';
            wrapper.appendChild(resultsContainer);

            let debounceTimer;

            addressInput.addEventListener('keyup', function () {
                clearTimeout(debounceTimer);
                const query = this.value;

                if (query.length < 10) { // Requirement: 10 characters minimum
                    resultsContainer.innerHTML = '';
                    resultsContainer.style.display = 'none';
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`https://data.geopf.fr/geocodage/completion?text=${encodeURIComponent(query)}&type=StreetAddress`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            resultsContainer.innerHTML = '';
                            // BUG FIX: The API returns 'results', not 'features'.
                            if (data.results && data.results.length > 0) {
                                resultsContainer.style.display = 'block';
                                const results = data.results.slice(0, 4); // Requirement: Max 4 lines
                                results.forEach(result => {
                                    const suggestionDiv = document.createElement('div');
                                    suggestionDiv.classList.add('dame-suggestion-item');
                                    suggestionDiv.textContent = result.fulltext; // Use 'fulltext' for the label
                                    suggestionDiv.dataset.feature = JSON.stringify(result); // Store the whole result object
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
                }, 1000); // Requirement: 1 second debounce
            });

            resultsContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('dame-suggestion-item')) {
                    const featureProperties = JSON.parse(e.target.dataset.feature);

                    addressInput.value = featureProperties.street; // Use 'street'
                    if(postalCodeInput) postalCodeInput.value = featureProperties.zipcode; // Use 'zipcode'
                    if(cityInput) cityInput.value = featureProperties.city; // Use 'city'

                    if(postalCodeInput) {
                        const event = new Event('keyup');
                        postalCodeInput.dispatchEvent(event);
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

    /**
     * Postal Code -> Department Link
     */
    const postalCodeField = document.getElementById('dame_postal_code');
    const departmentSelect = document.getElementById('dame_department');
    if (postalCodeField && departmentSelect) {
        postalCodeField.addEventListener('keyup', function () {
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
