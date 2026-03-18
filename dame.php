<?php
/**
 * Plugin Name:       DAME - Dossier Administratif des Membres Échiquéens
 * Plugin URI:        https://github.com/trollinou/dame
 * Description:       Gère une base de données d'adhérents pour un club.
 * Version:           3.4.5
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Etienne Gagnon
 * Text Domain:       dame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Définition des Constantes (CRITIQUE pour le fonctionnement)
if ( ! defined( 'DAME_VERSION' ) ) {
	define( 'DAME_VERSION', '3.4.5' );
}

if ( ! defined( 'DAME_PLUGIN_DIR' ) ) {
	define( 'DAME_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DAME_PLUGIN_URL' ) ) {
	define( 'DAME_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// 2. Autoloader
require_once DAME_PLUGIN_DIR . 'includes/Core/Autoloader.php';
DAME\Core\Autoloader::register();

// 3. Initialisation du Plugin
if ( class_exists( 'DAME\Core\Plugin' ) ) {
	$dame = DAME\Core\Plugin::get_instance();
	$dame->run();
}

/**
 * Fonction d'activation (pour les règles de réécriture)
 */
function dame_activate_plugin() {
    // Déclenche l'écriture des règles iCal / Sondage
    if ( class_exists( 'DAME\Services\ICalFeed' ) ) {
        $ical = new DAME\Services\ICalFeed();
        $ical->register_feed();
    }
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dame_activate_plugin' );
