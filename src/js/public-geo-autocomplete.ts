interface GeoCommune {
	nom: string;
	codesPostaux?: string[];
}

document.addEventListener('DOMContentLoaded', (): void => {
	/**
	 * Initializes autocomplete for a single city field, populating it with "City (Code)".
	 * @param cityId
	 */
	function initBirthCityAutocomplete(cityId: string): void {
		const cityInput = document.getElementById(
			cityId
		) as HTMLInputElement | null;

		if (!cityInput) {
			return;
		}

		const wrapper = cityInput.closest<HTMLElement>(
			'.dame-autocomplete-wrapper'
		);
		if (!wrapper) {
			return;
		}

		const resultsContainer = document.createElement('div');
		resultsContainer.className = 'dame-address-suggestions';
		resultsContainer.style.display = 'none';
		wrapper.appendChild(resultsContainer);

		let debounceTimer: ReturnType<typeof setTimeout> | undefined;
		let highlightedIndex = -1;

		cityInput.addEventListener(
			'keyup',
			function (this: HTMLInputElement, e: KeyboardEvent): void {
				if (
					['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(e.key)
				) {
					return;
				}

				if (debounceTimer) {
					clearTimeout(debounceTimer);
				}
				const query = this.value;

				if (query.length < 3) {
					resultsContainer.style.display = 'none';
					highlightedIndex = -1;
					return;
				}

				debounceTimer = setTimeout((): void => {
					fetch(
						`https://geo.api.gouv.fr/communes?fields=nom,codesPostaux&nom=${encodeURIComponent(
							query
						)}`
					)
						.then(
							(response: Response) =>
								response.json() as Promise<GeoCommune[]>
						)
						.then((data: GeoCommune[]): void => {
							resultsContainer.innerHTML = '';
							highlightedIndex = -1;
							if (data && data.length > 0) {
								resultsContainer.style.display = 'block';
								data.slice(0, 10).forEach(
									(commune: GeoCommune): void => {
										if (
											commune.codesPostaux &&
											commune.codesPostaux.length > 0
										) {
											const suggestionDiv =
												document.createElement('div');
											suggestionDiv.classList.add(
												'dame-suggestion-item'
											);
											const suggestionText = `${commune.nom} (${commune.codesPostaux[0]})`;
											suggestionDiv.textContent =
												suggestionText;
											suggestionDiv.dataset.value =
												suggestionText;
											resultsContainer.appendChild(
												suggestionDiv
											);
										}
									}
								);
							} else {
								resultsContainer.style.display = 'none';
							}
						})
						.catch((error: unknown): void => {
							console.error('Error fetching cities:', error);
							resultsContainer.style.display = 'none';
						});
				}, 250);
			}
		);

		cityInput.addEventListener('keydown', (e: KeyboardEvent): void => {
			const suggestions = resultsContainer.querySelectorAll<HTMLElement>(
				'.dame-suggestion-item'
			);
			if (suggestions.length === 0) {
				return;
			}

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

		function updateHighlight(
			suggestions: NodeListOf<HTMLElement>,
			index: number
		): void {
			suggestions.forEach((suggestion: HTMLElement, i: number): void => {
				if (i === index) {
					suggestion.classList.add('highlighted');
				} else {
					suggestion.classList.remove('highlighted');
				}
			});
		}

		function selectSuggestion(suggestion: HTMLElement): void {
			if (cityInput && suggestion.dataset.value) {
				cityInput.value = suggestion.dataset.value;
			}
			resultsContainer.innerHTML = '';
			resultsContainer.style.display = 'none';
			highlightedIndex = -1;
		}

		resultsContainer.addEventListener('click', (e: MouseEvent): void => {
			const target = e.target as HTMLElement | null;
			if (target?.classList.contains('dame-suggestion-item')) {
				selectSuggestion(target);
			}
		});

		document.addEventListener('click', (e: MouseEvent): void => {
			if (!wrapper.contains(e.target as Node)) {
				resultsContainer.style.display = 'none';
				highlightedIndex = -1;
			}
		});
	}

	// This now uses a dedicated function that only requires the city field.
	initBirthCityAutocomplete('dame_birth_city');
	initBirthCityAutocomplete('dame_legal_rep_1_commune_naissance');
	initBirthCityAutocomplete('dame_legal_rep_2_commune_naissance');
});
