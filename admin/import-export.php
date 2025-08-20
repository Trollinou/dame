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
		__( 'Nom', 'dame' ), __( 'Prénom', 'dame' ), __( 'Date de naissance', 'dame' ), __( 'Code postal de naissance', 'dame' ), __( 'Commune de naissance', 'dame' ), __( 'Sexe', 'dame' ), __( 'Profession', 'dame' ), __( 'Adresse email', 'dame' ), __( 'Numéro de téléphone', 'dame' ),
		__( 'Adresse 1', 'dame' ), __( 'Adresse 2', 'dame' ), __( 'Code Postal', 'dame' ), __( 'Ville', 'dame' ), __( 'Pays', 'dame' ), __( 'Numéro de licence', 'dame' ),
		__( 'Type de licence', 'dame' ), __( 'Ecole d\'échecs (O/N)', 'dame' ), __( 'Pôle excellence (O/N)', 'dame' ), __( 'Bénévole (O/N)', 'dame' ), __( 'Elu local (O/N)', 'dame' ), __( 'Arbitre', 'dame' ),
		__( 'Représentant légal 1 - Nom', 'dame' ), __( 'Représentant légal 1 - Prénom', 'dame' ), __( 'Représentant légal 1 - Profession', 'dame' ), __( 'Représentant légal 1 - Email', 'dame' ), __( 'Représentant légal 1 - Téléphone', 'dame' ),
		__( 'Représentant légal 1 - Adresse 1', 'dame' ), __( 'Représentant légal 1 - Adresse 2', 'dame' ), __( 'Représentant légal 1 - Code Postal', 'dame' ), __( 'Représentant légal 1 - Ville', 'dame' ),
		__( 'Représentant légal 2 - Nom', 'dame' ), __( 'Représentant légal 2 - Prénom', 'dame' ), __( 'Représentant légal 2 - Profession', 'dame' ), __( 'Représentant légal 2 - Email', 'dame' ), __( 'Représentant légal 2 - Téléphone', 'dame' ),
		__( 'Représentant légal 2 - Adresse 1', 'dame' ), __( 'Représentant légal 2 - Adresse 2', 'dame' ), __( 'Représentant légal 2 - Code Postal', 'dame' ), __( 'Représentant légal 2 - Ville', 'dame' ),
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
				get_post_meta( $post_id, '_dame_last_name', true ),
				get_post_meta( $post_id, '_dame_first_name', true ),
				$formatted_birth_date,
				get_post_meta( $post_id, '_dame_birth_postal_code', true ),
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
		'Nom', 'Prénom', 'Date de naissance', 'Code postal de naissance', 'Commune de naissance', 'Sexe', 'Adresse email', 'Numéro de téléphone', 'Adresse 1',
		'Adresse 2', 'Code Postal', 'Ville', 'Numéro de licence', 'Etat de l\'adhésion',
		'Autre téléphone', 'Taille vêtements', 'Etablissement scolaire', 'Académie', 'Allergies connu', 'Elu local (O/N)',
	);
	$col_map = array_flip( $header );

	// Data mapping from CSV columns to post meta keys
	$meta_mapping = array(
		'Nom' => '_dame_last_name',
		'Prénom' => '_dame_first_name',
		'Date de naissance' => '_dame_birth_date',
		'Code postal de naissance' => '_dame_birth_postal_code',
		'Commune de naissance' => '_dame_birth_city',
		'Sexe' => '_dame_sexe',
		'Adresse email' => '_dame_email',
		'Numéro de téléphone' => '_dame_phone_number',
		'Adresse 1' => '_dame_address_1',
		'Adresse 2' => '_dame_address_2',
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
			'post_title'  => strtoupper( $last_name ) . ' ' . $first_name,
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
					$normalized_value = strtoupper( trim( $value ) );

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
					$value = ( strtoupper( trim( $value ) ) === 'O' ) ? 1 : 0;
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
 * Handles the export of member data.
 */
function dame_handle_export_action() {
    if ( ! isset( $_POST['dame_export_action'] ) || ! isset( $_POST['dame_export_nonce'] ) || ! wp_verify_nonce( $_POST['dame_export_nonce'], 'dame_export_nonce_action' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", "dame" ) );
    }

    $export_data = array(
        'adherents'      => array(),
        'taxonomy_terms' => array(),
        'options'        => array(),
    );

    // 1. Export the taxonomy terms
    $saison_terms = get_terms(
        array(
            'taxonomy'   => 'dame_saison_adhesion',
            'hide_empty' => false,
        )
    );
    if ( ! is_wp_error( $saison_terms ) ) {
        foreach ( $saison_terms as $term ) {
            $export_data['taxonomy_terms'][] = array(
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
            );
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
                'post_title' => get_the_title(),
                'meta_data'  => array(),
                'saisons'    => array(),
            );

            // Get all post meta
            $all_meta = get_post_meta( $post_id );
            foreach ( $all_meta as $key => $value ) {
                // We only care about our own meta keys
                if ( strpos( $key, '_dame_' ) === 0 ) {
                    $member_data['meta_data'][ $key ] = maybe_unserialize( $value[0] );
                }
            }

            // Get assigned season slugs
            $adherent_saisons = wp_get_post_terms( $post_id, 'dame_saison_adhesion', array( 'fields' => 'slugs' ) );
            if ( ! is_wp_error( $adherent_saisons ) ) {
                $member_data['saisons'] = $adherent_saisons;
            }

            $export_data['adherents'][] = $member_data;
        }
        wp_reset_postdata();
    }

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

    // --- Clear existing data ---
    // 1. Delete adherents
    $existing_adherents = get_posts( array( 'post_type' => 'adherent', 'posts_per_page' => -1, 'fields' => 'ids' ) );
    foreach ( $existing_adherents as $adherent_id ) {
        wp_delete_post( $adherent_id, true ); // true to bypass trash
    }
    // 2. Delete season terms
    $existing_terms = get_terms( array( 'taxonomy' => 'dame_saison_adhesion', 'hide_empty' => false, 'fields' => 'ids' ) );
    foreach ( $existing_terms as $term_id ) {
        wp_delete_term( $term_id, 'dame_saison_adhesion' );
    }

    // --- Import new data ---
    // 1. Import taxonomy terms
    if ( ! empty( $import_data['taxonomy_terms'] ) ) {
        foreach ( $import_data['taxonomy_terms'] as $term_data ) {
            wp_insert_term( $term_data['name'], 'dame_saison_adhesion', array(
                'slug'        => $term_data['slug'],
                'description' => $term_data['description'],
            ) );
        }
    }

    // 2. Import adherents and their relationships
    $imported_count = 0;
    if ( ! empty( $import_data['adherents'] ) ) {
        foreach ( $import_data['adherents'] as $member_data ) {
            $post_data = array(
                'post_title'  => sanitize_text_field( $member_data['post_title'] ),
                'post_type'   => 'adherent',
                'post_status' => 'publish',
            );
            $post_id   = wp_insert_post( $post_data );

            if ( $post_id ) {
                // Restore meta data
                if ( ! empty( $member_data['meta_data'] ) ) {
                    foreach ( $member_data['meta_data'] as $key => $value ) {
                        update_post_meta( $post_id, $key, $value );
                    }
                }
                // Restore season terms
                if ( ! empty( $member_data['saisons'] ) ) {
                    wp_set_object_terms( $post_id, $member_data['saisons'], 'dame_saison_adhesion' );
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

    $message = sprintf(
        /* translators: %d: number of members imported */
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
 *
 * @param string $message The message to display.
 * @param string $type    The type of notice ('success', 'error', 'warning', 'info'). Defaults to 'success'.
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
