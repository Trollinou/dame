<?php
/**
 * Unit tests for ISO 8601 datetime normalization with timezones.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Core\Utils;

/**
 * Class IsoDateTimeTest
 */
final class IsoDateTimeTest extends TestCase {

	/**
	 * Tests HelloAsso summer date with +02:00 offset.
	 * 2026-05-08T00:00:00+02:00 must remain on the 8th of May in Europe/Paris.
	 */
	public function test_helloasso_summer_date_with_offset(): void {
		$raw    = '2026-05-08T00:00:00+02:00';
		$result = Utils::normalize_iso_datetime( $raw );

		$this->assertSame( '2026-05-08 00:00:00', $result );
	}

	/**
	 * Tests HelloAsso event with specific hours in summer (+02:00).
	 */
	public function test_helloasso_event_with_time_summer(): void {
		$raw    = '2026-06-15T09:30:00+02:00';
		$result = Utils::normalize_iso_datetime( $raw );

		$this->assertSame( '2026-06-15 09:30:00', $result );
	}

	/**
	 * Tests HelloAsso winter date with +01:00 offset.
	 */
	public function test_helloasso_winter_date_with_offset(): void {
		$raw    = '2026-01-15T14:30:00+01:00';
		$result = Utils::normalize_iso_datetime( $raw );

		$this->assertSame( '2026-01-15 14:30:00', $result );
	}

	/**
	 * Tests UTC date with Z notation.
	 * 2026-05-08T22:00:00Z corresponds to 2026-05-09 00:00:00 in Europe/Paris (UTC+2 in summer).
	 */
	public function test_utc_date_converted_to_local(): void {
		$raw    = '2026-05-08T22:00:00Z';
		$result = Utils::normalize_iso_datetime( $raw );

		$this->assertSame( '2026-05-09 00:00:00', $result );
	}

	/**
	 * Tests date without timezone or time component.
	 */
	public function test_simple_date_without_timezone(): void {
		$raw    = '2026-05-08';
		$result = Utils::normalize_iso_datetime( $raw );

		$this->assertSame( '2026-05-08 00:00:00', $result );
	}

	/**
	 * Tests date with time but without timezone.
	 */
	public function test_datetime_without_timezone(): void {
		$raw    = '2026-05-08 14:15:00';
		$result = Utils::normalize_iso_datetime( $raw );

		$this->assertSame( '2026-05-08 14:15:00', $result );
	}

	/**
	 * Tests empty or invalid inputs.
	 */
	public function test_invalid_and_empty_inputs(): void {
		$this->assertNull( Utils::normalize_iso_datetime( null ) );
		$this->assertNull( Utils::normalize_iso_datetime( '' ) );
		$this->assertNull( Utils::normalize_iso_datetime( '   ' ) );
		$this->assertNull( Utils::normalize_iso_datetime( 'not-a-date' ) );
	}
}

