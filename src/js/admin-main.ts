interface ArticleItem {
	ID: number | string;
	post_title: string;
}

document.addEventListener('DOMContentLoaded', (): void => {
	const isNewPost = document.body.classList.contains('post-new-php');
	const postType = document.getElementById('post_type') as HTMLInputElement | null;

	if (isNewPost && postType && postType.value === 'adherent') {
		// Mettre le focus sur le champ Prénom
		const firstNameInput = document.getElementById('dame_first_name') as HTMLInputElement | null;
		if (firstNameInput) {
			firstNameInput.focus();
		}
	}

	// Auto-select department from postal code
	const postalCodeInput = document.getElementById('dame_postal_code') as HTMLInputElement | null;
	const departmentSelect = document.getElementById('dame_department') as HTMLSelectElement | null;

	if (postalCodeInput && departmentSelect) {
		postalCodeInput.addEventListener('keyup', function (this: HTMLInputElement): void {
			const postalCode = this.value;
			if (postalCode.length >= 2) {
				let departmentCode = postalCode.substring(0, 2);

				// Handle Corsica postal codes (20) which can be 2A or 2B
				if (postalCode.length >= 3 && postalCode.startsWith('20')) {
					const thirdDigit = parseInt(postalCode.substring(2, 3), 10);
					if (!isNaN(thirdDigit)) {
						if (thirdDigit <= 1) {
							// 200xx, 201xx
							departmentCode = '2A';
						} else {
							// 202xx and above
							departmentCode = '2B';
						}
					}
				} else if (
					departmentCode === '97' ||
					postalCode.startsWith('988')
				) {
					// Handle overseas departments (97x and 988)
					if (postalCode.length >= 3) {
						departmentCode = postalCode.substring(0, 3);
					}
				} else if (postalCode.startsWith('980')) {
					// Handle Monaco (98000 -> 06)
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

				// If the department was changed, trigger the change event to update the region
				if (departmentChanged) {
					departmentSelect.dispatchEvent(new Event('change'));
				}
			}
		});
	}

	// Auto-set membership status to 'Active' when date is entered
	const membershipDateInput = document.getElementById('dame_membership_date') as HTMLInputElement | null;
	const membershipStatusSelect = document.getElementById(
		'dame_membership_status'
	) as HTMLSelectElement | null;
	if (membershipDateInput && membershipStatusSelect) {
		membershipDateInput.addEventListener('change', function (this: HTMLInputElement): void {
			if (this.value && membershipStatusSelect.value !== 'A') {
				membershipStatusSelect.value = 'A';
			}
		});
	}

	// Department -> Region auto-selection
	const departmentSelectForRegion =
		document.getElementById('dame_department') as HTMLSelectElement | null;
	const regionSelect = document.getElementById('dame_region') as HTMLSelectElement | null;

	if (
		departmentSelectForRegion &&
		regionSelect &&
		typeof dame_admin_data !== 'undefined'
	) {
		departmentSelectForRegion.addEventListener('change', function (this: HTMLSelectElement): void {
			const departmentCode = this.value;
			const mapping = dame_admin_data.department_region_mapping;
			const regionCode = mapping ? mapping[departmentCode] : undefined;
			if (regionCode) {
				regionSelect.value = regionCode;
			} else {
				regionSelect.value = 'NA'; // Default to N/A if not found
			}
		});
	}

	// Minor auto-population logic
	const birthDateInput = document.getElementById('dame_birth_date') as HTMLInputElement | null;
	const adherentPhone = document.getElementById('dame_phone_number') as HTMLInputElement | null;
	const adherentEmail = document.getElementById('dame_email') as HTMLInputElement | null;
	const adherentAddress1 = document.getElementById('dame_address_1') as HTMLInputElement | null;
	const adherentAddress2 = document.getElementById('dame_address_2') as HTMLInputElement | null;
	const adherentPostalCode = document.getElementById('dame_postal_code') as HTMLInputElement | null;
	const adherentCity = document.getElementById('dame_city') as HTMLInputElement | null;

	const rep1Phone = document.getElementById('dame_legal_rep_1_phone') as HTMLInputElement | null;
	const rep1Email = document.getElementById('dame_legal_rep_1_email') as HTMLInputElement | null;
	const rep1Address1 = document.getElementById('dame_legal_rep_1_address_1') as HTMLInputElement | null;
	const rep1Address2 = document.getElementById('dame_legal_rep_1_address_2') as HTMLInputElement | null;
	const rep1PostalCode = document.getElementById(
		'dame_legal_rep_1_postal_code'
	) as HTMLInputElement | null;
	const rep1City = document.getElementById('dame_legal_rep_1_city') as HTMLInputElement | null;

	function isMinor(): boolean {
		if (!birthDateInput || !birthDateInput.value) {
			return false;
		}
		const birthDate = new Date(birthDateInput.value);
		const today = new Date();
		let age = today.getFullYear() - birthDate.getFullYear();
		const m = today.getMonth() - birthDate.getMonth();
		if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
			age--;
		}
		return age < 18;
	}

	function copyAdherentDataToRep1(): void {
		if (isNewPost && isMinor()) {
			if (adherentPhone && rep1Phone) {
				rep1Phone.value = adherentPhone.value;
			}
			if (adherentEmail && rep1Email) {
				rep1Email.value = adherentEmail.value;
			}
			if (adherentAddress1 && rep1Address1) {
				rep1Address1.value = adherentAddress1.value;
			}
			if (adherentAddress2 && rep1Address2) {
				rep1Address2.value = adherentAddress2.value;
			}
			if (adherentPostalCode && rep1PostalCode) {
				rep1PostalCode.value = adherentPostalCode.value;
			}
			if (adherentCity && rep1City) {
				rep1City.value = adherentCity.value;
			}
		}
	}

	if (isNewPost) {
		if (birthDateInput) {
			birthDateInput.addEventListener('change', copyAdherentDataToRep1);
		}
		[
			adherentPhone,
			adherentEmail,
			adherentAddress1,
			adherentAddress2,
			adherentPostalCode,
			adherentCity,
		].forEach((field: HTMLInputElement | null): void => {
			if (field) {
				field.addEventListener('input', copyAdherentDataToRep1);
			}
		});
	}

	// Mailing page article filter logic
	const categoryFilter = document.getElementById('dame-category-filter');
	if (categoryFilter && typeof dame_mailing_data !== 'undefined') {
		const articlesContainer = document.getElementById(
			'dame-articles-list-container'
		);
		const checkboxes = categoryFilter.querySelectorAll<HTMLInputElement>(
			'input[type="checkbox"]'
		);
		const storageKey = 'dame_selected_categories';

		function updateArticlesList(): void {
			if (!articlesContainer) {
				return;
			}
			const selectedCategories = Array.from(checkboxes)
				.filter((cb: HTMLInputElement) => cb.checked)
				.map((cb: HTMLInputElement) => cb.value);

			localStorage.setItem(
				storageKey,
				JSON.stringify(selectedCategories)
			);
			articlesContainer.style.opacity = '0.5';

			const data = new URLSearchParams();
			data.append('action', 'dame_get_filtered_articles');
			data.append('nonce', dame_mailing_data.nonce);
			selectedCategories.forEach((catId: string): void => {
				data.append('categories[]', catId);
			});

			fetch(dame_mailing_data.ajax_url, {
				method: 'POST',
				body: data,
			})
				.then((response: Response) => response.json() as Promise<{ success: boolean; data: ArticleItem[] & { message?: string } }>)
				.then((response) => {
					articlesContainer.style.opacity = '1';
					articlesContainer.innerHTML = ''; // Clear previous content

					if (response.success) {
						const articles = response.data;
						if (Array.isArray(articles) && articles.length > 0) {
							const select = document.createElement('select');
							select.id = 'dame_article_to_send';
							select.name = 'dame_article_to_send';
							select.style.width = '100%';
							select.style.maxWidth = '400px';

							articles.forEach((article: ArticleItem): void => {
								const option = document.createElement('option');
								option.value = String(article.ID);
								option.innerHTML = article.post_title; // Use innerHTML to decode entities
								select.appendChild(option);
							});
							articlesContainer.appendChild(select);
						} else {
							const noResult = document.createElement('p');
							noResult.textContent =
								dame_mailing_data.no_articles_found || 'Aucun article trouvé.';
							articlesContainer.appendChild(noResult);
						}
					} else {
						const errorMsg = document.createElement('p');
						errorMsg.style.color = 'red';
						errorMsg.textContent =
							(response.data as { message?: string })?.message ||
							dame_mailing_data.generic_error || 'Une erreur est survenue.';
						articlesContainer.appendChild(errorMsg);
					}
				})
				.catch((error: unknown): void => {
					articlesContainer.style.opacity = '1';
					const errorMsg = document.createElement('p');
					errorMsg.style.color = 'red';
					errorMsg.textContent = dame_mailing_data.generic_error || 'Une erreur est survenue.';
					articlesContainer.innerHTML = '';
					articlesContainer.appendChild(errorMsg);

					console.error('Error fetching articles:', error);
				});
		}

		function loadInitialState(): void {
			try {
				const savedRaw = localStorage.getItem(storageKey);
				const savedCategories: string[] = savedRaw ? JSON.parse(savedRaw) : [];
				if (Array.isArray(savedCategories) && savedCategories.length > 0) {
					checkboxes.forEach((cb: HTMLInputElement): void => {
						if (savedCategories.includes(cb.value)) {
							cb.checked = true;
						}
					});
				}
			} catch (e: unknown) {
				console.error('Failed to parse saved categories:', e);
			}
			updateArticlesList();
		}

		checkboxes.forEach((cb: HTMLInputElement): void =>
			cb.addEventListener('change', updateArticlesList)
		);

		loadInitialState();
	}

	// Initialize color picker for taxonomy fields
	if (
		typeof jQuery !== 'undefined' &&
		typeof jQuery.fn?.wpColorPicker !== 'undefined'
	) {
		jQuery('.dame-color-picker').wpColorPicker();
	}

	// Auto-copy start date to end date for agenda events
	if (document.body.classList.contains('post-type-dame_agenda')) {
		const startDateInput = document.getElementById('dame_start_date') as HTMLInputElement | null;
		const endDateInput = document.getElementById('dame_end_date') as HTMLInputElement | null;

		if (startDateInput && endDateInput) {
			startDateInput.addEventListener('blur', function (this: HTMLInputElement): void {
				if (this.value && !endDateInput.value) {
					endDateInput.value = this.value;
				}
			});
		}

		// Form validation for required fields
		const postForm = document.getElementById('post') as HTMLFormElement | null;
		if (postForm) {
			postForm.addEventListener('submit', function (e: Event): void {
				const startDateEl = document.getElementById('dame_start_date') as HTMLInputElement | null;
				const endDateEl = document.getElementById('dame_end_date') as HTMLInputElement | null;
				const startDate = startDateEl ? startDateEl.value : '';
				const endDate = endDateEl ? endDateEl.value : '';
				const categoryCheckboxes = document.querySelectorAll<HTMLInputElement>(
					'#dame_agenda_categorychecklist input[type="checkbox"]'
				);
				let categoryChecked = false;

				for (let i = 0; i < categoryCheckboxes.length; i++) {
					if (categoryCheckboxes[i].checked) {
						categoryChecked = true;
						break;
					}
				}

				const errors: string[] = [];
				if (!startDate) {
					errors.push('La date de début est obligatoire.');
				}
				if (!endDate) {
					errors.push('La date de fin est obligatoire.');
				}
				if (!categoryChecked) {
					// Check if the add-new-category-pop is visible, which means the user is adding a new one.
					// This is a workaround for when a new category is being added.
					const newCategoryInput = document.getElementById(
						'newdame_agenda_category'
					) as HTMLInputElement | null;
					if (!newCategoryInput || !newCategoryInput.value) {
						errors.push(
							'Veuillez sélectionner au moins une catégorie.'
						);
					}
				}

				if (errors.length > 0) {
					e.preventDefault();
					alert(errors.join('\n'));
				}
			});
		}
	}
});
