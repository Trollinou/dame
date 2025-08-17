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
}
add_action( 'wp_enqueue_scripts', 'dame_enqueue_public_assets' );
