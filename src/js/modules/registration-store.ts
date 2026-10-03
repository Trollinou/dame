/**
 * Interactivity API Module - DAME Registration Form Store.
 *
 * Provides reactive form validation, representative toggling, and submission state.
 */

import { store, getContext } from '@wordpress/interactivity';

export interface RegistrationContext {
	isMinor: boolean;
	hasSecondRep: boolean;
	isSubmitting: boolean;
	status: 'idle' | 'success' | 'error';
	message: string;
	ajaxUrl: string;
	fullName?: string;
	healthQuestionnaire?: string;
	hasSignedHealth?: boolean;
	hasSignedParental?: boolean;
	postId?: number | null;
	nonce?: string;
	parentalAuthNonce?: string;
	paymentUrl?: string;
	senderEmail?: string;
}

export const registrationStore = store('dame/registration', {
	state: {
		get isBusy(): boolean {
			const ctx = getContext<RegistrationContext>();
			return ctx.isSubmitting;
		},
		get hasMessage(): boolean {
			const ctx = getContext<RegistrationContext>();
			return Boolean(ctx.message && ctx.message.trim().length > 0);
		},
		get isSuccess(): boolean {
			const ctx = getContext<RegistrationContext>();
			return ctx.status === 'success';
		},
		get isError(): boolean {
			const ctx = getContext<RegistrationContext>();
			return ctx.status === 'error';
		},
		get showLegalRepresentatives(): boolean {
			const ctx = getContext<RegistrationContext>();
			return ctx.isMinor;
		},
		get showSecondRepresentative(): boolean {
			const ctx = getContext<RegistrationContext>();
			return ctx.isMinor && ctx.hasSecondRep;
		},
		get needsMedicalCertificate(): boolean {
			const ctx = getContext<RegistrationContext>();
			return ctx.healthQuestionnaire === 'oui';
		},
		get hasSignedDocuments(): boolean {
			const ctx = getContext<RegistrationContext>();
			return Boolean(ctx.hasSignedHealth || ctx.hasSignedParental);
		},
		get hasUnsignedDocuments(): boolean {
			const ctx = getContext<RegistrationContext>();
			if (ctx.status !== 'success') {
				return false;
			}
			const needsHealth =
				ctx.healthQuestionnaire === 'non' && !ctx.hasSignedHealth;
			const needsParental = ctx.isMinor && !ctx.hasSignedParental;
			return Boolean(needsHealth || needsParental);
		},
		get showSignedHealth(): boolean {
			const ctx = getContext<RegistrationContext>();
			return Boolean(ctx.hasSignedHealth && ctx.postId && ctx.nonce);
		},
		get showSignedParental(): boolean {
			const ctx = getContext<RegistrationContext>();
			return Boolean(
				ctx.hasSignedParental && ctx.postId && ctx.parentalAuthNonce
			);
		},
		get showUnsignedHealth(): boolean {
			const ctx = getContext<RegistrationContext>();
			return Boolean(
				ctx.healthQuestionnaire === 'non' &&
				!ctx.hasSignedHealth &&
				ctx.postId &&
				ctx.nonce
			);
		},
		get showUnsignedParental(): boolean {
			const ctx = getContext<RegistrationContext>();
			return Boolean(
				ctx.isMinor &&
				!ctx.hasSignedParental &&
				ctx.postId &&
				ctx.parentalAuthNonce
			);
		},
		get healthPdfUrl(): string {
			const ctx = getContext<RegistrationContext>();
			if (!ctx.postId || !ctx.nonce) {
				return '';
			}
			return `${ctx.ajaxUrl}?action=dame_generate_health_form&post_id=${ctx.postId}&_wpnonce=${ctx.nonce}`;
		},
		get parentalPdfUrl(): string {
			const ctx = getContext<RegistrationContext>();
			if (!ctx.postId || !ctx.parentalAuthNonce) {
				return '';
			}
			return `${ctx.ajaxUrl}?action=dame_generate_parental_auth&post_id=${ctx.postId}&_wpnonce=${ctx.parentalAuthNonce}`;
		},
		get senderEmailMailto(): string {
			const ctx = getContext<RegistrationContext>();
			return ctx.senderEmail ? `mailto:${ctx.senderEmail}` : '';
		},
		get hasPaymentUrl(): boolean {
			const ctx = getContext<RegistrationContext>();
			return Boolean(ctx.paymentUrl && ctx.paymentUrl.trim().length > 0);
		},
	},
	actions: {
		onBirthDateChange(event: Event): void {
			const target = event.target as HTMLInputElement;
			if (!target || !target.value) {
				return;
			}

			const ctx = getContext<RegistrationContext>();
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
			event.stopPropagation();
			const form = event.target as HTMLFormElement;
			if (!form) {
				return;
			}

			const ctx = getContext<RegistrationContext>();
			if (ctx.isSubmitting) {
				return;
			}
			ctx.isSubmitting = true;
			ctx.message = '';
			ctx.status = 'idle';

			const submitBtn = form.querySelector<HTMLButtonElement>(
				'button[type="submit"]'
			);
			if (submitBtn) {
				submitBtn.disabled = true;
			}

			// Ensure canvas signature is exported to hidden input if needed
			const signatureCanvas = document.getElementById(
				'dame-signature-canvas'
			) as HTMLCanvasElement | null;
			const signatureInput = document.getElementById(
				'dame_signature_image'
			) as HTMLInputElement | null;
			const healthRadio = form.querySelector<HTMLInputElement>(
				'input[name="dame_health_questionnaire"]:checked'
			);

			if (
				healthRadio &&
				healthRadio.value === 'non' &&
				signatureCanvas &&
				signatureInput &&
				!signatureInput.value
			) {
				const blank = document.createElement('canvas');
				blank.width = signatureCanvas.width;
				blank.height = signatureCanvas.height;
				if (signatureCanvas.toDataURL() !== blank.toDataURL()) {
					signatureInput.value =
						signatureCanvas.toDataURL('image/png');
				}
			}

			const formData = new FormData(form);
			formData.append('action', 'dame_submit_pre_inscription');

			try {
				const response = await fetch(ctx.ajaxUrl, {
					method: 'POST',
					body: new URLSearchParams(
						formData as unknown as Record<string, string>
					),
				});

				const result = await response.json();
				if (result.success && result.data) {
					ctx.status = 'success';
					ctx.message =
						result.data.message ||
						'Votre fiche de préinscription a bien été enregistrée.';
					ctx.fullName = result.data.full_name || '';
					ctx.healthQuestionnaire =
						result.data.health_questionnaire || '';
					ctx.hasSignedHealth = Boolean(
						result.data.has_signed_health
					);
					ctx.hasSignedParental = Boolean(
						result.data.has_signed_parental
					);
					ctx.postId = result.data.post_id || null;
					ctx.nonce = result.data.nonce || '';
					ctx.parentalAuthNonce =
						result.data.parental_auth_nonce || '';
					ctx.paymentUrl = result.data.payment_url || '';
					ctx.senderEmail = result.data.sender_email || '';

					const wrapper = document.getElementById(
						'dame-pre-inscription-form-wrapper'
					);
					if (wrapper) {
						wrapper.scrollIntoView({ behavior: 'smooth' });
					}
				} else {
					ctx.status = 'error';
					ctx.message =
						result.data?.message ||
						"Erreur lors de l'enregistrement de la fiche.";
				}
			} catch {
				ctx.status = 'error';
				ctx.message = 'Erreur de communication avec le serveur.';
			} finally {
				ctx.isSubmitting = false;
			}
		},

		resetForm(): void {
			const ctx = getContext<RegistrationContext>();
			ctx.status = 'idle';
			ctx.message = '';
			ctx.isSubmitting = false;
			ctx.postId = null;
			ctx.nonce = '';
			ctx.parentalAuthNonce = '';
			ctx.hasSignedHealth = false;
			ctx.hasSignedParental = false;
			ctx.fullName = '';
			ctx.healthQuestionnaire = '';
			ctx.paymentUrl = '';
			ctx.senderEmail = '';

			const form = document.getElementById(
				'dame-pre-inscription-form'
			) as HTMLFormElement | null;
			if (form) {
				form.reset();
				const dynamicFields = document.getElementById(
					'dame-dynamic-fields'
				);
				if (dynamicFields) {
					dynamicFields.style.display = 'none';
				}
				const signatureSection = document.getElementById(
					'dame-signature-section'
				);
				if (signatureSection) {
					signatureSection.style.display = 'none';
				}
				const clearBtn = document.getElementById(
					'dame-clear-signature'
				);
				if (clearBtn) {
					clearBtn.click();
				}
				form.scrollIntoView({ behavior: 'smooth' });
			}
		},
	},
});
