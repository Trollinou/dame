<?php
/**
 * File for handling plugin data import and export.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles the export of member data to CSV.
 */
function dame_handle_csv_export_action() {
	if ( ! isset( $_POST['dame_export_csv_action'] ) || ! isset( $_POST['dame_export_csv_nonce'] ) || ! wp_verify_nonce( $_POST['dame_export_csv_nonce'], 'dame_export_csv_nonce_action' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", 'dame' ) );
	}

	$filename = 'dame-export-adherents-' . date( 'Y-m-d' ) . '.csv';

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
			$formatted_birth_date   = $birth_date ? date( 'd/m/Y', strtotime( $birth_date ) ) : '';

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
add_action( 'admin_init', 'dame_handle_csv_export_action' );

/**
 * Handles the import of member data from a CSV file.
 */
function dame_handle_csv_import_action() {
	if ( ! isset( $_POST['dame_import_csv_action'] ) || ! isset( $_POST['dame_import_csv_nonce'] ) || ! wp_verify_nonce( $_POST['dame_import_csv_nonce'], 'dame_import_csv_nonce_action' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", 'dame' ) );
	}

	if ( ! isset( $_FILES['dame_import_csv_file'] ) || $_FILES['dame_import_csv_file']['error'] !== UPLOAD_ERR_OK ) {
		dame_add_admin_notice( __( 'Erreur lors du téléversement du fichier.', 'dame' ), 'error' );
		return;
	}

	$file = $_FILES['dame_import_csv_file'];
	$mime_type = mime_content_type( $file['tmp_name'] );

	if ( 'text/plain' !== $mime_type && 'text/csv' !== $mime_type ) {
		dame_add_admin_notice( __( 'Le fichier téléversé n\'est pas un fichier CSV valide.', 'dame' ), 'error' );
		return;
	}

	// Increase execution time
	set_time_limit( 300 );

	$handle = fopen( $file['tmp_name'], 'r' );
	if ( false === $handle ) {
		dame_add_admin_notice( __( 'Impossible d\'ouvrir le fichier téléversé.', 'dame' ), 'error' );
		return;
	}

	// Read header row and map columns
	$header = fgetcsv( $handle, 0, ';' );
	if ( false === $header ) {
		dame_add_admin_notice( __( 'Impossible de lire l\'en-tête du fichier CSV.', 'dame' ), 'error' );
		fclose( $handle );
		return;
	}

	// Remove BOM from the first header element if present
	if ( isset( $header[0] ) ) {
		$header[0] = preg_replace( '/^\x{FEFF}/u', '', $header[0] );
	}

	$expected_headers = array(
		'Nom', 'Prénom', 'Date de naissance', 'Commune de naissance', 'Sexe', 'Adresse email', 'Numéro de téléphone', 'Adresse',
		'Complément', 'Code Postal', 'Ville', 'Numéro de licence', 'Etat de l\'adhésion',
		'Autre téléphone', 'Taille vêtements', 'Etablissement scolaire', 'Académie', 'Allergies connu', 'Elu local (O/N)',
	);
	$col_map = array_flip( $header );

	// Data mapping from CSV columns to post meta keys
	$meta_mapping = array(
		'Nom' => '_dame_last_name',
		'Prénom' => '_dame_first_name',
		'Date de naissance' => '_dame_birth_date',
		'Commune de naissance' => '_dame_birth_city',
		'Sexe' => '_dame_sexe',
		'Adresse email' => '_dame_email',
		'Numéro de téléphone' => '_dame_phone_number',
		'Adresse' => '_dame_address_1',
		'Complément' => '_dame_address_2',
		'Code Postal' => '_dame_postal_code',
		'Ville' => '_dame_city',
		'Numéro de licence' => '_dame_license_number',
		'Etat de l\'adhésion' => '_dame_membership_status',
		'Autre téléphone' => '_dame_autre_telephone',
		'Taille vêtements' => '_dame_taille_vetements',
		'Etablissement scolaire' => '_dame_school_name',
		'Académie' => '_dame_school_academy',
		'Allergies connu' => '_dame_allergies',
		'Elu local (O/N)' => '_dame_is_elu_local',
	);

	$imported_count = 0;
	$department_region_mapping = dame_get_department_region_mapping();

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

		$first_name = $member_data['Prénom'];
		$last_name = $member_data['Nom'];

		if ( empty( $first_name ) || empty( $last_name ) ) {
			continue; // Skip rows without a name
		}

		$post_data = array(
			'post_title'  => mb_strtoupper( $last_name, 'UTF-8' ) . ' ' . $first_name,
			'post_type'   => 'adherent',
			'post_status' => 'publish',
		);
		$post_id = wp_insert_post( $post_data );

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
			update_post_meta( $post_id, '_dame_license_type', 'Non précisé' );
			update_post_meta( $post_id, '_dame_arbitre_level', 'Non' );

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
	dame_add_admin_notice( $message );
}
add_action( 'admin_init', 'dame_handle_csv_import_action' );

/**
 * Gathers all adherent-related data for export.
 *
 * @return array The complete export data.
 */
function dame_get_adherent_export_data() {
    $export_data = array(
        'version'          => DAME_VERSION,
        'adherents'        => array(),
        'pre_inscriptions' => array(),
        'messages'         => array(),
        'message_opens'    => array(),
        'taxonomy_terms'   => array(),
        'options'          => array(),
    );

    // 1. Export the taxonomy terms for managed taxonomies
    $taxonomies_to_export = array( 'dame_saison_adhesion', 'dame_group' );
    foreach ( $taxonomies_to_export as $taxonomy ) {
        $terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            )
        );
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $export_data['taxonomy_terms'][ $taxonomy ] = array();
            foreach ( $terms as $term ) {
                $export_data['taxonomy_terms'][ $taxonomy ][] = array(
					'old_id'      => $term->term_id,
                    'name'        => $term->name,
                    'slug'        => $term->slug,
                    'description' => $term->description,
                );
            }
        }
    }

    // 2. Export the options
    $current_season_tag_id = get_option( 'dame_current_season_tag_id' );
    if ( $current_season_tag_id ) {
        $term = get_term( $current_season_tag_id );
        if ( $term && ! is_wp_error( $term ) ) {
            $export_data['options']['dame_current_season_tag_slug'] = $term->slug;
        }
    }

    // 3. Export adherents and their term relationships
    $adherents_query = new WP_Query(
        array(
            'post_type'      => 'adherent',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        )
    );

    if ( $adherents_query->have_posts() ) {
        while ( $adherents_query->have_posts() ) {
            $adherents_query->the_post();
            $post_id     = get_the_ID();
            $member_data = array(
				'old_id'     => $post_id,
                'post_title' => get_the_title(),
                'meta_data'  => array(),
                'taxonomies' => array(),
            );

            $all_meta = get_post_meta( $post_id );
            foreach ( $all_meta as $key => $value ) {
                if ( strpos( $key, '_dame_' ) === 0 ) {
                    $member_data['meta_data'][ $key ] = maybe_unserialize( $value[0] );
                }
            }

            foreach ( $taxonomies_to_export as $taxonomy ) {
                $terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
                if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                    $member_data['taxonomies'][ $taxonomy ] = $terms;
                }
            }

            $export_data['adherents'][] = $member_data;
        }
        wp_reset_postdata();
    }

    // 4. Export pre-inscriptions
    $pre_inscriptions_query = new WP_Query(
        array(
            'post_type'      => 'dame_pre_inscription',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        )
    );

    if ( $pre_inscriptions_query->have_posts() ) {
        while ( $pre_inscriptions_query->have_posts() ) {
            $pre_inscriptions_query->the_post();
            $post_id     = get_the_ID();
            $pre_inscription_data = array(
				'old_id'     => $post_id,
                'post_title' => get_the_title(),
                'meta_data'  => array(),
            );

            $all_meta = get_post_meta( $post_id );
            foreach ( $all_meta as $key => $value ) {
                if ( strpos( $key, '_dame_' ) === 0 ) {
                    $pre_inscription_data['meta_data'][ $key ] = maybe_unserialize( $value[0] );
                }
            }
            $export_data['pre_inscriptions'][] = $pre_inscription_data;
        }
        wp_reset_postdata();
    }

    // 5. Export messages
    $messages_query = new WP_Query(
        array(
            'post_type'      => 'dame_message',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        )
    );

    if ( $messages_query->have_posts() ) {
        while ( $messages_query->have_posts() ) {
            $messages_query->the_post();
            $post_id     = get_the_ID();
            $message_data = array(
                'old_id'       => $post_id,
                'post_title'   => get_the_title(),
                'post_content' => get_the_content(),
                'post_status'  => get_post_status(),
                'meta_data'    => array(),
            );

            $all_meta = get_post_meta( $post_id );
            foreach ( $all_meta as $key => $value ) {
                if ( strpos( $key, '_dame_' ) === 0 ) {
                    $message_data['meta_data'][ $key ] = maybe_unserialize( $value[0] );
                }
            }
            $export_data['messages'][] = $message_data;
        }
        wp_reset_postdata();
    }

    // 6. Export message opens
    global $wpdb;
    $table_name      = $wpdb->prefix . 'dame_message_opens';
    $open_stats_data = $wpdb->get_results( "SELECT message_id, email_hash, opened_at, user_ip FROM {$table_name}", ARRAY_A );
    if ( is_array( $open_stats_data ) ) {
        $export_data['message_opens'] = $open_stats_data;
    }

    return $export_data;
}

/**
 * Handles the export of member data.
 */
function dame_handle_export_action() {
    if ( ! isset( $_POST['dame_export_action'] ) || ! isset( $_POST['dame_export_nonce'] ) || ! wp_verify_nonce( $_POST['dame_export_nonce'], 'dame_export_nonce_action' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", "dame" ) );
    }

    $export_data = dame_get_adherent_export_data();

    $filename = 'dame-adherents-backup-' . date( 'Y-m-d' ) . '.json.gz';
    $data_to_compress = json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    $compressed_data = gzcompress( $data_to_compress );

    ob_clean();
    header( 'Content-Type: application/octet-stream' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Content-Length: ' . strlen( $compressed_data ) );
    echo $compressed_data;
    exit;
}
add_action( 'admin_init', 'dame_handle_export_action' );

/**
 * Handles the import of member data.
 */
function dame_handle_import_action() {
    if ( ! isset( $_POST['dame_import'] ) || ! isset( $_POST['dame_import_nonce'] ) || ! wp_verify_nonce( $_POST['dame_import_nonce'], 'dame_import_nonce_action' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", 'dame' ) );
    }

    if ( ! isset( $_FILES['dame_import_file'] ) || $_FILES['dame_import_file']['error'] !== UPLOAD_ERR_OK ) {
        dame_add_admin_notice( __( 'Erreur lors du téléversement du fichier.', 'dame' ), 'error' );
        return;
    }

    $file            = $_FILES['dame_import_file'];
    $filename        = $file['name'];
    $file_ext        = pathinfo( $filename, PATHINFO_EXTENSION );
    $file_ext_double = pathinfo( str_replace( '.gz', '', $filename ), PATHINFO_EXTENSION );

    if ( 'gz' !== $file_ext || 'json' !== $file_ext_double ) {
        dame_add_admin_notice( __( "Le fichier de sauvegarde téléversé n'est pas valide (format .json.gz attendu).", 'dame' ), 'error' );
        return;
    }

    $compressed_data = file_get_contents( $file['tmp_name'] );
    $json_data       = gzuncompress( $compressed_data );
    $import_data     = json_decode( $json_data, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        dame_add_admin_notice( __( 'Erreur lors de la lecture des données de la sauvegarde.', 'dame' ), 'error' );
        return;
    }

    // --- ID Mapping Tables ---
    $term_id_map = array();
    $adherent_id_map = array();
    $pre_inscription_id_map = array();
    $message_id_map = array();

    // --- Clear existing data ---
    // 1. Delete adherents, pre-inscriptions, and messages
    $post_types_to_delete = array( 'adherent', 'dame_pre_inscription', 'dame_message' );
    $existing_posts       = get_posts(
        array(
            'post_type'      => $post_types_to_delete,
            'posts_per_page' => -1,
            'post_status'    => 'any', // Ensure all statuses are included.
            'fields'         => 'ids',
        )
    );
    foreach ( $existing_posts as $post_id_to_delete ) {
        wp_delete_post( $post_id_to_delete, true ); // true to bypass trash
    }
    // 2. Clear message opens table
    global $wpdb;
    $table_name = $wpdb->prefix . 'dame_message_opens';
    $wpdb->query( "TRUNCATE TABLE {$table_name}" );

    // 3. Delete terms from managed taxonomies
    $taxonomies_to_clear = array( 'dame_saison_adhesion', 'dame_group' );
    foreach ( $taxonomies_to_clear as $taxonomy ) {
        $existing_terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids' ) );
        if ( ! is_wp_error( $existing_terms ) ) {
            foreach ( $existing_terms as $term_id ) {
                wp_delete_term( $term_id, $taxonomy );
            }
        }
    }

    // --- Import new data ---
    // 1. Import taxonomy terms and build map
    if ( ! empty( $import_data['taxonomy_terms'] ) && is_array( $import_data['taxonomy_terms'] ) ) {
        foreach ( $import_data['taxonomy_terms'] as $taxonomy => $terms ) {
            if ( ! empty( $terms ) && is_array( $terms ) ) {
                foreach ( $terms as $term_data ) {
					$old_id = isset($term_data['old_id']) ? $term_data['old_id'] : $term_data['slug']; // Fallback for old backups
                    $result = wp_insert_term(
                        $term_data['name'],
                        $taxonomy,
                        array(
                            'slug'        => $term_data['slug'],
                            'description' => $term_data['description'],
                        )
                    );
                    if ( ! is_wp_error( $result ) ) {
                        $term_id_map[ $old_id ] = $result['term_id'];
                    }
                }
            }
        }
    }

    // 2. Import adherents and build map
    $imported_count = 0;
    if ( ! empty( $import_data['adherents'] ) ) {
        foreach ( $import_data['adherents'] as $member_data ) {
            $post_data = array(
                'post_title'  => sanitize_text_field( $member_data['post_title'] ),
                'post_type'   => 'adherent',
                'post_status' => 'publish',
            );
            $new_post_id   = wp_insert_post( $post_data );

            if ( $new_post_id && ! is_wp_error( $new_post_id ) ) {
                if(isset($member_data['old_id'])) {
					$adherent_id_map[ $member_data['old_id'] ] = $new_post_id;
				}
                // Restore meta data
                if ( ! empty( $member_data['meta_data'] ) ) {
                    foreach ( $member_data['meta_data'] as $key => $value ) {
                        update_post_meta( $new_post_id, $key, $value );
                    }
                }
                // Restore taxonomy terms by slug (safer)
                if ( ! empty( $member_data['taxonomies'] ) && is_array( $member_data['taxonomies'] ) ) {
                    foreach ( $member_data['taxonomies'] as $taxonomy => $term_slugs ) {
                        wp_set_object_terms( $new_post_id, $term_slugs, $taxonomy );
                    }
                }
                $imported_count++;
            }
        }
    }

    // 3. Restore options
    if ( ! empty( $import_data['options']['dame_current_season_tag_slug'] ) ) {
        $term = get_term_by( 'slug', $import_data['options']['dame_current_season_tag_slug'], 'dame_saison_adhesion' );
        if ( $term && ! is_wp_error( $term ) ) {
            update_option( 'dame_current_season_tag_id', $term->term_id );
        }
    }

    // 4. Import pre-inscriptions and build map
    if ( ! empty( $import_data['pre_inscriptions'] ) ) {
        foreach ( $import_data['pre_inscriptions'] as $pre_inscription_data ) {
            $post_data = array(
                'post_title'  => sanitize_text_field( $pre_inscription_data['post_title'] ),
                'post_type'   => 'dame_pre_inscription',
                'post_status' => 'pending', // Pre-inscriptions are always pending
            );
            $new_post_id   = wp_insert_post( $post_data );

            if ( $new_post_id && ! is_wp_error( $new_post_id ) ) {
                if(isset($pre_inscription_data['old_id'])) {
					$pre_inscription_id_map[ $pre_inscription_data['old_id'] ] = $new_post_id;
				}
                if ( ! empty( $pre_inscription_data['meta_data'] ) ) {
                    foreach ( $pre_inscription_data['meta_data'] as $key => $value ) {
                        update_post_meta( $new_post_id, $key, $value );
                    }
                }
            }
        }
    }

    // 5. Import messages, remap recipient IDs, and build message ID map
    if ( ! empty( $import_data['messages'] ) && is_array( $import_data['messages'] ) ) {
        foreach ( $import_data['messages'] as $message_data ) {
            $post_data = array(
                'post_title'   => sanitize_text_field( $message_data['post_title'] ),
                'post_content' => wp_kses_post( $message_data['post_content'] ),
                'post_type'    => 'dame_message',
                'post_status'  => sanitize_key( $message_data['post_status'] ),
            );
            $new_post_id = wp_insert_post( $post_data );

            if ( $new_post_id && ! is_wp_error( $new_post_id ) ) {
                $message_id_map[ $message_data['old_id'] ] = $new_post_id;
                if ( ! empty( $message_data['meta_data'] ) ) {
                    foreach ( $message_data['meta_data'] as $key => $value ) {
                        $remapped_value = $value;
                        // Remap manual recipient IDs
                        if ( '_dame_manual_recipients' === $key && is_array( $value ) ) {
                            $remapped_value = array();
                            foreach ( $value as $old_adherent_id ) {
                                if ( isset( $adherent_id_map[ $old_adherent_id ] ) ) {
                                    $remapped_value[] = $adherent_id_map[ $old_adherent_id ];
                                }
                            }
                        }
                        // Remap term IDs (seasons, groups)
                        if ( in_array( $key, array( '_dame_recipient_seasons', '_dame_recipient_groups_saisonnier', '_dame_recipient_groups_permanent' ) ) && is_array( $value ) ) {
                             $remapped_value = array();
                            foreach ( $value as $old_term_id ) {
                                if ( isset( $term_id_map[ $old_term_id ] ) ) {
                                    $remapped_value[] = $term_id_map[ $old_term_id ];
                                }
                            }
                        }
                        update_post_meta( $new_post_id, $key, $remapped_value );
                    }
                }
            }
        }
    }

    // 6. Import message opens, using the new message ID map
    if ( ! empty( $import_data['message_opens'] ) && is_array( $import_data['message_opens'] ) ) {
        foreach ( $import_data['message_opens'] as $open_data ) {
            $old_message_id = $open_data['message_id'];
            if ( isset( $message_id_map[ $old_message_id ] ) ) {
                $new_message_id = $message_id_map[ $old_message_id ];
                $wpdb->insert(
                    $table_name,
                    array(
                        'message_id' => $new_message_id,
                        'email_hash' => sanitize_text_field( $open_data['email_hash'] ),
                        'opened_at'  => sanitize_text_field( $open_data['opened_at'] ),
                        'user_ip'    => sanitize_text_field( $open_data['user_ip'] ),
                    ),
                    array( '%d', '%s', '%s', '%s' )
                );
            }
        }
    }

    // --- Run migrations on imported data ---
    // This ensures that old backups are brought up to date with the current data structure.
    $imported_version = isset( $import_data['version'] ) ? $import_data['version'] : '2.2.0'; // Default to 2.2.0 for backups made before this feature.
    if ( function_exists( 'dame_perform_upgrade' ) ) {
        dame_perform_upgrade( $imported_version, DAME_VERSION );
    }

    $message = sprintf(
        _n(
            '%d adhérent a été importé avec succès.',
            '%d adhérents ont été importés avec succès.',
            $imported_count,
            'dame'
        ),
        $imported_count
    );
    dame_add_admin_notice( $message );
}
add_action( 'admin_init', 'dame_handle_import_action' );

/**
 * Adds an admin notice to be displayed on the next page load.
 */
function dame_add_admin_notice( $message, $type = 'success' ) {
    set_transient( 'dame_import_export_notice', array( 'message' => $message, 'type' => $type ), 30 );
}

/**
 * Displays the admin notice if one is set.
 */
function dame_display_import_export_notices() {
    if ( get_transient( 'dame_import_export_notice' ) ) {
        $notice = get_transient( 'dame_import_export_notice' );
        $message = $notice['message'];
        $type = $notice['type'];
        echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
        delete_transient( 'dame_import_export_notice' );
    }
}
add_action( 'admin_notices', 'dame_display_import_export_notices' );
