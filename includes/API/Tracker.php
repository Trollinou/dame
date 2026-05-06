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
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registers the custom REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
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
	 * @return void
	 */
	public function handle_tracking_pixel( $request ): void {
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
		$now        = current_time( 'mysql', true );

		// Update all recipients sharing this email for this message.
		// Since we now have one row per recipient, we mark everyone with this email as "opened".
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE {$table_name} 
			SET opened_at = %s, user_ip = %s 
			WHERE message_id = %d AND email_hash = %s",
			$now, $user_ip, $message_id, $email_hash
		) );

		// Fallback: If no rows were updated (e.g. log missing but pixel hit), 
		// we could insert a generic row, but it's better to log only known recipients.

		// Serve a 1x1 transparent GIF image.
		$this->send_pixel_response();
	}

	/**
	 * Sends a 1x1 transparent GIF response.
	 *
	 * @param int $status_code The HTTP status code to send.
	 * @return void
	 */
	public function send_pixel_response( $status_code = 200 ): void {
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
	public function get_user_ip(): string {
		$ip = '';

		// Check for proxy headers but validate they are valid IPs.
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// X-Forwarded-For can contain a list of IPs. We take the first one.
			$ips = explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
		}

		// Validate the IP address format.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return '0.0.0.0';
		}

		return (string) $ip;
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
