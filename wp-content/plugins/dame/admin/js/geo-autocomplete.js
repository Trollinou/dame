document.addEventListener('DOMContentLoaded', function () {
    function initGeoAutocomplete(postalCodeId, cityId) {
        const postalCodeInput = document.getElementById(postalCodeId);
        const cityInput = document.getElementById(cityId);

        if (!postalCodeInput || !cityInput) {
            return;
        }

        const wrapper = cityInput.closest('td');
        const resultsContainer = document.createElement('div');
        resultsContainer.className = 'dame-address-suggestions';
        resultsContainer.style.display = 'none';
        wrapper.style.position = 'relative';
        wrapper.appendChild(resultsContainer);

        let debounceTimer;

        function fetchCities(query, type) {
            let url = 'https://geo.api.gouv.fr/communes?fields=nom,codesPostaux';
            if (type === 'postalcode') {
                url += `&codePostal=${encodeURIComponent(query)}`;
            } else {
                url += `&nom=${encodeURIComponent(query)}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    if (data && data.length > 0) {
                        resultsContainer.style.display = 'block';
                        data.slice(0, 10).forEach(commune => {
                            const suggestionDiv = document.createElement('div');
                            suggestionDiv.classList.add('dame-suggestion-item');
                            suggestionDiv.textContent = `${commune.nom} (${commune.codesPostaux.join(', ')})`;
                            suggestionDiv.dataset.city = commune.nom;
                            suggestionDiv.dataset.postalCode = commune.codesPostaux[0];
                            resultsContainer.appendChild(suggestionDiv);
                        });
                    } else {
                        resultsContainer.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error fetching cities:', error);
                    resultsContainer.style.display = 'none';
                });
        }

        postalCodeInput.addEventListener('keyup', function () {
            clearTimeout(debounceTimer);
            const query = this.value;

            if (query.length === 5) {
                fetchCities(query, 'postalcode');
            } else {
                resultsContainer.style.display = 'none';
            }
        });

        cityInput.addEventListener('keyup', function () {
            clearTimeout(debounceTimer);
            const query = this.value;

            if (query.length >= 5) {
                debounceTimer = setTimeout(() => {
                    fetchCities(query, 'city');
                }, 1000);
            } else {
                resultsContainer.style.display = 'none';
            }
        });

        resultsContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('dame-suggestion-item')) {
                const feature = e.target.dataset;
                cityInput.value = feature.city;
                postalCodeInput.value = feature.postalCode;
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

    initGeoAutocomplete('dame_birth_postal_code', 'dame_birth_city');
});
