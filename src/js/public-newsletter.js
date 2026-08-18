/* global dameNewsletterData */
/**
 * Public Newsletter Modal & Form Interaction
 */

document.addEventListener('DOMContentLoaded', function () {
	// 1. Modal Trigger & Management
	const triggers = document.querySelectorAll('.dame-nl-btn-trigger');

	/**
	 * Open a specific modal dialog
	 *
	 * @param {HTMLElement}      modal
	 * @param {HTMLElement|null} triggerBtn
	 */
	function openModal(modal, triggerBtn = null) {
		if (!modal) {
			return;
		}
		modal.classList.add('dame-nl-modal--open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('dame-modal-active');

		if (triggerBtn) {
			modal._lastFocusedTrigger = triggerBtn;
		}

		const firstInput = modal.querySelector(
			'input:not([type="hidden"]):not([tabindex="-1"])'
		);
		if (firstInput) {
			setTimeout(function () {
				firstInput.focus();
			}, 80);
		}
	}

	/**
	 * Close a specific modal dialog
	 *
	 * @param {HTMLElement} modal
	 */
	function closeModal(modal) {
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

	// Attach click events on triggers
	triggers.forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			const targetId = btn.getAttribute('data-dame-modal-target');
			const modal = document.getElementById(targetId);
			if (modal) {
				openModal(modal, btn);
			}
		});
	});

	// Attach close events on close buttons and backdrops
	document.addEventListener('click', function (e) {
		const closeTarget = e.target.closest('[data-dame-modal-close]');
		if (closeTarget) {
			e.preventDefault();
			const modal = closeTarget.closest('.dame-nl-modal');
			if (modal) {
				closeModal(modal);
			}
		}
	});

	// Escape key to close open modals
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' || e.keyCode === 27) {
			const openModals = document.querySelectorAll(
				'.dame-nl-modal--open'
			);
			openModals.forEach(function (modal) {
				closeModal(modal);
			});
		}
	});

	// 2. AJAX Form Submissions
	const forms = document.querySelectorAll('.dame-nl-form');

	forms.forEach(function (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();

			const feedbackBox = form.querySelector('.dame-nl-form__feedback');
			const submitBtn = form.querySelector('.dame-nl-form__submit');

			if (!feedbackBox || !submitBtn) {
				return;
			}

			// Reset feedback
			feedbackBox.style.display = 'none';
			feedbackBox.className = 'dame-nl-form__feedback';
			feedbackBox.textContent = '';

			const lastNameInput = form.querySelector(
				'input[name="dame_newsletter_last_name"]'
			);
			const firstNameInput = form.querySelector(
				'input[name="dame_newsletter_first_name"]'
			);
			const emailInput = form.querySelector(
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

			const submitText = form.querySelector('.dame-nl-form__submit-text');
			const spinner = form.querySelector('.dame-nl-form__spinner');
			const originalText = submitText ? submitText.textContent : '';

			// UI loading state
			submitBtn.disabled = true;
			if (spinner) {
				spinner.style.display = 'inline-block';
			}
			if (submitText && typeof dameNewsletterData !== 'undefined') {
				submitText.textContent =
					dameNewsletterData.i18n?.submitting ||
					'Inscription en cours...';
			}

			const formData = new FormData(form);
			const ajaxUrl =
				typeof dameNewsletterData !== 'undefined'
					? dameNewsletterData.ajaxUrl
					: '/wp-admin/admin-ajax.php';

			fetch(ajaxUrl, {
				method: 'POST',
				body: formData,
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
				},
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (result) {
					feedbackBox.style.display = 'block';
					if (result.success) {
						feedbackBox.textContent =
							result.data?.message || 'Inscription réussie !';
						feedbackBox.classList.add(
							'dame-nl-form__feedback--success'
						);

						// Disable inputs
						form.querySelectorAll(
							'input:not([type="hidden"])'
						).forEach(function (input) {
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
				.catch(function () {
					feedbackBox.textContent =
						'Erreur de connexion. Veuillez réessayer ultérieurement.';
					feedbackBox.classList.add('dame-nl-form__feedback--error');
					feedbackBox.style.display = 'block';
					submitBtn.disabled = false;
				})
				.finally(function () {
					if (spinner) {
						spinner.style.display = 'none';
					}
					if (submitText && !submitBtn.disabled) {
						submitText.textContent = originalText;
					}
				});
		});
	});
});
