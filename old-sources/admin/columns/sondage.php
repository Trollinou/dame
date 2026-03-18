<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom columns to the sondage list table.
 *
 * @param array $columns The existing columns.
 * @return array The modified columns.
 */
function dame_add_sondage_columns( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $title ) {
        $new_columns[ $key ] = $title;
        if ( 'title' === $key ) {
            $new_columns['sondage_slug'] = __( 'Slug', 'dame' );
        }
    }
    return $new_columns;
}
add_filter( 'manage_sondage_posts_columns', 'dame_add_sondage_columns' );

/**
 * Display the content for custom sondage columns.
 *
 * @param string $column The name of the column.
 * @param int    $post_id The ID of the post.
 */
function dame_display_sondage_columns( $column, $post_id ) {
    if ( 'sondage_slug' === $column ) {
        $post = get_post( $post_id );
        echo esc_html( $post->post_name );
    }
}
add_action( 'manage_sondage_posts_custom_column', 'dame_display_sondage_columns', 10, 2 );
