document.addEventListener('DOMContentLoaded', (): void => {
	// Competition level toggle
	function toggleCompetitionLevel(): void {
		const checkedInput = document.querySelector<HTMLInputElement>(
			'input[name="dame_competition_type"]:checked'
		);
		const competitionType = checkedInput ? checkedInput.value : '';
		const wrapper = document.getElementById(
			'dame_competition_level_wrapper'
		);
		if (wrapper) {
			wrapper.style.display =
				!competitionType || competitionType === 'non' ? 'none' : '';
		}
	}

	toggleCompetitionLevel();
	document
		.querySelectorAll<HTMLInputElement>(
			'input[name="dame_competition_type"]'
		)
		.forEach((radio: HTMLInputElement): void => {
			radio.addEventListener('change', toggleCompetitionLevel);
		});

	// Time fields toggle
	function toggleTimeFields(): void {
		const allDay = document.getElementById(
			'dame_all_day'
		) as HTMLInputElement | null;
		const isChecked = allDay ? allDay.checked : false;
		document
			.querySelectorAll<HTMLElement>('.dame-time-fields')
			.forEach((el: HTMLElement): void => {
				el.style.display = isChecked ? 'none' : '';
			});
	}

	toggleTimeFields();
	const allDayCheckbox = document.getElementById(
		'dame_all_day'
	) as HTMLInputElement | null;
	if (allDayCheckbox) {
		allDayCheckbox.addEventListener('change', toggleTimeFields);
	}

	// UX: Copy start date to end date on blur if end date is empty
	const startDateInput = document.getElementById(
		'dame_start_date'
	) as HTMLInputElement | null;
	const endDateInput = document.getElementById(
		'dame_end_date'
	) as HTMLInputElement | null;
	if (startDateInput && endDateInput) {
		startDateInput.addEventListener('blur', (): void => {
			const startDate = startDateInput.value;
			const endDate = endDateInput.value;
			if (startDate && !endDate) {
				endDateInput.value = startDate;
			}
		});
	}

	// UX: Validate Category Selection and Competition Type on submit
	const postForm = document.getElementById('post') as HTMLFormElement | null;
	if (postForm) {
		postForm.addEventListener('submit', (e: Event): boolean | void => {
			const categoryChecklist = document.getElementById(
				'dame_agenda_categorychecklist'
			);
			if (categoryChecklist) {
				const checkedCount =
					categoryChecklist.querySelectorAll('input:checked').length;
				if (checkedCount === 0) {
					alert(
						dame_agenda_manager_data.alert_category ||
							'Veuillez sélectionner au moins une catégorie.'
					);
					e.preventDefault();
					const publishBtn = document.getElementById('publish');
					if (publishBtn) {
						publishBtn.classList.remove('disabled');
					}
					document
						.querySelectorAll('.spinner')
						.forEach((spinner: Element): void => {
							spinner.classList.remove('is-active');
						});
					return false;
				}
			}

			const competitionInputs = document.querySelectorAll(
				'input[name="dame_competition_type"]'
			);
			if (competitionInputs.length > 0) {
				const checkedComp = document.querySelector(
					'input[name="dame_competition_type"]:checked'
				);
				if (!checkedComp) {
					alert(
						dame_agenda_manager_data.alert_competition_type ||
							'Veuillez sélectionner un type de compétition.'
					);
					e.preventDefault();
					const publishBtn = document.getElementById('publish');
					if (publishBtn) {
						publishBtn.classList.remove('disabled');
					}
					document
						.querySelectorAll('.spinner')
						.forEach((spinner: Element): void => {
							spinner.classList.remove('is-active');
						});
					return false;
				}
			}
		});
	}

	function normalizeText(str: string | null | undefined): string {
		return (str || '')
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.toLowerCase()
			.trim();
	}

	// Participant filter
	const participantFilter = document.getElementById(
		'dame_participant_filter'
	) as HTMLInputElement | null;
	if (participantFilter) {
		const onFilterChange = (): void => {
			const value = normalizeText(participantFilter.value);
			document
				.querySelectorAll<HTMLElement>('#dame_participants_list li')
				.forEach((li: HTMLElement): void => {
					const text = normalizeText(li.textContent);
					li.style.display = text.includes(value) ? '' : 'none';
				});
		};
		participantFilter.addEventListener('keyup', onFilterChange);
		participantFilter.addEventListener('input', onFilterChange);
	}

	// --- Recurrence Form Dynamics ---
	function toggleRecurrenceOptions(): void {
		const recurrenceCheckbox = document.getElementById(
			'dame_enable_recurrence'
		) as HTMLInputElement | null;
		const recurrenceOptions = document.getElementById(
			'dame_recurrence_options'
		);
		if (recurrenceCheckbox && recurrenceOptions) {
			if (recurrenceCheckbox.checked) {
				recurrenceOptions.style.display = '';
				updateSeasonLimitNotice();
			} else {
				recurrenceOptions.style.display = 'none';
			}
		}
	}

	function toggleRecurrenceFrequency(): void {
		const freqSelect = document.getElementById(
			'dame_recurrence_frequency'
		) as HTMLSelectElement | null;
		const freq = freqSelect ? freqSelect.value : '';
		const weeklyRow = document.getElementById('dame_recurrence_weekly_row');
		const monthlyRow = document.getElementById(
			'dame_recurrence_monthly_row'
		);

		if (weeklyRow && monthlyRow) {
			if (freq === 'monthly') {
				weeklyRow.style.display = 'none';
				monthlyRow.style.display = '';
			} else {
				monthlyRow.style.display = 'none';
				weeklyRow.style.display = '';
			}
		}
	}

	function updateSeasonLimitNotice(): void {
		const startDateVal = startDateInput ? startDateInput.value : '';
		if (!startDateVal) {
			return;
		}

		const parts = startDateVal.split('-');
		if (parts.length === 3) {
			const year = parseInt(parts[0], 10);
			const month = parseInt(parts[1], 10);
			const endYear = month >= 9 ? year + 1 : year;
			const maxSeasonDate = `${endYear}-08-31`;
			const displayLimit = `31/08/${endYear}`;

			const endDateEl = document.getElementById(
				'dame_recurrence_end_date'
			);
			if (endDateEl) {
				endDateEl.setAttribute('max', maxSeasonDate);
			}

			const seasonLimitText = document.getElementById(
				'dame_season_limit_text'
			);
			if (seasonLimitText) {
				seasonLimitText.textContent = `Les répétitions ne pourront pas dépasser le ${displayLimit} (fin de saison).`;
			}
		}
	}

	const enableRecurrence = document.getElementById('dame_enable_recurrence');
	if (enableRecurrence) {
		enableRecurrence.addEventListener('change', toggleRecurrenceOptions);
	}

	const recurrenceFreq = document.getElementById('dame_recurrence_frequency');
	if (recurrenceFreq) {
		recurrenceFreq.addEventListener('change', toggleRecurrenceFrequency);
	}

	if (startDateInput) {
		startDateInput.addEventListener('change', updateSeasonLimitNotice);
	}

	// --- Series Deletion Confirmations ---
	document.addEventListener('click', (e: MouseEvent): boolean | void => {
		const target = e.target as HTMLElement | null;
		if (!target) {
			return;
		}
		const deleteSeriesBtn = target.closest<HTMLElement>(
			'.dame-js-delete-series-from'
		);
		if (deleteSeriesBtn) {
			const count = deleteSeriesBtn.dataset.count || '1';
			const isParent =
				deleteSeriesBtn.dataset.isParent === '1' ||
				(deleteSeriesBtn.dataset.isParent as unknown) === 1;

			let msg = '';
			if (isParent) {
				msg = `Êtes-vous sûr de vouloir supprimer tous les événements de cette série (${count} séances) ?`;
			} else {
				msg = `Êtes-vous sûr de vouloir supprimer cet événement et les suivants (${count} séances au total à partir de cette date) ? Les séances passées seront conservées.`;
			}

			if (!confirm(msg)) {
				e.preventDefault();
				return false;
			}
			return;
		}

		const deleteEntireBtn = target.closest<HTMLElement>(
			'.dame-js-delete-entire-series'
		);
		if (deleteEntireBtn) {
			const total = deleteEntireBtn.dataset.total || '';
			const msg = `Attention : Êtes-vous sûr de vouloir supprimer TOUTE la série (${total} séances, y compris les séances passées) ?`;

			if (!confirm(msg)) {
				e.preventDefault();
				return false;
			}
		}
	});
});
