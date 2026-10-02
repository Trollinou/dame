/**
 * Interactivity API Module - DAME Registration Form Store.
 *
 * Provides reactive form validation, representative toggling, and multi-step management.
 */

import { store, getContext } from '@wordpress/interactivity';

export interface RegistrationContext {
	isMinor: boolean;
	hasSecondRep: boolean;
	isSubmitting: boolean;
	status: 'idle' | 'success' | 'error';
	message: string;
	ajaxUrl: string;
}

export const registrationStore = store('dame/registration', {
	state: {
		get showLegalRepresentatives(): boolean {
			const ctx = getContext<RegistrationContext>();
			return ctx.isMinor;
		},
		get showSecondRepresentative(): boolean {
			const ctx = getContext<RegistrationContext>();
			return ctx.isMinor && ctx.hasSecondRep;
		},
	},
	actions: {
		onBirthDateChange(event: Event): void {
			const ctx = getContext<RegistrationContext>();
			const target = event.target as HTMLInputElement;
			if (!target || !target.value) {
				return;
			}

			const birthDate = new Date(target.value);
			const today = new Date();
			let age = today.getFullYear() - birthDate.getFullYear();
			const m = today.getMonth() - birthDate.getMonth();
			if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
				age--;
			}

			ctx.isMinor = age < 18;
		},

		toggleSecondRep(): void {
			const ctx = getContext<RegistrationContext>();
			ctx.hasSecondRep = !ctx.hasSecondRep;
		},

		async submitForm(event: Event): Promise<void> {
			event.preventDefault();
			const ctx = getContext<RegistrationContext>();
			const form = event.target as HTMLFormElement;
			if (!form) {
				return;
			}

			ctx.isSubmitting = true;
			ctx.message = '';

			const formData = new FormData(form);
			formData.append('action', 'dame_submit_pre_inscription');

			try {
				const response = await fetch(ctx.ajaxUrl, {
					method: 'POST',
					body: formData,
				});

				const result = await response.json();
				if (result.success) {
					ctx.status = 'success';
					ctx.message = result.data?.message || 'Votre fiche d\'inscription a bien été transmise.';
					form.reset();
				} else {
					ctx.status = 'error';
					ctx.message = result.data?.message || 'Erreur lors de l\'enregistrement de la fiche.';
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
