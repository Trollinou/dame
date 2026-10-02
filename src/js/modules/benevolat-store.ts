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
			const ctx = getContext<BenevolatContext>();
			const target = event.target as HTMLInputElement;
			if (!target) {
				return;
			}

			const slotId = target.value;
			if (target.checked) {
				if (!ctx.selectedSlots.includes(slotId)) {
					ctx.selectedSlots.push(slotId);
				}
			} else {
				ctx.selectedSlots = ctx.selectedSlots.filter(s => s !== slotId);
			}
		},

		async submitResponse(event: Event): Promise<void> {
			event.preventDefault();
			const ctx = getContext<BenevolatContext>();
			const form = event.target as HTMLFormElement;
			if (!form) {
				return;
			}

			ctx.isSubmitting = true;
			ctx.message = '';

			const formData = new FormData(form);

			try {
				const response = await fetch(form.action || ctx.ajaxUrl, {
					method: 'POST',
					body: formData,
				});

				if (response.ok) {
					ctx.status = 'success';
					ctx.message = 'Votre réponse a bien été enregistrée. Merci !';
				} else {
					ctx.status = 'error';
					ctx.message = 'Erreur lors de l\'enregistrement de votre réponse.';
				}
			} catch (err) {
				ctx.status = 'error';
				ctx.message = 'Erreur de communication avec le serveur.';
			} finally {
				ctx.isSubmitting = false;
			}
		},
	},
});
