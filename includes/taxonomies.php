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
