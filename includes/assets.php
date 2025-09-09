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
	global $post;

	// Enqueue the main public stylesheet.
	wp_enqueue_style(
		'dame-public-styles',
		plugin_dir_url( __FILE__ ) . '../public/css/dame-public-styles.css',
		array(),
		DAME_VERSION
	);

	// Enqueue calendar assets only if the shortcode is present.
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'dame_calendrier' ) ) {
		wp_enqueue_style(
			'dame-calendrier-styles',
			plugin_dir_url( __FILE__ ) . '../public/css/dame-calendrier.css',
			array(),
			DAME_VERSION
		);
		wp_enqueue_script(
			'dame-calendrier-js',
			plugin_dir_url( __FILE__ ) . '../public/js/dame-calendrier.js',
			array( 'jquery' ),
			DAME_VERSION,
			true
		);
		wp_localize_script(
			'dame-calendrier-js',
			'dame_calendar_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'dame_calendar_nonce' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'dame_enqueue_public_assets' );
