<?php
/**
 * Membership Role Sync Service.
 *
 * @package DAME\Services
 */

declare(strict_types=1);

namespace DAME\Services;

use WP_Query;
use WP_User;

/**
 * Class Membership_Role_Sync
 *
 * Synchronizes WordPress user roles ('membre' / 'subscriber') with active adherent memberships
 * for the valid membership seasons defined by Season_Service.
 */
class Membership_Role_Sync {

	/**
	 * Roles that must never be downgraded automatically.
	 *
	 * @var array<int, string>
	 */
	public const PROTECTED_ROLES = array( 'administrator', 'editor', 'staff', 'entraineur' );

	/**
	 * Post meta keys storing member or legal representative emails on adherent posts.
	 *
	 * @var array<int, string>
	 */
	private const EMAIL_META_KEYS = array(
		'_dame_email',
		'_dame_legal_rep_1_email',
		'_dame_legal_rep_2_email',
	);

	/**
	 * Initialize real-time synchronization hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'set_object_terms', array( $this, 'on_set_object_terms' ), 10, 6 );
		add_action( 'added_post_meta', array( $this, 'on_post_meta_changed' ), 10, 4 );
		add_action( 'updated_post_meta', array( $this, 'on_post_meta_changed' ), 10, 4 );
	}

	/**
	 * Handles taxonomy updates on adherent posts.
	 *
	 * @param int          $object_id  Post ID.
	 * @param array<mixed> $terms      Terms.
	 * @param array<mixed> $tt_ids     Term taxonomy IDs.
	 * @param string       $taxonomy   Taxonomy slug.
	 * @param bool         $append     Whether terms were appended.
	 * @param array<mixed> $old_tt_ids Previous term taxonomy IDs.
	 * @return void
	 */
	public function on_set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ): void {
		if ( 'dame_saison_adhesion' !== $taxonomy ) {
			return;
		}

		$post_id = (int) $object_id;
		if ( $post_id <= 0 || 'adherent' !== get_post_type( $post_id ) ) {
			return;
		}

		$this->sync_adherent_emails( $post_id );
	}

	/**
	 * Handles email meta updates on adherent posts.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return void
	 */
	public function on_post_meta_changed( $meta_id, $object_id, $meta_key, $meta_value ): void {
		if ( ! in_array( (string) $meta_key, self::EMAIL_META_KEYS, true ) ) {
			return;
		}

		$post_id = (int) $object_id;
		if ( $post_id <= 0 || 'adherent' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( is_string( $meta_value ) && is_email( $meta_value ) ) {
			$this->sync_user_by_email( $meta_value );
		}
	}

	/**
	 * Resolves the target role for a user based on current roles, verification status, and active membership.
	 *
	 * @param array<int, string> $current_roles         Current user roles.
	 * @param bool               $is_verified           Whether the user email is verified.
	 * @param bool               $has_active_membership Whether an active membership exists for the user email.
	 * @return string|null Target role ('membre' or 'subscriber'), or null if no change is needed.
	 */
	public static function resolve_target_role( array $current_roles, bool $is_verified, bool $has_active_membership ): ?string {
		if ( ! empty( array_intersect( self::PROTECTED_ROLES, $current_roles ) ) ) {
			return null;
		}

		$is_membre     = in_array( 'membre', $current_roles, true );
		$is_subscriber = in_array( 'subscriber', $current_roles, true );

		if ( $has_active_membership ) {
			if ( ! $is_verified ) {
				return null;
			}
			if ( $is_subscriber && ! $is_membre ) {
				return 'membre';
			}
			return null;
		}

		if ( $is_membre ) {
			return 'subscriber';
		}

		return null;
	}

	/**
	 * Checks whether at least one adherent with a valid membership season matches the given email.
	 *
	 * @param string $email Email address to check.
	 * @return bool True if an active membership exists for this email.
	 */
	public static function has_active_membership_for_email( string $email ): bool {
		$normalized = strtolower( trim( $email ) );
		if ( ! is_email( $normalized ) ) {
			return false;
		}

		$valid_season_ids = Season_Service::get_valid_membership_season_ids();
		if ( empty( $valid_season_ids ) ) {
			return false;
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'adherent',
				'post_status'            => array( 'publish', 'private', 'draft', 'pending', 'future' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'dame_saison_adhesion',
						'field'    => 'term_id',
						'terms'    => $valid_season_ids,
					),
				),
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => '_dame_email',
						'value'   => $normalized,
						'compare' => '=',
					),
					array(
						'key'     => '_dame_legal_rep_1_email',
						'value'   => $normalized,
						'compare' => '=',
					),
					array(
						'key'     => '_dame_legal_rep_2_email',
						'value'   => $normalized,
						'compare' => '=',
					),
				),
			)
		);

		return ! empty( $query->posts );
	}

	/**
	 * Returns a lookup map of all normalized emails belonging to adherents with a valid membership season.
	 *
	 * @return array<string, true> Associative array keyed by normalized email.
	 */
	public static function get_active_member_emails(): array {
		$valid_season_ids = Season_Service::get_valid_membership_season_ids();
		if ( empty( $valid_season_ids ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'adherent',
				'post_status'            => array( 'publish', 'private', 'draft', 'pending', 'future' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'dame_saison_adhesion',
						'field'    => 'term_id',
						'terms'    => $valid_season_ids,
					),
				),
			)
		);

		$emails = array();
		if ( ! is_array( $query->posts ) ) {
			return $emails;
		}

		foreach ( $query->posts as $post_item ) {
			if ( is_numeric( $post_item ) ) {
				$adherent_id = (int) $post_item;
			} elseif ( $post_item instanceof \WP_Post ) {
				$adherent_id = $post_item->ID;
			} else {
				continue;
			}

			foreach ( self::EMAIL_META_KEYS as $meta_key ) {
				$raw_email = (string) get_post_meta( $adherent_id, $meta_key, true );
				$email     = strtolower( trim( $raw_email ) );
				if ( '' !== $email && is_email( $email ) ) {
					$emails[ $email ] = true;
				}
			}
		}

		return $emails;
	}

	/**
	 * Synchronizes all WordPress users linked to an adherent post's emails.
	 *
	 * @param int $adherent_id Adherent post ID.
	 * @return void
	 */
	public function sync_adherent_emails( int $adherent_id ): void {
		$seen = array();
		foreach ( self::EMAIL_META_KEYS as $meta_key ) {
			$raw_email = (string) get_post_meta( $adherent_id, $meta_key, true );
			$email     = strtolower( trim( $raw_email ) );
			if ( '' !== $email && is_email( $email ) && ! isset( $seen[ $email ] ) ) {
				$seen[ $email ] = true;
				$this->sync_user_by_email( $email );
			}
		}
	}

	/**
	 * Synchronizes the role of a single WordPress user identified by email.
	 *
	 * @param string $email User email address.
	 * @return string|null New role applied, or null if unchanged.
	 */
	public function sync_user_by_email( string $email ): ?string {
		$user = get_user_by( 'email', $email );
		if ( ! $user instanceof WP_User ) {
			return null;
		}

		$is_verified           = '1' === (string) get_user_meta( $user->ID, '_dame_is_verified', true );
		$has_active_membership = self::has_active_membership_for_email( $email );
		$target_role           = self::resolve_target_role( (array) $user->roles, $is_verified, $has_active_membership );

		if ( null !== $target_role ) {
			$user->set_role( $target_role );
		}

		return $target_role;
	}

	/**
	 * Synchronizes all 'membre' and 'subscriber' WordPress users against active memberships.
	 *
	 * @return array{promoted: int, demoted: int} Counts of promoted and demoted users.
	 */
	public function sync_all_users(): array {
		$active_emails = self::get_active_member_emails();
		$users         = get_users(
			array(
				'role__in' => array( 'membre', 'subscriber' ),
			)
		);

		$promoted = 0;
		$demoted  = 0;

		foreach ( $users as $user ) {
			if ( ! $user instanceof WP_User ) {
				continue;
			}

			$email                 = strtolower( trim( (string) $user->user_email ) );
			$is_verified           = '1' === (string) get_user_meta( $user->ID, '_dame_is_verified', true );
			$has_active_membership = '' !== $email && isset( $active_emails[ $email ] );
			$target_role           = self::resolve_target_role( (array) $user->roles, $is_verified, $has_active_membership );

			if ( 'membre' === $target_role ) {
				$user->set_role( 'membre' );
				++$promoted;
			} elseif ( 'subscriber' === $target_role ) {
				$user->set_role( 'subscriber' );
				++$demoted;
			}
		}

		return array(
			'promoted' => $promoted,
			'demoted'  => $demoted,
		);
	}
}
