<?php
/**
 * Agenda Series Manager Service.
 *
 * @package DAME\Services\Agenda
 */

declare(strict_types=1);

namespace DAME\Services\Agenda;

use WP_Post;
use WP_Query;

/**
 * Class Series_Manager
 * Handles queries and bulk deletion for recurring event series.
 */
class Series_Manager {

	/**
	 * Retrieves all posts belonging to a recurrence group, optionally filtered from a start date.
	 *
	 * @param string      $group_id  The recurrence group ID.
	 * @param string|null $from_date Optional start date format YYYY-MM-DD.
	 * @return array<int> List of post IDs.
	 */
	public static function get_series_post_ids( string $group_id, ?string $from_date = null ): array {
		if ( empty( $group_id ) ) {
			return array();
		}

		$meta_query = array(
			array(
				'key'     => '_dame_recurrence_group_id',
				'value'   => $group_id,
				'compare' => '=',
			),
		);

		if ( ! empty( $from_date ) ) {
			$meta_query[] = array(
				'key'     => '_dame_start_date',
				'value'   => $from_date,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'dame_agenda',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'meta_query'             => $meta_query,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);

		/** @var array<int> $ids */
		$ids = $query->posts;
		return $ids;
	}

	/**
	 * Counts events in a recurrence series.
	 *
	 * @param string      $group_id  The recurrence group ID.
	 * @param string|null $from_date Optional start date.
	 * @return int
	 */
	public static function count_series_events( string $group_id, ?string $from_date = null ): int {
		return count( self::get_series_post_ids( $group_id, $from_date ) );
	}

	/**
	 * Deletes the specified event and all subsequent events in the series (from event date).
	 *
	 * @param int  $post_id      The base post ID from which to delete.
	 * @param bool $force_delete If true, permanently deletes; if false, moves to trash.
	 * @return int Number of deleted posts.
	 */
	public static function delete_series_from( int $post_id, bool $force_delete = false ): int {
		$group_id = (string) get_post_meta( $post_id, '_dame_recurrence_group_id', true );
		if ( empty( $group_id ) ) {
			return 0;
		}

		$from_date = (string) get_post_meta( $post_id, '_dame_start_date', true );
		$is_parent = (int) get_post_meta( $post_id, '_dame_recurrence_is_parent', true );

		// If this is the parent post, or no date is set, delete all events in the series.
		$post_ids = ( 1 === $is_parent || empty( $from_date ) )
			? self::get_series_post_ids( $group_id, null )
			: self::get_series_post_ids( $group_id, $from_date );

		// Ensure current post is included in deletion list if not returned by date query.
		if ( ! in_array( $post_id, $post_ids, true ) ) {
			$post_ids[] = $post_id;
		}

		$deleted_count = 0;
		foreach ( $post_ids as $id ) {
			if ( $force_delete ) {
				$res = wp_delete_post( $id, true );
			} else {
				$res = wp_trash_post( $id );
			}

			if ( false !== $res && null !== $res ) {
				$deleted_count++;
			}
		}

		return $deleted_count;
	}

	/**
	 * Deletes all events belonging to a recurrence series (past and future).
	 *
	 * @param string $group_id     The recurrence group ID.
	 * @param bool   $force_delete If true, permanently deletes; if false, moves to trash.
	 * @return int Number of deleted posts.
	 */
	public static function delete_entire_series( string $group_id, bool $force_delete = false ): int {
		$post_ids = self::get_series_post_ids( $group_id, null );

		$deleted_count = 0;
		foreach ( $post_ids as $id ) {
			if ( $force_delete ) {
				$res = wp_delete_post( $id, true );
			} else {
				$res = wp_trash_post( $id );
			}

			if ( false !== $res && null !== $res ) {
				$deleted_count++;
			}
		}

		return $deleted_count;
	}
}
