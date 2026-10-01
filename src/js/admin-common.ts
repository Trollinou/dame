/**
 * Common Admin Logic (Address Autocomplete, Region Sync, etc.)
 */

interface IgnAddressResult {
	fulltext: string;
	zipcode?: string;
	city?: string;
	x?: number | string;
	y?: number | string;
	[key: string]: unknown;
}

interface IgnAddressResponse {
	results?: IgnAddressResult[];
}

interface GeoCommuneAdmin {
	nom: string;
	codesPostaux?: string[];
}

interface ItineraireAdminResponse {
	distance?: number;
	duration?: number;
	[key: string]: unknown;
}

document.addEventListener('DOMContentLoaded', (): void => {
	// --- 1. Address Autocomplete ---
	function initAddressFields(): void {
		const addressInputs = document.querySelectorAll<HTMLInputElement>('.dame-js-address');
		addressInputs.forEach((addressInput: HTMLInputElement): void => {
			const group = addressInput.dataset.group;
			if (!group) {
				return;
			}

			const postalCodeInput = document.querySelector<HTMLInputElement>(
				`.dame-js-zip[data-group="${group}"]`
			);
			const cityInput = document.querySelector<HTMLInputElement>(
				`.dame-js-city[data-group="${group}"]`
			);
			const latitudeInput = document.querySelector<HTMLInputElement>(
				`.dame-js-lat[data-group="${group}"]`
			);
			const longitudeInput = document.querySelector<HTMLInputElement>(
				`.dame-js-long[data-group="${group}"], .dame-js-lng[data-group="${group}"]`
			);
			const distanceInput = document.querySelector<HTMLInputElement>(
				`.dame-js-dist[data-group="${group}"]`
			);
			const travelTimeInput = document.querySelector<HTMLInputElement>(
				`.dame-js-time[data-group="${group}"]`
			);

			const wrapper = addressInput.closest<HTMLElement>('.dame-autocomplete-wrapper');

			if (wrapper) {
				const resultsContainer = document.createElement('div');
				resultsContainer.className = 'dame-address-suggestions';
				resultsContainer.style.display = 'none';
				wrapper.appendChild(resultsContainer);

				let debounceTimer: ReturnType<typeof setTimeout> | undefined;
				let highlightedIndex = -1;

				addressInput.addEventListener('keyup', function (this: HTMLInputElement, e: KeyboardEvent): void {
					if (
						['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(
							e.key
						)
					) {
						return;
					}

					if (debounceTimer) {
						clearTimeout(debounceTimer);
					}
					const query = this.value;

					if (query.length < 5) {
						resultsContainer.innerHTML = '';
						resultsContainer.style.display = 'none';
						highlightedIndex = -1;
						return;
					}

					debounceTimer = setTimeout((): void => {
						fetch(
							`https://data.geopf.fr/geocodage/completion?text=${encodeURIComponent(
								query
							)}&type=StreetAddress`
						)
							.then((response: Response) => response.json() as Promise<IgnAddressResponse>)
							.then((data: IgnAddressResponse): void => {
								resultsContainer.innerHTML = '';
								highlightedIndex = -1;
								if (data.results && data.results.length > 0) {
									resultsContainer.style.display = 'block';
									data.results.forEach((result: IgnAddressResult): void => {
										const suggestionDiv =
											document.createElement('div');
										suggestionDiv.classList.add(
											'dame-suggestion-item'
										);
										suggestionDiv.textContent =
											result.fulltext;
										suggestionDiv.dataset.feature =
											JSON.stringify(result);
										resultsContainer.appendChild(
											suggestionDiv
										);
									});
								} else {
									resultsContainer.style.display = 'none';
								}
							})
							.catch((error: unknown): void => {
								console.error(
									'Error fetching address suggestions:',
									error
								);
								resultsContainer.style.display = 'none';
							});
					}, 250);
				});

				addressInput.addEventListener('keydown', (e: KeyboardEvent): void => {
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
						if (
							highlightedIndex > -1 &&
							suggestions[highlightedIndex]
						) {
							selectSuggestion(suggestions[highlightedIndex]);
						}
					} else if (e.key === 'Escape') {
						resultsContainer.style.display = 'none';
						highlightedIndex = -1;
					}
				});

				function updateHighlight(suggestions: NodeListOf<HTMLElement>, index: number): void {
					suggestions.forEach((suggestion: HTMLElement, i: number): void => {
						if (i === index) {
							suggestion.classList.add('highlighted');
						} else {
							suggestion.classList.remove('highlighted');
						}
					});
				}

				function selectSuggestion(suggestion: HTMLElement): void {
					if (!suggestion.dataset.feature) {
						return;
					}
					const featureProperties = JSON.parse(
						suggestion.dataset.feature
					) as IgnAddressResult;
					const streetAddress =
						featureProperties.fulltext.split(',')[0];
					addressInput.value = streetAddress.trim();

					if (postalCodeInput && featureProperties.zipcode) {
						postalCodeInput.value = featureProperties.zipcode;
						// Trigger keyup/change to update department
						postalCodeInput.dispatchEvent(new Event('keyup'));
					}
					if (cityInput && featureProperties.city) {
						cityInput.value = featureProperties.city;
					}
					if (latitudeInput && featureProperties.y !== undefined) {
						latitudeInput.value = String(featureProperties.y);
					}
					if (longitudeInput && featureProperties.x !== undefined) {
						longitudeInput.value = String(featureProperties.x);
					}

					if (
						distanceInput &&
						travelTimeInput &&
						latitudeInput &&
						longitudeInput &&
						latitudeInput.value &&
						longitudeInput.value
					) {
						calculateRoute(
							latitudeInput.value,
							longitudeInput.value,
							distanceInput,
							travelTimeInput
						);
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
		});
	}

	function calculateRoute(
		destLat: string | number,
		destLng: string | number,
		distanceInput: HTMLInputElement | null,
		travelTimeInput: HTMLInputElement | null,
		button?: HTMLButtonElement | null
	): void {
		if (
			!dame_admin_data ||
			!dame_admin_data.assoc_latitude ||
			!dame_admin_data.assoc_longitude
		) {
			console.warn(
				"DAME: Les coordonnées de l'association ne sont pas configurées dans les réglages."
			);
			if (button) {
				alert(
					'Les coordonnées du club (latitude / longitude) ne sont pas configurées dans les réglages du plugin.'
				);
			}
			return;
		}

		const startLat = dame_admin_data.assoc_latitude;
		const startLng = dame_admin_data.assoc_longitude;
		const url = `https://data.geopf.fr/navigation/itineraire?resource=bdtopo-osrm&start=${startLng},${startLat}&end=${destLng},${destLat}&profile=car&optimization=fastest&distanceUnit=kilometer&timeUnit=hour`;

		if (button) {
			button.disabled = true;
			button.textContent = 'Calcul en cours...';
		}

		fetch(url)
			.then((response: Response) => {
				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status}`);
				}
				return response.json() as Promise<ItineraireAdminResponse>;
			})
			.then((data: ItineraireAdminResponse): void => {
				if (
					typeof data.distance !== 'undefined' &&
					data.distance !== null &&
					typeof data.duration !== 'undefined' &&
					data.duration !== null
				) {
					const distanceInKm = Number(data.distance).toFixed(2);
					const durationInHours = Number(data.duration);
					const hours = Math.floor(durationInHours);
					const minutes = Math.round((durationInHours - hours) * 60);

					let formattedTime = '';
					if (hours > 0) {
						formattedTime = `${hours}h ${minutes < 10 ? '0' : ''}${minutes}min`;
					} else {
						formattedTime = `${minutes} min`;
					}

					if (distanceInput) {
						distanceInput.value = `${distanceInKm} km`;
					}
					if (travelTimeInput) {
						travelTimeInput.value = formattedTime;
					}
				}
			})
			.catch((error: unknown): void => {
				console.error('Error calculating route:', error);
				if (button) {
					alert(
						"Impossible de calculer l'itinéraire. Veuillez vérifier l'adresse ou les coordonnées GPS."
					);
				}
			})
			.finally((): void => {
				if (button) {
					button.disabled = false;
					button.textContent = 'Calculer';
				}
			});
	}

	// --- 2. Birth City Autocomplete ---
	function initBirthCityFields(): void {
		const cityInputs = document.querySelectorAll<HTMLInputElement>('.dame-js-birth-city');
		cityInputs.forEach((cityInput: HTMLInputElement): void => {
			const wrapper = cityInput.closest<HTMLElement>('.dame-autocomplete-wrapper');
			if (!wrapper) {
				return;
			}

			const resultsContainer = document.createElement('div');
			resultsContainer.className = 'dame-address-suggestions';
			resultsContainer.style.display = 'none';
			wrapper.appendChild(resultsContainer);

			let debounceTimer: ReturnType<typeof setTimeout> | undefined;
			let highlightedIndex = -1;

			cityInput.addEventListener('keyup', function (this: HTMLInputElement, e: KeyboardEvent): void {
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
					return;
				}
				debounceTimer = setTimeout((): void => {
					fetch(
						`https://geo.api.gouv.fr/communes?fields=nom,codesPostaux&nom=${encodeURIComponent(
							query
						)}`
					)
						.then((response: Response) => response.json() as Promise<GeoCommuneAdmin[]>)
						.then((data: GeoCommuneAdmin[]): void => {
							resultsContainer.innerHTML = '';
							highlightedIndex = -1;
							if (data && data.length > 0) {
								resultsContainer.style.display = 'block';
								data.slice(0, 10).forEach((commune: GeoCommuneAdmin): void => {
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
								});
							} else {
								resultsContainer.style.display = 'none';
							}
						})
						.catch((error: unknown): void =>
							console.error('Error fetching cities:', error)
						);
				}, 250);
			});

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
					if (
						highlightedIndex > -1 &&
						suggestions[highlightedIndex]
					) {
						cityInput.value =
							suggestions[highlightedIndex].dataset.value || '';
						resultsContainer.style.display = 'none';
					}
				} else if (e.key === 'Escape') {
					resultsContainer.style.display = 'none';
				}
			});

			function updateHighlight(suggestions: NodeListOf<HTMLElement>, index: number): void {
				suggestions.forEach((suggestion: HTMLElement, i: number): void => {
					if (i === index) {
						suggestion.classList.add('highlighted');
					} else {
						suggestion.classList.remove('highlighted');
					}
				});
			}

			resultsContainer.addEventListener('click', (e: MouseEvent): void => {
				const target = e.target as HTMLElement | null;
				if (target?.classList.contains('dame-suggestion-item')) {
					cityInput.value = target.dataset.value || '';
					resultsContainer.style.display = 'none';
				}
			});
			document.addEventListener('click', (e: MouseEvent): void => {
				if (!wrapper.contains(e.target as Node)) {
					resultsContainer.style.display = 'none';
				}
			});
		});
	}

	// --- 3. Postal Code -> Department -> Region ---
	function initRegionSync(): void {
		// Zip -> Dept
		const zipInputs = document.querySelectorAll<HTMLInputElement>('.dame-js-zip');
		zipInputs.forEach((zipInput: HTMLInputElement): void => {
			const group = zipInput.dataset.group;
			if (!group) {
				return;
			}
			const deptInput = document.querySelector<HTMLSelectElement>(
				`.dame-js-dept[data-group="${group}"]`
			);
			if (deptInput) {
				zipInput.addEventListener('keyup', function (this: HTMLInputElement): void {
					const postalCode = this.value;
					if (postalCode.length >= 2) {
						const departmentCode = postalCode.substring(0, 2);
						if (departmentCode === '20') {
							return;
						} // Corse
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
		const deptInputs = document.querySelectorAll<HTMLSelectElement>('.dame-js-dept');
		deptInputs.forEach((deptInput: HTMLSelectElement): void => {
			const group = deptInput.dataset.group;
			if (!group) {
				return;
			}
			const regionInput = document.querySelector<HTMLSelectElement>(
				`.dame-js-region[data-group="${group}"]`
			);

			if (
				regionInput &&
				dame_admin_data &&
				dame_admin_data.dept_region_map
			) {
				deptInput.addEventListener('change', function (this: HTMLSelectElement): void {
					const selectedDept = this.value;
					const mapping = dame_admin_data.dept_region_map;
					const regionCode = mapping ? mapping[selectedDept] : undefined;
					if (regionCode) {
						regionInput.value = regionCode;
					}
				});
			}
		});
	}

	// --- 4. Route Calculation Button Handler ---
	function initRouteCalculation(): void {
		const calcButtons = document.querySelectorAll<HTMLButtonElement>(
			'.dame-js-calc, #dame_calculate_route_button'
		);

		calcButtons.forEach((button: HTMLButtonElement): void => {
			button.addEventListener('click', (): void => {
				const group = button.dataset.group || 'event_location';
				const addressInput =
					document.querySelector<HTMLInputElement>(
						`.dame-js-address[data-group="${group}"]`
					) || (document.getElementById('dame_address_1') as HTMLInputElement | null);
				const postalCodeInput =
					document.querySelector<HTMLInputElement>(
						`.dame-js-zip[data-group="${group}"]`
					) || (document.getElementById('dame_postal_code') as HTMLInputElement | null);
				const cityInput =
					document.querySelector<HTMLInputElement>(
						`.dame-js-city[data-group="${group}"]`
					) || (document.getElementById('dame_city') as HTMLInputElement | null);
				const latitudeInput =
					document.querySelector<HTMLInputElement>(
						`.dame-js-lat[data-group="${group}"]`
					) || (document.getElementById('dame_latitude') as HTMLInputElement | null);
				const longitudeInput =
					document.querySelector<HTMLInputElement>(
						`.dame-js-long[data-group="${group}"], .dame-js-lng[data-group="${group}"]`
					) || (document.getElementById('dame_longitude') as HTMLInputElement | null);
				const distanceInput =
					document.querySelector<HTMLInputElement>(
						`.dame-js-dist[data-group="${group}"]`
					) || (document.getElementById('dame_distance') as HTMLInputElement | null);
				const travelTimeInput =
					document.querySelector<HTMLInputElement>(
						`.dame-js-time[data-group="${group}"]`
					) || (document.getElementById('dame_travel_time') as HTMLInputElement | null);

				const lat = latitudeInput ? latitudeInput.value.trim() : '';
				const lng = longitudeInput ? longitudeInput.value.trim() : '';

				if (lat && lng) {
					calculateRoute(
						lat,
						lng,
						distanceInput,
						travelTimeInput,
						button
					);
					return;
				}

				// If coordinates are missing, try geocoding from address text
				const addressQuery = [
					addressInput ? addressInput.value.trim() : '',
					postalCodeInput ? postalCodeInput.value.trim() : '',
					cityInput ? cityInput.value.trim() : '',
				]
					.filter(Boolean)
					.join(' ');

				if (!addressQuery) {
					alert(
						"Veuillez renseigner une adresse ou des coordonnées GPS pour calculer l'itinéraire."
					);
					return;
				}

				button.disabled = true;
				button.textContent = 'Calcul en cours...';

				fetch(
					`https://data.geopf.fr/geocodage/completion?text=${encodeURIComponent(
						addressQuery
					)}&type=StreetAddress`
				)
					.then((response: Response) => response.json() as Promise<IgnAddressResponse>)
					.then((data: IgnAddressResponse): void => {
						if (data.results && data.results.length > 0) {
							const result = data.results[0];
							if (latitudeInput && result.y !== undefined) {
								latitudeInput.value = String(result.y);
							}
							if (longitudeInput && result.x !== undefined) {
								longitudeInput.value = String(result.x);
							}
							if (result.y !== undefined && result.x !== undefined) {
								calculateRoute(
									result.y,
									result.x,
									distanceInput,
									travelTimeInput,
									button
								);
							} else {
								button.disabled = false;
								button.textContent = 'Calculer';
							}
						} else {
							button.disabled = false;
							button.textContent = 'Calculer';
							alert(
								"Adresse introuvable. Veuillez vérifier l'adresse saisie."
							);
						}
					})
					.catch((error: unknown): void => {
						console.error('Error geocoding address:', error);
						button.disabled = false;
						button.textContent = 'Calculer';
						alert(
							"Erreur lors de la recherche de l'adresse pour le calcul de l'itinéraire."
						);
					});
			});
		});
	}

	// --- 5. Generic Confirmation Handler (data-confirm) ---
	function initDataConfirm(): void {
		document.addEventListener('click', (e: MouseEvent): void => {
			const target = (e.target as HTMLElement | null)?.closest<HTMLElement>('[data-confirm]');
			if (!target) {
				return;
			}
			const message = target.getAttribute('data-confirm');
			if (message && !window.confirm(message)) {
				e.preventDefault();
				e.stopPropagation();
			}
		});
	}

	// --- 6. Generic Auto-Select Inputs (.dame-auto-select) ---
	function initAutoSelect(): void {
		document.addEventListener('focusin', (e: FocusEvent): void => {
			const target = e.target;
			if (target instanceof HTMLInputElement && target.classList.contains('dame-auto-select')) {
				target.select();
			}
		});

		document.addEventListener('click', (e: MouseEvent): void => {
			const target = e.target;
			if (target instanceof HTMLInputElement && target.classList.contains('dame-auto-select')) {
				target.select();
			}
		});
	}

	// Initialize all
	initAddressFields();
	initBirthCityFields();
	initRegionSync();
	initRouteCalculation();
	initDataConfirm();
	initAutoSelect();
});

