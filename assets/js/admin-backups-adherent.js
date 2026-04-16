document.addEventListener('DOMContentLoaded', function() {
	const importForm = document.getElementById('dame-import-form');
	if (importForm) {
		importForm.addEventListener('submit', function(e) {
			if (!confirm(dame_backup_adherent_data.confirm_restore)) {
				e.preventDefault();
			}
		});
	}
	const importCsvForm = document.getElementById('dame-import-csv-form');
	if (importCsvForm) {
		importCsvForm.addEventListener('submit', function(e) {
			if (!confirm(dame_backup_adherent_data.confirm_import_csv)) {
				e.preventDefault();
			}
		});
	}
});