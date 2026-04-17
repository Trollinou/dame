<?php
/**
 * Custom Post Type: Sondage
 *
 * @package DAME
 */

namespace DAME\CPT;

/**
 * Class Sondage
 */
class Sondage {

	/**
	 * Initialize the CPT hooks.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Register the custom post types.
	 */
	public function register() {
		$sondage_labels = [
			'name'               => _x( 'Sondages', 'post type general name', 'dame' ),
			'singular_name'      => _x( 'Sondage', 'post type singular name', 'dame' ),
			'menu_name'          => _x( 'Sondages', 'admin menu', 'dame' ),
			'name_admin_bar'     => _x( 'Sondage', 'add new on admin bar', 'dame' ),
			'add_new'            => _x( 'Ajouter', 'sondage', 'dame' ),
			'add_new_item'       => __( 'Ajouter un nouveau sondage', 'dame' ),
			'new_item'           => __( 'Nouveau sondage', 'dame' ),
			'edit_item'          => __( 'Modifier le sondage', 'dame' ),
			'view_item'          => __( 'Voir le sondage', 'dame' ),
			'all_items'          => __( 'Tous les sondages', 'dame' ),
			'search_items'       => __( 'Rechercher des sondages', 'dame' ),
			'parent_item_colon'  => __( 'Sondages parents:', 'dame' ),
			'not_found'          => __( 'Aucun sondage trouvé.', 'dame' ),
			'not_found_in_trash' => __( 'Aucun sondage trouvé dans la corbeille.', 'dame' ),
		];

		$sondage_args = [
			'labels'             => $sondage_labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'dame-admin',
			'query_var'          => true,
			'rewrite'            => [ 'slug' => 'sondage' ],
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-chart-bar',
			'supports'           => [ 'title', 'editor' ],
		];

		register_post_type( 'sondage', $sondage_args );

		$reponse_labels = [
			'name'          => _x( 'Réponses aux sondages', 'post type general name', 'dame' ),
			'singular_name' => _x( 'Réponse de sondage', 'post type singular name', 'dame' ),
		];

		$reponse_args = [
			'labels'              => $reponse_labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'query_var'           => true,
			'rewrite'             => [ 'slug' => 'sondage_reponse' ],
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => [ 'title', 'author' ],
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
		];

		register_post_type( 'sondage_reponse', $reponse_args );
	}
}
