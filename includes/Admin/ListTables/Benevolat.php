<?php

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
				$new_columns['poll_votes']     = __( 'Inscrits', 'dame' );
				$new_columns['poll_shortcode'] = __( 'Shortcode', 'dame' );
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
			case 'poll_votes':
				global $wpdb;
				$table_votes = $wpdb->prefix . 'dame_benevolat_votes';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$total = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT v.recipient_id) FROM {$table_votes} v INNER JOIN {$wpdb->posts} p ON v.recipient_id = p.ID WHERE v.poll_id = %d AND p.post_status = 'publish'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$post_id
					)
				);
				echo (int) $total;
				break;

			case 'poll_shortcode':
				$slug = get_post_field( 'post_name', $post_id );
				echo '<input type="text" readonly value="[dame_benevolat slug=&quot;' . esc_attr( (string) $slug ) . '&quot;]" class="large-text code" onclick="this.select()">';
				break;
		}
	}
}
