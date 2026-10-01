(() => {
	'use strict';

	document.addEventListener('DOMContentLoaded', () => {
		const button = document.getElementById('dame-send-test-birthday-email');
		const message = document.getElementById(
			'dame-send-test-birthday-email-message'
		);

		if (!button || !message) {
			return;
		}

		button.addEventListener('click', async (e) => {
			e.preventDefault();

			button.disabled = true;
			message.textContent = dame_settings_anniversaires.sending_message;
			message.style.color = '';

			const formData = new FormData();
			formData.append('action', 'dame_send_test_birthday_email');
			formData.append('_ajax_nonce', dame_settings_anniversaires.nonce);

			try {
				const response = await fetch(ajaxurl, {
					method: 'POST',
					body: formData,
				});
				const result = await response.json();

				if (result.success) {
					message.textContent = result.data.message;
					message.style.color = 'green';
				} else {
					message.textContent =
						result.data?.message || "Erreur lors de l'envoi";
					message.style.color = 'red';
				}
			} catch (error) {
				message.textContent = error.message;
				message.style.color = 'red';
			} finally {
				button.disabled = false;
			}
		});
	});
})();
