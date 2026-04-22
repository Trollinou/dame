document.addEventListener( 'DOMContentLoaded', function () {
	const revertButton = document.querySelector(
		'button[name="dame_revert_to_pre_inscription"]'
	);
	if ( revertButton ) {
		revertButton.addEventListener( 'click', function ( e ) {
			if ( ! confirm( dame_adherent_actions_data.confirm_revert ) ) {
				e.preventDefault();
			}
		} );
	}
} );
