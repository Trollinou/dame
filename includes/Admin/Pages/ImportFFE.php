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
		add_action( 'admin_init', [ $this, 'handle_import' ] );
	}

	/**
	 * Render the page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->display_report();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import fichier FFE', 'dame' ); ?></h1>
			<p><?php esc_html_e( 'Cet outil permet de mettre à jour les données des adhérents (Licence, ELO, ID FIDE) à partir d\'un export CSV de la FFE.', 'dame' ); ?></p>

			<div class="notice notice-info">
				<p>
					<?php esc_html_e( 'Le fichier CSV doit utiliser le point-virgule (;) comme séparateur.', 'dame' ); ?><br>
					<?php esc_html_e( 'Colonnes attendues : id_ffe (index 0), nom_complet (index 1), licence_num (index 2), elo_standard (index 5), elo_rapide (index 6), elo_blitz (index 7), fide_id (index 12).', 'dame' ); ?>
				</p>
			</div>

			<div class="card" style="max-width: 600px; margin-top: 20px;">
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'dame_import_ffe_nonce_action', 'dame_import_ffe_nonce' ); ?>
					<p>
						<label for="dame_ffe_csv"><strong><?php esc_html_e( 'Sélectionnez le fichier CSV :', 'dame' ); ?></strong></label><br>
						<input type="file" id="dame_ffe_csv" name="dame_ffe_csv" accept=".csv" required style="margin-top: 10px;">
					</p>
					<?php submit_button( __( 'Lancer l\'importation', 'dame' ), 'primary', 'dame_import_ffe_submit' ); ?>
				</form>
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

		if ( ! isset( $_POST['dame_import_ffe_nonce'] ) || ! wp_verify_nonce( $_POST['dame_import_ffe_nonce'], 'dame_import_ffe_nonce_action' ) ) {
			add_settings_error( 'dame_import_ffe', 'security_check', __( 'Vérification de sécurité échouée.', 'dame' ), 'error' );
			return;
		}

		if ( empty( $_FILES['dame_ffe_csv']['tmp_name'] ) ) {
			add_settings_error( 'dame_import_ffe', 'no_file', __( 'Aucun fichier sélectionné.', 'dame' ), 'error' );
			return;
		}

		$file = $_FILES['dame_ffe_csv'];

		// Check file type
		$file_type = wp_check_filetype( $file['name'] );
		if ( 'csv' !== $file_type['ext'] && 'text/csv' !== $file['type'] ) {
			add_settings_error( 'dame_import_ffe', 'invalid_type', __( 'Le fichier doit être au format CSV.', 'dame' ), 'error' );
			return;
		}

		$handle = fopen( $file['tmp_name'], 'r' );
		if ( ! $handle ) {
			add_settings_error( 'dame_import_ffe', 'open_error', __( 'Impossible d\'ouvrir le fichier.', 'dame' ), 'error' );
			return;
		}

		// 1. PRÉPARATION DES DONNÉES (Avant la boucle)
		$active_adherents = $this->get_active_adherents();
		$members_by_license = [];
		$members_by_name    = [];
		$members_info       = []; // For final report

		foreach ( $active_adherents as $adherent ) {
			$license = get_post_meta( $adherent->ID, '_dame_license_number', true );
			$license_clean = strtoupper( str_replace( ' ', '', (string) $license ) );
			
			if ( ! empty( $license_clean ) ) {
				$members_by_license[ $license_clean ] = $adherent->ID;
			}

			$normalized_name = $this->normalize_name( $adherent->post_title );
			$members_by_name[ $normalized_name ] = $adherent->ID;

			$members_info[ $adherent->ID ] = [
				'name'    => $adherent->post_title,
				'license' => $license ?: __( 'Non renseignée', 'dame' )
			];
		}

		$updated_count = 0;
		$updated_ids   = [];

		// 2. LOGIQUE DE CORRESPONDANCE (Dans la boucle)
		// Skip header if it exists
		$first_row = fgetcsv( $handle, 0, ';', '"', '\\' );
		if ( $first_row ) {
			if ( ! is_numeric( $first_row[0] ) && ! preg_match( '/^[A-Z][0-9]{5}$/', $first_row[2] ?? '' ) ) {
				// Likely header, skip
			} else {
				$this->process_import_row( $first_row, $members_by_license, $members_by_name, $updated_ids, $updated_count );
			}
		}

		while ( ( $row = fgetcsv( $handle, 0, ';', '"', '\\' ) ) !== false ) {
			$this->process_import_row( $row, $members_by_license, $members_by_name, $updated_ids, $updated_count );
		}

		fclose( $handle );

		// 3. GESTION DES ABSENTS ET RAPPORTS
		$missing_adherents = [];
		foreach ( $members_info as $id => $info ) {
			if ( ! in_array( $id, $updated_ids, true ) ) {
				$missing_adherents[] = sprintf( '%s (%s)', $info['name'], $info['license'] );
			}
		}

		// Save results to transient
		set_transient( 'dame_ffe_import_results', [
			'updated_count'     => $updated_count,
			'missing_adherents' => $missing_adherents
		], 30 );
	}

	/**
	 * Process a single CSV row using pre-built lookup tables.
	 *
	 * @param string[]             $row                 CSV row.
	 * @param array<string, int>   $members_by_license  Lookup table by license.
	 * @param array<string, int>   $members_by_name     Lookup table by name.
	 * @param int[]                $updated_ids         Array of updated post IDs.
	 * @param int                  $updated_count       Counter for updated records.
	 */
	private function process_import_row( array $row, array $members_by_license, array $members_by_name, array &$updated_ids, int &$updated_count ): void {
		if ( count( $row ) < 3 ) {
			return;
		}

		$id_ffe       = trim( (string) ($row[0] ?? '') );
		$nom_complet  = trim( (string) ($row[1] ?? '') );
		$licence_num  = trim( (string) ($row[2] ?? '') );
		$elo_standard = trim( (string) ($row[5] ?? '0') );
		$elo_rapide   = trim( (string) ($row[6] ?? '0') );
		$elo_blitz    = trim( (string) ($row[7] ?? '0') );
		$fide_id      = trim( (string) ($row[12] ?? '') );

		$licence_clean = strtoupper( str_replace( ' ', '', $licence_num ) );
		$nom_normalized = $this->normalize_name( $nom_complet );

		$post_id = 0;

		// ÉTAPE A : Recherche par Licence
		if ( ! empty( $licence_clean ) && isset( $members_by_license[ $licence_clean ] ) ) {
			$post_id = $members_by_license[ $licence_clean ];
		} 
		// ÉTAPE B : Recherche par Nom
		elseif ( ! empty( $nom_normalized ) && isset( $members_by_name[ $nom_normalized ] ) ) {
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
			$updated_count++;
		}
	}

	/**
	 * Get all active adherents.
	 *
	 * @return \WP_Post[]
	 */
	private function get_active_adherents(): array {
		$current_season_tag_id = get_option( 'dame_current_season_tag_id' );

		$args = [
			'post_type'      => 'adherent',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		];

		if ( $current_season_tag_id ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'dame_saison_adhesion',
					'field'    => 'term_id',
					'terms'    => (int) $current_season_tag_id,
				],
			];
		}

		return get_posts( $args );
	}

	/**
	 * Normalize a name for matching.
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

		$updated_count = $results['updated_count'];
		$missing_adherents = $results['missing_adherents'];
		delete_transient( 'dame_ffe_import_results' );

		?>
		<div class="notice notice-success is-dismissible">
			<p><strong><?php printf( esc_html__( 'Importation FFE terminée : %d adhérents mis à jour.', 'dame' ), $updated_count ); ?></strong></p>
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
