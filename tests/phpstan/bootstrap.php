<?php
/**
 * PHPStan Bootstrap file.
 *
 * Defines constants and environment variables needed for analysis.
 */

define( 'DAME_PLUGIN_URL', 'https://example.com/wp-content/plugins/dame/' );
define( 'DAME_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
if ( ! defined( 'DAME_VERSION' ) ) {
	define( 'DAME_VERSION', '5.0.0' );
}
define( 'COOKIEPATH', '/' );

if ( file_exists( dirname( __DIR__, 2 ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
}

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix   = 'DAME\\';
		$base_dir = dirname( __DIR__, 2 ) . '/includes/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class_name, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);
