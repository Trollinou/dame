document.addEventListener( 'DOMContentLoaded', function () {
	const radios = document.getElementsByName( 'dame_selection_method' );
	const filtersDiv = document.querySelector( '.dame-group-filters' );
	const manualDiv = document.querySelector( '.dame-manual-filters' );

	function toggleSections() {
		let method = 'group';
		for ( const radio of radios ) {
			if ( radio.checked ) {
				method = radio.value;
				break;
			}
		}
		if ( method === 'group' ) {
			if ( filtersDiv ) {
				filtersDiv.style.display = 'block';
			}
			if ( manualDiv ) {
				manualDiv.style.display = 'none';
			}
		} else {
			if ( filtersDiv ) {
				filtersDiv.style.display = 'none';
			}
			if ( manualDiv ) {
				manualDiv.style.display = 'block';
			}
		}
	}

	for ( const radio of radios ) {
		radio.addEventListener( 'change', toggleSections );
	}
	toggleSections();

	// Message status check
	const messageSelect = document.getElementById( 'dame_message_to_send' );
	const submitButton = document.getElementById( 'submit' );
	const warningDiv = document.getElementById( 'dame_message_warning' );

	function checkMessageStatus() {
		if ( ! messageSelect.value ) {
			submitButton.disabled = false;
			warningDiv.style.display = 'none';
			return;
		}
		const selectedOption =
			messageSelect.options[ messageSelect.selectedIndex ];
		const status = selectedOption.getAttribute( 'data-status' );

		if ( status === 'sent' || status === 'sending' ) {
			submitButton.disabled = true;
			warningDiv.style.display = 'block';
		} else {
			submitButton.disabled = false;
			warningDiv.style.display = 'none';
		}
	}

	if ( messageSelect ) {
		messageSelect.addEventListener( 'change', checkMessageStatus );
		checkMessageStatus(); // Check on load
	}
} );
