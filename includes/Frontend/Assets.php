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
	public function enqueue_styles_scripts(): void {
		// Enqueue the public-facing stylesheet.
		wp_enqueue_style(
			'dame-public-styles',
			\DAME_PLUGIN_URL . 'assets/css/public-styles.css',
			array(),
			\DAME_VERSION
		);

		// Enqueue PWA Installer Styles & Scripts
		wp_enqueue_style(
			'dame-public-pwa-installer',
			\DAME_PLUGIN_URL . 'assets/css/public-pwa-installer.css',
			array(),
			\DAME_VERSION
		);

		wp_enqueue_script(
			'dame-public-pwa-installer',
			\DAME_PLUGIN_URL . 'assets/js/public-pwa-installer.js',
			array(),
			\DAME_VERSION,
			true
		);

		wp_localize_script(
			'dame-public-pwa-installer',
			'damePwaInstaller',
			array(
				'swUrl'    => \DAME_PLUGIN_URL . 'pwa/dist/sw.js',
				'pwaScope' => \DAME_PLUGIN_URL . 'pwa/dist/',
				'siteName' => get_bloginfo( 'name' ),
				'siteIcon' => get_site_icon_url( 192 ) ?: \DAME_PLUGIN_URL . 'pwa/dist/assets/icon/icon-192.png',
				'pwaUrl'   => home_url( '/pwa' ),
			)
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
