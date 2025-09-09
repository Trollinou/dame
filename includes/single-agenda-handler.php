<?php
/**
 * Handles template loading for the single Agenda view.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Loads the custom template for single 'dame_agenda' posts.
 *
 * @param string $template The path of the template to include.
 * @return string The path of the template to include.
 */
function dame_load_single_agenda_template( $template ) {
	if ( is_singular( 'dame_agenda' ) ) {
		$new_template = DAME_PLUGIN_DIR . 'templates/single-dame_agenda.php';
		if ( file_exists( $new_template ) ) {
			return $new_template;
		}
	}
	return $template;
}
add_filter( 'template_include', 'dame_load_single_agenda_template' );
