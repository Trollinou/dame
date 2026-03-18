<?php
/**
 * Common custom columns for multiple CPTs.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Gets a color based on difficulty level.
 *
 * @param int $level The difficulty level (1-6).
 * @return string The hex color code.
 */
function dame_get_difficulty_color( $level ) {
    $colors = [
        1 => '#4CAF50', // Green
        2 => '#8BC34A', // Light Green
        3 => '#FFC107', // Amber
        4 => '#FF9800', // Orange
        5 => '#F44336', // Red
        6 => '#B71C1C', // Dark Red
    ];
    return $colors[ (int) $level ] ?? '#9E9E9E'; // Grey for default
}

/**
 * Adds a 'Difficulté' column to CPT admin lists.
 *
 * @param array $columns The existing columns.
 * @return array The modified columns.
 */
function dame_add_difficulty_column( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $title ) {
        $new_columns[ $key ] = $title;
        if ( 'title' === $key ) {
            $new_columns['dame_difficulty'] = __( 'Difficulté', 'dame' );
        }
    }
    return $new_columns;
}

/**
 * Renders the content for the 'Difficulté' column.
 *
 * @param string $column  The name of the column to render.
 * @param int    $post_id The ID of the post.
 */
function dame_render_difficulty_column( $column, $post_id ) {
    if ( 'dame_difficulty' === $column ) {
        $difficulty = get_post_meta( $post_id, '_dame_difficulty', true );
        if ( $difficulty ) {
            $color = dame_get_difficulty_color( $difficulty );
            echo '<span class="dashicons dashicons-star-filled" style="color:' . esc_attr( $color ) . ';"></span> ' . esc_html( $difficulty );
        } else {
            echo '—';
        }
    }
}

// Apply to all relevant CPTs
$cpts = ['dame_lecon', 'dame_exercice', 'dame_cours'];
foreach ( $cpts as $cpt ) {
    add_filter( "manage_edit-{$cpt}_columns", 'dame_add_difficulty_column' );
    add_action( "manage_{$cpt}_posts_custom_column", 'dame_render_difficulty_column', 10, 2 );
}
