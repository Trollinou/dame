document.addEventListener('DOMContentLoaded', (): void => {
	const restoreForm = document.getElementById(
		'dame-agenda-restore-form'
	) as HTMLFormElement | null;
	if (restoreForm) {
		restoreForm.addEventListener('submit', (e: Event): void => {
			if (!confirm(dame_backup_agenda_data.confirm_restore)) {
				e.preventDefault();
			}
		});
	}
});
