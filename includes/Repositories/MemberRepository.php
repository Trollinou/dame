<?php
/**
 * Member Data Repository.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Repositories;

use wpdb;

/**
 * Repository for Member and Contact database queries.
 */
class MemberRepository {

	/**
	 * WordPress database instance.
	 *
	 * @var wpdb
	 */
	private wpdb $db;

	/**
	 * Constructor.
	 *
	 * @param wpdb|null $db Optional database instance.
	 */
	public function __construct( ?wpdb $db = null ) {
		global $wpdb;
		$this->db = $db ?? $wpdb;
	}

	/**
	 * Finds an adherent ID by FFE license code.
	 *
	 * @param string $ffe_id FFE license ID (e.g. N12345).
	 * @return int|null Post ID if found, null otherwise.
	 */
	public function find_by_ffe_id( string $ffe_id ): ?int {
		$sanitized_ffe = sanitize_text_field( trim( $ffe_id ) );
		if ( empty( $sanitized_ffe ) ) {
			return null;
		}

		$cache_key = 'ffe_' . md5( strtolower( $sanitized_ffe ) );
		$cached    = wp_cache_get( $cache_key, 'dame_members' );

		if ( false !== $cached ) {
			return (int) $cached > 0 ? (int) $cached : null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$id = $this->db->get_var(
			$this->db->prepare(
				"SELECT post_id FROM %i WHERE meta_key = '_dame_ffe_id' AND meta_value = %s LIMIT 1",
				$this->db->postmeta,
				$sanitized_ffe
			)
		);

		$result_id = $id ? (int) $id : 0;
		wp_cache_set( $cache_key, $result_id, 'dame_members', 3600 );

		return $result_id > 0 ? $result_id : null;
	}

	/**
	 * Counts active members registered for a specific season.
	 *
	 * @param int $season_term_id Season taxonomy term ID.
	 * @return int Total number of active members in the season.
	 */
	public function count_active_members_by_season( int $season_term_id ): int {
		if ( $season_term_id <= 0 ) {
			return 0;
		}

		$cache_key = 'count_season_' . $season_term_id;
		$cached    = wp_cache_get( $cache_key, 'dame_members' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$count = $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(DISTINCT p.ID) FROM %i p
				INNER JOIN %i tr ON p.ID = tr.object_id
				INNER JOIN %i tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE p.post_type = 'adherent'
				AND p.post_status = 'publish'
				AND tt.term_id = %d",
				$this->db->posts,
				$this->db->term_relationships,
				$this->db->term_taxonomy,
				$season_term_id
			)
		);

		$count_int = (int) $count;
		wp_cache_set( $cache_key, $count_int, 'dame_members', 1800 );

		return $count_int;
	}

	/**
	 * Invalidates member repository caches upon mutation.
	 *
	 * @param int $post_id Post ID being mutated.
	 */
	public static function invalidate_member_cache( int $post_id ): void {
		$ffe_id = get_post_meta( $post_id, '_dame_ffe_id', true );
		if ( ! empty( $ffe_id ) && is_string( $ffe_id ) ) {
			wp_cache_delete( 'ffe_' . md5( strtolower( trim( $ffe_id ) ) ), 'dame_members' );
		}

		$terms = wp_get_post_terms( $post_id, 'dame_season', array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term_id ) {
				wp_cache_delete( 'count_season_' . (int) $term_id, 'dame_members' );
			}
		}
	}
}
