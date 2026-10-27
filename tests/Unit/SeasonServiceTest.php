<?php
/**
 * Tests for Season_Service.
 *
 * @package DAME\Tests\Unit
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Services\Season_Service;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Class SeasonServiceTest
 */
class SeasonServiceTest extends TestCase {

	/**
	 * Test format_season_name.
	 */
	public function test_format_season_name(): void {
		$this->assertSame( 'Saison 2024/2025', Season_Service::format_season_name( 2024, 2025 ) );
		$this->assertSame( 'Saison 2025/2026', Season_Service::format_season_name( 2025, 2026 ) );
	}

	/**
	 * Test parse_season_years with various inputs.
	 */
	public function test_parse_season_years(): void {
		$parsed = Season_Service::parse_season_years( 'Saison 2024/2025' );
		$this->assertNotNull( $parsed );
		$this->assertSame( 2024, $parsed['start'] );
		$this->assertSame( 2025, $parsed['end'] );

		// Whitespace tolerance.
		$parsed_space = Season_Service::parse_season_years( '2023 / 2024' );
		$this->assertNotNull( $parsed_space );
		$this->assertSame( 2023, $parsed_space['start'] );
		$this->assertSame( 2024, $parsed_space['end'] );

		// Invalid string.
		$this->assertNull( Season_Service::parse_season_years( 'Saison antérieure' ) );
		$this->assertNull( Season_Service::parse_season_years( '' ) );
	}

	/**
	 * Test sports season deadline (August 31st 23:59:59).
	 */
	public function test_get_sports_season_deadline(): void {
		// Event starting in September 2024 -> deadline is Aug 31st 2025.
		$date1    = new DateTimeImmutable( '2024-09-01 10:00:00', new DateTimeZone( 'UTC' ) );
		$deadline = Season_Service::get_sports_season_deadline( $date1 );
		$this->assertSame( '2025-08-31 23:59:59', $deadline->format( 'Y-m-d H:i:s' ) );

		// Event starting in January 2025 -> deadline is Aug 31st 2025.
		$date2    = new DateTimeImmutable( '2025-01-15 14:00:00', new DateTimeZone( 'UTC' ) );
		$deadline = Season_Service::get_sports_season_deadline( $date2 );
		$this->assertSame( '2025-08-31 23:59:59', $deadline->format( 'Y-m-d H:i:s' ) );

		// Event starting on August 31st 2025 -> deadline is Aug 31st 2025.
		$date3    = new DateTimeImmutable( '2025-08-31 09:00:00', new DateTimeZone( 'UTC' ) );
		$deadline = Season_Service::get_sports_season_deadline( $date3 );
		$this->assertSame( '2025-08-31 23:59:59', $deadline->format( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Test membership overlap check (July 1st to Oct 31st).
	 */
	public function test_is_in_membership_overlap(): void {
		// In overlap window.
		$this->assertTrue( Season_Service::is_in_membership_overlap( new DateTimeImmutable( '2024-07-01' ) ) );
		$this->assertTrue( Season_Service::is_in_membership_overlap( new DateTimeImmutable( '2024-08-15' ) ) );
		$this->assertTrue( Season_Service::is_in_membership_overlap( new DateTimeImmutable( '2024-09-01' ) ) );
		$this->assertTrue( Season_Service::is_in_membership_overlap( new DateTimeImmutable( '2024-10-31' ) ) );

		// Outside overlap window.
		$this->assertFalse( Season_Service::is_in_membership_overlap( new DateTimeImmutable( '2024-06-30' ) ) );
		$this->assertFalse( Season_Service::is_in_membership_overlap( new DateTimeImmutable( '2024-11-01' ) ) );
		$this->assertFalse( Season_Service::is_in_membership_overlap( new DateTimeImmutable( '2025-01-10' ) ) );
	}

	/**
	 * Test membership expiry date (October 31st N+1).
	 */
	public function test_get_membership_expiry_date(): void {
		$expiry = Season_Service::get_membership_expiry_date( 2024 );
		$this->assertSame( '2025-10-31 23:59:59', $expiry->format( 'Y-m-d H:i:s' ) );
	}
}

