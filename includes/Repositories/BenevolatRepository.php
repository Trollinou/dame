<?php
/**
 * Benevolat Data Repository.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Repositories;

use wpdb;

/**
 * Repository for managing Benevolat (Volunteering) votes and choices in database.
 */
class BenevolatRepository {

	/**
	 * WordPress database instance.
	 *
	 * @var wpdb
	 */
	private wpdb $db;

	/**
	 * Table name for benevolat votes.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Constructor.
	 *
	 * @param wpdb|null $db Optional database instance.
	 */
	public function __construct( ?wpdb $db = null ) {
		global $wpdb;
		$this->db         = $db ?? $wpdb;
		$this->table_name = $this->db->prefix . 'dame_benevolat_votes';
	}

	/**
	 * Retrieves the distinct voter count for a specific benevolat call.
	 *
	 * @param int $benevolat_id Benevolat Post ID.
	 * @return int Total number of unique voters.
	 */
	public function get_distinct_voter_count( int $benevolat_id ): int {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(DISTINCT v.recipient_id) FROM %i v INNER JOIN %i p ON v.recipient_id = p.ID WHERE v.poll_id = %d AND p.post_status = 'publish'",
				$this->table_name,
				$this->db->posts,
				$benevolat_id
			)
		);

		return (int) $count;
	}

	/**
	 * Retrieves all choice keys selected by a specific response ID.
	 *
	 * @param int $response_id Response Post ID.
	 * @return array<string> Array of choice keys.
	 */
	public function get_choices_by_response( int $response_id ): array {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$choices = $this->db->get_col(
			$this->db->prepare(
				'SELECT choice_key FROM %i WHERE recipient_id = %d',
				$this->table_name,
				$response_id
			)
		);

		return is_array( $choices ) ? $choices : array();
	}

	/**
	 * Deletes all choices for a given response ID.
	 *
	 * @param int $response_id Response Post ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_choices_by_response( int $response_id ): bool {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $this->db->delete(
			$this->table_name,
			array( 'recipient_id' => $response_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Saves a single choice for a response.
	 *
	 * @param int    $benevolat_id Benevolat Post ID.
	 * @param int    $response_id  Response Post ID.
	 * @param string $choice_key   Choice key identifier.
	 * @return bool True on success, false on failure.
	 */
	public function save_choice( int $benevolat_id, int $response_id, string $choice_key ): bool {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $this->db->insert(
			$this->table_name,
			array(
				'poll_id'      => $benevolat_id,
				'recipient_id' => $response_id,
				'choice_key'   => sanitize_text_field( $choice_key ),
				'voted_at'     => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Saves multiple choices for a response.
	 *
	 * @param int           $benevolat_id Benevolat Post ID.
	 * @param int           $response_id  Response Post ID.
	 * @param array<string> $choices      Array of choice keys.
	 * @return bool True on success.
	 */
	public function save_choices( int $benevolat_id, int $response_id, array $choices ): bool {
		foreach ( $choices as $choice_key ) {
			$this->save_choice( $benevolat_id, $response_id, (string) $choice_key );
		}

		return true;
	}

	/**
	 * Deletes all votes and choices associated with a specific benevolat call.
	 *
	 * @param int $benevolat_id Benevolat Post ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_all_for_benevolat( int $benevolat_id ): bool {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $this->db->delete(
			$this->table_name,
			array( 'poll_id' => $benevolat_id ),
			array( '%d' )
		);

		return false !== $result;
	}
}
