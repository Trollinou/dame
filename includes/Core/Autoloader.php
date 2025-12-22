<?php
/**
 * Autoloader for the DAME plugin.
 *
 * @package DAME
 */

namespace DAME\Core;

/**
 * Registers the SPL autoloader.
 */
class Autoloader {

	/**
	 * Registers the autoloader.
	 */
	public static function register() {
		spl_autoload_register( function ( $class ) {
			// Project-specific namespace prefix.
			$prefix = 'DAME\\';

			// Base directory for the namespace prefix.
			// __DIR__ is includes/Core, so we go up one level to includes/.
			$base_dir = plugin_dir_path( __DIR__ );

			// Does the class use the namespace prefix?
			$len = strlen( $prefix );
			if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				// No, move to the next registered autoloader.
				return;
			}

			// Get the relative class name.
			$relative_class = substr( $class, $len );

			// Replace the namespace prefix with the base directory, replace namespace
			// separators with directory separators in the relative class name, append
			// with .php.
			$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			// If the file exists, require it.
			if ( file_exists( $file ) ) {
				require $file;
			}
		} );
	}
}
