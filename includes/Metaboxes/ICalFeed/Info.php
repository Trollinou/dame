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
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}

	/**
	 * Adds the metabox for iCal Feed information.
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'dame_ical_feed_info',
			__( 'Informations de connexion', 'dame' ),
			array( $this, 'render' ),
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
	public function render( $post ): void {
		$feed_slug = $post->post_name;

		if ( empty( $feed_slug ) || 'auto-draft' === $post->post_status ) {
			echo '<p class="description" style="color: #646970;">' . esc_html__( 'Le titre saisi ci-dessus servira de nom pour ce flux. Enregistrez ou publiez le flux pour générer son URL personnalisée.', 'dame' ) . '</p>';
		} else {
			$feed_url = home_url( '/feed/agenda/' . $feed_slug . '.ics' );

			echo '<p><strong>' . esc_html__( 'URL de ce flux :', 'dame' ) . '</strong></p>';
			echo '<input type="text" value="' . esc_url( $feed_url ) . '" class="widefat" readonly onclick="this.select();">';
			echo '<p class="description">' . esc_html__( 'Copiez cette URL pour vous abonner à ce flux personnalisé dans votre agenda (le nom du calendrier correspond au titre du flux).', 'dame' ) . '</p>';
		}

		echo '<hr>';

		echo '<p><strong>' . esc_html__( 'Flux Globaux :', 'dame' ) . '</strong></p>';

		echo '<label>' . esc_html__( 'Flux Public :', 'dame' ) . '</label>';
		echo '<input type="text" value="' . esc_url( home_url( '/feed/agenda/public.ics' ) ) . '" class="widefat" readonly onclick="this.select();" style="margin-bottom: 10px;">';

		echo '<label>' . esc_html__( 'Flux Privé :', 'dame' ) . '</label>';
		echo '<input type="text" value="' . esc_url( home_url( '/feed/agenda/prive.ics' ) ) . '" class="widefat" readonly onclick="this.select();">';
		echo '<p class="description">' . esc_html__( 'Le flux privé nécessite une authentification.', 'dame' ) . '</p>';
	}
}
