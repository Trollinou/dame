<?php
/**
 * Tracker API Handler.
 *
 * @package DAME
 */

namespace DAME\API;

use WP_REST_Server;
use WP_REST_Request;

/**
 * Class Tracker
 */
class Tracker {

	/**
	 * Initialize the Tracker.
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registers the custom REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			'dame/v1',
			'/track',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_tracking_pixel' ],
				'permission_callback' => '__return_true', // Publicly accessible.
			)
		);
	}

	/**
	 * Handles the tracking pixel request.
	 *
	 * This function is called when the tracking pixel image is loaded in the email client.
	 * It records the email open event in the database.
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public function handle_tracking_pixel( $request ) {
		global $wpdb;

		$message_id = $request->get_param( 'mid' );
		$email_hash = $request->get_param( 'h' );

		// Sanitize parameters.
		$message_id = absint( $message_id );
		$email_hash = sanitize_text_field( $email_hash );

		// Basic validation for MD5 hash.
		if ( empty( $message_id ) || ! preg_match( '/^[a-f0-9]{32}$/', $email_hash ) ) {
			// Invalid data, return a 400 Bad Request response, but still as a GIF.
			$this->send_pixel_response( 400 );
		}

		$table_name = $wpdb->prefix . 'dame_message_opens';
		$user_ip    = $this->get_user_ip();

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
		$this->send_pixel_response();
	}

	/**
	 * Sends a 1x1 transparent GIF response.
	 *
	 * @param int $status_code The HTTP status code to send.
	 */
	public function send_pixel_response( $status_code = 200 ) {
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
	public function get_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} else {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		}
		return $ip;
	}

	/**
	 * Helper to get the tracking pixel URL.
	 *
	 * @param int    $message_id The message ID.
	 * @param string $email      The recipient's email.
	 * @return string The tracking pixel URL.
	 */
	public static function get_pixel_url( $message_id, $email ) {
		$email_hash = md5( mb_strtolower( trim( $email ), 'UTF-8' ) );
		return rest_url( "dame/v1/track?mid={$message_id}&h={$email_hash}" );
	}
}
