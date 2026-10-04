/**
 * Public Newsletter Modal & Form Interaction
 */

interface ModalWithTrigger extends HTMLElement {
	_lastFocusedTrigger?: HTMLElement | null;
}

document.addEventListener('DOMContentLoaded', (): void => {
	// Move modals to body to escape any parent CSS stacking context or transform
	const modals = document.querySelectorAll<HTMLElement>('.dame-nl-modal');
	modals.forEach((modal: HTMLElement): void => {
		if (modal.parentElement !== document.body) {
			document.body.appendChild(modal);
		}
	});

	/**
	 * Open a specific modal dialog
	 * @param modal
	 * @param triggerBtn
	 */
	function openModal(
		modal: ModalWithTrigger | null,
		triggerBtn: HTMLElement | null = null
	): void {
		if (!modal) {
			return;
		}
		if (modal.parentElement !== document.body) {
			document.body.appendChild(modal);
		}
		modal.classList.add('dame-nl-modal--open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('dame-modal-active');

		if (triggerBtn) {
			modal._lastFocusedTrigger = triggerBtn;
		}

		const firstInput = modal.querySelector<HTMLInputElement>(
			'input:not([type="hidden"]):not([tabindex="-1"])'
		);
		if (firstInput) {
			setTimeout((): void => {
				firstInput.focus();
			}, 220);
		}
	}

	/**
	 * Close a specific modal dialog
	 * @param modal
	 */
	function closeModal(modal: ModalWithTrigger | null): void {
		if (!modal) {
			return;
		}
		modal.classList.remove('dame-nl-modal--open');
		modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('dame-modal-active');

		if (modal._lastFocusedTrigger) {
			modal._lastFocusedTrigger.focus();
			modal._lastFocusedTrigger = null;
		}
	}

	// Attach delegated click events for triggers, close buttons, and backdrops
	document.addEventListener('click', (e: MouseEvent): void => {
		const target = e.target as HTMLElement | null;

		// 1. Trigger button clicked
		const triggerBtn = target?.closest<HTMLElement>('.dame-nl-btn-trigger');
		if (triggerBtn) {
			e.preventDefault();
			const targetId = triggerBtn.getAttribute('data-dame-modal-target');
			if (targetId) {
				const modal = document.getElementById(
					targetId
				) as ModalWithTrigger | null;
				if (modal) {
					openModal(modal, triggerBtn);
				}
			}
			return;
		}

		// 2. Close button or backdrop clicked
		const closeTarget = target?.closest<HTMLElement>(
			'[data-dame-modal-close]'
		);
		if (closeTarget) {
			e.preventDefault();
			const modal =
				closeTarget.closest<ModalWithTrigger>('.dame-nl-modal');
			if (modal) {
				closeModal(modal);
			}
		}
	});

	// Escape key to close open modals
	document.addEventListener('keydown', (e: KeyboardEvent): void => {
		if (e.key === 'Escape' || e.key === 'Esc') {
			const openModals = document.querySelectorAll<ModalWithTrigger>(
				'.dame-nl-modal--open'
			);
			openModals.forEach((modal: ModalWithTrigger): void => {
				closeModal(modal);
			});
		}
	});

	// 2. AJAX Form Submissions
	const forms = document.querySelectorAll<HTMLFormElement>('.dame-nl-form');

	forms.forEach((form: HTMLFormElement): void => {
		// Do not attach legacy listener if Interactivity API manages this form.
		if (
			form.hasAttribute('data-wp-on--submit') ||
			form.closest('[data-wp-interactive]')
		) {
			return;
		}

		form.addEventListener('submit', (e: Event): void => {
			e.preventDefault();

			const feedbackBox = form.querySelector<HTMLElement>(
				'.dame-nl-form__feedback'
			);
			const submitBtn = form.querySelector<HTMLButtonElement>(
				'.dame-nl-form__submit'
			);

			if (!feedbackBox || !submitBtn) {
				return;
			}

			// Reset feedback
			feedbackBox.style.display = 'none';
			feedbackBox.className = 'dame-nl-form__feedback';
			feedbackBox.textContent = '';

			const lastNameInput = form.querySelector<HTMLInputElement>(
				'input[name="dame_newsletter_last_name"]'
			);
			const firstNameInput = form.querySelector<HTMLInputElement>(
				'input[name="dame_newsletter_first_name"]'
			);
			const emailInput = form.querySelector<HTMLInputElement>(
				'input[name="dame_newsletter_email"]'
			);

			const lastName = lastNameInput ? lastNameInput.value.trim() : '';
			const firstName = firstNameInput ? firstNameInput.value.trim() : '';
			const email = emailInput ? emailInput.value.trim() : '';

			if (!lastName || !firstName || !email) {
				feedbackBox.textContent =
					'Veuillez renseigner tous les champs obligatoires.';
				feedbackBox.classList.add('dame-nl-form__feedback--error');
				feedbackBox.style.display = 'block';
				return;
			}

			const submitText = form.querySelector<HTMLElement>(
				'.dame-nl-form__submit-text'
			);
			const spinner = form.querySelector<HTMLElement>(
				'.dame-nl-form__spinner'
			);
			const originalText = submitText ? submitText.textContent || '' : '';

			// UI loading state
			submitBtn.disabled = true;
			if (spinner) {
				spinner.style.display = 'inline-block';
			}
			if (submitText && typeof dameNewsletterData !== 'undefined') {
				submitText.textContent =
					dameNewsletterData?.i18n?.submitting ||
					'Inscription en cours...';
			}

			const formData = new FormData(form);
			const ajaxUrl =
				typeof dameNewsletterData !== 'undefined' &&
				dameNewsletterData?.ajaxUrl
					? dameNewsletterData.ajaxUrl
					: '/wp-admin/admin-ajax.php';

			fetch(ajaxUrl, {
				method: 'POST',
				body: formData,
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
				},
			})
				.then(
					(response: Response) =>
						response.json() as Promise<{
							success: boolean;
							data?: { message?: string };
						}>
				)
				.then((result) => {
					feedbackBox.style.display = 'block';
					if (result.success) {
						feedbackBox.textContent =
							result.data?.message || 'Inscription réussie !';
						feedbackBox.classList.add(
							'dame-nl-form__feedback--success'
						);

						// Disable inputs
						form.querySelectorAll<HTMLInputElement>(
							'input:not([type="hidden"])'
						).forEach((input: HTMLInputElement): void => {
							input.value = '';
							input.disabled = true;
						});
						submitBtn.style.display = 'none';
					} else {
						feedbackBox.textContent =
							result.data?.message ||
							"Une erreur est survenue lors de l'inscription.";
						feedbackBox.classList.add(
							'dame-nl-form__feedback--error'
						);
						submitBtn.disabled = false;
					}
				})
				.catch((): void => {
					feedbackBox.textContent =
						'Erreur de connexion. Veuillez réessayer ultérieurement.';
					feedbackBox.classList.add('dame-nl-form__feedback--error');
					feedbackBox.style.display = 'block';
					submitBtn.disabled = false;
				})
				.finally((): void => {
					if (spinner) {
						spinner.style.display = 'none';
					}
					if (submitText && !submitBtn.disabled) {
						submitText.textContent = originalText;
					}
				});
		});
	});

	// 5. Confirmation Notice Overlay Close
	document.addEventListener('click', (e: MouseEvent): void => {
		const target = e.target as HTMLElement | null;
		if (target?.closest('.dame-nl-notice-close')) {
			const overlay = document.getElementById(
				'dame-newsletter-notice-overlay'
			);
			overlay?.remove();
		}
	});
});
