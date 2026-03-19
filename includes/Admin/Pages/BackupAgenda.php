<?php
namespace DAME\Admin\Pages;

class BackupAgenda {

	public function init() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
	}

	public function add_menu_page() {
		add_submenu_page(
			'edit.php?post_type=dame_agenda',
			__( 'Sauvegarde / Restauration', 'dame' ),
			__( 'Sauvegarde / Restauration', 'dame' ),
			'manage_options',
			'dame-backup-agenda',
			[ $this, 'render' ]
		);
	}

	public function render() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="dame-backup-restore-wrapper">
				<div class="dame-backup-section" style="margin-bottom: 2em; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h2><?php esc_html_e( "Sauvegarder les données de l'agenda", 'dame' ); ?></h2>
					<p><?php esc_html_e( "Cliquez sur le bouton ci-dessous pour télécharger une sauvegarde de tous les événements et catégories de l'agenda.", 'dame' ); ?></p>
					<form method="post" action="">
						<?php wp_nonce_field( 'dame_agenda_backup_nonce_action', 'dame_agenda_backup_nonce' ); ?>
						<input type="hidden" name="dame_agenda_backup_action" value="1">
						<?php submit_button( __( 'Télécharger la sauvegarde de l\'agenda (.json.gz)', 'dame' ), 'primary', 'submit', false ); ?>
					</form>
				</div>

				<div class="dame-restore-section" style="padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-left: 4px solid #dc3232;">
					<h2><?php esc_html_e( "Restaurer les données de l'agenda", 'dame' ); ?></h2>
					<p style="color: #dc3232;"><strong><?php esc_html_e( "Attention : la restauration d'une sauvegarde effacera et remplacera tous les événements actuels de l'agenda.", 'dame' ); ?></strong></p>
					<form method="post" enctype="multipart/form-data" id="dame-agenda-restore-form" action="">
						<?php wp_nonce_field( 'dame_agenda_restore_nonce_action', 'dame_agenda_restore_nonce' ); ?>
						<p>
							<label for="dame_agenda_restore_file"><strong><?php esc_html_e( 'Fichier de sauvegarde (.json.gz) :', 'dame' ); ?></strong></label><br>
							<input type="file" id="dame_agenda_restore_file" name="dame_agenda_restore_file" accept=".gz" required>
						</p>
						<?php submit_button( __( 'Restaurer la base de données de l\'agenda', 'dame' ), 'delete', 'dame_agenda_restore_action' ); ?>
					</form>
				</div>
			</div>

			<script>
				document.addEventListener('DOMContentLoaded', function() {
					const restoreForm = document.getElementById('dame-agenda-restore-form');
					if (restoreForm) {
						restoreForm.addEventListener('submit', function(e) {
							if (!confirm("<?php echo esc_js( __( "Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Tous les événements et catégories existants seront supprimés et remplacés. Cette action est irréversible.", 'dame' ) ); ?>")) {
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
