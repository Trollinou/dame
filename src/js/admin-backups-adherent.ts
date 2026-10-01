document.addEventListener('DOMContentLoaded', (): void => {
	const importForm = document.getElementById('dame-import-form') as HTMLFormElement | null;
	if (importForm) {
		importForm.addEventListener('submit', (e: Event): void => {
			if (!confirm(dame_backup_adherent_data.confirm_restore)) {
				e.preventDefault();
			}
		});
	}
	const importCsvForm = document.getElementById('dame-import-csv-form') as HTMLFormElement | null;
	if (importCsvForm) {
		importCsvForm.addEventListener('submit', (e: Event): void => {
			if (!confirm(dame_backup_adherent_data.confirm_import_csv)) {
				e.preventDefault();
			}
		});
	}
});
