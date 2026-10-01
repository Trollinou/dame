<?php
/**
 * Notice Trait for Backup Services.
 *
 * @package DAME\Services\Backup
 */

declare(strict_types=1);

namespace DAME\Services\Backup;

/**
 * Trait providing notice management for backup and restore services.
 */
trait NoticeTrait {

	/**
	 * Adds an admin notice to be displayed on the next page load.
	 *
	 * @param string $message The notice message.
	 * @param string $type The notice type (success, error, warning, etc.).
	 */
	protected function add_admin_notice( string $message, string $type = 'success' ): void {
		set_transient(
			'dame_import_export_notice',
			array(
				'message' => $message,
				'type'    => $type,
			),
			30
		);
	}
}
