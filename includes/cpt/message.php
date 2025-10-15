<?php
/**
 * Message Custom Post Type.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the Message CPT.
 */
function dame_register_message_cpt() {
    $labels = array(
        'name'                  => _x( 'Messages', 'Post Type General Name', 'dame' ),
        'singular_name'         => _x( 'Message', 'Post Type Singular Name', 'dame' ),
        'menu_name'             => __( 'Messages', 'dame' ),
        'name_admin_bar'        => __( 'Message', 'dame' ),
        'archives'              => __( 'Archives des messages', 'dame' ),
        'attributes'            => __( 'Attributs du message', 'dame' ),
        'parent_item_colon'     => __( 'Message parent :', 'dame' ),
        'all_items'             => __( 'Tous les messages', 'dame' ),
        'add_new_item'          => __( 'Ajouter un nouveau message', 'dame' ),
        'add_new'               => __( 'Ajouter', 'dame' ),
        'new_item'              => __( 'Nouveau message', 'dame' ),
        'edit_item'             => __( 'Modifier le message', 'dame' ),
        'update_item'           => __( 'Mettre à jour le message', 'dame' ),
        'view_item'             => __( 'Voir le message', 'dame' ),
        'view_items'            => __( 'Voir les messages', 'dame' ),
        'search_items'          => __( 'Rechercher un message', 'dame' ),
        'not_found'             => __( 'Non trouvé', 'dame' ),
        'not_found_in_trash'    => __( 'Non trouvé dans la corbeille', 'dame' ),
        'insert_into_item'      => __( 'Insérer dans le message', 'dame' ),
        'uploaded_to_this_item' => __( 'Téléversé sur ce message', 'dame' ),
        'items_list'            => __( 'Liste des messages', 'dame' ),
        'items_list_navigation' => __( 'Navigation de la liste des messages', 'dame' ),
        'filter_items_list'     => __( 'Filtrer la liste des messages', 'dame' ),
    );

    $args = array(
        'label'                 => __( 'Message', 'dame' ),
        'description'           => __( 'Messages à envoyer aux adhérents', 'dame' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'revisions' ),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => 'edit.php?post_type=adherent',
        'menu_icon'             => 'dashicons-email-alt',
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
        'capabilities' => array(
            'edit_posts' => 'edit_posts',
            'edit_others_posts' => 'edit_others_posts',
            'publish_posts' => 'publish_posts',
            'read_private_posts' => 'read_private_posts',
            'delete_posts' => 'delete_posts',
            'delete_private_posts' => 'delete_private_posts',
            'delete_published_posts' => 'delete_published_posts',
            'delete_others_posts' => 'delete_others_posts',
            'edit_private_posts' => 'edit_private_posts',
            'edit_published_posts' => 'edit_published_posts',
            'create_posts' => 'edit_posts',
        ),
        'show_in_rest'          => true, // Enable block editor support
    );

    register_post_type( 'dame_message', $args );
}
add_action( 'init', 'dame_register_message_cpt', 0 );
