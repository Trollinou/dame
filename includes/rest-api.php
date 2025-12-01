<?php
/**
 * File to handle all REST API endpoints for the DAME plugin.
 *
 * @package DAME
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Registers the custom REST API routes.
 */
function dame_register_rest_routes() {
    register_rest_route(
        'dame/v1',
        '/track',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'dame_handle_tracking_pixel',
            'permission_callback' => '__return_true', // Publicly accessible.
        )
    );
}
add_action( 'rest_api_init', 'dame_register_rest_routes' );

/**
 * Handles the tracking pixel request.
 *
 * This function is called when the tracking pixel image is loaded in the email client.
 * It records the email open event in the database.
 *
 * @param WP_REST_Request $request The request object.
 */
function dame_handle_tracking_pixel( $request ) {
    global $wpdb;

    $message_id = $request->get_param( 'mid' );
    $email_hash = $request->get_param( 'h' );

    // Sanitize parameters.
    $message_id = absint( $message_id );
    $email_hash = sanitize_text_field( $email_hash );

    // Basic validation.
    if ( empty( $message_id ) || empty( $email_hash ) || ! ctype_alnum( $email_hash ) || strlen( $email_hash ) !== 32 ) {
        // Invalid data, return a 400 Bad Request response, but still as a GIF.
        dame_send_pixel_response( 400 );
    }

    $table_name = $wpdb->prefix . 'dame_message_opens';
    $user_ip    = dame_get_user_ip();

    // Check if this open has already been recorded.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $existing_open = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE message_id = %d AND email_hash = %s",
            $message_id,
            $email_hash
        )
    );

    // If not already recorded, insert a new record.
    if ( null === $existing_open ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            $table_name,
            array(
                'message_id' => $message_id,
                'email_hash' => $email_hash,
                'opened_at'  => current_time( 'mysql', 1 ),
                'user_ip'    => $user_ip,
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
            )
        );
    }

    // Serve a 1x1 transparent GIF image.
    dame_send_pixel_response();
}

/**
 * Sends a 1x1 transparent GIF response.
 *
 * @param int $status_code The HTTP status code to send.
 */
function dame_send_pixel_response( $status_code = 200 ) {
    status_header( $status_code );
    header( 'Content-Type: image/gif' );
    header( 'Cache-Control: no-cache, no-store, must-revalidate' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );

    // 1x1 transparent GIF binary data.
    echo base64_decode( 'R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==' );
    exit;
}

/**
 * Gets the user's IP address.
 *
 * @return string The user's IP address.
 */
function dame_get_user_ip() {
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
    } else {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    }
    return $ip;
}
