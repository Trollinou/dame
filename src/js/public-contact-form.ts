document.addEventListener('DOMContentLoaded', (): void => {
	const form = (document.getElementById('dame-public-contact-form') ||
		document.getElementById('dame-contact-form')) as HTMLFormElement | null;
	const feedback = document.getElementById(
		'dame-contact-feedback'
	) as HTMLElement | null;

	if (form && feedback) {
		form.addEventListener('submit', (e: Event): void => {
			if (e.defaultPrevented) {
				return;
			}
			e.preventDefault();

			// Validation HTML5 basique
			if (!form.checkValidity()) {
				form.reportValidity();
				return;
			}

			// Récupération des données du formulaire (incluant le champ action)
			const formData = new FormData(form);
			if (!formData.has('action')) {
				formData.append('action', 'dame_submit_contact_form');
			}

			const submitBtn = form.querySelector<HTMLButtonElement>(
				'button[type="submit"]'
			);
			const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.innerHTML = 'Envoi en cours...';
			}

			const ajaxUrl =
				typeof dame_contact_ajax !== 'undefined' &&
				dame_contact_ajax.ajax_url
					? dame_contact_ajax.ajax_url
					: '/wp-admin/admin-ajax.php';

			// Requête AJAX
			fetch(ajaxUrl, {
				method: 'POST',
				body: formData,
			})
				.then((response: Response) => {
					if (!response.ok) {
						throw new Error(
							'Erreur réseau (' + response.status + ')'
						);
					}
					return response.json() as Promise<{
						success: boolean;
						data: { message?: string } | string;
					}>;
				})
				.then((data) => {
					feedback.style.display = 'block';
					feedback.removeAttribute('hidden');
					const message =
						typeof data.data === 'object' &&
						data.data !== null &&
						data.data.message
							? data.data.message
							: String(data.data);

					if (data.success) {
						feedback.className =
							'dame-feedback dame-feedback--success';
						feedback.textContent = message;
						form.reset();
					} else {
						feedback.className =
							'dame-feedback dame-feedback--error';
						feedback.textContent = message;
					}
				})
				.catch((error: unknown) => {
					feedback.style.display = 'block';
					feedback.removeAttribute('hidden');
					feedback.className = 'dame-feedback dame-feedback--error';
					feedback.textContent =
						'Erreur de connexion au serveur. Veuillez réessayer.';
					console.error('Erreur AJAX Contact:', error);
				})
				.finally(() => {
					if (submitBtn) {
						submitBtn.disabled = false;
						submitBtn.innerHTML = originalBtnText;
					}
				});
		});
	}
});
