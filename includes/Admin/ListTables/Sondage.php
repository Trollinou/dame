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
	public function init() {
		add_filter( 'manage_dame_sondage_posts_columns', [ $this, 'add_columns' ] );
		add_action( 'manage_dame_sondage_posts_custom_column', [ $this, 'display_columns' ], 10, 2 );
	}

	/**
	 * Add custom columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array New columns.
	 */
	public function add_columns( $columns ) {
		$new_columns = [];
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'title' === $key ) {
				$new_columns['sondage_slug'] = __( 'Slug', 'dame' );
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
	public function display_columns( $column, $post_id ) {
		if ( 'sondage_slug' === $column ) {
			$post = get_post( $post_id );
			echo esc_html( $post->post_name );
		}
	}
}
