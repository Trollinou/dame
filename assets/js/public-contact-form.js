document.addEventListener( 'DOMContentLoaded', function () {
	const form = document.getElementById( 'dame-contact-form' );
	const feedback = document.getElementById( 'dame-contact-feedback' );

	if ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			// Validation HTML5 basique
			if ( ! form.checkValidity() ) {
				form.reportValidity();
				return;
			}

			// Récupération des données du formulaire (incluant le champ caché 'action')
			const formData = new FormData( form );

			// Sécurité : si le JS n'avait pas le champ action, on le force au cas où
			if ( ! formData.has( 'action' ) ) {
				formData.append( 'action', 'dame_submit_contact_form' );
			}

			const submitBtn = form.querySelector( 'button[type="submit"]' );
			const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

			if ( submitBtn ) {
				submitBtn.disabled = true;
				submitBtn.innerHTML = 'Envoi en cours...';
			}

			// Requête AJAX
			fetch( dame_contact_ajax.ajax_url, {
				method: 'POST',
				body: formData,
			} )
				.then( ( response ) => {
					if ( ! response.ok ) {
						throw new Error(
							'Erreur réseau (' + response.status + ')'
						);
					}
					return response.json();
				} )
				.then( ( data ) => {
					feedback.style.display = 'block';
					if ( data.success ) {
						// Les données viennent de data.data['message'] car data.data est l'objet passé à wp_send_json_success()
						const message = data.data.message
							? data.data.message
							: data.data;
						feedback.innerHTML =
							'<div class="notice notice-success" style="color: green; padding: 10px; border: 1px solid green; margin-top: 15px;">' +
							message +
							'</div>';
						form.reset();
					} else {
						const message = data.data.message
							? data.data.message
							: data.data;
						feedback.innerHTML =
							'<div class="notice notice-error" style="color: red; padding: 10px; border: 1px solid red; margin-top: 15px;">' +
							message +
							'</div>';
					}
				} )
				.catch( ( error ) => {
					feedback.style.display = 'block';
					feedback.innerHTML =
						'<div class="notice notice-error" style="color: red; padding: 10px; border: 1px solid red; margin-top: 15px;">Erreur de connexion au serveur. Veuillez réessayer.</div>';
					console.error( 'Erreur AJAX Contact:', error );
				} )
				.finally( () => {
					if ( submitBtn ) {
						submitBtn.disabled = false;
						submitBtn.innerHTML = originalBtnText;
					}
				} );
		} );
	}
} );
