<?php
/**
 * Benevolat Admin List Table.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin\ListTables;

/**
 * Class Benevolat
 * Manages admin columns for Benevolat CPT.
 */
class Benevolat {

	/**
	 * Initialize.
	 */
	public function init(): void {
		add_filter( 'manage_benevolat_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_benevolat_posts_custom_column', array( $this, 'display_columns' ), 10, 2 );
	}

	/**
	 * Add custom columns.
	 *
	 * @param array<string, mixed> $columns Existing columns.
	 * @return array<string, mixed> New columns.
	 */
	public function add_columns( $columns ): array {
		$new_columns = array();
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'title' === $key ) {
				$new_columns['benevolat_article']   = __( 'Article lié', 'dame' );
				$new_columns['benevolat_votes']     = __( 'Inscrits', 'dame' );
				$new_columns['benevolat_shortcode'] = __( 'Shortcode', 'dame' );
			}
		}
		return $new_columns;
	}

	/**
	 * Display custom column content.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function display_columns( $column, $post_id ): void {
		switch ( $column ) {
			case 'benevolat_article':
				$linked_article_id = (int) get_post_meta( $post_id, '_dame_linked_article_id', true );
				if ( $linked_article_id > 0 && 'post' === get_post_type( $linked_article_id ) ) {
					$edit_url = get_edit_post_link( $linked_article_id );
					$title    = get_the_title( $linked_article_id );
					$status   = get_post_status( $linked_article_id );
					echo '<a href="' . esc_url( (string) $edit_url ) . '"><strong>' . esc_html( $title ? $title : '#' . $linked_article_id ) . '</strong></a>';
					if ( 'publish' !== $status ) {
						echo ' <span class="dame-status-badge" style="font-size: 10px; background: #eee; padding: 1px 4px; border-radius: 2px;">' . esc_html( (string) $status ) . '</span>';
					}
				} else {
					echo '<span style="color: #a7aaad;">—</span>';
				}
				break;

			case 'benevolat_votes':
				$repository = new \DAME\Repositories\BenevolatRepository();
				echo esc_html( (string) $repository->get_distinct_voter_count( $post_id ) );
				break;

			case 'benevolat_shortcode':
				$slug = get_post_field( 'post_name', $post_id );
				echo '<input type="text" readonly value="[dame_benevolat slug=&quot;' . esc_attr( (string) $slug ) . '&quot;]" class="large-text code dame-auto-select">';
				break;
		}
	}
}
