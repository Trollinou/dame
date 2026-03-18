<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Sondage CPT
 *
 * @return void
 */
function dame_register_sondage_cpt() {
	$labels = array(
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
	);

	$args = array(
		'labels'             => $labels,
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'sondage' ),
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => 20,
		'menu_icon'          => 'dashicons-chart-bar',
		'supports'           => array( 'title', 'editor' ),
	);

	register_post_type( 'sondage', $args );
}
add_action( 'init', 'dame_register_sondage_cpt' );

/**
 * Register Sondage Reponse CPT
 *
 * @return void
 */
function dame_register_sondage_reponse_cpt() {
    $labels = array(
		'name'          => _x( 'Réponses aux sondages', 'post type general name', 'dame' ),
		'singular_name' => _x( 'Réponse de sondage', 'post type singular name', 'dame' ),
	);

	$args = array(
		'labels'              => $labels,
		'public'              => false,
		'publicly_queryable'  => false,
		'show_ui'             => false,
		'show_in_menu'        => false,
		'query_var'           => true,
		'rewrite'             => array( 'slug' => 'sondage_reponse' ),
		'capability_type'     => 'post',
		'has_archive'         => false,
		'hierarchical'        => false,
		'supports'            => array( 'title', 'author' ),
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => false,
	);

	register_post_type( 'sondage_reponse', $args );
}
add_action( 'init', 'dame_register_sondage_reponse_cpt' );