<?php
/**
 * File for handling the Backup/Restore of Agenda content.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Gathers all agenda-related data for export.
 *
 * @return array The complete export data.
 */
function dame_get_agenda_export_data() {
    $export_data = array(
        'version'        => DAME_VERSION,
        'events'         => array(),
        'taxonomy_terms' => array(),
    );

    // 1. Export the taxonomy terms and their color meta
    $agenda_categories = get_terms(
        array(
            'taxonomy'   => 'dame_agenda_category',
            'hide_empty' => false,
        )
    );
    if ( ! is_wp_error( $agenda_categories ) ) {
        foreach ( $agenda_categories as $term ) {
            $term_meta = get_option( "taxonomy_" . $term->term_id );
            $color = isset( $term_meta['color'] ) ? $term_meta['color'] : '';

            $export_data['taxonomy_terms'][] = array(
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'color'       => $color,
            );
        }
    }

    // 2. Export events and their term relationships
    $events_query = new WP_Query(
        array(
            'post_type'      => 'dame_agenda',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        )
    );

    if ( $events_query->have_posts() ) {
        while ( $events_query->have_posts() ) {
            $events_query->the_post();
            $post_id     = get_the_ID();
            $event_data = array(
                'post_title'   => get_the_title(),
                'post_content' => get_the_content(),
                'meta_data'    => array(),
                'categories'   => array(),
            );

            $all_meta = get_post_meta( $post_id );
            foreach ( $all_meta as $key => $value ) {
                // We can backup all meta, not just _dame_ ones for events
                $event_data['meta_data'][ $key ] = maybe_unserialize( $value[0] );
            }

            $event_categories = wp_get_post_terms( $post_id, 'dame_agenda_category', array( 'fields' => 'slugs' ) );
            if ( ! is_wp_error( $event_categories ) ) {
                $event_data['categories'] = $event_categories;
            }

            $export_data['events'][] = $event_data;
        }
        wp_reset_postdata();
    }

    return $export_data;
}

/**
 * Handles the export of agenda data.
 */
function dame_handle_agenda_export_action() {
    if ( ! isset( $_POST['dame_agenda_backup_action'] ) || ! isset( $_POST['dame_agenda_backup_nonce'] ) || ! wp_verify_nonce( $_POST['dame_agenda_backup_nonce'], 'dame_agenda_backup_nonce_action' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", "dame" ) );
    }

    $export_data = dame_get_agenda_export_data();

    $filename = 'dame-agenda-backup-' . date( 'Y-m-d' ) . '.json.gz';
    $data_to_compress = json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    $compressed_data = gzcompress( $data_to_compress );

    ob_clean();
    header( 'Content-Type: application/octet-stream' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Content-Length: ' . strlen( $compressed_data ) );
    echo $compressed_data;
    exit;
}
add_action( 'admin_init', 'dame_handle_agenda_export_action' );

/**
 * Handles the import of agenda data.
 */
function dame_handle_agenda_import_action() {
    if ( ! isset( $_POST['dame_agenda_restore_action'] ) || ! isset( $_POST['dame_agenda_restore_nonce'] ) || ! wp_verify_nonce( $_POST['dame_agenda_restore_nonce'], 'dame_agenda_restore_nonce_action' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", 'dame' ) );
    }

    if ( ! isset( $_FILES['dame_agenda_restore_file'] ) || $_FILES['dame_agenda_restore_file']['error'] !== UPLOAD_ERR_OK ) {
        dame_add_admin_notice( __( 'Erreur lors du téléversement du fichier.', 'dame' ), 'error' );
        return;
    }

    $file            = $_FILES['dame_agenda_restore_file'];
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
    // 1. Delete events
    $existing_events = get_posts( array( 'post_type' => 'dame_agenda', 'posts_per_page' => -1, 'fields' => 'ids' ) );
    foreach ( $existing_events as $post_id_to_delete ) {
        wp_delete_post( $post_id_to_delete, true ); // true to bypass trash
    }
    // 2. Delete category terms
    $existing_terms = get_terms( array( 'taxonomy' => 'dame_agenda_category', 'hide_empty' => false, 'fields' => 'ids' ) );
    foreach ( $existing_terms as $term_id ) {
        delete_option( "taxonomy_$term_id" ); // Delete color meta
        wp_delete_term( $term_id, 'dame_agenda_category' );
    }

    // --- Import new data ---
    // 1. Import taxonomy terms
    if ( ! empty( $import_data['taxonomy_terms'] ) ) {
        foreach ( $import_data['taxonomy_terms'] as $term_data ) {
            $result = wp_insert_term(
                $term_data['name'],
                'dame_agenda_category',
                array(
                    'slug'        => $term_data['slug'],
                    'description' => $term_data['description'],
                )
            );

            if ( ! is_wp_error( $result ) && isset( $term_data['color'] ) ) {
                $term_id = $result['term_id'];
                update_option( "taxonomy_$term_id", array( 'color' => sanitize_hex_color( $term_data['color'] ) ) );
            }
        }
    }

    // 2. Import events and their relationships
    $imported_count = 0;
    if ( ! empty( $import_data['events'] ) ) {
        foreach ( $import_data['events'] as $event_data ) {
            $post_data = array(
                'post_title'   => sanitize_text_field( $event_data['post_title'] ),
                'post_content' => wp_kses_post( $event_data['post_content'] ),
                'post_type'    => 'dame_agenda',
                'post_status'  => 'publish',
            );
            $post_id   = wp_insert_post( $post_data );

            if ( $post_id ) {
                // Restore meta data
                if ( ! empty( $event_data['meta_data'] ) ) {
                    foreach ( $event_data['meta_data'] as $key => $value ) {
                        update_post_meta( $post_id, $key, $value );
                    }
                }
                // Restore category terms
                if ( ! empty( $event_data['categories'] ) ) {
                    wp_set_object_terms( $post_id, $event_data['categories'], 'dame_agenda_category' );
                }
                $imported_count++;
            }
        }
    }

    $message = sprintf(
        _n(
            '%d événement a été importé avec succès.',
            '%d événements ont été importés avec succès.',
            $imported_count,
            'dame'
        ),
        $imported_count
    );
    // Use the existing notice function if it exists, to be safe.
    if (function_exists('dame_add_admin_notice')) {
        dame_add_admin_notice( $message );
    }
}
add_action( 'admin_init', 'dame_handle_agenda_import_action' );
