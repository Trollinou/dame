<?php
/**
 * Contact Backup & Import/Export Service.
 *
 * @package DAME\Services\Backup
 */

declare(strict_types=1);

namespace DAME\Services\Backup;

use WP_Query;
use WP_Post;
use DAME\Core\Utils;
use DAME\Services\Data_Provider;

/**
 * Handles CSV export, CSV import, HelloAsso import and duplicate detection for contacts.
 */
class ContactBackup {
	use NoticeTrait;

	/**
	 * Handle contacts export to CSV.
	 */
	public function export_csv(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in router.
		$type_slug = isset( $_POST['contact_type'] ) ? sanitize_key( wp_unslash( $_POST['contact_type'] ) ) : '';
		if ( empty( $type_slug ) ) {
			return;
		}

		$filename = 'dame-export-contacts-' . $type_slug . '-' . wp_date( 'Y-m-d' ) . '.csv';

		ob_clean();
		header( 'Content-Type: text/csv; charset=windows-1252' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );
		if ( ! is_resource( $output ) ) {
			return;
		}

		// Headers.
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

		// Convert headers to CP1252.
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
				$post_id = $post instanceof WP_Post ? $post->ID : (int) $post;
				if ( ! $post_id ) {
					continue;
				}
				$row = array(
					get_post_meta( $post_id, '_dame_contact_organization', true ),
					get_post_meta( $post_id, '_dame_contact_last_name', true ),
					get_post_meta( $post_id, '_dame_contact_first_name', true ),
					get_post_meta( $post_id, '_dame_contact_role', true ),
					get_post_meta( $post_id, '_dame_contact_email', true ),
					get_post_meta( $post_id, '_dame_contact_no_emails', true ) ? 'O' : 'N',
					get_post_meta( $post_id, '_dame_contact_address_1', true ),
					get_post_meta( $post_id, '_dame_contact_address_2', true ),
					get_post_meta( $post_id, '_dame_contact_postcode', true ),
					get_post_meta( $post_id, '_dame_contact_city', true ),
					$type_slug,
				);

				// Convert row to CP1252.
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
	public function import_csv(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in router.
		$type_slug = isset( $_POST['contact_type'] ) ? sanitize_key( wp_unslash( $_POST['contact_type'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in router.
		if ( empty( $type_slug ) || ! isset( $_FILES['dame_import_contacts_file'] ) || ! is_array( $_FILES['dame_import_contacts_file'] ) || empty( $_FILES['dame_import_contacts_file']['tmp_name'] ) || ( isset( $_FILES['dame_import_contacts_file']['error'] ) && UPLOAD_ERR_OK !== $_FILES['dame_import_contacts_file']['error'] ) ) {
			$this->add_admin_notice( __( 'Données invalides pour l\'import.', 'dame' ), 'error' );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded temp file path verified in router.
		$raw_tmp  = isset( $_FILES['dame_import_contacts_file']['tmp_name'] ) ? $_FILES['dame_import_contacts_file']['tmp_name'] : '';
		$tmp_file = sanitize_text_field( wp_unslash( $raw_tmp ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Local CSV import file handle.
		$handle = fopen( $tmp_file, 'r' );
		if ( ! $handle ) {
			$this->add_admin_notice( __( 'Impossible d\'ouvrir le fichier CSV.', 'dame' ), 'error' );
			return;
		}

		// Read headers.
		$headers = fgetcsv( $handle, 0, ';', '"', '\\' );
		if ( ! $headers ) {
			$this->add_admin_notice( __( 'Impossible de lire l\'en-tête du fichier CSV.', 'dame' ), 'error' );
			fclose( $handle );
			return;
		}

		// Convert headers to UTF-8 and map them.
		$headers = array_map( fn( $h ) => mb_convert_encoding( (string) $h, 'UTF-8', 'Windows-1252' ), $headers );

		// Remove BOM if present.
		if ( isset( $headers[0] ) ) {
			$headers[0] = preg_replace( '/^\x{FEFF}/u', '', $headers[0] );
		}

		$col_map = array_flip( $headers );

		$created = 0;
		$updated = 0;

		while ( ( $row = fgetcsv( $handle, 0, ';', '"', '\\' ) ) !== false ) {
			// Convert to UTF-8.
			$row = array_map( fn( $val ) => mb_convert_encoding( (string) $val, 'UTF-8', 'Windows-1252' ), $row );

			// Map columns based on headers.
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

			// Deduplication Logic.
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
				$new_title = ! empty( $base_name ) ? $base_name : __( 'Contact sans nom', 'dame' );
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

				// Enrichment: Dept & Region.
				$dept_code = Data_Provider::get_department_from_postal_code( $postcode );
				if ( $dept_code ) {
					update_post_meta( $post_id, '_dame_contact_department', $dept_code );
					$region_code = Data_Provider::get_region_for_department( $dept_code );
					if ( $region_code ) {
						update_post_meta( $post_id, '_dame_contact_region', $region_code );
					}
				}

				// Assign Taxonomy.
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
				/* translators: %d: adherent ID */
				$full_name = sprintf( __( 'Adhérent #%d', 'dame' ), $post_id );
			}

			// 1. Emails.
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

			// 2. Normalized Names.
			$name_sources = array();
			if ( ! empty( $last_name ) && ! empty( $first_name ) && mb_strlen( $last_name ) >= 2 && mb_strlen( $first_name ) >= 2 ) {
				/* translators: 1: last name, 2: first name */
				$name_sources[] = array( $last_name, $first_name, sprintf( __( 'Adhérent : %1$s %2$s', 'dame' ), $last_name, $first_name ) );
			}
			if ( ! empty( $birth_name ) && ! empty( $first_name ) && $birth_name !== $last_name && mb_strlen( $birth_name ) >= 2 && mb_strlen( $first_name ) >= 2 ) {
				/* translators: 1: birth name, 2: first name */
				$name_sources[] = array( $birth_name, $first_name, sprintf( __( 'Nom de naissance : %1$s %2$s', 'dame' ), $birth_name, $first_name ) );
			}
			$rep1_l = trim( (string) ( $meta['_dame_legal_rep_1_last_name'] ?? '' ) );
			$rep1_f = trim( (string) ( $meta['_dame_legal_rep_1_first_name'] ?? '' ) );
			if ( ! empty( $rep1_l ) && ! empty( $rep1_f ) && mb_strlen( $rep1_l ) >= 2 && mb_strlen( $rep1_f ) >= 2 ) {
				/* translators: 1: rep last name, 2: rep first name */
				$name_sources[] = array( $rep1_l, $rep1_f, sprintf( __( 'Resp. Légal 1 : %1$s %2$s', 'dame' ), $rep1_l, $rep1_f ) );
			}
			$rep2_l = trim( (string) ( $meta['_dame_legal_rep_2_last_name'] ?? '' ) );
			$rep2_f = trim( (string) ( $meta['_dame_legal_rep_2_first_name'] ?? '' ) );
			if ( ! empty( $rep2_l ) && ! empty( $rep2_f ) && mb_strlen( $rep2_l ) >= 2 && mb_strlen( $rep2_f ) >= 2 ) {
				/* translators: 1: rep last name, 2: rep first name */
				$name_sources[] = array( $rep2_l, $rep2_f, sprintf( __( 'Resp. Légal 2 : %1$s %2$s', 'dame' ), $rep2_l, $rep2_f ) );
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

			// 3. Licenses.
			$raw_lic = trim( (string) ( $meta['_dame_license_number'] ?? '' ) );
			if ( ! empty( $raw_lic ) ) {
				$extracted = Utils::extract_license_numbers( $raw_lic );
				if ( empty( $extracted ) ) {
					$extracted[] = mb_strtoupper( $raw_lic, 'UTF-8' );
				}
				foreach ( $extracted as $lic_code ) {
					/* translators: %s: license number */
					$detail                = sprintf( __( 'Licence %s', 'dame' ), $lic_code );
					$licenses[ $lic_code ] = array(
						'id'     => $post_id,
						'name'   => $full_name,
						'detail' => $detail,
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
				/* translators: %d: contact ID */
				$contact_name = sprintf( __( 'Contact #%d', 'dame' ), $contact_id );
			}

			$matched       = false;
			$adherent_id   = 0;
			$adherent_name = '';
			$match_reason  = '';

			// Check 1: Email.
			if ( ! empty( $email ) && isset( $index['emails'][ $email ] ) ) {
				$matched       = true;
				$adherent_id   = $index['emails'][ $email ]['id'];
				$adherent_name = $index['emails'][ $email ]['name'];
				$match_reason  = $index['emails'][ $email ]['detail'];
			}

			// Check 2: Nom + Prénom.
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
				// Get assigned categories.
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
	public function delete_duplicates(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in router.
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
	public function import_helloasso_csv(): void {
		global $wpdb;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in router.
		$type_slug = isset( $_POST['contact_type'] ) ? sanitize_key( wp_unslash( $_POST['contact_type'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in router.
		if ( empty( $type_slug ) || ! isset( $_FILES['dame_import_helloasso_file'] ) || ! is_array( $_FILES['dame_import_helloasso_file'] ) || empty( $_FILES['dame_import_helloasso_file']['tmp_name'] ) || ( isset( $_FILES['dame_import_helloasso_file']['error'] ) && UPLOAD_ERR_OK !== $_FILES['dame_import_helloasso_file']['error'] ) ) {
			$this->add_admin_notice( __( 'Données invalides pour l\'import HelloAsso.', 'dame' ), 'error' );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded temp file path verified in router.
		$raw_tmp  = isset( $_FILES['dame_import_helloasso_file']['tmp_name'] ) ? $_FILES['dame_import_helloasso_file']['tmp_name'] : '';
		$tmp_file = sanitize_text_field( wp_unslash( $raw_tmp ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Local CSV import file handle.
		$handle = fopen( $tmp_file, 'r' );
		if ( ! $handle ) {
			$this->add_admin_notice( __( 'Impossible d\'ouvrir le fichier CSV HelloAsso.', 'dame' ), 'error' );
			return;
		}

		// Read headers.
		$headers = fgetcsv( $handle, 0, ';', '"', '\\' );
		if ( false === $headers ) {
			$this->add_admin_notice( __( 'Impossible de lire l\'en-tête du fichier CSV HelloAsso.', 'dame' ), 'error' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Local CSV import file handle.
			fclose( $handle );
			return;
		}

		// Convert headers to UTF-8 and clean BOM.
		$headers = array_map( fn( $h ) => mb_convert_encoding( (string) $h, 'UTF-8', 'auto' ), $headers );
		if ( isset( $headers[0] ) ) {
			$headers[0] = (string) preg_replace( '/^\x{FEFF}/u', '', (string) $headers[0] );
		}

		$headers_str = array_map( 'strval', $headers );
		$col_map     = array_flip( $headers_str );

		// Find potential license column in headers.
		$license_col_idx = null;
		foreach ( $col_map as $header_name => $idx ) {
			$h_lower = mb_strtolower( (string) $header_name, 'UTF-8' );
			if ( str_contains( $h_lower, 'licence' ) || str_contains( $h_lower, 'fide' ) || str_contains( $h_lower, 'ffe' ) ) {
				$license_col_idx = $idx;
				break;
			}
		}

		// 1. Fetch multi-criteria matching index for all adherents.
		$matching_index = self::get_adherents_matching_index();

		$created           = 0;
		$updated           = 0;
		$skipped_adherents = 0;
		$processed_emails  = array();

		while ( ( $row = fgetcsv( $handle, 0, ';', '"', '\\' ) ) !== false ) {
			// Convert row to UTF-8.
			$row = array_map( fn( $val ) => mb_convert_encoding( (string) $val, 'UTF-8', 'auto' ), $row );

			// Check order status if present.
			$status_idx = $col_map['Statut de la commande'] ?? null;
			if ( null !== $status_idx && isset( $row[ $status_idx ] ) ) {
				$order_status = trim( (string) $row[ $status_idx ] );
				if ( ! empty( $order_status ) && 0 !== strcasecmp( $order_status, 'Validé' ) && 0 !== strcasecmp( $order_status, 'Valide' ) ) {
					continue;
				}
			}

			// Extract email (Email payeur).
			$raw_email = isset( $col_map['Email payeur'] ) ? trim( (string) ( $row[ $col_map['Email payeur'] ] ?? '' ) ) : '';
			$email     = sanitize_email( $raw_email );
			if ( empty( $email ) || ! is_email( $email ) ) {
				continue;
			}

			$email_lower = mb_strtolower( $email );

			// Deduplication in current batch.
			if ( isset( $processed_emails[ $email_lower ] ) ) {
				continue;
			}
			$processed_emails[ $email_lower ] = true;

			// Extract identity fields.
			$payer_last  = isset( $col_map['Nom payeur'] ) ? trim( (string) ( $row[ $col_map['Nom payeur'] ] ?? '' ) ) : '';
			$payer_first = isset( $col_map['Prénom payeur'] ) ? trim( (string) ( $row[ $col_map['Prénom payeur'] ] ?? '' ) ) : '';
			$part_last   = isset( $col_map['Nom participant'] ) ? trim( (string) ( $row[ $col_map['Nom participant'] ] ?? '' ) ) : '';
			$part_first  = isset( $col_map['Prénom participant'] ) ? trim( (string) ( $row[ $col_map['Prénom participant'] ] ?? '' ) ) : '';
			$org         = isset( $col_map['Raison sociale'] ) ? trim( (string) ( $row[ $col_map['Raison sociale'] ] ?? '' ) ) : '';
			$raw_license = ( null !== $license_col_idx && isset( $row[ $license_col_idx ] ) ) ? trim( (string) $row[ $license_col_idx ] ) : '';

			$is_adherent_matched = false;

			// Check A: Email.
			if ( isset( $matching_index['emails'][ $email_lower ] ) ) {
				$is_adherent_matched = true;
			}

			// Check B: Nom + Prénom (Participant ou Payeur).
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

			// Check C: Licence FFE / FIDE.
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

			// Check if contact already exists in dame_contact by email.
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
				// Contact already exists: append target category without overriding existing categories.
				wp_set_object_terms( (int) $existing_contact_id, $type_slug, 'dame_contact_type', true );
				++$updated;
			} else {
				// Create new contact.
				$formatted_last  = Utils::format_lastname( $last_name );
				$formatted_first = Utils::format_firstname( $first_name );
				$base_name       = trim( $formatted_last . ' ' . $formatted_first );

				if ( ! empty( $org ) ) {
					$new_title = $org . ( ! empty( $base_name ) ? ' (' . $base_name . ')' : '' );
				} else {
					$new_title = ! empty( $base_name ) ? $base_name : __( 'Contact sans nom', 'dame' );
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

					// Assign taxonomy (append = true).
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
}
