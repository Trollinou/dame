document.addEventListener('DOMContentLoaded', (): void => {
	const revertButton = document.querySelector<HTMLButtonElement>(
		'button[name="dame_revert_to_pre_inscription"]'
	);
	if (revertButton) {
		revertButton.addEventListener('click', (e: MouseEvent): void => {
			if (!confirm(dame_adherent_actions_data.confirm_revert)) {
				e.preventDefault();
			}
		});
	}
});
