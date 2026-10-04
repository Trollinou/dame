<?php
/**
 * Adherent Backup & Import/Export Service.
 *
 * @package DAME\Services\Backup
 */

declare(strict_types=1);

namespace DAME\Services\Backup;

use WP_Query;
use WP_Post;
use DateTime;
use DAME\Core\Utils;
use DAME\Services\Data_Provider;

/**
 * Handles CSV and JSON export/import and backup/restore for Adherents.
 */
class AdherentBackup {
	use NoticeTrait;

	/**
	 * Export adherents to CSV file.
	 */
	public function export_csv(): void {
		$filename = 'dame-export-adherents-' . wp_date( 'Y-m-d' ) . '.csv';

		ob_clean();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );
		if ( ! is_resource( $output ) ) {
			return;
		}

		// Add BOM to fix UTF-8 in Excel.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// --- Dynamic Headers ---.
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

		// --- Dynamic Rows ---.
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
				if ( ! $post_id ) {
					continue;
				}

				// Get adherent's seasons.
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

				// Add dynamic season data.
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
	public function import_csv(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in router.
		if ( ! isset( $_FILES['dame_import_csv_file'] ) || ! is_array( $_FILES['dame_import_csv_file'] ) || empty( $_FILES['dame_import_csv_file']['tmp_name'] ) || ( isset( $_FILES['dame_import_csv_file']['error'] ) && UPLOAD_ERR_OK !== $_FILES['dame_import_csv_file']['error'] ) ) {
			$this->add_admin_notice( __( 'Erreur lors du téléversement du fichier.', 'dame' ), 'error' );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded temp file path verified in router.
		$raw_tmp   = isset( $_FILES['dame_import_csv_file']['tmp_name'] ) ? $_FILES['dame_import_csv_file']['tmp_name'] : '';
		$tmp_file  = sanitize_text_field( wp_unslash( $raw_tmp ) );
		$mime_type = mime_content_type( $tmp_file );

		if ( 'text/plain' !== $mime_type && 'text/csv' !== $mime_type ) {
			$this->add_admin_notice( __( 'Le fichier téléversé n\'est pas un fichier CSV valide.', 'dame' ), 'error' );
			return;
		}

		// Increase execution time.
		set_time_limit( 300 );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Local CSV file handle.
		$handle = fopen( $tmp_file, 'r' );
		if ( false === $handle ) {
			$this->add_admin_notice( __( 'Impossible d\'ouvrir le fichier téléversé.', 'dame' ), 'error' );
			return;
		}

		// Read header row and map columns.
		$header = fgetcsv( $handle, 0, ';', '"', '\\' );
		if ( false === $header ) {
			$this->add_admin_notice( __( 'Impossible de lire l\'en-tête du fichier CSV.', 'dame' ), 'error' );
			fclose( $handle );
			return;
		}

		// Remove BOM from the first header element if present.
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

		// Data mapping from CSV columns to post meta keys.
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

		$imported_count = 0;
		$all_seasons    = get_terms(
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

			// Capture dynamic seasons if present in CSV row based on expected pattern.
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

			$first_name = $member_data['Prénom'] ?? '';
			$last_name  = $member_data['Nom d\'usage'] ?? '';
			$birth_name = $member_data['Nom de naissance'] ?? '';

			if ( empty( $first_name ) || ( empty( $last_name ) && empty( $birth_name ) ) ) {
				continue; // Skip rows without a name.
			}

			$post_id = 0;
			$email   = $member_data['Adresse email'] ?? '';
			$license = $member_data['Numéro de licence'] ?? '';

			$effective_last_name = ! empty( $last_name ) ? $last_name : $birth_name;
			$post_title          = Utils::format_lastname( (string) $effective_last_name ) . ' ' . Utils::format_firstname( (string) $first_name );

			// Reconciliation.
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
				// Update title in case name changed.
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $post_title,
					)
				);
			}

			if ( $post_id ) {
				foreach ( $meta_mapping as $csv_header => $meta_key ) {
					$value = $member_data[ $csv_header ] ?? '';

					if ( '_dame_birth_date' === $meta_key ) {
						if ( ! empty( $value ) ) {
							$date = DateTime::createFromFormat( 'd/m/Y', $value );
							if ( $date ) {
								$value = $date->format( 'Y-m-d' );
							} else {
								$value = ''; // Invalid date format.
							}
						} else {
							$value = '1950-09-19';
						}
					}

					if ( '_dame_membership_status' === $meta_key ) {
						$status_key       = 'N'; // Default to 'Non Adhérent'.
						$normalized_value = mb_strtoupper( trim( $value ), 'UTF-8' );

						// Handle cases like "Actif (A)" by extracting the key.
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

					// Sanitize phone numbers.
					if ( in_array( $meta_key, array( '_dame_phone_number', '_dame_autre_telephone' ) ) ) {
						$phone_number = str_replace( array( ' ', '.' ), '', $value );
						if ( substr( $phone_number, 0, 3 ) === '+33' ) {
							$phone_number = '0' . substr( $phone_number, 3 );
						} elseif ( substr( $phone_number, 0, 2 ) === '33' ) {
							$phone_number = '0' . substr( $phone_number, 2 );
						}
						$value = $phone_number;
					}

					// Handle boolean fields (O/N).
					$boolean_fields = array(
						'_dame_is_junior',
						'_dame_is_pole_excellence',
						'_dame_is_benevole',
						'_dame_is_elu_local',
					);
					if ( in_array( $meta_key, $boolean_fields ) ) {
						$value = ( mb_strtoupper( trim( (string) $value ), 'UTF-8' ) === 'O' ) ? 1 : 0;
					}

					update_post_meta( $post_id, $meta_key, sanitize_text_field( (string) $value ) );
				}

				// Handle postal code logic.
				$postal_code = $member_data['Code Postal'] ?? '';
				if ( ! empty( $postal_code ) ) {
					update_post_meta( $post_id, '_dame_country', 'FR' );

					$department_code = Data_Provider::get_department_from_postal_code( $postal_code );
					if ( $department_code ) {
						update_post_meta( $post_id, '_dame_department', $department_code );

						$region_code = Data_Provider::get_region_for_department( $department_code );
						if ( $region_code ) {
							update_post_meta( $post_id, '_dame_region', $region_code );
						}
					}
				}

				// Set defaults for fields not in CSV.
				if ( empty( get_post_meta( $post_id, '_dame_license_type', true ) ) ) {
					update_post_meta( $post_id, '_dame_license_type', 'Non précisé' );
				}
				if ( empty( get_post_meta( $post_id, '_dame_arbitre_level', true ) ) ) {
					update_post_meta( $post_id, '_dame_arbitre_level', 'Non' );
				}

				// Handle Seasons.
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

	/**
	 * Generate Adherent export data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate_export_data(): array {
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

		// 1. Export Users and Usermeta.
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

		// 2. Taxonomies.
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

		// Combined Post Types for this section.
		$post_types = array( 'adherent', 'dame_contact', 'dame_pre_inscription', 'dame_message' );
		$query      = new WP_Query(
			array(
				'post_type'      => $post_types,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		// Optimisation : Pré-chargement des métadonnées.
		if ( ! empty( $query->posts ) ) {
			update_meta_cache( 'post', wp_list_pluck( $query->posts, 'ID' ) );
		}

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
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

		// Message logs (envois + ouvertures).
		$table_name = $wpdb->prefix . 'dame_message_opens';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- System backup message tracking query.
		$tracking_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', $table_name ), ARRAY_A );
		if ( is_array( $tracking_data ) ) {
			$data['message_tracking'] = $tracking_data;
		}

		// Options critiques.
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
	public function export_json(): void {
		$data     = $this->generate_export_data();
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
	public function import_json(): void {
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

		// 1. PURGE.
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

		// 2. RESTORE TAXONOMIES.
		foreach ( $data['taxonomy_terms'] ?? array() as $tax => $terms ) {
			foreach ( $terms as $t ) {
				$term_id = (int) $t['term_id'];
				$tt_id   = (int) $t['term_taxonomy_id'];

				// Term check.
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

				// Taxonomy check.
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
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- System restore purge term meta.
					$wpdb->delete( $wpdb->termmeta, array( 'term_id' => $term_id ) );
					foreach ( $t['meta_data'] as $k => $vals ) {
						if ( is_array( $vals ) ) {
							foreach ( $vals as $v ) {
								add_term_meta( $term_id, $k, $v, false );
							}
						} else {
							add_term_meta( $term_id, $k, $vals, false );
						}
					}
				}
			}
		}

		// 3. RESTORE POSTS.
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
				// Clean existing meta if updating.
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

		// 4. RESTORE MESSAGE TRACKING.
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

		// 5. REALIGN.
		if ( $max_post_id > 0 ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = %d", $max_post_id + 1 ) );
		}

		// 6. RESTORE USERS (Upsert logic to avoid locking current admin out).
		$max_user_id     = 0;
		$current_user_id = get_current_user_id();

		foreach ( $data['users'] ?? array() as $u ) {
			$uid         = (int) $u['data']['ID'];
			$max_user_id = max( $max_user_id, $uid );

			// Check if user already exists.
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE ID = %d", $uid ) );

			if ( ! $exists ) {
				$wpdb->insert( $wpdb->users, $u['data'] );
			} elseif ( $uid !== $current_user_id ) {
				// Don't update the current user performing the restore to avoid session issues.
				$wpdb->update( $wpdb->users, $u['data'], array( 'ID' => $uid ) );
			}

			// Restore User Meta.
			// We clear existing meta first (except for current user to be safe).
			if ( $uid !== $current_user_id ) {
				$wpdb->delete( $wpdb->usermeta, array( 'user_id' => $uid ) );
			}

			foreach ( $u['meta'] as $k => $vals ) {
				// Normalize capability and user_level keys to current prefix.
				$normalized_key = $k;
				if ( preg_match( '/^(.*)capabilities$/', $k, $matches ) ) {
					$normalized_key = $wpdb->prefix . 'capabilities';
				} elseif ( preg_match( '/^(.*)user_level$/', $k, $matches ) ) {
					$normalized_key = $wpdb->prefix . 'user_level';
				}

				foreach ( $vals as $v ) {
					if ( $uid === $current_user_id ) {
						// For current user, only update keys if they don't exist to avoid breaking session.
						if ( ! get_user_meta( $uid, $normalized_key, true ) ) {
							add_user_meta( $uid, $normalized_key, $v, false );
						}
					} else {
						// Raw direct insert to maintain exact serialized data.
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

		// 7. RESTORE OPTIONS.
		if ( ! empty( $data['options']['dame_current_season_tag_slug'] ) ) {
			$term = get_term_by( 'slug', $data['options']['dame_current_season_tag_slug'], 'dame_saison_adhesion' );
			if ( $term && ! is_wp_error( $term ) ) {
				update_option( 'dame_current_season_tag_id', $term->term_id );
			}
		} elseif ( ! empty( $data['options']['dame_current_season_tag_id'] ) ) {
			// Fallback to ID if slug not found.
			update_option( 'dame_current_season_tag_id', $data['options']['dame_current_season_tag_id'] );
		}

		if ( ! empty( $data['options']['dame_options'] ) ) {
			update_option( 'dame_options', $data['options']['dame_options'] );
		}

		$this->add_admin_notice( 'Restauration des adhérents, contacts et messages terminée avec succès (IDs et réglages conservés).' );
	}
}
