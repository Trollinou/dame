<?php
/**
 * Admin Assets Manager.
 *
 * @package DAME
 */

namespace DAME\Admin;

/**
 * Class Assets
 */
class Assets {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen || 'adherent' !== $screen->post_type ) {
			return;
		}

		// Adherent Admin JS
		wp_enqueue_script(
			'dame-admin-adherent',
			DAME_URL . 'assets/js/admin-adherent.js',
			[],
			DAME_VERSION,
			true
		);

		// Adherent Admin CSS
		wp_enqueue_style(
			'dame-admin-adherent-css',
			DAME_URL . 'assets/css/admin-adherent.css',
			[],
			DAME_VERSION
		);

		// Get Association Coordinates from options
		$options = get_option( 'dame_options', [] );
		$assoc_latitude  = isset( $options['assoc_latitude'] ) ? $options['assoc_latitude'] : '';
		$assoc_longitude = isset( $options['assoc_longitude'] ) ? $options['assoc_longitude'] : '';

		wp_localize_script(
			'dame-admin-adherent',
			'dame_admin_data',
			[
				'assoc_latitude'  => $assoc_latitude,
				'assoc_longitude' => $assoc_longitude,
			]
		);
	}
}
