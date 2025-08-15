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
/**
 * Returns the capabilities for the Exercice CPT.
 *
 * @return array The list of capabilities.
 */
function dame_get_exercice_capabilities() {
    return array(
        'edit_exercice'          => true,
        'read_exercice'          => true,
        'delete_exercice'        => true,
        'edit_exercices'         => true,
        'edit_others_exercices'  => true,
        'publish_exercices'      => true,
        'read_private_exercices' => true,
    );
}

/**
 * Returns the capabilities for the Cours CPT.
 *
 * @return array The list of capabilities.
 */
function dame_get_cours_capabilities() {
    return array(
        'edit_cours'          => true,
        'read_cours'          => true,
        'delete_cours'        => true,
        'edit_cours'         => true,
        'edit_others_cours'  => true,
        'publish_cours'      => true,
        'read_private_cours' => true,
    );
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
    // Based on Editor.
    $editor = get_role( 'editor' );
    if ( $editor ) {
        $entraineur_caps = array_merge(
            $editor->capabilities,
            dame_get_exercice_capabilities(),
            dame_get_cours_capabilities()
        );
        add_role( 'entraineur', __( 'Entraineur', 'dame' ), $entraineur_caps );
    }

    // Add caps to Administrator
    $admin = get_role( 'administrator' );
    if ( $admin ) {
        $admin_caps = array_merge(
            dame_get_exercice_capabilities(),
            dame_get_cours_capabilities()
        );
        foreach ( $admin_caps as $cap => $grant ) {
            $admin->add_cap( $cap, $grant );
        }
    }

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
