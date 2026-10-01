(() => {
	'use strict';

	document.addEventListener('DOMContentLoaded', (): void => {
		const button = document.getElementById('dame-send-test-birthday-email') as HTMLButtonElement | null;
		const message = document.getElementById(
			'dame-send-test-birthday-email-message'
		) as HTMLElement | null;

		if (!button || !message) {
			return;
		}

		button.addEventListener('click', async (e: MouseEvent): Promise<void> => {
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
				const result = (await response.json()) as { success: boolean; data?: { message?: string } };

				if (result.success) {
					message.textContent = result.data?.message || 'Email envoyé avec succès';
					message.style.color = 'green';
				} else {
					message.textContent =
						result.data?.message || "Erreur lors de l'envoi";
					message.style.color = 'red';
				}
			} catch (error: unknown) {
				message.textContent = error instanceof Error ? error.message : "Erreur inconnue";
				message.style.color = 'red';
			} finally {
				button.disabled = false;
			}
		});
	});
})();
