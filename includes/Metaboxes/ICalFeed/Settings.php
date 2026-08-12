<?php
/**
 * ICalFeed Settings Metabox Class.
 *
 * @package DAME\Metaboxes\ICalFeed
 */

namespace DAME\Metaboxes\ICalFeed;

use WP_Post;

/**
 * Class Settings
 * Manages the settings metabox for the ICalFeed CPT.
 */
class Settings {

	/**
	 * Initialize the metabox.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_dame_ical_feed', array( $this, 'save' ) );
	}

	/**
	 * Adds the metabox for iCal Feed settings.
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'dame_ical_feed_settings',
			__( 'Configuration du flux', 'dame' ),
			array( $this, 'render' ),
			'dame_ical_feed',
			'normal',
			'high'
		);
	}

	/**
	 * Renders the metabox content for iCal Feed settings.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render( $post ): void {
		wp_nonce_field( 'dame_save_ical_feed_meta', 'dame_ical_feed_nonce' );

		$selected_categories = get_post_meta( $post->ID, '_dame_ical_feed_categories', true );
		if ( ! is_array( $selected_categories ) ) {
			$selected_categories = array();
		}

		$categories = get_terms(
			array(
				'taxonomy'   => 'dame_agenda_category',
				'hide_empty' => false,
			)
		);

		echo '<p>' . esc_html__( 'Sélectionnez les catégories d\'événements à inclure dans ce flux. Seuls les événements publics seront inclus.', 'dame' ) . '</p>';

		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			echo '<p>' . esc_html__( 'Aucune catégorie d\'événement n\'a été trouvée.', 'dame' ) . '</p>';
			return;
		}

		echo '<div class="category-checklist-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 6px;">';
		foreach ( $categories as $category ) {
			$checked = in_array( (int) $category->term_id, array_map( 'intval', $selected_categories ), true );
			echo '<label style="display: block;">';
			echo '<input type="checkbox" name="dame_ical_feed_categories[]" value="' . esc_attr( (string) $category->term_id ) . '" ' . checked( $checked, true, false ) . '> ';
			echo esc_html( $category->name );
			echo '</label>';
		}
		echo '</div>';
	}

	/**
	 * Saves the metadata for the iCal Feed.
	 *
	 * @param int $post_id The post ID.
	 */
	public function save( $post_id ): void {
		$nonce = isset( $_POST['dame_ical_feed_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_ical_feed_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'dame_save_ical_feed_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['dame_ical_feed_categories'] ) && is_array( $_POST['dame_ical_feed_categories'] ) ) {
			$categories = array_map( 'intval', wp_unslash( $_POST['dame_ical_feed_categories'] ) );
			update_post_meta( $post_id, '_dame_ical_feed_categories', $categories );
		} else {
			delete_post_meta( $post_id, '_dame_ical_feed_categories' );
		}
	}
}
