<?php
namespace DAME\Admin\Pages;

class Backups {
	public function render() {
		wp_enqueue_script( 'dame-admin-backup-adherent', \DAME_PLUGIN_URL . 'assets/js/admin-backup-adherent.js', array(), \DAME_VERSION, true );
		wp_localize_script( 'dame-admin-backup-adherent', 'dame_backup_adherent_data', array(
			'confirm_restore' => __( "Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Toutes les données d'adhérents existantes seront supprimées et remplacées. Cette action est irréversible.", "dame" ),
			'confirm_import_csv' => __( "Êtes-vous sûr de vouloir importer ce fichier CSV ?", "dame" )
		) );
		wp_enqueue_script( 'dame-admin-backup-agenda', \DAME_PLUGIN_URL . 'assets/js/admin-backup-agenda.js', array(), \DAME_VERSION, true );
		wp_localize_script( 'dame-admin-backup-agenda', 'dame_backup_agenda_data', array(
			'confirm_restore' => __( "Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Tous les événements et catégories existants seront supprimés et remplacés. Cette action est irréversible.", "dame" )
		) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( "Sauvegardes et Import", "dame" ); ?></h1>

			<h2><?php esc_html_e( "Adhérents", "dame" ); ?></h2>
			<div class="dame-import-export-wrapper" style="display:flex; gap: 20px;">
				<div class="dame-export-section" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3><?php esc_html_e( "Sauvegarder ou Exporter", "dame" ); ?></h3>
					<form method="post" action="" style="display: inline-block; margin-right: 10px; margin-bottom: 10px;">
						<?php wp_nonce_field( 'dame_export_csv_nonce_action', 'dame_export_csv_nonce' ); ?>
						<input type="hidden" name="dame_export_csv_action" value="1">
						<?php submit_button( __( "Télécharger un export CSV", "dame" ), 'secondary', 'submit', false ); ?>
					</form>

					<form method="post" action="" style="display: inline-block; margin-bottom: 10px;">
						<?php wp_nonce_field( 'dame_export_nonce_action', 'dame_export_nonce' ); ?>
						<input type="hidden" name="dame_export_action" value="1">
						<?php submit_button( __( "Télécharger une sauvegarde complète (.json.gz)", "dame" ), 'primary', 'submit', false ); ?>
					</form>
				</div>

				<div class="dame-import-section" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-left: 4px solid #dc3232;">
					<h3><?php esc_html_e( "Restaurer ou Importer", "dame" ); ?></h3>

					<div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
						<h4><?php esc_html_e( "Import partiel (CSV)", "dame" ); ?></h4>
						<form method="post" enctype="multipart/form-data" id="dame-import-csv-form" action="">
							<?php wp_nonce_field( 'dame_import_csv_nonce_action', 'dame_import_csv_nonce' ); ?>
							<p>
								<label for="dame_import_csv_file"><strong><?php esc_html_e( "Fichier CSV :", "dame" ); ?></strong></label><br>
								<input type="file" id="dame_import_csv_file" name="dame_import_csv_file" accept=".csv" required>
							</p>
							<?php submit_button( __( "Importer le fichier CSV", "dame" ), 'secondary', 'dame_import_csv_action' ); ?>
						</form>
					</div>

					<div>
						<h4><?php esc_html_e( "Restauration complète (.json.gz)", "dame" ); ?></h4>
						<form method="post" enctype="multipart/form-data" id="dame-import-form" action="">
							<?php wp_nonce_field( 'dame_import_nonce_action', 'dame_import_nonce' ); ?>
							<p>
								<label for="dame_import_file"><strong><?php esc_html_e( "Fichier de sauvegarde :", "dame" ); ?></strong></label><br>
								<input type="file" id="dame_import_file" name="dame_import_file" accept=".gz" required>
							</p>
							<?php submit_button( __( "Restaurer la base de données", "dame" ), 'delete', 'dame_import' ); ?>
						</form>
					</div>
				</div>
			</div>

			<h2 style="margin-top: 40px;"><?php esc_html_e( "Agenda", "dame" ); ?></h2>
			<div class="dame-backup-restore-wrapper" style="display:flex; gap: 20px;">
				<div class="dame-backup-section" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3><?php esc_html_e( "Sauvegarder les données de l'agenda", "dame" ); ?></h3>
					<form method="post" action="">
						<?php wp_nonce_field( 'dame_agenda_backup_nonce_action', 'dame_agenda_backup_nonce' ); ?>
						<input type="hidden" name="dame_agenda_backup_action" value="1">
						<?php submit_button( __( "Télécharger la sauvegarde de l'agenda (.json.gz)", "dame" ), 'primary', 'submit', false ); ?>
					</form>
				</div>

				<div class="dame-restore-section" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-left: 4px solid #dc3232;">
					<h3><?php esc_html_e( "Restaurer les données de l'agenda", "dame" ); ?></h3>
					<form method="post" enctype="multipart/form-data" id="dame-agenda-restore-form" action="">
						<?php wp_nonce_field( 'dame_agenda_restore_nonce_action', 'dame_agenda_restore_nonce' ); ?>
						<p>
							<label for="dame_agenda_restore_file"><strong><?php esc_html_e( "Fichier de sauvegarde (.json.gz) :", "dame" ); ?></strong></label><br>
							<input type="file" id="dame_agenda_restore_file" name="dame_agenda_restore_file" accept=".gz" required>
						</p>
						<?php submit_button( __( "Restaurer la base de données de l'agenda", "dame" ), 'delete', 'dame_agenda_restore_action' ); ?>
					</form>
				</div>
			</div>

		</div>
		<?php
	}
}
