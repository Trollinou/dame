<?php
/**
 * REST API Data Endpoints.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\API\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use DAME\Services\Data_Provider;

/**
 * Class Data_Endpoints
 * Exposes static lookup data from Data_Provider via the REST API.
 */
class Data_Endpoints {

	/**
	 * Namespace for the API.
	 *
	 * @var string
	 */
	protected string $namespace = 'dame/v1';

	/**
	 * Base path for the resource.
	 *
	 * @var string
	 */
	protected string $rest_base = 'data';

	/**
	 * Birthday service.
	 *
	 * @var \DAME\Services\Birthday
	 */
	private \DAME\Services\Birthday $birthday_service;

	/**
	 * Constructor.
	 *
	 * @param \DAME\Services\Birthday $birthday_service Birthday service instance.
	 */
	public function __construct( \DAME\Services\Birthday $birthday_service ) {
		$this->birthday_service = $birthday_service;
	}

	/**
	 * Initialize the class and register hooks.
	 */
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<type>[a-zA-Z0-9_-]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_data' ],
					'permission_callback' => [ $this, 'get_permissions_check' ],
					'args'                => [
						'type' => [
							'validate_callback' => function( $param ) {
								return is_string( $param );
							},
							'required'          => true,
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/birthdays/today',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_today_birthdays' ],
					'permission_callback' => [ $this, 'get_permissions_check' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/birthdays/upcoming',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_upcoming_birthdays' ],
					'permission_callback' => [ $this, 'get_permissions_check' ],
					'args'                => [
						'limit' => [
							'default'           => 10,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/pwa-menu',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_pwa_menu' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	/**
	 * Permission callback for the endpoints.
	 *
	 * @return bool
	 */
	public function get_permissions_check(): bool {
		// Only users who can edit posts (Staff, Coaches, Admins) can access this data.
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Retrieves the PWA menu items.
	 *
	 * @return WP_REST_Response
	 */
	public function get_pwa_menu(): WP_REST_Response {
		$menu_items = wp_get_nav_menu_items( 'Menu_PWA' );

		if ( ! $menu_items || is_wp_error( $menu_items ) ) {
			return rest_ensure_response( [] );
		}

		$data = array_map(
			function ( $item ) {
				return [
					'id'         => (int) $item->ID,
					'object_id'  => (int) $item->object_id,
					'parent'     => (int) $item->menu_item_parent,
					'title'      => (string) $item->title,
					'menu_order' => (int) $item->menu_order,
				];
			},
			$menu_items
		);

		return rest_ensure_response( $data );
	}

	/**
	 * Retrieves members having their birthday in the coming days.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_upcoming_birthdays( WP_REST_Request $request ): WP_REST_Response {
		$limit  = (int) $request['limit'];
		$result = $this->birthday_service->get_upcoming_birthdays( $limit );
		return rest_ensure_response( $result );
	}

	/**
	 * Retrieves members having their birthday today.
	 *
	 * @return WP_REST_Response
	 */
	public function get_today_birthdays(): WP_REST_Response {
		$birthdays = $this->birthday_service->get_today_birthdays();
		return rest_ensure_response( $birthdays );
	}

	/**
	 * Callback to retrieve the data based on the requested type.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_data( WP_REST_Request $request ): mixed {
		$type = $request['type'];
		$data = null;

		switch ( $type ) {
			case 'countries':
				$data = Data_Provider::get_countries();
				break;
			case 'regions':
				$data = Data_Provider::get_regions();
				break;
			case 'departments':
				$data = Data_Provider::get_departments();
				break;
			case 'department-region-mapping':
				$data = Data_Provider::get_department_region_mapping();
				break;
			case 'academies':
				$data = Data_Provider::get_academies();
				break;
			case 'health-document-options':
				$data = Data_Provider::get_health_document_options();
				break;
			case 'clothing-sizes':
				$data = Data_Provider::get_clothing_sizes();
				break;
			default:
				return new \WP_Error(
					'rest_invalid_type',
					__( 'Type de données invalide.', 'dame' ),
					[ 'status' => 404 ]
				);
		}

		return rest_ensure_response( $data );
	}
}
