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

		$is_adherent_cpt        = 'adherent' === $screen->post_type || 'dame_adherent' === $screen->post_type;
		$is_pre_inscription_cpt = 'dame_pre_inscription' === $screen->post_type;
		$is_contact_cpt         = 'dame_contact' === $screen->post_type;
		$is_agenda_cpt          = 'dame_agenda' === $screen->post_type;
		$is_benevolat_cpt       = 'benevolat' === $screen->post_type || 'dame_benevolat' === $screen->post_type;
		$is_message_cpt         = 'dame_message' === $screen->post_type;
		$is_settings_page       = false !== strpos( (string) $screen->id, 'dame' );

		// Sortie prématurée si nous ne sommes pas sur un écran géré par le plugin pour les styles/scripts lourds.
		if ( ! $is_adherent_cpt && ! $is_settings_page && ! $is_pre_inscription_cpt && ! $is_contact_cpt && ! $is_agenda_cpt && ! $is_benevolat_cpt && ! $is_message_cpt ) {
			return;
		}

		// --- Shared Assets (Common JS & CSS) ---.

		// Enqueue Admin Styles across all DAME screens.
		wp_enqueue_style(
			'dame-admin-styles',
			\DAME_PLUGIN_URL . 'assets/css/admin-styles.css',
			array(),
			\DAME_VERSION
		);

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
			\DAME_PLUGIN_URL . 'assets/css/admin-common.css',
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

		if ( $is_adherent_cpt || 'dame-hidden_page_dame-view-adherent' === $screen->id ) {
			wp_enqueue_script(
				'dame-admin-adherent',
				\DAME_PLUGIN_URL . 'assets/js/admin-adherent.js',
				array( 'dame-admin-common' ), // Depends on common.
				\DAME_VERSION,
				true
			);

			wp_enqueue_script(
				'dame-admin-attestation',
				\DAME_PLUGIN_URL . 'assets/js/admin-attestation.js',
				array(),
				\DAME_VERSION,
				true
			);

			wp_localize_script(
				'dame-admin-attestation',
				'dameAttestationData',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'dame_attestation_action' ),
				)
			);
		}
	}
}
