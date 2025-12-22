<?php
/**
 * Main Plugin Class.
 *
 * @package DAME
 */

namespace DAME\Core;

use DAME\CPT\Adherent;
use DAME\Metaboxes\Adherent\Manager as AdherentMetaboxManager;

/**
 * The core plugin class.
 */
class Plugin {

	/**
	 * The unique instance of the plugin.
	 *
	 * @var Plugin
	 */
	private static $instance;

	/**
	 * Gets the unique instance of the plugin.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton.
	 */
	private function __construct() {
		// Initialization code here.
	}

	/**
	 * Starts the plugin execution.
	 */
	public function run() {
		// Load legacy helpers (temporary dependency).
		if ( defined( 'DAME_PATH' ) ) {
			require_once DAME_PATH . 'includes/data-lists.php';
			require_once DAME_PATH . 'includes/utils.php';
			require_once DAME_PATH . 'includes/taxonomies.php';
			require_once DAME_PATH . 'includes/assets.php';
		}

		// Initialize CPTs.
		$adherent_cpt = new Adherent();
		$adherent_cpt->init();

		// Initialize Metaboxes.
		if ( is_admin() ) {
			$adherent_metaboxes = new AdherentMetaboxManager();
			$adherent_metaboxes->init();
		}
	}
}
