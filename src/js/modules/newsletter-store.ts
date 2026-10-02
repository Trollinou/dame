/**
 * Interactivity API Module - DAME Newsletter Store.
 *
 * Provides reactive modal and submission state for the newsletter shortcode.
 */

import { store, getContext } from '@wordpress/interactivity';

export interface NewsletterContext {
	isOpen: boolean;
	isSubmitting: boolean;
	status: 'idle' | 'success' | 'error';
	message: string;
	ajaxUrl: string;
	nonce: string;
}

export const newsletterStore = store('dame/newsletter', {
	state: {
		get isModalVisible(): boolean {
			const ctx = getContext<NewsletterContext>();
			return ctx.isOpen;
		},
	},
	actions: {
		openModal(): void {
			const ctx = getContext<NewsletterContext>();
			ctx.isOpen = true;
			ctx.status = 'idle';
			ctx.message = '';
		},

		closeModal(): void {
			const ctx = getContext<NewsletterContext>();
			ctx.isOpen = false;
		},

		async submitForm(event: Event): Promise<void> {
			event.preventDefault();
			const form = event.target as HTMLFormElement;
			if (!form) {
				return;
			}

			const ctx = getContext<NewsletterContext>();
			ctx.isSubmitting = true;
			ctx.message = '';

			const formData = new FormData(form);
			formData.append('action', 'dame_submit_newsletter');
			formData.append('nonce', ctx.nonce);

			try {
				const response = await fetch(ctx.ajaxUrl, {
					method: 'POST',
					body: formData,
				});

				const result = await response.json();
				if (result.success) {
					ctx.status = 'success';
					ctx.message =
						result.data?.message || 'Inscription réussie !';
					form.reset();
				} else {
					ctx.status = 'error';
					ctx.message =
						result.data?.message || 'Une erreur est survenue.';
				}
			} catch {
				ctx.status = 'error';
				ctx.message = 'Erreur de connexion au serveur.';
			} finally {
				ctx.isSubmitting = false;
			}
		},
	},
});
