<?php
/**
 * Main Plugin Class.
 *
 * @package DAME
 */

namespace DAME\Core;

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
		// TODO: Load Admin modules.
		// TODO: Load Public modules.
		// TODO: Load CPTs.
		// TODO: Load Services.
	}
}
