<?php
/**
 * ICalFeed Info Metabox Class.
 *
 * @package DAME\Metaboxes\ICalFeed
 */

namespace DAME\Metaboxes\ICalFeed;

use WP_Post;

/**
 * Class Info
 * Displays the iCal feed URLs in a metabox.
 */
class Info {

	/**
	 * Initialize the metabox.
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
	}

	/**
	 * Adds the metabox for iCal Feed information.
	 */
	public function add_meta_box() {
		add_meta_box(
			'dame_ical_feed_info',
			__( 'Informations de connexion', 'dame' ),
			[ $this, 'render' ],
			'dame_ical_feed',
			'side',
			'high'
		);
	}

	/**
	 * Renders the metabox content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render( $post ) {
		$feed_slug = $post->post_name;
		$feed_url  = home_url( '/feed/agenda/' . $feed_slug . '.ics' );

		echo '<p><strong>' . __( 'URL de ce flux :', 'dame' ) . '</strong></p>';
		echo '<input type="text" value="' . esc_url( $feed_url ) . '" class="widefat" readonly onclick="this.select();">';
		echo '<p class="description">' . __( 'Copiez cette URL pour vous abonner à ce flux personnalisé dans votre application d\'agenda.', 'dame' ) . '</p>';

		echo '<hr>';

		echo '<p><strong>' . __( 'Flux Globaux :', 'dame' ) . '</strong></p>';

		echo '<label>' . __( 'Flux Public :', 'dame' ) . '</label>';
		echo '<input type="text" value="' . esc_url( home_url( '/feed/agenda/public.ics' ) ) . '" class="widefat" readonly onclick="this.select();" style="margin-bottom: 10px;">';

		echo '<label>' . __( 'Flux Privé :', 'dame' ) . '</label>';
		echo '<input type="text" value="' . esc_url( home_url( '/feed/agenda/prive.ics' ) ) . '" class="widefat" readonly onclick="this.select();">';
		echo '<p class="description">' . __( 'Le flux privé nécessite une authentification.', 'dame' ) . '</p>';
	}
}
