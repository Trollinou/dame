/**
 * Admin Mailing Interactivity.
 *
 * Logic for toggling filters, searchable lists, and automatic contact pre-checking.
 */

document.addEventListener('DOMContentLoaded', (): void => {
	// 1. Toggling Adherent Methods
	const adMethodRadios = document.querySelectorAll<HTMLInputElement>(
		'input[name="dame_adherent_method"]'
	);
	const adGroupWrap = document.querySelector<HTMLElement>('.dame-adherent-group-wrap');
	const adManualWrap = document.querySelector<HTMLElement>('.dame-adherent-manual-wrap');

	if (adMethodRadios.length > 0 && adGroupWrap && adManualWrap) {
		adMethodRadios.forEach((radio: HTMLInputElement): void => {
			radio.addEventListener('change', function (this: HTMLInputElement): void {
				if (this.value === 'group') {
					adGroupWrap.classList.remove('dame-hidden');
					adManualWrap.classList.add('dame-hidden');
				} else {
					adGroupWrap.classList.add('dame-hidden');
					adManualWrap.classList.remove('dame-hidden');
				}
			});
		});
	}

	// 2. Toggling Contact Methods with "Magic" Pre-checking
	const contactMethodRadios = document.querySelectorAll<HTMLInputElement>(
		'input[name="dame_contact_method"]'
	);
	const contactGroupWrap = document.querySelector<HTMLElement>('.dame-contact-group-wrap');
	const contactManualWrap = document.querySelector<HTMLElement>(
		'.dame-contact-manual-wrap'
	);

	if (
		contactMethodRadios.length > 0 &&
		contactGroupWrap &&
		contactManualWrap
	) {
		const initialChecked = document.querySelector<HTMLInputElement>(
			'input[name="dame_contact_method"]:checked'
		);
		let currentContactMethod = initialChecked ? initialChecked.value : 'group';

		contactMethodRadios.forEach((radio: HTMLInputElement): void => {
			radio.addEventListener('change', function (this: HTMLInputElement): void {
				const newMethod = this.value;

				// Confirmation lors du retour au mode Critères depuis Manuel
				if (
					currentContactMethod === 'manual' &&
					newMethod === 'group'
				) {
					if (
						confirm(
							'Souhaitez-vous vraiment revenir à la sélection par critères ? Vos filtres actuels et votre sélection manuelle seront réinitialisés.'
						)
					) {
						resetContactCriteria();
					} else {
						// Annulation : on restaure le bouton radio Manuel
						const manualRadio = document.querySelector<HTMLInputElement>(
							'input[name="dame_contact_method"][value="manual"]'
						);
						if (manualRadio) {
							manualRadio.checked = true;
						}
						return;
					}
				}

				currentContactMethod = newMethod;

				if (newMethod === 'group') {
					contactGroupWrap.classList.remove('dame-hidden');
					contactManualWrap.classList.add('dame-hidden');

					// Reset manual selection when going back to Criteria
					contactManualWrap
						.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')
						.forEach((cb: HTMLInputElement) => (cb.checked = false));
					const manualList = contactManualWrap.querySelector<HTMLElement>(
						'.dame-checkbox-list'
					);
					if (manualList) {
						reorderList(manualList);
						updateSelectionCount(manualList);
					}
				} else {
					contactGroupWrap.classList.add('dame-hidden');
					contactManualWrap.classList.remove('dame-hidden');

					// MAGIC: Pre-check based on criteria
					performContactPrecheck();
				}
			});
		});
	}

	// Listen for changes to checkboxes to trigger reordering and count update
	document.addEventListener('change', (e: Event): void => {
		const target = e.target as HTMLElement | null;
		if (target?.matches('.dame-checkbox-list input[type="checkbox"]')) {
			const listContainer = target.closest<HTMLElement>('.dame-checkbox-list');
			if (listContainer) {
				reorderList(listContainer);
				updateSelectionCount(listContainer);
			}
		}

		// Logic for syncing Region -> Departments
		if (
			target?.matches(
				'.dame-region-criteria-list input[type="checkbox"]'
			)
		) {
			const checkbox = target as HTMLInputElement;
			const regionCode = checkbox.value;
			const isChecked = checkbox.checked;
			const depts =
				typeof dame_mailing_data !== 'undefined' &&
				dame_mailing_data.regionMapping
					? dame_mailing_data.regionMapping[regionCode] || []
					: [];

			const deptList = document.querySelector<HTMLElement>(
				'.dame-dept-criteria-list .dame-checkbox-list'
			);
			if (!deptList || depts.length === 0) {
				return;
			}

			depts.forEach((code: string): void => {
				const deptCheckbox = deptList.querySelector<HTMLInputElement>(
					`input[value="${code}"]`
				);
				if (deptCheckbox) {
					deptCheckbox.checked = isChecked;
				}
			});

			// Trigger reorder and count update for departments list
			reorderList(deptList);
			updateSelectionCount(deptList);
		}
	});

	/**
	 * Updates the selection counter for a list.
	 */
	function updateSelectionCount(listContainer: HTMLElement): void {
		const wrapper = listContainer.closest<HTMLElement>('.dame-searchable-list-wrapper');
		if (!wrapper) {
			return;
		}
		const countSpan = wrapper.querySelector<HTMLElement>('.dame-selection-count');
		if (countSpan) {
			const checkedCount = listContainer.querySelectorAll(
				'input[type="checkbox"]:checked'
			).length;
			countSpan.textContent = String(checkedCount);
		}
	}

	/**
	 * Reorders a list to move checked items to the top.
	 */
	function reorderList(listContainer: HTMLElement): void {
		const labels = Array.from(listContainer.querySelectorAll<HTMLLabelElement>('label'));

		// Sort labels: checked first, then alphabetical
		labels.sort((a, b) => {
			const aInput = a.querySelector<HTMLInputElement>('input');
			const bInput = b.querySelector<HTMLInputElement>('input');
			const aChecked = aInput ? aInput.checked : false;
			const bChecked = bInput ? bInput.checked : false;

			if (aChecked && !bChecked) {
				return -1;
			}
			if (!aChecked && bChecked) {
				return 1;
			}

			const aText = a.textContent ? a.textContent.trim() : '';
			const bText = b.textContent ? b.textContent.trim() : '';
			return aText.localeCompare(bText);
		});

		// Append sorted elements back to container
		labels.forEach((label: HTMLLabelElement) => listContainer.appendChild(label));
	}

	/**
	 * Réinitialise tous les filtres de critères pour les contacts.
	 */
	function resetContactCriteria(): void {
		const typesSelect = document.getElementById(
			'dame_contact_types_select'
		) as HTMLSelectElement | null;
		if (typesSelect) {
			Array.from(typesSelect.options).forEach(
				(opt: HTMLOptionElement) => (opt.selected = false)
			);
		}
		const criteriaLists = document.querySelectorAll<HTMLElement>(
			'.dame-dept-criteria-list, .dame-region-criteria-list'
		);
		criteriaLists.forEach((container: HTMLElement): void => {
			container
				.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')
				.forEach((cb: HTMLInputElement) => (cb.checked = false));
			const list = container.querySelector<HTMLElement>('.dame-checkbox-list');
			if (list) {
				reorderList(list);
				updateSelectionCount(list);
			}
		});
	}

	/**
	 * Scans criteria and checks corresponding manual checkboxes.
	 */
	function performContactPrecheck(): void {
		const typesSelect = document.getElementById(
			'dame_contact_types_select'
		) as HTMLSelectElement | null;
		if (!typesSelect || !contactManualWrap) {
			return;
		}

		const selectedTypes = Array.from(typesSelect.selectedOptions).map(
			(opt: HTMLOptionElement) => opt.value
		);
		const selectedDepts = Array.from(
			document.querySelectorAll<HTMLInputElement>(
				'.dame-dept-criteria-list input[type="checkbox"]:checked'
			)
		).map((cb: HTMLInputElement) => cb.value);

		const hasTypes = selectedTypes.length > 0;
		const hasDepts = selectedDepts.length > 0;

		const contactCheckboxes = contactManualWrap.querySelectorAll<HTMLInputElement>(
			'input[type="checkbox"]'
		);

		contactCheckboxes.forEach((cb: HTMLInputElement): void => {
			const dept = cb.getAttribute('data-dept') || '';
			const typesAttr = cb.getAttribute('data-types') || '';
			const types = typesAttr.split(',');

			const matchType = types.some((t: string) => selectedTypes.includes(t));
			const matchDept = selectedDepts.includes(dept);

			let shouldCheck = false;

			if (hasTypes && hasDepts) {
				// Intersection du Type et des départements sélectionnés
				shouldCheck = matchType && matchDept;
			} else if (hasTypes) {
				// Seulement par type
				shouldCheck = matchType;
			} else if (hasDepts) {
				// Seulement par département
				shouldCheck = matchDept;
			}

			if (shouldCheck) {
				cb.checked = true;
			}
		});

		// Trigger reorder after magic pre-check
		const manualList = contactManualWrap.querySelector<HTMLElement>(
			'.dame-checkbox-list'
		);
		if (manualList) {
			reorderList(manualList);
			updateSelectionCount(manualList);
		}
	}

	// 3. Searchable Lists Logic (Universal)
	const searchInputs = document.querySelectorAll<HTMLInputElement>('.dame-list-search');
	const checkboxLists = document.querySelectorAll<HTMLElement>('.dame-checkbox-list');

	// Initial reorder and count update for all lists on page load
	checkboxLists.forEach((list: HTMLElement): void => {
		reorderList(list);
		updateSelectionCount(list);
	});

	/**
	 * Normalizes text by converting to lowercase and removing accents.
	 */
	function normalizeText(text: string | null | undefined): string {
		return (text || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '');
	}

	searchInputs.forEach((input: HTMLInputElement): void => {
		input.addEventListener('keyup', function (this: HTMLInputElement): void {
			const wrapper = this.closest<HTMLElement>('.dame-searchable-list-wrapper');
			const list = wrapper
				? wrapper.querySelector<HTMLElement>('.dame-checkbox-list')
				: null;
			if (!list) {
				return;
			}

			const filter = normalizeText(this.value);

			const labels = list.querySelectorAll<HTMLLabelElement>('label');
			labels.forEach((label: HTMLLabelElement): void => {
				const text = normalizeText(label.textContent);
				if (text.indexOf(filter) > -1) {
					label.style.display = 'block';
				} else {
					label.style.display = 'none';
				}
			});
		});
	});

	// 4. Already sent warning logic
	const messageSelect = document.getElementById('dame_message_to_send') as HTMLSelectElement | null;
	const warningDiv = document.getElementById('dame_message_warning') as HTMLElement | null;

	if (messageSelect && warningDiv) {
		messageSelect.addEventListener('change', function (this: HTMLSelectElement): void {
			const selectedOption = this.options[this.selectedIndex];
			const status = selectedOption ? selectedOption.getAttribute('data-status') : null;
			const submitBtn = document.querySelector<HTMLInputElement>('input[type="submit"]');

			if (status === 'scheduled') {
				warningDiv.style.display = 'block';
				warningDiv.style.color = '#d63638';
				warningDiv.textContent =
					"Ce message est actuellement en cours d'envoi. Veuillez attendre la fin du traitement.";
				if (submitBtn) {
					submitBtn.disabled = true;
				}
			} else if (status === 'sent') {
				warningDiv.style.display = 'block';
				warningDiv.style.color = '#2271b1';
				warningDiv.textContent =
					"Ce message a déjà été expédié. Tout nouvel envoi sera incrémental : les personnes l'ayant déjà reçu seront automatiquement ignorées.";
				if (submitBtn) {
					submitBtn.disabled = false;
				}
			} else {
				warningDiv.style.display = 'none';
				if (submitBtn) {
					submitBtn.disabled = false;
				}
			}
		});
	}
});
