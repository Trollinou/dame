<?php
/**
 * Pre-inscription Custom Post Type.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the Pre-inscription CPT.
 */
function dame_register_pre_inscription_cpt() {

	$labels = array(
		'name'                  => _x( 'Préinscriptions', 'Post Type General Name', 'dame' ),
		'singular_name'         => _x( 'Préinscription', 'Post Type Singular Name', 'dame' ),
		'menu_name'             => __( 'Préinscriptions', 'dame' ),
		'name_admin_bar'        => __( 'Préinscription', 'dame' ),
		'archives'              => __( 'Archives des préinscriptions', 'dame' ),
		'attributes'            => __( 'Attributs de la préinscription', 'dame' ),
		'parent_item_colon'     => __( 'Préinscription parente :', 'dame' ),
		'all_items'             => __( 'Toutes les préinscriptions', 'dame' ),
		'add_new_item'          => __( 'Ajouter une nouvelle préinscription', 'dame' ),
		'add_new'               => __( 'Ajouter', 'dame' ),
		'new_item'              => __( 'Nouvelle préinscription', 'dame' ),
		'edit_item'             => __( 'Modifier la préinscription', 'dame' ),
		'update_item'           => __( 'Mettre à jour la préinscription', 'dame' ),
		'view_item'             => __( 'Voir la préinscription', 'dame' ),
		'view_items'            => __( 'Voir les préinscriptions', 'dame' ),
		'search_items'          => __( 'Rechercher une préinscription', 'dame' ),
		'not_found'             => __( 'Non trouvé', 'dame' ),
		'not_found_in_trash'    => __( 'Non trouvé dans la corbeille', 'dame' ),
		'featured_image'        => __( 'Image mise en avant', 'dame' ),
		'set_featured_image'    => __( 'Définir l\'image mise en avant', 'dame' ),
		'remove_featured_image' => __( 'Supprimer l\'image mise en avant', 'dame' ),
		'use_featured_image'    => __( 'Utiliser comme image mise en avant', 'dame' ),
		'insert_into_item'      => __( 'Insérer dans la préinscription', 'dame' ),
		'uploaded_to_this_item' => __( 'Téléversé sur cette préinscription', 'dame' ),
		'items_list'            => __( 'Liste des préinscriptions', 'dame' ),
		'items_list_navigation' => __( 'Navigation de la liste des préinscriptions', 'dame' ),
		'filter_items_list'     => __( 'Filtrer la liste des préinscriptions', 'dame' ),
	);

	$args = array(
		'label'                 => __( 'Préinscription', 'dame' ),
		'description'           => __( 'Les préinscriptions des futurs adhérents', 'dame' ),
		'labels'                => $labels,
		'supports'              => array( 'title' ), // Title will be generated from name
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => 'edit.php?post_type=adherent',
		'menu_icon'             => 'dashicons-id-alt',
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'capability_type'       => 'post',
		'capabilities'          => array(
			'create_posts' => 'do_not_allow', // Prevent manual creation from UI.
		),
		'map_meta_cap'          => true,
		'show_in_rest'          => true,
	);

	register_post_type( 'dame_pre_inscription', $args );

}
add_action( 'init', 'dame_register_pre_inscription_cpt', 0 );
