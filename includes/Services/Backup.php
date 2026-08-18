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
		add_action( 'admin_init', array( $this, 'handle_manual_actions' ) );
		add_action( 'admin_notices', array( $this, 'display_import_export_notices' ) );
	}

	/**
	 * Adds an admin notice to be displayed on the next page load.
	 *
	 * @param string $message The notice message.
	 * @param string $type The notice type (success, error, etc.).
	 */
	private function add_admin_notice( string $message, string $type = 'success' ): void {
		set_transient(
			'dame_import_export_notice',
			array(
				'message' => $message,
				'type'    => $type,
			),
			30
		);
	}

	/**
	 * Displays the admin notice if one is set.
	 */
	public function display_import_export_notices(): void {
		if ( get_transient( 'dame_import_export_notice' ) ) {
			$notice  = get_transient( 'dame_import_export_notice' );
			$message = $notice['message'];
			$type    = $notice['type'];
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
		if ( isset( $_POST['dame_export_csv_action'], $_POST['dame_export_csv_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_export_csv_nonce'] ) ), 'dame_export_csv_nonce_action' ) ) {
			$this->export_csv_adherents();
		}

		// 2. Import CSV Adherents
		if ( isset( $_POST['dame_import_csv_action'], $_POST['dame_import_csv_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_import_csv_nonce'] ) ), 'dame_import_csv_nonce_action' ) ) {
			$this->import_csv_adherents();
		}

		// 3. Export JSON Adherents (Backup)
		if ( isset( $_POST['dame_export_action'], $_POST['dame_export_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_export_nonce'] ) ), 'dame_export_nonce_action' ) ) {
			$this->export_json_adherents();
		}

		// 4. Import JSON Adherents (Restore)
		if ( isset( $_POST['dame_import'], $_POST['dame_import_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_import_nonce'] ) ), 'dame_import_nonce_action' ) ) {
			$this->import_json_adherents();
		}

		// 5. Export JSON Agenda
		if ( isset( $_POST['dame_agenda_backup_action'], $_POST['dame_agenda_backup_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_agenda_backup_nonce'] ) ), 'dame_agenda_backup_nonce_action' ) ) {
			$this->export_json_agenda();
		}

		// 6. Import JSON Agenda
		if ( isset( $_POST['dame_agenda_restore_action'], $_POST['dame_agenda_restore_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_agenda_restore_nonce'] ) ), 'dame_agenda_restore_nonce_action' ) ) {
			$this->import_json_agenda();
		}

		// 7. Export JSON Site Content
		if ( isset( $_POST['dame_site_backup_action'], $_POST['dame_site_backup_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_site_backup_nonce'] ) ), 'dame_site_backup_nonce_action' ) ) {
			$this->export_json_site();
		}

		// 8. Import JSON Site Content
		if ( isset( $_POST['dame_site_restore_action'], $_POST['dame_site_restore_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_site_restore_nonce'] ) ), 'dame_site_restore_nonce_action' ) ) {
			$this->import_json_site();
		}

		// 9. Export CSV Contacts
		if ( isset( $_POST['dame_export_contacts_csv_action'], $_POST['dame_export_contacts_csv_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_export_contacts_csv_nonce'] ) ), 'dame_export_contacts_csv_nonce_action' ) ) {
			$this->export_csv_contacts();
		}

		// 10. Import CSV Contacts
		if ( isset( $_POST['dame_import_contacts_csv_action'], $_POST['dame_import_contacts_csv_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_import_contacts_csv_nonce'] ) ), 'dame_import_contacts_csv_nonce_action' ) ) {
			$this->import_csv_contacts();
		}

		// 11. Import CSV HelloAsso Contacts
		if ( isset( $_POST['dame_import_helloasso_csv_action'], $_POST['dame_import_helloasso_csv_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_import_helloasso_csv_nonce'] ) ), 'dame_import_helloasso_csv_nonce_action' ) ) {
			$this->import_csv_helloasso_contacts();
		}

		// 12. Delete Contact Duplicates
		if ( isset( $_POST['dame_delete_contact_duplicates_action'], $_POST['dame_delete_contact_duplicates_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_delete_contact_duplicates_nonce'] ) ), 'dame_delete_contact_duplicates_nonce_action' ) ) {
			$this->delete_contact_duplicates();
		}
	}

	/*
	 * -------------------------------------------------------------------------
	 * CONTACTS - CSV
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Handle contacts export to CSV.
	 */
	private function export_csv_contacts(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in handle_manual_actions.
		$type_slug = isset( $_POST['contact_type'] ) ? sanitize_key( wp_unslash( $_POST['contact_type'] ) ) : '';
		if ( empty( $type_slug ) ) {
			return;
		}

		$filename = 'dame-export-contacts-' . $type_slug . '-' . wp_date( 'Y-m-d' ) . '.csv';

		ob_clean();
		header( 'Content-Type: text/csv; charset=windows-1252' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );

		// Headers
		$headers = array(
			'Organisation',
			'Nom',
			'Prenom',
			'Role',
			'Email',
			'Refus mailing',
			'Adresse',
			'Complement',
			'Code Postal',
			'Ville',
			'Type',
		);

		// Convert headers to CP1252
		$headers_encoded = array_map( fn( $h ) => mb_convert_encoding( (string) $h, 'Windows-1252', 'UTF-8' ), $headers );
		fputcsv( $output, $headers_encoded, ';', '"', '\\' );

		$query = new WP_Query(
			array(
				'post_type'      => 'dame_contact',
				'posts_per_page' => -1,
				'tax_query'      => array(
					array(
						'taxonomy' => 'dame_contact_type',
						'field'    => 'slug',
						'terms'    => $type_slug,
					),
				),
			)
		);

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$row = array(
					get_post_meta( $post->ID, '_dame_contact_organization', true ),
					get_post_meta( $post->ID, '_dame_contact_last_name', true ),
					get_post_meta( $post->ID, '_dame_contact_first_name', true ),
					get_post_meta( $post->ID, '_dame_contact_role', true ),
					get_post_meta( $post->ID, '_dame_contact_email', true ),
					get_post_meta( $post->ID, '_dame_contact_no_emails', true ) ? 'O' : 'N',
					get_post_meta( $post->ID, '_dame_contact_address_1', true ),
					get_post_meta( $post->ID, '_dame_contact_address_2', true ),
					get_post_meta( $post->ID, '_dame_contact_postcode', true ),
					get_post_meta( $post->ID, '_dame_contact_city', true ),
					$type_slug,
				);

				// Convert row to CP1252
				$row_encoded = array_map( fn( $val ) => mb_convert_encoding( (string) $val, 'Windows-1252', 'UTF-8' ), $row );
				fputcsv( $output, $row_encoded, ';', '"', '\\' );
			}
		}

		fclose( $output );
		exit;
	}

	/**
	 * Handle contacts import from CSV.
	 */
	private function import_csv_contacts(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in handle_manual_actions.
		$type_slug = isset( $_POST['contact_type'] ) ? sanitize_key( wp_unslash( $_POST['contact_type'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in handle_manual_actions.
		if ( empty( $type_slug ) || ! isset( $_FILES['dame_import_contacts_file'] ) || ! is_array( $_FILES['dame_import_contacts_file'] ) || empty( $_FILES['dame_import_contacts_file']['tmp_name'] ) || ( isset( $_FILES['dame_import_contacts_file']['error'] ) && UPLOAD_ERR_OK !== $_FILES['dame_import_contacts_file']['error'] ) ) {
			$this->add_admin_notice( __( 'Données invalides pour l\'import.', 'dame' ), 'error' );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded temp file path verified in handle_manual_actions.
		$raw_tmp  = isset( $_FILES['dame_import_contacts_file']['tmp_name'] ) ? $_FILES['dame_import_contacts_file']['tmp_name'] : '';
		$tmp_file = sanitize_text_field( wp_unslash( $raw_tmp ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Local CSV import file handle.
		$handle = fopen( $tmp_file, 'r' );
		if ( ! $handle ) {
			$this->add_admin_notice( __( 'Impossible d\'ouvrir le fichier CSV.', 'dame' ), 'error' );
			return;
		}

		// Read headers
		$headers = fgetcsv( $handle, 0, ';', '"', '\\' );
		if ( ! $headers ) {
			$this->add_admin_notice( __( 'Impossible de lire l\'en-tête du fichier CSV.', 'dame' ), 'error' );
			fclose( $handle );
			return;
		}

		// Convert headers to UTF-8 and map them
		$headers = array_map( fn( $h ) => mb_convert_encoding( (string) $h, 'UTF-8', 'Windows-1252' ), $headers );

		// Remove BOM if present
		if ( isset( $headers[0] ) ) {
			$headers[0] = preg_replace( '/^\x{FEFF}/u', '', $headers[0] );
		}

		$col_map = array_flip( $headers );

		$created      = 0;
		$updated      = 0;
		$dept_mapping = Data_Provider::get_department_region_mapping();

		while ( ( $row = fgetcsv( $handle, 0, ';', '"', '\\' ) ) !== false ) {
			// Convert to UTF-8
			$row = array_map( fn( $val ) => mb_convert_encoding( (string) $val, 'UTF-8', 'Windows-1252' ), $row );

			// Map columns based on headers
			$org        = isset( $col_map['Organisation'] ) ? trim( $row[ $col_map['Organisation'] ] ?? '' ) : '';
			$last_name  = isset( $col_map['Nom'] ) ? trim( $row[ $col_map['Nom'] ] ?? '' ) : '';
			$first_name = isset( $col_map['Prenom'] ) ? trim( $row[ $col_map['Prenom'] ] ?? '' ) : '';
			$role       = isset( $col_map['Role'] ) ? trim( $row[ $col_map['Role'] ] ?? '' ) : '';
			$email      = isset( $col_map['Email'] ) ? trim( $row[ $col_map['Email'] ] ?? '' ) : '';

			$refus = '0';
			if ( isset( $col_map['Refus mailing'] ) ) {
				$refus = trim( mb_strtoupper( (string) ( $row[ $col_map['Refus mailing'] ] ?? '' ), 'UTF-8' ) ) === 'O' ? '1' : '0';
			}

			$addr1    = isset( $col_map['Adresse'] ) ? trim( $row[ $col_map['Adresse'] ] ?? '' ) : '';
			$addr2    = isset( $col_map['Complement'] ) ? trim( $row[ $col_map['Complement'] ] ?? '' ) : '';
			$postcode = isset( $col_map['Code Postal'] ) ? trim( $row[ $col_map['Code Postal'] ] ?? '' ) : '';
			$city     = isset( $col_map['Ville'] ) ? trim( $row[ $col_map['Ville'] ] ?? '' ) : '';

			if ( empty( $last_name ) && empty( $first_name ) && empty( $org ) ) {
				continue;
			}

			// Deduplication Logic
			$args = array(
				'post_type'      => 'dame_contact',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( 'relation' => 'AND' ),
			);

			if ( empty( $org ) ) {
				$args['meta_query'][] = array(
					'key'     => '_dame_contact_last_name',
					'value'   => $last_name,
					'compare' => '=',
				);
				$args['meta_query'][] = array(
					'key'     => '_dame_contact_first_name',
					'value'   => $first_name,
					'compare' => '=',
				);
				$args['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'     => '_dame_contact_organization',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_dame_contact_organization',
						'value'   => '',
						'compare' => '=',
					),
				);
			} else {
				$args['meta_query'][] = array(
					'key'     => '_dame_contact_organization',
					'value'   => $org,
					'compare' => '=',
				);
				$args['meta_query'][] = array(
					'key'     => '_dame_contact_last_name',
					'value'   => $last_name,
					'compare' => '=',
				);
				$args['meta_query'][] = array(
					'key'     => '_dame_contact_first_name',
					'value'   => $first_name,
					'compare' => '=',
				);
			}

			$ids     = get_posts( $args );
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
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $new_title,
					)
				);
				++$updated;
			} else {
				$post_id = wp_insert_post(
					array(
						'post_type'   => 'dame_contact',
						'post_title'  => $new_title,
						'post_status' => 'publish',
					)
				);
				++$created;
			}

			if ( $post_id ) {
				update_post_meta( $post_id, '_dame_contact_organization', $org );
				update_post_meta( $post_id, '_dame_contact_last_name', $formatted_last );
				update_post_meta( $post_id, '_dame_contact_first_name', $formatted_first );
				update_post_meta( $post_id, '_dame_contact_role', $role );
				update_post_meta( $post_id, '_dame_contact_email', sanitize_email( $email ) );
				update_post_meta( $post_id, '_dame_contact_no_emails', $refus );
				update_post_meta( $post_id, '_dame_contact_address_1', $addr1 );
				update_post_meta( $post_id, '_dame_contact_address_2', $addr2 );
				update_post_meta( $post_id, '_dame_contact_postcode', $postcode );
				update_post_meta( $post_id, '_dame_contact_city', $city );

				// Enrichment: Dept & Region
				$dept_code = substr( $postcode, 0, 2 );
				if ( strlen( $postcode ) >= 3 ) {
					if ( strpos( $postcode, '20' ) === 0 ) {
						$dept_code = (int) substr( $postcode, 2, 1 ) <= 1 ? '2A' : '2B';
					} elseif ( strpos( $postcode, '97' ) === 0 || strpos( $postcode, '988' ) === 0 ) {
						$dept_code = substr( $postcode, 0, 3 );
					} elseif ( strpos( $postcode, '980' ) === 0 ) {
						$dept_code = '06';
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

		$this->add_admin_notice(
			sprintf(
				/* translators: 1: Count of created contacts, 2: Count of updated contacts */
				__( 'Import terminé : %1$d contacts créés, %2$d mis à jour.', 'dame' ),
				$created,
				$updated
			)
		);
	}

	/**
	 * Builds an index of all adherents (emails, normalized names, and licenses) for fast matching.
	 *
	 * @return array{
	 *     emails: array<string, array{id: int, name: string}>,
	 *     names: array<string, array{id: int, name: string}>,
	 *     licenses: array<string, array{id: int, name: string}>
	 * }
	 */
	/**
	 * Builds an index of all adherents (emails, normalized names, and licenses) for fast matching.
	 *
	 * @return array{
	 *     emails: array<string, array{id: int, name: string, detail: string}>,
	 *     names: array<string, array{id: int, name: string, detail: string}>,
	 *     licenses: array<string, array{id: int, name: string, detail: string}>
	 * }
	 */
	public static function get_adherents_matching_index(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT p.ID as post_id, pm.meta_key, pm.meta_value
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE p.post_type = 'adherent'
			   AND p.post_status = 'publish'
			   AND pm.meta_key IN (
				   '_dame_email', '_dame_legal_rep_1_email', '_dame_legal_rep_2_email',
				   '_dame_last_name', '_dame_birth_name', '_dame_first_name',
				   '_dame_legal_rep_1_last_name', '_dame_legal_rep_1_first_name',
				   '_dame_legal_rep_2_last_name', '_dame_legal_rep_2_first_name',
				   '_dame_license_number'
			   )"
		);

		$adherents_meta = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$adherents_meta[ (int) $row->post_id ][ $row->meta_key ] = $row->meta_value;
			}
		}

		$emails   = array();
		$names    = array();
		$licenses = array();

		foreach ( $adherents_meta as $post_id => $meta ) {
			$first_name = trim( (string) ( $meta['_dame_first_name'] ?? '' ) );
			$last_name  = trim( (string) ( $meta['_dame_last_name'] ?? ( $meta['_dame_birth_name'] ?? '' ) ) );
			$birth_name = trim( (string) ( $meta['_dame_birth_name'] ?? '' ) );
			$full_name  = trim( Utils::format_lastname( $last_name ) . ' ' . Utils::format_firstname( $first_name ) );
			if ( empty( $full_name ) ) {
				$full_name = sprintf( __( 'Adhérent #%d', 'dame' ), $post_id );
			}

			// 1. Emails
			$possible_emails = array(
				array( $meta['_dame_email'] ?? '', __( 'Email adhérent', 'dame' ) ),
				array( $meta['_dame_legal_rep_1_email'] ?? '', __( 'Email Resp. Légal 1', 'dame' ) ),
				array( $meta['_dame_legal_rep_2_email'] ?? '', __( 'Email Resp. Légal 2', 'dame' ) ),
			);
			foreach ( $possible_emails as $em_data ) {
				$clean_em = mb_strtolower( trim( (string) $em_data[0] ) );
				if ( ! empty( $clean_em ) && is_email( $clean_em ) ) {
					$emails[ $clean_em ] = array(
						'id'     => $post_id,
						'name'   => $full_name,
						'detail' => sprintf( '%s (%s)', $em_data[1], $clean_em ),
					);
				}
			}

			// 2. Normalized Names
			$name_sources = array();
			if ( ! empty( $last_name ) && ! empty( $first_name ) && mb_strlen( $last_name ) >= 2 && mb_strlen( $first_name ) >= 2 ) {
				$name_sources[] = array( $last_name, $first_name, sprintf( __( 'Adhérent : %s %s', 'dame' ), $last_name, $first_name ) );
			}
			if ( ! empty( $birth_name ) && ! empty( $first_name ) && $birth_name !== $last_name && mb_strlen( $birth_name ) >= 2 && mb_strlen( $first_name ) >= 2 ) {
				$name_sources[] = array( $birth_name, $first_name, sprintf( __( 'Nom de naissance : %s %s', 'dame' ), $birth_name, $first_name ) );
			}
			$rep1_l = trim( (string) ( $meta['_dame_legal_rep_1_last_name'] ?? '' ) );
			$rep1_f = trim( (string) ( $meta['_dame_legal_rep_1_first_name'] ?? '' ) );
			if ( ! empty( $rep1_l ) && ! empty( $rep1_f ) && mb_strlen( $rep1_l ) >= 2 && mb_strlen( $rep1_f ) >= 2 ) {
				$name_sources[] = array( $rep1_l, $rep1_f, sprintf( __( 'Resp. Légal 1 : %s %s', 'dame' ), $rep1_l, $rep1_f ) );
			}
			$rep2_l = trim( (string) ( $meta['_dame_legal_rep_2_last_name'] ?? '' ) );
			$rep2_f = trim( (string) ( $meta['_dame_legal_rep_2_first_name'] ?? '' ) );
			if ( ! empty( $rep2_l ) && ! empty( $rep2_f ) && mb_strlen( $rep2_l ) >= 2 && mb_strlen( $rep2_f ) >= 2 ) {
				$name_sources[] = array( $rep2_l, $rep2_f, sprintf( __( 'Resp. Légal 2 : %s %s', 'dame' ), $rep2_l, $rep2_f ) );
			}

			foreach ( $name_sources as $src ) {
				$key1 = Utils::normalize_name( $src[0] . $src[1] );
				$key2 = Utils::normalize_name( $src[1] . $src[0] );
				if ( mb_strlen( $key1 ) >= 4 ) {
					$names[ $key1 ] = array(
						'id'     => $post_id,
						'name'   => $full_name,
						'detail' => $src[2],
					);
				}
				if ( mb_strlen( $key2 ) >= 4 ) {
					$names[ $key2 ] = array(
						'id'     => $post_id,
						'name'   => $full_name,
						'detail' => $src[2],
					);
				}
			}

			// 3. Licenses
			$raw_lic = trim( (string) ( $meta['_dame_license_number'] ?? '' ) );
			if ( ! empty( $raw_lic ) ) {
				$extracted = Utils::extract_license_numbers( $raw_lic );
				if ( empty( $extracted ) ) {
					$extracted[] = mb_strtoupper( $raw_lic, 'UTF-8' );
				}
				foreach ( $extracted as $lic_code ) {
					$licenses[ $lic_code ] = array(
						'id'     => $post_id,
						'name'   => $full_name,
						'detail' => sprintf( __( 'Licence %s', 'dame' ), $lic_code ),
					);
				}
			}
		}

		return array(
			'emails'   => $emails,
			'names'    => $names,
			'licenses' => $licenses,
		);
	}

	/**
	 * Detects contacts that match an adherent (by email or normalized full name).
	 *
	 * @return array<int, array{
	 *     contact_id: int,
	 *     contact_name: string,
	 *     contact_email: string,
	 *     contact_org: string,
	 *     categories: array<int, string>,
	 *     adherent_id: int,
	 *     adherent_name: string,
	 *     match_reason: string
	 * }>
	 */
	public static function get_contact_adherent_duplicates(): array {
		global $wpdb;

		$index = self::get_adherents_matching_index();

		$contacts_rows = $wpdb->get_results(
			"SELECT p.ID as post_id, pm.meta_key, pm.meta_value
			 FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE p.post_type = 'dame_contact'
			   AND p.post_status = 'publish'
			   AND pm.meta_key IN ('_dame_contact_email', '_dame_contact_last_name', '_dame_contact_first_name', '_dame_contact_organization')"
		);

		$contacts = array();
		if ( is_array( $contacts_rows ) ) {
			foreach ( $contacts_rows as $row ) {
				$contacts[ (int) $row->post_id ][ $row->meta_key ] = $row->meta_value;
			}
		}

		$duplicates = array();

		foreach ( $contacts as $contact_id => $meta ) {
			$email = mb_strtolower( trim( (string) ( $meta['_dame_contact_email'] ?? '' ) ) );
			$last  = trim( (string) ( $meta['_dame_contact_last_name'] ?? '' ) );
			$first = trim( (string) ( $meta['_dame_contact_first_name'] ?? '' ) );
			$org   = trim( (string) ( $meta['_dame_contact_organization'] ?? '' ) );

			$contact_name = trim( Utils::format_lastname( $last ) . ' ' . Utils::format_firstname( $first ) );
			if ( empty( $contact_name ) ) {
				$contact_name = sprintf( __( 'Contact #%d', 'dame' ), $contact_id );
			}

			$matched       = false;
			$adherent_id   = 0;
			$adherent_name = '';
			$match_reason  = '';

			// Check 1: Email
			if ( ! empty( $email ) && isset( $index['emails'][ $email ] ) ) {
				$matched       = true;
				$adherent_id   = $index['emails'][ $email ]['id'];
				$adherent_name = $index['emails'][ $email ]['name'];
				$match_reason  = $index['emails'][ $email ]['detail'];
			}

			// Check 2: Nom + Prénom
			if ( ! $matched && ! empty( $last ) && ! empty( $first ) && mb_strlen( $last ) >= 2 && mb_strlen( $first ) >= 2 ) {
				$key1 = Utils::normalize_name( $last . $first );
				$key2 = Utils::normalize_name( $first . $last );

				if ( mb_strlen( $key1 ) >= 4 && isset( $index['names'][ $key1 ] ) ) {
					$matched       = true;
					$adherent_id   = $index['names'][ $key1 ]['id'];
					$adherent_name = $index['names'][ $key1 ]['name'];
					$match_reason  = $index['names'][ $key1 ]['detail'];
				} elseif ( mb_strlen( $key2 ) >= 4 && isset( $index['names'][ $key2 ] ) ) {
					$matched       = true;
					$adherent_id   = $index['names'][ $key2 ]['id'];
					$adherent_name = $index['names'][ $key2 ]['name'];
					$match_reason  = $index['names'][ $key2 ]['detail'];
				}
			}

			if ( $matched ) {
				// Get assigned categories
				$terms      = wp_get_object_terms( $contact_id, 'dame_contact_type', array( 'fields' => 'names' ) );
				$categories = ( ! is_wp_error( $terms ) && is_array( $terms ) ) ? array_values( array_map( 'strval', $terms ) ) : array();

				$duplicates[] = array(
					'contact_id'    => $contact_id,
					'contact_name'  => $contact_name,
					'contact_email' => $email,
					'contact_org'   => $org,
					'categories'    => $categories,
					'adherent_id'   => $adherent_id,
					'adherent_name' => $adherent_name,
					'match_reason'  => $match_reason,
				);
			}
		}

		return $duplicates;
	}

	/**
	 * Deletes selected contact duplicates.
	 */
	private function delete_contact_duplicates(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in handle_manual_actions.
		$selected = isset( $_POST['selected_contacts'] ) && is_array( $_POST['selected_contacts'] ) ? array_map( 'absint', $_POST['selected_contacts'] ) : array();
		$selected = array_filter( $selected );

		if ( empty( $selected ) ) {
			$this->add_admin_notice( __( 'Aucun contact sélectionné pour la suppression.', 'dame' ), 'warning' );
			return;
		}

		$deleted = 0;
		foreach ( $selected as $contact_id ) {
			if ( 'dame_contact' === get_post_type( $contact_id ) ) {
				if ( wp_delete_post( $contact_id, true ) ) {
					++$deleted;
				}
			}
		}

		$this->add_admin_notice(
			sprintf(
				/* translators: %d: Number of deleted contacts */
				__( '%d contacts ont été supprimés avec succès.', 'dame' ),
				$deleted
			)
		);
	}

	/**
	 * Handle HelloAsso tournament participants import from CSV with multi-criteria matching.
	 */
	private function import_csv_helloasso_contacts(): void {
		global $wpdb;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in handle_manual_actions.
		$type_slug = isset( $_POST['contact_type'] ) ? sanitize_key( wp_unslash( $_POST['contact_type'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in handle_manual_actions.
		if ( empty( $type_slug ) || ! isset( $_FILES['dame_import_helloasso_file'] ) || ! is_array( $_FILES['dame_import_helloasso_file'] ) || empty( $_FILES['dame_import_helloasso_file']['tmp_name'] ) || ( isset( $_FILES['dame_import_helloasso_file']['error'] ) && UPLOAD_ERR_OK !== $_FILES['dame_import_helloasso_file']['error'] ) ) {
			$this->add_admin_notice( __( 'Données invalides pour l\'import HelloAsso.', 'dame' ), 'error' );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded temp file path verified in handle_manual_actions.
		$raw_tmp  = isset( $_FILES['dame_import_helloasso_file']['tmp_name'] ) ? $_FILES['dame_import_helloasso_file']['tmp_name'] : '';
		$tmp_file = sanitize_text_field( wp_unslash( $raw_tmp ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Local CSV import file handle.
		$handle = fopen( $tmp_file, 'r' );
		if ( ! $handle ) {
			$this->add_admin_notice( __( 'Impossible d\'ouvrir le fichier CSV HelloAsso.', 'dame' ), 'error' );
			return;
		}

		// Read headers
		$headers = fgetcsv( $handle, 0, ';', '"', '\\' );
		if ( ! $headers ) {
			$this->add_admin_notice( __( 'Impossible de lire l\'en-tête du fichier CSV HelloAsso.', 'dame' ), 'error' );
			fclose( $handle );
			return;
		}

		// Convert headers to UTF-8 and clean BOM
		$headers = array_map( fn( $h ) => mb_convert_encoding( (string) $h, 'UTF-8', 'auto' ), $headers );
		if ( isset( $headers[0] ) ) {
			$headers[0] = preg_replace( '/^\x{FEFF}/u', '', $headers[0] );
		}

		$col_map = array_flip( $headers );

		// Find potential license column in headers
		$license_col_idx = null;
		foreach ( $col_map as $header_name => $idx ) {
			$h_lower = mb_strtolower( (string) $header_name, 'UTF-8' );
			if ( str_contains( $h_lower, 'licence' ) || str_contains( $h_lower, 'fide' ) || str_contains( $h_lower, 'ffe' ) ) {
				$license_col_idx = $idx;
				break;
			}
		}

		// 1. Fetch multi-criteria matching index for all adherents
		$matching_index = self::get_adherents_matching_index();

		$created           = 0;
		$updated           = 0;
		$skipped_adherents = 0;
		$processed_emails  = array();

		while ( ( $row = fgetcsv( $handle, 0, ';', '"', '\\' ) ) !== false ) {
			// Convert row to UTF-8
			$row = array_map( fn( $val ) => mb_convert_encoding( (string) $val, 'UTF-8', 'auto' ), $row );

			// Check order status if present
			$status_idx = $col_map['Statut de la commande'] ?? null;
			if ( null !== $status_idx && isset( $row[ $status_idx ] ) ) {
				$order_status = trim( $row[ $status_idx ] );
				if ( ! empty( $order_status ) && 0 !== strcasecmp( $order_status, 'Validé' ) && 0 !== strcasecmp( $order_status, 'Valide' ) ) {
					continue;
				}
			}

			// Extract email (Email payeur)
			$raw_email = isset( $col_map['Email payeur'] ) ? trim( $row[ $col_map['Email payeur'] ] ?? '' ) : '';
			$email     = sanitize_email( $raw_email );
			if ( empty( $email ) || ! is_email( $email ) ) {
				continue;
			}

			$email_lower = mb_strtolower( $email );

			// Deduplication in current batch
			if ( isset( $processed_emails[ $email_lower ] ) ) {
				continue;
			}
			$processed_emails[ $email_lower ] = true;

			// Extract identity fields
			$payer_last  = isset( $col_map['Nom payeur'] ) ? trim( $row[ $col_map['Nom payeur'] ] ?? '' ) : '';
			$payer_first = isset( $col_map['Prénom payeur'] ) ? trim( $row[ $col_map['Prénom payeur'] ] ?? '' ) : '';
			$part_last   = isset( $col_map['Nom participant'] ) ? trim( $row[ $col_map['Nom participant'] ] ?? '' ) : '';
			$part_first  = isset( $col_map['Prénom participant'] ) ? trim( $row[ $col_map['Prénom participant'] ] ?? '' ) : '';
			$org         = isset( $col_map['Raison sociale'] ) ? trim( $row[ $col_map['Raison sociale'] ] ?? '' ) : '';
			$raw_license = ( null !== $license_col_idx && isset( $row[ $license_col_idx ] ) ) ? trim( $row[ $license_col_idx ] ) : '';

			$is_adherent_matched = false;

			// Check A: Email
			if ( isset( $matching_index['emails'][ $email_lower ] ) ) {
				$is_adherent_matched = true;
			}

			// Check B: Nom + Prénom (Participant ou Payeur)
			if ( ! $is_adherent_matched ) {
				$candidate_names = array();
				if ( ! empty( $part_last ) && ! empty( $part_first ) ) {
					$candidate_names[] = Utils::normalize_name( $part_last . $part_first );
					$candidate_names[] = Utils::normalize_name( $part_first . $part_last );
				}
				if ( ! empty( $payer_last ) && ! empty( $payer_first ) ) {
					$candidate_names[] = Utils::normalize_name( $payer_last . $payer_first );
					$candidate_names[] = Utils::normalize_name( $payer_first . $payer_last );
				}

				foreach ( $candidate_names as $c_name ) {
					if ( ! empty( $c_name ) && isset( $matching_index['names'][ $c_name ] ) ) {
						$is_adherent_matched = true;
						break;
					}
				}
			}

			// Check C: Licence FFE / FIDE
			if ( ! $is_adherent_matched && ! empty( $raw_license ) ) {
				$extracted_lics = Utils::extract_license_numbers( $raw_license );
				foreach ( $extracted_lics as $lic_code ) {
					if ( isset( $matching_index['licenses'][ $lic_code ] ) ) {
						$is_adherent_matched = true;
						break;
					}
				}
			}

			if ( $is_adherent_matched ) {
				++$skipped_adherents;
				continue;
			}

			$last_name  = ! empty( $payer_last ) ? $payer_last : $part_last;
			$first_name = ! empty( $payer_first ) ? $payer_first : $part_first;

			// Check if contact already exists in dame_contact by email
			$existing_contact_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT p.ID 
					 FROM {$wpdb->posts} p
					 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
					 WHERE p.post_type = 'dame_contact'
					   AND p.post_status != 'trash'
					   AND pm.meta_key = '_dame_contact_email'
					   AND LOWER(pm.meta_value) = %s
					 LIMIT 1",
					$email_lower
				)
			);

			if ( ! empty( $existing_contact_id ) ) {
				// Contact already exists: append target category without overriding existing categories
				wp_set_object_terms( (int) $existing_contact_id, $type_slug, 'dame_contact_type', true );
				++$updated;
			} else {
				// Create new contact
				$formatted_last  = Utils::format_lastname( $last_name );
				$formatted_first = Utils::format_firstname( $first_name );
				$base_name       = trim( $formatted_last . ' ' . $formatted_first );

				if ( ! empty( $org ) ) {
					$new_title = $org . ( ! empty( $base_name ) ? ' (' . $base_name . ')' : '' );
				} else {
					$new_title = $base_name ?: __( 'Contact sans nom', 'dame' );
				}

				$post_id = wp_insert_post(
					array(
						'post_type'   => 'dame_contact',
						'post_title'  => $new_title,
						'post_status' => 'publish',
					)
				);

				if ( $post_id && ! is_wp_error( $post_id ) ) {
					update_post_meta( $post_id, '_dame_contact_organization', $org );
					update_post_meta( $post_id, '_dame_contact_last_name', $formatted_last );
					update_post_meta( $post_id, '_dame_contact_first_name', $formatted_first );
					update_post_meta( $post_id, '_dame_contact_role', __( 'Participant tournoi', 'dame' ) );
					update_post_meta( $post_id, '_dame_contact_email', $email );
					update_post_meta( $post_id, '_dame_contact_no_emails', '0' );

					// Assign taxonomy (append = true)
					wp_set_object_terms( $post_id, $type_slug, 'dame_contact_type', true );

					++$created;
				}
			}
		}

		fclose( $handle );

		$this->add_admin_notice(
			sprintf(
				/* translators: 1: Count of created contacts, 2: Count of updated contacts, 3: Count of skipped adherents */
				__( 'Import HelloAsso terminé : %1$d contacts créés, %2$d associés à la catégorie, %3$d adhérents existants ignorés.', 'dame' ),
				$created,
				$updated,
				$skipped_adherents
			)
		);
	}

	/*
	 * -------------------------------------------------------------------------
	 * ADHERENTS - CSV
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Export adherents to CSV file.
	 */
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
			__( 'Nom de naissance', 'dame' ),
			__( 'Nom d\'usage', 'dame' ),
			__( 'Prénom', 'dame' ),
			__( 'Date de naissance', 'dame' ),
			__( 'Lieu de naissance', 'dame' ),
			__( 'Sexe', 'dame' ),
			__( 'Profession', 'dame' ),
			__( 'Adresse email', 'dame' ),
			__( 'Numéro de téléphone', 'dame' ),
			__( 'Adresse', 'dame' ),
			__( 'Complément', 'dame' ),
			__( 'Code Postal', 'dame' ),
			__( 'Ville', 'dame' ),
			__( 'Pays', 'dame' ),
			__( 'Numéro de licence', 'dame' ),
			__( 'Type de licence', 'dame' ),
			__( 'Ecole d\'échecs (O/N)', 'dame' ),
			__( 'Pôle excellence (O/N)', 'dame' ),
			__( 'Bénévole (O/N)', 'dame' ),
			__( 'Elu local (O/N)', 'dame' ),
			__( 'Arbitre', 'dame' ),
			__( 'Représentant légal 1 - Nom de naissance', 'dame' ),
			__( 'Représentant légal 1 - Prénom', 'dame' ),
			__( 'Représentant légal 1 - Profession', 'dame' ),
			__( 'Représentant légal 1 - Email', 'dame' ),
			__( 'Représentant légal 1 - Téléphone', 'dame' ),
			__( 'Représentant légal 1 - Adresse', 'dame' ),
			__( 'Représentant légal 1 - Complément', 'dame' ),
			__( 'Représentant légal 1 - Code Postal', 'dame' ),
			__( 'Représentant légal 1 - Ville', 'dame' ),
			__( 'Représentant légal 2 - Nom de naissance', 'dame' ),
			__( 'Représentant légal 2 - Prénom', 'dame' ),
			__( 'Représentant légal 2 - Profession', 'dame' ),
			__( 'Représentant légal 2 - Email', 'dame' ),
			__( 'Représentant légal 2 - Téléphone', 'dame' ),
			__( 'Représentant légal 2 - Adresse', 'dame' ),
			__( 'Représentant légal 2 - Complément', 'dame' ),
			__( 'Représentant légal 2 - Code Postal', 'dame' ),
			__( 'Représentant légal 2 - Ville', 'dame' ),
			__( 'Autre téléphone', 'dame' ),
			__( 'Taille vêtements', 'dame' ),
			__( 'Allergies', 'dame' ),
			__( 'Régime alimentaire', 'dame' ),
			__( 'Moyen de locomotion', 'dame' ),
		);

		// 3. Add dynamic season headers.
		if ( ! is_wp_error( $all_seasons ) ) {
			foreach ( $all_seasons as $season ) {
				/* translators: %s: Season name */
				$headers[] = sprintf( __( 'Adhérent %s', 'dame' ), $season->name );
			}
		}

		fputcsv( $output, $headers, ';', '"', '\\' );

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

				// Format dates (fixed calendar dates, no timezone shift wanted).
				$birth_date           = get_post_meta( $post_id, '_dame_birth_date', true );
				$formatted_birth_date = '';
				if ( ! empty( $birth_date ) ) {
					$parts = explode( '-', $birth_date );
					if ( count( $parts ) === 3 ) {
						$formatted_birth_date = sprintf( '%02d/%02d/%04d', $parts[2], $parts[1], $parts[0] );
					}
				}

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

				fputcsv( $output, $row, ';', '"', '\\' );
			}
			wp_reset_postdata();
		}

		fclose( $output );
		exit;
	}

	/**
	 * Import adherents from uploaded CSV file.
	 */
	private function import_csv_adherents(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in handle_manual_actions.
		if ( ! isset( $_FILES['dame_import_csv_file'] ) || ! is_array( $_FILES['dame_import_csv_file'] ) || empty( $_FILES['dame_import_csv_file']['tmp_name'] ) || ( isset( $_FILES['dame_import_csv_file']['error'] ) && UPLOAD_ERR_OK !== $_FILES['dame_import_csv_file']['error'] ) ) {
			$this->add_admin_notice( __( 'Erreur lors du téléversement du fichier.', 'dame' ), 'error' );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded temp file path verified in handle_manual_actions.
		$raw_tmp   = isset( $_FILES['dame_import_csv_file']['tmp_name'] ) ? $_FILES['dame_import_csv_file']['tmp_name'] : '';
		$tmp_file  = sanitize_text_field( wp_unslash( $raw_tmp ) );
		$mime_type = mime_content_type( $tmp_file );

		if ( 'text/plain' !== $mime_type && 'text/csv' !== $mime_type ) {
			$this->add_admin_notice( __( 'Le fichier téléversé n\'est pas un fichier CSV valide.', 'dame' ), 'error' );
			return;
		}

		// Increase execution time
		set_time_limit( 300 );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Local CSV file handle.
		$handle = fopen( $tmp_file, 'r' );
		if ( false === $handle ) {
			$this->add_admin_notice( __( 'Impossible d\'ouvrir le fichier téléversé.', 'dame' ), 'error' );
			return;
		}

		// Read header row and map columns
		$header = fgetcsv( $handle, 0, ';', '"', '\\' );
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
			'Nom de naissance',
			'Nom d\'usage',
			'Prénom',
			'Date de naissance',
			'Lieu de naissance',
			'Sexe',
			'Profession',
			'Adresse email',
			'Numéro de téléphone',
			'Adresse',
			'Complément',
			'Code Postal',
			'Ville',
			'Pays',
			'Numéro de licence',
			'Type de licence',
			'Ecole d\'échecs (O/N)',
			'Pôle excellence (O/N)',
			'Bénévole (O/N)',
			'Elu local (O/N)',
			'Arbitre',
			'Représentant légal 1 - Nom de naissance',
			'Représentant légal 1 - Prénom',
			'Représentant légal 1 - Profession',
			'Représentant légal 1 - Email',
			'Représentant légal 1 - Téléphone',
			'Représentant légal 1 - Adresse',
			'Représentant légal 1 - Complément',
			'Représentant légal 1 - Code Postal',
			'Représentant légal 1 - Ville',
			'Représentant légal 2 - Nom de naissance',
			'Représentant légal 2 - Prénom',
			'Représentant légal 2 - Profession',
			'Représentant légal 2 - Email',
			'Représentant légal 2 - Téléphone',
			'Représentant légal 2 - Adresse',
			'Représentant légal 2 - Complément',
			'Représentant légal 2 - Code Postal',
			'Représentant légal 2 - Ville',
			'Autre téléphone',
			'Taille vêtements',
			'Allergies',
			'Régime alimentaire',
			'Moyen de locomotion',
			'Statut adhésion',
		);
		$col_map          = array_flip( $header );

		// Data mapping from CSV columns to post meta keys
		$meta_mapping = array(
			'Nom de naissance'                        => '_dame_birth_name',
			'Nom d\'usage'                            => '_dame_last_name',
			'Prénom'                                  => '_dame_first_name',
			'Date de naissance'                       => '_dame_birth_date',
			'Lieu de naissance'                       => '_dame_birth_city',
			'Sexe'                                    => '_dame_sexe',
			'Profession'                              => '_dame_profession',
			'Adresse email'                           => '_dame_email',
			'Numéro de téléphone'                     => '_dame_phone_number',
			'Adresse'                                 => '_dame_address_1',
			'Complément'                              => '_dame_address_2',
			'Code Postal'                             => '_dame_postal_code',
			'Ville'                                   => '_dame_city',
			'Pays'                                    => '_dame_country',
			'Numéro de licence'                       => '_dame_license_number',
			'Type de licence'                         => '_dame_license_type',
			'Ecole d\'échecs (O/N)'                   => '_dame_is_junior',
			'Pôle excellence (O/N)'                   => '_dame_is_pole_excellence',
			'Bénévole (O/N)'                          => '_dame_is_benevole',
			'Elu local (O/N)'                         => '_dame_is_elu_local',
			'Arbitre'                                 => '_dame_arbitre_level',
			'Représentant légal 1 - Nom de naissance' => '_dame_legal_rep_1_last_name',
			'Représentant légal 1 - Prénom'           => '_dame_legal_rep_1_first_name',
			'Représentant légal 1 - Profession'       => '_dame_legal_rep_1_profession',
			'Représentant légal 1 - Email'            => '_dame_legal_rep_1_email',
			'Représentant légal 1 - Téléphone'        => '_dame_legal_rep_1_phone',
			'Représentant légal 1 - Adresse'          => '_dame_legal_rep_1_address_1',
			'Représentant légal 1 - Complément'       => '_dame_legal_rep_1_address_2',
			'Représentant légal 1 - Code Postal'      => '_dame_legal_rep_1_postal_code',
			'Représentant légal 1 - Ville'            => '_dame_legal_rep_1_city',
			'Représentant légal 2 - Nom de naissance' => '_dame_legal_rep_2_last_name',
			'Représentant légal 2 - Prénom'           => '_dame_legal_rep_2_first_name',
			'Représentant légal 2 - Profession'       => '_dame_legal_rep_2_profession',
			'Représentant légal 2 - Email'            => '_dame_legal_rep_2_email',
			'Représentant légal 2 - Téléphone'        => '_dame_legal_rep_2_phone',
			'Représentant légal 2 - Adresse'          => '_dame_legal_rep_2_address_1',
			'Représentant légal 2 - Complément'       => '_dame_legal_rep_2_address_2',
			'Représentant légal 2 - Code Postal'      => '_dame_legal_rep_2_postal_code',
			'Représentant légal 2 - Ville'            => '_dame_legal_rep_2_city',
			'Autre téléphone'                         => '_dame_autre_telephone',
			'Taille vêtements'                        => '_dame_taille_vetements',
			'Allergies'                               => '_dame_allergies',
			'Régime alimentaire'                      => '_dame_diet',
			'Moyen de locomotion'                     => '_dame_transport',
			'Statut adhésion'                         => '_dame_membership_status',
		);

		$imported_count            = 0;
		$department_region_mapping = Data_Provider::get_department_region_mapping();
		$all_seasons               = get_terms(
			array(
				'taxonomy'   => 'dame_saison_adhesion',
				'hide_empty' => false,
			)
		);

		while ( ( $row = fgetcsv( $handle, 0, ';', '"', '\\' ) ) !== false ) {
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
			$season_data = array();
			if ( ! is_wp_error( $all_seasons ) ) {
				foreach ( $all_seasons as $season ) {
					$season_header = 'Adhérent ' . $season->name;
					$col_index     = isset( $col_map[ $season_header ] ) ? $col_map[ $season_header ] : -1;
					if ( $col_index !== -1 && isset( $row[ $col_index ] ) ) {
						$season_data[ $season->slug ] = trim( mb_strtoupper( $row[ $col_index ], 'UTF-8' ) ) === 'O';
					}
				}
			}

			$first_name = $member_data['Prénom'];
			$last_name  = $member_data['Nom d\'usage'];
			$birth_name = $member_data['Nom de naissance'];

			if ( empty( $first_name ) || ( empty( $last_name ) && empty( $birth_name ) ) ) {
				continue; // Skip rows without a name
			}

			$post_id = 0;
			$email   = $member_data['Adresse email'];
			$license = $member_data['Numéro de licence'];

			$effective_last_name = ! empty( $last_name ) ? $last_name : $birth_name;
			$post_title          = \DAME\Core\Utils::format_lastname( (string) $effective_last_name ) . ' ' . \DAME\Core\Utils::format_firstname( (string) $first_name );

			// Reconciliation
			$query_args = array(
				'post_type'      => 'adherent',
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			);

			if ( ! empty( $license ) ) {
				$query_args['meta_query'] = array(
					array(
						'key'     => '_dame_license_number',
						'value'   => $license,
						'compare' => '=',
					),
				);
				$posts                    = get_posts( $query_args );
				if ( ! empty( $posts ) ) {
					$post_id = $posts[0];
				}
			}

			if ( ! $post_id && ! empty( $email ) ) {
				$query_args['meta_query'] = array(
					array(
						'key'     => '_dame_email',
						'value'   => $email,
						'compare' => '=',
					),
				);
				$posts                    = get_posts( $query_args );
				if ( ! empty( $posts ) ) {
					$post_id = $posts[0];
				}
			}

			if ( ! $post_id ) {
				$query_args['title'] = $post_title;
				unset( $query_args['meta_query'] );
				$posts = get_posts( $query_args );
				if ( ! empty( $posts ) ) {
					$post_id = $posts[0];
				}
			}

			if ( ! $post_id ) {
				$post_data = array(
					'post_title'  => $post_title,
					'post_type'   => 'adherent',
					'post_status' => 'publish',
				);
				$post_id   = wp_insert_post( $post_data );
			} else {
				// Update title in case name changed
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $post_title,
					)
				);
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
						$status_key       = 'N'; // Default to 'Non Adhérent'
						$normalized_value = mb_strtoupper( trim( $value ), 'UTF-8' );

						// Handle cases like "Actif (A)" by extracting the key
						if ( preg_match( '/\(([A-Z])\)/', $normalized_value, $matches ) ) {
							$normalized_value = $matches[1];
						}

						$status_map = array(
							'ACTIF'        => 'A',
							'A'            => 'A',
							'EXPIRÉ'       => 'E',
							'EXPIRE'       => 'E',
							'E'            => 'E',
							'ANCIEN'       => 'X',
							'X'            => 'X',
							'NON ADHÉRENT' => 'N',
							'NON ADHERENT' => 'N',
							'N'            => 'N',
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
					$boolean_fields = array(
						'_dame_is_junior',
						'_dame_is_pole_excellence',
						'_dame_is_benevole',
						'_dame_is_elu_local',
					);
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
						} elseif ( strpos( $postal_code, '97' ) === 0 || strpos( $postal_code, '988' ) === 0 ) {
							$department_code = substr( $postal_code, 0, 3 );
						} elseif ( strpos( $postal_code, '980' ) === 0 ) {
							$department_code = '06';
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
						} elseif ( in_array( $season_slug, $current_seasons, true ) ) {
								wp_remove_object_terms( $post_id, $season_slug, 'dame_saison_adhesion' );
						}
					}
				}

				++$imported_count;
			}
		}

		fclose( $handle );

		$message = sprintf(
			/* translators: %d: Count of imported adherents */
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

	/*
	 * -------------------------------------------------------------------------
	 * ADHERENTS - JSON (BACKUP/RESTORE)
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Generate Adherent export data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate_adherent_export_data(): array {
		$data = array(
			'version'          => DAME_VERSION,
			'taxonomy_terms'   => array(),
			'adherents'        => array(),
			'contacts'         => array(),
			'pre_inscriptions' => array(),
			'messages'         => array(),
			'message_tracking' => array(),
			'users'            => array(),
			'options'          => array(),
		);

		global $wpdb;

		// 1. Export Users and Usermeta
		$users = $wpdb->get_results( "SELECT * FROM $wpdb->users", ARRAY_A );
		foreach ( $users as $user ) {
			$meta      = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE user_id = %d", $user['ID'] ), ARRAY_A );
			$user_meta = array();
			foreach ( $meta as $m ) {
				$user_meta[ $m['meta_key'] ][] = $m['meta_value'];
			}
			$data['users'][] = array(
				'data' => $user,
				'meta' => $user_meta,
			);
		}

		// 2. Taxonomies
		foreach ( array( 'dame_saison_adhesion', 'dame_group', 'dame_contact_type' ) as $tax ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $tax,
					'hide_empty' => false,
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_data = array(
						'term_id'          => $term->term_id,
						'term_taxonomy_id' => $term->term_taxonomy_id,
						'name'             => $term->name,
						'slug'             => $term->slug,
						'description'      => $term->description,
						'parent'           => $term->parent,
						'meta_data'        => array(),
					);

					$term_meta = get_term_meta( $term->term_id );
					if ( ! empty( $term_meta ) ) {
						foreach ( $term_meta as $k => $vals ) {
							$term_data['meta_data'][ $k ] = array_map( 'maybe_unserialize', $vals );
						}
					}
					$data['taxonomy_terms'][ $tax ][] = $term_data;
				}
			}
		}

		// Combined Post Types for this section
		$post_types = array( 'adherent', 'dame_contact', 'dame_pre_inscription', 'dame_message' );
		$query      = new WP_Query(
			array(
				'post_type'      => $post_types,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		// Optimisation : Pré-chargement des métadonnées
		if ( ! empty( $query->posts ) ) {
			update_meta_cache( 'post', wp_list_pluck( $query->posts, 'ID' ) );
		}

		foreach ( $query->posts as $post ) {
			$meta = array();
			foreach ( get_post_meta( $post->ID ) as $k => $vals ) {
				$meta[ $k ] = array_map( 'maybe_unserialize', $vals );
			}

			$taxs              = array();
			$object_taxonomies = get_object_taxonomies( $post->post_type );
			foreach ( $object_taxonomies as $tax ) {
				$terms = wp_get_post_terms( $post->ID, $tax, array( 'fields' => 'slugs' ) );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$taxs[ $tax ] = $terms;
				}
			}

			$item = array(
				'ID'            => $post->ID,
				'post_author'   => $post->post_author,
				'post_title'    => $post->post_title,
				'post_content'  => $post->post_content,
				'post_excerpt'  => $post->post_excerpt,
				'post_type'     => $post->post_type,
				'post_status'   => $post->post_status,
				'post_name'     => $post->post_name,
				'post_parent'   => $post->post_parent,
				'post_date'     => $post->post_date,
				'post_date_gmt' => $post->post_date_gmt,
				'menu_order'    => $post->menu_order,
				'meta_data'     => $meta,
				'taxonomies'    => $taxs,
			);

			if ( 'adherent' === $post->post_type ) {
				$data['adherents'][] = $item;
			} elseif ( 'dame_contact' === $post->post_type ) {
				$data['contacts'][] = $item;
			} elseif ( 'dame_pre_inscription' === $post->post_type ) {
				$data['pre_inscriptions'][] = $item;
			} elseif ( 'dame_message' === $post->post_type ) {
				$data['messages'][] = $item;
			}
		}

		// Message logs (envois + ouvertures)
		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_message_opens';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- System backup message tracking query.
		$tracking_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', $table_name ), ARRAY_A );
		if ( is_array( $tracking_data ) ) {
			$data['message_tracking'] = $tracking_data;
		}

		// Options critiques
		$current_season_tag_id = get_option( 'dame_current_season_tag_id' );
		if ( $current_season_tag_id ) {
			$term = get_term( $current_season_tag_id, 'dame_saison_adhesion' );
			if ( $term && ! is_wp_error( $term ) ) {
				$data['options']['dame_current_season_tag_id']   = $term->term_id;
				$data['options']['dame_current_season_tag_slug'] = $term->slug;
			}
		}
		$dame_options = get_option( 'dame_options' );
		if ( is_array( $dame_options ) ) {
			unset( $dame_options['smtp_password'] );
		}
		$data['options']['dame_options'] = $dame_options;

		return $data;
	}

	/**
	 * Export JSON adherents backup file.
	 */
	private function export_json_adherents(): void {
		$data     = $this->generate_adherent_export_data();
		$gz       = gzcompress( (string) wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		$filename = 'dame-adherents-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		ob_clean();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( (string) $gz ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary stream output.
		echo $gz;
		exit;
	}

	/**
	 * Import JSON adherents restore file.
	 */
	private function import_json_adherents(): void {
		if ( ! isset( $_POST['dame_import_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_import_nonce'] ) ), 'dame_import_nonce_action' ) ) {
			return;
		}
		if ( ! isset( $_FILES['dame_import_file'] ) || ! is_array( $_FILES['dame_import_file'] ) || empty( $_FILES['dame_import_file']['tmp_name'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded temp file path.
		$tmp_file = sanitize_text_field( wp_unslash( $_FILES['dame_import_file']['tmp_name'] ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local temp file read.
		$json = gzuncompress( (string) file_get_contents( $tmp_file ) );
		$data = json_decode( (string) $json, true );
		if ( ! $data ) {
			return;
		}

		global $wpdb;
		$post_types   = array( 'adherent', 'dame_contact', 'dame_pre_inscription', 'dame_message' );
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// 1. PURGE
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- System restore purge.
		$posts_to_delete = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM %i WHERE post_type IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( array( $wpdb->posts ), $post_types )
			)
		);
		foreach ( $posts_to_delete as $pid ) {
			wp_delete_post( (int) $pid, true );
		}
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}dame_message_opens" );

		foreach ( array( 'dame_saison_adhesion', 'dame_group', 'dame_contact_type' ) as $tax ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $tax,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $tid ) {
					wp_delete_term( (int) $tid, $tax );
				}
			}
		}

		// 2. RESTORE TAXONOMIES
		foreach ( $data['taxonomy_terms'] ?? array() as $tax => $terms ) {
			foreach ( $terms as $t ) {
				$term_id = (int) $t['term_id'];
				$tt_id   = (int) $t['term_taxonomy_id'];

				// Term check
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->terms WHERE term_id = %d", $term_id ) );
				if ( ! $exists ) {
					$wpdb->insert(
						$wpdb->terms,
						array(
							'term_id'    => $term_id,
							'name'       => $t['name'],
							'slug'       => $t['slug'],
							'term_group' => 0,
						)
					);
				} else {
					$wpdb->update(
						$wpdb->terms,
						array(
							'name' => $t['name'],
							'slug' => $t['slug'],
						),
						array( 'term_id' => $term_id )
					);
				}

				// Taxonomy check
				$tt_exists = $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $tt_id ) );
				if ( ! $tt_exists ) {
					$wpdb->insert(
						$wpdb->term_taxonomy,
						array(
							'term_taxonomy_id' => $tt_id,
							'term_id'          => $term_id,
							'taxonomy'         => $tax,
							'description'      => $t['description'],
							'parent'           => $t['parent'],
							'count'            => 0,
						)
					);
				} else {
					$wpdb->update(
						$wpdb->term_taxonomy,
						array(
							'term_id'     => $term_id,
							'taxonomy'    => $tax,
							'description' => $t['description'],
							'parent'      => $t['parent'],
						),
						array( 'term_taxonomy_id' => $tt_id )
					);
				}

				if ( ! empty( $t['meta_data'] ) ) {
					foreach ( $t['meta_data'] as $k => $v ) {
						update_term_meta( $term_id, $k, $v );
					}
				}
			}
		}

		// 3. RESTORE POSTS
		$max_post_id = 0;
		$all_items   = array_merge( $data['adherents'] ?? array(), $data['contacts'] ?? array(), $data['pre_inscriptions'] ?? array(), $data['messages'] ?? array() );

		foreach ( $all_items as $p ) {
			$pid         = (int) $p['ID'];
			$max_post_id = max( $max_post_id, $pid );

			$post_data = array(
				'ID'                    => $pid,
				'post_author'           => $p['post_author'],
				'post_date'             => $p['post_date'],
				'post_date_gmt'         => $p['post_date_gmt'],
				'post_content'          => $p['post_content'] ?? '',
				'post_title'            => $p['post_title'],
				'post_excerpt'          => $p['post_excerpt'] ?? '',
				'post_status'           => $p['post_status'],
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'post_name'             => $p['post_name'],
				'post_modified'         => $p['post_date'],
				'post_modified_gmt'     => $p['post_date_gmt'],
				'post_parent'           => $p['post_parent'] ?? 0,
				'menu_order'            => $p['menu_order'] ?? 0,
				'post_type'             => $p['post_type'],
				'post_content_filtered' => '',
				'to_ping'               => '',
				'pinged'                => '',
				'guid'                  => '',
			);

			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d", $pid ) ) ) {
				$wpdb->insert( $wpdb->posts, $post_data );
			} else {
				$wpdb->update( $wpdb->posts, $post_data, array( 'ID' => $pid ) );
				// Clean existing meta if updating
				$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $pid ) );
			}

			foreach ( $p['meta_data'] as $k => $vals ) {
				foreach ( $vals as $v ) {
					add_post_meta( $pid, $k, $v, false );
				}
			}
			foreach ( $p['taxonomies'] ?? array() as $tax => $slugs ) {
				wp_set_object_terms( $pid, $slugs, $tax );
			}
		}

		// 4. RESTORE MESSAGE TRACKING
		foreach ( $data['message_tracking'] ?? array() as $mo ) {
			$wpdb->insert(
				"{$wpdb->prefix}dame_message_opens",
				array(
					'message_id'      => $mo['message_id'],
					'recipient_id'    => $mo['recipient_id'] ?? 0,
					'recipient_email' => $mo['recipient_email'] ?? '',
					'email_hash'      => $mo['email_hash'],
					'sent_at'         => $mo['sent_at'] ?? null,
					'opened_at'       => $mo['opened_at'] ?? null,
					'user_ip'         => $mo['user_ip'] ?? null,
				)
			);
		}

		// 5. REALIGN
		if ( $max_post_id > 0 ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = %d", $max_post_id + 1 ) );
		}

		// 6. RESTORE USERS (Upsert logic to avoid locking current admin out)
		$max_user_id     = 0;
		$current_user_id = get_current_user_id();

		foreach ( $data['users'] ?? array() as $u ) {
			$uid         = (int) $u['data']['ID'];
			$max_user_id = max( $max_user_id, $uid );

			// Check if user already exists
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE ID = %d", $uid ) );

			if ( ! $exists ) {
				$wpdb->insert( $wpdb->users, $u['data'] );
			} elseif ( $uid !== $current_user_id ) {
				// Don't update the current user performing the restore to avoid session issues
				$wpdb->update( $wpdb->users, $u['data'], array( 'ID' => $uid ) );
			}

			// Restore User Meta
			// We clear existing meta first (except for current user to be safe)
			if ( $uid !== $current_user_id ) {
				$wpdb->delete( $wpdb->usermeta, array( 'user_id' => $uid ) );
			}

			foreach ( $u['meta'] as $k => $vals ) {
				// Normalize capability and user_level keys to current prefix
				$normalized_key = $k;
				if ( preg_match( '/^(.*)capabilities$/', $k, $matches ) ) {
					$normalized_key = $wpdb->prefix . 'capabilities';
				} elseif ( preg_match( '/^(.*)user_level$/', $k, $matches ) ) {
					$normalized_key = $wpdb->prefix . 'user_level';
				}

				foreach ( $vals as $v ) {
					if ( $uid === $current_user_id ) {
						// For current user, only update keys if they don't exist to avoid breaking session
						if ( ! get_user_meta( $uid, $normalized_key, true ) ) {
							add_user_meta( $uid, $normalized_key, $v, false );
						}
					} else {
						// Use update_user_meta for the first value and add_user_meta for subsequent if multiple (rare for these keys)
						// But here we are iterating over $vals which came from a raw DB query.
						// To preserve the EXACT raw value (which is already serialized in DB),
						// it's safer to use $wpdb->insert to avoid double serialization by WP meta functions.
						$wpdb->insert(
							$wpdb->usermeta,
							array(
								'user_id'    => $uid,
								'meta_key'   => $normalized_key,
								'meta_value' => $v,
							)
						);
					}
				}
			}
		}

		if ( $max_user_id > 0 ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE $wpdb->users AUTO_INCREMENT = %d", $max_user_id + 1 ) );
		}

		// 7. RESTORE OPTIONS
		if ( ! empty( $data['options']['dame_current_season_tag_slug'] ) ) {
			$term = get_term_by( 'slug', $data['options']['dame_current_season_tag_slug'], 'dame_saison_adhesion' );
			if ( $term && ! is_wp_error( $term ) ) {
				update_option( 'dame_current_season_tag_id', $term->term_id );
			}
		} elseif ( ! empty( $data['options']['dame_current_season_tag_id'] ) ) {
			// Fallback to ID if slug not found
			update_option( 'dame_current_season_tag_id', $data['options']['dame_current_season_tag_id'] );
		}

		if ( ! empty( $data['options']['dame_options'] ) ) {
			update_option( 'dame_options', $data['options']['dame_options'] );
		}

		$this->add_admin_notice( 'Restauration des adhérents, contacts et messages terminée avec succès (IDs et réglages conservés).' );
	}

	/*
	 * -------------------------------------------------------------------------
	 * AGENDA - JSON (BACKUP/RESTORE)
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Generate Agenda export data (Events and Polls).
	 *
	 * @return array<string, mixed>
	 */
	public function generate_agenda_export_data(): array {
		$data = array(
			'version'        => DAME_VERSION,
			'posts'          => array(),
			'taxonomy_terms' => array(),
		);

		// Taxonomy Terms
		$terms = get_terms(
			array(
				'taxonomy'   => 'dame_agenda_category',
				'hide_empty' => false,
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$term_data = array(
					'term_id'          => $t->term_id,
					'term_taxonomy_id' => $t->term_taxonomy_id,
					'name'             => $t->name,
					'slug'             => $t->slug,
					'description'      => $t->description,
					'parent'           => $t->parent,
					'meta_data'        => array(),
				);
				$meta      = get_option( 'taxonomy_' . $t->term_id );
				if ( ! empty( $meta ) ) {
					$term_data['meta_data']['dame_taxonomy_meta'] = $meta;
				}
				$data['taxonomy_terms'][] = $term_data;
			}
		}

		// Events and Benevolat
		$post_types = array( 'dame_agenda', 'benevolat', 'benevolat_reponse' );
		$query      = new WP_Query(
			array(
				'post_type'      => $post_types,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		// Optimisation : Pré-chargement des métadonnées
		if ( ! empty( $query->posts ) ) {
			update_meta_cache( 'post', wp_list_pluck( $query->posts, 'ID' ) );
		}

		foreach ( $query->posts as $post ) {
			$meta = array();
			foreach ( get_post_meta( $post->ID ) as $k => $vals ) {
				$meta[ $k ] = array_map( 'maybe_unserialize', $vals );
			}
			$cats = wp_get_post_terms( $post->ID, 'dame_agenda_category', array( 'fields' => 'slugs' ) );

			$data['posts'][] = array(
				'ID'            => $post->ID,
				'post_author'   => $post->post_author,
				'post_date'     => $post->post_date,
				'post_date_gmt' => $post->post_date_gmt,
				'post_content'  => $post->post_content,
				'post_title'    => $post->post_title,
				'post_excerpt'  => $post->post_excerpt,
				'post_status'   => $post->post_status,
				'post_name'     => $post->post_name,
				'post_parent'   => $post->post_parent,
				'menu_order'    => $post->menu_order,
				'post_type'     => $post->post_type,
				'meta_data'     => $meta,
				'categories'    => $cats,
			);
		}

		// Benevolat Votes
		global $wpdb;
		$table_votes = $wpdb->prefix . 'dame_benevolat_votes';
		// Benevolat Votes
		global $wpdb;
		$table_votes = $wpdb->prefix . 'dame_benevolat_votes';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- System backup.
		$votes_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', $table_votes ), ARRAY_A );
		if ( is_array( $votes_data ) ) {
			$data['benevolat_votes'] = $votes_data;
		}

		return $data;
	}

	/**
	 * Export JSON agenda backup file.
	 */
	private function export_json_agenda(): void {
		$data     = $this->generate_agenda_export_data();
		$gz       = gzcompress( (string) wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		$filename = 'dame-agenda-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		ob_clean();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( (string) $gz ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary stream output.
		echo $gz;
		exit;
	}

	/**
	 * Import JSON agenda restore file.
	 */
	private function import_json_agenda(): void {
		if ( ! isset( $_POST['dame_agenda_restore_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_agenda_restore_nonce'] ) ), 'dame_agenda_restore_nonce_action' ) ) {
			return;
		}
		if ( ! isset( $_FILES['dame_agenda_restore_file'] ) || ! is_array( $_FILES['dame_agenda_restore_file'] ) || empty( $_FILES['dame_agenda_restore_file']['tmp_name'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded temp file path.
		$tmp_file = sanitize_text_field( wp_unslash( $_FILES['dame_agenda_restore_file']['tmp_name'] ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local temp file read.
		$json = gzuncompress( (string) file_get_contents( $tmp_file ) );
		$data = json_decode( (string) $json, true );
		if ( ! $data ) {
			return;
		}

		global $wpdb;
		$post_types   = array( 'dame_agenda', 'benevolat', 'benevolat_reponse' );
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// 1. PURGE
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- System restore purge.
		$posts_to_delete = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM %i WHERE post_type IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( array( $wpdb->posts ), $post_types )
			)
		);
		foreach ( $posts_to_delete as $pid ) {
			wp_delete_post( (int) $pid, true );
		}
		$terms = get_terms(
			array(
				'taxonomy'   => 'dame_agenda_category',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		foreach ( $terms as $tid ) {
			delete_option( "taxonomy_$tid" );
			wp_delete_term( (int) $tid, 'dame_agenda_category' ); }
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}dame_benevolat_votes" );

		// 2. RESTORE TAXONOMIES
		foreach ( $data['taxonomy_terms'] ?? array() as $t ) {
			$term_id = (int) $t['term_id'];
			$tt_id   = (int) $t['term_taxonomy_id'];

			// Term check
			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->terms WHERE term_id = %d", $term_id ) ) ) {
				$wpdb->insert(
					$wpdb->terms,
					array(
						'term_id'    => $term_id,
						'name'       => $t['name'],
						'slug'       => $t['slug'],
						'term_group' => 0,
					)
				);
			} else {
				$wpdb->update(
					$wpdb->terms,
					array(
						'name' => $t['name'],
						'slug' => $t['slug'],
					),
					array( 'term_id' => $term_id )
				);
			}

			// Taxonomy check
			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $tt_id ) ) ) {
				$wpdb->insert(
					$wpdb->term_taxonomy,
					array(
						'term_taxonomy_id' => $tt_id,
						'term_id'          => $term_id,
						'taxonomy'         => 'dame_agenda_category',
						'description'      => $t['description'],
						'parent'           => $t['parent'],
						'count'            => 0,
					)
				);
			} else {
				$wpdb->update(
					$wpdb->term_taxonomy,
					array(
						'term_id'     => $term_id,
						'taxonomy'    => 'dame_agenda_category',
						'description' => $t['description'],
						'parent'      => $t['parent'],
					),
					array( 'term_taxonomy_id' => $tt_id )
				);
			}

			if ( ! empty( $t['meta_data']['dame_taxonomy_meta'] ) ) {
				update_option( 'taxonomy_' . $term_id, $t['meta_data']['dame_taxonomy_meta'] );
			}
		}

		// 3. RESTORE POSTS
		$max_post_id = 0;
		foreach ( $data['posts'] ?? array() as $p ) {
			$pid         = (int) $p['ID'];
			$max_post_id = max( $max_post_id, $pid );
			$post_data   = array(
				'ID'                    => $pid,
				'post_author'           => $p['post_author'],
				'post_date'             => $p['post_date'],
				'post_date_gmt'         => $p['post_date_gmt'],
				'post_content'          => $p['post_content'],
				'post_title'            => $p['post_title'],
				'post_excerpt'          => $p['post_excerpt'],
				'post_status'           => $p['post_status'],
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'post_name'             => $p['post_name'],
				'post_modified'         => $p['post_date'],
				'post_modified_gmt'     => $p['post_date_gmt'],
				'post_parent'           => $p['post_parent'],
				'menu_order'            => $p['menu_order'],
				'post_type'             => $p['post_type'],
				'post_content_filtered' => '',
				'to_ping'               => '',
				'pinged'                => '',
				'guid'                  => '',
			);

			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d", $pid ) ) ) {
				$wpdb->insert( $wpdb->posts, $post_data );
			} else {
				$wpdb->update( $wpdb->posts, $post_data, array( 'ID' => $pid ) );
				$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $pid ) );
			}
			foreach ( $p['meta_data'] as $k => $vals ) {
				foreach ( $vals as $v ) {
					add_post_meta( $pid, $k, $v, false );
				}
			}
			if ( ! empty( $p['categories'] ) ) {
				wp_set_object_terms( $pid, $p['categories'], 'dame_agenda_category' );
			}
		}

		// 4. RESTORE VOTES
		$votes = $data['benevolat_votes'] ?? $data['poll_votes'] ?? array();
		foreach ( $votes as $vote ) {
			$wpdb->insert(
				"{$wpdb->prefix}dame_benevolat_votes",
				array(
					'poll_id'      => $vote['poll_id'],
					'recipient_id' => $vote['recipient_id'],
					'choice_key'   => $vote['choice_key'],
					'voted_at'     => $vote['voted_at'],
				)
			);
		}

		// 5. REALIGN
		if ( $max_post_id > 0 ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = %d", $max_post_id + 1 ) );
		}

		// 6. TRIGGER AUTO-UPGRADE IF BACKUP IS OLD
		$backup_version = $data['version'] ?? '1.0.0';
		update_option( 'dame_plugin_version', $backup_version );
		( new \DAME\Core\Upgrader() )->check_for_updates();

		$this->add_admin_notice( "Restauration de l'agenda et des appels à bénévoles terminée avec succès (Données mises à jour)." );
	}

	/*
	 * -------------------------------------------------------------------------
	 * ARTICLES, PAGES, MENUS - JSON (BACKUP/RESTORE)
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Generate Site Content export data (Posts, Pages, Menus).
	 *
	 * @return array<string, mixed>
	 */
	public function generate_site_export_data(): array {
		$data       = array(
			'version'        => DAME_VERSION,
			'posts'          => array(),
			'taxonomy_terms' => array(),
		);
		$post_types = array( 'post', 'page', 'nav_menu_item' );

		// 1. Identify and Export Taxonomies
		$taxonomies = get_object_taxonomies( $post_types );
		foreach ( $taxonomies as $tax ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $tax,
					'hide_empty' => false,
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $t ) {
					$term_data = array(
						'term_id'          => $t->term_id,
						'term_taxonomy_id' => $t->term_taxonomy_id,
						'name'             => $t->name,
						'slug'             => $t->slug,
						'description'      => $t->description,
						'parent'           => $t->parent,
						'meta_data'        => array(),
					);

					// Export term meta
					$term_meta = get_term_meta( $t->term_id );
					if ( ! empty( $term_meta ) ) {
						foreach ( $term_meta as $k => $v ) {
							$term_data['meta_data'][ $k ] = maybe_unserialize( $v[0] );
						}
					}

					$data['taxonomy_terms'][ $tax ][] = $term_data;
				}
			}
		}

		// 2. Export Posts
		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		// Optimisation : Pré-chargement des métadonnées
		if ( ! empty( $posts ) ) {
			update_meta_cache( 'post', wp_list_pluck( $posts, 'ID' ) );
		}

		foreach ( $posts as $p ) {
			$meta = array();
			foreach ( get_post_meta( $p->ID ) as $k => $vals ) {
				$meta[ $k ] = array_map( 'maybe_unserialize', $vals );
			}

			$tax_relationships = array();
			foreach ( $taxonomies as $tax ) {
				$terms = wp_get_post_terms( $p->ID, $tax, array( 'fields' => 'slugs' ) );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$tax_relationships[ $tax ] = $terms;
				}
			}

			$data['posts'][] = array(
				'ID'            => $p->ID,
				'post_author'   => $p->post_author,
				'post_title'    => $p->post_title,
				'post_content'  => $p->post_content,
				'post_excerpt'  => $p->post_excerpt,
				'post_type'     => $p->post_type,
				'post_status'   => $p->post_status,
				'post_name'     => $p->post_name,
				'post_parent'   => $p->post_parent,
				'post_date'     => $p->post_date,
				'post_date_gmt' => $p->post_date_gmt,
				'menu_order'    => $p->menu_order,
				'meta_data'     => $meta,
				'taxonomies'    => $tax_relationships,
			);
		}

		return $data;
	}

	/**
	 * Export Site Content to JSON GZ.
	 */
	private function export_json_site(): void {
		$data     = $this->generate_site_export_data();
		$gz       = gzcompress( (string) wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		$filename = 'dame-site-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		ob_clean();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( (string) $gz ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary stream output.
		echo $gz;
		exit;
	}

	/**
	 * Import Site Content from JSON GZ.
	 */
	private function import_json_site(): void {
		if ( ! isset( $_POST['dame_site_restore_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_site_restore_nonce'] ) ), 'dame_site_restore_nonce_action' ) ) {
			return;
		}
		if ( ! isset( $_FILES['dame_site_restore_file'] ) || ! is_array( $_FILES['dame_site_restore_file'] ) || empty( $_FILES['dame_site_restore_file']['tmp_name'] ) || ( isset( $_FILES['dame_site_restore_file']['error'] ) && UPLOAD_ERR_OK !== $_FILES['dame_site_restore_file']['error'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded temp file path.
		$tmp_file = sanitize_text_field( wp_unslash( $_FILES['dame_site_restore_file']['tmp_name'] ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local temp file read.
		$json = gzuncompress( (string) file_get_contents( $tmp_file ) );
		$data = json_decode( (string) $json, true );
		if ( ! $data ) {
			return;
		}

		global $wpdb;
		$post_types   = array( 'post', 'page', 'nav_menu_item' );
		$taxonomies   = get_object_taxonomies( $post_types );
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// 1. PURGE EVERYTHING
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- System restore purge.
		$posts_to_delete = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM %i WHERE post_type IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( array( $wpdb->posts ), $post_types )
			)
		);
		foreach ( $posts_to_delete as $pid ) {
			wp_delete_post( (int) $pid, true );
		}

		foreach ( $taxonomies as $tax ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $tax,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $tid ) {
					wp_delete_term( (int) $tid, $tax );
				}
			}
		}

		// 2. RESTORE TAXONOMIES (Forcing IDs)
		foreach ( $data['taxonomy_terms'] ?? array() as $tax => $terms ) {
			foreach ( $terms as $t ) {
				$term_id = (int) $t['term_id'];
				$tt_id   = (int) $t['term_taxonomy_id'];

				// Term check
				if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->terms WHERE term_id = %d", $term_id ) ) ) {
					$wpdb->insert(
						$wpdb->terms,
						array(
							'term_id'    => $term_id,
							'name'       => $t['name'],
							'slug'       => $t['slug'],
							'term_group' => 0,
						)
					);
				} else {
					$wpdb->update(
						$wpdb->terms,
						array(
							'name' => $t['name'],
							'slug' => $t['slug'],
						),
						array( 'term_id' => $term_id )
					);
				}

				// Taxonomy relation check
				if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $tt_id ) ) ) {
					$wpdb->insert(
						$wpdb->term_taxonomy,
						array(
							'term_taxonomy_id' => $tt_id,
							'term_id'          => $term_id,
							'taxonomy'         => $tax,
							'description'      => $t['description'],
							'parent'           => $t['parent'],
							'count'            => 0,
						)
					);
				} else {
					$wpdb->update(
						$wpdb->term_taxonomy,
						array(
							'term_id'     => $term_id,
							'taxonomy'    => $tax,
							'description' => $t['description'],
							'parent'      => $t['parent'],
						),
						array( 'term_taxonomy_id' => $tt_id )
					);
				}

				// Restore Term Meta
				if ( ! empty( $t['meta_data'] ) ) {
					foreach ( $t['meta_data'] as $k => $v ) {
						update_term_meta( $term_id, $k, $v );
					}
				}
			}
		}

		// 3. RESTORE POSTS (Forcing IDs)
		$max_post_id = 0;
		foreach ( $data['posts'] ?? array() as $p ) {
			$pid         = (int) $p['ID'];
			$max_post_id = max( $max_post_id, $pid );

			$post_data = array(
				'ID'                    => $pid,
				'post_author'           => $p['post_author'],
				'post_date'             => $p['post_date'],
				'post_date_gmt'         => $p['post_date_gmt'],
				'post_content'          => $p['post_content'],
				'post_title'            => $p['post_title'],
				'post_excerpt'          => $p['post_excerpt'],
				'post_status'           => $p['post_status'],
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'post_name'             => $p['post_name'],
				'post_modified'         => $p['post_date'],
				'post_modified_gmt'     => $p['post_date_gmt'],
				'post_parent'           => $p['post_parent'],
				'menu_order'            => $p['menu_order'],
				'post_type'             => $p['post_type'],
				'post_content_filtered' => '',
				'to_ping'               => '',
				'pinged'                => '',
				'guid'                  => '',
			);

			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d", $pid ) ) ) {
				$wpdb->insert( $wpdb->posts, $post_data );
			} else {
				$wpdb->update( $wpdb->posts, $post_data, array( 'ID' => $pid ) );
				$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $pid ) );
			}

			// Restore Meta
			foreach ( $p['meta_data'] as $k => $vals ) {
				foreach ( $vals as $v ) {
					add_post_meta( $pid, $k, $v, false );
				}
			}

			// Restore Taxonomies
			foreach ( $p['taxonomies'] ?? array() as $tax => $slugs ) {
				wp_set_object_terms( $pid, $slugs, $tax );
			}
		}

		// 4. REALIGN AUTO_INCREMENT
		if ( $max_post_id > 0 ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = %d", $max_post_id + 1 ) );
		}

		// 5. TRIGGER AUTO-UPGRADE IF BACKUP IS OLD
		$backup_version = $data['version'] ?? '1.0.0';
		update_option( 'dame_plugin_version', $backup_version );
		( new \DAME\Core\Upgrader() )->check_for_updates();

		$this->add_admin_notice( 'Contenu du site restauré avec succès (Données mises à jour).' );
	}

	/*
	 * -------------------------------------------------------------------------
	 * CRON JOB
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Runs the daily scheduled backup job.
	 */
	public function run_scheduled_backup(): void {
		global $wpdb;
		$upload_dir = wp_upload_dir();
		$backup_dir = trailingslashit( $upload_dir['basedir'] ) . 'dame-backups';
		wp_mkdir_p( $backup_dir );

		// Initialize WP_Filesystem
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		// Generate files
		$data_adherent = $this->generate_adherent_export_data();
		$file_adherent = trailingslashit( $backup_dir ) . 'dame-adherents-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		if ( $wp_filesystem ) {
			$wp_filesystem->put_contents( $file_adherent, (string) gzcompress( (string) wp_json_encode( $data_adherent ) ) );
		}

		$data_agenda = $this->generate_agenda_export_data();
		$file_agenda = trailingslashit( $backup_dir ) . 'dame-agenda-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		if ( $wp_filesystem ) {
			$wp_filesystem->put_contents( $file_agenda, (string) gzcompress( (string) wp_json_encode( $data_agenda ) ) );
		}

		$data_site = $this->generate_site_export_data();
		$file_site = trailingslashit( $backup_dir ) . 'dame-site-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		if ( $wp_filesystem ) {
			$wp_filesystem->put_contents( $file_site, (string) gzcompress( (string) wp_json_encode( $data_site ) ) );
		}

		// Send Email
		$options = get_option( 'dame_options' );
		$to      = $options['sender_email'] ?? get_option( 'admin_email' );
		if ( $to ) {
			/* translators: %s: Site title */
			$subject = sprintf( __( 'Sauvegarde journalière DAME pour %s', 'dame' ), get_bloginfo( 'name' ) );
			$body    = '<p>' . __( 'Veuillez trouver ci-joint les sauvegardes journalières.', 'dame' ) . '</p>';
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			wp_mail( $to, $subject, $body, $headers, array( $file_adherent, $file_agenda, $file_site ) );
		}

		// Cleanup
		wp_delete_file( $file_adherent );
		wp_delete_file( $file_agenda );
		wp_delete_file( $file_site );
	}
}
