<?php
/**
 * File for handling backup and restore of learning content.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles the export of learning data.
 */
function dame_handle_backup_action() {
    if ( ! isset( $_POST['dame_backup_action'] ) || ! isset( $_POST['dame_backup_nonce'] ) || ! wp_verify_nonce( $_POST['dame_backup_nonce'], 'dame_backup_nonce_action' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", "dame" ) );
    }

    $post_types = array( 'dame_lecon', 'dame_exercice', 'dame_cours' );
    $taxonomy = 'dame_chess_category';

    $export_data = array(
        'posts' => array(),
        'terms' => array(),
    );

    // Export terms
    $terms = get_terms( array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ) );

    foreach ( $terms as $term ) {
        $export_data['terms'][] = array(
            'term_id'     => $term->term_id,
            'name'        => $term->name,
            'slug'        => $term->slug,
            'description' => $term->description,
            'parent'      => $term->parent,
        );
    }

    // Export posts
    $posts_query = new WP_Query( array(
        'post_type'      => $post_types,
        'posts_per_page' => -1,
        'post_status'    => 'any',
    ) );

    if ( $posts_query->have_posts() ) {
        while ( $posts_query->have_posts() ) {
            $posts_query->the_post();
            $post_id = get_the_ID();
            $post_data = array(
                'post_title'   => get_the_title(),
                'post_content' => get_the_content(),
                'post_excerpt' => get_the_excerpt(),
                'post_status'  => get_post_status(),
                'post_type'    => get_post_type(),
                'post_name'    => get_post_field( 'post_name' ),
                'meta_input'   => array(),
                'tax_input'    => array(),
            );

            // Get all post meta
            $meta = get_post_meta( $post_id );
            foreach ( $meta as $key => $value ) {
                $post_data['meta_input'][ $key ] = maybe_unserialize( $value[0] );
            }

            // Get all terms for the post
            $terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
            if ( ! is_wp_error( $terms ) ) {
                $post_data['tax_input'][ $taxonomy ] = $terms;
            }

            $export_data['posts'][] = $post_data;
        }
        wp_reset_postdata();
    }

    $filename = 'dame-apprentissage-backup-' . date( 'Y-m-d' ) . '.json.gz';
    $data_to_compress = json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    $compressed_data = gzcompress( $data_to_compress );

    ob_clean();
    header( 'Content-Type: application/octet-stream' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Content-Length: ' . strlen( $compressed_data ) );
    echo $compressed_data;
    exit;
}
add_action( 'admin_init', 'dame_handle_backup_action' );

/**
 * Handles the import of learning data.
 */
function dame_handle_restore_action() {
    if ( ! isset( $_POST['dame_restore_action'] ) || ! isset( $_POST['dame_restore_nonce'] ) || ! wp_verify_nonce( $_POST['dame_restore_nonce'], 'dame_restore_nonce_action' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", "dame" ) );
    }

    if ( ! isset( $_FILES['dame_restore_file'] ) || $_FILES['dame_restore_file']['error'] !== UPLOAD_ERR_OK ) {
        dame_add_admin_notice( __( "Erreur lors du téléversement du fichier.", "dame" ), 'error' );
        return;
    }

    $file = $_FILES['dame_restore_file'];
    $filename = $file['name'];
    $file_ext = pathinfo( $filename, PATHINFO_EXTENSION );
    $file_ext_double = pathinfo( str_replace( '.gz', '', $filename ), PATHINFO_EXTENSION );

    if ( $file_ext !== 'gz' || $file_ext_double !== 'json' ) {
        dame_add_admin_notice( __( "Le fichier téléversé n'est pas une sauvegarde valide (format .json.gz attendu).", "dame" ), 'error' );
        return;
    }

    $compressed_data = file_get_contents( $file['tmp_name'] );
    $json_data = gzuncompress( $compressed_data );
    $import_data = json_decode( $json_data, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        dame_add_admin_notice( __( "Erreur lors de la lecture des données JSON.", "dame" ), 'error' );
        return;
    }

    // Clear existing data
    $post_types = array( 'dame_lecon', 'dame_exercice', 'dame_cours' );
    $taxonomy = 'dame_chess_category';

    $existing_posts = get_posts( array( 'post_type' => $post_types, 'posts_per_page' => -1, 'fields' => 'ids' ) );
    foreach ( $existing_posts as $post_id ) {
        wp_delete_post( $post_id, true ); // true to bypass trash
    }

    $existing_terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids' ) );
    foreach ( $existing_terms as $term_id ) {
        wp_delete_term( $term_id, $taxonomy );
    }

    // Import terms
    $term_map = array(); // old_id => new_id
    if ( ! empty( $import_data['terms'] ) ) {
        foreach ( $import_data['terms'] as $term_data ) {
            $new_term = wp_insert_term( $term_data['name'], $taxonomy, array(
                'slug'        => $term_data['slug'],
                'description' => $term_data['description'],
                'parent'      => 0, // Will be updated later
            ) );
            if ( ! is_wp_error( $new_term ) ) {
                $term_map[ $term_data['term_id'] ] = $new_term['term_id'];
            }
        }

        // Update term parents
        foreach ( $import_data['terms'] as $term_data ) {
            if ( $term_data['parent'] && isset( $term_map[ $term_data['term_id'] ], $term_map[ $term_data['parent'] ] ) ) {
                wp_update_term( $term_map[ $term_data['term_id'] ], $taxonomy, array(
                    'parent' => $term_map[ $term_data['parent'] ],
                ) );
            }
        }
    }

    // Import posts
    if ( ! empty( $import_data['posts'] ) ) {
        foreach ( $import_data['posts'] as $post_data ) {
            $post_id = wp_insert_post( $post_data, true );
            if ( ! is_wp_error( $post_id ) ) {
                // The terms are already set by tax_input in wp_insert_post
            }
        }
    }

    dame_add_admin_notice( __( "La restauration des données d'apprentissage a été effectuée avec succès.", "dame" ) );
}
add_action( 'admin_init', 'dame_handle_restore_action' );
