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
 * Handles the export of member data.
 */
function dame_handle_export_action() {
    if ( ! isset( $_POST['dame_export_action'] ) || ! isset( $_POST['dame_export_nonce'] ) || ! wp_verify_nonce( $_POST['dame_export_nonce'], 'dame_export_nonce_action' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", "dame" ) );
    }

    $adherents_args = array(
        'post_type'      => 'adherent',
        'posts_per_page' => -1,
        'post_status'    => 'any',
    );

    $adherents_query = new WP_Query( $adherents_args );
    $export_data = array();

    if ( $adherents_query->have_posts() ) {
        while ( $adherents_query->have_posts() ) {
            $adherents_query->the_post();
            $post_id = get_the_ID();
            $member_data = array(
                'post_title' => get_the_title(),
            );

            $meta_keys = [
                '_dame_first_name', '_dame_last_name', '_dame_birth_date', '_dame_sexe', '_dame_license_number',
                '_dame_phone_number', '_dame_email', '_dame_address_1', '_dame_address_2', '_dame_postal_code',
                '_dame_city', '_dame_country', '_dame_region', '_dame_department', '_dame_school_name', '_dame_school_academy',
                '_dame_legal_rep_1_first_name', '_dame_legal_rep_1_last_name', '_dame_legal_rep_1_email', '_dame_legal_rep_1_phone',
                '_dame_legal_rep_1_address_1', '_dame_legal_rep_1_address_2', '_dame_legal_rep_1_postal_code', '_dame_legal_rep_1_city',
                '_dame_legal_rep_2_first_name', '_dame_legal_rep_2_last_name', '_dame_legal_rep_2_email', '_dame_legal_rep_2_phone',
                '_dame_legal_rep_2_address_1', '_dame_legal_rep_2_address_2', '_dame_legal_rep_2_postal_code', '_dame_legal_rep_2_city',
                '_dame_membership_date', '_dame_is_junior', '_dame_is_pole_excellence', '_dame_linked_wp_user',
                '_dame_arbitre_level', '_dame_membership_status'
            ];

            foreach ( $meta_keys as $meta_key ) {
                $member_data[ $meta_key ] = get_post_meta( $post_id, $meta_key, true );
            }
            $export_data[] = $member_data;
        }
        wp_reset_postdata();
    }

    $filename = 'dame-export-' . date( 'Y-m-d' ) . '.json';

    header( 'Content-Type: application/json' );
    header( 'Content-Disposition: attachment; filename=' . $filename );
    echo json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    wp_die();
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
        wp_die( esc_html__( "Vous n'avez pas la permission d'effectuer cette action.", "dame" ) );
    }

    if ( ! isset( $_FILES['dame_import_file'] ) || $_FILES['dame_import_file']['error'] !== UPLOAD_ERR_OK ) {
        dame_add_admin_notice( __( "Erreur lors du téléversement du fichier.", "dame" ), 'error' );
        return;
    }

    $file = $_FILES['dame_import_file'];
    if ( 'application/json' !== $file['type'] ) {
        dame_add_admin_notice( __( "Le fichier téléversé n'est pas un fichier JSON valide.", "dame" ), 'error' );
        return;
    }

    $json_data = file_get_contents( $file['tmp_name'] );
    $import_data = json_decode( $json_data, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        dame_add_admin_notice( __( "Erreur lors de la lecture des données JSON.", "dame" ), 'error' );
        return;
    }

    // Clear existing adherents before import
    $existing_adherents_args = array( 'post_type' => 'adherent', 'posts_per_page' => -1, 'fields' => 'ids' );
    $existing_adherents = get_posts( $existing_adherents_args );
    foreach ( $existing_adherents as $adherent_id ) {
        wp_delete_post( $adherent_id, true ); // true to bypass trash
    }

    $imported_count = 0;
    $meta_keys = [
        '_dame_first_name', '_dame_last_name', '_dame_birth_date', '_dame_sexe', '_dame_license_number',
        '_dame_phone_number', '_dame_email', '_dame_address_1', '_dame_address_2', '_dame_postal_code',
        '_dame_city', '_dame_country', '_dame_region', '_dame_department', '_dame_school_name', '_dame_school_academy',
        '_dame_legal_rep_1_first_name', '_dame_legal_rep_1_last_name', '_dame_legal_rep_1_email', '_dame_legal_rep_1_phone',
        '_dame_legal_rep_1_address_1', '_dame_legal_rep_1_address_2', '_dame_legal_rep_1_postal_code', '_dame_legal_rep_1_city',
        '_dame_legal_rep_2_first_name', '_dame_legal_rep_2_last_name', '_dame_legal_rep_2_email', '_dame_legal_rep_2_phone',
        '_dame_legal_rep_2_address_1', '_dame_legal_rep_2_address_2', '_dame_legal_rep_2_postal_code', '_dame_legal_rep_2_city',
        '_dame_membership_date', '_dame_is_junior', '_dame_is_pole_excellence', '_dame_linked_wp_user',
        '_dame_arbitre_level', '_dame_membership_status'
    ];

    foreach ( $import_data as $member_data ) {
        $post_data = array(
            'post_title'  => sanitize_text_field( $member_data['post_title'] ),
            'post_type'   => 'adherent',
            'post_status' => 'publish',
        );
        $post_id = wp_insert_post( $post_data );

        if ( $post_id ) {
            foreach ( $meta_keys as $meta_key ) {
                if ( isset( $member_data[ $meta_key ] ) ) {
                    update_post_meta( $post_id, $meta_key, sanitize_text_field( $member_data[ $meta_key ] ) );
                }
            }
            $imported_count++;
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
