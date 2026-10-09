/**
 * Admin Association Tab Scripts.
 * Handles WP Media Uploader for Club Logo and Stamp/Signature.
 */

/* eslint-disable no-unused-vars */
interface WpMediaAttachment {
	id: number;
	url: string;
	sizes?: {
		medium?: { url: string };
	};
}

interface WpMediaFrame {
	on: (event: string, callback: () => void) => void;
	open: () => void;
	state: () => {
		get: (prop: string) => {
			first: () => {
				toJSON: () => WpMediaAttachment;
			};
		};
	};
}

interface WpMediaGlobal {
	media?: (options: {
		title: string;
		button: { text: string };
		multiple: boolean;
		library: { type: string };
	}) => WpMediaFrame;
}
/* eslint-enable no-unused-vars */

document.addEventListener('DOMContentLoaded', (): void => {
	const wrappers = document.querySelectorAll<HTMLElement>(
		'.dame-media-uploader-wrapper'
	);

	wrappers.forEach((wrapper): void => {
		const uploadBtn = wrapper.querySelector<HTMLButtonElement>(
			'.dame-media-upload-btn'
		);
		const input =
			wrapper.querySelector<HTMLInputElement>('.dame-media-input');

		if (!uploadBtn || !input) {
			return;
		}

		const removeBtn = wrapper.querySelector<HTMLButtonElement>(
			'.dame-media-remove-btn'
		);
		const preview = wrapper.querySelector<HTMLElement>(
			'.dame-media-preview'
		);
		const previewImg = preview?.querySelector<HTMLImageElement>('img');

		uploadBtn.addEventListener('click', (e: MouseEvent): void => {
			e.preventDefault();

			const wpAny = (window as unknown as { wp?: WpMediaGlobal }).wp;

			if (!wpAny || typeof wpAny.media !== 'function') {
				return;
			}

			const title =
				uploadBtn.getAttribute('data-title') ||
				'Sélectionner une image';

			const mediaFrame = wpAny.media({
				title,
				button: {
					text: 'Utiliser cette image',
				},
				multiple: false,
				library: {
					type: 'image',
				},
			});

			mediaFrame.on('select', (): void => {
				const attachment = mediaFrame
					.state()
					.get('selection')
					.first()
					.toJSON();
				if (!attachment || !attachment.id) {
					return;
				}

				input.value = String(attachment.id);
				const imgUrl = attachment.sizes?.medium?.url || attachment.url;

				if (preview && previewImg) {
					previewImg.src = imgUrl;
					preview.style.display = 'block';
				}

				if (removeBtn) {
					removeBtn.style.display = 'inline-block';
				}
			});

			mediaFrame.open();
		});

		if (removeBtn) {
			removeBtn.addEventListener('click', (e: MouseEvent): void => {
				e.preventDefault();
				input.value = '';

				if (preview) {
					preview.style.display = 'none';
				}
				if (previewImg) {
					previewImg.src = '';
				}
				removeBtn.style.display = 'none';
			});
		}
	});

	// --- 2. Copy Siege Social address to Salle de jeu when checked ---
	const sameAddressCheckbox = document.querySelector<HTMLInputElement>(
		'#dame_assoc_same_address'
	);
	const siegeAddr1 = document.querySelector<HTMLInputElement>(
		'#dame_assoc_siege_address_1'
	);
	const siegeAddr2 = document.querySelector<HTMLInputElement>(
		'#dame_assoc_siege_address_2'
	);
	const siegeZip = document.querySelector<HTMLInputElement>(
		'#dame_assoc_siege_postal_code'
	);
	const siegeCity = document.querySelector<HTMLInputElement>(
		'#dame_assoc_siege_city'
	);

	const salleAddr1 = document.querySelector<HTMLInputElement>(
		'#dame_assoc_address_1'
	);
	const salleAddr2 = document.querySelector<HTMLInputElement>(
		'#dame_assoc_address_2'
	);
	const salleZip = document.querySelector<HTMLInputElement>(
		'#dame_assoc_postal_code'
	);
	const salleCity =
		document.querySelector<HTMLInputElement>('#dame_assoc_city');

	function syncSameAddress(): void {
		if (!sameAddressCheckbox || !sameAddressCheckbox.checked) {
			return;
		}
		if (salleAddr1 && siegeAddr1) {
			salleAddr1.value = siegeAddr1.value;
		}
		if (salleAddr2 && siegeAddr2) {
			salleAddr2.value = siegeAddr2.value;
		}
		if (salleZip && siegeZip) {
			salleZip.value = siegeZip.value;
		}
		if (salleCity && siegeCity) {
			salleCity.value = siegeCity.value;
		}
	}

	if (sameAddressCheckbox) {
		sameAddressCheckbox.addEventListener('change', (): void => {
			if (sameAddressCheckbox.checked) {
				syncSameAddress();
			}
		});

		// Listen to changes on siege inputs if checked
		[siegeAddr1, siegeAddr2, siegeZip, siegeCity].forEach(
			(el: HTMLInputElement | null): void => {
				if (el) {
					el.addEventListener('input', (): void => {
						if (sameAddressCheckbox.checked) {
							syncSameAddress();
						}
					});
				}
			}
		);
	}
});
