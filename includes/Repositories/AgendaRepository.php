<?php
/**
 * Agenda Data Repository.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Repositories;

use wpdb;

/**
 * Repository for managing Agenda events, recurrences and series in database.
 */
class AgendaRepository {

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
	 * Retrieves event IDs belonging to a recurrence series.
	 *
	 * @param string      $group_id  Recurrence group ID identifier.
	 * @param string|null $from_date Optional minimum start date (Y-m-d).
	 * @return array<int> Array of event Post IDs.
	 */
	public function get_series_event_ids( string $group_id, ?string $from_date = null ): array {
		if ( null !== $from_date && '' !== $from_date ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $this->db->get_col(
				$this->db->prepare(
					"SELECT p.ID FROM %i p
					INNER JOIN %i pm_series ON p.ID = pm_series.post_id AND pm_series.meta_key = '_dame_recurrence_group_id' AND pm_series.meta_value = %s
					INNER JOIN %i pm_date ON p.ID = pm_date.post_id AND pm_date.meta_key = '_dame_start_date' AND pm_date.meta_value >= %s
					WHERE p.post_type = 'dame_agenda' AND p.post_status != 'trash'",
					$this->db->posts,
					$this->db->postmeta,
					$group_id,
					$this->db->postmeta,
					$from_date
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $this->db->get_col(
				$this->db->prepare(
					"SELECT p.ID FROM %i p
					INNER JOIN %i pm_series ON p.ID = pm_series.post_id AND pm_series.meta_key = '_dame_recurrence_group_id' AND pm_series.meta_value = %s
					WHERE p.post_type = 'dame_agenda' AND p.post_status != 'trash'",
					$this->db->posts,
					$this->db->postmeta,
					$group_id
				)
			);
		}

		return array_map( 'intval', is_array( $ids ) ? $ids : array() );
	}
}
