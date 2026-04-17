document.addEventListener('DOMContentLoaded', function () {

    /**
     * Common Admin Logic (Address Autocomplete, Region Sync, etc.)
     * Works via CSS classes:
     * - .dame-js-address
     * - .dame-js-zip
     * - .dame-js-city
     * - .dame-js-lat
     * - .dame-js-long
     * - .dame-js-dist
     * - .dame-js-time
     * - .dame-js-birth-city
     * - .dame-js-dept
     * - .dame-js-region
     */

    // --- 1. Address Autocomplete ---
    function initAddressFields() {
        const addressInputs = document.querySelectorAll('.dame-js-address');
        addressInputs.forEach(function(addressInput) {
            // Find related fields in the same container (or globally if needed, but scoping to row/group is safer)
            // Strategy: Look for closest common ancestor (like tr, p, or form) or rely on specific naming?
            // The prompt says "use CSS classes".
            // Since fields are scattered (Address on one line, Zip/City on another), we need a way to link them.
            // A simple robust way: look for siblings or query globally if unique?
            // "The script must function exclusively with CSS classes".
            // Problem: If there are multiple addresses (Adherent, Legal Rep 1, Legal Rep 2), `document.querySelector('.dame-js-zip')` will just find the first one.
            // Solution: We assume they are grouped in a wrapper, OR we use `data-group` attribute?
            // The Metaboxes have table structure.
            // Let's assume we find the "container" (e.g. `tr` or `table` or `.postbox`).
            // Actually, for Adherent CPT, fields are distinct: `dame_address_1`, `dame_legal_rep_1_address_1`.
            // If we switch to classes, we lose the explicit ID mapping.
            // Constraint check: "Ce script doit fonctionner exclusivement avec des classes CSS... et non des IDs".
            // To handle multiple sets of address fields on one page, I'll use a `data-group` attribute on the inputs or a wrapper.
            // But I cannot easily change the HTML structure everywhere to add a wrapper around disjointed fields (Address line 1 vs City).
            // Compromise: I will look for related fields relative to the address input using a scoping mechanism,
            // OR I will assume the fields share a common suffix/prefix or data attribute?
            // Let's try to find fields by their class within the same "scope" (e.g. Metabox).
            // But Legal Reps are in the SAME metabox.
            // Okay, let's look at the structure.
            // Adherent Identity: Address, Zip, City are in one table.
            // Legal Reps: Rep 1 Address, Zip, City are in one table; Rep 2 in another section.
            // I will add `data-group="adherent"`, `data-group="rep1"`, `data-group="rep2"` to the inputs in PHP.
            // Then JS can query `.dame-js-zip[data-group="..."]`.

            const group = addressInput.dataset.group;
            if (!group) return;

            const postalCodeInput = document.querySelector(`.dame-js-zip[data-group="${group}"]`);
            const cityInput       = document.querySelector(`.dame-js-city[data-group="${group}"]`);
            const latitudeInput   = document.querySelector(`.dame-js-lat[data-group="${group}"]`);
            const longitudeInput  = document.querySelector(`.dame-js-long[data-group="${group}"]`);
            const distanceInput   = document.querySelector(`.dame-js-dist[data-group="${group}"]`);
            const travelTimeInput = document.querySelector(`.dame-js-time[data-group="${group}"]`);
            const departmentInput = document.querySelector(`.dame-js-dept[data-group="${group}"]`); // For linking zip -> dept

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
                                    data.results.forEach(result => {
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
                        if (highlightedIndex >= suggestions.length) highlightedIndex = 0;
                        updateHighlight(suggestions, highlightedIndex);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        highlightedIndex--;
                        if (highlightedIndex < 0) highlightedIndex = suggestions.length - 1;
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
                        if (i === index) suggestion.classList.add('highlighted');
                        else suggestion.classList.remove('highlighted');
                    });
                }

                function selectSuggestion(suggestion) {
                    const featureProperties = JSON.parse(suggestion.dataset.feature);
                    let streetAddress = featureProperties.fulltext.split(',')[0];
                    addressInput.value = streetAddress.trim();

                    if (postalCodeInput) {
                        postalCodeInput.value = featureProperties.zipcode;
                        // Trigger keyup/change to update department
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
        });
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


    // --- 2. Birth City Autocomplete ---
    function initBirthCityFields() {
        const cityInputs = document.querySelectorAll('.dame-js-birth-city');
        cityInputs.forEach(function(cityInput) {
            const wrapper = cityInput.closest('.dame-autocomplete-wrapper');
            if (!wrapper) return;

            const resultsContainer = document.createElement('div');
            resultsContainer.className = 'dame-address-suggestions';
            resultsContainer.style.display = 'none';
            wrapper.appendChild(resultsContainer);

            let debounceTimer;
            let highlightedIndex = -1;

            cityInput.addEventListener('keyup', function (e) {
                if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(e.key)) return;
                clearTimeout(debounceTimer);
                const query = this.value;
                if (query.length < 3) {
                    resultsContainer.style.display = 'none';
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
                        .catch(error => console.error('Error fetching cities:', error));
                }, 250);
            });

            cityInput.addEventListener('keydown', function (e) {
                const suggestions = resultsContainer.querySelectorAll('.dame-suggestion-item');
                if (suggestions.length === 0) return;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    highlightedIndex++;
                    if (highlightedIndex >= suggestions.length) highlightedIndex = 0;
                    updateHighlight(suggestions, highlightedIndex);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    highlightedIndex--;
                    if (highlightedIndex < 0) highlightedIndex = suggestions.length - 1;
                    updateHighlight(suggestions, highlightedIndex);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (highlightedIndex > -1 && suggestions[highlightedIndex]) {
                        cityInput.value = suggestions[highlightedIndex].dataset.value;
                        resultsContainer.style.display = 'none';
                    }
                } else if (e.key === 'Escape') {
                    resultsContainer.style.display = 'none';
                }
            });

            function updateHighlight(suggestions, index) {
                suggestions.forEach((suggestion, i) => {
                    if (i === index) suggestion.classList.add('highlighted');
                    else suggestion.classList.remove('highlighted');
                });
            }

            resultsContainer.addEventListener('click', function (e) {
                if (e.target.classList.contains('dame-suggestion-item')) {
                    cityInput.value = e.target.dataset.value;
                    resultsContainer.style.display = 'none';
                }
            });
            document.addEventListener('click', function (e) {
                if (!wrapper.contains(e.target)) resultsContainer.style.display = 'none';
            });
        });
    }


    // --- 3. Postal Code -> Department -> Region ---
    function initRegionSync() {
        // Zip -> Dept
        const zipInputs = document.querySelectorAll('.dame-js-zip');
        zipInputs.forEach(function(zipInput) {
            const group = zipInput.dataset.group;
            if (!group) return;
            const deptInput = document.querySelector(`.dame-js-dept[data-group="${group}"]`);
            if (deptInput) {
                zipInput.addEventListener('keyup', function () {
                    const postalCode = this.value;
                    if (postalCode.length >= 2) {
                        let departmentCode = postalCode.substring(0, 2);
                        if (departmentCode === '20') return; // Corse
                        for (let i = 0; i < deptInput.options.length; i++) {
                            if (deptInput.options[i].value === departmentCode) {
                                deptInput.value = departmentCode;
                                deptInput.dispatchEvent(new Event('change'));
                                break;
                            }
                        }
                    }
                });
            }
        });

        // Dept -> Region
        const deptInputs = document.querySelectorAll('.dame-js-dept');
        deptInputs.forEach(function(deptInput) {
            const group = deptInput.dataset.group;
            if (!group) return;
            const regionInput = document.querySelector(`.dame-js-region[data-group="${group}"]`);

            if (regionInput && dame_admin_data && dame_admin_data.dept_region_map) {
                deptInput.addEventListener('change', function() {
                    const selectedDept = this.value;
                    const regionCode = dame_admin_data.dept_region_map[selectedDept];
                    if (regionCode) {
                        regionInput.value = regionCode;
                    }
                });
            }
        });
    }

    // Initialize all
    initAddressFields();
    initBirthCityFields();
    initRegionSync();
});
