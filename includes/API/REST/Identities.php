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

		$raw_results = [];
		$member_ids  = [];

		// 1. RECHERCHE : Trouver tous les Adhérents liés à cet email (directement ou via RL).
		$query = new WP_Query(
			[
				'post_type'      => 'adherent',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'meta_query'     => [
					'relation' => 'OR',
					[ 'key' => '_dame_email', 'value' => $email, 'compare' => '=' ],
					[ 'key' => '_dame_legal_rep_1_email', 'value' => $email, 'compare' => '=' ],
					[ 'key' => '_dame_legal_rep_2_email', 'value' => $email, 'compare' => '=' ],
				],
			]
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();
				$member_ids[] = $post_id;

				// Match Adhérent direct
				if ( $email === get_post_meta( $post_id, '_dame_email', true ) ) {
					$raw_results[] = [
						'id'        => 'member_' . $post_id,
						'name'      => get_the_title( $post_id ),
						'type'      => 'member',
						'member_id' => $post_id,
					];
				}

				// Match Représentant Légal 1
				if ( $email === get_post_meta( $post_id, '_dame_legal_rep_1_email', true ) ) {
					$first_name = get_post_meta( $post_id, '_dame_legal_rep_1_first_name', true );
					$last_name  = get_post_meta( $post_id, '_dame_legal_rep_1_last_name', true );
					$raw_results[] = [
						'id'        => 'rep_' . $post_id . '_1',
						'name'      => trim( (string) $first_name . ' ' . (string) $last_name ),
						'type'      => 'representative',
						'member_id' => $post_id,
					];
				}

				// Match Représentant Légal 2
				if ( $email === get_post_meta( $post_id, '_dame_legal_rep_2_email', true ) ) {
					$first_name = get_post_meta( $post_id, '_dame_legal_rep_2_first_name', true );
					$last_name  = get_post_meta( $post_id, '_dame_legal_rep_2_last_name', true );
					$raw_results[] = [
						'id'        => 'rep_' . $post_id . '_2',
						'name'      => trim( (string) $first_name . ' ' . (string) $last_name ),
						'type'      => 'representative',
						'member_id' => $post_id,
					];
				}
			}
			wp_reset_postdata();
		}

		// 2. LOGIQUE DE FILTRAGE
		$unique_member_ids = array_unique( $member_ids );
		$final_identities  = $raw_results;

		// CAS SPÉCIAL : Un seul adhérent trouvé
		if ( count( $unique_member_ids ) === 1 ) {
			$post_id    = $unique_member_ids[0];
			$birth_date = get_post_meta( $post_id, '_dame_birth_date', true );
			$is_major   = false;

			if ( ! empty( $birth_date ) ) {
				$birth      = new \DateTime( (string) $birth_date );
				$today      = new \DateTime();
				$age        = $today->diff( $birth )->y;
				$is_major   = $age >= 18;
			}

			// Si majeur, on ne garde que l'identité de type 'member'
			if ( $is_major ) {
				// On cherche s'il y a une entrée 'member' dans nos résultats
				$member_entry = null;
				foreach ( $raw_results as $entry ) {
					if ( $entry['type'] === 'member' ) {
						$member_entry = $entry;
						break;
					}
				}

				// Si on a trouvé une entrée 'member', on ne renvoie que celle-là
				if ( $member_entry ) {
					$final_identities = [ $member_entry ];
				} else {
					// Si l'adulte n'a pas d'email adhérent mais est trouvé via RL, on transforme l'entrée RL en Adhérent
					// pour éviter d'afficher "Jean (RL)" alors qu'il est seul et adulte.
					if ( ! empty( $raw_results ) ) {
						$first_rep = $raw_results[0];
						$final_identities = [ [
							'id'        => 'member_' . $post_id,
							'name'      => get_the_title( $post_id ),
							'type'      => 'member',
							'member_id' => $post_id,
						] ];
					}
				}
			}
		}

		// 3. AJOUT DE L'IDENTITÉ ADMIN (SI AUTORISÉ)
		$allowed_roles = [ 'staff', 'entraineur', 'editor', 'administrator' ];
		if ( array_intersect( $allowed_roles, (array) $current_user->roles ) ) {
			array_unshift( $final_identities, [
				'id'        => 'wp_virtual',
				'name'      => $current_user->display_name,
				'type'      => 'admin',
				'member_id' => 0,
			] );
		}

		return rest_ensure_response( $final_identities );
	}
}
