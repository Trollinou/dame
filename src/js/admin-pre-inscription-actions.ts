document.addEventListener('DOMContentLoaded', (): void => {
	const deleteButton = document.querySelector<HTMLElement>(
		'.dame-delete-button'
	);
	if (deleteButton) {
		deleteButton.addEventListener('click', (e: MouseEvent): void => {
			if (!confirm(dame_pre_inscription_actions_data.confirm_delete)) {
				e.preventDefault();
			}
		});
	}
});
