<?php
/**
 * Plugin Name:       DAME - Dossier Administratif des Membres Échiquéens
 * Plugin URI:
 * Description:       Gère une base de données d'adhérents pour un club.
 * Version:           1.6.0
 * Author:            Etienne
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

define( 'DAME_VERSION', '1.6.0' );

/**
 * Handles plugin updates.
 */
function dame_check_for_updates() {
    $current_version = get_option( 'dame_plugin_version', '1.0.0' );
    if ( version_compare( $current_version, DAME_VERSION, '<' ) ) {
        dame_perform_upgrade( $current_version, DAME_VERSION );
    }
}
add_action( 'plugins_loaded', 'dame_check_for_updates' );

/**
 * Perform the upgrade procedures.
 *
 * @param string $old_version The old version number.
 * @param string $new_version The new version number.
 */
function dame_perform_upgrade( $old_version, $new_version ) {
    // In the future, you can add upgrade logic here based on version.
    // Example:
    // if ( version_compare( $old_version, '1.3.0', '<' ) ) {
    //     // Code to migrate data for version 1.3.0
    // }

    // Update the version in the database to the new version.
    update_option( 'dame_plugin_version', $new_version );
}


// Include plugin files
require_once plugin_dir_path( __FILE__ ) . 'includes/roles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/cpt.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/data-lists.php';

if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/metaboxes.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/columns.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';
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
