<?php
/**
 * Agenda Batch Creator Service.
 *
 * @package DAME\Services\Agenda
 */

declare(strict_types=1);

namespace DAME\Services\Agenda;

use DateTimeImmutable;
use WP_Post;

/**
 * Class Batch_Creator
 * Handles duplication and batch creation of recurring dame_agenda posts.
 */
class Batch_Creator {

	/**
	 * Creates a recurring series of events from a parent post.
	 *
	 * @param int                     $parent_post_id   The parent post ID.
	 * @param array<DateTimeImmutable> $occurrence_dates List of subsequent occurrence dates.
	 * @return array<int> List of newly created post IDs.
	 */
	public static function create_series( int $parent_post_id, array $occurrence_dates ): array {
		if ( empty( $occurrence_dates ) ) {
			return array();
		}

		$parent_post = get_post( $parent_post_id );
		if ( ! $parent_post instanceof WP_Post ) {
			return array();
		}

		// Generate a unique recurrence group ID.
		$group_id = uniqid( 'rec_', true );

		// Tag parent post with group metadata.
		update_post_meta( $parent_post_id, '_dame_recurrence_group_id', $group_id );
		update_post_meta( $parent_post_id, '_dame_recurrence_is_parent', 1 );

		// Retrieve parent meta.
		$parent_start_date_str = (string) get_post_meta( $parent_post_id, '_dame_start_date', true );
		$parent_end_date_str   = (string) get_post_meta( $parent_post_id, '_dame_end_date', true );

		// Calculate duration in days between parent start and end date.
		$day_span = 0;
		if ( ! empty( $parent_start_date_str ) && ! empty( $parent_end_date_str ) ) {
			$p_start  = new DateTimeImmutable( $parent_start_date_str );
			$p_end    = new DateTimeImmutable( $parent_end_date_str );
			$diff     = $p_start->diff( $p_end );
			$day_span = (int) $diff->days;
			if ( $diff->invert ) {
				$day_span = 0;
			}
		}

		// Retrieve categories.
		$category_ids = wp_get_object_terms(
			$parent_post_id,
			'dame_agenda_category',
			array( 'fields' => 'ids' )
		);
		if ( is_wp_error( $category_ids ) ) {
			$category_ids = array();
		}

		// Standard meta fields to clone.
		$meta_fields = array(
			'_dame_start_time',
			'_dame_end_time',
			'_dame_all_day',
			'_dame_competition_type',
			'_dame_competition_level',
			'_dame_location_name',
			'_dame_address_1',
			'_dame_address_2',
			'_dame_postal_code',
			'_dame_city',
			'_dame_latitude',
			'_dame_longitude',
			'_dame_distance',
			'_dame_travel_time',
			'_dame_agenda_description',
		);

		$cloned_meta = array();
		foreach ( $meta_fields as $meta_key ) {
			$cloned_meta[ $meta_key ] = get_post_meta( $parent_post_id, $meta_key, true );
		}

		// Participants array.
		$participants = get_post_meta( $parent_post_id, '_dame_event_participants', true );
		if ( ! is_array( $participants ) ) {
			$participants = array();
		}

		$created_ids = array();

		// Avoid re-triggering save hooks infinitely.
		remove_all_actions( 'save_post_dame_agenda' );

		$base_slug = ! empty( $parent_post->post_name )
			? $parent_post->post_name
			: sanitize_title( $parent_post->post_title );

		foreach ( $occurrence_dates as $date ) {
			$start_date_str = $date->format( 'Y-m-d' );
			$end_date       = ( $day_span > 0 ) ? $date->modify( sprintf( '+%d days', $day_span ) ) : $date;
			$end_date_str   = $end_date->format( 'Y-m-d' );
			$slug_suffix    = $date->format( 'Ymd' );
			$post_slug      = sprintf( '%s-%s', $base_slug, $slug_suffix );

			$post_data = array(
				'post_title'   => $parent_post->post_title,
				'post_content' => $parent_post->post_content,
				'post_status'  => $parent_post->post_status,
				'post_type'    => 'dame_agenda',
				'post_author'  => (int) $parent_post->post_author,
				'post_name'    => $post_slug,
			);

			$new_post_id = wp_insert_post( $post_data );

			if ( ! is_wp_error( $new_post_id ) && $new_post_id > 0 ) {
				// Assign taxonomy terms.
				if ( ! empty( $category_ids ) ) {
					wp_set_object_terms( $new_post_id, array_map( 'intval', $category_ids ), 'dame_agenda_category' );
				}

				// Assign dates.
				update_post_meta( $new_post_id, '_dame_start_date', $start_date_str );
				update_post_meta( $new_post_id, '_dame_end_date', $end_date_str );

				// Assign cloned meta.
				foreach ( $cloned_meta as $key => $val ) {
					update_post_meta( $new_post_id, $key, $val );
				}

				// Assign participants.
				update_post_meta( $new_post_id, '_dame_event_participants', $participants );

				// Recurrence group link.
				update_post_meta( $new_post_id, '_dame_recurrence_group_id', $group_id );
				update_post_meta( $new_post_id, '_dame_recurrence_is_parent', 0 );

				$created_ids[] = $new_post_id;
			}
		}

		return $created_ids;
	}
}
