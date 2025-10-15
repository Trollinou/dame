<?php
/**
 * Handles asset enqueuing for the DAME plugin.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Enqueues front-end scripts and styles.
 */
function dame_enqueue_public_assets() {
    // Enqueue the public-facing stylesheet.
    wp_enqueue_style(
        'dame-public-styles',
        plugin_dir_url( __FILE__ ) . '../public/css/dame-public-styles.css',
        array(),
        DAME_VERSION
    );

    // Enqueue the single event script on single event pages for the GPS button functionality.
    if ( is_singular( 'dame_agenda' ) ) {
        wp_enqueue_script( 'dame-single-event-script', plugin_dir_url( __FILE__ ) . '../public/js/single-event.js', array( 'jquery' ), DAME_VERSION, true );
    }
}
add_action( 'wp_enqueue_scripts', 'dame_enqueue_public_assets' );

/**
 * Enqueues admin scripts and styles.
 *
 * @param string $hook The current admin page hook.
 */
function dame_enqueue_admin_assets( $hook ) {
    global $post;

    // For CPT list view pages.
    if ( 'edit.php' === $hook && isset( $_GET['post_type'] ) && 'adherent' === $_GET['post_type'] ) {
        wp_enqueue_style(
            'dame-admin-styles',
            plugin_dir_url( __FILE__ ) . '../admin/css/main.css',
            array(),
            DAME_VERSION
        );
    }
}
add_action( 'admin_enqueue_scripts', 'dame_enqueue_admin_assets' );
