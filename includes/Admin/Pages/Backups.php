<?php
/**
 * Backups and Import/Export Page.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin\Pages;

use DAME\Services\Data_Provider;

/**
 * Class Backups
 */
class Backups {

	/**
	 * Initialize the class.
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( string $hook ): void {
		if ( strpos( $hook, 'dame-backups' ) === false ) {
			return;
		}

		wp_enqueue_script( 'dame-admin-backups-adherent', \DAME_PLUGIN_URL . 'assets/js/admin-backups-adherent.js', array(), \DAME_VERSION, true );
		wp_localize_script(
			'dame-admin-backups-adherent',
			'dame_backup_adherent_data',
			array(
				'confirm_restore'    => __( "Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Toutes les données d'adhérents existantes seront supprimées et remplacées. Cette action est irréversible.", 'dame' ),
				'confirm_import_csv' => __( 'Êtes-vous sûr de vouloir importer ce fichier CSV ?', 'dame' ),
			)
		);
		wp_enqueue_script( 'dame-admin-backups-agenda', \DAME_PLUGIN_URL . 'assets/js/admin-backups-agenda.js', array(), \DAME_VERSION, true );
		wp_localize_script(
			'dame-admin-backups-agenda',
			'dame_backup_agenda_data',
			array(
				'confirm_restore' => __( 'Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Tous les événements et catégories existants seront supprimés et remplacées. Cette action est irréversible.', 'dame' ),
			)
		);

		wp_enqueue_script( 'dame-admin-backups-site', \DAME_PLUGIN_URL . 'assets/js/admin-backups-site.js', array(), \DAME_VERSION, true );
		wp_localize_script(
			'dame-admin-backups-site',
			'dame_backup_site_data',
			array(
				'confirm_restore' => __( 'Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Tous les articles, pages et menus existants seront supprimés et remplacés. Cette action est irréversible.', 'dame' ),
			)
		);
	}

	/**
	 * Render the page.
	 */
	public function render(): void {
		$contact_types = get_terms(
			array(
				'taxonomy'   => 'dame_contact_type',
				'hide_empty' => false,
			)
		);

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sauvegardes et Import', 'dame' ); ?></h1>

			<h2><?php esc_html_e( 'Adhérents, Contacts et Préinscription', 'dame' ); ?></h2>
			<div class="dame-import-export-wrapper" style="display:flex; gap: 20px; align-items: stretch;">
				
				<!-- COLONNE GAUCHE : SAUVEGARDE ET EXPORT -->
				<div class="dame-export-section" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3><?php esc_html_e( 'Sauvegarde et Export', 'dame' ); ?></h3>
					
					<div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
						<h4><?php esc_html_e( 'Sauvegarde complète (.json.gz)', 'dame' ); ?></h4>
						<form method="post" action="">
							<?php wp_nonce_field( 'dame_export_nonce_action', 'dame_export_nonce' ); ?>
							<input type="hidden" name="dame_export_action" value="1">
							<?php submit_button( __( 'Télécharger une sauvegarde complète (.json.gz)', 'dame' ), 'primary', 'submit', false ); ?>
						</form>
					</div>

					<div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
						<h4><?php esc_html_e( 'Exporter les adhérents (CSV)', 'dame' ); ?></h4>
						<form method="post" action="">
							<?php wp_nonce_field( 'dame_export_csv_nonce_action', 'dame_export_csv_nonce' ); ?>
							<input type="hidden" name="dame_export_csv_action" value="1">
							<?php submit_button( __( 'Exporter les adhérents (CSV)', 'dame' ), 'secondary', 'submit', false ); ?>
						</form>
					</div>

					<div>
						<h4><?php esc_html_e( 'Exporter les contacts', 'dame' ); ?></h4>
						<form method="post" action="">
							<?php wp_nonce_field( 'dame_export_contacts_csv_nonce_action', 'dame_export_contacts_csv_nonce' ); ?>
							<input type="hidden" name="dame_export_contacts_csv_action" value="1">
							<p>
								<label for="dame_contact_type_export"><strong><?php esc_html_e( 'Type de contact :', 'dame' ); ?></strong></label><br>
								<select name="contact_type" id="dame_contact_type_export" required style="width: 100%; max-width: 300px; margin-top: 5px;">
									<option value=""><?php esc_html_e( '-- Sélectionner un type --', 'dame' ); ?></option>
									<?php
									if ( ! is_wp_error( $contact_types ) ) :
										foreach ( $contact_types as $type ) :
											?>
										<option value="<?php echo esc_attr( $type->slug ); ?>"><?php echo esc_html( $type->name ); ?></option>
																			<?php
									endforeach;
endif;
									?>
								</select>
							</p>
							<?php submit_button( __( 'Exporter les contacts (CSV)', 'dame' ), 'secondary', 'submit', false ); ?>
						</form>
					</div>
				</div>

				<!-- COLONNE DROITE : RESTAURATION ET IMPORT -->
				<div class="dame-import-section" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-left: 4px solid #dc3232;">
					<h3><?php esc_html_e( 'Restauration et Import', 'dame' ); ?></h3>

					<div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
						<h4><?php esc_html_e( 'Restauration complète (.json.gz)', 'dame' ); ?></h4>
						<form method="post" enctype="multipart/form-data" id="dame-import-form" action="">
							<?php wp_nonce_field( 'dame_import_nonce_action', 'dame_import_nonce' ); ?>
							<p>
								<label for="dame_import_file"><strong><?php esc_html_e( 'Fichier de sauvegarde :', 'dame' ); ?></strong></label><br>
								<input type="file" id="dame_import_file" name="dame_import_file" accept=".gz" required style="margin-top: 5px;">
							</p>
							<?php submit_button( __( 'Restaurer la base de données', 'dame' ), 'delete', 'dame_import', false ); ?>
						</form>
					</div>

					<div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
						<h4><?php esc_html_e( 'Importer des adhérents (CSV)', 'dame' ); ?></h4>
						<form method="post" enctype="multipart/form-data" id="dame-import-csv-form" action="">
							<?php wp_nonce_field( 'dame_import_csv_nonce_action', 'dame_import_csv_nonce' ); ?>
							<p>
								<label for="dame_import_csv_file"><strong><?php esc_html_e( 'Fichier CSV :', 'dame' ); ?></strong></label><br>
								<input type="file" id="dame_import_csv_file" name="dame_import_csv_file" accept=".csv" required style="margin-top: 5px;">
							</p>
							<?php submit_button( __( 'Importer les adhérents (CSV)', 'dame' ), 'secondary', 'dame_import_csv_action', false ); ?>
						</form>
					</div>

					<div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
						<h4><?php esc_html_e( 'Importer des contacts', 'dame' ); ?></h4>
						<form method="post" enctype="multipart/form-data" action="">
							<?php wp_nonce_field( 'dame_import_contacts_csv_nonce_action', 'dame_import_contacts_csv_nonce' ); ?>
							<input type="hidden" name="dame_import_contacts_csv_action" value="1">
							<p>
								<label for="dame_contact_type_import"><strong><?php esc_html_e( 'Type cible :', 'dame' ); ?></strong></label><br>
								<select name="contact_type" id="dame_contact_type_import" required style="width: 100%; max-width: 300px; margin-top: 5px;">
									<option value=""><?php esc_html_e( '-- Sélectionner un type --', 'dame' ); ?></option>
									<?php
									if ( ! is_wp_error( $contact_types ) ) :
										foreach ( $contact_types as $type ) :
											?>
										<option value="<?php echo esc_attr( $type->slug ); ?>"><?php echo esc_html( $type->name ); ?></option>
																			<?php
									endforeach;
endif;
									?>
								</select>
							</p>
							<p>
								<label for="dame_import_contacts_file"><strong><?php esc_html_e( 'Fichier CSV :', 'dame' ); ?></strong></label><br>
								<input type="file" id="dame_import_contacts_file" name="dame_import_contacts_file" accept=".csv" required style="margin-top: 5px;">
							</p>
							<?php submit_button( __( 'Importer les contacts (CSV)', 'dame' ), 'secondary', 'submit', false ); ?>
						</form>
					</div>

					<div>
						<h4><?php esc_html_e( 'Importer des contacts HelloAsso (CSV)', 'dame' ); ?></h4>
						<form method="post" enctype="multipart/form-data" action="">
							<?php wp_nonce_field( 'dame_import_helloasso_csv_nonce_action', 'dame_import_helloasso_csv_nonce' ); ?>
							<input type="hidden" name="dame_import_helloasso_csv_action" value="1">
							<p>
								<label for="dame_helloasso_contact_type_import"><strong><?php esc_html_e( 'Type cible :', 'dame' ); ?></strong></label><br>
								<select name="contact_type" id="dame_helloasso_contact_type_import" required style="width: 100%; max-width: 300px; margin-top: 5px;">
									<option value=""><?php esc_html_e( '-- Sélectionner un type --', 'dame' ); ?></option>
									<?php
									if ( ! is_wp_error( $contact_types ) ) :
										foreach ( $contact_types as $type ) :
											?>
										<option value="<?php echo esc_attr( $type->slug ); ?>"><?php echo esc_html( $type->name ); ?></option>
																			<?php
									endforeach;
endif;
									?>
								</select>
							</p>
							<p>
								<label for="dame_import_helloasso_file"><strong><?php esc_html_e( 'Fichier CSV HelloAsso :', 'dame' ); ?></strong></label><br>
								<input type="file" id="dame_import_helloasso_file" name="dame_import_helloasso_file" accept=".csv" required style="margin-top: 5px;">
							</p>
							<?php submit_button( __( 'Importer les contacts HelloAsso (CSV)', 'dame' ), 'secondary', 'submit', false ); ?>
						</form>
					</div>
				</div>
			</div>

			<!-- SECTION DOUBLONS CONTACTS / ADHÉRENTS -->
			<h2 style="margin-top: 40px;"><?php esc_html_e( 'Détection et Nettoyage des doublons Contacts / Adhérents', 'dame' ); ?></h2>
			<div class="dame-duplicates-section" style="padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<p style="margin-top: 0;">
					<?php esc_html_e( 'Cet outil analyse l\'ensemble des contacts externes et détecte ceux qui correspondent à un adhérent enregistré (par Email identique ou par Nom & Prénom). Vous pouvez sélectionner les fiches contacts à supprimer pour nettoyer la base.', 'dame' ); ?>
				</p>
				<?php
				$duplicates = \DAME\Services\Backup::get_contact_adherent_duplicates();
				if ( empty( $duplicates ) ) :
					?>
					<div class="notice notice-success inline" style="margin: 10px 0; padding: 10px 15px;">
						<p style="margin: 0;"><?php esc_html_e( '✓ Aucun contact en doublon avec les adhérents n\'a été détecté.', 'dame' ); ?></p>
					</div>
				<?php else : ?>
					<form method="post" action="">
						<?php wp_nonce_field( 'dame_delete_contact_duplicates_nonce_action', 'dame_delete_contact_duplicates_nonce' ); ?>
						<input type="hidden" name="dame_delete_contact_duplicates_action" value="1">
						
						<p style="color: #d63638; font-weight: 600;">
							<?php
							printf(
								/* translators: %d: Number of detected duplicates */
								esc_html__( '%d contact(s) suspecté(s) d\'être des adhérents ont été trouvés :', 'dame' ),
								count( $duplicates )
							);
							?>
						</p>

						<table class="wp-list-table widefat fixed striped" style="margin-bottom: 15px;">
							<thead>
								<tr>
									<td id="cb" class="manage-column column-cb check-column" style="width: 35px;">
										<input id="cb-select-all-duplicates" type="checkbox" onclick="document.querySelectorAll('.dame-duplicate-cb').forEach(cb => cb.checked = this.checked);">
									</td>
									<th scope="col" style="font-weight: 600;"><?php esc_html_e( 'Contact externe', 'dame' ); ?></th>
									<th scope="col" style="font-weight: 600;"><?php esc_html_e( 'Email contact', 'dame' ); ?></th>
									<th scope="col" style="font-weight: 600;"><?php esc_html_e( 'Catégorie(s)', 'dame' ); ?></th>
									<th scope="col" style="font-weight: 600;"><?php esc_html_e( 'Adhérent correspondant & Motif', 'dame' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $duplicates as $dup ) : ?>
									<tr>
										<th scope="row" class="check-column">
											<input type="checkbox" class="dame-duplicate-cb" name="selected_contacts[]" value="<?php echo esc_attr( (string) $dup['contact_id'] ); ?>">
										</th>
										<td>
											<strong><?php echo esc_html( $dup['contact_name'] ); ?></strong>
											<?php if ( ! empty( $dup['contact_org'] ) ) : ?>
												<br><span style="color: #666; font-size: 12px;"><?php echo esc_html( $dup['contact_org'] ); ?></span>
											<?php endif; ?>
										</td>
										<td>
											<?php echo ! empty( $dup['contact_email'] ) ? esc_html( $dup['contact_email'] ) : '<em>' . esc_html__( 'Aucun', 'dame' ) . '</em>'; ?>
										</td>
										<td>
											<?php
											if ( ! empty( $dup['categories'] ) ) {
												echo esc_html( implode( ', ', $dup['categories'] ) );
											} else {
												echo '<em>' . esc_html__( 'Aucune', 'dame' ) . '</em>';
											}
											?>
										</td>
										<td>
											<a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $dup['adherent_id'] . '&action=edit' ) ); ?>" target="_blank" style="font-weight: 500; text-decoration: underline;">
												<?php echo esc_html( $dup['adherent_name'] ); ?> (#<?php echo esc_html( (string) $dup['adherent_id'] ); ?>)
											</a>
											<br><span style="color: #666; font-size: 12px;"><?php echo esc_html( $dup['match_reason'] ); ?></span>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<?php
						submit_button(
							__( 'Supprimer les contacts sélectionnés', 'dame' ),
							'delete',
							'submit',
							false,
							array(
								'onclick' => 'return confirm("' . esc_js( __( 'Êtes-vous sûr de vouloir supprimer définitivement les contacts sélectionnés ? Cette action est irréversible.', 'dame' ) ) . '");',
							)
						);
						?>
					</form>
				<?php endif; ?>
			</div>

			<h2 style="margin-top: 40px;"><?php esc_html_e( 'Agenda', 'dame' ); ?></h2>
			<div class="dame-backup-restore-wrapper" style="display:flex; gap: 20px;">
				<div class="dame-backup-section" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3><?php esc_html_e( "Sauvegarder les données de l'agenda", 'dame' ); ?></h3>
					<form method="post" action="">
						<?php wp_nonce_field( 'dame_agenda_backup_nonce_action', 'dame_agenda_backup_nonce' ); ?>
						<input type="hidden" name="dame_agenda_backup_action" value="1">
						<?php submit_button( __( "Télécharger la sauvegarde de l'agenda (.json.gz)", 'dame' ), 'primary', 'submit', false ); ?>
					</form>
				</div>

				<div class="dame-restore-section" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-left: 4px solid #dc3232;">
					<h3><?php esc_html_e( "Restaurer les données de l'agenda", 'dame' ); ?></h3>
					<form method="post" enctype="multipart/form-data" id="dame-agenda-restore-form" action="">
						<?php wp_nonce_field( 'dame_agenda_restore_nonce_action', 'dame_agenda_restore_nonce' ); ?>
						<p>
							<label for="dame_agenda_restore_file"><strong><?php esc_html_e( 'Fichier de sauvegarde (.json.gz) :', 'dame' ); ?></strong></label><br>
							<input type="file" id="dame_agenda_restore_file" name="dame_agenda_restore_file" accept=".gz" required style="margin-top: 5px;">
						</p>
						<?php submit_button( __( "Restaurer la base de données de l'agenda", 'dame' ), 'delete', 'dame_agenda_restore_action', false ); ?>
					</form>
				</div>
			</div>

			<h2 style="margin-top: 40px;"><?php esc_html_e( 'Contenu du site', 'dame' ); ?></h2>
			<div class="dame-backup-restore-wrapper" style="display:flex; gap: 20px;">
				<div class="dame-backup-section" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3><?php esc_html_e( 'Sauvegarder les articles, pages et menus', 'dame' ); ?></h3>
					<form method="post" action="">
						<?php wp_nonce_field( 'dame_site_backup_nonce_action', 'dame_site_backup_nonce' ); ?>
						<input type="hidden" name="dame_site_backup_action" value="1">
						<?php submit_button( __( 'Télécharger la sauvegarde du site (.json.gz)', 'dame' ), 'primary', 'submit', false ); ?>
					</form>
				</div>

				<div class="dame-restore-section" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-left: 4px solid #dc3232;">
					<h3><?php esc_html_e( 'Restaurer les articles, pages et menus', 'dame' ); ?></h3>
					<form method="post" enctype="multipart/form-data" id="dame-site-restore-form" action="">
						<?php wp_nonce_field( 'dame_site_restore_nonce_action', 'dame_site_restore_nonce' ); ?>
						<p>
							<label for="dame_site_restore_file"><strong><?php esc_html_e( 'Fichier de sauvegarde (.json.gz) :', 'dame' ); ?></strong></label><br>
							<input type="file" id="dame_site_restore_file" name="dame_site_restore_file" accept=".gz" required style="margin-top: 5px;">
						</p>
						<?php submit_button( __( 'Restaurer le contenu du site', 'dame' ), 'delete', 'dame_site_restore_action', false ); ?>
					</form>
				</div>
			</div>

		</div>
		<?php
	}
}
