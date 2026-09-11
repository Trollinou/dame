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

		$terms = get_terms(
			array(
				'taxonomy'   => 'dame_agenda_category',
				'hide_empty' => false,
			)
		);

		echo '<p>' . esc_html__( 'Sélectionnez les catégories d\'événements à inclure dans ce flux. Seuls les événements publics seront inclus.', 'dame' ) . '</p>';

		if ( empty( $terms ) || is_wp_error( $terms ) || ! is_array( $terms ) ) {
			echo '<p>' . esc_html__( 'Aucune catégorie d\'événement n\'a été trouvée.', 'dame' ) . '</p>';
			return;
		}

		/** @var array<\WP_Term> $term_objects */
		$term_objects = array_filter( $terms, static fn( $term ) => $term instanceof \WP_Term );

		echo '<div class="category-checklist-container" style="max-height: 220px; overflow-y: auto; border: 1px solid #ddd; padding: 8px; background: #fff;">';
		$this->render_category_tree( $term_objects, 0, 0, array_map( 'intval', $selected_categories ) );
		echo '</div>';

		$current_slug = ( 'auto-draft' === $post->post_status ) ? '' : $post->post_name;

		echo '<div style="margin-top: 15px;">';
		echo '<p><label for="dame_ical_feed_slug"><strong>' . esc_html__( 'Identifiant personnalisé du flux (Slug / URL) :', 'dame' ) . '</strong></label></p>';
		echo '<input type="text" id="dame_ical_feed_slug" name="dame_ical_feed_slug" value="' . esc_attr( $current_slug ) . '" class="widefat" placeholder="' . esc_attr__( 'Laisser vide pour générer automatiquement à partir du titre', 'dame' ) . '">';
		echo '<p class="description">' . esc_html__( 'Détermine le nom utilisé dans l\'adresse du flux (/feed/agenda/votre-identifiant.ics).', 'dame' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Renders category tree recursively with indentation.
	 *
	 * @param array<\WP_Term> $terms               List of taxonomy terms.
	 * @param int             $parent_id           Parent term ID.
	 * @param int             $depth               Current depth level.
	 * @param array<int>      $selected_categories Selected category IDs.
	 */
	private function render_category_tree( array $terms, int $parent_id, int $depth, array $selected_categories ): void {
		$margin_left = $depth * 20;

		foreach ( $terms as $term ) {
			if ( (int) $term->parent === $parent_id ) {
				$checked = in_array( (int) $term->term_id, $selected_categories, true );
				$prefix  = $depth > 0 ? '— ' : '';

				echo '<label style="display: block; margin-left: ' . (int) $margin_left . 'px; padding: 2px 0;">';
				echo '<input type="checkbox" name="dame_ical_feed_categories[]" value="' . esc_attr( (string) $term->term_id ) . '" ' . checked( $checked, true, false ) . '> ';
				echo esc_html( $prefix . $term->name );
				echo '</label>';

				$this->render_category_tree( $terms, (int) $term->term_id, $depth + 1, $selected_categories );
			}
		}
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

		if ( isset( $_POST['dame_ical_feed_slug'] ) ) {
			$raw_slug = sanitize_title( wp_unslash( $_POST['dame_ical_feed_slug'] ) );
			$post     = get_post( $post_id );
			if ( $post && ! empty( $raw_slug ) && $post->post_name !== $raw_slug ) {
				$unique_slug = wp_unique_post_slug( $raw_slug, $post_id, $post->post_status, $post->post_type, (int) $post->post_parent );
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct update avoids infinite loops with save_post.
				$wpdb->update(
					$wpdb->posts,
					array( 'post_name' => $unique_slug ),
					array( 'ID' => $post_id ),
					array( '%s' ),
					array( '%d' )
				);
				clean_post_cache( $post_id );
			}
		}

		if ( isset( $_POST['dame_ical_feed_categories'] ) && is_array( $_POST['dame_ical_feed_categories'] ) ) {
			$categories = array_map( 'intval', wp_unslash( $_POST['dame_ical_feed_categories'] ) );
			update_post_meta( $post_id, '_dame_ical_feed_categories', $categories );
		} else {
			delete_post_meta( $post_id, '_dame_ical_feed_categories' );
		}
	}
}
