<?php
/**
 * REST API My Identities Endpoint.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\API\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;
use WP_Query;

/**
 * Class Identities
 * Handles retrieving identities linked to the current user.
 */
class Identities {

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
	protected string $rest_base = 'my-identities';

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
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_my_identities' ],
					'permission_callback' => [ $this, 'get_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Permission callback for the endpoint.
	 *
	 * @return bool|WP_Error
	 */
	public function get_permissions_check(): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Vous devez être connecté.', 'dame' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}

	/**
	 * Retrieves identities linked to the current user's email.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_my_identities( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$current_user = wp_get_current_user();
		$email        = $current_user->user_email;

		if ( empty( $email ) ) {
			return new WP_Error(
				'no_email',
				__( 'Utilisateur sans email', 'dame' ),
				[ 'status' => 400 ]
			);
		}

		$identities = [];

		// RECHERCHE 1 & 2 : L'utilisateur est lui-même un Adhérent OU un Représentant Légal.
		$query = new WP_Query(
			[
				'post_type'      => 'adherent',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'meta_query'     => [
					'relation' => 'OR',
					[
						'key'     => '_dame_email',
						'value'   => $email,
						'compare' => '=',
					],
					[
						'key'     => '_dame_legal_rep_1_email',
						'value'   => $email,
						'compare' => '=',
					],
					[
						'key'     => '_dame_legal_rep_2_email',
						'value'   => $email,
						'compare' => '=',
					],
				],
			]
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				// Check for Member match.
				$member_email = get_post_meta( $post_id, '_dame_email', true );
				if ( $email === $member_email ) {
					$identities[] = [
						'id'        => 'member_' . $post_id,
						'name'      => get_the_title( $post_id ),
						'type'      => 'member',
						'member_id' => $post_id,
					];
				}

				// Check for Legal Rep 1 match.
				$rep1_email = get_post_meta( $post_id, '_dame_legal_rep_1_email', true );
				if ( $email === $rep1_email ) {
					$first_name = get_post_meta( $post_id, '_dame_legal_rep_1_first_name', true );
					$last_name  = get_post_meta( $post_id, '_dame_legal_rep_1_last_name', true );
					$identities[] = [
						'id'        => 'rep_' . $post_id . '_1',
						'name'      => trim( $first_name . ' ' . $last_name ),
						'type'      => 'representative',
						'member_id' => $post_id,
					];
				}

				// Check for Legal Rep 2 match.
				$rep2_email = get_post_meta( $post_id, '_dame_legal_rep_2_email', true );
				if ( $email === $rep2_email ) {
					$first_name = get_post_meta( $post_id, '_dame_legal_rep_2_first_name', true );
					$last_name  = get_post_meta( $post_id, '_dame_legal_rep_2_last_name', true );
					$identities[] = [
						'id'        => 'rep_' . $post_id . '_2',
						'name'      => trim( $first_name . ' ' . $last_name ),
						'type'      => 'representative',
						'member_id' => $post_id,
					];
				}
			}
			wp_reset_postdata();
		}

		return rest_ensure_response( $identities );
	}
}
