document.addEventListener('DOMContentLoaded', function () {
    /**
     * Initializes autocomplete for a single city field, populating it with "City (Code)".
     * @param {string} cityId The ID of the city input field.
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

        cityInput.addEventListener('keyup', function () {
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
            }, 500);
        });

        resultsContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('dame-suggestion-item')) {
                cityInput.value = e.target.dataset.value;
                resultsContainer.innerHTML = '';
                resultsContainer.style.display = 'none';
            }
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) {
                resultsContainer.style.display = 'none';
            }
        });
    }

    // The old initGeoAutocomplete function was here. It has been removed as it is no longer used.

    // This now uses a dedicated function that only requires the city field.
    initBirthCityAutocomplete('dame_birth_city');
    initBirthCityAutocomplete('dame_legal_rep_1_commune_naissance');
    initBirthCityAutocomplete('dame_legal_rep_2_commune_naissance');
});
