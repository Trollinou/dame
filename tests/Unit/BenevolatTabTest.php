<?php
/**
 * Unit tests for Benevolat settings tab.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Admin\Settings\Tabs\Benevolat;

/**
 * Class BenevolatTabTest
 */
final class BenevolatTabTest extends TestCase {

	/**
	 * Test sanitization of benevolat settings tab inputs.
	 */
	public function test_sanitize(): void {
		$tab = new Benevolat();

		$existing = array(
			'existing_key' => 'value',
		);

		$input = array(
			'benevolat_auto_publish'         => '1',
			'benevolat_default_category'     => '12',
			'benevolat_default_thumbnail_id' => '345',
		);

		$sanitized = $tab->sanitize( $input, $existing );

		$this->assertSame( 1, $sanitized['benevolat_auto_publish'] );
		$this->assertSame( 12, $sanitized['benevolat_default_category'] );
		$this->assertSame( 345, $sanitized['benevolat_default_thumbnail_id'] );
		$this->assertSame( 'value', $sanitized['existing_key'] );

		// Test unchecked auto-publish.
		$input_unchecked = array();
		$sanitized2      = $tab->sanitize( $input_unchecked, $sanitized );

		$this->assertSame( 0, $sanitized2['benevolat_auto_publish'] );
	}
}
