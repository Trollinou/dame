<?php
/**
 * ICalFeed CPT Class.
 *
 * @package DAME\CPT
 */

namespace DAME\CPT;

/**
 * Class ICalFeed
 * Handles the registration for the 'dame_ical_feed' Custom Post Type.
 */
class ICalFeed {

	/**
	 * Initialize the CPT.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Register the iCal Feed CPT.
	 */
	public function register(): void {
		$labels = array(
			'name'               => _x( 'Flux iCalendar', 'post type general name', 'dame' ),
			'singular_name'      => _x( 'Flux iCalendar', 'post type singular name', 'dame' ),
			'menu_name'          => _x( 'Flux iCalendar', 'admin menu', 'dame' ),
			'name_admin_bar'     => _x( 'Flux iCalendar', 'add new on admin bar', 'dame' ),
			'add_new'            => _x( 'Ajouter un flux', 'ical feed', 'dame' ),
			'add_new_item'       => __( 'Ajouter un nouveau flux', 'dame' ),
			'new_item'           => __( 'Nouveau flux', 'dame' ),
			'edit_item'          => __( 'Modifier le flux', 'dame' ),
			'view_item'          => __( 'Voir le flux', 'dame' ),
			'all_items'          => __( 'Tous les flux', 'dame' ),
			'search_items'       => __( 'Rechercher des flux', 'dame' ),
			'parent_item_colon'  => __( 'Flux parent :', 'dame' ),
			'not_found'          => __( 'Aucun flux trouvé.', 'dame' ),
			'not_found_in_trash' => __( 'Aucun flux trouvé dans la corbeille.', 'dame' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'dame-admin',
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
		);

		register_post_type( 'dame_ical_feed', $args );
	}
}
