<?php
/**
 * Unit tests for Document_Storage and EG Backup integration.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DAME\Services\Document_Storage;

/**
 * Class DocumentStorageTest
 */
final class DocumentStorageTest extends TestCase {

	/**
	 * Test that Document_Storage registers its location adhering to EG Backup HOOKS_API.md.
	 */
	public function test_register_backup_location(): void {
		$initial_locations = array(
			'other_plugin' => array(
				'id' => 'other_plugin',
			),
		);

		$locations = Document_Storage::register_backup_location( $initial_locations );

		// 1. Initial locations must be preserved.
		$this->assertArrayHasKey( 'other_plugin', $locations );

		// 2. DAME documents location must be present.
		$this->assertArrayHasKey( 'dame_documents', $locations );

		$dame_loc = $locations['dame_documents'];

		// 3. Data contract checks from HOOKS_API.md.
		$this->assertSame( 'dame_documents', $dame_loc['id'] );
		$this->assertSame( 'DAME', $dame_loc['plugin_name'] );
		$this->assertSame( 'documents', $dame_loc['category'] );
		$this->assertTrue( $dame_loc['recommended'] );
		$this->assertNotEmpty( $dame_loc['label'] );
		$this->assertNotEmpty( $dame_loc['description'] );
		$this->assertStringContainsString( 'dame-documents', $dame_loc['path'] );
		$this->assertIsArray( $dame_loc['exclude'] );
		$this->assertContains( '*.tmp', $dame_loc['exclude'] );
		$this->assertContains( 'temp', $dame_loc['exclude'] );
	}
}
