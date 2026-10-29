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
	const adGroupWrap = document.querySelector<HTMLElement>(
		'.dame-adherent-group-wrap'
	);
	const adManualWrap = document.querySelector<HTMLElement>(
		'.dame-adherent-manual-wrap'
	);

	if (adMethodRadios.length > 0 && adGroupWrap && adManualWrap) {
		adMethodRadios.forEach((radio: HTMLInputElement): void => {
			radio.addEventListener('change', (): void => {
				if (radio.value === 'group') {
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
	const contactGroupWrap = document.querySelector<HTMLElement>(
		'.dame-contact-group-wrap'
	);
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
		let currentContactMethod = initialChecked
			? initialChecked.value
			: 'group';

		contactMethodRadios.forEach((radio: HTMLInputElement): void => {
			radio.addEventListener('change', (): void => {
				const newMethod = radio.value;

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
						const manualRadio =
							document.querySelector<HTMLInputElement>(
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
						.querySelectorAll<HTMLInputElement>(
							'input[type="checkbox"]'
						)
						.forEach(
							(cb: HTMLInputElement) => (cb.checked = false)
						);
					const manualList =
						contactManualWrap.querySelector<HTMLElement>(
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
			const listContainer = target.closest<HTMLElement>(
				'.dame-checkbox-list'
			);
			if (listContainer) {
				reorderList(listContainer);
				updateSelectionCount(listContainer);
			}
		}

		// Logic for syncing Region -> Departments
		if (
			target?.matches('.dame-region-criteria-list input[type="checkbox"]')
		) {
			const checkbox = target as HTMLInputElement;
			checkbox.indeterminate = false;
			checkbox.classList.remove('is-indeterminate');
			const regionCode = checkbox.value;
			const isChecked = checkbox.checked;
			const mapping = getRegionMapping();
			const depts = mapping[regionCode] || [];

			const deptList = document.querySelector<HTMLElement>(
				'.dame-dept-criteria-list .dame-checkbox-list'
			);
			if (deptList && depts.length > 0) {
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
				syncAllRegions();
			}
		}

		// Logic for syncing Department -> Region (tri-state indeterminate)
		if (
			target?.matches('.dame-dept-criteria-list input[type="checkbox"]')
		) {
			const regionList = document.querySelector<HTMLElement>(
				'.dame-region-criteria-list .dame-checkbox-list'
			);
			if (regionList) {
				syncAllRegions();
				reorderList(regionList);
				syncAllRegions();
				updateSelectionCount(regionList);
			}
		}
	});

	/**
	 * Retrieves the Region -> Departments mapping from localized global data.
	 */
	function getRegionMapping(): Record<string, string[]> {
		if (
			typeof dame_mailing_data !== 'undefined' &&
			dame_mailing_data.regionMapping
		) {
			return dame_mailing_data.regionMapping;
		} else if (
			typeof dameMailingData !== 'undefined' &&
			dameMailingData?.regionMapping
		) {
			return dameMailingData.regionMapping;
		}
		return {};
	}

	/**
	 * Updates the tri-state (checked / unchecked / indeterminate) of a region checkbox.
	 * @param regionCode
	 */
	function syncRegionState(regionCode: string): void {
		const mapping = getRegionMapping();
		const depts = mapping[regionCode] || [];
		if (depts.length === 0) {
			return;
		}

		const regionList = document.querySelector<HTMLElement>(
			'.dame-region-criteria-list .dame-checkbox-list'
		);
		const deptList = document.querySelector<HTMLElement>(
			'.dame-dept-criteria-list .dame-checkbox-list'
		);
		if (!regionList || !deptList) {
			return;
		}

		const regionCheckbox = regionList.querySelector<HTMLInputElement>(
			`input[value="${regionCode}"]`
		);
		if (!regionCheckbox) {
			return;
		}

		let checkedCount = 0;
		depts.forEach((code: string): void => {
			const deptCheckbox = deptList.querySelector<HTMLInputElement>(
				`input[value="${code}"]`
			);
			if (deptCheckbox?.checked) {
				checkedCount++;
			}
		});

		if (checkedCount === depts.length) {
			regionCheckbox.checked = true;
			regionCheckbox.indeterminate = false;
			regionCheckbox.classList.remove('is-indeterminate');
		} else if (checkedCount === 0) {
			regionCheckbox.checked = false;
			regionCheckbox.indeterminate = false;
			regionCheckbox.classList.remove('is-indeterminate');
		} else {
			regionCheckbox.checked = false;
			regionCheckbox.indeterminate = true;
			regionCheckbox.classList.add('is-indeterminate');
		}
	}

	/**
	 * Synchronizes all region checkboxes based on currently checked departments.
	 */
	function syncAllRegions(): void {
		const mapping = getRegionMapping();
		Object.keys(mapping).forEach((regionCode: string): void => {
			syncRegionState(regionCode);
		});
	}

	/**
	 * Updates the selection counter for a list.
	 * @param listContainer
	 */
	function updateSelectionCount(listContainer: HTMLElement): void {
		const wrapper = listContainer.closest<HTMLElement>(
			'.dame-searchable-list-wrapper'
		);
		if (!wrapper) {
			return;
		}
		const countSpan = wrapper.querySelector<HTMLElement>(
			'.dame-selection-count'
		);
		if (countSpan) {
			const checkedCount = listContainer.querySelectorAll(
				'input[type="checkbox"]:checked, input[type="checkbox"].is-indeterminate'
			).length;
			countSpan.textContent = String(checkedCount);
		}
	}

	/**
	 * Reorders a list to move checked items to the top.
	 * @param listContainer
	 */
	function reorderList(listContainer: HTMLElement): void {
		const labels = Array.from(
			listContainer.querySelectorAll<HTMLLabelElement>('label')
		);

		// Sort labels: checked first (2), indeterminate next (1), unchecked last (0), then alphabetical
		labels.sort((a, b) => {
			const aInput = a.querySelector<HTMLInputElement>('input');
			const bInput = b.querySelector<HTMLInputElement>('input');

			const getScore = (input: HTMLInputElement | null): number => {
				if (input?.checked) {
					return 2;
				}
				if (
					input?.indeterminate ||
					input?.classList.contains('is-indeterminate')
				) {
					return 1;
				}
				return 0;
			};

			const aScore = getScore(aInput);
			const bScore = getScore(bInput);

			if (aScore !== bScore) {
				return bScore - aScore;
			}

			const aText = a.textContent ? a.textContent.trim() : '';
			const bText = b.textContent ? b.textContent.trim() : '';
			return aText.localeCompare(bText);
		});

		// Append sorted elements back to container
		labels.forEach((label: HTMLLabelElement) =>
			listContainer.appendChild(label)
		);
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
				.forEach((cb: HTMLInputElement) => {
					cb.checked = false;
					cb.indeterminate = false;
					cb.classList.remove('is-indeterminate');
				});
			const list = container.querySelector<HTMLElement>(
				'.dame-checkbox-list'
			);
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
		const hasTypes = selectedTypes.length > 0;
		// Au moins un type de contact doit obligatoirement être sélectionné
		if (!hasTypes) {
			return;
		}

		const selectedDepts = Array.from(
			document.querySelectorAll<HTMLInputElement>(
				'.dame-dept-criteria-list input[type="checkbox"]:checked'
			)
		).map((cb: HTMLInputElement) => cb.value);

		const hasDepts = selectedDepts.length > 0;

		const contactCheckboxes =
			contactManualWrap.querySelectorAll<HTMLInputElement>(
				'input[type="checkbox"]'
			);

		contactCheckboxes.forEach((cb: HTMLInputElement): void => {
			const dept = cb.getAttribute('data-dept') || '';
			const typesAttr = cb.getAttribute('data-types') || '';
			const types = typesAttr.split(',');

			const matchType = types.some((t: string) =>
				selectedTypes.includes(t)
			);
			// Si des départements sont sélectionnés, restreindre strictement à ces départements
			const matchGeo = !hasDepts || selectedDepts.includes(dept);

			if (matchType && matchGeo) {
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

	// 3. Toggling HelloAsso Methods with Pre-checking
	const haMethodRadios = document.querySelectorAll<HTMLInputElement>(
		'input[name="dame_ha_method"]'
	);
	const haGroupWrap = document.querySelector<HTMLElement>(
		'.dame-ha-group-wrap'
	);
	const haManualWrap = document.querySelector<HTMLElement>(
		'.dame-ha-manual-wrap'
	);

	if (haMethodRadios.length > 0 && haGroupWrap && haManualWrap) {
		const initialHaChecked = document.querySelector<HTMLInputElement>(
			'input[name="dame_ha_method"]:checked'
		);
		let currentHaMethod = initialHaChecked
			? initialHaChecked.value
			: 'group';

		haMethodRadios.forEach((radio: HTMLInputElement): void => {
			radio.addEventListener('change', (): void => {
				const newMethod = radio.value;

				if (currentHaMethod === 'manual' && newMethod === 'group') {
					if (
						confirm(
							'Souhaitez-vous vraiment revenir à la sélection par critères ? Vos filtres actuels et votre sélection manuelle seront réinitialisés.'
						)
					) {
						resetHaCriteria();
					} else {
						const manualRadio =
							document.querySelector<HTMLInputElement>(
								'input[name="dame_ha_method"][value="manual"]'
							);
						if (manualRadio) {
							manualRadio.checked = true;
						}
						return;
					}
				}

				currentHaMethod = newMethod;

				if (newMethod === 'group') {
					haGroupWrap.classList.remove('dame-hidden');
					haManualWrap.classList.add('dame-hidden');

					haManualWrap
						.querySelectorAll<HTMLInputElement>(
							'input[type="checkbox"]'
						)
						.forEach(
							(cb: HTMLInputElement) => (cb.checked = false)
						);
					const manualList =
						haManualWrap.querySelector<HTMLElement>(
							'.dame-checkbox-list'
						);
					if (manualList) {
						reorderList(manualList);
						updateSelectionCount(manualList);
					}
				} else {
					haGroupWrap.classList.add('dame-hidden');
					haManualWrap.classList.remove('dame-hidden');

					performHaPrecheck();
				}
			});
		});
	}

	function resetHaCriteria(): void {
		if (!haGroupWrap || !haManualWrap) {
			return;
		}
		haGroupWrap
			.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')
			.forEach((cb: HTMLInputElement) => (cb.checked = false));

		haManualWrap
			.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')
			.forEach((cb: HTMLInputElement) => (cb.checked = false));

		const lists = [
			haGroupWrap.querySelector<HTMLElement>('.dame-checkbox-list'),
			haManualWrap.querySelector<HTMLElement>('.dame-checkbox-list'),
		];
		lists.forEach((list: HTMLElement | null) => {
			if (list) {
				reorderList(list);
				updateSelectionCount(list);
			}
		});
	}

	function performHaPrecheck(): void {
		if (!haGroupWrap || !haManualWrap) {
			return;
		}

		const selectedCampaigns = Array.from(
			haGroupWrap.querySelectorAll<HTMLInputElement>(
				'input[name="dame_recipient_ha_campaigns[]"]:checked'
			)
		).map((cb: HTMLInputElement) => cb.value);

		if (selectedCampaigns.length === 0) {
			return;
		}

		const manualCheckboxes =
			haManualWrap.querySelectorAll<HTMLInputElement>(
				'input[name="dame_manual_ha_items[]"]'
			);

		manualCheckboxes.forEach((cb: HTMLInputElement) => {
			const campaignId = cb.getAttribute('data-campaign');
			if (campaignId && selectedCampaigns.includes(campaignId)) {
				cb.checked = true;
			}
		});

		const manualList =
			haManualWrap.querySelector<HTMLElement>('.dame-checkbox-list');
		if (manualList) {
			reorderList(manualList);
			updateSelectionCount(manualList);
		}
	}

	// 4. Searchable Lists Logic (Universal)
	const searchInputs =
		document.querySelectorAll<HTMLInputElement>('.dame-list-search');
	const checkboxLists = document.querySelectorAll<HTMLElement>(
		'.dame-checkbox-list'
	);

	// Initial reorder and count update for all lists on page load
	checkboxLists.forEach((list: HTMLElement): void => {
		reorderList(list);
		updateSelectionCount(list);
	});
	syncAllRegions();

	/**
	 * Normalizes text by converting to lowercase and removing accents.
	 * @param text
	 */
	function normalizeText(text: string | null | undefined): string {
		return (text || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '');
	}

	searchInputs.forEach((input: HTMLInputElement): void => {
		input.addEventListener('keyup', (): void => {
			const wrapper = input.closest<HTMLElement>(
				'.dame-searchable-list-wrapper'
			);
			const list = wrapper
				? wrapper.querySelector<HTMLElement>('.dame-checkbox-list')
				: null;
			if (!list) {
				return;
			}

			const filter = normalizeText(input.value);

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

	// 4. Already sent warning and sticky action bar logic
	const messageSelect = document.getElementById(
		'dame_message_to_send'
	) as HTMLSelectElement | null;
	const prioritySelect = document.getElementById(
		'dame_sending_priority'
	) as HTMLSelectElement | null;
	const warningDiv = document.getElementById(
		'dame_message_warning'
	) as HTMLElement | null;
	const stickyMessageName = document.getElementById(
		'dame_sticky_message_name'
	) as HTMLElement | null;
	const stickyPriorityName = document.getElementById(
		'dame_sticky_priority_name'
	) as HTMLElement | null;
	const submitBtn = document.getElementById(
		'dame_submit_mailing_btn'
	) as HTMLButtonElement | null;

	function updateStickyInfo(): void {
		if (messageSelect && stickyMessageName) {
			const selectedOption =
				messageSelect.options[messageSelect.selectedIndex];
			if (selectedOption && selectedOption.value) {
				stickyMessageName.textContent = selectedOption.text;
			} else {
				stickyMessageName.textContent = 'Aucun message sélectionné';
			}
		}

		if (prioritySelect && stickyPriorityName) {
			const selectedPrio =
				prioritySelect.options[prioritySelect.selectedIndex];
			if (selectedPrio && selectedPrio.value) {
				stickyPriorityName.textContent = `Priorité : ${selectedPrio.text}`;
			} else {
				stickyPriorityName.textContent = 'Priorité non définie';
			}
		}
	}

	if (messageSelect) {
		messageSelect.addEventListener('change', (): void => {
			updateStickyInfo();

			const selectedOption =
				messageSelect.options[messageSelect.selectedIndex];
			const status = selectedOption
				? selectedOption.getAttribute('data-status')
				: null;

			if (warningDiv) {
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
			}
		});
	}

	if (prioritySelect) {
		prioritySelect.addEventListener('change', (): void => {
			updateStickyInfo();
		});
	}

	// Initialize sticky bar on load
	updateStickyInfo();
});
