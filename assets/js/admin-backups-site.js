document.addEventListener('DOMContentLoaded', function() {
    const siteRestoreForm = document.getElementById('dame-site-restore-form');
    if (siteRestoreForm) {
        siteRestoreForm.addEventListener('submit', function(e) {
            if (!confirm(dame_backup_site_data.confirm_restore)) {
                e.preventDefault();
            }
        });
    }
});
