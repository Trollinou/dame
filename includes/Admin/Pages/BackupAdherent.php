<?php
namespace DAME\Admin\Pages;

class BackupAdherent {

	public function init() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
	}

	public function add_menu_page() {
		add_submenu_page(
			'edit.php?post_type=adherent',
			__( 'Sauvegarde / Restauration', 'dame' ),
			__( 'Sauvegarde / Restauration', 'dame' ),
			'manage_options',
			'dame-backup-adherent',
			[ $this, 'render' ]
		);
	}

	public function render() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="dame-import-export-wrapper">
				<div class="dame-export-section" style="margin-bottom: 2em; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h2><?php esc_html_e( 'Sauvegarder ou Exporter les données', 'dame' ); ?></h2>
					<p><?php esc_html_e( "Cliquez sur l'un des boutons ci-dessous pour télécharger les données des adhérents.", 'dame' ); ?></p>

					<form method="post" action="" style="display: inline-block; margin-right: 10px;">
						<?php wp_nonce_field( 'dame_export_csv_nonce_action', 'dame_export_csv_nonce' ); ?>
						<input type="hidden" name="dame_export_csv_action" value="1">
						<?php submit_button( __( 'Télécharger un export CSV', 'dame' ), 'secondary', 'submit', false ); ?>
					</form>

					<form method="post" action="" style="display: inline-block;">
						<?php wp_nonce_field( 'dame_export_nonce_action', 'dame_export_nonce' ); ?>
						<input type="hidden" name="dame_export_action" value="1">
						<?php submit_button( __( 'Télécharger une sauvegarde complète (.json.gz)', 'dame' ), 'primary', 'submit', false ); ?>
					</form>
				</div>

				<div class="dame-import-section" style="padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-left: 4px solid #dc3232;">
					<h2><?php esc_html_e( 'Restaurer ou Importer des données', 'dame' ); ?></h2>

					<div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
						<h3><?php esc_html_e( 'Import partiel (CSV)', 'dame' ); ?></h3>
						<p><?php esc_html_e( "L'import CSV permet d'ajouter ou de mettre à jour des adhérents.", 'dame' ); ?></p>
						<form method="post" enctype="multipart/form-data" id="dame-import-csv-form" action="">
							<?php wp_nonce_field( 'dame_import_csv_nonce_action', 'dame_import_csv_nonce' ); ?>
							<p>
								<label for="dame_import_csv_file"><strong><?php esc_html_e( 'Fichier CSV :', 'dame' ); ?></strong></label><br>
								<input type="file" id="dame_import_csv_file" name="dame_import_csv_file" accept=".csv" required>
							</p>
							<?php submit_button( __( 'Importer le fichier CSV', 'dame' ), 'secondary', 'dame_import_csv_action' ); ?>
						</form>
					</div>

					<div>
						<h3><?php esc_html_e( 'Restauration complète (.json.gz)', 'dame' ); ?></h3>
						<p style="color: #dc3232;"><strong><?php esc_html_e( "Attention : la restauration d'une sauvegarde complète effacera et remplacera toutes les données actuelles (Adhérents, Pré-inscriptions, Historique des messages).", 'dame' ); ?></strong></p>
						<form method="post" enctype="multipart/form-data" id="dame-import-form" action="">
							<?php wp_nonce_field( 'dame_import_nonce_action', 'dame_import_nonce' ); ?>
							<p>
								<label for="dame_import_file"><strong><?php esc_html_e( 'Fichier de sauvegarde :', 'dame' ); ?></strong></label><br>
								<input type="file" id="dame_import_file" name="dame_import_file" accept=".gz" required>
							</p>
							<?php submit_button( __( 'Restaurer la base de données', 'dame' ), 'delete', 'dame_import' ); ?>
						</form>
					</div>
				</div>
			</div>

			<script>
				document.addEventListener('DOMContentLoaded', function() {
					const importForm = document.getElementById('dame-import-form');
					if (importForm) {
						importForm.addEventListener('submit', function(e) {
							if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Toutes les données d\\'adhérents existantes seront supprimées et remplacées. Cette action est irréversible.', 'dame' ) ); ?>")) {
								e.preventDefault();
							}
						});
					}
					const importCsvForm = document.getElementById('dame-import-csv-form');
					if (importCsvForm) {
						importCsvForm.addEventListener('submit', function(e) {
							if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir importer ce fichier CSV ?', 'dame' ) ); ?>")) {
								e.preventDefault();
							}
						});
					}
				});
			</script>
		</div>
		<?php
	}
}
