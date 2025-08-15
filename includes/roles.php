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
        'edit_cours_item'    => true,
        'read_cours_item'    => true,
        'delete_cours_item'  => true,
        'edit_cours'         => true,
        'edit_others_cours'  => true,
        'publish_cours'      => true,
        'read_private_cours' => true,
    );
}

/**
 * Adds the custom capabilities for the plugin to the relevant roles.
 */
function dame_add_capabilities_to_roles() {
    // Add caps to Entraineur
    $entraineur = get_role( 'entraineur' );
    if ( $entraineur ) {
        $caps_to_add = array_merge(
            dame_get_exercice_capabilities(),
            dame_get_cours_capabilities()
        );
        foreach ( $caps_to_add as $cap => $grant ) {
            $entraineur->add_cap( $cap, $grant );
        }
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
    // Based on Editor. We add capabilities separately.
    $editor = get_role( 'editor' );
    if ( $editor ) {
        add_role( 'entraineur', __( 'Entraineur', 'dame' ), $editor->capabilities );
    }

    // Add all our custom capabilities to the roles.
    dame_add_capabilities_to_roles();

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
