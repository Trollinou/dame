<?php
/**
 * Unit tests for Message Reset & Send Filtering Logic.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Admin\Pages\Mailing\RecipientResolver;

/**
 * Class MessageResetSendTest
 */
final class MessageResetSendTest extends TestCase {

	/**
	 * Resolver instance.
	 *
	 * @var RecipientResolver
	 */
	private RecipientResolver $resolver;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->resolver = new RecipientResolver();
	}

	/**
	 * Tests filter_already_received when tracking table has no records for the message (e.g. after reset).
	 */
	public function test_filter_already_received_returns_all_ids_when_empty_tracking(): void {
		global $wpdb;
		$original_wpdb = $wpdb ?? null;

		$wpdb_mock = new class() {
			public string $prefix = 'wp_';

			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			public function get_col( string $query ): array {
				return array();
			}
		};

		$wpdb = $wpdb_mock;

		try {
			$ids    = array( 101, 102, 103, 104 );
			$result = $this->resolver->filter_already_received( $ids, 42 );

			$this->assertSame( $ids, $result, 'When message tracking has no records, all recipient IDs must be returned.' );
		} finally {
			$wpdb = $original_wpdb;
		}
	}

	/**
	 * Tests filter_already_received filters out recipient IDs present in tracking table.
	 */
	public function test_filter_already_received_filters_tracked_ids(): void {
		global $wpdb;
		$original_wpdb = $wpdb ?? null;

		$wpdb_mock = new class() {
			public string $prefix = 'wp_';

			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			public function get_col( string $query ): array {
				return array( 102, 104 );
			}
		};

		$wpdb = $wpdb_mock;

		try {
			$ids    = array( 101, 102, 103, 104 );
			$result = $this->resolver->filter_already_received( $ids, 42 );

			$this->assertSame( array( 101, 103 ), $result );
		} finally {
			$wpdb = $original_wpdb;
		}
	}

	/**
	 * Tests filter_already_sent_emails returns all data when tracking table has no records.
	 */
	public function test_filter_already_sent_emails_returns_all_when_no_records(): void {
		global $wpdb;
		$original_wpdb = $wpdb ?? null;

		$wpdb_mock = new class() {
			public string $prefix = 'wp_';

			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			public function get_results( string $query, string $output = 'OBJECT' ): array {
				return array();
			}
		};

		$wpdb = $wpdb_mock;

		try {
			$email_data = array(
				'user1@example.com' => array(
					'id'        => 101,
					'names'     => array( 'User 1' ),
					'prio'      => 1,
					'raw_email' => 'user1@example.com',
				),
			);

			$result = $this->resolver->filter_already_sent_emails( $email_data, 42 );
			$this->assertSame( $email_data, $result );
		} finally {
			$wpdb = $original_wpdb;
		}
	}

	/**
	 * Tests filter_already_sent_emails filters out emails previously dispatched.
	 */
	public function test_filter_already_sent_emails_filters_out_matching_emails(): void {
		global $wpdb;
		$original_wpdb = $wpdb ?? null;

		$wpdb_mock = new class() {
			public string $prefix = 'wp_';

			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			public function get_results( string $query, string $output = 'OBJECT' ): array {
				return array(
					array(
						'email'          => 'user1@example.com',
						'recipient_name' => 'User 1',
					),
				);
			}
		};

		$wpdb = $wpdb_mock;

		try {
			$email_data = array(
				'user1@example.com' => array(
					'id'        => 101,
					'names'     => array( 'User 1' ),
					'prio'      => 1,
					'raw_email' => 'user1@example.com',
				),
				'user2@example.com' => array(
					'id'        => 102,
					'names'     => array( 'User 2' ),
					'prio'      => 1,
					'raw_email' => 'user2@example.com',
				),
			);

			$result = $this->resolver->filter_already_sent_emails( $email_data, 42 );
			$this->assertCount( 1, $result );
			$this->assertArrayHasKey( 'user2@example.com', $result );
			$this->assertArrayNotHasKey( 'user1@example.com', $result );
		} finally {
			$wpdb = $original_wpdb;
		}
	}
}

