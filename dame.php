<?php
/**
 * Plugin Name:       DAME - Dossier Administratif des Membres Échiquéens
 * Plugin URI:
 * Description:       Gère une base de données d'adhérents pour un club.
 * Version:           3.4.5
 * Requires at least: 6.8
 * Requires PHP:      8.2
 * Author:            Etienne Gagnon
 * Author URI:
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dame
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define Constants.
define( 'DAME_VERSION', '3.4.5' );
define( 'DAME_PATH', plugin_dir_path( __FILE__ ) );
define( 'DAME_URL', plugin_dir_url( __FILE__ ) );

// Require Autoloader.
require_once DAME_PATH . 'includes/Core/Autoloader.php';

// Register Autoloader.
DAME\Core\Autoloader::register();

// Initialize the Plugin.
function dame_init_plugin() {
	$plugin = DAME\Core\Plugin::get_instance();
	$plugin->run();
}
add_action( 'plugins_loaded', 'dame_init_plugin' );
