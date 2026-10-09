/**
 * Admin Attestation Modal and Actions.
 * Handles single member attestation modal, prefilling, PDF download, and email sending.
 */

interface AttestationResponseData {
	adherent_id: number;
	adherent_name: string;
	season_name: string;
	payment_status: 'renewal' | 'first_registration';
	payment_amount: number;
	payment_date: string;
	payment_method: string;
	emails: string[];
}

interface DameAttestationGlobal {
	ajaxUrl: string;
	nonce: string;
}

declare const dameAttestationData: DameAttestationGlobal;

document.addEventListener('DOMContentLoaded', (): void => {
	if (typeof dameAttestationData === 'undefined') {
		return;
	}

	let currentAdherentId = 0;
	let currentData: AttestationResponseData | null = null;
	let isSubmitting = false;

	// Create modal container in DOM once.
	const modalOverlay = document.createElement('div');
	modalOverlay.className = 'dame-attestation-modal-overlay';
	modalOverlay.style.display = 'none';
	modalOverlay.setAttribute('role', 'dialog');
	modalOverlay.setAttribute('aria-modal', 'true');
	modalOverlay.setAttribute(
		'aria-labelledby',
		'dame-attestation-modal-title'
	);

	modalOverlay.innerHTML = `
		<div class="dame-attestation-modal">
			<div class="dame-attestation-modal-header">
				<h2 id="dame-attestation-modal-title">
					<span class="dashicons dashicons-printer"></span>
					Attestation de paiement
				</h2>
				<button type="button" class="dame-modal-close-btn" aria-label="Fermer">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="dame-attestation-modal-body">
				<div class="dame-modal-loading">
					<span class="spinner is-active"></span>
					<p>Chargement des informations...</p>
				</div>
				<div class="dame-modal-content" style="display: none;">
					<div class="dame-modal-feedback" style="display: none;"></div>

					<div class="dame-form-group">
						<label for="dame-modal-adherent-name">Adhérent(e)</label>
						<input type="text" id="dame-modal-adherent-name" readonly disabled />
					</div>

					<div class="dame-form-group">
						<label for="dame-modal-season-name">Saison sportive</label>
						<input type="text" id="dame-modal-season-name" readonly disabled />
					</div>

					<div class="dame-form-group">
						<label for="dame-modal-payment-status">Statut d'adhésion</label>
						<select id="dame-modal-payment-status">
							<option value="renewal">Renouvellement</option>
							<option value="first_registration">1ère adhésion (Polo floqué)</option>
						</select>
					</div>

					<div class="dame-form-group">
						<label for="dame-modal-payment-amount">Montant réglé (€)</label>
						<input type="number" id="dame-modal-payment-amount" step="0.5" min="0" required />
						<p class="dame-help-text">Le montant en lettres sera automatiquement transcrit sur l'attestation.</p>
					</div>

					<div class="dame-form-group">
						<label for="dame-modal-payment-date">Date du règlement</label>
						<input type="date" id="dame-modal-payment-date" required />
					</div>

					<div class="dame-form-group">
						<label for="dame-modal-payment-method">Mode de règlement</label>
						<select id="dame-modal-payment-method">
							<option value="HelloAsso">HelloAsso</option>
							<option value="Chèques">Chèques</option>
							<option value="Espèces">Espèces</option>
							<option value="Carte bancaire">Carte bancaire</option>
						</select>
					</div>

					<div class="dame-recipients-box">
						<strong>Destinataires e-mail détectés :</strong>
						<ul id="dame-modal-emails-list"></ul>
					</div>
				</div>
			</div>
			<div class="dame-attestation-modal-footer">
				<button type="button" class="button button-secondary dame-modal-cancel-btn">Annuler</button>
				<button type="button" class="button button-secondary dame-modal-download-btn">
					<span class="dashicons dashicons-download"></span>
					Télécharger le PDF
				</button>
				<button type="button" class="button button-primary dame-modal-send-btn">
					<span class="dashicons dashicons-email-alt"></span>
					Envoyer par e-mail
				</button>
			</div>
		</div>
	`;

	document.body.appendChild(modalOverlay);

	const loadingEl = modalOverlay.querySelector<HTMLElement>(
		'.dame-modal-loading'
	);
	const contentEl = modalOverlay.querySelector<HTMLElement>(
		'.dame-modal-content'
	);
	const feedbackEl = modalOverlay.querySelector<HTMLElement>(
		'.dame-modal-feedback'
	);
	const closeBtn = modalOverlay.querySelector<HTMLButtonElement>(
		'.dame-modal-close-btn'
	);
	const cancelBtn = modalOverlay.querySelector<HTMLButtonElement>(
		'.dame-modal-cancel-btn'
	);
	const downloadBtn = modalOverlay.querySelector<HTMLButtonElement>(
		'.dame-modal-download-btn'
	);
	const sendBtn = modalOverlay.querySelector<HTMLButtonElement>(
		'.dame-modal-send-btn'
	);

	const nameInput = modalOverlay.querySelector<HTMLInputElement>(
		'#dame-modal-adherent-name'
	);
	const seasonInput = modalOverlay.querySelector<HTMLInputElement>(
		'#dame-modal-season-name'
	);
	const statusSelect = modalOverlay.querySelector<HTMLSelectElement>(
		'#dame-modal-payment-status'
	);
	const amountInput = modalOverlay.querySelector<HTMLInputElement>(
		'#dame-modal-payment-amount'
	);
	const dateInput = modalOverlay.querySelector<HTMLInputElement>(
		'#dame-modal-payment-date'
	);
	const methodSelect = modalOverlay.querySelector<HTMLSelectElement>(
		'#dame-modal-payment-method'
	);
	const emailsList = modalOverlay.querySelector<HTMLUListElement>(
		'#dame-modal-emails-list'
	);

	const closeModal = (): void => {
		if (isSubmitting) {
			return;
		}
		modalOverlay.style.display = 'none';
		if (feedbackEl) {
			feedbackEl.style.display = 'none';
			feedbackEl.className = 'dame-modal-feedback';
			feedbackEl.textContent = '';
		}
	};

	closeBtn?.addEventListener('click', closeModal);
	cancelBtn?.addEventListener('click', closeModal);

	modalOverlay.addEventListener('click', (e: MouseEvent): void => {
		if (e.target === modalOverlay) {
			closeModal();
		}
	});

	// Handle open button click (delegation for dynamically loaded table rows and static buttons)
	document.addEventListener('click', (e: MouseEvent): void => {
		const target = (e.target as HTMLElement).closest<HTMLElement>(
			'.dame-open-attestation-btn'
		);
		if (!target) {
			return;
		}

		e.preventDefault();
		const rawId = target.getAttribute('data-adherent-id');
		if (!rawId) {
			return;
		}

		currentAdherentId = parseInt(rawId, 10);
		if (currentAdherentId <= 0) {
			return;
		}

		// Open modal in loading state
		modalOverlay.style.display = 'flex';
		if (loadingEl) {
			loadingEl.style.display = 'block';
		}
		if (contentEl) {
			contentEl.style.display = 'none';
		}
		if (feedbackEl) {
			feedbackEl.style.display = 'none';
		}

		// Fetch pre-filled data
		const fetchUrl = `${dameAttestationData.ajaxUrl}?action=dame_get_attestation_data&adherent_id=${currentAdherentId}&_wpnonce=${dameAttestationData.nonce}`;
		fetch(fetchUrl)
			.then((res) => res.json())
			.then((resp) => {
				if (!resp.success || !resp.data) {
					throw new Error(
						resp.data?.message ||
							'Erreur lors du chargement des informations.'
					);
				}

				currentData = resp.data as AttestationResponseData;

				if (nameInput) {
					nameInput.value = currentData.adherent_name;
				}
				if (seasonInput) {
					seasonInput.value = currentData.season_name;
				}
				if (statusSelect) {
					statusSelect.value = currentData.payment_status;
				}
				if (amountInput) {
					amountInput.value = String(currentData.payment_amount);
				}
				if (dateInput) {
					dateInput.value = currentData.payment_date;
				}
				if (methodSelect) {
					methodSelect.value = currentData.payment_method;
				}

				if (emailsList) {
					emailsList.innerHTML = '';
					if (currentData.emails.length > 0) {
						currentData.emails.forEach((email) => {
							const li = document.createElement('li');
							li.textContent = email;
							emailsList.appendChild(li);
						});
					} else {
						const li = document.createElement('li');
						li.textContent = 'Aucune adresse enregistrée';
						li.style.color = '#dc2626';
						emailsList.appendChild(li);
					}
				}

				if (loadingEl) {
					loadingEl.style.display = 'none';
				}
				if (contentEl) {
					contentEl.style.display = 'block';
				}
			})
			.catch((err: Error) => {
				if (loadingEl) {
					loadingEl.style.display = 'none';
				}
				if (contentEl) {
					contentEl.style.display = 'block';
				}
				if (feedbackEl) {
					feedbackEl.className = 'dame-modal-feedback notice-error';
					feedbackEl.textContent = err.message;
					feedbackEl.style.display = 'block';
				}
			});
	});

	// Handle Download PDF button
	downloadBtn?.addEventListener('click', (): void => {
		if (
			currentAdherentId <= 0 ||
			!amountInput ||
			!dateInput ||
			!statusSelect ||
			!methodSelect
		) {
			return;
		}

		const params = new URLSearchParams({
			action: 'dame_download_attestation_pdf',
			adherent_id: String(currentAdherentId),
			payment_status: statusSelect.value,
			payment_amount: amountInput.value,
			payment_date: dateInput.value,
			payment_method: methodSelect.value,
			_wpnonce: dameAttestationData.nonce,
		});

		window.location.href = `${dameAttestationData.ajaxUrl}?${params.toString()}`;
	});

	// Handle Send Email button
	sendBtn?.addEventListener('click', (): void => {
		if (
			isSubmitting ||
			currentAdherentId <= 0 ||
			!amountInput ||
			!dateInput ||
			!statusSelect ||
			!methodSelect
		) {
			return;
		}

		isSubmitting = true;
		sendBtn.disabled = true;
		const originalText = sendBtn.innerHTML;
		sendBtn.innerHTML =
			'<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> Envoi en cours...';

		if (feedbackEl) {
			feedbackEl.style.display = 'none';
		}

		const formData = new FormData();
		formData.append('action', 'dame_send_attestation_email');
		formData.append('adherent_id', String(currentAdherentId));
		formData.append('payment_status', statusSelect.value);
		formData.append('payment_amount', amountInput.value);
		formData.append('payment_date', dateInput.value);
		formData.append('payment_method', methodSelect.value);
		formData.append('_wpnonce', dameAttestationData.nonce);

		fetch(dameAttestationData.ajaxUrl, {
			method: 'POST',
			body: formData,
		})
			.then((res) => res.json())
			.then((resp) => {
				if (feedbackEl) {
					feedbackEl.style.display = 'block';
					if (resp.success) {
						feedbackEl.className =
							'dame-modal-feedback notice-success';
						feedbackEl.textContent =
							resp.data?.message ||
							'Attestation envoyée avec succès.';
					} else {
						feedbackEl.className =
							'dame-modal-feedback notice-error';
						feedbackEl.textContent =
							resp.data?.message ||
							"Erreur lors de l'envoi de l'attestation.";
					}
				}
			})
			.catch((err: Error) => {
				if (feedbackEl) {
					feedbackEl.className = 'dame-modal-feedback notice-error';
					feedbackEl.textContent = `Erreur réseau : ${err.message}`;
					feedbackEl.style.display = 'block';
				}
			})
			.finally(() => {
				isSubmitting = false;
				sendBtn.disabled = false;
				sendBtn.innerHTML = originalText;
			});
	});
});
