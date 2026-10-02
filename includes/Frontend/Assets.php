<?php
/**
 * Frontend Assets Management.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Frontend;

/**
 * Handles asset enqueuing for the DAME plugin on the frontend.
 */
class Assets {

	/**
	 * Initializes the frontend assets class.
	 */
	public function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
	}

	/**
	 * Enqueues front-end scripts and styles.
	 */
	public function enqueue_styles_scripts(): void {
		// Enqueue the public-facing stylesheet.
		wp_enqueue_style(
			'dame-public-styles',
			\DAME_PLUGIN_URL . 'assets/css/public-styles.css',
			array(),
			\DAME_VERSION
		);

		// Register the agenda stylesheet for block and template dependencies.
		wp_register_style(
			'dame-public-agenda',
			\DAME_PLUGIN_URL . 'assets/css/public-agenda.css',
			array( 'dame-public-styles' ),
			\DAME_VERSION
		);

		// Register the modern Interactivity API Script Modules (WordPress 6.5+ / 7.x).
		if ( function_exists( 'wp_register_script_module' ) ) {
			wp_register_script_module(
				'dame/agenda',
				\DAME_PLUGIN_URL . 'assets/js/modules/agenda-store.js',
				array( '@wordpress/interactivity' ),
				\DAME_VERSION
			);

			wp_register_script_module(
				'dame/newsletter',
				\DAME_PLUGIN_URL . 'assets/js/modules/newsletter-store.js',
				array( '@wordpress/interactivity' ),
				\DAME_VERSION
			);

			wp_register_script_module(
				'dame/benevolat',
				\DAME_PLUGIN_URL . 'assets/js/modules/benevolat-store.js',
				array( '@wordpress/interactivity' ),
				\DAME_VERSION
			);

			wp_register_script_module(
				'dame/contact',
				\DAME_PLUGIN_URL . 'assets/js/modules/contact-store.js',
				array( '@wordpress/interactivity' ),
				\DAME_VERSION
			);

			wp_register_script_module(
				'dame/registration',
				\DAME_PLUGIN_URL . 'assets/js/modules/registration-store.js',
				array( '@wordpress/interactivity' ),
				\DAME_VERSION
			);
		}

		// Enqueue the single event script on single event pages for the GPS button functionality.
		if ( is_singular( 'dame_agenda' ) ) {
			wp_enqueue_script(
				'dame-public-single-event',
				\DAME_PLUGIN_URL . 'assets/js/public-single-event.js',
				array(),
				\DAME_VERSION,
				true
			);
		}
	}
}
