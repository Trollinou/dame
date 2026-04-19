<?php

namespace DAME\Services;

use WP_Query;
use DateTime;
use WP_Error;
use DAME\Core\Utils;

/**
 * Service handling Backups (Export/Import) for Adherents and Agenda.
 */
class Backup {

	/**
	 * Initialize the service.
	 */
	public function init(): void {
		// Handle manual export/import actions (triggered via admin POST).
		add_action( 'admin_init', [ $this, 'handle_manual_actions' ] );
		add_action( 'admin_notices', [ $this, 'display_import_export_notices' ] );
	}

	/**
	 * Adds an admin notice to be displayed on the next page load.
	 */
	private function add_admin_notice( $message, $type = 'success' ): void {
		set_transient( 'dame_import_export_notice', array( 'message' => $message, 'type' => $type ), 30 );
	}

	/**
	 * Displays the admin notice if one is set.
	 */
	public function display_import_export_notices(): void {
		if ( get_transient( 'dame_import_export_notice' ) ) {
			$notice = get_transient( 'dame_import_export_notice' );
			$message = $notice['message'];
			$type = $notice['type'];
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
			delete_transient( 'dame_import_export_notice' );
		}
	}

	/**
	 * Dispatch manual actions based on POST requests.
	 */
	public function handle_manual_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// 1. Export CSV Adherents
		if ( isset( $_POST['dame_export_csv_action'], $_POST['dame_export_csv_nonce'] ) && wp_verify_nonce( $_POST['dame_export_csv_nonce'], 'dame_export_csv_nonce_action' ) ) {
			$this->export_csv_adherents();
		}

		// 2. Import CSV Adherents
		if ( isset( $_POST['dame_import_csv_action'], $_POST['dame_import_csv_nonce'] ) && wp_verify_nonce( $_POST['dame_import_csv_nonce'], 'dame_import_csv_nonce_action' ) ) {
			$this->import_csv_adherents();
		}

		// 3. Export JSON Adherents (Backup)
		if ( isset( $_POST['dame_export_action'], $_POST['dame_export_nonce'] ) && wp_verify_nonce( $_POST['dame_export_nonce'], 'dame_export_nonce_action' ) ) {
			$this->export_json_adherents();
		}

		// 4. Import JSON Adherents (Restore)
		if ( isset( $_POST['dame_import'], $_POST['dame_import_nonce'] ) && wp_verify_nonce( $_POST['dame_import_nonce'], 'dame_import_nonce_action' ) ) {
			$this->import_json_adherents();
		}

		// 5. Export JSON Agenda
		if ( isset( $_POST['dame_agenda_backup_action'], $_POST['dame_agenda_backup_nonce'] ) && wp_verify_nonce( $_POST['dame_agenda_backup_nonce'], 'dame_agenda_backup_nonce_action' ) ) {
			$this->export_json_agenda();
		}

		// 6. Import JSON Agenda
		if ( isset( $_POST['dame_agenda_restore_action'], $_POST['dame_agenda_restore_nonce'] ) && wp_verify_nonce( $_POST['dame_agenda_restore_nonce'], 'dame_agenda_restore_nonce_action' ) ) {
			$this->import_json_agenda();
		}

		// 7. Export CSV Contacts
		if ( isset( $_POST['dame_export_contacts_csv_action'], $_POST['dame_export_contacts_csv_nonce'] ) && wp_verify_nonce( $_POST['dame_export_contacts_csv_nonce'], 'dame_export_contacts_csv_nonce_action' ) ) {
			$this->export_csv_contacts();
		}

		// 8. Import CSV Contacts
		if ( isset( $_POST['dame_import_contacts_csv_action'], $_POST['dame_import_contacts_csv_nonce'] ) && wp_verify_nonce( $_POST['dame_import_contacts_csv_nonce'], 'dame_import_contacts_csv_nonce_action' ) ) {
			$this->import_csv_contacts();
		}
	}

	/* -------------------------------------------------------------------------
	 * CONTACTS - CSV
	 * ------------------------------------------------------------------------- */

	/**
	 * Handle contacts export to CSV.
	 */
	private function export_csv_contacts(): void {
		$type_slug = isset( $_POST['contact_type'] ) ? sanitize_key( $_POST['contact_type'] ) : '';
		if ( empty( $type_slug ) ) {
			return;
		}

		$filename = 'dame-export-contacts-' . $type_slug . '-' . wp_date( 'Y-m-d' ) . '.csv';

		ob_clean();
		header( 'Content-Type: text/csv; charset=windows-1252' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );

		// Headers
		$headers = [
			'Organisation', 'Nom', 'Prenom', 'Role', 'Email', 'Adresse', 'Complement', 'Code Postal', 'Ville', 'Type'
		];

		// Convert headers to CP1252
		$headers_encoded = array_map( fn( $h ) => mb_convert_encoding( (string) $h, 'Windows-1252', 'UTF-8' ), $headers );
		fputcsv( $output, $headers_encoded, ';' );

		$query = new WP_Query( [
			'post_type'      => 'dame_contact',
			'posts_per_page' => -1,
			'tax_query'      => [
				[
					'taxonomy' => 'dame_contact_type',
					'field'    => 'slug',
					'terms'    => $type_slug,
				],
			],
		] );

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$row = [
					get_post_meta( $post->ID, '_dame_contact_organization', true ),
					get_post_meta( $post->ID, '_dame_contact_last_name', true ),
					get_post_meta( $post->ID, '_dame_contact_first_name', true ),
					get_post_meta( $post->ID, '_dame_contact_role', true ),
					get_post_meta( $post->ID, '_dame_contact_email', true ),
					get_post_meta( $post->ID, '_dame_contact_address_1', true ),
					get_post_meta( $post->ID, '_dame_contact_address_2', true ),
					get_post_meta( $post->ID, '_dame_contact_postcode', true ),
					get_post_meta( $post->ID, '_dame_contact_city', true ),
					$type_slug,
				];

				// Convert row to CP1252
				$row_encoded = array_map( fn( $val ) => mb_convert_encoding( (string) $val, 'Windows-1252', 'UTF-8' ), $row );
				fputcsv( $output, $row_encoded, ';' );
			}
		}

		fclose( $output );
		exit;
	}

	/**
	 * Handle contacts import from CSV.
	 */
	private function import_csv_contacts(): void {
		$type_slug = isset( $_POST['contact_type'] ) ? sanitize_key( $_POST['contact_type'] ) : '';
		if ( empty( $type_slug ) || ! isset( $_FILES['dame_import_contacts_file'] ) || $_FILES['dame_import_contacts_file']['error'] !== UPLOAD_ERR_OK ) {
			$this->add_admin_notice( __( 'Données invalides pour l\'import.', 'dame' ), 'error' );
			return;
		}

		$handle = fopen( $_FILES['dame_import_contacts_file']['tmp_name'], 'r' );
		if ( ! $handle ) {
			$this->add_admin_notice( __( 'Impossible d\'ouvrir le fichier CSV.', 'dame' ), 'error' );
			return;
		}

		// Read headers and skip
		fgetcsv( $handle, 0, ';' );

		$created = 0;
		$updated = 0;
		$dept_mapping = Data_Provider::get_department_region_mapping();

		while ( ( $row = fgetcsv( $handle, 0, ';' ) ) !== false ) {
			// Convert to UTF-8
			$row = array_map( fn( $val ) => mb_convert_encoding( (string) $val, 'UTF-8', 'Windows-1252' ), $row );

			// Map columns: 0:Org, 1:Nom, 2:Prenom, 3:Email, 4:Adresse, 5:Complement, 6:CP, 7:Ville
			$org       = trim( $row[0] ?? '' );
			$last_name = trim( $row[1] ?? '' );
			$first_name = trim( $row[2] ?? '' );
			$email     = trim( $row[3] ?? '' );
			$addr1     = trim( $row[4] ?? '' );
			$addr2     = trim( $row[5] ?? '' );
			$postcode  = trim( $row[6] ?? '' );
			$city      = trim( $row[7] ?? '' );

			if ( empty( $last_name ) && empty( $first_name ) && empty( $org ) ) {
				continue;
			}

			// Deduplication Logic
			$args = [
				'post_type'      => 'dame_contact',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => [ 'relation' => 'AND' ],
			];

			if ( empty( $org ) ) {
				$args['meta_query'][] = [ 'key' => '_dame_contact_last_name', 'value' => $last_name, 'compare' => '=' ];
				$args['meta_query'][] = [ 'key' => '_dame_contact_first_name', 'value' => $first_name, 'compare' => '=' ];
				$args['meta_query'][] = [
					'relation' => 'OR',
					[ 'key' => '_dame_contact_organization', 'compare' => 'NOT EXISTS' ],
					[ 'key' => '_dame_contact_organization', 'value' => '', 'compare' => '=' ],
				];
			} else {
				$args['meta_query'][] = [ 'key' => '_dame_contact_organization', 'value' => $org, 'compare' => '=' ];
				$args['meta_query'][] = [ 'key' => '_dame_contact_last_name', 'value' => $last_name, 'compare' => '=' ];
				$args['meta_query'][] = [ 'key' => '_dame_contact_first_name', 'value' => $first_name, 'compare' => '=' ];
			}

			$ids = get_posts( $args );
			$post_id = ! empty( $ids ) ? (int) $ids[0] : 0;

			$formatted_last  = Utils::format_lastname( $last_name );
			$formatted_first = Utils::format_firstname( $first_name );
			
			$base_name = trim( $formatted_last . ' ' . $formatted_first );
			if ( ! empty( $org ) ) {
				$new_title = $org . ( ! empty( $base_name ) ? ' (' . $base_name . ')' : '' );
			} else {
				$new_title = $base_name ?: __( 'Contact sans nom', 'dame' );
			}

			if ( $post_id ) {
				wp_update_post( [ 'ID' => $post_id, 'post_title' => $new_title ] );
				$updated++;
			} else {
				$post_id = wp_insert_post( [
					'post_type'   => 'dame_contact',
					'post_title'  => $new_title,
					'post_status' => 'publish',
				] );
				$created++;
			}

			if ( $post_id ) {
				update_post_meta( $post_id, '_dame_contact_organization', $org );
				update_post_meta( $post_id, '_dame_contact_last_name', $formatted_last );
				update_post_meta( $post_id, '_dame_contact_first_name', $formatted_first );
				update_post_meta( $post_id, '_dame_contact_email', sanitize_email( $email ) );
				update_post_meta( $post_id, '_dame_contact_address_1', $addr1 );
				update_post_meta( $post_id, '_dame_contact_address_2', $addr2 );
				update_post_meta( $post_id, '_dame_contact_postcode', $postcode );
				update_post_meta( $post_id, '_dame_contact_city', $city );

				// Enrichment: Dept & Region
				$dept_code = substr( $postcode, 0, 2 );
				if ( strlen( $postcode ) >= 3 ) {
					if ( strpos( $postcode, '20' ) === 0 ) {
						$dept_code = (int) substr( $postcode, 2, 1 ) <= 1 ? '2A' : '2B';
					} elseif ( strpos( $postcode, '97' ) === 0 ) {
						$dept_code = substr( $postcode, 0, 3 );
					}
				}
				update_post_meta( $post_id, '_dame_contact_department', $dept_code );
				if ( isset( $dept_mapping[ $dept_code ] ) ) {
					update_post_meta( $post_id, '_dame_contact_region', $dept_mapping[ $dept_code ] );
				}

				// Assign Taxonomy
				wp_set_object_terms( $post_id, $type_slug, 'dame_contact_type' );
			}
		}

		fclose( $handle );

		$this->add_admin_notice( sprintf(
			__( 'Import terminé : %d contacts créés, %d mis à jour.', 'dame' ),
			$created,
			$updated
		) );
	}

	/* -------------------------------------------------------------------------
	 * ADHERENTS - CSV
	 * ------------------------------------------------------------------------- */

	private function export_csv_adherents(): void {
		$filename = 'dame-export-adherents-' . wp_date( 'Y-m-d' ) . '.csv';

		ob_clean();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		// Add BOM to fix UTF-8 in Excel.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// --- Dynamic Headers ---
		// 1. Get all seasons and sort them.
		$all_seasons = get_terms(
			array(
				'taxonomy'   => 'dame_saison_adhesion',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'DESC',
			)
		);

		// 2. Build the header array.
		$headers = array(
			__( 'Nom de naissance', 'dame' ), __( 'Nom d\'usage', 'dame' ), __( 'Prénom', 'dame' ), __( 'Date de naissance', 'dame' ), __( 'Lieu de naissance', 'dame' ), __( 'Sexe', 'dame' ), __( 'Profession', 'dame' ), __( 'Adresse email', 'dame' ), __( 'Numéro de téléphone', 'dame' ),
			__( 'Adresse', 'dame' ), __( 'Complément', 'dame' ), __( 'Code Postal', 'dame' ), __( 'Ville', 'dame' ), __( 'Pays', 'dame' ), __( 'Numéro de licence', 'dame' ),
			__( 'Type de licence', 'dame' ), __( 'Ecole d\'échecs (O/N)', 'dame' ), __( 'Pôle excellence (O/N)', 'dame' ), __( 'Bénévole (O/N)', 'dame' ), __( 'Elu local (O/N)', 'dame' ), __( 'Arbitre', 'dame' ),
			__( 'Représentant légal 1 - Nom de naissance', 'dame' ), __( 'Représentant légal 1 - Prénom', 'dame' ), __( 'Représentant légal 1 - Profession', 'dame' ), __( 'Représentant légal 1 - Email', 'dame' ), __( 'Représentant légal 1 - Téléphone', 'dame' ),
			__( 'Représentant légal 1 - Adresse', 'dame' ), __( 'Représentant légal 1 - Complément', 'dame' ), __( 'Représentant légal 1 - Code Postal', 'dame' ), __( 'Représentant légal 1 - Ville', 'dame' ),
			__( 'Représentant légal 2 - Nom de naissance', 'dame' ), __( 'Représentant légal 2 - Prénom', 'dame' ), __( 'Représentant légal 2 - Profession', 'dame' ), __( 'Représentant légal 2 - Email', 'dame' ), __( 'Représentant légal 2 - Téléphone', 'dame' ),
			__( 'Représentant légal 2 - Adresse', 'dame' ), __( 'Représentant légal 2 - Complément', 'dame' ), __( 'Représentant légal 2 - Code Postal', 'dame' ), __( 'Représentant légal 2 - Ville', 'dame' ),
			__( 'Autre téléphone', 'dame' ), __( 'Taille vêtements', 'dame' ), __( 'Allergies', 'dame' ), __( 'Régime alimentaire', 'dame' ), __( 'Moyen de locomotion', 'dame' ),
		);

		// 3. Add dynamic season headers.
		if ( ! is_wp_error( $all_seasons ) ) {
			foreach ( $all_seasons as $season ) {
				$headers[] = sprintf( __( 'Adhérent %s', 'dame' ), $season->name );
			}
		}

		fputcsv( $output, $headers, ';' );

		// --- Dynamic Rows ---
		$adherents_query = new WP_Query(
			array(
				'post_type'      => 'adherent',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( $adherents_query->have_posts() ) {
			while ( $adherents_query->have_posts() ) {
				$adherents_query->the_post();
				$post_id = get_the_ID();

				// Get adherent's seasons
				$adherent_seasons_slugs = wp_get_post_terms( $post_id, 'dame_saison_adhesion', array( 'fields' => 'slugs' ) );
				if ( is_wp_error( $adherent_seasons_slugs ) ) {
					$adherent_seasons_slugs = array();
				}

				// Format dates.
				$birth_date             = get_post_meta( $post_id, '_dame_birth_date', true );
				$formatted_birth_date   = $birth_date ? wp_date( 'd/m/Y', strtotime( $birth_date ), new \DateTimeZone('UTC') ) : '';

				// Format booleans.
				$is_ecole_echecs    = get_post_meta( $post_id, '_dame_is_junior', true ) ? 'O' : 'N';
				$is_pole_excellence = get_post_meta( $post_id, '_dame_is_pole_excellence', true ) ? 'O' : 'N';
				$is_benevole        = get_post_meta( $post_id, '_dame_is_benevole', true ) ? 'O' : 'N';
				$is_elu_local       = get_post_meta( $post_id, '_dame_is_elu_local', true ) ? 'O' : 'N';

				$row = array(
					get_post_meta( $post_id, '_dame_birth_name', true ),
					get_post_meta( $post_id, '_dame_last_name', true ),
					get_post_meta( $post_id, '_dame_first_name', true ),
					$formatted_birth_date,
					get_post_meta( $post_id, '_dame_birth_city', true ),
					get_post_meta( $post_id, '_dame_sexe', true ),
					get_post_meta( $post_id, '_dame_profession', true ),
					get_post_meta( $post_id, '_dame_email', true ),
					get_post_meta( $post_id, '_dame_phone_number', true ),
					get_post_meta( $post_id, '_dame_address_1', true ),
					get_post_meta( $post_id, '_dame_address_2', true ),
					get_post_meta( $post_id, '_dame_postal_code', true ),
					get_post_meta( $post_id, '_dame_city', true ),
					get_post_meta( $post_id, '_dame_country', true ),
					get_post_meta( $post_id, '_dame_license_number', true ),
					get_post_meta( $post_id, '_dame_license_type', true ),
					$is_ecole_echecs,
					$is_pole_excellence,
					$is_benevole,
					$is_elu_local,
					get_post_meta( $post_id, '_dame_arbitre_level', true ),
					get_post_meta( $post_id, '_dame_legal_rep_1_last_name', true ),
					get_post_meta( $post_id, '_dame_legal_rep_1_first_name', true ),
					get_post_meta( $post_id, '_dame_legal_rep_1_profession', true ),
					get_post_meta( $post_id, '_dame_legal_rep_1_email', true ),
					get_post_meta( $post_id, '_dame_legal_rep_1_phone', true ),
					get_post_meta( $post_id, '_dame_legal_rep_1_address_1', true ),
					get_post_meta( $post_id, '_dame_legal_rep_1_address_2', true ),
					get_post_meta( $post_id, '_dame_legal_rep_1_postal_code', true ),
					get_post_meta( $post_id, '_dame_legal_rep_1_city', true ),
					get_post_meta( $post_id, '_dame_legal_rep_2_last_name', true ),
					get_post_meta( $post_id, '_dame_legal_rep_2_first_name', true ),
					get_post_meta( $post_id, '_dame_legal_rep_2_profession', true ),
					get_post_meta( $post_id, '_dame_legal_rep_2_email', true ),
					get_post_meta( $post_id, '_dame_legal_rep_2_phone', true ),
					get_post_meta( $post_id, '_dame_legal_rep_2_address_1', true ),
					get_post_meta( $post_id, '_dame_legal_rep_2_address_2', true ),
					get_post_meta( $post_id, '_dame_legal_rep_2_postal_code', true ),
					get_post_meta( $post_id, '_dame_legal_rep_2_city', true ),
					get_post_meta( $post_id, '_dame_autre_telephone', true ),
					get_post_meta( $post_id, '_dame_taille_vetements', true ),
					get_post_meta( $post_id, '_dame_allergies', true ),
					get_post_meta( $post_id, '_dame_diet', true ),
					get_post_meta( $post_id, '_dame_transport', true ),
				);

				// Add dynamic season data
				if ( ! is_wp_error( $all_seasons ) ) {
					foreach ( $all_seasons as $season ) {
						$row[] = in_array( $season->slug, $adherent_seasons_slugs, true ) ? 'O' : 'N';
					}
				}

				fputcsv( $output, $row, ';' );
			}
			wp_reset_postdata();
		}

		fclose( $output );
		exit;
	}

	private function import_csv_adherents(): void {
		if ( ! isset( $_FILES['dame_import_csv_file'] ) || $_FILES['dame_import_csv_file']['error'] !== UPLOAD_ERR_OK ) {
			$this->add_admin_notice( __( 'Erreur lors du téléversement du fichier.', 'dame' ), 'error' );
			return;
		}

		$file = $_FILES['dame_import_csv_file'];
		$mime_type = mime_content_type( $file['tmp_name'] );

		if ( 'text/plain' !== $mime_type && 'text/csv' !== $mime_type ) {
			$this->add_admin_notice( __( 'Le fichier téléversé n\'est pas un fichier CSV valide.', 'dame' ), 'error' );
			return;
		}

		// Increase execution time
		set_time_limit( 300 );

		$handle = fopen( $file['tmp_name'], 'r' );
		if ( false === $handle ) {
			$this->add_admin_notice( __( 'Impossible d\'ouvrir le fichier téléversé.', 'dame' ), 'error' );
			return;
		}

		// Read header row and map columns
		$header = fgetcsv( $handle, 0, ';' );
		if ( false === $header ) {
			$this->add_admin_notice( __( 'Impossible de lire l\'en-tête du fichier CSV.', 'dame' ), 'error' );
			fclose( $handle );
			return;
		}

		// Remove BOM from the first header element if present
		if ( isset( $header[0] ) ) {
			$header[0] = preg_replace( '/^\x{FEFF}/u', '', $header[0] );
		}

		$expected_headers = array(
			'Nom de naissance', 'Nom d\'usage', 'Prénom', 'Date de naissance', 'Lieu de naissance', 'Sexe', 'Profession', 'Adresse email', 'Numéro de téléphone',
			'Adresse', 'Complément', 'Code Postal', 'Ville', 'Pays', 'Numéro de licence', 'Type de licence', 'Ecole d\'échecs (O/N)', 'Pôle excellence (O/N)', 'Bénévole (O/N)', 'Elu local (O/N)', 'Arbitre',
			'Représentant légal 1 - Nom de naissance', 'Représentant légal 1 - Prénom', 'Représentant légal 1 - Profession', 'Représentant légal 1 - Email', 'Représentant légal 1 - Téléphone',
			'Représentant légal 1 - Adresse', 'Représentant légal 1 - Complément', 'Représentant légal 1 - Code Postal', 'Représentant légal 1 - Ville',
			'Représentant légal 2 - Nom de naissance', 'Représentant légal 2 - Prénom', 'Représentant légal 2 - Profession', 'Représentant légal 2 - Email', 'Représentant légal 2 - Téléphone',
			'Représentant légal 2 - Adresse', 'Représentant légal 2 - Complément', 'Représentant légal 2 - Code Postal', 'Représentant légal 2 - Ville',
			'Autre téléphone', 'Taille vêtements', 'Allergies', 'Régime alimentaire', 'Moyen de locomotion'
		);
		$col_map = array_flip( $header );

		// Data mapping from CSV columns to post meta keys
		$meta_mapping = array(
			'Nom de naissance' => '_dame_birth_name',
			'Nom d\'usage' => '_dame_last_name',
			'Prénom' => '_dame_first_name',
			'Date de naissance' => '_dame_birth_date',
			'Lieu de naissance' => '_dame_birth_city',
			'Sexe' => '_dame_sexe',
			'Profession' => '_dame_profession',
			'Adresse email' => '_dame_email',
			'Numéro de téléphone' => '_dame_phone_number',
			'Adresse' => '_dame_address_1',
			'Complément' => '_dame_address_2',
			'Code Postal' => '_dame_postal_code',
			'Ville' => '_dame_city',
			'Pays' => '_dame_country',
			'Numéro de licence' => '_dame_license_number',
			'Type de licence' => '_dame_license_type',
			'Ecole d\'échecs (O/N)' => '_dame_is_junior',
			'Pôle excellence (O/N)' => '_dame_is_pole_excellence',
			'Bénévole (O/N)' => '_dame_is_benevole',
			'Elu local (O/N)' => '_dame_is_elu_local',
			'Arbitre' => '_dame_arbitre_level',
			'Représentant légal 1 - Nom de naissance' => '_dame_legal_rep_1_last_name',
			'Représentant légal 1 - Prénom' => '_dame_legal_rep_1_first_name',
			'Représentant légal 1 - Profession' => '_dame_legal_rep_1_profession',
			'Représentant légal 1 - Email' => '_dame_legal_rep_1_email',
			'Représentant légal 1 - Téléphone' => '_dame_legal_rep_1_phone',
			'Représentant légal 1 - Adresse' => '_dame_legal_rep_1_address_1',
			'Représentant légal 1 - Complément' => '_dame_legal_rep_1_address_2',
			'Représentant légal 1 - Code Postal' => '_dame_legal_rep_1_postal_code',
			'Représentant légal 1 - Ville' => '_dame_legal_rep_1_city',
			'Représentant légal 2 - Nom de naissance' => '_dame_legal_rep_2_last_name',
			'Représentant légal 2 - Prénom' => '_dame_legal_rep_2_first_name',
			'Représentant légal 2 - Profession' => '_dame_legal_rep_2_profession',
			'Représentant légal 2 - Email' => '_dame_legal_rep_2_email',
			'Représentant légal 2 - Téléphone' => '_dame_legal_rep_2_phone',
			'Représentant légal 2 - Adresse' => '_dame_legal_rep_2_address_1',
			'Représentant légal 2 - Complément' => '_dame_legal_rep_2_address_2',
			'Représentant légal 2 - Code Postal' => '_dame_legal_rep_2_postal_code',
			'Représentant légal 2 - Ville' => '_dame_legal_rep_2_city',
			'Autre téléphone' => '_dame_autre_telephone',
			'Taille vêtements' => '_dame_taille_vetements',
			'Allergies' => '_dame_allergies',
			'Régime alimentaire' => '_dame_diet',
			'Moyen de locomotion' => '_dame_transport',
		);

		$imported_count = 0;
		$department_region_mapping = dame_get_department_region_mapping();
		$all_seasons = get_terms( [ 'taxonomy' => 'dame_saison_adhesion', 'hide_empty' => false ] );

		while ( ( $row = fgetcsv( $handle, 0, ';' ) ) !== false ) {
			$member_data = array();
			foreach ( $expected_headers as $header_name ) {
				$col_index = isset( $col_map[ $header_name ] ) ? $col_map[ $header_name ] : -1;
				if ( $col_index !== -1 && isset( $row[ $col_index ] ) ) {
					$member_data[ $header_name ] = trim( $row[ $col_index ] );
				} else {
					$member_data[ $header_name ] = '';
				}
			}

			// Capture dynamic seasons if present in CSV row based on expected pattern
			$season_data = [];
			if ( ! is_wp_error( $all_seasons ) ) {
				foreach ( $all_seasons as $season ) {
					$season_header = 'Adhérent ' . $season->name;
					$col_index = isset( $col_map[ $season_header ] ) ? $col_map[ $season_header ] : -1;
					if ( $col_index !== -1 && isset( $row[ $col_index ] ) ) {
						$season_data[ $season->slug ] = trim( mb_strtoupper( $row[ $col_index ], 'UTF-8' ) ) === 'O';
					}
				}
			}

			$first_name = $member_data['Prénom'];
			$last_name = $member_data['Nom d\'usage'];

			if ( empty( $first_name ) || empty( $last_name ) ) {
				continue; // Skip rows without a name
			}

			$post_id = 0;
			$email = $member_data['Adresse email'];
			$license = $member_data['Numéro de licence'];
			$post_title = mb_strtoupper( $last_name, 'UTF-8' ) . ' ' . $first_name;

			// Reconciliation
			$query_args = array(
				'post_type'      => 'adherent',
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			);

			if ( ! empty( $license ) ) {
				$query_args['meta_query'] = array(
					array( 'key' => '_dame_license_number', 'value' => $license, 'compare' => '=' ),
				);
				$posts = get_posts( $query_args );
				if ( ! empty( $posts ) ) $post_id = $posts[0];
			}

			if ( ! $post_id && ! empty( $email ) ) {
				$query_args['meta_query'] = array(
					array( 'key' => '_dame_email', 'value' => $email, 'compare' => '=' ),
				);
				$posts = get_posts( $query_args );
				if ( ! empty( $posts ) ) $post_id = $posts[0];
			}

			if ( ! $post_id ) {
				$query_args['title'] = $post_title;
				unset( $query_args['meta_query'] );
				$posts = get_posts( $query_args );
				if ( ! empty( $posts ) ) $post_id = $posts[0];
			}

			if ( ! $post_id ) {
				$post_data = array(
					'post_title'  => $post_title,
					'post_type'   => 'adherent',
					'post_status' => 'publish',
				);
				$post_id = wp_insert_post( $post_data );
			} else {
				// Update title in case name changed
				wp_update_post( array(
					'ID'         => $post_id,
					'post_title' => $post_title,
				) );
			}

			if ( $post_id ) {
				foreach ( $meta_mapping as $csv_header => $meta_key ) {
					$value = $member_data[ $csv_header ];

					if ( '_dame_birth_date' === $meta_key ) {
						if ( ! empty( $value ) ) {
							$date = DateTime::createFromFormat( 'd/m/Y', $value );
							if ( $date ) {
								$value = $date->format( 'Y-m-d' );
							} else {
								$value = ''; // Invalid date format
							}
						} else {
							$value = '1950-09-19';
						}
					}

					if ( '_dame_membership_status' === $meta_key ) {
						$status_key = 'N'; // Default to 'Non Adhérent'
						$normalized_value = mb_strtoupper( trim( $value ), 'UTF-8' );

						// Handle cases like "Actif (A)" by extracting the key
						if ( preg_match( '/\(([A-Z])\)/', $normalized_value, $matches ) ) {
							$normalized_value = $matches[1];
						}

						$status_map = array(
							'ACTIF' => 'A',
							'A' => 'A',
							'EXPIRÉ' => 'E',
							'EXPIRE' => 'E',
							'E' => 'E',
							'ANCIEN' => 'X',
							'X' => 'X',
							'NON ADHÉRENT' => 'N',
							'NON ADHERENT' => 'N',
							'N' => 'N',
						);

						if ( isset( $status_map[ $normalized_value ] ) ) {
							$status_key = $status_map[ $normalized_value ];
						}
						$value = $status_key;
					}

					// Sanitize phone numbers
					if ( in_array( $meta_key, array( '_dame_phone_number', '_dame_autre_telephone' ) ) ) {
						$phone_number = str_replace( array( ' ', '.' ), '', $value );
						if ( substr( $phone_number, 0, 3 ) === '+33' ) {
							$phone_number = '0' . substr( $phone_number, 3 );
						} elseif ( substr( $phone_number, 0, 2 ) === '33' ) {
							$phone_number = '0' . substr( $phone_number, 2 );
						}
						$value = $phone_number;
					}

					// Handle boolean fields (O/N)
					$boolean_fields = [
						'_dame_is_junior',
						'_dame_is_pole_excellence',
						'_dame_is_benevole',
						'_dame_is_elu_local',
					];
					if ( in_array( $meta_key, $boolean_fields ) ) {
						$value = ( mb_strtoupper( trim( $value ), 'UTF-8' ) === 'O' ) ? 1 : 0;
					}

					update_post_meta( $post_id, $meta_key, sanitize_text_field( $value ) );
				}

				// Handle postal code logic
				$postal_code = $member_data['Code Postal'];
				if ( ! empty( $postal_code ) ) {
					update_post_meta( $post_id, '_dame_country', 'FR' );

					$department_code = substr( $postal_code, 0, 2 );
					if ( strlen( $postal_code ) >= 3 ) {
						if ( strpos( $postal_code, '20' ) === 0 ) {
							$department_code = intval( substr( $postal_code, 2, 1 ) ) <= 1 ? '2A' : '2B';
						} elseif ( strpos( $postal_code, '97' ) === 0 ) {
							$department_code = substr( $postal_code, 0, 3 );
						}
					}

					update_post_meta( $post_id, '_dame_department', $department_code );

					if ( isset( $department_region_mapping[ $department_code ] ) ) {
						update_post_meta( $post_id, '_dame_region', $department_region_mapping[ $department_code ] );
					}
				}

				// Set defaults for fields not in CSV
				if ( empty( get_post_meta( $post_id, '_dame_license_type', true ) ) ) {
					update_post_meta( $post_id, '_dame_license_type', 'Non précisé' );
				}
				if ( empty( get_post_meta( $post_id, '_dame_arbitre_level', true ) ) ) {
					update_post_meta( $post_id, '_dame_arbitre_level', 'Non' );
				}

				// Handle Seasons
				if ( ! empty( $season_data ) ) {
					$current_seasons = wp_get_post_terms( $post_id, 'dame_saison_adhesion', array( 'fields' => 'slugs' ) );
					if ( is_wp_error( $current_seasons ) ) {
						$current_seasons = array();
					}

					foreach ( $season_data as $season_slug => $is_in_season ) {
						if ( $is_in_season ) {
							if ( ! in_array( $season_slug, $current_seasons, true ) ) {
								wp_set_object_terms( $post_id, $season_slug, 'dame_saison_adhesion', true );
							}
						} else {
							if ( in_array( $season_slug, $current_seasons, true ) ) {
								wp_remove_object_terms( $post_id, $season_slug, 'dame_saison_adhesion' );
							}
						}
					}
				}

				$imported_count++;
			}
		}

		fclose( $handle );

		$message = sprintf(
			_n(
				'%d adhérent a été importé avec succès.',
				'%d adhérents ont été importés avec succès.',
				$imported_count,
				'dame'
			),
			$imported_count
		);
		$this->add_admin_notice( $message );
	}

	/* -------------------------------------------------------------------------
	 * ADHERENTS - JSON (BACKUP/RESTORE)
	 * ------------------------------------------------------------------------- */

	public function generate_adherent_export_data() {
		$data = [
			'version' => DAME_VERSION,
			'taxonomy_terms' => [], 'adherents' => [], 'contacts' => [], 'pre_inscriptions' => [], 'messages' => [], 'message_opens' => [], 'options' => []
		];

		// Taxonomies
		foreach ( [ 'dame_saison_adhesion', 'dame_group', 'dame_contact_type' ] as $tax ) {
			$terms = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false ] );
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_data = [ 'old_id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug, 'description' => $term->description ];

					// Export term meta, specifically for groups
					$term_meta = get_term_meta( $term->term_id );
					if ( ! empty( $term_meta ) ) {
						$term_data['meta_data'] = [];
						foreach ( $term_meta as $k => $v ) {
							$term_data['meta_data'][ $k ] = maybe_unserialize( $v[0] );
						}
					} else if ( $tax === 'dame_group' ) {
						// Fallback if not using term_meta directly in legacy, but a custom option taxonomy_termId
						$group_type = get_term_meta( $term->term_id, '_dame_group_type', true );
						if ( ! empty( $group_type ) ) {
							$term_data['meta_data'] = [ '_dame_group_type' => $group_type ];
						}
					}

					$data['taxonomy_terms'][ $tax ][] = $term_data;
				}
			}
		}

		// Adherents
		$query = new WP_Query( [ 'post_type' => 'adherent', 'posts_per_page' => -1, 'post_status' => 'any' ] );
		foreach ( $query->posts as $post ) {
			$meta = [];
			foreach ( get_post_meta( $post->ID ) as $k => $v ) {
				if ( strpos( $k, '_dame_' ) === 0 ) $meta[ $k ] = maybe_unserialize( $v[0] );
			}
			$taxs = [];
			foreach ( [ 'dame_saison_adhesion', 'dame_group' ] as $tax ) {
				$taxs[ $tax ] = wp_get_post_terms( $post->ID, $tax, [ 'fields' => 'slugs' ] );
			}
			$data['adherents'][] = [ 'old_id' => $post->ID, 'post_title' => $post->post_title, 'meta_data' => $meta, 'taxonomies' => $taxs ];
		}

		// Contacts (dame_contact)
		$query_contacts = new WP_Query( [ 'post_type' => 'dame_contact', 'posts_per_page' => -1, 'post_status' => 'any' ] );
		foreach ( $query_contacts->posts as $post ) {
			$meta = [];
			foreach ( get_post_meta( $post->ID ) as $k => $v ) {
				if ( strpos( $k, '_dame_' ) === 0 ) $meta[ $k ] = maybe_unserialize( $v[0] );
			}
			$taxs = [];
			$taxs['dame_contact_type'] = wp_get_post_terms( $post->ID, 'dame_contact_type', [ 'fields' => 'slugs' ] );
			$data['contacts'][] = [ 'old_id' => $post->ID, 'post_title' => $post->post_title, 'meta_data' => $meta, 'taxonomies' => $taxs ];
		}

		// Options
		$current_season_tag_id = get_option( 'dame_current_season_tag_id' );
		if ( $current_season_tag_id ) {
			$term = get_term( $current_season_tag_id );
			if ( $term && ! is_wp_error( $term ) ) {
				$data['options']['dame_current_season_tag_slug'] = $term->slug;
			}
		}

		// Pre-inscriptions
		$query = new WP_Query( [ 'post_type' => 'dame_pre_inscription', 'posts_per_page' => -1, 'post_status' => 'any' ] );
		foreach ( $query->posts as $post ) {
			$meta = [];
			foreach ( get_post_meta( $post->ID ) as $k => $v ) {
				if ( strpos( $k, '_dame_' ) === 0 ) $meta[ $k ] = maybe_unserialize( $v[0] );
			}
			$data['pre_inscriptions'][] = [ 'old_id' => $post->ID, 'post_title' => $post->post_title, 'meta_data' => $meta ];
		}

		// Messages
		$query = new WP_Query( [ 'post_type' => 'dame_message', 'posts_per_page' => -1, 'post_status' => 'any' ] );
		foreach ( $query->posts as $post ) {
			$meta = [];
			foreach ( get_post_meta( $post->ID ) as $k => $v ) {
				if ( strpos( $k, '_dame_' ) === 0 ) $meta[ $k ] = maybe_unserialize( $v[0] );
			}
			$data['messages'][] = [ 'old_id' => $post->ID, 'post_title' => $post->post_title, 'post_content' => $post->post_content, 'post_status' => $post->post_status, 'meta_data' => $meta ];
		}

		// Message opens
		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_message_opens';
		$open_stats_data = $wpdb->get_results( "SELECT message_id, email_hash, opened_at, user_ip FROM {$table_name}", ARRAY_A );
		if ( is_array( $open_stats_data ) ) {
			$data['message_opens'] = $open_stats_data;
		}

		return $data;
	}

	private function export_json_adherents(): void {
		$data = $this->generate_adherent_export_data();
		$gz = gzcompress( json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		$filename = 'dame-adherents-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		ob_clean();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $gz ) );
		echo $gz;
		exit;
	}

	private function import_json_adherents(): void {
		if ( ! isset( $_FILES['dame_import_file'] ) ) return;
		$json = gzuncompress( file_get_contents( $_FILES['dame_import_file']['tmp_name'] ) );
		$data = json_decode( $json, true );

		if ( ! $data ) return;

		// 1. CLEAR DATA
		global $wpdb;
		$posts = get_posts( [ 'post_type' => [ 'adherent', 'dame_contact', 'dame_pre_inscription', 'dame_message' ], 'posts_per_page' => -1, 'post_status' => 'any', 'fields' => 'ids' ] );
		foreach ( $posts as $pid ) wp_delete_post( $pid, true );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}dame_message_opens" );

		foreach ( [ 'dame_saison_adhesion', 'dame_group', 'dame_contact_type' ] as $tax ) {
			$terms = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'ids' ] );
			if ( ! is_wp_error( $terms ) ) foreach ( $terms as $tid ) wp_delete_term( $tid, $tax );
		}

		// 2. IMPORT DATA (With ID Mapping)
		$map_terms = []; $map_adherents = []; $map_messages = [];

		// Taxonomies
		foreach ( $data['taxonomy_terms'] ?? [] as $tax => $terms ) {
			foreach ( $terms as $t ) {
				$new = wp_insert_term( $t['name'], $tax, [ 'slug' => $t['slug'], 'description' => $t['description'] ] );
				if ( ! is_wp_error( $new ) ) {
					$new_term_id = $new['term_id'];
					$map_terms[ $t['old_id'] ?? $t['slug'] ] = $new_term_id;

					// Import term metadata, restoring Group types
					if ( ! empty( $t['meta_data'] ) ) {
						foreach ( $t['meta_data'] as $k => $v ) {
							update_term_meta( $new_term_id, $k, $v );
						}
					}
				}
			}
		}

		// Adherents
		$meta_insert_values = [];
		$meta_insert_placeholders = [];
		foreach ( $data['adherents'] ?? [] as $a ) {
			$pid = wp_insert_post( [ 'post_title' => $a['post_title'], 'post_type' => 'adherent', 'post_status' => 'publish' ] );
			if ( $pid ) {
				$map_adherents[ $a['old_id'] ] = $pid;
				foreach ( $a['meta_data'] as $k => $v ) {
					$meta_insert_values[] = $pid;
					$meta_insert_values[] = $k;
					$meta_insert_values[] = maybe_serialize( $v );
					$meta_insert_placeholders[] = '(%d, %s, %s)';
				}
				foreach ( $a['taxonomies'] as $tax => $slugs ) wp_set_object_terms( $pid, $slugs, $tax );
			}
		}
		if ( ! empty( $meta_insert_placeholders ) ) {
			$query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ', ', $meta_insert_placeholders );
			$wpdb->query( $wpdb->prepare( $query, $meta_insert_values ) );
		}

		// Contacts (dame_contact)
		$contact_meta_insert_values = [];
		$contact_meta_insert_placeholders = [];
		foreach ( $data['contacts'] ?? [] as $c ) {
			$pid = wp_insert_post( [ 'post_title' => $c['post_title'], 'post_type' => 'dame_contact', 'post_status' => 'publish' ] );
			if ( $pid ) {
				foreach ( $c['meta_data'] as $k => $v ) {
					$contact_meta_insert_values[] = $pid;
					$contact_meta_insert_values[] = $k;
					$contact_meta_insert_values[] = maybe_serialize( $v );
					$contact_meta_insert_placeholders[] = '(%d, %s, %s)';
				}
				foreach ( $c['taxonomies'] as $tax => $slugs ) wp_set_object_terms( $pid, $slugs, $tax );
			}
		}
		if ( ! empty( $contact_meta_insert_placeholders ) ) {
			$query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ', ', $contact_meta_insert_placeholders );
			$wpdb->query( $wpdb->prepare( $query, $contact_meta_insert_values ) );
		}

		// Options
		if ( ! empty( $data['options']['dame_current_season_tag_slug'] ) ) {
			$term = get_term_by( 'slug', $data['options']['dame_current_season_tag_slug'], 'dame_saison_adhesion' );
			if ( $term && ! is_wp_error( $term ) ) {
				update_option( 'dame_current_season_tag_id', $term->term_id );
			}
		}

		// Pre-inscriptions
		$pi_meta_insert_values = [];
		$pi_meta_insert_placeholders = [];
		foreach ( $data['pre_inscriptions'] ?? [] as $pi ) {
			$pid = wp_insert_post( [ 'post_title' => $pi['post_title'], 'post_type' => 'dame_pre_inscription', 'post_status' => 'pending' ] );
			if ( $pid ) {
				foreach ( $pi['meta_data'] as $k => $v ) {
					$pi_meta_insert_values[] = $pid;
					$pi_meta_insert_values[] = $k;
					$pi_meta_insert_values[] = maybe_serialize( $v );
					$pi_meta_insert_placeholders[] = '(%d, %s, %s)';
				}
			}
		}
		if ( ! empty( $pi_meta_insert_placeholders ) ) {
			$query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ', ', $pi_meta_insert_placeholders );
			$wpdb->query( $wpdb->prepare( $query, $pi_meta_insert_values ) );
		}

		// Messages
		$msg_meta_insert_values = [];
		$msg_meta_insert_placeholders = [];
		foreach ( $data['messages'] ?? [] as $m ) {
			// Sanitize input to match original code
			$post_title = sanitize_text_field( $m['post_title'] );
			$post_content = wp_kses_post( $m['post_content'] );
			$post_status = sanitize_key( $m['post_status'] );

			$pid = wp_insert_post( [ 'post_title' => $post_title, 'post_content' => $post_content, 'post_type' => 'dame_message', 'post_status' => $post_status ] );
			if ( $pid ) {
				$map_messages[ $m['old_id'] ] = $pid;
				if ( isset( $m['meta_data'] ) && is_array( $m['meta_data'] ) ) {
					foreach ( $m['meta_data'] as $k => $v ) {
						$remapped_value = $v;
						if ( '_dame_manual_recipients' === $k && is_array( $v ) ) {
							$remapped_value = [];
							foreach ( $v as $old_id ) if ( isset( $map_adherents[ $old_id ] ) ) $remapped_value[] = $map_adherents[ $old_id ];
						}
						if ( in_array( $k, [ '_dame_recipient_seasons', '_dame_recipient_groups_saisonnier', '_dame_recipient_groups_permanent' ] ) && is_array( $v ) ) {
							$remapped_value = [];
							foreach ( $v as $old_id ) if ( isset( $map_terms[ $old_id ] ) ) $remapped_value[] = $map_terms[ $old_id ];
						}
						$msg_meta_insert_values[] = $pid;
						$msg_meta_insert_values[] = $k;
						$msg_meta_insert_values[] = maybe_serialize( $remapped_value );
						$msg_meta_insert_placeholders[] = '(%d, %s, %s)';
					}
				}
			}
		}
		if ( ! empty( $msg_meta_insert_placeholders ) ) {
			$query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ', ', $msg_meta_insert_placeholders );
			$wpdb->query( $wpdb->prepare( $query, $msg_meta_insert_values ) );
		}

		// Message opens
		foreach ( $data['message_opens'] ?? [] as $mo ) {
			if ( isset( $map_messages[ $mo['message_id'] ] ) ) {
				$wpdb->insert(
					"{$wpdb->prefix}dame_message_opens",
					[ 'message_id' => $map_messages[ $mo['message_id'] ], 'email_hash' => $mo['email_hash'], 'opened_at' => $mo['opened_at'], 'user_ip' => $mo['user_ip'] ],
					[ '%d', '%s', '%s', '%s' ]
				);
			}
		}

		$imported_version = $data['version'] ?? '2.2.0';
		if ( function_exists( 'dame_perform_upgrade' ) ) {
			dame_perform_upgrade( $imported_version, DAME_VERSION );
		}

		$this->add_admin_notice( "Import terminé avec succès." );
	}

	/* -------------------------------------------------------------------------
	 * AGENDA - JSON (BACKUP/RESTORE)
	 * ------------------------------------------------------------------------- */

	public function generate_agenda_export_data() {
		$data = [ 'version' => DAME_VERSION, 'events' => [], 'taxonomy_terms' => [] ];
		// Agenda Categories
		$terms = get_terms( [ 'taxonomy' => 'dame_agenda_category', 'hide_empty' => false ] );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$meta = get_option( "taxonomy_" . $t->term_id );
				$data['taxonomy_terms'][] = [ 'name' => $t->name, 'slug' => $t->slug, 'description' => $t->description, 'color' => $meta['color'] ?? '' ];
			}
		}
		// Events
		$posts = get_posts( [ 'post_type' => 'dame_agenda', 'posts_per_page' => -1, 'post_status' => 'any' ] );
		foreach ( $posts as $p ) {
			$meta = [];
			foreach ( get_post_meta( $p->ID ) as $k => $v ) $meta[ $k ] = maybe_unserialize( $v[0] );
			$cats = wp_get_post_terms( $p->ID, 'dame_agenda_category', [ 'fields' => 'slugs' ] );
			$data['events'][] = [ 'post_title' => $p->post_title, 'post_content' => $p->post_content, 'meta_data' => $meta, 'categories' => $cats ];
		}
		return $data;
	}

	private function export_json_agenda(): void {
		$data = $this->generate_agenda_export_data();
		$gz = gzcompress( json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		$filename = 'dame-agenda-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		ob_clean();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $gz ) );
		echo $gz;
		exit;
	}

	private function import_json_agenda(): void {
		if ( ! isset( $_FILES['dame_agenda_restore_file'] ) ) return;
		$json = gzuncompress( file_get_contents( $_FILES['dame_agenda_restore_file']['tmp_name'] ) );
		$data = json_decode( $json, true );
		if ( ! $data ) return;

		// Clear
		$posts = get_posts( [ 'post_type' => 'dame_agenda', 'posts_per_page' => -1, 'fields' => 'ids' ] );
		foreach ( $posts as $pid ) wp_delete_post( $pid, true );
		$terms = get_terms( [ 'taxonomy' => 'dame_agenda_category', 'hide_empty' => false, 'fields' => 'ids' ] );
		foreach ( $terms as $tid ) { delete_option( "taxonomy_$tid" ); wp_delete_term( $tid, 'dame_agenda_category' ); }

		// Import
		foreach ( $data['taxonomy_terms'] ?? [] as $t ) {
			$new = wp_insert_term( $t['name'], 'dame_agenda_category', [ 'slug' => $t['slug'], 'description' => $t['description'] ] );
			if ( ! is_wp_error( $new ) && ! empty( $t['color'] ) ) update_option( "taxonomy_" . $new['term_id'], [ 'color' => $t['color'] ] );
		}
		$agenda_meta_insert_values = [];
		$agenda_meta_insert_placeholders = [];
		foreach ( $data['events'] ?? [] as $e ) {
			$pid = wp_insert_post( [ 'post_title' => $e['post_title'], 'post_content' => $e['post_content'], 'post_type' => 'dame_agenda', 'post_status' => 'publish' ] );
			if ( $pid ) {
				foreach ( $e['meta_data'] as $k => $v ) {
					$agenda_meta_insert_values[] = $pid;
					$agenda_meta_insert_values[] = $k;
					$agenda_meta_insert_values[] = maybe_serialize( $v );
					$agenda_meta_insert_placeholders[] = '(%d, %s, %s)';
				}
				if ( ! empty( $e['categories'] ) ) wp_set_object_terms( $pid, $e['categories'], 'dame_agenda_category' );
			}
		}
		if ( ! empty( $agenda_meta_insert_placeholders ) ) {
			$query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ', ', $agenda_meta_insert_placeholders );
			$wpdb->query( $wpdb->prepare( $query, $agenda_meta_insert_values ) );
		}
		$this->add_admin_notice( "Agenda restauré avec succès." );
	}

	/* -------------------------------------------------------------------------
	 * CRON JOB
	 * ------------------------------------------------------------------------- */

	public function run_scheduled_backup(): void {
		global $wpdb;
		$upload_dir = wp_upload_dir();
		$backup_dir = trailingslashit( $upload_dir['basedir'] ) . 'dame-backups';
		wp_mkdir_p( $backup_dir );

		// Generate files
		$data_adherent = $this->generate_adherent_export_data();
		$file_adherent = trailingslashit( $backup_dir ) . 'dame-adherents-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		file_put_contents( $file_adherent, gzcompress( json_encode( $data_adherent ) ) );

		$data_agenda = $this->generate_agenda_export_data();
		$file_agenda = trailingslashit( $backup_dir ) . 'dame-agenda-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		file_put_contents( $file_agenda, gzcompress( json_encode( $data_agenda ) ) );

		// Send Email
		$options = get_option( 'dame_options' );
		$to = $options['sender_email'] ?? get_option( 'admin_email' );
		if ( $to ) {
			$subject = sprintf( __( 'Sauvegarde journalière DAME pour %s', 'dame' ), get_bloginfo( 'name' ) );
			$body = '<p>' . __( 'Veuillez trouver ci-joint les sauvegardes journalières.', 'dame' ) . '</p>';
			$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
			wp_mail( $to, $subject, $body, $headers, [ $file_adherent, $file_agenda ] );
		}

		// Cleanup
		@unlink( $file_adherent );
		@unlink( $file_agenda );
	}
}
