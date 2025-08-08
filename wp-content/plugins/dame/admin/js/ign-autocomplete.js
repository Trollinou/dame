document.addEventListener('DOMContentLoaded', function () {
    const addressInput = document.getElementById('dame_address_1');
    if (!addressInput) {
        return;
    }

    const postalCodeInput = document.getElementById('dame_postal_code');
    const cityInput = document.getElementById('dame_city');
    const departmentSelect = document.getElementById('dame_department');

    const resultsContainer = document.createElement('div');
    resultsContainer.id = 'dame-address-suggestions';
    addressInput.parentNode.insertBefore(resultsContainer, addressInput.nextSibling);

    let debounceTimer;

    addressInput.addEventListener('keyup', function () {
        clearTimeout(debounceTimer);
        const query = this.value;

        if (query.length < 3) {
            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`https://data.geopf.fr/geocodage/completion?text=${encodeURIComponent(query)}&type=StreetAddress`)
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    if (data.features && data.features.length > 0) {
                        resultsContainer.style.display = 'block';
                        data.features.forEach(feature => {
                            const suggestionDiv = document.createElement('div');
                            suggestionDiv.classList.add('dame-suggestion-item');
                            suggestionDiv.textContent = feature.properties.label;
                            suggestionDiv.dataset.feature = JSON.stringify(feature.properties); // Store properties
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
        }, 300);
    });

    resultsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('dame-suggestion-item')) {
            const featureProperties = JSON.parse(e.target.dataset.feature);

            addressInput.value = featureProperties.name; // Street name and number
            if(postalCodeInput) postalCodeInput.value = featureProperties.postcode;
            if(cityInput) cityInput.value = featureProperties.city;

            // Trigger keyup on postal code to auto-select department
            if(postalCodeInput) {
                const event = new Event('keyup');
                postalCodeInput.dispatchEvent(event);
            }

            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
        }
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== addressInput) {
            resultsContainer.style.display = 'none';
        }
    });

    // --- Existing JS from previous steps ---
    if (postalCodeInput && departmentSelect) {
        postalCodeInput.addEventListener('keyup', function () {
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
