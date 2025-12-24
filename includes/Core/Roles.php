<?php
/**
 * Roles Management.
 *
 * @package DAME
 */

namespace DAME\Core;

/**
 * Class Roles
 */
class Roles {

	/**
	 * Initialize the roles.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_roles' ] );
	}

	/**
	 * Register custom roles and assign capabilities.
	 */
	public function register_roles() {
		// Role: Membre
		add_role(
			'membre',
			__( 'Membre', 'dame' ),
			array(
				'read'          => true,
				'post_comments' => true,
			)
		);

		// Role: Staff (Membre du Bureau)
		// Based on Contributor
		$contributor = get_role( 'contributor' );
		if ( $contributor ) {
			$staff_caps = array_merge(
				$contributor->capabilities,
				array(
					'read_private_pages' => true,
					'read_private_posts' => true,
					'edit_pages'         => true,
				)
			);
			add_role( 'staff', __( 'Membre du Bureau', 'dame' ), $staff_caps );
		}

		// Role: Entraineur
		// Based on Editor
		$entraineur_caps = array(
			'delete_others_pages'    => true,
			'delete_others_posts'    => true,
			'delete_pages'           => true,
			'delete_posts'           => true,
			'delete_private_pages'   => true,
			'delete_private_posts'   => true,
			'delete_published_pages' => true,
			'delete_published_posts' => true,
			'edit_others_pages'      => true,
			'edit_others_posts'      => true,
			'edit_pages'             => true,
			'edit_posts'             => true,
			'edit_private_pages'     => true,
			'edit_private_posts'     => true,
			'edit_published_pages'   => true,
			'edit_published_posts'   => true,
			'manage_categories'      => true,
			'manage_links'           => true,
			'moderate_comments'      => true,
			'publish_pages'          => true,
			'publish_posts'          => true,
			'read'                   => true,
			'read_private_pages'     => true,
			'read_private_posts'     => true,
			'unfiltered_html'        => true,
			'upload_files'           => true,
		);
		add_role( 'entraineur', __( 'Entraineur', 'dame' ), $entraineur_caps );

		// Assign custom message capabilities
		$roles_to_modify = array( 'administrator', 'editor', 'staff' );
		foreach ( $roles_to_modify as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->add_cap( 'edit_dame_message' );
				$role->add_cap( 'read_dame_message' );
				$role->add_cap( 'delete_dame_message' );
				$role->add_cap( 'edit_dame_messages' );
				$role->add_cap( 'edit_others_dame_messages' );
				$role->add_cap( 'publish_dame_messages' );
				$role->add_cap( 'read_private_dame_messages' );
			}
		}
	}
}
