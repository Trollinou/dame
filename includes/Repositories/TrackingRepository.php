<?php
/**
 * Tracking Data Repository.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Repositories;

use wpdb;

/**
 * Repository for managing Message tracking, queuing, and open rates in database.
 */
class TrackingRepository {

	/**
	 * WordPress database instance.
	 *
	 * @var wpdb
	 */
	private wpdb $db;

	/**
	 * Table name for message opens and queue.
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
		$this->table_name = $this->db->prefix . 'dame_message_opens';
	}

	/**
	 * Records an email open event.
	 *
	 * @param int    $message_id Message Post ID.
	 * @param string $email_hash MD5 hash of recipient's email.
	 * @param string $user_ip    Visitor's IP address.
	 * @param string $now        Current datetime string (Y-m-d H:i:s).
	 * @return bool True if updated, false otherwise.
	 */
	public function record_open( int $message_id, string $email_hash, string $user_ip, string $now ): bool {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $this->db->query(
			$this->db->prepare(
				'UPDATE %i SET opened_at = %s, user_ip = %s WHERE message_id = %d AND email_hash = %s',
				$this->table_name,
				$now,
				$user_ip,
				$message_id,
				$email_hash
			)
		);

		return false !== $updated && $updated > 0;
	}

	/**
	 * Gets total recipient count for a given message.
	 *
	 * @param int $message_id Message Post ID.
	 * @return int Total recipients in log.
	 */
	public function get_total_recipients_count( int $message_id ): int {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $this->db->get_var(
			$this->db->prepare(
				'SELECT COUNT(*) FROM %i WHERE message_id = %d',
				$this->table_name,
				$message_id
			)
		);

		return (int) $count;
	}

	/**
	 * Gets total unique opened count for a given message.
	 *
	 * @param int $message_id Message Post ID.
	 * @return int Total unique opened count.
	 */
	public function get_unique_opens_count( int $message_id ): int {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $this->db->get_var(
			$this->db->prepare(
				'SELECT COUNT(DISTINCT email_hash) FROM %i WHERE message_id = %d AND opened_at IS NOT NULL',
				$this->table_name,
				$message_id
			)
		);

		return (int) $count;
	}

	/**
	 * Retrieves a map of recipient_id => opened_at for opened emails in a message.
	 *
	 * @param int $message_id Message Post ID.
	 * @return array<int, string> Map of recipient ID to opened_at date string.
	 */
	public function get_opened_recipients_map( int $message_id ): array {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $this->db->get_results(
			$this->db->prepare(
				'SELECT recipient_id, opened_at FROM %i WHERE message_id = %d AND opened_at IS NOT NULL',
				$this->table_name,
				$message_id
			)
		);

		$opened_recipients = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $open ) {
				$opened_recipients[ (int) $open->recipient_id ] = (string) $open->opened_at;
			}
		}

		return $opened_recipients;
	}

	/**
	 * Retrieves detailed tracking records for a specific message.
	 *
	 * @param int $message_id Message Post ID.
	 * @return array<object> List of row objects.
	 */
	public function get_message_report( int $message_id ): array {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $this->db->get_results(
			$this->db->prepare(
				'SELECT * FROM %i WHERE message_id = %d ORDER BY id ASC',
				$this->table_name,
				$message_id
			)
		);

		return is_array( $rows ) ? $rows : array();
	}
}
