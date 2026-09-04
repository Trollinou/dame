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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_my_identities' ),
					'permission_callback' => array( $this, 'get_permissions_check' ),
				),
			)
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
				array( 'status' => 401 )
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
			return new WP_Error( 'no_email', __( 'Utilisateur sans email', 'dame' ), array( 'status' => 400 ) );
		}

		// 1. REQUÊTES DE BASE (Uniquement les membres actifs)
		$args_base = array(
			'post_type'      => 'adherent',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		// Requête A (Directe : l'utilisateur est le joueur)
		$query_a = new WP_Query(
			array_merge(
				$args_base,
				array(
					'meta_query' => array(
						array(
							'key'     => '_dame_email',
							'value'   => $email,
							'compare' => '=',
						),
					),
				)
			)
		);

		// Requête B (Responsable Légal : l'utilisateur est le parent)
		$query_b = new WP_Query(
			array_merge(
				$args_base,
				array(
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key'     => '_dame_legal_rep_1_email',
							'value'   => $email,
							'compare' => '=',
						),
						array(
							'key'     => '_dame_legal_rep_2_email',
							'value'   => $email,
							'compare' => '=',
						),
					),
				)
			)
		);

		$adherents_a = $query_a->posts;
		$adherents_b = $query_b->posts;

		$accessible_adh_ids = array_values(
			array_filter(
				array_map(
					fn( $p ) => (int) $p->ID,
					array_merge( $adherents_a, $adherents_b )
				)
			)
		);

		$pending_pre_or = array(
			array(
				'key'     => '_dame_email',
				'value'   => $email,
				'compare' => '=',
			),
			array(
				'key'     => '_dame_legal_rep_1_email',
				'value'   => $email,
				'compare' => '=',
			),
			array(
				'key'     => '_dame_legal_rep_2_email',
				'value'   => $email,
				'compare' => '=',
			),
			array(
				'key'     => '_dame_submitted_by_email',
				'value'   => $email,
				'compare' => '=',
			),
		);

		if ( ! empty( $accessible_adh_ids ) ) {
			$pending_pre_or[] = array(
				'key'     => '_dame_adherent_id',
				'value'   => $accessible_adh_ids,
				'compare' => 'IN',
			);
		}

		// Query pending pre-inscriptions for this user's email or accessible adherents in a single request
		$pending_pre_query = new WP_Query(
			array(
				'post_type'      => 'dame_pre_inscription',
				'post_status'    => array( 'pending', 'publish', 'draft' ),
				'posts_per_page' => -1,
				'meta_query'     => array_merge(
					array( 'relation' => 'OR' ),
					$pending_pre_or
				),
			)
		);
		$pending_preinscriptions = $pending_pre_query->posts;
		$matched_pre_ids         = array();

		$identities = array();
		$seen_ids   = array(); // Pour éviter les doublons techniques

		// 2. CONSTRUCTION DES IDENTITÉS

		// AJOUT DES PROFILS JOUEURS (Adhérents directs)
		foreach ( $adherents_a as $adh ) {
			$identities[] = $this->prepare_full_identity( $adh, 'member', array(), $pending_preinscriptions, $matched_pre_ids, $email );
			$seen_ids[]   = 'member_' . $adh->ID;
		}

		// AJOUT DES PROFILS PARENTS (Responsables Légaux)
		if ( ! empty( $adherents_b ) || ! empty( $pending_preinscriptions ) ) {
			$reps = $this->extract_representative_identities( $adherents_b, $email, $pending_preinscriptions, $matched_pre_ids );
			foreach ( $reps as $rep ) {
				$identities[] = $rep;
			}
		}

		// 3. CAS PARTICULIER : Identité Admin
		$allowed_roles = array( 'staff', 'entraineur', 'editor', 'administrator' );
		if ( array_intersect( $allowed_roles, (array) $current_user->roles ) ) {
			array_unshift(
				$identities,
				array(
					'id'                  => 'wp_virtual',
					'name'                => $current_user->display_name,
					'firstname'           => ! empty( $current_user->first_name ) ? $current_user->first_name : $current_user->display_name,
					'type'                => 'admin',
					'member_id'           => 0,
					'elo_standard'        => 'NC',
					'elo_rapide'          => 'NC',
					'elo_blitz'           => 'NC',
					'associated_members'  => array(),
					'has_pre_inscription' => false,
					'already_registered'  => true,
				)
			);
		}

		return rest_ensure_response( $identities );
	}

	/**
	 * Matches an adherent post to a pending pre-inscription from a cached list or targeted query.
	 *
	 * @param int        $adherent_id             The adherent post ID.
	 * @param \WP_Post[] $pending_preinscriptions List of pending pre-inscription posts.
	 * @param string     $user_email              Optional user email.
	 * @return \WP_Post|null
	 */
	private function match_pending_pre_inscription( int $adherent_id, array $pending_preinscriptions, string $user_email = '' ): ?\WP_Post {
		// 1. Direct match by _dame_adherent_id
		foreach ( $pending_preinscriptions as $pre ) {
			$linked_adh_id = (int) get_post_meta( $pre->ID, '_dame_adherent_id', true );
			if ( $linked_adh_id === $adherent_id ) {
				return $pre;
			}
		}

		// 2. Fallback match by first_name and birth_date
		$adh_fname = (string) get_post_meta( $adherent_id, '_dame_first_name', true );
		$adh_bdate = (string) get_post_meta( $adherent_id, '_dame_birth_date', true );
		$adh_email = (string) get_post_meta( $adherent_id, '_dame_email', true );

		if ( ! empty( $adh_fname ) && ! empty( $adh_bdate ) ) {
			foreach ( $pending_preinscriptions as $pre ) {
				$pre_fname = (string) get_post_meta( $pre->ID, '_dame_first_name', true );
				$pre_bdate = (string) get_post_meta( $pre->ID, '_dame_birth_date', true );

				if ( strcasecmp( trim( $adh_fname ), trim( $pre_fname ) ) === 0 && $adh_bdate === $pre_bdate ) {
					update_post_meta( $pre->ID, '_dame_adherent_id', $adherent_id );
					return $pre;
				}
			}

			// 3. Fallback targeted DB query in case pre-inscription wasn't yet loaded in memory
			$email_conditions = array(
				array(
					'key'     => '_dame_adherent_id',
					'value'   => $adherent_id,
					'compare' => '=',
				),
			);
			if ( ! empty( $user_email ) ) {
				$email_conditions[] = array(
					'key'     => '_dame_submitted_by_email',
					'value'   => $user_email,
					'compare' => '=',
				);
				$email_conditions[] = array(
					'key'     => '_dame_legal_rep_1_email',
					'value'   => $user_email,
					'compare' => '=',
				);
				$email_conditions[] = array(
					'key'     => '_dame_legal_rep_2_email',
					'value'   => $user_email,
					'compare' => '=',
				);
			}
			if ( ! empty( $adh_email ) ) {
				$email_conditions[] = array(
					'key'     => '_dame_email',
					'value'   => $adh_email,
					'compare' => '=',
				);
			}

			$db_query = new WP_Query(
				array(
					'post_type'      => 'dame_pre_inscription',
					'post_status'    => array( 'pending', 'publish', 'draft' ),
					'posts_per_page' => 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'     => '_dame_first_name',
							'value'   => $adh_fname,
							'compare' => '=',
						),
						array(
							'key'     => '_dame_birth_date',
							'value'   => $adh_bdate,
							'compare' => '=',
						),
						array_merge(
							array( 'relation' => 'OR' ),
							$email_conditions
						),
					),
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			);

			if ( ! empty( $db_query->posts ) ) {
				$found_pre = $db_query->posts[0];
				update_post_meta( $found_pre->ID, '_dame_adherent_id', $adherent_id );
				return $found_pre;
			}
		}

		return null;
	}

	/**
	 * Extracts representative identities from a list of adherents.
	 *
	 * @param \WP_Post[] $adherents               List of adherent posts.
	 * @param string     $email                   Email to check.
	 * @param \WP_Post[] $pending_preinscriptions Cached pending pre-inscriptions.
	 * @param int[]      $matched_pre_ids         Array of matched pre-inscription IDs passed by reference.
	 * @return array<int, array<string, mixed>>
	 */
	private function extract_representative_identities( array $adherents, string $email, array $pending_preinscriptions = array(), array &$matched_pre_ids = array() ): array {
		$reps       = array();
		$seen_names = array();

		foreach ( $adherents as $adh ) {
			$rep_names_to_check = array();

			// On vérifie RL1
			if ( get_post_meta( $adh->ID, '_dame_legal_rep_1_email', true ) === $email ) {
				$rep_names_to_check[] = trim( get_post_meta( $adh->ID, '_dame_legal_rep_1_first_name', true ) . ' ' . get_post_meta( $adh->ID, '_dame_legal_rep_1_last_name', true ) );
			}
			// On vérifie RL2
			if ( get_post_meta( $adh->ID, '_dame_legal_rep_2_email', true ) === $email ) {
				$rep_names_to_check[] = trim( get_post_meta( $adh->ID, '_dame_legal_rep_2_first_name', true ) . ' ' . get_post_meta( $adh->ID, '_dame_legal_rep_2_last_name', true ) );
			}

			foreach ( array_unique( $rep_names_to_check ) as $rep_name ) {
				if ( empty( $rep_name ) ) {
					continue;
				}

				if ( ! isset( $seen_names[ $rep_name ] ) ) {
					$reps[ $rep_name ]       = array(
						'id'                 => 'rep_' . md5( $rep_name ),
						'name'               => $rep_name,
						'firstname'          => explode( ' ', $rep_name )[0],
						'type'               => 'representative',
						'elo_standard'       => 'NC',
						'elo_rapide'         => 'NC',
						'elo_blitz'          => 'NC',
						'associated_members' => array(), // On remplira après
					);
					$seen_names[ $rep_name ] = $rep_name;
				}

				// On ajoute cet enfant à la liste des membres associés de ce parent
				$elo_std     = get_post_meta( $adh->ID, '_dame_elo_standard', true );
				$elo_rap     = get_post_meta( $adh->ID, '_dame_elo_rapide', true );
				$elo_blz     = get_post_meta( $adh->ID, '_dame_elo_blitz', true );
				$matched_pre = $this->match_pending_pre_inscription( $adh->ID, $pending_preinscriptions, $email );
				if ( $matched_pre ) {
					$matched_pre_ids[] = $matched_pre->ID;
				}

				$reps[ $rep_name ]['associated_members'][] = array(
					'firstname'           => $this->get_firstname( $adh->ID ),
					'member_id'           => $adh->ID,
					'elo_standard'        => ! empty( $elo_std ) ? $elo_std : 'NC',
					'elo_rapide'          => ! empty( $elo_rap ) ? $elo_rap : 'NC',
					'elo_blitz'           => ! empty( $elo_blz ) ? $elo_blz : 'NC',
					'already_registered'  => has_term( (int) get_option( 'dame_current_season_tag_id' ), 'dame_saison_adhesion', $adh->ID ),
					'has_pre_inscription' => ( null !== $matched_pre ),
					'pre_inscription_id'  => $matched_pre ? $matched_pre->ID : null,
				);
			}
		}

		// Check for any pending pre-inscriptions that didn't match existing adherents
		// (e.g. newly pre-inscribed children for this representative)
		$unmatched_pres = array();
		foreach ( $pending_preinscriptions as $pre ) {
			if ( ! in_array( $pre->ID, $matched_pre_ids, true ) ) {
				// Verify this pre-inscription has this email as a legal rep or submitter
				$rep1_email = (string) get_post_meta( $pre->ID, '_dame_legal_rep_1_email', true );
				$rep2_email = (string) get_post_meta( $pre->ID, '_dame_legal_rep_2_email', true );
				$sub_email  = (string) get_post_meta( $pre->ID, '_dame_submitted_by_email', true );
				if ( $email === $rep1_email || $email === $rep2_email || $email === $sub_email ) {
					$unmatched_pres[] = $pre;
				}
			}
		}

		if ( ! empty( $unmatched_pres ) ) {
			// If no representative profile exists yet, create one using the legal rep name from the pre-inscription
			if ( empty( $reps ) ) {
				$first_pre = $unmatched_pres[0];
				$rep_name  = trim( (string) get_post_meta( $first_pre->ID, '_dame_legal_rep_1_first_name', true ) . ' ' . (string) get_post_meta( $first_pre->ID, '_dame_legal_rep_1_last_name', true ) );
				if ( empty( $rep_name ) ) {
					$current_user = wp_get_current_user();
					$rep_name     = $current_user->display_name;
				}
				$reps[ $rep_name ] = array(
					'id'                 => 'rep_' . md5( $rep_name ),
					'name'               => $rep_name,
					'firstname'          => explode( ' ', $rep_name )[0],
					'type'               => 'representative',
					'elo_standard'       => 'NC',
					'elo_rapide'         => 'NC',
					'elo_blitz'          => 'NC',
					'associated_members' => array(),
				);
			}

			// Add each unmatched pre-inscription to the representative's associated members
			$first_rep_key = array_key_first( $reps );
			foreach ( $unmatched_pres as $unmatched_pre ) {
				$matched_pre_ids[] = $unmatched_pre->ID;
				$child_fname       = (string) get_post_meta( $unmatched_pre->ID, '_dame_first_name', true );
				$reps[ $first_rep_key ]['associated_members'][] = array(
					'firstname'           => $child_fname,
					'name'                => get_the_title( $unmatched_pre->ID ),
					'member_id'           => 0,
					'elo_standard'        => 'NC',
					'elo_rapide'          => 'NC',
					'elo_blitz'           => 'NC',
					'already_registered'  => false,
					'has_pre_inscription' => true,
					'pre_inscription_id'  => $unmatched_pre->ID,
				);
			}
		}

		return array_values( $reps );
	}

	/**
	 * Prepares a full identity object.
	 *
	 * @param \WP_Post   $post                  The adherent post.
	 * @param string     $type                  Identity type.
	 * @param \WP_Post[] $associated_adherents  Associated adherent posts.
	 * @param \WP_Post[] $pending_preinscriptions Cached pending pre-inscriptions.
	 * @param int[]      $matched_pre_ids       Array of matched pre-inscription IDs passed by reference.
	 * @param string     $user_email            Optional user email.
	 * @return array<string, mixed>
	 */
	private function prepare_full_identity( \WP_Post $post, string $type, array $associated_adherents, array $pending_preinscriptions = array(), array &$matched_pre_ids = array(), string $user_email = '' ): array {
		$post_id     = $post->ID;
		$elo_std     = get_post_meta( $post_id, '_dame_elo_standard', true );
		$elo_rap     = get_post_meta( $post_id, '_dame_elo_rapide', true );
		$elo_blz     = get_post_meta( $post_id, '_dame_elo_blitz', true );
		$matched_pre = $this->match_pending_pre_inscription( $post_id, $pending_preinscriptions, $user_email );
		if ( $matched_pre ) {
			$matched_pre_ids[] = $matched_pre->ID;
		}

		return array(
			'id'                  => 'member_' . $post_id,
			'name'                => get_the_title( $post_id ),
			'firstname'           => $this->get_firstname( $post_id ),
			'type'                => $type,
			'member_id'           => $post_id,
			'elo_standard'        => ! empty( $elo_std ) ? $elo_std : 'NC',
			'elo_rapide'          => ! empty( $elo_rap ) ? $elo_rap : 'NC',
			'elo_blitz'           => ! empty( $elo_blz ) ? $elo_blz : 'NC',
			'associated_members'  => $this->prepare_associated_members( $associated_adherents, $pending_preinscriptions, $matched_pre_ids, $user_email ),
			'already_registered'  => has_term( (int) get_option( 'dame_current_season_tag_id' ), 'dame_saison_adhesion', $post_id ),
			'has_pre_inscription' => ( null !== $matched_pre ),
			'pre_inscription_id'  => $matched_pre ? $matched_pre->ID : null,
		);
	}

	/**
	 * Prepares the list of associated members for the JSON response.
	 *
	 * @param \WP_Post[] $adherents               List of adherent posts.
	 * @param \WP_Post[] $pending_preinscriptions Cached pending pre-inscriptions.
	 * @param int[]      $matched_pre_ids         Array of matched pre-inscription IDs passed by reference.
	 * @param string     $user_email              Optional user email.
	 * @return array<int, array<string, mixed>>
	 */
	private function prepare_associated_members( array $adherents, array $pending_preinscriptions = array(), array &$matched_pre_ids = array(), string $user_email = '' ): array {
		$list = array();
		foreach ( $adherents as $adh ) {
			$elo_std     = get_post_meta( $adh->ID, '_dame_elo_standard', true );
			$elo_rap     = get_post_meta( $adh->ID, '_dame_elo_rapide', true );
			$elo_blz     = get_post_meta( $adh->ID, '_dame_elo_blitz', true );
			$matched_pre = $this->match_pending_pre_inscription( $adh->ID, $pending_preinscriptions, $user_email );
			if ( $matched_pre ) {
				$matched_pre_ids[] = $matched_pre->ID;
			}
			$list[] = array(
				'firstname'           => $this->get_firstname( $adh->ID ),
				'member_id'           => $adh->ID,
				'elo_standard'        => ! empty( $elo_std ) ? $elo_std : 'NC',
				'elo_rapide'          => ! empty( $elo_rap ) ? $elo_rap : 'NC',
				'elo_blitz'           => ! empty( $elo_blz ) ? $elo_blz : 'NC',
				'already_registered'  => has_term( (int) get_option( 'dame_current_season_tag_id' ), 'dame_saison_adhesion', $adh->ID ),
				'has_pre_inscription' => ( null !== $matched_pre ),
				'pre_inscription_id'  => $matched_pre ? $matched_pre->ID : null,
			);
		}
		return $list;
	}

	/**
	 * Gets the first name of an adherent.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_firstname( int $post_id ): string {
		$firstname = get_post_meta( $post_id, '_dame_first_name', true );
		if ( empty( $firstname ) ) {
			$parts     = explode( ' ', get_the_title( $post_id ) );
			$firstname = ( count( $parts ) > 1 ) ? implode( ' ', array_slice( $parts, 1 ) ) : $parts[0];
		}
		return (string) $firstname;
	}
}
