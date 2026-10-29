<?php
/**
 * Ticketing Data Repository for HelloAsso campaigns and items.
 *
 * @package DAME\Repositories
 */

declare(strict_types=1);

namespace DAME\Repositories;

use wpdb;

/**
 * Class TicketingRepository
 */
class TicketingRepository {

	/**
	 * WordPress database instance.
	 *
	 * @var wpdb
	 */
	private wpdb $db;

	/**
	 * Cache group name.
	 */
	public const CACHE_GROUP = 'dame_ticketing';

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
	 * Gets the campaigns table name.
	 *
	 * @return string
	 */
	public function get_campaigns_table(): string {
		return $this->db->prefix . 'dame_ha_campaigns';
	}

	/**
	 * Gets the items table name.
	 *
	 * @return string
	 */
	public function get_items_table(): string {
		return $this->db->prefix . 'dame_ha_items';
	}

	/**
	 * Finds or creates a campaign based on its form slug and form type.
	 *
	 * @param string               $form_slug Form slug.
	 * @param string               $form_type Form type (Event, Membership, etc.).
	 * @param array<string, mixed> $data      Campaign data to set or update.
	 * @return int Campaign ID or 0 on failure.
	 */
	public function get_or_create_campaign( string $form_slug, string $form_type, array $data = array() ): int {
		$table     = $this->get_campaigns_table();
		$cache_key = 'campaign_id_' . md5( $form_slug . '_' . $form_type );
		$cached_id = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached_id && is_numeric( $cached_id ) && (int) $cached_id > 0 ) {
			$campaign_id = (int) $cached_id;
			if ( ! empty( $data ) ) {
				$this->update_campaign( $campaign_id, $data );
			}
			return $campaign_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$existing_id = $this->db->get_var(
			$this->db->prepare(
				'SELECT id FROM %i WHERE form_slug = %s AND form_type = %s LIMIT 1',
				$table,
				$form_slug,
				$form_type
			)
		);

		if ( $existing_id ) {
			$campaign_id = (int) $existing_id;
			wp_cache_set( $cache_key, $campaign_id, self::CACHE_GROUP, 3600 );
			if ( ! empty( $data ) ) {
				$this->update_campaign( $campaign_id, $data );
			}
			return $campaign_id;
		}

		// Auto-categorize campaign if not specified.
		$category = $data['campaign_category'] ?? 'other';
		if ( 'other' === $category ) {
			$lower_type = strtolower( $form_type );
			if ( 'membership' === $lower_type ) {
				$category = 'membership';
			} elseif ( 'event' === $lower_type ) {
				$category = 'tournament';
			}
		}

		$insert_data = array(
			'form_slug'         => sanitize_title( $form_slug ),
			'form_type'         => sanitize_text_field( $form_type ),
			'title'             => sanitize_text_field( (string) ( $data['title'] ?? $form_slug ) ),
			'campaign_category' => sanitize_key( (string) $category ),
			'linked_agenda_id'  => isset( $data['linked_agenda_id'] ) ? absint( $data['linked_agenda_id'] ) : null,
			'linked_season_id'  => isset( $data['linked_season_id'] ) ? absint( $data['linked_season_id'] ) : null,
			'start_date'        => isset( $data['start_date'] ) ? sanitize_text_field( (string) $data['start_date'] ) : null,
			'end_date'          => isset( $data['end_date'] ) ? sanitize_text_field( (string) $data['end_date'] ) : null,
			'last_sync_at'      => current_time( 'mysql' ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $this->db->insert(
			$table,
			$insert_data,
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return 0;
		}

		$campaign_id = (int) $this->db->insert_id;
		wp_cache_set( $cache_key, $campaign_id, self::CACHE_GROUP, 3600 );
		wp_cache_delete( 'campaigns_list', self::CACHE_GROUP );
		wp_cache_delete( 'campaign_type_counts', self::CACHE_GROUP );

		return $campaign_id;
	}

	/**
	 * Updates campaign information.
	 *
	 * @param int                  $campaign_id Campaign ID.
	 * @param array<string, mixed> $data        Fields to update.
	 * @return bool
	 */
	public function update_campaign( int $campaign_id, array $data ): bool {
		$table = $this->get_campaigns_table();

		$clean_data = array();
		$formats    = array();

		if ( isset( $data['title'] ) ) {
			$clean_data['title'] = sanitize_text_field( (string) $data['title'] );
			$formats[]           = '%s';
		}
		if ( isset( $data['campaign_category'] ) ) {
			$clean_data['campaign_category'] = sanitize_key( (string) $data['campaign_category'] );
			$formats[]                       = '%s';
		}
		if ( array_key_exists( 'linked_agenda_id', $data ) ) {
			$clean_data['linked_agenda_id'] = ! empty( $data['linked_agenda_id'] ) ? absint( $data['linked_agenda_id'] ) : null;
			$formats[]                      = '%d';
		}
		if ( array_key_exists( 'linked_season_id', $data ) ) {
			$clean_data['linked_season_id'] = ! empty( $data['linked_season_id'] ) ? absint( $data['linked_season_id'] ) : null;
			$formats[]                      = '%d';
		}
		if ( array_key_exists( 'start_date', $data ) ) {
			$clean_data['start_date'] = ! empty( $data['start_date'] ) ? sanitize_text_field( (string) $data['start_date'] ) : null;
			$formats[]                = '%s';
		}
		if ( array_key_exists( 'end_date', $data ) ) {
			$clean_data['end_date'] = ! empty( $data['end_date'] ) ? sanitize_text_field( (string) $data['end_date'] ) : null;
			$formats[]              = '%s';
		}
		if ( isset( $data['last_sync_at'] ) ) {
			$clean_data['last_sync_at'] = sanitize_text_field( (string) $data['last_sync_at'] );
			$formats[]                  = '%s';
		}

		if ( empty( $clean_data ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $this->db->update(
			$table,
			$clean_data,
			array( 'id' => $campaign_id ),
			$formats,
			array( '%d' )
		);

		wp_cache_delete( 'campaign_' . $campaign_id, self::CACHE_GROUP );
		wp_cache_delete( 'campaigns_list', self::CACHE_GROUP );
		wp_cache_delete( 'campaign_type_counts', self::CACHE_GROUP );

		return false !== $result;
	}

	/**
	 * Retrieves all campaigns with optional filtering and sorting.
	 *
	 * @param array<string, mixed> $filters Optional filters ('form_type', 'orderby', 'order').
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all_campaigns( array $filters = array() ): array {
		$cache_key = 'campaigns_list_' . md5( (string) wp_json_encode( $filters ) );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$table  = $this->get_campaigns_table();
		$where  = 'WHERE 1=1';
		$params = array( $table );

		if ( ! empty( $filters['form_type'] ) ) {
			$where   .= ' AND form_type = %s';
			$params[] = sanitize_text_field( (string) $filters['form_type'] );
		}

		$allowed_orderby = array(
			'start_date' => 'start_date',
			'title'      => 'title',
			'id'         => 'id',
			'form_type'  => 'form_type',
		);
		$orderby_key     = (string) ( $filters['orderby'] ?? 'start_date' );
		$order_col       = $allowed_orderby[ $orderby_key ] ?? 'start_date';
		$order_dir       = ( isset( $filters['order'] ) && 'ASC' === strtoupper( (string) $filters['order'] ) ) ? 'ASC' : 'DESC';

		// For start_date, place NULL values at the bottom regardless of sort order.
		if ( 'start_date' === $order_col ) {
			$order_clause = ( 'ASC' === $order_dir )
				? 'ORDER BY (start_date IS NULL), start_date ASC, id DESC'
				: 'ORDER BY (start_date IS NULL), start_date DESC, id DESC';
		} else {
			$order_clause = "ORDER BY {$order_col} {$order_dir}, id DESC";
		}

		$sql = "SELECT * FROM %i {$where} {$order_clause}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $this->db->get_results(
			$this->db->prepare( $sql, ...$params ),
			ARRAY_A
		);

		$result = is_array( $rows ) ? $rows : array();
		wp_cache_set( $cache_key, $result, self::CACHE_GROUP, 1800 );

		return $result;
	}

	/**
	 * Returns the counts of campaigns grouped by form_type.
	 *
	 * @return array<string, int>
	 */
	public function get_campaign_type_counts(): array {
		$cached = wp_cache_get( 'campaign_type_counts', self::CACHE_GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$table = $this->get_campaigns_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $this->db->get_results(
			$this->db->prepare( 'SELECT form_type, COUNT(*) as cnt FROM %i GROUP BY form_type', $table ),
			ARRAY_A
		);

		$counts = array(
			'all' => 0,
		);

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$ft             = (string) ( $row['form_type'] ?? '' );
				$c              = (int) ( $row['cnt'] ?? 0 );
				$counts[ $ft ]  = $c;
				$counts['all'] += $c;
			}
		}

		wp_cache_set( 'campaign_type_counts', $counts, self::CACHE_GROUP, 1800 );

		return $counts;
	}

	/**
	 * Retrieves a single campaign by ID.
	 *
	 * @param int $campaign_id Campaign ID.
	 * @return array<string, mixed>|null
	 */
	public function get_campaign( int $campaign_id ): ?array {
		if ( $campaign_id <= 0 ) {
			return null;
		}

		$cache_key = 'campaign_' . $campaign_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$table = $this->get_campaigns_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$row = $this->db->get_row(
			$this->db->prepare( 'SELECT * FROM %i WHERE id = %d LIMIT 1', $table, $campaign_id ),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		wp_cache_set( $cache_key, $row, self::CACHE_GROUP, 1800 );
		return $row;
	}

	/**
	 * Inserts or updates an item (ticket/participant).
	 *
	 * Uses ha_item_id as the unique idempotency key when provided.
	 *
	 * @param array<string, mixed> $item_data Item record data.
	 * @return int Item ID or 0 on failure.
	 */
	public function save_item( array $item_data ): int {
		$table      = $this->get_items_table();
		$ha_item_id = isset( $item_data['ha_item_id'] ) && is_numeric( $item_data['ha_item_id'] ) ? (int) $item_data['ha_item_id'] : null;

		$existing_id = 0;
		if ( null !== $ha_item_id && $ha_item_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$existing_id = (int) $this->db->get_var(
				$this->db->prepare( 'SELECT id FROM %i WHERE ha_item_id = %d LIMIT 1', $table, $ha_item_id )
			);
		}

		$data = array(
			'campaign_id'                => absint( (int) ( $item_data['campaign_id'] ?? 0 ) ),
			'ha_item_id'                 => $ha_item_id,
			'ha_order_id'                => isset( $item_data['ha_order_id'] ) ? absint( (int) $item_data['ha_order_id'] ) : null,
			'source'                     => sanitize_key( (string) ( $item_data['source'] ?? 'helloasso' ) ),
			'status'                     => sanitize_text_field( (string) ( $item_data['status'] ?? 'Processed' ) ),
			'participant_first_name'     => sanitize_text_field( (string) ( $item_data['participant_first_name'] ?? '' ) ),
			'participant_last_name'      => sanitize_text_field( (string) ( $item_data['participant_last_name'] ?? '' ) ),
			'participant_email'          => sanitize_email( (string) ( $item_data['participant_email'] ?? '' ) ),
			'participant_birth_date'     => ! empty( $item_data['participant_birth_date'] ) ? sanitize_text_field( (string) $item_data['participant_birth_date'] ) : null,
			'payer_first_name'           => sanitize_text_field( (string) ( $item_data['payer_first_name'] ?? '' ) ),
			'payer_last_name'            => sanitize_text_field( (string) ( $item_data['payer_last_name'] ?? '' ) ),
			'payer_email'                => sanitize_email( (string) ( $item_data['payer_email'] ?? '' ) ),
			'tier_name'                  => sanitize_text_field( (string) ( $item_data['tier_name'] ?? '' ) ),
			'amount_cents'               => (int) ( $item_data['amount_cents'] ?? 0 ),
			'payment_method'             => sanitize_text_field( (string) ( $item_data['payment_method'] ?? 'HelloAsso' ) ),
			'order_date'                 => ! empty( $item_data['order_date'] ) ? sanitize_text_field( (string) $item_data['order_date'] ) : current_time( 'mysql' ),
			'license_number'             => sanitize_text_field( (string) ( $item_data['license_number'] ?? '' ) ),
			'fide_id'                    => sanitize_text_field( (string) ( $item_data['fide_id'] ?? '' ) ),
			'elo'                        => isset( $item_data['elo'] ) && is_numeric( $item_data['elo'] ) ? (int) $item_data['elo'] : null,
			'club_name'                  => sanitize_text_field( (string) ( $item_data['club_name'] ?? '' ) ),
			'phone'                      => sanitize_text_field( (string) ( $item_data['phone'] ?? '' ) ),
			'custom_fields_json'         => isset( $item_data['custom_fields_json'] ) ? ( is_string( $item_data['custom_fields_json'] ) ? $item_data['custom_fields_json'] : wp_json_encode( $item_data['custom_fields_json'] ) ) : null,
			'matched_adherent_id'        => absint( (int) ( $item_data['matched_adherent_id'] ?? 0 ) ),
			'matched_pre_inscription_id' => absint( (int) ( $item_data['matched_pre_inscription_id'] ?? 0 ) ),
			'matched_contact_id'         => absint( (int) ( $item_data['matched_contact_id'] ?? 0 ) ),
			'checked_in'                 => ! empty( $item_data['checked_in'] ) ? 1 : 0,
			'checked_in_at'              => ! empty( $item_data['checked_in_at'] ) ? sanitize_text_field( (string) $item_data['checked_in_at'] ) : null,
		);

		$formats = array(
			'%d', // campaign_id.
			null === $data['ha_item_id'] ? null : '%d',
			null === $data['ha_order_id'] ? null : '%d',
			'%s', // source.
			'%s', // status.
			'%s', // participant_first_name.
			'%s', // participant_last_name.
			'%s', // participant_email.
			null === $data['participant_birth_date'] ? null : '%s',
			'%s', // payer_first_name.
			'%s', // payer_last_name.
			'%s', // payer_email.
			'%s', // tier_name.
			'%d', // amount_cents.
			'%s', // payment_method.
			'%s', // order_date.
			'%s', // license_number.
			'%s', // fide_id.
			null === $data['elo'] ? null : '%d',
			'%s', // club_name.
			'%s', // phone.
			'%s', // custom_fields_json.
			'%d', // matched_adherent_id.
			'%d', // matched_pre_inscription_id.
			'%d', // matched_contact_id.
			'%d', // checked_in.
			null === $data['checked_in_at'] ? null : '%s',
		);

		// Align formats array strictly to non-null vs null values.
		$filtered_data    = array();
		$filtered_formats = array();
		$idx              = 0;
		foreach ( $data as $k => $v ) {
			$filtered_data[ $k ] = $v;
			if ( null !== $v && null !== $formats[ $idx ] ) {
				$filtered_formats[] = (string) $formats[ $idx ];
			} else {
				$filtered_formats[] = '%s';
			}
			++$idx;
		}

		if ( $existing_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->db->update(
				$table,
				$filtered_data,
				array( 'id' => $existing_id ),
				$filtered_formats,
				array( '%d' )
			);
			$item_id = $existing_id;
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->db->insert(
				$table,
				$filtered_data,
				$filtered_formats
			);
			$item_id = (int) $this->db->insert_id;
		}

		// Purge caches.
		wp_cache_delete( 'campaign_items_' . $data['campaign_id'], self::CACHE_GROUP );
		wp_cache_delete( 'campaign_stats_' . $data['campaign_id'], self::CACHE_GROUP );

		return $item_id;
	}

	/**
	 * Retrieves items belonging to a campaign, optionally filtered by checked_in, tier or multiple tiers.
	 *
	 * @param int                  $campaign_id Campaign ID.
	 * @param array<string, mixed> $filters     Optional filters: checked_in (bool), tier (string), tiers (array<int, string>).
	 * @return array<int, array<string, mixed>>
	 */
	public function get_campaign_items( int $campaign_id, array $filters = array() ): array {
		if ( $campaign_id <= 0 ) {
			return array();
		}

		$table     = $this->get_items_table();
		$cache_key = 'campaign_items_' . $campaign_id . '_' . md5( (string) wp_json_encode( $filters ) );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$where_parts = array( 'campaign_id = %d' );
		$params      = array( $campaign_id );

		if ( isset( $filters['checked_in'] ) ) {
			$where_parts[] = 'checked_in = %d';
			$params[]      = $filters['checked_in'] ? 1 : 0;
		}

		if ( ! empty( $filters['tiers'] ) && is_array( $filters['tiers'] ) ) {
			$placeholders = array();
			foreach ( $filters['tiers'] as $t ) {
				$placeholders[] = '%s';
				$params[]       = sanitize_text_field( (string) $t );
			}
			$where_parts[] = 'tier_name IN (' . implode( ', ', $placeholders ) . ')';
		} elseif ( ! empty( $filters['tier'] ) ) {
			$where_parts[] = 'tier_name = %s';
			$params[]      = sanitize_text_field( (string) $filters['tier'] );
		}

		$where_clause = implode( ' AND ', $where_parts );
		$sql          = "SELECT * FROM %i WHERE {$where_clause} ORDER BY participant_last_name ASC, participant_first_name ASC";

		// Prepend table placeholder.
		array_unshift( $params, $table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $this->db->get_results(
			$this->db->prepare( $sql, ...$params ),
			ARRAY_A
		);

		$result = is_array( $rows ) ? $rows : array();
		wp_cache_set( $cache_key, $result, self::CACHE_GROUP, 600 );

		return $result;
	}

	/**
	 * Toggles or updates the checked_in status of a ticket item.
	 *
	 * @param int       $item_id    Ticket item ID.
	 * @param bool|null $checked_in True/False to set, or null to toggle.
	 * @return bool True on success.
	 */
	public function set_checked_in( int $item_id, ?bool $checked_in = null ): bool {
		if ( $item_id <= 0 ) {
			return false;
		}

		$table = $this->get_items_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$current = $this->db->get_row(
			$this->db->prepare( 'SELECT id, campaign_id, checked_in FROM %i WHERE id = %d LIMIT 1', $table, $item_id ),
			ARRAY_A
		);

		if ( ! is_array( $current ) ) {
			return false;
		}

		$new_status = null !== $checked_in ? ( $checked_in ? 1 : 0 ) : ( 1 === (int) $current['checked_in'] ? 0 : 1 );
		$checked_at = 1 === $new_status ? current_time( 'mysql' ) : null;

		$update_data    = array( 'checked_in' => $new_status );
		$update_formats = array( '%d' );
		if ( null !== $checked_at ) {
			$update_data['checked_in_at'] = $checked_at;
			$update_formats[]             = '%s';
		} else {
			$update_data['checked_in_at'] = null;
			$update_formats[]             = '%s';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $this->db->update(
			$table,
			$update_data,
			array( 'id' => $item_id ),
			$update_formats,
			array( '%d' )
		);

		$campaign_id = (int) $current['campaign_id'];
		wp_cache_delete( 'campaign_items_' . $campaign_id, self::CACHE_GROUP );
		wp_cache_delete( 'campaign_stats_' . $campaign_id, self::CACHE_GROUP );

		return false !== $result;
	}

	/**
	 * Retrieves a single ticket item by ID.
	 *
	 * @param int $item_id Ticket item ID.
	 * @return array<string, mixed>|null
	 */
	public function get_item( int $item_id ): ?array {
		if ( $item_id <= 0 ) {
			return null;
		}

		$table = $this->get_items_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$row = $this->db->get_row(
			$this->db->prepare( 'SELECT * FROM %i WHERE id = %d LIMIT 1', $table, $item_id ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Computes statistics for a given campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 * @return array{total_items: int, checked_in: int, total_amount_cents: int, tiers: array<string, int>}
	 */
	public function get_campaign_stats( int $campaign_id ): array {
		if ( $campaign_id <= 0 ) {
			return array(
				'total_items'        => 0,
				'checked_in'         => 0,
				'total_amount_cents' => 0,
				'tiers'              => array(),
			);
		}

		$cache_key = 'campaign_stats_' . $campaign_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached; // @phpstan-ignore return.type
		}

		$table = $this->get_items_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$totals = $this->db->get_row(
			$this->db->prepare(
				"SELECT 
					COUNT(*) as total_items,
					SUM(CASE WHEN checked_in = 1 THEN 1 ELSE 0 END) as checked_in,
					COALESCE(SUM(amount_cents), 0) as total_amount_cents
				 FROM %i
				 WHERE campaign_id = %d AND status != 'Refunded' AND status != 'Canceled'",
				$table,
				$campaign_id
			),
			ARRAY_A
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$tier_rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT tier_name, COUNT(*) as cnt 
				 FROM %i 
				 WHERE campaign_id = %d AND status != 'Refunded' AND status != 'Canceled'
				 GROUP BY tier_name",
				$table,
				$campaign_id
			),
			ARRAY_A
		);

		$tiers = array();
		if ( is_array( $tier_rows ) ) {
			foreach ( $tier_rows as $t ) {
				$name           = ! empty( $t['tier_name'] ) ? (string) $t['tier_name'] : __( 'Standard', 'dame' );
				$tiers[ $name ] = (int) $t['cnt'];
			}
		}

		$stats = array(
			'total_items'        => (int) ( $totals['total_items'] ?? 0 ),
			'checked_in'         => (int) ( $totals['checked_in'] ?? 0 ),
			'total_amount_cents' => (int) ( $totals['total_amount_cents'] ?? 0 ),
			'tiers'              => $tiers,
		);

		wp_cache_set( $cache_key, $stats, self::CACHE_GROUP, 600 );

		return $stats;
	}

	/**
	 * Retrieves campaigns that have active (non-canceled, non-refunded) items.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_campaigns_with_active_items(): array {
		$cache_key = 'campaigns_with_active_items';
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$campaigns_table = $this->get_campaigns_table();
		$items_table     = $this->get_items_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT c.*, COUNT(i.id) as active_items_count 
				 FROM %i c
				 INNER JOIN %i i ON c.id = i.campaign_id
				 WHERE i.status NOT IN ('Canceled', 'Refunded')
				 GROUP BY c.id
				 ORDER BY (c.start_date IS NULL), c.start_date DESC, c.id DESC",
				$campaigns_table,
				$items_table
			),
			ARRAY_A
		);

		$result = is_array( $rows ) ? $rows : array();
		wp_cache_set( $cache_key, $result, self::CACHE_GROUP, 1800 );

		return $result;
	}

	/**
	 * Retrieves all active items with campaign title.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all_active_items(): array {
		$cache_key = 'all_active_items_list';
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$campaigns_table = $this->get_campaigns_table();
		$items_table     = $this->get_items_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT i.*, c.title as campaign_title, c.form_type as campaign_form_type
				 FROM %i i
				 INNER JOIN %i c ON i.campaign_id = c.id
				 WHERE i.status NOT IN ('Canceled', 'Refunded')
				 ORDER BY i.participant_last_name ASC, i.participant_first_name ASC, i.id ASC",
				$items_table,
				$campaigns_table
			),
			ARRAY_A
		);

		$result = is_array( $rows ) ? $rows : array();
		wp_cache_set( $cache_key, $result, self::CACHE_GROUP, 900 );

		return $result;
	}

	/**
	 * Retrieves active items belonging to specified campaign IDs.
	 *
	 * @param array<int> $campaign_ids List of campaign IDs.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_active_items_by_campaigns( array $campaign_ids ): array {
		$clean_ids = array_values( array_filter( array_map( 'absint', $campaign_ids ) ) );
		if ( empty( $clean_ids ) ) {
			return array();
		}

		$campaigns_table = $this->get_campaigns_table();
		$items_table     = $this->get_items_table();
		$placeholders    = implode( ', ', array_fill( 0, count( $clean_ids ), '%d' ) );

		$params = array_merge( array( $items_table, $campaigns_table ), $clean_ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT i.*, c.title as campaign_title, c.form_type as campaign_form_type
				 FROM %i i
				 INNER JOIN %i c ON i.campaign_id = c.id
				 WHERE i.status NOT IN ('Canceled', 'Refunded') AND i.campaign_id IN ({$placeholders})
				 ORDER BY i.participant_last_name ASC, i.participant_first_name ASC",
				...$params
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Retrieves active items by their IDs.
	 *
	 * @param array<int> $item_ids List of item IDs.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_active_items_by_ids( array $item_ids ): array {
		$clean_ids = array_values( array_filter( array_map( 'absint', $item_ids ) ) );
		if ( empty( $clean_ids ) ) {
			return array();
		}

		$campaigns_table = $this->get_campaigns_table();
		$items_table     = $this->get_items_table();
		$placeholders    = implode( ', ', array_fill( 0, count( $clean_ids ), '%d' ) );

		$params = array_merge( array( $items_table, $campaigns_table ), $clean_ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT i.*, c.title as campaign_title, c.form_type as campaign_form_type
				 FROM %i i
				 INNER JOIN %i c ON i.campaign_id = c.id
				 WHERE i.status NOT IN ('Canceled', 'Refunded') AND i.id IN ({$placeholders})
				 ORDER BY i.participant_last_name ASC, i.participant_first_name ASC",
				...$params
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}
}
