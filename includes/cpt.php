<?php
/**
 * File for registering the Adherent Custom Post Type.
 *
 * @package DAME - Dossier et Apprentissage des Membres Échiquéens
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the Adherent CPT.
 */
function dame_register_adherent_cpt() {

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
        'supports'              => array( 'title' ), // We will use title for the full name
        'hierarchical'          => false,
        'public'                => false, // Not publicly queryable
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
        'show_in_rest'          => true, // Enable block editor support
    );

    register_post_type( 'adherent', $args );

}
add_action( 'init', 'dame_register_adherent_cpt', 0 );

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
		'show_in_menu'          => 'edit.php?post_type=dame_adherent',
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

/**
 * Register the Agenda CPT.
 */
function dame_register_agenda_cpt() {

    $labels = array(
        'name'                  => _x( 'Agenda', 'Post Type General Name', 'dame' ),
        'singular_name'         => _x( 'Événement', 'Post Type Singular Name', 'dame' ),
        'menu_name'             => __( 'Agenda', 'dame' ),
        'name_admin_bar'        => __( 'Événement', 'dame' ),
        'archives'              => __( 'Archives des événements', 'dame' ),
        'attributes'            => __( 'Attributs de l\'événement', 'dame' ),
        'parent_item_colon'     => __( 'Événement parent :', 'dame' ),
        'all_items'             => __( 'Tous les événements', 'dame' ),
        'add_new_item'          => __( 'Ajouter un nouvel événement', 'dame' ),
        'add_new'               => __( 'Ajouter', 'dame' ),
        'new_item'              => __( 'Nouvel événement', 'dame' ),
        'edit_item'             => __( 'Modifier l\'événement', 'dame' ),
        'update_item'           => __( 'Mettre à jour l\'événement', 'dame' ),
        'view_item'             => __( 'Voir l\'événement', 'dame' ),
        'view_items'            => __( 'Voir les événements', 'dame' ),
        'search_items'          => __( 'Rechercher un événement', 'dame' ),
        'not_found'             => __( 'Non trouvé', 'dame' ),
        'not_found_in_trash'    => __( 'Non trouvé dans la corbeille', 'dame' ),
        'featured_image'        => __( 'Image mise en avant', 'dame' ),
        'set_featured_image'    => __( 'Définir l\'image mise en avant', 'dame' ),
        'remove_featured_image' => __( 'Supprimer l\'image mise en avant', 'dame' ),
        'use_featured_image'    => __( 'Utiliser comme image mise en avant', 'dame' ),
        'insert_into_item'      => __( 'Insérer dans l\'événement', 'dame' ),
        'uploaded_to_this_item' => __( 'Téléversé sur cet événement', 'dame' ),
        'items_list'            => __( 'Liste des événements', 'dame' ),
        'items_list_navigation' => __( 'Navigation de la liste des événements', 'dame' ),
        'filter_items_list'     => __( 'Filtrer la liste des événements', 'dame' ),
    );

    $args = array(
        'label'                 => __( 'Événement', 'dame' ),
        'description'           => __( 'Les événements de l\'agenda', 'dame' ),
        'labels'                => $labels,
        'supports'              => array( 'title' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 25,
        'menu_icon'             => 'dashicons-calendar-alt',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => false,
    );

    register_post_type( 'dame_agenda', $args );
}
add_action( 'init', 'dame_register_agenda_cpt', 0 );
