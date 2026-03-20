<?php
/**
 * File for handling the admin toolbar menu.
 *
 * @package DAME
 */

namespace DAME\Admin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Handles the admin toolbar integration.
 */
class Toolbar {

	/**
	 * Initializes the toolbar hooks.
	 */
	public function init() {
		add_action( 'admin_bar_menu', [ $this, 'add_nodes' ], 999 );
		add_action( 'admin_action_dame_manual_backup', [ $this, 'handle_manual_backup' ] );
		add_action( 'admin_notices', [ $this, 'show_backup_notice' ] );
	}

	/**
	 * Adds the DAME menu to the WordPress admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
	 */
	public function add_nodes( $wp_admin_bar ) {
		// Add the main DAME menu item.
		$wp_admin_bar->add_node(
			[
				'id'    => 'dame_menu',
				'title' => '<span class="ab-icon">&#x265F;</span>' . __( "DAME", "dame" ),
				'href'  => admin_url( 'edit.php?post_type=adherent' ),
			]
		);

		// Add the "Voir les préinscriptions" sub-menu item.
		$wp_admin_bar->add_node(
			[
				'id'     => 'dame_view_preinscriptions',
				'parent' => 'dame_menu',
				'title'  => __( "Voir les préinscriptions", "dame" ),
				'href'   => admin_url( 'edit.php?post_type=dame_pre_inscription' ),
			]
		);

		// Add the "Envoyer un article" sub-menu item.
		$wp_admin_bar->add_node(
			[
				'id'     => 'dame_send_article',
				'parent' => 'dame_menu',
				'title'  => __( "Envoyer un article", "dame" ),
				'href'   => admin_url( 'edit.php?post_type=adherent&page=dame-mailing' ),
			]
		);

		// Add the "Faire une sauvegarde" sub-menu item.
		$backup_url = wp_nonce_url( admin_url( 'admin.php?action=dame_manual_backup' ), 'dame_manual_backup_nonce' );

		$wp_admin_bar->add_node(
			[
				'id'     => 'dame_manual_backup',
				'parent' => 'dame_menu',
				'title'  => __( "Faire une sauvegarde", "dame" ),
				'href'   => $backup_url,
			]
		);
	}

	/**
	 * Handles the manual backup trigger from the admin bar.
	 */
	public function handle_manual_backup() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'dame_manual_backup_nonce' ) ) {
			wp_die( __( "Invalid nonce.", "dame" ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( "You do not have sufficient permissions to perform this action.", "dame" ) );
		}

		// Trigger the backup.
		do_action( 'dame_daily_backup_event' );

		// Redirect back to the dashboard with a success message.
		wp_safe_redirect( add_query_arg( 'dame_backup_triggered', '1', admin_url() ) );
		exit;
	}

	/**
	 * Displays an admin notice when the manual backup is triggered.
	 */
	public function show_backup_notice() {
		if ( isset( $_GET['dame_backup_triggered'] ) && '1' === $_GET['dame_backup_triggered'] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( "La sauvegarde a été déclenchée avec succès.", "dame" ); ?></p>
			</div>
			<?php
		}
	}
}
