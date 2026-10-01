document.addEventListener('DOMContentLoaded', (): void => {
	// Handler for the "Open in GPS" button
	document.addEventListener('click', (e: MouseEvent): void => {
		const target = e.target as HTMLElement | null;
		const button = target?.closest<HTMLButtonElement>('#dame-open-gps');
		if (!button) {
			return;
		}

		const lat = button.dataset.lat;
		const lng = button.dataset.lng;

		if (!lat || !lng) {
			return;
		}

		// Check for iOS
		const isIOS =
			/iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

		let url: string;
		if (isIOS) {
			// Apple Maps URL scheme
			url = `https://maps.apple.com/?q=${lat},${lng}`;
		} else {
			// Google Maps URL scheme for all other platforms
			url = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
		}

		window.open(url, '_blank');
	});
});
