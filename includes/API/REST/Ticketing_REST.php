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
use DAME\Repositories\TicketingRepository;
use DAME\Services\HelloAsso\Matcher;

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
	 * Ticketing repository instance.
	 *
	 * @var TicketingRepository
	 */
	private TicketingRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param WebhookProcessor|null    $processor  Optional processor.
	 * @param TicketingRepository|null $repository Optional repository.
	 */
	public function __construct( ?WebhookProcessor $processor = null, ?TicketingRepository $repository = null ) {
		$this->processor  = $processor ?? new WebhookProcessor();
		$this->repository = $repository ?? new TicketingRepository();
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

		// 2. PWA: List all ticketing campaigns with stats.
		register_rest_route(
			$this->namespace,
			'/ticketing/campaigns',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_campaigns' ),
					'permission_callback' => array( $this, 'check_pwa_permission' ),
					'args'                => array(
						'form_type' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => static fn( $param ) => is_string( $param ) && strlen( $param ) <= 50,
						),
						'orderby'   => array(
							'type'              => 'string',
							'required'          => false,
							'default'           => 'start_date',
							'enum'              => array( 'start_date', 'title', 'id', 'form_type' ),
							'sanitize_callback' => 'sanitize_key',
						),
						'order'     => array(
							'type'              => 'string',
							'required'          => false,
							'default'           => 'desc',
							'enum'              => array( 'asc', 'desc', 'ASC', 'DESC' ),
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);

		// 3. PWA: Get items/inscrits for a specific campaign.
		register_rest_route(
			$this->namespace,
			'/ticketing/campaigns/(?P<id>\d+)/items',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_campaign_items' ),
					'permission_callback' => array( $this, 'check_pwa_permission' ),
					'args'                => array(
						'id'         => array(
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => static fn( $p ) => is_numeric( $p ) && (int) $p > 0,
						),
						'checked_in' => array(
							'type'     => 'string',
							'required' => false,
						),
						'tier'       => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_onsite_item' ),
					'permission_callback' => array( $this, 'check_pwa_permission' ),
					'args'                => array(
						'id'                     => array(
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => static fn( $p ) => is_numeric( $p ) && (int) $p > 0,
						),
						'participant_first_name' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'participant_last_name'  => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		// 4. PWA: Toggle checkin for an item.
		register_rest_route(
			$this->namespace,
			'/ticketing/items/(?P<id>\d+)/checkin',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'toggle_checkin' ),
					'permission_callback' => array( $this, 'check_pwa_permission' ),
					'args'                => array(
						'id'         => array(
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => static fn( $p ) => is_numeric( $p ) && (int) $p > 0,
						),
						'checked_in' => array(
							'type'     => 'boolean',
							'required' => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Checks permissions for PWA access (administrators, editors, staff, coaches).
	 *
	 * @return bool
	 */
	public function check_pwa_permission(): bool {
		return current_user_can( 'manage_dame_ticketing' ) || current_user_can( 'edit_posts' );
	}

	/**
	 * PWA: Retrieves all campaigns with computed stats.
	 *
	 * @param WP_REST_Request|null $request Optional request.
	 * @return WP_REST_Response
	 */
	public function get_campaigns( ?WP_REST_Request $request = null ): WP_REST_Response {
		$filters = array();
		if ( $request instanceof WP_REST_Request ) {
			if ( ! empty( $request['form_type'] ) ) {
				$filters['form_type'] = sanitize_text_field( (string) $request['form_type'] );
			}
			if ( ! empty( $request['orderby'] ) ) {
				$filters['orderby'] = sanitize_key( (string) $request['orderby'] );
			}
			if ( ! empty( $request['order'] ) ) {
				$filters['order'] = sanitize_key( (string) $request['order'] );
			}
		}

		$campaigns = $this->repository->get_all_campaigns( $filters );
		$result    = array();

		foreach ( $campaigns as $camp ) {
			$cid   = (int) $camp['id'];
			$stats = $this->repository->get_campaign_stats( $cid );

			$result[] = array(
				'id'                 => $cid,
				'form_slug'          => (string) $camp['form_slug'],
				'form_type'          => (string) $camp['form_type'],
				'title'              => (string) $camp['title'],
				'campaign_category'  => (string) $camp['campaign_category'],
				'linked_agenda_id'   => isset( $camp['linked_agenda_id'] ) ? (int) $camp['linked_agenda_id'] : null,
				'linked_season_id'   => isset( $camp['linked_season_id'] ) ? (int) $camp['linked_season_id'] : null,
				'start_date'         => $camp['start_date'] ?? null,
				'end_date'           => $camp['end_date'] ?? null,
				'last_sync_at'       => $camp['last_sync_at'] ?? null,
				'total_items'        => $stats['total_items'],
				'checked_in_count'   => $stats['checked_in'],
				'total_amount_cents' => $stats['total_amount_cents'],
				'tiers'              => $stats['tiers'],
			);
		}

		return rest_ensure_response( $result );
	}

	/**
	 * PWA: Retrieves items for a specific campaign.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_campaign_items( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$campaign_id = (int) $request['id'];
		$campaign    = $this->repository->get_campaign( $campaign_id );
		if ( ! $campaign ) {
			return new WP_Error( 'dame_ha_campaign_not_found', __( 'Campagne introuvable.', 'dame' ), array( 'status' => 404 ) );
		}

		$filters = array();
		if ( isset( $request['checked_in'] ) ) {
			$filters['checked_in'] = ( '1' === (string) $request['checked_in'] || 'true' === (string) $request['checked_in'] );
		}
		if ( ! empty( $request['tier'] ) ) {
			$filters['tier'] = sanitize_text_field( (string) $request['tier'] );
		}

		$raw_items = $this->repository->get_campaign_items( $campaign_id, $filters );
		$items     = array();

		foreach ( $raw_items as $it ) {
			$items[] = array(
				'id'                         => (int) $it['id'],
				'campaign_id'                => (int) $it['campaign_id'],
				'ha_item_id'                 => isset( $it['ha_item_id'] ) ? (int) $it['ha_item_id'] : null,
				'source'                     => (string) ( $it['source'] ?? 'helloasso' ),
				'status'                     => (string) ( $it['status'] ?? 'Processed' ),
				'participant_first_name'     => (string) $it['participant_first_name'],
				'participant_last_name'      => (string) $it['participant_last_name'],
				'participant_email'          => (string) $it['participant_email'],
				'participant_birth_date'     => $it['participant_birth_date'] ?? null,
				'payer_first_name'           => (string) ( $it['payer_first_name'] ?? '' ),
				'payer_last_name'            => (string) ( $it['payer_last_name'] ?? '' ),
				'payer_email'                => (string) ( $it['payer_email'] ?? '' ),
				'tier_name'                  => (string) ( $it['tier_name'] ?? '' ),
				'amount_cents'               => (int) ( $it['amount_cents'] ?? 0 ),
				'payment_method'             => (string) ( $it['payment_method'] ?? 'HelloAsso' ),
				'order_date'                 => $it['order_date'] ?? null,
				'license_number'             => (string) ( $it['license_number'] ?? '' ),
				'fide_id'                    => (string) ( $it['fide_id'] ?? '' ),
				'elo'                        => isset( $it['elo'] ) ? (int) $it['elo'] : null,
				'club_name'                  => (string) ( $it['club_name'] ?? '' ),
				'phone'                      => (string) ( $it['phone'] ?? '' ),
				'matched_adherent_id'        => (int) ( $it['matched_adherent_id'] ?? 0 ),
				'matched_pre_inscription_id' => (int) ( $it['matched_pre_inscription_id'] ?? 0 ),
				'matched_contact_id'         => (int) ( $it['matched_contact_id'] ?? 0 ),
				'checked_in'                 => ! empty( $it['checked_in'] ),
				'checked_in_at'              => $it['checked_in_at'] ?? null,
			);
		}

		return rest_ensure_response( $items );
	}

	/**
	 * PWA: Toggles or updates checked_in status for an item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function toggle_checkin( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$item_id = (int) $request['id'];
		$status  = isset( $request['checked_in'] ) ? (bool) $request['checked_in'] : null;

		$success = $this->repository->set_checked_in( $item_id, $status );
		if ( ! $success ) {
			return new WP_Error( 'dame_ha_item_not_found', __( 'Billet introuvable.', 'dame' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'item_id' => $item_id,
			)
		);
	}

	/**
	 * PWA: Adds an onsite participant and marks them checked in.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_onsite_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$campaign_id = (int) $request['id'];
		$campaign    = $this->repository->get_campaign( $campaign_id );
		if ( ! $campaign ) {
			return new WP_Error( 'dame_ha_campaign_not_found', __( 'Campagne introuvable.', 'dame' ), array( 'status' => 404 ) );
		}

		$first_name = sanitize_text_field( (string) $request['participant_first_name'] );
		$last_name  = sanitize_text_field( (string) $request['participant_last_name'] );
		$email      = sanitize_email( (string) ( $request['participant_email'] ?? '' ) );
		$license    = strtoupper( str_replace( ' ', '', sanitize_text_field( (string) ( $request['license_number'] ?? '' ) ) ) );
		$club       = sanitize_text_field( (string) ( $request['club_name'] ?? '' ) );
		$elo        = isset( $request['elo'] ) && is_numeric( $request['elo'] ) ? (int) $request['elo'] : null;
		$tier       = sanitize_text_field( (string) ( $request['tier_name'] ?? '' ) );
		$amount     = (int) ( ( (float) ( $request['amount'] ?? 0 ) ) * 100 );
		$method     = sanitize_text_field( (string) ( $request['payment_method'] ?? 'Espèces' ) );

		$match = Matcher::match_and_enrich(
			array(
				'first_name'  => $first_name,
				'last_name'   => $last_name,
				'email'       => $email,
				'payer_email' => $email,
				'license'     => $license,
			)
		);

		$item_id = $this->repository->save_item(
			array(
				'campaign_id'                => $campaign_id,
				'source'                     => 'onsite',
				'status'                     => 'Processed',
				'participant_first_name'     => $first_name,
				'participant_last_name'      => $last_name,
				'participant_email'          => $email,
				'payer_first_name'           => $first_name,
				'payer_last_name'            => $last_name,
				'payer_email'                => $email,
				'tier_name'                  => $tier,
				'amount_cents'               => $amount,
				'payment_method'             => $method,
				'license_number'             => ! empty( $match['enriched_data']['license_number'] ) ? (string) $match['enriched_data']['license_number'] : $license,
				'fide_id'                    => (string) ( $match['enriched_data']['fide_id'] ?? '' ),
				'elo'                        => isset( $match['enriched_data']['elo'] ) ? (int) $match['enriched_data']['elo'] : $elo,
				'club_name'                  => ! empty( $match['enriched_data']['club_name'] ) ? (string) $match['enriched_data']['club_name'] : $club,
				'matched_adherent_id'        => $match['matched_adherent_id'],
				'matched_pre_inscription_id' => $match['matched_pre_inscription_id'],
				'matched_contact_id'         => $match['matched_contact_id'],
				'checked_in'                 => 1,
				'checked_in_at'              => current_time( 'mysql' ),
			)
		);

		if ( $item_id <= 0 ) {
			return new WP_Error( 'dame_ha_save_failed', __( 'Échec de l\'enregistrement de l\'inscrit.', 'dame' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'item_id' => $item_id,
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
