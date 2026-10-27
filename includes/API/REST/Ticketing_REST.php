<?php
/**
 * REST API Endpoints for HelloAsso Webhook & Ticketing.
 *
 * @package DAME\API\REST
 */

declare(strict_types=1);

namespace DAME\API\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;
use DAME\Services\HelloAsso\Client;
use DAME\Services\HelloAsso\WebhookProcessor;

/**
 * Class Ticketing_REST
 */
class Ticketing_REST {

	/**
	 * API namespace.
	 *
	 * @var non-falsy-string
	 */
	protected string $namespace = 'dame/v1';

	/**
	 * Webhook processor instance.
	 *
	 * @var WebhookProcessor
	 */
	private WebhookProcessor $processor;

	/**
	 * Constructor.
	 *
	 * @param WebhookProcessor|null $processor Optional processor.
	 */
	public function __construct( ?WebhookProcessor $processor = null ) {
		$this->processor = $processor ?? new WebhookProcessor();
	}

	/**
	 * Registers REST routes.
	 */
	public function register_routes(): void {
		// 1. HelloAsso Webhook endpoint (Public with token validation).
		register_rest_route(
			$this->namespace,
			'/helloasso/webhook',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_webhook' ),
					'permission_callback' => array( $this, 'validate_webhook_permission' ),
				),
			)
		);
	}

	/**
	 * Validates webhook token authenticity.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_webhook_permission( WP_REST_Request $request ): bool|WP_Error {
		$provided_token = (string) $request->get_param( 'token' );
		$expected_token = Client::get_or_create_webhook_token();

		if ( empty( $provided_token ) || ! hash_equals( $expected_token, $provided_token ) ) {
			return new WP_Error(
				'dame_ha_invalid_token',
				__( 'Jeton de webhook invalide ou absent.', 'dame' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Handles incoming webhook POST request.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function handle_webhook( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();

		if ( ! is_array( $body ) || empty( $body ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Payload JSON vide ou invalide.', 'dame' ),
				)
			);
		}

		$result = $this->processor->process( $body );

		return rest_ensure_response( $result );
	}
}
