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
delete_transient( 'dame_import_export_notice' );

global $wpdb;

// 1. Delete all Plugin Custom Post Types
$post_types = array(
	'adherent',
	'dame_contact',
	'dame_pre_inscription',
	'dame_message',
	'dame_agenda',
	'dame_ical_feed',
	'sondage',
	'sondage_reponse',
);

foreach ( $post_types as $pt ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", $pt ) );
	if ( ! empty( $ids ) ) {
		$ids_formatted = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE ID IN ($ids_formatted)", ...$ids ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($ids_formatted)", ...$ids ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ($ids_formatted)", ...$ids ) );
	}
}

// 2. Delete Custom Taxonomies
$taxonomies = array(
	'dame_saison_adhesion',
	'dame_group',
	'dame_contact_type',
	'dame_agenda_category',
);

foreach ( $taxonomies as $tax_name ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$term_ids = $wpdb->get_col( $wpdb->prepare( "SELECT t.term_id FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s", $tax_name ) );
	if ( ! empty( $term_ids ) ) {
		$term_ids_formatted = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->terms} WHERE term_id IN ($term_ids_formatted)", ...$term_ids ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->termmeta} WHERE term_id IN ($term_ids_formatted)", ...$term_ids ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->term_taxonomy} WHERE term_id IN ($term_ids_formatted)", ...$term_ids ) );
	}
}

// 3. Drop Custom SQL Tables
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}dame_message_opens" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}dame_poll_votes" );

// Delete the custom roles as a fallback.
// They are normally removed on deactivation, but this is a failsafe.
remove_role( 'membre' );
remove_role( 'entraineur' );
