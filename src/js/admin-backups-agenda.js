document.addEventListener( 'DOMContentLoaded', function () {
	const restoreForm = document.getElementById( 'dame-agenda-restore-form' );
	if ( restoreForm ) {
		restoreForm.addEventListener( 'submit', function ( e ) {
			if ( ! confirm( dame_backup_agenda_data.confirm_restore ) ) {
				e.preventDefault();
			}
		} );
	}
} );
