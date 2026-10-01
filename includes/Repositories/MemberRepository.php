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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$id = $this->db->get_var(
			$this->db->prepare(
				"SELECT post_id FROM %i WHERE meta_key = '_dame_ffe_id' AND meta_value = %s LIMIT 1",
				$this->db->postmeta,
				$sanitized_ffe
			)
		);

		return $id ? (int) $id : null;
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

		return (int) $count;
	}
}
