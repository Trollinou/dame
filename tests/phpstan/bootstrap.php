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

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'get_post_type' ) ) {
	function get_post_type( mixed $post = null ): string|false {
		return false;
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

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

// Global WordPress Options & REST mocks for unit tests.
global $dame_test_options;
$dame_test_options = array();

global $dame_test_transients;
$dame_test_transients = array();

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( string $transient ): mixed {
		global $dame_test_transients;
		return $dame_test_transients[ $transient ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( string $transient, mixed $value, int $expiration = 0 ): bool {
		global $dame_test_transients;
		$dame_test_transients[ $transient ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( string $transient ): bool {
		global $dame_test_transients;
		unset( $dame_test_transients[ $transient ] );
		return true;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, mixed $default_value = false ): mixed {
		global $dame_test_options;
		return $dame_test_options[ $option ] ?? $default_value;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option, mixed $value, mixed $autoload = null ): bool {
		global $dame_test_options;
		$dame_test_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( string $path = '' ): string {
		return 'https://example.com/wp-json/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( int $length = 12, bool $special_chars = true, bool $extra_special_chars = false ): string {
		return substr( bin2hex( random_bytes( (int) ceil( $length / 2 ) ) ), 0, $length );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( string $url, int $component = -1 ): mixed {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( string $format, ?int $timestamp = null, ?DateTimeZone $timezone = null ): string {
		$timestamp = $timestamp ?? time();
		return date( $format, $timestamp );
	}
}


