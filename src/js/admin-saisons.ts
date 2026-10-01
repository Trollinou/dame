document.addEventListener('DOMContentLoaded', (): void => {
	const resetButton = document.getElementById('dame_annual_reset') as HTMLButtonElement | null;
	if (resetButton) {
		resetButton.addEventListener('click', (e: MouseEvent): void => {
			if (!confirm(dame_saisons_data.confirm_reset)) {
				e.preventDefault();
			} else {
				setTimeout((): void => {
					resetButton.disabled = true;
				}, 0);
			}
		});
	}
});
