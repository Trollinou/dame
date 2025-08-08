<?php
/**
 * Plugin Name:       DAME - Dossier Administratif des Membres Échiquéens
 * Plugin URI:
 * Description:       Gère une base de données d'adhérents pour un club.
 * Version:           1.0.0
 * Author:            Jules
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

define( 'DAME_VERSION', '1.0.0' );

// Include plugin files
require_once plugin_dir_path( __FILE__ ) . 'includes/roles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/cpt.php';

if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/metaboxes.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/columns.php';
}


/**
 * Load plugin textdomain.
 */
function dame_load_textdomain() {
    load_plugin_textdomain( 'dame', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'dame_load_textdomain' );

// Register hooks
register_activation_hook( __FILE__, 'dame_add_custom_roles' );
register_deactivation_hook( __FILE__, 'dame_remove_custom_roles' );
