<?php
/**
 * Unit tests for Data_Provider regional and departmental mappings.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Services\Data_Provider;

/**
 * Class DataProviderTest.
 */
final class DataProviderTest extends TestCase {

	/**
	 * Test that regions map accurately to French departments.
	 */
	public function test_get_departments_by_region(): void {
		$bfc_depts = Data_Provider::get_departments_by_region( 'BFC' );
		$this->assertContains( '21', $bfc_depts );
		$this->assertContains( '25', $bfc_depts );
		$this->assertContains( '39', $bfc_depts );
		$this->assertNotContains( '75', $bfc_depts );

		$idf_depts = Data_Provider::get_departments_by_region( 'IDF' );
		$this->assertContains( '75', $idf_depts );
		$this->assertContains( '92', $idf_depts );
		$this->assertNotContains( '21', $idf_depts );
	}
}

