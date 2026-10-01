document.addEventListener('DOMContentLoaded', (): void => {
	const form = document.getElementById('dame-contact-form') as HTMLFormElement | null;
	const feedback = document.getElementById('dame-contact-feedback') as HTMLElement | null;

	if (form && feedback) {
		form.addEventListener('submit', (e: Event): void => {
			e.preventDefault();

			// Validation HTML5 basique
			if (!form.checkValidity()) {
				form.reportValidity();
				return;
			}

			// Récupération des données du formulaire (incluant le champ caché 'action')
			const formData = new FormData(form);

			// Sécurité : si le JS n'avait pas le champ action, on le force au cas où
			if (!formData.has('action')) {
				formData.append('action', 'dame_submit_contact_form');
			}

			const submitBtn = form.querySelector<HTMLButtonElement>('button[type="submit"]');
			const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.innerHTML = 'Envoi en cours...';
			}

			// Requête AJAX
			fetch(dame_contact_ajax.ajax_url, {
				method: 'POST',
				body: formData,
			})
				.then((response: Response) => {
					if (!response.ok) {
						throw new Error(
							'Erreur réseau (' + response.status + ')'
						);
					}
					return response.json() as Promise<{ success: boolean; data: { message?: string } | string }>;
				})
				.then((data) => {
					feedback.style.display = 'block';
					const message = typeof data.data === 'object' && data.data !== null && data.data.message
						? data.data.message
						: String(data.data);

					if (data.success) {
						feedback.innerHTML =
							'<div class="notice notice-success" style="color: green; padding: 10px; border: 1px solid green; margin-top: 15px;">' +
							message +
							'</div>';
						form.reset();
					} else {
						feedback.innerHTML =
							'<div class="notice notice-error" style="color: red; padding: 10px; border: 1px solid red; margin-top: 15px;">' +
							message +
							'</div>';
					}
				})
				.catch((error: unknown) => {
					feedback.style.display = 'block';
					feedback.innerHTML =
						'<div class="notice notice-error" style="color: red; padding: 10px; border: 1px solid red; margin-top: 15px;">Erreur de connexion au serveur. Veuillez réessayer.</div>';
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
