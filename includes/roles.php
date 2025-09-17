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
    // Based on Editor capabilities.
    $editor_capabilities = array(
        'delete_others_pages'    => true,
        'delete_others_posts'    => true,
        'delete_pages'           => true,
        'delete_posts'           => true,
        'delete_private_pages'   => true,
        'delete_private_posts'   => true,
        'delete_published_pages' => true,
        'delete_published_posts' => true,
        'edit_others_pages'      => true,
        'edit_others_posts'      => true,
        'edit_pages'             => true,
        'edit_posts'             => true,
        'edit_private_pages'     => true,
        'edit_private_posts'     => true,
        'edit_published_pages'   => true,
        'edit_published_posts'   => true,
        'manage_categories'      => true,
        'manage_links'           => true,
        'moderate_comments'      => true,
        'publish_pages'          => true,
        'publish_posts'          => true,
        'read'                   => true,
        'read_private_pages'     => true,
        'read_private_posts'     => true,
        'unfiltered_html'        => true,
        'upload_files'           => true,
    );
    add_role( 'entraineur', __( 'Entraineur', 'dame' ), $editor_capabilities );

    // Flush rewrite rules to register CPT slugs
    flush_rewrite_rules();

    // Store the plugin version on activation.
    update_option( 'dame_plugin_version', DAME_VERSION );
}

/**
 * Remove custom roles on plugin deactivation.
 */
function dame_remove_custom_roles() {
    remove_role( 'membre' );
    remove_role( 'entraineur' );
}
