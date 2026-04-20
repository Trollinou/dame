<?php
/**
 * Frontend Assets Management.
 *
 * @package DAME
 */

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
	public function enqueue_styles_scripts() {
		// Enqueue the public-facing stylesheet.
		wp_enqueue_style(
			'dame-public-styles',
			\DAME_PLUGIN_URL . 'assets/css/public-styles.css',
			array(),
			\DAME_VERSION
		);

		// Enqueue the single event script on single event pages for the GPS button functionality.
		if ( is_singular( 'dame_agenda' ) ) {
			wp_enqueue_script(
				'dame-public-single-event',
				\DAME_PLUGIN_URL . 'assets/js/public-single-event.js',
				array( 'jquery' ),
				\DAME_VERSION,
				true
			);
		}
	}
}
