jQuery(document).ready(function ($) {
	// Competition level toggle
	function toggleCompetitionLevel() {
		const competitionType = $(
			'input[name="dame_competition_type"]:checked'
		).val();
		if (!competitionType || competitionType === 'non') {
			$('#dame_competition_level_wrapper').hide();
		} else {
			$('#dame_competition_level_wrapper').show();
		}
	}
	// Run on page load
	toggleCompetitionLevel();
	// Run on change
	$('input[name="dame_competition_type"]').on('change', function () {
		toggleCompetitionLevel();
	});

	// Time fields toggle
	function toggleTimeFields() {
		if ($('#dame_all_day').is(':checked')) {
			$('.dame-time-fields').hide();
		} else {
			$('.dame-time-fields').show();
		}
	}
	toggleTimeFields(); // Initial check
	$('#dame_all_day').on('change', toggleTimeFields);

	// UX: Copy start date to end date on blur if end date is empty
	$('#dame_start_date').on('blur', function () {
		const startDate = $(this).val();
		const endDate = $('#dame_end_date').val();
		if (startDate && !endDate) {
			$('#dame_end_date').val(startDate);
		}
	});

	// UX: Validate Category Selection and Competition Type on submit
	$('#post').on('submit', function (e) {
		// Only if we are on the agenda edit screen
		if ($('#dame_agenda_categorychecklist').length > 0) {
			if (
				$('#dame_agenda_categorychecklist input:checked').length === 0
			) {
				alert(dame_agenda_manager_data.alert_category);
				e.preventDefault();
				// Remove spinner/disabled state to allow retry
				$('#publish').removeClass('disabled');
				$('.spinner').removeClass('is-active');
				return false;
			}
		}

		if (
			$('input[name="dame_competition_type"]').length > 0 &&
			$('input[name="dame_competition_type"]:checked').length === 0
		) {
			alert(
				dame_agenda_manager_data.alert_competition_type ||
					'Veuillez sélectionner un type de compétition.'
			);
			e.preventDefault();
			$('#publish').removeClass('disabled');
			$('.spinner').removeClass('is-active');
			return false;
		}
	});

	function normalizeText(str) {
		return (str || '')
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.toLowerCase()
			.trim();
	}

	// Participant filter
	$('#dame_participant_filter').on('keyup input', function () {
		const value = normalizeText($(this).val());
		$('#dame_participants_list li').each(function () {
			$(this).toggle(normalizeText($(this).text()).indexOf(value) > -1);
		});
	});

	// --- Recurrence Form Dynamics ---
	function toggleRecurrenceOptions() {
		if ($('#dame_enable_recurrence').is(':checked')) {
			$('#dame_recurrence_options').slideDown(150);
			updateSeasonLimitNotice();
		} else {
			$('#dame_recurrence_options').slideUp(150);
		}
	}

	function toggleRecurrenceFrequency() {
		const freq = $('#dame_recurrence_frequency').val();
		if (freq === 'monthly') {
			$('#dame_recurrence_weekly_row').hide();
			$('#dame_recurrence_monthly_row').show();
		} else {
			$('#dame_recurrence_monthly_row').hide();
			$('#dame_recurrence_weekly_row').show();
		}
	}

	function updateSeasonLimitNotice() {
		const startDateVal = $('#dame_start_date').val();
		if (!startDateVal) {
			return;
		}

		const parts = startDateVal.split('-');
		if (parts.length === 3) {
			const year = parseInt(parts[0], 10);
			const month = parseInt(parts[1], 10);
			const endYear = month >= 9 ? year + 1 : year;
			const maxSeasonDate = endYear + '-08-31';
			const displayLimit = '31/08/' + endYear;

			$('#dame_recurrence_end_date').attr('max', maxSeasonDate);
			$('#dame_season_limit_text').text(
				'Les répétitions ne pourront pas dépasser le ' +
					displayLimit +
					' (fin de saison).'
			);
		}
	}

	$('#dame_enable_recurrence').on('change', toggleRecurrenceOptions);
	$('#dame_recurrence_frequency').on('change', toggleRecurrenceFrequency);
	$('#dame_start_date').on('change', updateSeasonLimitNotice);

	// --- Series Deletion Confirmations ---
	$('.dame-js-delete-series-from').on('click', function (e) {
		const count = $(this).data('count') || 1;
		const isParent =
			$(this).data('is-parent') === 1 ||
			$(this).data('is-parent') === '1';

		let msg = '';
		if (isParent) {
			msg =
				'Êtes-vous sûr de vouloir supprimer tous les événements de cette série (' +
				count +
				' séances) ?';
		} else {
			msg =
				'Êtes-vous sûr de vouloir supprimer cet événement et les suivants (' +
				count +
				' séances au total à partir de cette date) ? Les séances passées seront conservées.';
		}

		if (!confirm(msg)) {
			e.preventDefault();
			return false;
		}
	});

	$('.dame-js-delete-entire-series').on('click', function (e) {
		const total = $(this).data('total') || '';
		const msg =
			'Attention : Êtes-vous sûr de vouloir supprimer TOUTE la série (' +
			total +
			' séances, y compris les séances passées) ?';

		if (!confirm(msg)) {
			e.preventDefault();
			return false;
		}
	});
});
