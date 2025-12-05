(function ($) {
	'use strict';

	$(document).ready(function () {
		$('#dame-contact-form').on('submit', function (e) {
			e.preventDefault();

			const form = $(this);
			const submitButton = form.find('button[type="submit"]'); // eslint-disable-line @wordpress/no-unused-vars-before-return
			const messageContainer = $('#dame-contact-form-messages');
			// Clear previous messages
			messageContainer.text('').removeClass('success error');

			// Client-side validation
			const name = $('#dame_contact_name').val();
			const email = $('#dame_contact_email').val();
			const subject = $('#dame_contact_subject').val();
			const message = $('#dame_contact_message').val();

			if (!name || !email || !subject || !message) {
				messageContainer
					.text('Veuillez remplir tous les champs obligatoires.')
					.css('color', 'red');
				return;
			}

			// Disable button
			submitButton.prop('disabled', true);
			messageContainer.text('Envoi en cours...').css('color', 'inherit');

			const formData = form.serialize();

			$.ajax({
				type: 'POST',
				url: dame_contact_ajax.ajax_url,
				data: formData + '&action=dame_contact_submit',
				success(response) {
					if (response.success) {
						messageContainer
							.text(response.data.message)
							.css('color', 'green');
						form.trigger('reset'); // Clear form fields
					} else {
						messageContainer
							.text(response.data.message)
							.css('color', 'red');
					}
				},
				error() {
					messageContainer
						.text('Une erreur est survenue. Veuillez réessayer.')
						.css('color', 'red');
				},
				complete() {
					// Re-enable button
					submitButton.prop('disabled', false);
				},
			});
		});
	});
})(jQuery);
