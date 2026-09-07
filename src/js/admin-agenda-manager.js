jQuery(document).ready(function ($) {
	// Competition level toggle
	function toggleCompetitionLevel() {
		const competitionType = $(
			'input[name="dame_competition_type"]:checked'
		).val();
		if (competitionType === 'non') {
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

	// UX: Validate Category Selection on submit
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
});
