<?php
/**
 * File for handling menu manipulations.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Reorders the 'Adhérents' submenu to place 'Assignation des comptes' after 'Ajouter'.
 */
function dame_reorder_admin_submenu() {
    global $submenu;

    $parent_slug = 'edit.php?post_type=adherent';

    if ( ! isset( $submenu[ $parent_slug ] ) ) {
        return;
    }

    $menu_items = $submenu[ $parent_slug ];
    $new_menu_order = array();
    $item_to_move = null;
    $add_new_key = null;

    // Find the 'add new' key and the item to move
    foreach ( $menu_items as $key => $item ) {
        if ( $item[2] === 'dame-user-assignment' ) {
            $item_to_move = $item;
            unset( $menu_items[ $key ] );
        }
        if ( $item[2] === 'post-new.php?post_type=adherent' ) {
            $add_new_key = $key;
        }
    }

    // If we have the item and the position, re-insert it
    if ( $item_to_move && $add_new_key !== null ) {
        $new_menu = array();
        foreach ( $menu_items as $key => $item ) {
            $new_menu[ $key ] = $item;
            if ( $key === $add_new_key ) {
                // Find a new unique key for our item to avoid conflicts
                $new_key = $add_new_key + 0.5;
                while ( isset( $new_menu[ $new_key ] ) ) {
                    $new_key += 0.1;
                }
                $new_menu[ $new_key ] = $item_to_move;
            }
        }
        // Sort the menu by key to respect the new order
        ksort( $new_menu );
        $submenu[ $parent_slug ] = array_values( $new_menu ); // Re-index numerically
    } else if ($item_to_move) {
        // Fallback: if 'Add New' not found, just add it back to the end
        $submenu[ $parent_slug ][] = $item_to_move;
    }
}
add_action( 'admin_menu', 'dame_reorder_admin_submenu', 999 );
