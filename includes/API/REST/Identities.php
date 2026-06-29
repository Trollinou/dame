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
	 * Retrieves identities linked to the current user's email based on business rules.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_my_identities( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$current_user = wp_get_current_user();
		$email        = $current_user->user_email;

		if ( empty( $email ) ) {
			return new WP_Error( 'no_email', __( 'Utilisateur sans email', 'dame' ), [ 'status' => 400 ] );
		}

		// 1. REQUÊTES DE BASE (Uniquement les membres actifs)
		$args_base = [
			'post_type'      => 'adherent',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		];

		// Requête A (Directe : l'utilisateur est le joueur)
		$query_a = new WP_Query( array_merge( $args_base, [
			'meta_query' => [ [ 'key' => '_dame_email', 'value' => $email, 'compare' => '=' ] ],
		] ) );

		// Requête B (Responsable Légal : l'utilisateur est le parent)
		$query_b = new WP_Query( array_merge( $args_base, [
			'meta_query' => [
				'relation' => 'OR',
				[ 'key' => '_dame_legal_rep_1_email', 'value' => $email, 'compare' => '=' ],
				[ 'key' => '_dame_legal_rep_2_email', 'value' => $email, 'compare' => '=' ],
			],
		] ) );

		$adherents_a = $query_a->posts;
		$adherents_b = $query_b->posts;

		$identities = [];
		$seen_ids   = []; // Pour éviter les doublons techniques

		// 2. CONSTRUCTION DES IDENTITÉS
		
		// AJOUT DES PROFILS JOUEURS (Adhérents directs)
		foreach ( $adherents_a as $adh ) {
			$identities[] = $this->prepare_full_identity( $adh, 'member', [] );
			$seen_ids[]   = 'member_' . $adh->ID;
		}

		// AJOUT DES PROFILS PARENTS (Responsables Légaux)
		if ( ! empty( $adherents_b ) ) {
			$reps = $this->extract_representative_identities( $adherents_b, $email );
			foreach ( $reps as $rep ) {
				$identities[] = $rep;
			}
		}

		// 3. CAS PARTICULIER : Identité Admin
		$allowed_roles = [ 'staff', 'entraineur', 'editor', 'administrator' ];
		if ( array_intersect( $allowed_roles, (array) $current_user->roles ) ) {
			array_unshift( $identities, [
				'id'                => 'wp_virtual',
				'name'              => $current_user->display_name,
				'firstname'         => $current_user->first_name ?: $current_user->display_name,
				'type'              => 'admin',
				'member_id'         => 0,
				'elo_standard'      => 'NC',
				'elo_rapide'        => 'NC',
				'elo_blitz'         => 'NC',
				'associated_members' => [],
			] );
		}

		return rest_ensure_response( $identities );
	}

	/**
	 * Extracts representative identities from a list of adherents.
	 *
	 * @param \WP_Post[] $adherents List of adherent posts.
	 * @param string     $email     Email to check.
	 * @return array<int, array<string, mixed>>
	 */
	private function extract_representative_identities( array $adherents, string $email ): array {
		$reps = [];
		$seen_names = [];

		foreach ( $adherents as $adh ) {
			$rep_names_to_check = [];
			
			// On vérifie RL1
			if ( get_post_meta( $adh->ID, '_dame_legal_rep_1_email', true ) === $email ) {
				$rep_names_to_check[] = trim( get_post_meta( $adh->ID, '_dame_legal_rep_1_first_name', true ) . ' ' . get_post_meta( $adh->ID, '_dame_legal_rep_1_last_name', true ) );
			}
			// On vérifie RL2
			if ( get_post_meta( $adh->ID, '_dame_legal_rep_2_email', true ) === $email ) {
				$rep_names_to_check[] = trim( get_post_meta( $adh->ID, '_dame_legal_rep_2_first_name', true ) . ' ' . get_post_meta( $adh->ID, '_dame_legal_rep_2_last_name', true ) );
			}

			foreach ( array_unique( $rep_names_to_check ) as $rep_name ) {
				if ( empty( $rep_name ) ) continue;

				if ( ! isset( $seen_names[ $rep_name ] ) ) {
					$reps[ $rep_name ] = [
						'id'                => 'rep_' . md5( $rep_name ),
						'name'              => $rep_name,
						'firstname'         => explode( ' ', $rep_name )[0],
						'type'              => 'representative',
						'elo_standard'      => 'NC',
						'elo_rapide'        => 'NC',
						'elo_blitz'         => 'NC',
						'associated_members' => [], // On remplira après
					];
					$seen_names[ $rep_name ] = $rep_name;
				}
				
				// On ajoute cet enfant à la liste des membres associés de ce parent
				$reps[ $rep_name ]['associated_members'][] = [
					'firstname'          => $this->get_firstname( $adh->ID ),
					'member_id'          => $adh->ID,
					'elo_standard'       => get_post_meta( $adh->ID, '_dame_elo_standard', true ) ?: 'NC',
					'elo_rapide'         => get_post_meta( $adh->ID, '_dame_elo_rapide', true ) ?: 'NC',
					'elo_blitz'          => get_post_meta( $adh->ID, '_dame_elo_blitz', true ) ?: 'NC',
					'already_registered' => has_term( (int) get_option( 'dame_current_season_tag_id' ), 'dame_saison_adhesion', $adh->ID ),
				];
			}
		}

		return array_values( $reps );
	}

	/**
	 * Prepares a full identity object.
	 *
	 * @param \WP_Post   $post                 The adherent post.
	 * @param string     $type                 Identity type.
	 * @param \WP_Post[] $associated_adherents Associated adherent posts.
	 * @return array<string, mixed>
	 */
	private function prepare_full_identity( \WP_Post $post, string $type, array $associated_adherents ): array {
		$post_id = $post->ID;
		return [
			'id'                 => 'member_' . $post_id,
			'name'               => get_the_title( $post_id ),
			'firstname'          => $this->get_firstname( $post_id ),
			'type'               => $type,
			'member_id'          => $post_id,
			'elo_standard'       => get_post_meta( $post_id, '_dame_elo_standard', true ) ?: 'NC',
			'elo_rapide'         => get_post_meta( $post_id, '_dame_elo_rapide', true ) ?: 'NC',
			'elo_blitz'          => get_post_meta( $post_id, '_dame_elo_blitz', true ) ?: 'NC',
			'associated_members' => $this->prepare_associated_members( $associated_adherents ),
			'already_registered' => has_term( (int) get_option( 'dame_current_season_tag_id' ), 'dame_saison_adhesion', $post_id ),
		];
	}

	/**
	 * Prepares the list of associated members for the JSON response.
	 *
	 * @param \WP_Post[] $adherents List of adherent posts.
	 * @return array<int, array<string, mixed>>
	 */
	private function prepare_associated_members( array $adherents ): array {
		$list = [];
		foreach ( $adherents as $adh ) {
			$list[] = [
				'firstname'          => $this->get_firstname( $adh->ID ),
				'member_id'          => $adh->ID,
				'elo_standard'       => get_post_meta( $adh->ID, '_dame_elo_standard', true ) ?: 'NC',
				'elo_rapide'         => get_post_meta( $adh->ID, '_dame_elo_rapide', true ) ?: 'NC',
				'elo_blitz'          => get_post_meta( $adh->ID, '_dame_elo_blitz', true ) ?: 'NC',
				'already_registered' => has_term( (int) get_option( 'dame_current_season_tag_id' ), 'dame_saison_adhesion', $adh->ID ),
			];
		}
		return $list;
	}

	/**
	 * Gets the first name of an adherent.
	 */
	private function get_firstname( int $post_id ): string {
		$firstname = get_post_meta( $post_id, '_dame_first_name', true );
		if ( empty( $firstname ) ) {
			$parts = explode( ' ', get_the_title( $post_id ) );
			$firstname = ( count( $parts ) > 1 ) ? implode( ' ', array_slice( $parts, 1 ) ) : $parts[0];
		}
		return (string) $firstname;
	}
}
