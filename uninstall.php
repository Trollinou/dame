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
delete_option( 'dame_current_season_tag_id' );

global $wpdb;

// Delete all 'adherent' custom post types.
$adherent_post_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'adherent'" );

if ( ! empty( $adherent_post_ids ) ) {
    // Delete the posts
    $wpdb->query( "DELETE FROM $wpdb->posts WHERE ID IN (" . implode( ',', array_map( 'absint', $adherent_post_ids ) ) . ")" );

    // Delete post meta
    $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN (" . implode( ',', array_map( 'absint', $adherent_post_ids ) ) . ")" );

    // Clean up term relationships for the deleted posts.
    $wpdb->query( "DELETE FROM $wpdb->term_relationships WHERE object_id IN (" . implode( ',', array_map( 'absint', $adherent_post_ids ) ) . ")" );
}

// Delete custom taxonomy terms for 'dame_saison_adhesion'.
$taxonomy = 'dame_saison_adhesion';
$term_ids = $wpdb->get_col( $wpdb->prepare( "SELECT t.term_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s", $taxonomy ) );
if ( ! empty( $term_ids ) ) {
    $term_ids_str = implode( ',', array_map( 'absint', $term_ids ) );
    $wpdb->query( "DELETE FROM $wpdb->terms WHERE term_id IN ($term_ids_str)" );
    $wpdb->query( "DELETE FROM $wpdb->termmeta WHERE term_id IN ($term_ids_str)" );
    $wpdb->query( "DELETE FROM $wpdb->term_taxonomy WHERE term_id IN ($term_ids_str)" );
}

// Delete the custom roles as a fallback.
// They are normally removed on deactivation, but this is a failsafe.
remove_role( 'membre' );
remove_role( 'entraineur' );
