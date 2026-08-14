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
