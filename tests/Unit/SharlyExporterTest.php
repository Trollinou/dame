<?php
/**
 * Tests for SharlyExporter.
 *
 * @package DAME\Tests\Unit
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Services\HelloAsso\SharlyExporter;

/**
 * Class SharlyExporterTest
 */
class SharlyExporterTest extends TestCase {

	/**
	 * Test CSV generation format.
	 */
	public function test_generate_csv(): void {
		$items = array(
			array(
				'participant_first_name' => 'Paul',
				'participant_last_name'  => 'Morphy',
				'participant_birth_date' => '2012-06-22',
				'license_number'         => 'B12345',
				'fide_id'                => '654321',
				'elo'                    => 1850,
				'club_name'              => 'Échecs Club',
				'tier_name'              => 'Open A',
				'checked_in'             => 1,
			),
		);

		$csv = SharlyExporter::generate_csv( $items );

		$this->assertStringContainsString( "\xEF\xBB\xBF", $csv );
		$this->assertStringContainsString( 'Nom;Prenom;Sexe;Date_Naissance;Code_FFE;Code_FIDE;Elo;Club;Categorie;Tarif;Pointe_Present', $csv );
		$this->assertStringContainsString( 'MORPHY;Paul;M;22/06/2012;B12345;654321;1850;"Échecs Club";;"Open A";OUI', $csv );
	}
}
