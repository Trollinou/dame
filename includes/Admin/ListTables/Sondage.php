<?php

namespace DAME\Admin\ListTables;

/**
 * Class Sondage
 * Manages admin columns for Sondage CPT.
 */
class Sondage {

	/**
	 * Initialize.
	 */
	public function init(): void {
		add_filter( 'manage_dame_sondage_posts_columns', [ $this, 'add_columns' ] );
		add_action( 'manage_dame_sondage_posts_custom_column', [ $this, 'display_columns' ], 10, 2 );
	}

	/**
	 * Add custom columns.
	 *
	 * @param array<string, mixed> $columns Existing columns.
	 * @return array<string, mixed> New columns.
	 */
	public function add_columns( $columns ): array {
		$new_columns = [];
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'title' === $key ) {
				$new_columns['poll_end_date'] = __( 'Date de fin', 'dame' );
				$new_columns['poll_votes']    = __( 'Total Votes', 'dame' );
				$new_columns['poll_shortcode']= __( 'Shortcode', 'dame' );
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
			case 'poll_end_date':
				$date = get_post_meta( $post_id, '_dame_poll_end_date', true );
				echo $date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ) : '—';
				break;

			case 'poll_votes':
				global $wpdb;
				$table_votes = $wpdb->prefix . 'dame_poll_votes';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$total = $wpdb->get_var( $wpdb->prepare( 
					"SELECT COUNT(*) 
					 FROM {$table_votes} v
					 INNER JOIN {$wpdb->posts} p ON v.recipient_id = p.ID
					 WHERE v.poll_id = %d AND p.post_status = 'publish'", 
					$post_id 
				) );
				echo intval( $total );
				break;

			case 'poll_shortcode':
				$slug = get_post_field( 'post_name', $post_id );
				echo '<input type="text" readonly value="[dame_sondage slug=&quot;' . esc_attr( $slug ) . '&quot;]" class="large-text code" onclick="this.select()">';
				break;
		}
	}
}
