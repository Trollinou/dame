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
	public function init(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
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

		// Détection des écrans autorisés : Adhérents, Pré-inscriptions, Réglages et Contacts
		$is_adherent_cpt        = 'adherent' === $screen->post_type;
		$is_pre_inscription_cpt = 'dame_pre_inscription' === $screen->post_type;
		$is_contact_cpt         = 'dame_contact' === $screen->post_type; // Nouveau CPT Contact
		$is_settings_page       = $screen->id && strpos( $screen->id, 'dame-settings' ) !== false;

		// Sortie prématurée si nous ne sommes pas sur un écran géré par le plugin
		if ( ! $is_adherent_cpt && ! $is_settings_page && ! $is_pre_inscription_cpt && ! $is_contact_cpt ) {
			return;
		}

		// --- Shared Assets (Common JS & CSS) ---

		// Register Common JS
		wp_register_script(
			'dame-admin-common',
			\DAME_PLUGIN_URL . 'assets/js/admin-common.js',
			[],
			\DAME_VERSION,
			true
		);

		// Enqueue Common CSS (Autocomplete styles)
		wp_enqueue_style(
			'dame-admin-common-css',
			\DAME_PLUGIN_URL . 'assets/css/admin-common.css', // Using existing file as common CSS
			[],
			\DAME_VERSION
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
				\DAME_PLUGIN_URL . 'assets/js/admin-adherent.js',
				[ 'dame-admin-common' ], // Depends on common
				\DAME_VERSION,
				true
			);
		}

		if ( $is_pre_inscription_cpt ) {
			wp_enqueue_style(
				'dame-admin-styles',
				\DAME_PLUGIN_URL . 'assets/css/admin-styles.css',
				[],
				\DAME_VERSION
			);
		}
	}
}
