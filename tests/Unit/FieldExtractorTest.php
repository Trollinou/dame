<?php
/**
 * Tests for FieldExtractor.
 *
 * @package DAME\Tests\Unit
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Services\HelloAsso\FieldExtractor;

/**
 * Class FieldExtractorTest
 */
class FieldExtractorTest extends TestCase {

	/**
	 * Test extraction of chess fields from custom fields array.
	 */
	public function test_extract_chess_data(): void {
		$custom_fields = array(
			array(
				'name'   => 'Numéro de licence FFE',
				'answer' => 'A12345',
			),
			array(
				'name'   => 'Votre Club d\'échecs',
				'answer' => 'Échiquier Castelbriantais',
			),
			array(
				'name'   => 'Classement Elo estimé ou officiel',
				'answer' => '1650',
			),
			array(
				'name'   => 'Téléphone en cas d\'urgence',
				'answer' => '06 12 34 56 78',
			),
			array(
				'name'   => 'Date de naissance',
				'answer' => '15/04/2010',
			),
		);

		$extracted = FieldExtractor::extract_chess_data( $custom_fields );

		$this->assertSame( 'A12345', $extracted['license_number'] );
		$this->assertSame( 'Échiquier Castelbriantais', $extracted['club_name'] );
		$this->assertSame( 1650, $extracted['elo'] );
		$this->assertSame( '06 12 34 56 78', $extracted['phone'] );
		$this->assertSame( '2010-04-15', $extracted['birth_date'] );
	}

	/**
	 * Test extraction with empty or null input.
	 */
	public function test_extract_with_empty_input(): void {
		$extracted = FieldExtractor::extract_chess_data( null );
		$this->assertSame( '', $extracted['license_number'] );
		$this->assertNull( $extracted['elo'] );
		$this->assertEmpty( $extracted['extracted_fields'] );
	}
}
