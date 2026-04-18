document.addEventListener( 'DOMContentLoaded', function () {
	const deleteButton = document.querySelector( '.dame-delete-button' );
	if ( deleteButton ) {
		deleteButton.addEventListener( 'click', function ( e ) {
			if (
				! confirm( dame_pre_inscription_actions_data.confirm_delete )
			) {
				e.preventDefault();
			}
		} );
	}
} );
