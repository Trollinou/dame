document.addEventListener('DOMContentLoaded', function () {

    /**
     * Initialize Autocomplete for Address Fields
     */
    function initAddressAutocomplete(addressId, postalCodeId, cityId, latitudeId, longitudeId, distanceId, travelTimeId) {
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
                    if (highlightedIndex > -1 && suggestions[highlightedIndex]) {
                        selectSuggestion(suggestions[highlightedIndex]);
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
                    // Trigger keyup to update department if logic exists
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

                if (distanceInput && travelTimeInput && latitudeInput && longitudeInput && latitudeInput.value && longitudeInput.value) {
                    calculateRoute(latitudeInput.value, longitudeInput.value, distanceInput, travelTimeInput);
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
                if (!wrapper.contains(e.target)) {
                    resultsContainer.style.display = 'none';
                    highlightedIndex = -1;
                }
            });
        }
    }

    function calculateRoute(destLat, destLng, distanceInput, travelTimeInput) {
        if (!dame_admin_data || !dame_admin_data.assoc_latitude || !dame_admin_data.assoc_longitude) {
            return;
        }

        const startLat = dame_admin_data.assoc_latitude;
        const startLng = dame_admin_data.assoc_longitude;

        const url = `https://data.geopf.fr/navigation/itineraire?resource=bdtopo-osrm&start=${startLng},${startLat}&end=${destLng},${destLat}&profile=car&optimization=fastest&distanceUnit=kilometer&timeUnit=hour`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.distance && data.duration) {
                    const distanceInKm = data.distance.toFixed(2);
                    const durationInHours = data.duration;
                    const hours = Math.floor(durationInHours);
                    const minutes = Math.round((durationInHours - hours) * 60);

                    if (distanceInput) distanceInput.value = `${distanceInKm} km`;
                    if (travelTimeInput) travelTimeInput.value = `${hours}h ${minutes}min`;
                }
            })
            .catch(error => console.error('Error calculating route:', error));
    }


    /**
     * Initialize Autocomplete for Birth City Fields
     */
    function initBirthCityAutocomplete(cityId) {
        const cityInput = document.getElementById(cityId);

        if (!cityInput) {
            return;
        }

        const wrapper = cityInput.closest('.dame-autocomplete-wrapper');
        if (!wrapper) return;

        const resultsContainer = document.createElement('div');
        resultsContainer.className = 'dame-address-suggestions';
        resultsContainer.style.display = 'none';
        wrapper.appendChild(resultsContainer);

        let debounceTimer;
        let highlightedIndex = -1;

        cityInput.addEventListener('keyup', function (e) {
            if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(e.key)) {
                return;
            }

            clearTimeout(debounceTimer);
            const query = this.value;

            if (query.length < 3) {
                resultsContainer.style.display = 'none';
                highlightedIndex = -1;
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`https://geo.api.gouv.fr/communes?fields=nom,codesPostaux&nom=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        resultsContainer.innerHTML = '';
                        highlightedIndex = -1;
                        if (data && data.length > 0) {
                            resultsContainer.style.display = 'block';
                            data.slice(0, 10).forEach(commune => {
                                if (commune.codesPostaux && commune.codesPostaux.length > 0) {
                                    const suggestionDiv = document.createElement('div');
                                    suggestionDiv.classList.add('dame-suggestion-item');
                                    const suggestionText = `${commune.nom} (${commune.codesPostaux[0]})`;
                                    suggestionDiv.textContent = suggestionText;
                                    suggestionDiv.dataset.value = suggestionText;
                                    resultsContainer.appendChild(suggestionDiv);
                                }
                            });
                        } else {
                            resultsContainer.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching cities:', error);
                        resultsContainer.style.display = 'none';
                    });
            }, 250);
        });

        cityInput.addEventListener('keydown', function (e) {
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
                if (highlightedIndex > -1 && suggestions[highlightedIndex]) {
                    selectSuggestion(suggestions[highlightedIndex]);
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
            cityInput.value = suggestion.dataset.value;
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
            if (!wrapper.contains(e.target)) {
                resultsContainer.style.display = 'none';
                highlightedIndex = -1;
            }
        });
    }


    // --- Initialization ---

    // 1. Address Autocomplete (Main Adherent)
    // Note: dame_latitude, etc. are now hidden inputs in Identity.php
    initAddressAutocomplete(
        'dame_address_1',
        'dame_postal_code',
        'dame_city',
        'dame_latitude',
        'dame_longitude',
        'dame_distance',
        'dame_travel_time'
    );

    // 2. Address Autocomplete (Legal Reps)
    initAddressAutocomplete('dame_legal_rep_1_address_1', 'dame_legal_rep_1_postal_code', 'dame_legal_rep_1_city');
    initAddressAutocomplete('dame_legal_rep_2_address_1', 'dame_legal_rep_2_postal_code', 'dame_legal_rep_2_city');

    // 3. Birth City Autocomplete
    initBirthCityAutocomplete('dame_birth_city');
    initBirthCityAutocomplete('dame_legal_rep_1_commune_naissance');
    initBirthCityAutocomplete('dame_legal_rep_2_commune_naissance');


    // 4. Postal Code -> Department Link (Only for main address)
    const mainPostalCodeField = document.getElementById('dame_postal_code');
    const departmentSelect = document.getElementById('dame_department');
    if (mainPostalCodeField && departmentSelect) {
        mainPostalCodeField.addEventListener('keyup', function () {
            const postalCode = this.value;
            if (postalCode.length >= 2) {
                let departmentCode = postalCode.substring(0, 2);
                if (departmentCode === '20') { return; } // Corse is complex
                for (let i = 0; i < departmentSelect.options.length; i++) {
                    const option = departmentSelect.options[i];
                    if (option.value === departmentCode) {
                        departmentSelect.value = departmentCode;
                        departmentSelect.dispatchEvent(new Event('change'));
                        break;
                    }
                }
            }
        });
    }

    // 5. Usage Name Fallback Logic
    const birthNameInput = document.getElementById('dame_birth_name');
    const lastNameInput = document.getElementById('dame_last_name');

    if (birthNameInput && lastNameInput) {
        birthNameInput.addEventListener('blur', function() {
            if (this.value && !lastNameInput.value) {
                lastNameInput.value = this.value;
            }
        });
    }

    // 6. Department -> Region Link (Using Localized Data)
    const regionSelect = document.getElementById('dame_region');
    if (departmentSelect && regionSelect && dame_admin_data && dame_admin_data.dept_region_map) {
        departmentSelect.addEventListener('change', function() {
            const selectedDept = this.value;
            const regionCode = dame_admin_data.dept_region_map[selectedDept];

            if (regionCode) {
                regionSelect.value = regionCode;
            }
        });
    }

});
