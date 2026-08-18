<?php
/**
 * FFE Import Page.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin\Pages;

use WP_Query;
use DAME\Core\Utils;

/**
 * Class ImportFFE
 */
class ImportFFE {

	/**
	 * Initialize the class.
	 */
	public function init(): void {
		add_action( 'admin_init', array( $this, 'handle_import' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( string $hook ): void {
		if ( strpos( $hook, 'dame-import-ffe' ) === false ) {
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
	}

	/**
	 * Render the page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$contact_types = get_terms(
			array(
				'taxonomy'   => 'dame_contact_type',
				'hide_empty' => false,
			)
		);

		$this->display_report();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Manuel', 'dame' ); ?></h1>
			<p><?php esc_html_e( 'Ce module rassemble l\'ensemble des outils d\'importation de fichiers CSV (adhérents, contacts, participants HelloAsso et mise à jour FFE).', 'dame' ); ?></p>

			<!-- 1. SECTION ADHÉRENTS -->
			<h2 style="margin-top: 30px;"><?php esc_html_e( 'Adhérents', 'dame' ); ?></h2>
			<div style="display:flex; gap: 20px; align-items: stretch;">
				
				<!-- MISE À JOUR FFE -->
				<div style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3><?php esc_html_e( 'Mise à jour FFE (Licences & ELO)', 'dame' ); ?></h3>
					<p style="color: #666; font-size: 13px;">
						<?php esc_html_e( 'Met à jour les licences, classements ELO et identifiants FIDE des adhérents actifs à partir d\'un export FFE.', 'dame' ); ?>
					</p>
					<form method="post" enctype="multipart/form-data">
						<?php wp_nonce_field( 'dame_import_ffe_nonce_action', 'dame_import_ffe_nonce' ); ?>
						<p>
							<label for="dame_ffe_csv"><strong><?php esc_html_e( 'Fichier CSV FFE :', 'dame' ); ?></strong></label><br>
							<input type="file" id="dame_ffe_csv" name="dame_ffe_csv" accept=".csv" required style="margin-top: 5px;">
						</p>
						<?php submit_button( __( 'Lancer la mise à jour FFE', 'dame' ), 'primary', 'dame_import_ffe_submit', false ); ?>
					</form>
				</div>

				<!-- IMPORTER ADHÉRENTS CSV -->
				<div style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3><?php esc_html_e( 'Importer des adhérents (CSV)', 'dame' ); ?></h3>
					<p style="color: #666; font-size: 13px;">
						<?php esc_html_e( 'Importe ou met à jour des fiches adhérents à partir d\'un export CSV standard DAME.', 'dame' ); ?>
					</p>
					<form method="post" enctype="multipart/form-data" id="dame-import-csv-form" action="">
						<?php wp_nonce_field( 'dame_import_csv_nonce_action', 'dame_import_csv_nonce' ); ?>
						<p>
							<label for="dame_import_csv_file"><strong><?php esc_html_e( 'Fichier CSV Adhérents :', 'dame' ); ?></strong></label><br>
							<input type="file" id="dame_import_csv_file" name="dame_import_csv_file" accept=".csv" required style="margin-top: 5px;">
						</p>
						<?php submit_button( __( 'Importer les adhérents (CSV)', 'dame' ), 'secondary', 'dame_import_csv_action', false ); ?>
					</form>
				</div>
			</div>

			<!-- 2. SECTION CONTACTS -->
			<h2 style="margin-top: 40px;"><?php esc_html_e( 'Contacts externes', 'dame' ); ?></h2>
			<div style="display:flex; gap: 20px; align-items: stretch;">
				
				<!-- IMPORTER CONTACTS STANDARD -->
				<div style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3><?php esc_html_e( 'Importer des contacts (CSV)', 'dame' ); ?></h3>
					<p style="color: #666; font-size: 13px;">
						<?php esc_html_e( 'Importe des contacts externes (clubs, partenaires, presse) et les affecte à la catégorie sélectionnée.', 'dame' ); ?>
					</p>
					<form method="post" enctype="multipart/form-data" action="">
						<?php wp_nonce_field( 'dame_import_contacts_csv_nonce_action', 'dame_import_contacts_csv_nonce' ); ?>
						<input type="hidden" name="dame_import_contacts_csv_action" value="1">
						<p>
							<label for="dame_contact_type_import"><strong><?php esc_html_e( 'Catégorie cible :', 'dame' ); ?></strong></label><br>
							<select name="contact_type" id="dame_contact_type_import" required style="width: 100%; max-width: 300px; margin-top: 5px;">
								<option value=""><?php esc_html_e( '-- Sélectionner une catégorie --', 'dame' ); ?></option>
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
							<label for="dame_import_contacts_file"><strong><?php esc_html_e( 'Fichier CSV Contacts :', 'dame' ); ?></strong></label><br>
							<input type="file" id="dame_import_contacts_file" name="dame_import_contacts_file" accept=".csv" required style="margin-top: 5px;">
						</p>
						<?php submit_button( __( 'Importer les contacts (CSV)', 'dame' ), 'secondary', 'submit', false ); ?>
					</form>
				</div>

				<!-- IMPORTER HELLOASSO -->
				<div style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3><?php esc_html_e( 'Importer des contacts HelloAsso (CSV)', 'dame' ); ?></h3>
					<p style="color: #666; font-size: 13px;">
						<?php esc_html_e( 'Importe les participants issus d\'un export HelloAsso en excluant automatiquement les adhérents du club.', 'dame' ); ?>
					</p>
					<form method="post" enctype="multipart/form-data" action="">
						<?php wp_nonce_field( 'dame_import_helloasso_csv_nonce_action', 'dame_import_helloasso_csv_nonce' ); ?>
						<input type="hidden" name="dame_import_helloasso_csv_action" value="1">
						<p>
							<label for="dame_helloasso_contact_type_import"><strong><?php esc_html_e( 'Catégorie cible :', 'dame' ); ?></strong></label><br>
							<select name="contact_type" id="dame_helloasso_contact_type_import" required style="width: 100%; max-width: 300px; margin-top: 5px;">
								<option value=""><?php esc_html_e( '-- Sélectionner une catégorie --', 'dame' ); ?></option>
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

			<!-- 3. SECTION DOUBLONS CONTACTS / ADHÉRENTS -->
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

		</div>
		<?php
	}

	/**
	 * Handle the import process.
	 */
	public function handle_import(): void {
		if ( ! isset( $_POST['dame_import_ffe_submit'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$nonce = isset( $_POST['dame_import_ffe_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_import_ffe_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dame_import_ffe_nonce_action' ) ) {
			add_settings_error( 'dame_import_ffe', 'security_check', __( 'Vérification de sécurité échouée.', 'dame' ), 'error' );
			return;
		}

		if ( empty( $_FILES['dame_ffe_csv']['tmp_name'] ) ) {
			add_settings_error( 'dame_import_ffe', 'no_file', __( 'Aucun fichier sélectionné.', 'dame' ), 'error' );
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$file = $_FILES['dame_ffe_csv'];

		// Check file type
		$file_type = wp_check_filetype( $file['name'] );
		if ( 'csv' !== $file_type['ext'] && 'text/csv' !== $file['type'] ) {
			add_settings_error( 'dame_import_ffe', 'invalid_type', __( 'Le fichier doit être au format CSV.', 'dame' ), 'error' );
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $file['tmp_name'], 'r' );
		if ( ! $handle ) {
			add_settings_error( 'dame_import_ffe', 'open_error', __( 'Impossible d\'ouvrir le fichier.', 'dame' ), 'error' );
			return;
		}

		// 1. PRÉPARATION DES DONNÉES (Avant la boucle)
		$active_adherents   = $this->get_active_adherents();
		$members_by_license = array();
		$members_by_name    = array();
		$members_info       = array(); // For final report

		foreach ( $active_adherents as $adherent ) {
			$license       = get_post_meta( $adherent->ID, '_dame_license_number', true );
			$license_clean = strtoupper( str_replace( ' ', '', (string) $license ) );

			if ( ! empty( $license_clean ) ) {
				$members_by_license[ $license_clean ] = $adherent->ID;
			}

			$normalized_name                     = $this->normalize_name( $adherent->post_title );
			$members_by_name[ $normalized_name ] = $adherent->ID;

			$members_info[ $adherent->ID ] = array(
				'name'    => $adherent->post_title,
				'license' => $license ?: __( 'Non renseignée', 'dame' ),
			);
		}

		$updated_count = 0;
		$updated_ids   = array();

		// 2. LOGIQUE DE CORRESPONDANCE (Dans la boucle)
		// Skip header if it exists
		$first_row = fgetcsv( $handle, 0, ';', '"', '\\' );
		if ( $first_row ) {
			$is_data_row = is_numeric( $first_row[0] ) || preg_match( '/^[A-Z][0-9]{5}$/', $first_row[2] ?? '' );
			if ( $is_data_row ) {
				$this->process_import_row( $first_row, $members_by_license, $members_by_name, $updated_ids, $updated_count );
			}
		}

		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( false !== ( $row = fgetcsv( $handle, 0, ';', '"', '\\' ) ) ) {
			$this->process_import_row( $row, $members_by_license, $members_by_name, $updated_ids, $updated_count );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );

		// 3. GESTION DES ABSENTS ET RAPPORTS
		$missing_adherents = array();
		foreach ( $members_info as $id => $info ) {
			if ( ! in_array( $id, $updated_ids, true ) ) {
				$missing_adherents[] = sprintf( '%s (%s)', $info['name'], $info['license'] );
			}
		}

		// Save results to transient
		set_transient(
			'dame_ffe_import_results',
			array(
				'updated_count'     => $updated_count,
				'missing_adherents' => $missing_adherents,
			),
			30
		);
	}

	/**
	 * Process a single CSV row using pre-built lookup tables.
	 *
	 * @param string[]           $row                 CSV row.
	 * @param array<string, int> $members_by_license  Lookup table by license.
	 * @param array<string, int> $members_by_name     Lookup table by name.
	 * @param int[]              $updated_ids         Array of updated post IDs.
	 * @param int                $updated_count       Counter for updated records.
	 */
	private function process_import_row( array $row, array $members_by_license, array $members_by_name, array &$updated_ids, int &$updated_count ): void {
		if ( count( $row ) < 3 ) {
			return;
		}

		$id_ffe       = trim( (string) ( $row[0] ?? '' ) );
		$nom_complet  = trim( (string) ( $row[1] ?? '' ) );
		$licence_num  = trim( (string) ( $row[2] ?? '' ) );
		$elo_standard = trim( (string) ( $row[5] ?? '0' ) );
		$elo_rapide   = trim( (string) ( $row[6] ?? '0' ) );
		$elo_blitz    = trim( (string) ( $row[7] ?? '0' ) );
		$fide_id      = trim( (string) ( $row[12] ?? '' ) );

		$licence_clean  = strtoupper( str_replace( ' ', '', $licence_num ) );
		$nom_normalized = $this->normalize_name( $nom_complet );

		$post_id = 0;

		// ÉTAPE A : Recherche par Licence
		if ( ! empty( $licence_clean ) && isset( $members_by_license[ $licence_clean ] ) ) {
			$post_id = $members_by_license[ $licence_clean ];
		} elseif ( ! empty( $nom_normalized ) && isset( $members_by_name[ $nom_normalized ] ) ) {
			// ÉTAPE B : Recherche par Nom
			$post_id = $members_by_name[ $nom_normalized ];
		}

		if ( $post_id && ! in_array( $post_id, $updated_ids, true ) ) {
			// Match found! Update data
			update_post_meta( $post_id, '_dame_license_number', $licence_num );
			update_post_meta( $post_id, '_dame_ffe_id', $id_ffe );
			update_post_meta( $post_id, '_dame_fide_id', $fide_id );
			update_post_meta( $post_id, '_dame_elo_standard', $elo_standard );
			update_post_meta( $post_id, '_dame_elo_rapide', $elo_rapide );
			update_post_meta( $post_id, '_dame_elo_blitz', $elo_blitz );

			$updated_ids[] = $post_id;
			++$updated_count;
		}
	}

	/**
	 * Get all active adherents.
	 *
	 * @return \WP_Post[]
	 */
	private function get_active_adherents(): array {
		$current_season_tag_id = get_option( 'dame_current_season_tag_id' );

		$args = array(
			'post_type'      => 'adherent',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);

		if ( $current_season_tag_id ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'dame_saison_adhesion',
					'field'    => 'term_id',
					'terms'    => (int) $current_season_tag_id,
				),
			);
		}

		return get_posts( $args );
	}

	/**
	 * Normalize a name for matching.
	 *
	 * @param string $name Name to normalize.
	 */
	private function normalize_name( string $name ): string {
		// Convert to ASCII
		$name = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $name );
		// Lowercase
		$name = strtolower( (string) $name );
		// Remove non-alphanumeric (except spaces)
		$name = preg_replace( '/[^a-z0-9 ]/', '', $name );
		// Remove extra spaces
		$name = preg_replace( '/\s+/', '', trim( (string) $name ) );

		return (string) $name;
	}

	/**
	 * Display the import report.
	 */
	private function display_report(): void {
		$results = get_transient( 'dame_ffe_import_results' );
		if ( ! $results ) {
			return;
		}

		$updated_count     = $results['updated_count'];
		$missing_adherents = $results['missing_adherents'];
		delete_transient( 'dame_ffe_import_results' );

		?>
		<div class="notice notice-success is-dismissible">
			<p><strong>
			<?php
			// translators: %d is the number of updated members.
			echo esc_html( sprintf( __( 'Importation FFE terminée : %d adhérents mis à jour.', 'dame' ), (int) $updated_count ) );
			?>
			</strong></p>
		</div>

		<?php if ( ! empty( $missing_adherents ) ) : ?>
			<div class="notice notice-warning is-dismissible">
				<p><strong><?php esc_html_e( 'Adhérents actifs non trouvés dans le fichier FFE :', 'dame' ); ?></strong></p>
				<ul style="max-height: 200px; overflow-y: auto;">
					<?php foreach ( $missing_adherents as $info ) : ?>
						<li><?php echo esc_html( $info ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<?php
	}
}
