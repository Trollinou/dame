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
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
	}

	/**
	 * Pre-registers admin scripts and styles.
	 */
	public function register_admin_assets(): void {
		wp_register_style(
			'dame-admin-styles',
			\DAME_PLUGIN_URL . 'assets/css/admin-styles.css',
			array(),
			\DAME_VERSION
		);

		wp_register_style(
			'dame-admin-common-css',
			\DAME_PLUGIN_URL . 'assets/css/admin-common.css',
			array(),
			\DAME_VERSION
		);

		wp_register_script(
			'dame-admin-common',
			\DAME_PLUGIN_URL . 'assets/js/admin-common.js',
			array(),
			\DAME_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_register_script(
			'dame-admin-command-palette',
			\DAME_PLUGIN_URL . 'assets/js/admin-command-palette.js',
			array( 'wp-commands', 'wp-data' ),
			\DAME_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_register_script(
			'dame-admin-adherent',
			\DAME_PLUGIN_URL . 'assets/js/admin-adherent.js',
			array( 'dame-admin-common' ),
			\DAME_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_register_script(
			'dame-admin-attestation',
			\DAME_PLUGIN_URL . 'assets/js/admin-attestation.js',
			array(),
			\DAME_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}

	/**
	 * Enqueue scripts and styles conditionally per screen.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$is_adherent_cpt        = 'adherent' === $screen->post_type || 'dame_adherent' === $screen->post_type;
		$is_pre_inscription_cpt = 'dame_pre_inscription' === $screen->post_type;
		$is_contact_cpt         = 'dame_contact' === $screen->post_type;
		$is_agenda_cpt          = 'dame_agenda' === $screen->post_type;
		$is_benevolat_cpt       = 'benevolat' === $screen->post_type || 'dame_benevolat' === $screen->post_type;
		$is_message_cpt         = 'dame_message' === $screen->post_type;
		$is_settings_page       = false !== strpos( (string) $screen->id, 'dame' );

		// Sortie immédiate si nous ne sommes pas sur un écran géré par le plugin.
		if ( ! $is_adherent_cpt && ! $is_settings_page && ! $is_pre_inscription_cpt && ! $is_contact_cpt && ! $is_agenda_cpt && ! $is_benevolat_cpt && ! $is_message_cpt ) {
			return;
		}

		// 1. Command Palette : réservée aux écrans du plugin si l'API wp-commands est présente.
		if ( wp_script_is( 'wp-commands', 'registered' ) ) {
			wp_enqueue_script( 'dame-admin-command-palette' );
			$commands_payload = array(
				'adminUrl' => admin_url(),
			);
			wp_localize_script(
				'dame-admin-command-palette',
				'dame_admin_commands',
				$commands_payload
			);
			wp_localize_script(
				'dame-admin-command-palette',
				'dameAdminCommands',
				$commands_payload
			);
		}

		// 2. Styles d'administration communs aux écrans DAME.
		wp_enqueue_style( 'dame-admin-styles' );

		// 3. Autocomplétion IGN / Coordonnées : uniquement là où les champs d'adresses existent.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab                        = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$needs_address_autocomplete = (
			( $is_adherent_cpt && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) ||
			( $is_agenda_cpt && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) ||
			( $is_pre_inscription_cpt && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) ||
			( 'dame-hidden_page_dame-view-adherent' === $screen->id ) ||
			( false !== strpos( (string) $hook, 'dame-settings' ) && ( 'association' === $tab || empty( $tab ) ) )
		);

		if ( $needs_address_autocomplete ) {
			wp_enqueue_style( 'dame-admin-common-css' );

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
		}

		// 4. Scripts spécifiques aux Adhérents.
		// Synchronisation nom d'usage / nom de naissance : uniquement sur le formulaire d'édition.
		if ( ( $is_adherent_cpt && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) || 'dame-hidden_page_dame-view-adherent' === $screen->id ) {
			wp_enqueue_script( 'dame-admin-adherent' );
		}

		// Modale et actions d'attestation : liste, vue et édition de fiche adhérent.
		if ( $is_adherent_cpt || 'dame-hidden_page_dame-view-adherent' === $screen->id ) {
			wp_enqueue_script( 'dame-admin-attestation' );
			$attestation_payload = array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'dame_attestation_action' ),
			);
			wp_localize_script(
				'dame-admin-attestation',
				'dame_attestation_data',
				$attestation_payload
			);
			wp_localize_script(
				'dame-admin-attestation',
				'dameAttestationData',
				$attestation_payload
			);
		}
	}
}
