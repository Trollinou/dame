/**
 * Public Pre-Inscription Form Handling
 */

document.addEventListener('DOMContentLoaded', (): void => {
	// We call the autocomplete initializers here, as this script is loaded
	// after dame-public-geo-autocomplete.js, ensuring the function is available.
	if (typeof window.initBirthCityAutocomplete === 'function') {
		window.initBirthCityAutocomplete('dame_birth_city');
		window.initBirthCityAutocomplete('dame_legal_rep_1_commune_naissance');
		window.initBirthCityAutocomplete('dame_legal_rep_2_commune_naissance');
	}

	const birthDateInput = document.getElementById(
		'dame_birth_date'
	) as HTMLInputElement | null;
	if (!birthDateInput) {
		return;
	}

	const dynamicFields = document.getElementById('dame-dynamic-fields');
	const majeurFields = document.getElementById('dame-adherent-majeur-fields');
	const mineurFields = document.getElementById('dame-adherent-mineur-fields');

	const healthQuestionnaireLinkContainer = document.getElementById(
		'health-questionnaire-link-container'
	);
	const healthQuestionnaireLink = document.getElementById(
		'health-questionnaire-link'
	) as HTMLAnchorElement | null;

	const pdfBaseUrl = '/wp-content/plugins/dame/assets/pdf/';
	const mineurPDF = pdfBaseUrl + 'questionnaire_sante_mineur.pdf';
	const majeurPDF = pdfBaseUrl + 'questionnaire_sante_majeur.pdf';

	// Adherent fields
	const birthCityInput = document.getElementById(
		'dame_birth_city'
	) as HTMLInputElement | null;
	const birthCityRequiredIndicator = document.getElementById(
		'dame_birth_city_required_indicator'
	);
	const lastNameInput = document.getElementById(
		'dame_last_name'
	) as HTMLInputElement | null;

	// Rep 1 fields
	const rep1RequiredIndicators = document.querySelectorAll<HTMLElement>(
		'.dame-rep1-required-indicator'
	);
	const rep1FirstNameInput = document.getElementById(
		'dame_legal_rep_1_first_name'
	) as HTMLInputElement | null;
	const rep1LastNameInput = document.getElementById(
		'dame_legal_rep_1_last_name'
	) as HTMLInputElement | null;
	const rep1EmailInput = document.getElementById(
		'dame_legal_rep_1_email'
	) as HTMLInputElement | null;
	const rep1PhoneInput = document.getElementById(
		'dame_legal_rep_1_phone'
	) as HTMLInputElement | null;
	const rep1Address1Input = document.getElementById(
		'dame_legal_rep_1_address_1'
	) as HTMLInputElement | null;
	const rep1CityInput = document.getElementById(
		'dame_legal_rep_1_city'
	) as HTMLInputElement | null;
	const rep1RequiredInputs: (HTMLInputElement | null)[] = [
		rep1FirstNameInput,
		rep1LastNameInput,
		rep1EmailInput,
		rep1PhoneInput,
		rep1Address1Input,
		rep1CityInput,
	];

	birthDateInput.addEventListener(
		'change',
		function (this: HTMLInputElement): void {
			const birthDate = new Date(this.value);
			if (isNaN(birthDate.getTime())) {
				if (dynamicFields) {
					dynamicFields.style.display = 'none';
				}
				if (healthQuestionnaireLinkContainer) {
					healthQuestionnaireLinkContainer.style.display = 'none';
				}
				return;
			}

			const today = new Date();
			let age = today.getFullYear() - birthDate.getFullYear();
			const m = today.getMonth() - birthDate.getMonth();
			if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
				age--;
			}

			if (dynamicFields) {
				dynamicFields.style.display = 'block';
			}

			if (age >= 18) {
				if (majeurFields) {
					majeurFields.style.display = 'block';
				}
				if (mineurFields) {
					mineurFields.style.display = 'none';
				}

				// For adults, birth city is required.
				if (birthCityInput) {
					birthCityInput.required = true;
				}
				if (birthCityRequiredIndicator) {
					birthCityRequiredIndicator.style.display = 'inline';
				}

				// Clear all inputs within the minor fields container to prevent submission of hidden data
				if (mineurFields) {
					const minorInputs =
						mineurFields.querySelectorAll<HTMLInputElement>(
							'input'
						);
					minorInputs.forEach((input: HTMLInputElement): void => {
						input.value = '';
					});
				}
				// Make rep 1 fields not required and hide indicators
				rep1RequiredInputs.forEach(
					(input: HTMLInputElement | null): void => {
						if (input) {
							input.required = false;
						}
					}
				);
				rep1RequiredIndicators.forEach(
					(indicator: HTMLElement): void => {
						indicator.style.display = 'none';
					}
				);

				if (healthQuestionnaireLink) {
					healthQuestionnaireLink.href = majeurPDF;
					healthQuestionnaireLink.textContent =
						'Consulter le questionnaire pour Majeur';
				}
				if (healthQuestionnaireLinkContainer) {
					healthQuestionnaireLinkContainer.style.display = 'inline';
				}
			} else {
				if (majeurFields) {
					majeurFields.style.display = 'none';
				}
				if (mineurFields) {
					mineurFields.style.display = 'block';
				}

				// For minors, birth city is not required.
				if (birthCityInput) {
					birthCityInput.required = false;
				}
				if (birthCityRequiredIndicator) {
					birthCityRequiredIndicator.style.display = 'none';
				}

				// Make rep 1 fields required and show indicators
				rep1RequiredInputs.forEach(
					(input: HTMLInputElement | null): void => {
						if (input) {
							input.required = true;
						}
					}
				);
				rep1RequiredIndicators.forEach(
					(indicator: HTMLElement): void => {
						indicator.style.display = 'inline';
					}
				);

				if (healthQuestionnaireLink) {
					healthQuestionnaireLink.href = mineurPDF;
					healthQuestionnaireLink.textContent =
						'Consulter le questionnaire pour Mineur';
				}
				if (healthQuestionnaireLinkContainer) {
					healthQuestionnaireLinkContainer.style.display = 'inline';
				}
			}
		}
	);

	// Add live formatting for name fields
	const firstNameInput = document.getElementById(
		'dame_first_name'
	) as HTMLInputElement | null;
	const birthNameInput = document.getElementById(
		'dame_birth_name'
	) as HTMLInputElement | null;
	const rep2FirstNameInput = document.getElementById(
		'dame_legal_rep_2_first_name'
	) as HTMLInputElement | null;
	const rep2LastNameInput = document.getElementById(
		'dame_legal_rep_2_last_name'
	) as HTMLInputElement | null;

	if (firstNameInput) {
		firstNameInput.addEventListener('input', formatFirstNameInput);
	}
	if (birthNameInput) {
		birthNameInput.addEventListener('input', formatLastNameInput);
	}
	if (lastNameInput) {
		lastNameInput.addEventListener('input', formatLastNameInput);
	}
	if (rep1FirstNameInput) {
		rep1FirstNameInput.addEventListener('input', formatFirstNameInput);
	}
	if (rep1LastNameInput) {
		rep1LastNameInput.addEventListener('input', formatLastNameInput);
	}
	if (rep2FirstNameInput) {
		rep2FirstNameInput.addEventListener('input', formatFirstNameInput);
	}
	if (rep2LastNameInput) {
		rep2LastNameInput.addEventListener('input', formatLastNameInput);
	}

	// Form and submit elements
	const form = document.getElementById(
		'dame-pre-inscription-form'
	) as HTMLFormElement | null;
	const consentCheckbox = document.getElementById(
		'dame_consent_checkbox'
	) as HTMLInputElement | null;
	const submitButtonInForm = form
		? form.querySelector<HTMLButtonElement>('button[type="submit"]')
		: null;

	// Signature Canvas & Validation Logic
	const signatureSection = document.getElementById('dame-signature-section');
	const signatureCanvas = document.getElementById(
		'dame-signature-canvas'
	) as HTMLCanvasElement | null;
	const clearSignatureBtn = document.getElementById('dame-clear-signature');
	const signatureImageInput = document.getElementById(
		'dame_signature_image'
	) as HTMLInputElement | null;
	const healthAttestationConsent = document.getElementById(
		'dame_health_attestation_consent'
	) as HTMLInputElement | null;
	const parentalAuthConsentP = document.getElementById(
		'dame-parental-auth-consent-p'
	);
	const parentalAuthConsent = document.getElementById(
		'dame_parental_auth_consent'
	) as HTMLInputElement | null;
	const signatureHint = document.getElementById('dame-signature-hint');

	let signatureCtx: CanvasRenderingContext2D | null = null;
	let isDrawing = false;
	let hasSignature = false;

	const setupCanvas = (): void => {
		if (!signatureCanvas) {
			return;
		}
		const rect = signatureCanvas.getBoundingClientRect();
		if (rect.width === 0) {
			return;
		}

		let tempImg: ImageData | null = null;
		if (
			signatureCtx &&
			signatureCanvas.width > 0 &&
			signatureCanvas.height > 0 &&
			hasSignature
		) {
			tempImg = signatureCtx.getImageData(
				0,
				0,
				signatureCanvas.width,
				signatureCanvas.height
			);
		}

		const dpr = window.devicePixelRatio || 1;
		signatureCanvas.width = rect.width * dpr;
		signatureCanvas.height = 160 * dpr;

		signatureCtx = signatureCanvas.getContext('2d');
		if (signatureCtx) {
			signatureCtx.scale(dpr, dpr);
			signatureCtx.lineCap = 'round';
			signatureCtx.lineJoin = 'round';
			signatureCtx.lineWidth = 2.5;
			signatureCtx.strokeStyle = '#000000';

			if (tempImg) {
				signatureCtx.putImageData(tempImg, 0, 0);
			}
		}
	};

	const clearSignature = (): void => {
		if (!signatureCanvas || !signatureCtx) {
			return;
		}
		signatureCtx.clearRect(
			0,
			0,
			signatureCanvas.width,
			signatureCanvas.height
		);
		hasSignature = false;
		if (signatureImageInput) {
			signatureImageInput.value = '';
		}
		checkSubmitState();
	};

	if (clearSignatureBtn) {
		clearSignatureBtn.addEventListener('click', clearSignature);
	}

	window.addEventListener('resize', setupCanvas);

	const getPointerPos = (e: PointerEvent): { x: number; y: number } => {
		if (!signatureCanvas) {
			return { x: 0, y: 0 };
		}
		const rect = signatureCanvas.getBoundingClientRect();
		return {
			x: e.clientX - rect.left,
			y: e.clientY - rect.top,
		};
	};

	if (signatureCanvas) {
		signatureCanvas.addEventListener(
			'pointerdown',
			(e: PointerEvent): void => {
				if (!signatureCtx) {
					setupCanvas();
				}
				signatureCanvas.setPointerCapture(e.pointerId);
				isDrawing = true;
				const pos = getPointerPos(e);
				if (signatureCtx) {
					signatureCtx.beginPath();
					signatureCtx.moveTo(pos.x, pos.y);
				}
			}
		);

		signatureCanvas.addEventListener(
			'pointermove',
			(e: PointerEvent): void => {
				if (!isDrawing || !signatureCtx) {
					return;
				}
				const pos = getPointerPos(e);
				signatureCtx.lineTo(pos.x, pos.y);
				signatureCtx.stroke();
				hasSignature = true;
				checkSubmitState();
			}
		);

		const stopDrawing = (e: PointerEvent): void => {
			if (!isDrawing) {
				return;
			}
			isDrawing = false;
			if (
				signatureCanvas.hasPointerCapture &&
				signatureCanvas.hasPointerCapture(e.pointerId)
			) {
				signatureCanvas.releasePointerCapture(e.pointerId);
			}
			if (hasSignature && signatureImageInput) {
				signatureImageInput.value =
					signatureCanvas.toDataURL('image/png');
			}
			checkSubmitState();
		};

		signatureCanvas.addEventListener('pointerup', stopDrawing);
		signatureCanvas.addEventListener('pointercancel', stopDrawing);
	}

	// Dynamic health questionnaire change handler
	const updateHealthAndSignatureState = (): void => {
		const selectedRadio = form
			? form.querySelector<HTMLInputElement>(
					'input[name="dame_health_questionnaire"]:checked'
				)
			: null;
		const val = selectedRadio ? selectedRadio.value : '';

		const birthDate = birthDateInput
			? new Date(birthDateInput.value)
			: null;
		let isMinor = false;
		if (birthDate && !isNaN(birthDate.getTime())) {
			const today = new Date();
			let age = today.getFullYear() - birthDate.getFullYear();
			const m = today.getMonth() - birthDate.getMonth();
			if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
				age--;
			}
			isMinor = age < 18;
		}

		if (val === 'non') {
			if (signatureSection) {
				signatureSection.style.display = 'block';
				setTimeout(setupCanvas, 50);
			}
			if (isMinor) {
				if (parentalAuthConsentP) {
					parentalAuthConsentP.style.display = 'block';
				}
				if (signatureHint) {
					signatureHint.textContent =
						'Veuillez apposer ci-dessous la signature manuscrite du représentant légal.';
				}
			} else {
				if (parentalAuthConsentP) {
					parentalAuthConsentP.style.display = 'none';
				}
				if (signatureHint) {
					signatureHint.textContent =
						'Veuillez apposer ci-dessous votre signature manuscrite.';
				}
			}
		} else if (signatureSection) {
			signatureSection.style.display = 'none';
		}
		checkSubmitState();
	};

	const healthRadios = form
		? form.querySelectorAll<HTMLInputElement>(
				'input[name="dame_health_questionnaire"]'
			)
		: [];
	healthRadios.forEach((radio: HTMLInputElement): void => {
		radio.addEventListener('change', updateHealthAndSignatureState);
	});

	birthDateInput.addEventListener('change', (): void => {
		setTimeout(updateHealthAndSignatureState, 50);
	});

	// Check form validation state for submit button
	const checkSubmitState = (): void => {
		if (!submitButtonInForm) {
			return;
		}

		const isConsentChecked = consentCheckbox
			? consentCheckbox.checked
			: false;
		if (!isConsentChecked) {
			submitButtonInForm.disabled = true;
			return;
		}

		const selectedRadio = form
			? form.querySelector<HTMLInputElement>(
					'input[name="dame_health_questionnaire"]:checked'
				)
			: null;
		const healthVal = selectedRadio ? selectedRadio.value : '';

		if (healthVal === 'non') {
			const isHealthConsent = healthAttestationConsent
				? healthAttestationConsent.checked
				: false;
			if (!isHealthConsent) {
				submitButtonInForm.disabled = true;
				return;
			}

			// If minor, parental auth consent must also be checked
			const birthDate = birthDateInput
				? new Date(birthDateInput.value)
				: null;
			let isMinor = false;
			if (birthDate && !isNaN(birthDate.getTime())) {
				const today = new Date();
				let age = today.getFullYear() - birthDate.getFullYear();
				const m = today.getMonth() - birthDate.getMonth();
				if (
					m < 0 ||
					(m === 0 && today.getDate() < birthDate.getDate())
				) {
					age--;
				}
				isMinor = age < 18;
			}

			if (isMinor) {
				const isParentalConsent = parentalAuthConsent
					? parentalAuthConsent.checked
					: false;
				if (!isParentalConsent) {
					submitButtonInForm.disabled = true;
					return;
				}
			}

			if (!hasSignature) {
				submitButtonInForm.disabled = true;
				return;
			}
		}

		submitButtonInForm.disabled = false;
	};

	if (consentCheckbox) {
		consentCheckbox.addEventListener('change', checkSubmitState);
	}
	if (healthAttestationConsent) {
		healthAttestationConsent.addEventListener('change', checkSubmitState);
	}
	if (parentalAuthConsent) {
		parentalAuthConsent.addEventListener('change', checkSubmitState);
	}

	// Add event listeners for copy buttons
	const copyButtons =
		document.querySelectorAll<HTMLElement>('.dame-copy-button');
	copyButtons.forEach((button: HTMLElement): void => {
		button.addEventListener('click', function (this: HTMLElement): void {
			const repId = this.getAttribute('data-rep-id');
			if (repId) {
				copyAdherentData(repId);
			}
		});
	});
});

function copyAdherentData(repId: string | number): void {
	// Adherent fields
	const birthNameInput = document.getElementById(
		'dame_birth_name'
	) as HTMLInputElement | null;
	const emailInput = document.getElementById(
		'dame_email'
	) as HTMLInputElement | null;
	const phoneInput = document.getElementById(
		'dame_phone_number'
	) as HTMLInputElement | null;
	const address1Input = document.getElementById(
		'dame_address_1'
	) as HTMLInputElement | null;
	const address2Input = document.getElementById(
		'dame_address_2'
	) as HTMLInputElement | null;
	const postalCodeInput = document.getElementById(
		'dame_postal_code'
	) as HTMLInputElement | null;
	const cityInput = document.getElementById(
		'dame_city'
	) as HTMLInputElement | null;

	// Rep fields
	const repLastNameInput = document.getElementById(
		'dame_legal_rep_' + repId + '_last_name'
	) as HTMLInputElement | null;
	const repEmailInput = document.getElementById(
		'dame_legal_rep_' + repId + '_email'
	) as HTMLInputElement | null;
	const repPhoneInput = document.getElementById(
		'dame_legal_rep_' + repId + '_phone'
	) as HTMLInputElement | null;
	const repAddress1Input = document.getElementById(
		'dame_legal_rep_' + repId + '_address_1'
	) as HTMLInputElement | null;
	const repAddress2Input = document.getElementById(
		'dame_legal_rep_' + repId + '_address_2'
	) as HTMLInputElement | null;
	const repPostalCodeInput = document.getElementById(
		'dame_legal_rep_' + repId + '_postal_code'
	) as HTMLInputElement | null;
	const repCityInput = document.getElementById(
		'dame_legal_rep_' + repId + '_city'
	) as HTMLInputElement | null;

	if (
		birthNameInput &&
		emailInput &&
		phoneInput &&
		address1Input &&
		postalCodeInput &&
		cityInput &&
		repLastNameInput &&
		repEmailInput &&
		repPhoneInput &&
		repAddress1Input &&
		repPostalCodeInput &&
		repCityInput
	) {
		repLastNameInput.value = birthNameInput.value;
		repEmailInput.value = emailInput.value;
		repPhoneInput.value = phoneInput.value;
		repAddress1Input.value = address1Input.value;
		if (address2Input && repAddress2Input) {
			repAddress2Input.value = address2Input.value;
		}
		repPostalCodeInput.value = postalCodeInput.value;
		repCityInput.value = cityInput.value;
	}
}

/**
 * Formats a string to Mixed Case.
 * Capitalizes the first letter of each word separated by a space or a hyphen.
 * @param str
 */
function formatToMixedCase(str: string): string {
	if (!str) {
		return '';
	}
	return str
		.toLowerCase()
		.replace(/(^|[\s-])\S/g, (match: string) => match.toUpperCase());
}

/**
 * Formats the input value of a first name field to Mixed Case.
 * @param event
 */
function formatFirstNameInput(event: Event): void {
	const input = event.target as HTMLInputElement;
	if (!input) {
		return;
	}
	const value = input.value;
	const formattedValue = formatToMixedCase(value);
	const cursorPosition = input.selectionStart;

	input.value = formattedValue;
	// Restore cursor position
	if (cursorPosition !== null) {
		input.setSelectionRange(cursorPosition, cursorPosition);
	}
}

/**
 * Formats the input value of a last name field to uppercase.
 * @param event
 */
function formatLastNameInput(event: Event): void {
	const input = event.target as HTMLInputElement;
	if (!input) {
		return;
	}
	const value = input.value;
	const formattedValue = value.toUpperCase();
	const cursorPosition = input.selectionStart;

	input.value = formattedValue;
	// Restore cursor position
	if (cursorPosition !== null) {
		input.setSelectionRange(cursorPosition, cursorPosition);
	}
}
