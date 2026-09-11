<?php
/**
 * ICalFeed Admin Columns.
 *
 * @package DAME\Admin\Columns
 */

declare(strict_types=1);

namespace DAME\Admin\Columns;

/**
 * Class ICalFeed
 * Manages custom columns for the ICalFeed post list table.
 */
class ICalFeed {

	/**
	 * Initialize columns.
	 */
	public function init(): void {
		add_filter( 'manage_dame_ical_feed_posts_columns', array( $this, 'manage_columns' ) );
		add_action( 'manage_dame_ical_feed_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
	}

	/**
	 * Sets the custom columns for the ICalFeed CPT list table.
	 *
	 * @param array<string, mixed> $columns Existing columns.
	 * @return array<string, mixed> Modified columns.
	 */
	public function manage_columns( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			if ( 'title' === $key ) {
				$new_columns['title']      = __( 'Nom du flux', 'dame' );
				$new_columns['feed_url']   = __( 'URL d\'abonnement', 'dame' );
				$new_columns['categories'] = __( 'Catégories incluses', 'dame' );
			} elseif ( 'date' === $key ) {
				$new_columns[ $key ] = $value;
			} else {
				$new_columns[ $key ] = $value;
			}
		}

		return $new_columns;
	}

	/**
	 * Renders custom column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_columns( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'feed_url':
				$post = get_post( $post_id );
				if ( ! $post || empty( $post->post_name ) ) {
					echo '—';
					return;
				}

				$feed_url = home_url( '/feed/agenda/' . $post->post_name . '.ics' );
				echo '<input type="text" value="' . esc_url( $feed_url ) . '" readonly onfocus="this.select();" class="widefat" style="width: 100%; max-width: 450px;">';
				break;

			case 'categories':
				$selected_categories = get_post_meta( $post_id, '_dame_ical_feed_categories', true );
				if ( ! is_array( $selected_categories ) || empty( $selected_categories ) ) {
					echo '<span style="color: #d63638;">' . esc_html__( 'Aucune catégorie (flux vide)', 'dame' ) . '</span>';
					return;
				}

				$terms = get_terms(
					array(
						'taxonomy'   => 'dame_agenda_category',
						'include'    => array_map( 'intval', $selected_categories ),
						'hide_empty' => false,
					)
				);

				if ( is_array( $terms ) && ! empty( $terms ) ) {
					$names = array();
					foreach ( $terms as $term ) {
						if ( $term instanceof \WP_Term ) {
							$names[] = $term->name;
						}
					}
					echo esc_html( implode( ', ', $names ) );
				} else {
					echo '—';
				}
				break;
		}
	}
}
