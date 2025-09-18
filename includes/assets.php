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

    // Enqueue the agenda script on single event pages for the GPS button functionality.
    if ( is_singular( 'dame_agenda' ) ) {
        wp_enqueue_script( 'dame-agenda-script', plugin_dir_url( __FILE__ ) . '../public/js/agenda.js', array( 'jquery' ), DAME_VERSION, true );
    }
}
add_action( 'wp_enqueue_scripts', 'dame_enqueue_public_assets' );
