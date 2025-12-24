<?php
/**
 * Admin Assets Manager.
 *
 * @package DAME
 */

namespace DAME\Admin;
use DAME\Services\Data_Provider;

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

		if ( ! $screen ) {
			return;
		}

		// Check if we are on the Adherent CPT, Pre-inscription CPT or Settings Page
		$is_adherent_cpt        = 'adherent' === $screen->post_type;
		$is_pre_inscription_cpt = 'dame_pre_inscription' === $screen->post_type;
		$is_settings_page       = 'settings_page_dame-settings' === $screen->id || 'toplevel_page_dame-settings' === $screen->id;

		if ( ! $is_adherent_cpt && ! $is_settings_page && ! $is_pre_inscription_cpt ) {
			return;
		}

		// --- Shared Assets (Common JS & CSS) ---

		// Register Common JS
		wp_register_script(
			'dame-admin-common',
			DAME_URL . 'assets/js/admin-common.js',
			[],
			DAME_VERSION,
			true
		);

		// Enqueue Common CSS (Autocomplete styles)
		wp_enqueue_style(
			'dame-admin-common-css',
			DAME_URL . 'assets/css/admin-adherent.css', // Using existing file as common CSS
			[],
			DAME_VERSION
		);

		// Localize Common Data
		$options = get_option( 'dame_options', [] );
		$assoc_latitude  = isset( $options['assoc_latitude'] ) ? $options['assoc_latitude'] : '';
		$assoc_longitude = isset( $options['assoc_longitude'] ) ? $options['assoc_longitude'] : '';

		wp_localize_script(
			'dame-admin-common',
			'dame_admin_data',
			[
				'assoc_latitude'  => $assoc_latitude,
				'assoc_longitude' => $assoc_longitude,
				'dept_region_map' => Data_Provider::get_department_region_mapping(),
			]
		);

		wp_enqueue_script( 'dame-admin-common' );


		// --- Adherent CPT Specific ---

		if ( $is_adherent_cpt ) {
			wp_enqueue_script(
				'dame-admin-adherent',
				DAME_URL . 'assets/js/admin-adherent.js',
				[ 'dame-admin-common' ], // Depends on common
				DAME_VERSION,
				true
			);
		}

		if ( $is_pre_inscription_cpt ) {
			wp_enqueue_style(
				'dame-admin-dame-css',
				DAME_URL . 'assets/css/admin-dame.css',
				[],
				DAME_VERSION
			);
		}
	}
}
