<?php
/**
 * File for handling the admin toolbar menu.
 *
 * @package DAME
 */

declare(strict_types=1);

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
	public function init(): void {
		add_action( 'admin_bar_menu', array( $this, 'add_nodes' ), 999 );
	}

	/**
	 * Adds the DAME menu to the WordPress admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
	 */
	public function add_nodes( $wp_admin_bar ): void {
		// Do not show if the user cannot edit DAME messages.
		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			return;
		}

		// Add the main DAME menu item (Dashboard).
		$wp_admin_bar->add_node(
			array(
				'id'    => 'dame_menu',
				'title' => '<span class="ab-icon">&#x265F;</span>' . __( 'DAME', 'dame' ),
				'href'  => admin_url( 'admin.php?page=dame-admin' ),
			)
		);

		// Item 2: Préinscriptions.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'dame_view_preinscriptions',
				'parent' => 'dame_menu',
				'title'  => __( 'Préinscriptions', 'dame' ),
				'href'   => admin_url( 'edit.php?post_type=dame_pre_inscription' ),
			)
		);

		// Item 3: Adhérents.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'dame_view_adherents',
				'parent' => 'dame_menu',
				'title'  => __( 'Adhérents', 'dame' ),
				'href'   => admin_url( 'edit.php?post_type=adherent' ),
			)
		);

		// Item 4: Contacts.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'dame_view_contacts',
				'parent' => 'dame_menu',
				'title'  => __( 'Contacts', 'dame' ),
				'href'   => admin_url( 'edit.php?post_type=dame_contact' ),
			)
		);

		// Item 5: Évènements.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'dame_view_events',
				'parent' => 'dame_menu',
				'title'  => __( 'Évènements', 'dame' ),
				'href'   => admin_url( 'edit.php?post_type=dame_agenda' ),
			)
		);

		// Item 6: HelloAsso.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'dame_view_helloasso',
				'parent' => 'dame_menu',
				'title'  => __( 'HelloAsso', 'dame' ),
				'href'   => admin_url( 'admin.php?page=dame-ticketing' ),
			)
		);

		// Item 7: Appels à bénévoles.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'dame_view_polls',
				'parent' => 'dame_menu',
				'title'  => __( 'Appels à bénévoles', 'dame' ),
				'href'   => admin_url( 'edit.php?post_type=benevolat' ),
			)
		);

		// Item 8: Messages.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'dame_view_messages',
				'parent' => 'dame_menu',
				'title'  => __( 'Messages', 'dame' ),
				'href'   => admin_url( 'edit.php?post_type=dame_message' ),
			)
		);
	}
}
