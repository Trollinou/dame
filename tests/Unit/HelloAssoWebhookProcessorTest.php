<?php
/**
 * Tests for HelloAsso WebhookProcessor item filtering.
 *
 * @package DAME\Tests\Unit
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Services\HelloAsso\WebhookProcessor;
use DAME\Repositories\TicketingRepository;

/**
 * Class HelloAssoWebhookProcessorTest
 */
class HelloAssoWebhookProcessorTest extends TestCase {

	/**
	 * Test that extra donations and tips on an Event campaign are filtered out and cleaned up.
	 */
	public function test_extra_donations_and_tips_are_filtered_out(): void {
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

			public function get_var( string $query ): ?string {
				return null;
			}

			public function get_col( string $query ): array {
				return array();
			}

			public function esc_like( string $text ): string {
				return addcslashes( $text, '_%\\' );
			}
		};

		$wpdb = $wpdb_mock;

		try {
			$mock_repo = $this->createMock( TicketingRepository::class );

			// Expect campaign lookup or creation.
			$mock_repo->method( 'get_or_create_campaign' )
				->willReturn( 42 );

			// Expect save_item to be called exactly once for the Registration item (16 €).
			$mock_repo->expects( $this->once() )
				->method( 'save_item' )
				->with(
					$this->callback(
						function ( array $item_data ): bool {
							return 1600 === $item_data['amount_cents']
								&& 'Tarif Adulte' === $item_data['tier_name']
								&& 'ROPIOT' === $item_data['participant_last_name'];
						}
					)
				)
				->willReturn( 101 );

			// Expect delete_item_by_ha_id to be called for the 4 € donation item ID 9999.
			$mock_repo->expects( $this->once() )
				->method( 'delete_item_by_ha_id' )
				->with( 9999 )
				->willReturn( true );

			$processor = new WebhookProcessor( $mock_repo );

			$payload = array(
				'eventType' => 'Order',
				'data'      => array(
					'formSlug' => 'open-echecs-2026',
					'formType' => 'Event',
					'formName' => 'Open d\'échecs 2026',
					'payer'    => array(
						'firstName' => 'Vincent',
						'lastName'  => 'ROPIOT',
						'email'     => 'ropiot.vincent21@gmail.com',
					),
					'items'    => array(
						// Item 1: Real ticket.
						array(
							'id'     => 1001,
							'type'   => 'Registration',
							'name'   => 'Tarif Adulte',
							'amount' => 1600,
							'user'   => array(
								'firstName' => 'Vincent',
								'lastName'  => 'ROPIOT',
								'email'     => 'ropiot.vincent21@gmail.com',
							),
						),
						// Item 2: Extra 4 € donation on the form.
						array(
							'id'     => 9999,
							'type'   => 'Donation',
							'amount' => 400,
							'name'   => '',
							'user'   => null,
						),
					),
				),
			);

			$result = $processor->process( $payload );

			$this->assertTrue( $result['success'] );
			$this->assertSame( 1, $result['processed_items'] );
		} finally {
			$wpdb = $original_wpdb;
		}
	}
}
