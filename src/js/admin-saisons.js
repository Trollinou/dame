document.addEventListener( 'DOMContentLoaded', function () {
	const resetButton = document.getElementById( 'dame_annual_reset' );
	if ( resetButton ) {
		resetButton.addEventListener( 'click', function ( e ) {
			if ( ! confirm( dame_saisons_data.confirm_reset ) ) {
				e.preventDefault();
			} else {
				setTimeout( function () {
					resetButton.disabled = true;
				}, 0 );
			}
		} );
	}
} );
