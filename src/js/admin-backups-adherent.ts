document.addEventListener('DOMContentLoaded', (): void => {
	const importForm = document.getElementById(
		'dame-import-form'
	) as HTMLFormElement | null;
	if (importForm) {
		importForm.addEventListener('submit', (e: Event): void => {
			if (!confirm(dame_backup_adherent_data.confirm_restore)) {
				e.preventDefault();
			}
		});
	}
	const importCsvForm = document.getElementById(
		'dame-import-csv-form'
	) as HTMLFormElement | null;
	if (importCsvForm) {
		importCsvForm.addEventListener('submit', (e: Event): void => {
			if (!confirm(dame_backup_adherent_data.confirm_import_csv)) {
				e.preventDefault();
			}
		});
	}

	const selectAllDuplicates = document.getElementById(
		'cb-select-all-duplicates'
	) as HTMLInputElement | null;
	if (selectAllDuplicates) {
		selectAllDuplicates.addEventListener('change', (): void => {
			const checkboxes =
				document.querySelectorAll<HTMLInputElement>(
					'.dame-duplicate-cb'
				);
			checkboxes.forEach((cb: HTMLInputElement): void => {
				cb.checked = selectAllDuplicates.checked;
			});
		});
	}
});
