<?php
/**
 * HelloAsso API Client.
 *
 * @package DAME\Services\HelloAsso
 */

declare(strict_types=1);

namespace DAME\Services\HelloAsso;

use WP_Error;

/**
 * Class Client
 *
 * Handles OAuth2 client credentials authentication and API v5 communication with HelloAsso.
 */
class Client {

	/**
	 * HelloAsso API base URL (Production fallback).
	 */
	public const API_BASE_URL = 'https://api.helloasso.com/v5';

	/**
	 * HelloAsso OAuth2 token URL (Production fallback).
	 */
	public const OAUTH_URL = 'https://api.helloasso.com/oauth2/token';

	/**
	 * Environment modes.
	 */
	public const ENV_PRODUCTION = 'production';
	public const ENV_SANDBOX    = 'sandbox';

	/**
	 * Transient key for caching the OAuth2 access token.
	 */
	public const TOKEN_TRANSIENT_KEY = 'dame_ha_access_token';

	/**
	 * Transient key for caching the OAuth2 refresh token.
	 */
	public const REFRESH_TRANSIENT_KEY = 'dame_ha_refresh_token';

	/**
	 * Retrieves the configured HelloAsso options.
	 *
	 * @return array{client_id: string, client_secret: string, org_slug: string, webhook_token: string, environment: string}
	 */
	public static function get_config(): array {
		$options = get_option( 'dame_options', array() );

		return array(
			'client_id'     => trim( (string) ( $options['helloasso_client_id'] ?? '' ) ),
			'client_secret' => trim( (string) ( $options['helloasso_client_secret'] ?? '' ) ),
			'org_slug'      => trim( (string) ( $options['helloasso_org_slug'] ?? '' ) ),
			'webhook_token' => trim( (string) ( $options['helloasso_webhook_token'] ?? '' ) ),
			'environment'   => trim( (string) ( $options['helloasso_environment'] ?? self::ENV_PRODUCTION ) ),
		);
	}

	/**
	 * Returns the OAuth2 token URL for the configured environment.
	 *
	 * @return string
	 */
	public static function get_oauth_url(): string {
		$cfg = self::get_config();
		return ( self::ENV_SANDBOX === $cfg['environment'] )
			? 'https://api.helloasso-sandbox.com/oauth2/token'
			: 'https://api.helloasso.com/oauth2/token';
	}

	/**
	 * Returns the API base URL (v5) for the configured environment.
	 *
	 * @return string
	 */
	public static function get_api_base_url(): string {
		$cfg = self::get_config();
		return ( self::ENV_SANDBOX === $cfg['environment'] )
			? 'https://api.helloasso-sandbox.com/v5'
			: 'https://api.helloasso.com/v5';
	}

	/**
	 * Checks whether the API credentials are fully configured.
	 *
	 * @return bool True if configured.
	 */
	public static function is_configured(): bool {
		$cfg = self::get_config();
		return ! empty( $cfg['client_id'] ) && ! empty( $cfg['client_secret'] ) && ! empty( $cfg['org_slug'] );
	}

	/**
	 * Executes a token request against the HelloAsso OAuth2 endpoint.
	 *
	 * @param array<string, string> $body_params Parameters for x-www-form-urlencoded body.
	 * @return string|WP_Error Access token or WP_Error.
	 */
	private static function request_token( array $body_params ): string|WP_Error {
		$cfg = self::get_config();
		$url = self::get_oauth_url();

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
				'body'    => http_build_query( $body_params, '', '&' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );

		if ( 200 !== $status || ! is_array( $data ) || empty( $data['access_token'] ) ) {
			$error_code = is_array( $data ) && isset( $data['error'] ) ? (string) $data['error'] : '';
			$error_desc = is_array( $data ) && isset( $data['error_description'] ) ? (string) $data['error_description'] : '';

			if ( empty( $error_desc ) && is_array( $data ) && isset( $data['message'] ) ) {
				$error_desc = (string) $data['message'];
			}

			$msg = sprintf( 'HTTP %d', $status );
			if ( ! empty( $error_code ) || ! empty( $error_desc ) ) {
				$msg .= sprintf( ' — %s : %s', $error_code ?: 'Erreur', $error_desc ?: $body );
			} else {
				$status_message = wp_remote_retrieve_response_message( $response );
				$msg           .= sprintf( ' — %s', $status_message ?: $body );
			}

			if ( 'invalid_client' === $error_code || 400 === $status || 401 === $status ) {
				$env_name = ( self::ENV_SANDBOX === $cfg['environment'] ) ? 'Sandbox / Test (helloasso-sandbox.com)' : 'Production (helloasso.com)';
				$msg     .= sprintf(
					' [%s : %s]',
					__( 'Vérifiez votre Client ID, Client Secret et l\'environnement actif', 'dame' ),
					$env_name
				);
			}

			return new WP_Error( 'dame_ha_auth_failed', $msg, array( 'status' => $status ) );
		}

		$token      = (string) $data['access_token'];
		$expires_in = isset( $data['expires_in'] ) ? max( 60, (int) $data['expires_in'] - 120 ) : 1800;

		set_transient( self::TOKEN_TRANSIENT_KEY, $token, $expires_in );

		// Store refresh token for future renewals.
		if ( ! empty( $data['refresh_token'] ) ) {
			set_transient( self::REFRESH_TRANSIENT_KEY, (string) $data['refresh_token'], 30 * DAY_IN_SECONDS );
		}

		return $token;
	}

	/**
	 * Gets a valid OAuth2 access token, fetching or refreshing it via the transient cache.
	 *
	 * @param bool $force_refresh Whether to bypass the transient cache.
	 * @return string|WP_Error Access token string or WP_Error on failure.
	 */
	public static function get_access_token( bool $force_refresh = false ): string|WP_Error {
		if ( ! $force_refresh ) {
			$cached = get_transient( self::TOKEN_TRANSIENT_KEY );
			if ( is_string( $cached ) && ! empty( $cached ) ) {
				return $cached;
			}
		}

		$cfg = self::get_config();
		if ( empty( $cfg['client_id'] ) || empty( $cfg['client_secret'] ) ) {
			return new WP_Error(
				'dame_ha_not_configured',
				__( 'Identifiants API HelloAsso non renseignés dans les réglages.', 'dame' )
			);
		}

		// 1. If not forcing a full refresh, attempt renewal using refresh_token if available.
		if ( ! $force_refresh ) {
			$refresh_token = get_transient( self::REFRESH_TRANSIENT_KEY );
			if ( is_string( $refresh_token ) && ! empty( $refresh_token ) ) {
				$renewed = self::request_token(
					array(
						'grant_type'    => 'refresh_token',
						'refresh_token' => $refresh_token,
						'client_id'     => $cfg['client_id'],
					)
				);

				if ( ! is_wp_error( $renewed ) ) {
					return $renewed;
				}
				// If refresh token failed (expired or revoked), delete it and fallback to client_credentials.
				delete_transient( self::REFRESH_TRANSIENT_KEY );
			}
		}

		// 2. Full client_credentials authentication.
		return self::request_token(
			array(
				'grant_type'    => 'client_credentials',
				'client_id'     => $cfg['client_id'],
				'client_secret' => $cfg['client_secret'],
			)
		);
	}

	/**
	 * Executes an authenticated GET request to the HelloAsso API v5.
	 *
	 * @param string               $endpoint Endpoint relative to /v5 (e.g. 'organizations/{slug}/forms').
	 * @param array<string, mixed> $query_args Optional query parameters.
	 * @return array<string, mixed>|WP_Error Decoded JSON response or WP_Error.
	 */
	public static function get( string $endpoint, array $query_args = array() ): array|WP_Error {
		$token = self::get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$url = self::get_api_base_url() . '/' . ltrim( $endpoint, '/' );
		if ( ! empty( $query_args ) ) {
			$url = add_query_arg( $query_args, $url );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );

		if ( $status >= 400 ) {
			$msg = is_array( $data ) && isset( $data['message'] ) ? (string) $data['message'] : sprintf( 'Erreur HTTP %d', $status );
			return new WP_Error( 'dame_ha_api_error', $msg, array( 'status' => $status ) );
		}

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Tests API connectivity and verifies that the organization exists.
	 *
	 * @return array{success: bool, message: string, org_name?: string}
	 */
	public static function test_connection(): array {
		$cfg = self::get_config();
		if ( ! self::is_configured() ) {
			return array(
				'success' => false,
				'message' => __( 'Veuillez renseigner le Client ID, le Client Secret et le Slug de l\'organisation.', 'dame' ),
			);
		}

		// Force live token validation (bypassing any stale cache).
		$token = self::get_access_token( true );
		if ( is_wp_error( $token ) ) {
			return array(
				'success' => false,
				'message' => $token->get_error_message(),
			);
		}

		$res = self::get( 'organizations/' . rawurlencode( $cfg['org_slug'] ) );
		if ( is_wp_error( $res ) ) {
			return array(
				'success' => false,
				'message' => $res->get_error_message(),
			);
		}

		$org_name = $res['name'] ?? $cfg['org_slug'];

		return array(
			'success'  => true,
			/* translators: %s: Organisation name */
			'message'  => sprintf( __( 'Connexion réussie avec l\'organisation « %s » !', 'dame' ), (string) $org_name ),
			'org_name' => (string) $org_name,
		);
	}

	/**
	 * Generates or retrieves a secure random webhook token.
	 *
	 * @return string 32-character hexadecimal token.
	 */
	public static function get_or_create_webhook_token(): string {
		$options = get_option( 'dame_options', array() );
		$token   = trim( (string) ( $options['helloasso_webhook_token'] ?? '' ) );

		if ( empty( $token ) ) {
			$token                              = wp_generate_password( 32, false, false );
			$options['helloasso_webhook_token'] = $token;
			update_option( 'dame_options', $options, false );
		}

		return $token;
	}

	/**
	 * Returns the complete public Webhook URL to configure in the HelloAsso back-office.
	 *
	 * @return string Full webhook URL.
	 */
	public static function get_webhook_url(): string {
		$token = self::get_or_create_webhook_token();
		return rest_url( 'dame/v1/helloasso/webhook' ) . '?token=' . rawurlencode( $token );
	}
}
