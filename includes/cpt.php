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

/**
 * Register the Leçon CPT.
 */
function dame_register_lecon_cpt() {

    $labels = array(
        'name'                  => _x( 'Leçons', 'Post Type General Name', 'dame' ),
        'singular_name'         => _x( 'Leçon', 'Post Type Singular Name', 'dame' ),
        'menu_name'             => __( 'Leçons', 'dame' ),
        'name_admin_bar'        => __( 'Leçon', 'dame' ),
        'archives'              => __( 'Archives des leçons', 'dame' ),
        'attributes'            => __( 'Attributs de la leçon', 'dame' ),
        'parent_item_colon'     => __( 'Leçon parente :', 'dame' ),
        'all_items'             => __( 'Toutes les leçons', 'dame' ),
        'add_new_item'          => __( 'Ajouter une nouvelle leçon', 'dame' ),
        'add_new'               => __( 'Ajouter', 'dame' ),
        'new_item'              => __( 'Nouvelle leçon', 'dame' ),
        'edit_item'             => __( 'Modifier la leçon', 'dame' ),
        'update_item'           => __( 'Mettre à jour la leçon', 'dame' ),
        'view_item'             => __( 'Voir la leçon', 'dame' ),
        'view_items'            => __( 'Voir les leçons', 'dame' ),
        'search_items'          => __( 'Rechercher une leçon', 'dame' ),
        'not_found'             => __( 'Non trouvé', 'dame' ),
        'not_found_in_trash'    => __( 'Non trouvé dans la corbeille', 'dame' ),
        'featured_image'        => __( 'Image mise en avant', 'dame' ),
        'set_featured_image'    => __( 'Définir l\'image mise en avant', 'dame' ),
        'remove_featured_image' => __( 'Supprimer l\'image mise en avant', 'dame' ),
        'use_featured_image'    => __( 'Utiliser comme image mise en avant', 'dame' ),
        'insert_into_item'      => __( 'Insérer dans la leçon', 'dame' ),
        'uploaded_to_this_item' => __( 'Téléversé sur cette leçon', 'dame' ),
        'items_list'            => __( 'Liste des leçons', 'dame' ),
        'items_list_navigation' => __( 'Navigation de la liste des leçons', 'dame' ),
        'filter_items_list'     => __( 'Filtrer la liste des leçons', 'dame' ),
    );

    $args = array(
        'label'                 => __( 'Leçon', 'dame' ),
        'description'           => __( 'Leçons de la section Échecs', 'dame' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail', 'revisions', 'author' ),
        'taxonomies'            => array( 'dame_chess_category' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => 'dame-apprentissage',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'page',
        'show_in_rest'          => true,
    );

    register_post_type( 'dame_lecon', $args );
}
add_action( 'init', 'dame_register_lecon_cpt', 0 );

/**
 * Register the Exercice CPT.
 */
function dame_register_exercice_cpt() {

    $labels = array(
        'name'                  => _x( 'Exercices', 'Post Type General Name', 'dame' ),
        'singular_name'         => _x( 'Exercice', 'Post Type Singular Name', 'dame' ),
        'menu_name'             => __( 'Exercices', 'dame' ),
        'name_admin_bar'        => __( 'Exercice', 'dame' ),
        'archives'              => __( 'Archives des exercices', 'dame' ),
        'attributes'            => __( 'Attributs de l\'exercice', 'dame' ),
        'parent_item_colon'     => __( 'Exercice parent :', 'dame' ),
        'all_items'             => __( 'Tous les exercices', 'dame' ),
        'add_new_item'          => __( 'Ajouter un nouvel exercice', 'dame' ),
        'add_new'               => __( 'Ajouter', 'dame' ),
        'new_item'              => __( 'Nouvel exercice', 'dame' ),
        'edit_item'             => __( 'Modifier l\'exercice', 'dame' ),
        'update_item'           => __( 'Mettre à jour l\'exercice', 'dame' ),
        'view_item'             => __( 'Voir l\'exercice', 'dame' ),
        'view_items'            => __( 'Voir les exercices', 'dame' ),
        'search_items'          => __( 'Rechercher un exercice', 'dame' ),
        'not_found'             => __( 'Non trouvé', 'dame' ),
        'not_found_in_trash'    => __( 'Non trouvé dans la corbeille', 'dame' ),
        'items_list'            => __( 'Liste des exercices', 'dame' ),
        'items_list_navigation' => __( 'Navigation de la liste des exercices', 'dame' ),
        'filter_items_list'     => __( 'Filtrer la liste des exercices', 'dame' ),
    );

    $args = array(
        'label'                 => __( 'Exercice', 'dame' ),
        'description'           => __( 'Exercices de la section Échecs', 'dame' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'revisions', 'author' ),
        'taxonomies'            => array( 'dame_chess_category' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => 'dame-apprentissage',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post', // Changed to 'post' for more granular control
        'capabilities' => array(
            'edit_post'          => 'edit_exercice',
            'read_post'          => 'read_exercice',
            'delete_post'        => 'delete_exercice',
            'edit_posts'         => 'edit_exercices',
            'edit_others_posts'  => 'edit_others_exercices',
            'publish_posts'      => 'publish_exercices',
            'read_private_posts' => 'read_private_exercices',
        ),
        'map_meta_cap'          => true, // Required to make meta capabilities work
        'show_in_rest'          => true,
    );

    register_post_type( 'dame_exercice', $args );
}
add_action( 'init', 'dame_register_exercice_cpt', 0 );

/**
 * Register the Cours CPT.
 */
function dame_register_cours_cpt() {

    $labels = array(
        'name'                  => _x( 'Cours', 'Post Type General Name', 'dame' ),
        'singular_name'         => _x( 'Cours', 'Post Type Singular Name', 'dame' ),
        'menu_name'             => __( 'Cours', 'dame' ),
        'name_admin_bar'        => __( 'Cours', 'dame' ),
        'archives'              => __( 'Archives des cours', 'dame' ),
        'attributes'            => __( 'Attributs du cours', 'dame' ),
        'parent_item_colon'     => __( 'Cours parent :', 'dame' ),
        'all_items'             => __( 'Tous les cours', 'dame' ),
        'add_new_item'          => __( 'Ajouter un nouveau cours', 'dame' ),
        'add_new'               => __( 'Ajouter', 'dame' ),
        'new_item'              => __( 'Nouveau cours', 'dame' ),
        'edit_item'             => __( 'Modifier le cours', 'dame' ),
        'update_item'           => __( 'Mettre à jour le cours', 'dame' ),
        'view_item'             => __( 'Voir le cours', 'dame' ),
        'view_items'            => __( 'Voir les cours', 'dame' ),
        'search_items'          => __( 'Rechercher un cours', 'dame' ),
    );

    $args = array(
        'label'                 => __( 'Cours', 'dame' ),
        'description'           => __( 'Cours constitués de leçons et d\'exercices', 'dame' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'author' ),
        'taxonomies'            => array( 'dame_chess_category' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => 'dame-apprentissage',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post', // Using 'post' and custom capabilities like for exercices
        'capabilities' => array(
            'edit_post'          => 'edit_cours_item',
            'read_post'          => 'read_cours_item',
            'delete_post'        => 'delete_cours_item',
            'edit_posts'         => 'edit_cours',
            'edit_others_posts'  => 'edit_others_cours',
            'publish_posts'      => 'publish_cours',
            'read_private_posts' => 'read_private_cours',
        ),
        'map_meta_cap'          => true,
        'show_in_rest'          => true,
    );

    register_post_type( 'dame_cours', $args );
}
add_action( 'init', 'dame_register_cours_cpt', 0 );
