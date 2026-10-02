<?php
/**
 * Admin Assets Manager.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin;

use DAME\Services\Data_Provider;

/**
 * Class Assets
 */
class Assets {

	/**
	 * Initialize the class.
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		// Enqueue Command Palette integration across WP Admin (WordPress 6.3+ / 7.x).
		wp_enqueue_script(
			'dame-admin-command-palette',
			\DAME_PLUGIN_URL . 'assets/js/admin-command-palette.js',
			array( 'wp-commands', 'wp-data' ),
			\DAME_VERSION,
			true
		);

		wp_localize_script(
			'dame-admin-command-palette',
			'dameAdminCommands',
			array(
				'adminUrl' => admin_url(),
			)
		);

		// Sortie prématurée si nous ne sommes pas sur un écran géré par le plugin pour les styles/scripts lourds.
		if ( ! $is_adherent_cpt && ! $is_settings_page && ! $is_pre_inscription_cpt && ! $is_contact_cpt ) {
			return;
		}

		// --- Shared Assets (Common JS & CSS) ---.

		// Register Common JS.
		wp_register_script(
			'dame-admin-common',
			\DAME_PLUGIN_URL . 'assets/js/admin-common.js',
			array(),
			\DAME_VERSION,
			true
		);

		// Enqueue Common CSS (Autocomplete styles).
		wp_enqueue_style(
			'dame-admin-common-css',
			\DAME_PLUGIN_URL . 'assets/css/admin-common.css', // Using existing file as common CSS.
			array(),
			\DAME_VERSION
		);

		// Localize Common Data.
		$options         = get_option( 'dame_options', array() );
		$assoc_latitude  = isset( $options['assoc_latitude'] ) ? $options['assoc_latitude'] : '';
		$assoc_longitude = isset( $options['assoc_longitude'] ) ? $options['assoc_longitude'] : '';

		wp_localize_script(
			'dame-admin-common',
			'dame_admin_data',
			array(
				'assoc_latitude'  => $assoc_latitude,
				'assoc_longitude' => $assoc_longitude,
				'dept_region_map' => Data_Provider::get_department_region_mapping(),
			)
		);

		wp_enqueue_script( 'dame-admin-common' );

		// --- Adherent CPT Specific ---.

		if ( $is_adherent_cpt ) {
			wp_enqueue_script(
				'dame-admin-adherent',
				\DAME_PLUGIN_URL . 'assets/js/admin-adherent.js',
				array( 'dame-admin-common' ), // Depends on common.
				\DAME_VERSION,
				true
			);
		}

		if ( $is_pre_inscription_cpt ) {
			wp_enqueue_style(
				'dame-admin-styles',
				\DAME_PLUGIN_URL . 'assets/css/admin-styles.css',
				array(),
				\DAME_VERSION
			);
		}
	}
}
