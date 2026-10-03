/**
 * Interactivity API Module - DAME Benevolat Store.
 *
 * Provides reactive slot selection and real-time response management for volunteering.
 */

import { store, getContext } from '@wordpress/interactivity';

export interface BenevolatContext {
	selectedSlots: string[];
	isSubmitting: boolean;
	status: 'idle' | 'success' | 'error';
	message: string;
	ajaxUrl: string;
	nonce: string;
}

export const benevolatStore = store('dame/benevolat', {
	state: {
		get selectedCount(): number {
			const ctx = getContext<BenevolatContext>();
			return ctx.selectedSlots.length;
		},
	},
	actions: {
		toggleSlot(event: Event): void {
			const target = event.target as HTMLInputElement;
			if (!target) {
				return;
			}

			const ctx = getContext<BenevolatContext>();
			const slotId = target.value;
			if (target.checked) {
				if (!ctx.selectedSlots.includes(slotId)) {
					ctx.selectedSlots.push(slotId);
				}
			} else {
				ctx.selectedSlots = ctx.selectedSlots.filter(
					(s: string) => s !== slotId
				);
			}
		},

		async submitResponse(event: Event): Promise<void> {
			event.preventDefault();
			event.stopPropagation();
			const form = event.target as HTMLFormElement;
			if (!form) {
				return;
			}

			// HTML5 native validation
			if (!form.checkValidity()) {
				form.reportValidity();
				return;
			}

			const ctx = getContext<BenevolatContext>();
			if (ctx.isSubmitting) {
				return;
			}
			ctx.isSubmitting = true;
			ctx.status = 'idle';
			ctx.message = '';

			const submitBtn = form.querySelector<
				HTMLButtonElement | HTMLInputElement
			>('button[type="submit"], input[type="submit"]');
			if (submitBtn) {
				submitBtn.disabled = true;
			}

			const formData = new FormData(form);
			if (!formData.has('submit_benevolat')) {
				formData.append('submit_benevolat', '1');
			}

			try {
				const response = await fetch(form.action || ctx.ajaxUrl, {
					method: 'POST',
					body: formData,
				});

				if (response.ok) {
					ctx.status = 'success';
					ctx.message =
						'Votre réponse a bien été enregistrée. Merci !';
				} else {
					ctx.status = 'error';
					ctx.message =
						"Erreur lors de l'enregistrement de votre réponse.";
				}
			} catch {
				ctx.status = 'error';
				ctx.message = 'Erreur de communication avec le serveur.';
			} finally {
				ctx.isSubmitting = false;
				if (submitBtn) {
					submitBtn.disabled = false;
				}
			}
		},
	},
});
