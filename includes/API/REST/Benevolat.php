<?php
/**
 * REST API Benevolat Endpoints.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\API\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Class Benevolat
 * Handles REST API requests for Benevolats (volunteering).
 */
class Benevolat {

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
	protected string $rest_base = 'benevolats';

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
			'/' . $this->rest_base . '/(?P<id>\d+)/vote',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_vote' ],
					'permission_callback' => [ $this, 'check_vote_permissions' ],
					'args'                => [
						'id'      => [
							'validate_callback' => function( $param ) {
								return is_numeric( $param );
							},
							'required'          => true,
						],
						'choices' => [
							'validate_callback' => function( $param ) {
								return is_array( $param );
							},
							'required'          => true,
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)/my-vote',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_my_vote' ],
					'permission_callback' => [ $this, 'check_vote_permissions' ],
					'args'                => [
						'id' => [
							'validate_callback' => function( $param ) {
								return is_numeric( $param );
							},
							'required'          => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Check if the user is logged in to vote.
	 *
	 * @return bool
	 */
	public function check_vote_permissions(): bool {
		return is_user_logged_in();
	}

	/**
	 * Retrieves the current user's vote for a specific benevolat.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function get_my_vote( WP_REST_Request $request ): WP_REST_Response {
		$benevolat_id = (int) $request['id'];
		$current_user = wp_get_current_user();

		$existing_vote = get_posts( [
			'post_type'      => 'benevolat_reponse',
			'post_parent'    => $benevolat_id,
			'author'         => $current_user->ID,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );

		if ( empty( $existing_vote ) ) {
			return rest_ensure_response( [ 'choices' => [] ] );
		}

		$response_id = (int) $existing_vote[0];

		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_benevolat_votes';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$choices = $wpdb->get_col( $wpdb->prepare(
			"SELECT choice_key FROM {$table_name} WHERE recipient_id = %d",
			$response_id
		) );

		return rest_ensure_response( [ 'choices' => $choices ?: [] ] );
	}

	/**
	 * Handles the vote submission (Create or Update).
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_vote( WP_REST_Request $request ) {
		$benevolat_id = (int) $request['id'];
		$choices      = $request->get_param( 'choices' );

		// 1. Verify benevolat existence and type.
		$benevolat = get_post( $benevolat_id );
		if ( ! $benevolat || 'benevolat' !== $benevolat->post_type ) {
			return new WP_Error( 'invalid_benevolat', 'L\'appel à bénévoles n\'existe pas.', [ 'status' => 404 ] );
		}

		// 1b. Get configuration and today's date.
		$benevolat_data = get_post_meta( $benevolat_id, '_dame_benevolat_data', true );
		if ( ! is_array( $benevolat_data ) ) {
			return new WP_Error( 'invalid_config', 'Configuration invalide.', [ 'status' => 500 ] );
		}
		$today = current_time( 'Y-m-d' );

		// 1c. Check if all dates are passed.
		$all_passed = true;
		foreach ( $benevolat_data as $date_info ) {
			if ( isset( $date_info['date'] ) && $date_info['date'] >= $today ) {
				$all_passed = false;
				break;
			}
		}
		if ( $all_passed ) {
			return new WP_Error( 'benevolat_closed', 'Cet appel est entièrement clôturé.', [ 'status' => 400 ] );
		}

		$current_user = wp_get_current_user();
		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_benevolat_votes';

		// 2. Check for existing vote.
		$existing_vote = get_posts( [
			'post_type'      => 'benevolat_reponse',
			'post_parent'    => $benevolat_id,
			'author'         => $current_user->ID,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );

		$past_choices = [];
		if ( ! empty( $existing_vote ) ) {
			// UPDATE MODE.
			$response_id = (int) $existing_vote[0];

			// Get existing choices to preserve historical data.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$old_choices = $wpdb->get_col( $wpdb->prepare(
				"SELECT choice_key FROM {$table_name} WHERE recipient_id = %d",
				$response_id
			) );

			foreach ( $old_choices as $old_key ) {
				$parts = explode( '_', (string) $old_key );
				$date_index = (int) $parts[0];
				if ( isset( $benevolat_data[ $date_index ]['date'] ) && $benevolat_data[ $date_index ]['date'] < $today ) {
					$past_choices[] = $old_key;
				}
			}

			// Delete existing choices for this response.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->delete( $table_name, [ 'recipient_id' => $response_id ], [ '%d' ] );
		} else {
			// CREATE MODE.
			$response_id = wp_insert_post( [
				'post_type'   => 'benevolat_reponse',
				'post_parent' => $benevolat_id,
				'post_status' => 'publish',
				'post_author' => $current_user->ID,
				'post_title'  => $current_user->display_name,
			] );

			if ( is_wp_error( $response_id ) || 0 === $response_id ) {
				return new WP_Error( 'db_error', 'Erreur lors de l\'enregistrement.', [ 'status' => 500 ] );
			}
		}

		// 3. Filter and merge choices.
		$final_choices = $past_choices;
		foreach ( $choices as $choice_key ) {
			$parts = explode( '_', (string) $choice_key );
			$date_index = (int) $parts[0];

			// Only accept choices for today or future dates.
			if ( isset( $benevolat_data[ $date_index ]['date'] ) && $benevolat_data[ $date_index ]['date'] >= $today ) {
				$final_choices[] = (string) $choice_key;
			}
		}

		// Ensure uniqueness.
		$final_choices = array_unique( $final_choices );

		// 4. Update Post Meta for shortcode synchronization.
		$meta_responses = [];
		foreach ( $final_choices as $choice_key ) {
			$parts = explode( '_', (string) $choice_key );
			if ( count( $parts ) === 2 ) {
				$date_idx = (int) $parts[0];
				$time_idx = (int) $parts[1];
				if ( ! isset( $meta_responses[ $date_idx ] ) ) {
					$meta_responses[ $date_idx ] = [];
				}
				$meta_responses[ $date_idx ][ $time_idx ] = '1';
			}
		}
		update_post_meta( $response_id, '_dame_benevolat_responses', $meta_responses );

		// 5. Insertion SQL des nouveaux choix.
		foreach ( $final_choices as $choice_key ) {
			$wpdb->insert(
				$table_name,
				[
					'poll_id'      => $benevolat_id,
					'recipient_id' => $response_id,
					'choice_key'   => sanitize_text_field( (string) $choice_key ),
				],
				[ '%d', '%d', '%s' ]
			);
		}

		return rest_ensure_response( [
			'success' => true,
			'message' => 'Enregistré.',
		] );
	}
}
