interface IgnFeature {
	fulltext: string;
	zipcode?: string;
	city?: string;
	x?: number | string;
	y?: number | string;
	[key: string]: unknown;
}

interface IgnResponse {
	results?: IgnFeature[];
}

interface ItineraireResponse {
	distance?: number;
	duration?: number;
	[key: string]: unknown;
}

document.addEventListener('DOMContentLoaded', (): void => {
	function initAutocomplete(
		addressId: string,
		postalCodeId: string,
		cityId: string,
		latitudeId?: string,
		longitudeId?: string,
		distanceId?: string,
		travelTimeId?: string
	): void {
		const addressInput = document.getElementById(
			addressId
		) as HTMLInputElement | null;
		if (!addressInput) {
			return;
		}

		const postalCodeInput = document.getElementById(
			postalCodeId
		) as HTMLInputElement | null;
		const cityInput = document.getElementById(
			cityId
		) as HTMLInputElement | null;
		const latitudeInput = latitudeId
			? (document.getElementById(latitudeId) as HTMLInputElement | null)
			: null;
		const longitudeInput = longitudeId
			? (document.getElementById(longitudeId) as HTMLInputElement | null)
			: null;
		const distanceInput = distanceId
			? (document.getElementById(distanceId) as HTMLInputElement | null)
			: null;
		const travelTimeInput = travelTimeId
			? (document.getElementById(travelTimeId) as HTMLInputElement | null)
			: null;
		const wrapper = addressInput.closest<HTMLElement>(
			'.dame-autocomplete-wrapper'
		);

		if (wrapper) {
			const resultsContainer = document.createElement('div');
			resultsContainer.className = 'dame-address-suggestions';
			resultsContainer.style.display = 'none';
			wrapper.appendChild(resultsContainer);

			let debounceTimer: ReturnType<typeof setTimeout> | undefined;
			let highlightedIndex = -1;

			addressInput.addEventListener(
				'keyup',
				function (this: HTMLInputElement, e: KeyboardEvent): void {
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
							.then(
								(response: Response) =>
									response.json() as Promise<IgnResponse>
							)
							.then((data: IgnResponse): void => {
								resultsContainer.innerHTML = '';
								highlightedIndex = -1;
								if (data.results && data.results.length > 0) {
									resultsContainer.style.display = 'block';
									const results = data.results;
									results.forEach(
										(result: IgnFeature): void => {
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
										}
									);
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
				}
			);

			addressInput.addEventListener(
				'keydown',
				(e: KeyboardEvent): void => {
					const suggestions =
						resultsContainer.querySelectorAll<HTMLElement>(
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
				}
			);

			function updateHighlight(
				suggestions: NodeListOf<HTMLElement>,
				index: number
			): void {
				suggestions.forEach(
					(suggestion: HTMLElement, i: number): void => {
						if (i === index) {
							suggestion.classList.add('highlighted');
						} else {
							suggestion.classList.remove('highlighted');
						}
					}
				);
			}

			function selectSuggestion(suggestion: HTMLElement): void {
				if (!suggestion.dataset.feature) {
					return;
				}
				const featureProperties = JSON.parse(
					suggestion.dataset.feature
				) as IgnFeature;
				const streetAddress = featureProperties.fulltext.split(',')[0];
				if (addressInput) {
					addressInput.value = streetAddress.trim();
				}

				if (postalCodeInput && featureProperties.zipcode) {
					postalCodeInput.value = featureProperties.zipcode;
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

				// If the global pre-fill function exists (on the public form), call it.
				if (typeof window.prefillRep1 === 'function') {
					window.prefillRep1();
				}

				resultsContainer.innerHTML = '';
				resultsContainer.style.display = 'none';
				highlightedIndex = -1;
			}

			resultsContainer.addEventListener(
				'click',
				(e: MouseEvent): void => {
					const target = e.target as HTMLElement | null;
					if (target?.classList.contains('dame-suggestion-item')) {
						selectSuggestion(target);
					}
				}
			);

			document.addEventListener('click', (e: MouseEvent): void => {
				if (!wrapper.contains(e.target as Node)) {
					resultsContainer.style.display = 'none';
					highlightedIndex = -1;
				}
			});
		}
	}

	function calculateRoute(
		destLat: string | number,
		destLng: string | number,
		distanceInput: HTMLInputElement | null,
		travelTimeInput: HTMLInputElement | null
	): void {
		if (typeof dame_admin_data === 'undefined' || !dame_admin_data) {
			return;
		}

		const startLat = dame_admin_data.assoc_latitude;
		const startLng = dame_admin_data.assoc_longitude;

		if (!startLat || !startLng) {
			return;
		}

		const url = `https://data.geopf.fr/navigation/itineraire?resource=bdtopo-osrm&start=${startLng},${startLat}&end=${destLng},${destLat}&profile=car&optimization=fastest&distanceUnit=kilometer&timeUnit=hour`;

		fetch(url)
			.then(
				(response: Response) =>
					response.json() as Promise<ItineraireResponse>
			)
			.then((data: ItineraireResponse): void => {
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
			.catch((error: unknown): void =>
				console.error('Error calculating route:', error)
			);
	}

	// Initialize for all address fields
	initAutocomplete(
		'dame_address_1',
		'dame_postal_code',
		'dame_city',
		'dame_latitude',
		'dame_longitude',
		'dame_distance',
		'dame_travel_time'
	);

	const calculateButton = document.getElementById(
		'dame_calculate_route_button'
	);
	if (calculateButton) {
		calculateButton.addEventListener('click', (): void => {
			const latInput = document.getElementById(
				'dame_latitude'
			) as HTMLInputElement | null;
			const lngInput = document.getElementById(
				'dame_longitude'
			) as HTMLInputElement | null;
			const distanceInput = document.getElementById(
				'dame_distance'
			) as HTMLInputElement | null;
			const travelTimeInput = document.getElementById(
				'dame_travel_time'
			) as HTMLInputElement | null;

			if (latInput?.value && lngInput?.value) {
				calculateRoute(
					latInput.value,
					lngInput.value,
					distanceInput,
					travelTimeInput
				);
			}
		});
	}

	initAutocomplete(
		'dame_legal_rep_1_address_1',
		'dame_legal_rep_1_postal_code',
		'dame_legal_rep_1_city'
	);
	initAutocomplete(
		'dame_legal_rep_2_address_1',
		'dame_legal_rep_2_postal_code',
		'dame_legal_rep_2_city'
	);
	initAutocomplete(
		'dame_assoc_address_1',
		'dame_assoc_postal_code',
		'dame_assoc_city',
		'dame_assoc_latitude',
		'dame_assoc_longitude'
	);

	/**
	 * Postal Code -> Department Link (Only for main address)
	 */
	const mainPostalCodeField = document.getElementById(
		'dame_postal_code'
	) as HTMLInputElement | null;
	const departmentSelect = document.getElementById(
		'dame_department'
	) as HTMLSelectElement | null;
	if (mainPostalCodeField && departmentSelect) {
		mainPostalCodeField.addEventListener('keyup', (): void => {
			const postalCode = mainPostalCodeField.value;
			if (postalCode.length >= 2) {
				let departmentCode = postalCode.substring(0, 2);
				if (departmentCode === '20') {
					return;
				}
				if (
					(departmentCode === '97' || postalCode.startsWith('988')) &&
					postalCode.length >= 3
				) {
					departmentCode = postalCode.substring(0, 3);
				} else if (postalCode.startsWith('980')) {
					departmentCode = '06';
				}

				let departmentChanged = false;
				for (let i = 0; i < departmentSelect.options.length; i++) {
					const option = departmentSelect.options[i];
					if (option.value === departmentCode) {
						if (departmentSelect.value !== departmentCode) {
							departmentSelect.value = departmentCode;
							departmentChanged = true;
						}
						break;
					}
				}

				if (departmentChanged) {
					departmentSelect.dispatchEvent(new Event('change'));
				}
			}
		});
	}

	/**
	 * Membership Date -> Membership Status Link
	 */
	const membershipDateInput = document.getElementById(
		'dame_membership_date'
	) as HTMLInputElement | null;
	const membershipStatusSelect = document.getElementById(
		'dame_membership_status'
	) as HTMLSelectElement | null;
	if (membershipDateInput && membershipStatusSelect) {
		membershipDateInput.addEventListener('change', (): void => {
			if (membershipDateInput.value) {
				membershipStatusSelect.value = 'A';
			}
		});
	}
});
