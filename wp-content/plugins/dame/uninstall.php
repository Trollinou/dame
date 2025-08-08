<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package DAME
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Check if the user has opted-in to data removal from the settings page.
$options = get_option( 'dame_options' );
if ( ! isset( $options['delete_on_uninstall'] ) || 1 !== (int) $options['delete_on_uninstall'] ) {
    return;
}

// Delete the options.
delete_option( 'dame_options' );
delete_option( 'dame_plugin_version' );
delete_option( 'dame_last_reset_year' );

global $wpdb;

// Delete all 'adherent' custom post types.
$adherent_post_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'adherent'" );

if ( ! empty( $adherent_post_ids ) ) {
    // Delete the posts
    $wpdb->query( "DELETE FROM $wpdb->posts WHERE ID IN (" . implode( ',', array_map( 'absint', $adherent_post_ids ) ) . ")" );

    // Delete post meta
    $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN (" . implode( ',', array_map( 'absint', $adherent_post_ids ) ) . ")" );

    // Additional cleanup for term relationships, though we don't use custom taxonomies.
    // It's good practice to include it.
    $wpdb->query( "DELETE FROM $wpdb->term_relationships WHERE object_id IN (" . implode( ',', array_map( 'absint', $adherent_post_ids ) ) . ")" );
}

// Delete the custom roles as a fallback.
// They are normally removed on deactivation, but this is a failsafe.
remove_role( 'membre' );
remove_role( 'entraineur' );
