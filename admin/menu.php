<?php
/**
 * File for handling menu manipulations.
 *
 * @package DAME - Dossier et Apprentissage des Membres Échiquéens
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Add the submenus to the Adherent CPT menu.
 */
function dame_add_adherent_submenus() {
    add_submenu_page(
        'edit.php?post_type=adherent',
        __( 'Assignation des comptes', 'dame' ),
        __( 'Assignation des comptes', 'dame' ),
        'administrator',
        'dame-user-assignment',
        'dame_render_user_assignment_page'
    );
    add_submenu_page(
        'edit.php?post_type=adherent',
        __( 'Envoyer un article', 'dame' ),
        __( 'Envoyer un article', 'dame' ),
        'manage_options', // Capability
        'dame-mailing',
        'dame_render_mailing_page'
    );
    add_submenu_page(
        'edit.php?post_type=adherent',
        __( 'Sauvegarde / Restauration', 'dame' ),
        __( 'Sauvegarde / Restauration', 'dame' ),
        'manage_options',
        'dame-backup-restore',
        'dame_render_backup_restore_page'
    );
}
add_action( 'admin_menu', 'dame_add_adherent_submenus' );


/**
 * Reorders the 'Adhérents' submenu to place 'Assignation des comptes' after 'Ajouter'.
 *
 * This function runs late on the 'admin_menu' hook to ensure all submenu items have been added.
 * It manually rebuilds the submenu array for the 'adherent' CPT to avoid errors
 * caused by manipulating array keys.
 */
function dame_reorder_admin_submenu() {
    global $submenu;

    $parent_slug = 'edit.php?post_type=adherent';
    if ( ! isset( $submenu[ $parent_slug ] ) ) {
        return;
    }

    $item_to_move = null;
    $add_new_slug = 'post-new.php?post_type=adherent';
    $item_to_move_slug = 'dame-user-assignment';

    // Find the item to move and remove it from the array for now.
    foreach ( $submenu[ $parent_slug ] as $key => $item ) {
        if ( $item[2] === $item_to_move_slug ) {
            $item_to_move = $item;
            unset( $submenu[ $parent_slug ][ $key ] );
            break; // Found it, stop looping.
        }
    }

    // If we didn't find the item, there's nothing to do.
    if ( $item_to_move === null ) {
        return;
    }

    // Now, create a new array and insert the item in the correct place.
    $new_submenu = array();
    $item_inserted = false;
    foreach ( $submenu[ $parent_slug ] as $item ) {
        $new_submenu[] = $item;
        if ( $item[2] === $add_new_slug ) {
            // This is the 'Add New' item, so add our item right after it.
            $new_submenu[] = $item_to_move;
            $item_inserted = true;
        }
    }

    // If the 'Add New' link wasn't found for some reason, add our item to the end.
    if ( ! $item_inserted ) {
        $new_submenu[] = $item_to_move;
    }

    $submenu[ $parent_slug ] = $new_submenu;
}
add_action( 'admin_menu', 'dame_reorder_admin_submenu', 999 );
