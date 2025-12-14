document.addEventListener('DOMContentLoaded', function () {

    function initAutocomplete(addressId, postalCodeId, cityId, latitudeId, longitudeId, distanceId, travelTimeId) {
        const addressInput = document.getElementById(addressId);
        if (!addressInput) {
            return;
        }

        const postalCodeInput = document.getElementById(postalCodeId);
        const cityInput = document.getElementById(cityId);
        const latitudeInput = document.getElementById(latitudeId);
        const longitudeInput = document.getElementById(longitudeId);
        const distanceInput = document.getElementById(distanceId);
        const travelTimeInput = document.getElementById(travelTimeId);
        const wrapper = addressInput.closest('.dame-autocomplete-wrapper');

        if (wrapper) {
            const resultsContainer = document.createElement('div');
            resultsContainer.className = 'dame-address-suggestions';
            resultsContainer.style.display = 'none';
            wrapper.appendChild(resultsContainer);

            let debounceTimer;
            let highlightedIndex = -1;

            addressInput.addEventListener('keyup', function (e) {
                // Ignore navigation keys
                if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(e.key)) {
                    return;
                }

                clearTimeout(debounceTimer);
                const query = this.value;

                if (query.length < 5) {
                    resultsContainer.innerHTML = '';
                    resultsContainer.style.display = 'none';
                    highlightedIndex = -1;
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`https://data.geopf.fr/geocodage/completion?text=${encodeURIComponent(query)}&type=StreetAddress`)
                        .then(response => response.json())
                        .then(data => {
                            resultsContainer.innerHTML = '';
                            highlightedIndex = -1;
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

            addressInput.addEventListener('keydown', function (e) {
                const suggestions = resultsContainer.querySelectorAll('.dame-suggestion-item');
                if (suggestions.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    highlightedIndex++;
                    if (highlightedIndex >= suggestions.length) {
                        highlightedIndex = 0;
                    }
                    updateHighlight(suggestions, highlightedIndex);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    highlightedIndex--;
                    if (highlightedIndex < 0) {
                        highlightedIndex = suggestions.length - 1;
                    }
                    updateHighlight(suggestions, highlightedIndex);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (highlightedIndex > -1) {
                        suggestions[highlightedIndex].click();
                    }
                } else if (e.key === 'Escape') {
                    resultsContainer.style.display = 'none';
                    highlightedIndex = -1;
                }
            });

            function updateHighlight(suggestions, index) {
                suggestions.forEach((suggestion, i) => {
                    if (i === index) {
                        suggestion.classList.add('highlighted');
                    } else {
                        suggestion.classList.remove('highlighted');
                    }
                });
            }

            function selectSuggestion(suggestion) {
                const featureProperties = JSON.parse(suggestion.dataset.feature);
                let streetAddress = featureProperties.fulltext.split(',')[0];
                addressInput.value = streetAddress.trim();

                if (postalCodeInput) {
                    postalCodeInput.value = featureProperties.zipcode;
                    postalCodeInput.dispatchEvent(new Event('keyup'));
                }
                if (cityInput) {
                    cityInput.value = featureProperties.city;
                }
                if (latitudeInput && featureProperties.y) {
                    latitudeInput.value = featureProperties.y;
                }
                if (longitudeInput && featureProperties.x) {
                    longitudeInput.value = featureProperties.x;
                }

                if (distanceInput && travelTimeInput && latitudeInput.value && longitudeInput.value) {
                    calculateRoute(latitudeInput.value, longitudeInput.value, distanceInput, travelTimeInput);
                }

                // If the global pre-fill function exists (on the public form), call it.
                if (typeof prefillRep1 === 'function') {
                    prefillRep1();
                }

                resultsContainer.innerHTML = '';
                resultsContainer.style.display = 'none';
                highlightedIndex = -1;
            }

            resultsContainer.addEventListener('click', function (e) {
                if (e.target.classList.contains('dame-suggestion-item')) {
                    selectSuggestion(e.target);
                }
            });

            document.addEventListener('click', function (e) {
                if (e.target !== addressInput) {
                    resultsContainer.style.display = 'none';
                    highlightedIndex = -1;
                }
            });
        }
    }

    function calculateRoute(destLat, destLng, distanceInput, travelTimeInput) {
        const startLat = dame_admin_data.assoc_latitude;
        const startLng = dame_admin_data.assoc_longitude;

        if (!startLat || !startLng) {
            return;
        }

        const url = `https://data.geopf.fr/navigation/itineraire?resource=bdtopo-osrm&start=${startLng},${startLat}&end=${destLng},${destLat}&profile=car&optimization=fastest&distanceUnit=kilometer&timeUnit=hour`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.distance && data.duration) {
                    const distanceInKm = data.distance.toFixed(2);
                    const durationInHours = data.duration;
                    const hours = Math.floor(durationInHours);
                    const minutes = Math.round((durationInHours - hours) * 60);

                    distanceInput.value = `${distanceInKm} km`;
                    travelTimeInput.value = `${hours}h ${minutes}min`;
                }
            })
            .catch(error => console.error('Error calculating route:', error));
    }

    // Initialize for all address fields
    initAutocomplete('dame_address_1', 'dame_postal_code', 'dame_city', 'dame_latitude', 'dame_longitude', 'dame_distance', 'dame_travel_time');

    const calculateButton = document.getElementById('dame_calculate_route_button');
    if (calculateButton) {
        calculateButton.addEventListener('click', function() {
            const lat = document.getElementById('dame_latitude').value;
            const lng = document.getElementById('dame_longitude').value;
            const distanceInput = document.getElementById('dame_distance');
            const travelTimeInput = document.getElementById('dame_travel_time');

            if (lat && lng) {
                calculateRoute(lat, lng, distanceInput, travelTimeInput);
            }
        });
    }

    initAutocomplete('dame_legal_rep_1_address_1', 'dame_legal_rep_1_postal_code', 'dame_legal_rep_1_city');
    initAutocomplete('dame_legal_rep_2_address_1', 'dame_legal_rep_2_postal_code', 'dame_legal_rep_2_city');
    initAutocomplete('dame_assoc_address_1', 'dame_assoc_postal_code', 'dame_assoc_city', 'dame_assoc_latitude', 'dame_assoc_longitude');


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
