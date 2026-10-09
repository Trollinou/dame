<?php
/**
 * Unit tests for amount to French words conversion.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Core\Utils;

/**
 * Class UtilsAmountTest
 */
final class UtilsAmountTest extends TestCase {

	/**
	 * Tests standard amounts for club memberships.
	 */
	public function test_standard_club_amounts(): void {
		$this->assertSame( 'cent quarante euros', Utils::number_to_french_words( 140.0 ) );
		$this->assertSame( 'cent trente euros', Utils::number_to_french_words( 130.0 ) );
		$this->assertSame( 'cent soixante euros', Utils::number_to_french_words( 160.0 ) );
		$this->assertSame( 'cent soixante-dix euros', Utils::number_to_french_words( 170.0 ) );
		$this->assertSame( 'soixante-dix euros', Utils::number_to_french_words( 70.0 ) );
	}

	/**
	 * Tests edge cases: zero, singular, decimals.
	 */
	public function test_edge_cases(): void {
		$this->assertSame( 'zéro euro', Utils::number_to_french_words( 0.0 ) );
		$this->assertSame( 'un euro', Utils::number_to_french_words( 1.0 ) );
		$this->assertSame( 'deux euros', Utils::number_to_french_words( 2.0 ) );
		$this->assertSame( 'un euro et un centime', Utils::number_to_french_words( 1.01 ) );
		$this->assertSame( 'un euro et cinquante centimes', Utils::number_to_french_words( 1.50 ) );
		$this->assertSame( 'cinquante centimes', Utils::number_to_french_words( 0.50 ) );
		$this->assertSame( 'cent vingt-cinq euros et cinquante centimes', Utils::number_to_french_words( 125.50 ) );
	}

	/**
	 * Tests complex numbers: 80, 81, 71, 91, hundreds.
	 */
	public function test_complex_french_numbers(): void {
		$this->assertSame( 'soixante et onze euros', Utils::number_to_french_words( 71.0 ) );
		$this->assertSame( 'quatre-vingts euros', Utils::number_to_french_words( 80.0 ) );
		$this->assertSame( 'quatre-vingt-un euros', Utils::number_to_french_words( 81.0 ) );
		$this->assertSame( 'quatre-vingt-onze euros', Utils::number_to_french_words( 91.0 ) );
		$this->assertSame( 'cent euros', Utils::number_to_french_words( 100.0 ) );
		$this->assertSame( 'deux cents euros', Utils::number_to_french_words( 200.0 ) );
		$this->assertSame( 'deux cent cinq euros', Utils::number_to_french_words( 205.0 ) );
		$this->assertSame( 'mille euros', Utils::number_to_french_words( 1000.0 ) );
		$this->assertSame( 'deux mille euros', Utils::number_to_french_words( 2000.0 ) );
	}
}
