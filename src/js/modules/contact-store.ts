/**
 * Interactivity API Module - DAME Contact Store.
 *
 * Provides reactive form submission and validation state for the contact form shortcode.
 */

import { store, getContext } from '@wordpress/interactivity';

export interface ContactContext {
	isSubmitting: boolean;
	status: 'idle' | 'success' | 'error';
	message: string;
	ajaxUrl: string;
	nonce: string;
}

export const contactStore = store('dame/contact', {
	state: {
		get isBusy(): boolean {
			const ctx = getContext<ContactContext>();
			return ctx.isSubmitting;
		},
		get hasMessage(): boolean {
			const ctx = getContext<ContactContext>();
			return Boolean(ctx.message && ctx.message.trim().length > 0);
		},
		get isSuccess(): boolean {
			const ctx = getContext<ContactContext>();
			return ctx.status === 'success';
		},
		get isError(): boolean {
			const ctx = getContext<ContactContext>();
			return ctx.status === 'error';
		},
	},
	actions: {
		async submitForm(event: Event): Promise<void> {
			event.preventDefault();
			const form = event.target as HTMLFormElement;
			if (!form) {
				return;
			}

			// HTML5 native validation
			if (!form.checkValidity()) {
				form.reportValidity();
				return;
			}

			const ctx = getContext<ContactContext>();
			ctx.isSubmitting = true;
			ctx.status = 'idle';
			ctx.message = '';

			const formData = new FormData(form);
			if (!formData.has('action')) {
				formData.append('action', 'dame_submit_contact_form');
			}

			try {
				const response = await fetch(ctx.ajaxUrl, {
					method: 'POST',
					body: formData,
				});

				const result = await response.json();
				if (result.success) {
					ctx.status = 'success';
					ctx.message =
						result.data?.message ||
						'Votre message a bien été envoyé.';
					form.reset();
				} else {
					ctx.status = 'error';
					ctx.message =
						result.data?.message ||
						"Une erreur est survenue lors de l'envoi du message.";
				}
			} catch {
				ctx.status = 'error';
				ctx.message =
					'Erreur de connexion au serveur. Veuillez réessayer.';
			} finally {
				ctx.isSubmitting = false;
			}
		},
	},
});
