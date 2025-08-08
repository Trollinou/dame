<?php
/**
 * File for handling custom roles.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Add custom roles for the plugin.
 */
function dame_add_custom_roles() {
    // Role: Membre (Member)
    // Based on Subscriber, but can post comments.
    $subscriber = get_role( 'subscriber' );
    if ( $subscriber ) {
        $capabilities = $subscriber->capabilities;
        $capabilities['post_comments'] = true; // As requested
        add_role( 'membre', __( 'Membre', 'dame' ), $capabilities );
    }

    // Role: Entraineur (Coach)
    // Based on Editor.
    $editor = get_role( 'editor' );
    if ( $editor ) {
        add_role( 'entraineur', __( 'Entraineur', 'dame' ), $editor->capabilities );
    }
}

/**
 * Remove custom roles on plugin deactivation.
 */
function dame_remove_custom_roles() {
    remove_role( 'membre' );
    remove_role( 'entraineur' );
}
