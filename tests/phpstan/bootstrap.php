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
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
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

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( string $path ): string {
		return rtrim( $path, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( string $path ): string {
		return rtrim( $path, '/\\' );
	}
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir(): array {
		return array( 'basedir' => sys_get_temp_dir() . '/wp-content/uploads' );
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( string $dir ): bool {
		return true;
	}
}

