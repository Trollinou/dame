<?php
/**
 * Registers the `dame_ical_feed` custom post type.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Registers the Custom Post Type for iCal Feeds.
 */
function dame_register_ical_feed_cpt() {
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
        'show_in_menu'       => 'edit.php?post_type=dame_agenda',
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
add_action( 'init', 'dame_register_ical_feed_cpt' );
