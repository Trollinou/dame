<?php
/**
 * Adherent Custom Post Type.
 *
 * @package DAME
 */

namespace DAME\CPT;

/**
 * Class Adherent
 */
class Adherent {

	/**
	 * Registers the CPT.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_post_type' ], 0 );
	}

	/**
	 * Register the custom post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Adhérents', 'Post Type General Name', 'dame' ),
			'singular_name'         => _x( 'Adhérent', 'Post Type Singular Name', 'dame' ),
			'menu_name'             => __( 'Adhérents', 'dame' ),
			'name_admin_bar'        => __( 'Adhérent', 'dame' ),
			'archives'              => __( 'Archives des adhérents', 'dame' ),
			'attributes'            => __( 'Attributs de l\'adhérent', 'dame' ),
			'parent_item_colon'     => __( 'Adhérent parent :', 'dame' ),
			'all_items'             => __( 'Tous les adhérents', 'dame' ),
			'add_new_item'          => __( 'Ajouter un nouvel adhérent', 'dame' ),
			'add_new'               => __( 'Ajouter', 'dame' ),
			'new_item'              => __( 'Nouvel adhérent', 'dame' ),
			'edit_item'             => __( 'Modifier l\'adhérent', 'dame' ),
			'update_item'           => __( 'Mettre à jour l\'adhérent', 'dame' ),
			'view_item'             => __( 'Voir l\'adhérent', 'dame' ),
			'view_items'            => __( 'Voir les adhérents', 'dame' ),
			'search_items'          => __( 'Rechercher un adhérent', 'dame' ),
			'not_found'             => __( 'Non trouvé', 'dame' ),
			'not_found_in_trash'    => __( 'Non trouvé dans la corbeille', 'dame' ),
			'featured_image'        => __( 'Image mise en avant', 'dame' ),
			'set_featured_image'    => __( 'Définir l\'image mise en avant', 'dame' ),
			'remove_featured_image' => __( 'Supprimer l\'image mise en avant', 'dame' ),
			'use_featured_image'    => __( 'Utiliser comme image mise en avant', 'dame' ),
			'insert_into_item'      => __( 'Insérer dans l\'adhérent', 'dame' ),
			'uploaded_to_this_item' => __( 'Téléversé sur cet adhérent', 'dame' ),
			'items_list'            => __( 'Liste des adhérents', 'dame' ),
			'items_list_navigation' => __( 'Navigation de la liste des adhérents', 'dame' ),
			'filter_items_list'     => __( 'Filtrer la liste des adhérents', 'dame' ),
		);

		$args = array(
			'label'                 => __( 'Adhérent', 'dame' ),
			'description'           => __( 'Les adhérents du club', 'dame' ),
			'labels'                => $labels,
			'supports'              => array( 'title' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 20,
			'menu_icon'             => 'dashicons-groups',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
			'show_in_rest'          => true,
		);

		register_post_type( 'dame_adherent', $args );
	}
}
