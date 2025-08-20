<?php
/**
 * File for registering custom taxonomies.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register Chess Category Taxonomy
 */
function dame_register_chess_category_taxonomy() {
    $labels = array(
        'name'              => _x( 'Catégories d\'échecs', 'taxonomy general name', 'dame' ),
        'singular_name'     => _x( 'Catégorie d\'échecs', 'taxonomy singular name', 'dame' ),
        'search_items'      => __( 'Rechercher les catégories', 'dame' ),
        'all_items'         => __( 'Toutes les catégories', 'dame' ),
        'parent_item'       => __( 'Catégorie parente', 'dame' ),
        'parent_item_colon' => __( 'Catégorie parente :', 'dame' ),
        'edit_item'         => __( 'Modifier la catégorie', 'dame' ),
        'update_item'       => __( 'Mettre à jour la catégorie', 'dame' ),
        'add_new_item'      => __( 'Ajouter une nouvelle catégorie', 'dame' ),
        'new_item_name'     => __( 'Nom de la nouvelle catégorie', 'dame' ),
        'menu_name'         => __( 'Catégories d\'échecs', 'dame' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'chess-category' ),
        'show_in_rest'      => true, // Available in block editor
        'show_in_menu'      => false,
    );

    // We will register this for the CPTs later
    register_taxonomy( 'dame_chess_category', array( 'dame_lecon', 'dame_exercice', 'dame_cours' ), $args );
}
add_action( 'init', 'dame_register_chess_category_taxonomy', 0 );


/**
 * Register Membership Season Taxonomy for Adherents.
 */
function dame_register_membership_season_taxonomy() {
    $labels = array(
        'name'                       => _x( 'Saisons d\'adhésion', 'taxonomy general name', 'dame' ),
        'singular_name'              => _x( 'Saison d\'adhésion', 'taxonomy singular name', 'dame' ),
        'search_items'               => __( 'Rechercher les saisons', 'dame' ),
        'popular_items'              => __( 'Saisons populaires', 'dame' ),
        'all_items'                  => __( 'Toutes les saisons', 'dame' ),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __( 'Modifier la saison', 'dame' ),
        'update_item'                => __( 'Mettre à jour la saison', 'dame' ),
        'add_new_item'               => __( 'Ajouter une nouvelle saison', 'dame' ),
        'new_item_name'              => __( 'Nom de la nouvelle saison', 'dame' ),
        'separate_items_with_commas' => __( 'Séparer les saisons avec des virgules', 'dame' ),
        'add_or_remove_items'        => __( 'Ajouter ou supprimer des saisons', 'dame' ),
        'choose_from_most_used'      => __( 'Choisir parmi les saisons les plus utilisées', 'dame' ),
        'not_found'                  => __( 'Aucune saison trouvée.', 'dame' ),
        'menu_name'                  => __( 'Saisons d\'adhésion', 'dame' ),
    );

    $args = array(
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => false, // We will handle this column manually.
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'saison-adhesion' ),
        'show_in_rest'      => true,
    );

    register_taxonomy( 'dame_saison_adhesion', 'adherent', $args );
}
add_action( 'init', 'dame_register_membership_season_taxonomy', 0 );
