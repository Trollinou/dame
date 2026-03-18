<?php
/**
 * Custom columns for the WP User list.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Adds a custom column to the user list table.
 *
 * @param array $columns The existing user list columns.
 * @return array The modified user list columns.
 */
function dame_add_user_lesson_completion_column( $columns ) {
    if ( current_user_can( 'entraineur' ) || current_user_can( 'administrator' ) ) {
        $columns['dame_completed_lessons'] = __( 'Leçons Terminées', 'dame' );
    }
    return $columns;
}
add_filter( 'manage_users_columns', 'dame_add_user_lesson_completion_column' );

/**
 * Renders the content for the custom user list column.
 *
 * @param string $value       The column's current value.
 * @param string $column_name The name of the column.
 * @param int    $user_id     The ID of the user.
 * @return string The column's new value.
 */
function dame_render_user_lesson_completion_column( $value, $column_name, $user_id ) {
    if ( 'dame_completed_lessons' === $column_name && ( current_user_can( 'entraineur' ) || current_user_can( 'administrator' ) ) ) {
        $completed_lessons = get_user_meta( $user_id, 'dame_completed_lessons', true );
        if ( is_array( $completed_lessons ) ) {
            return count( $completed_lessons );
        }
        return 0;
    }
    return $value;
}
add_filter( 'manage_users_custom_column', 'dame_render_user_lesson_completion_column', 10, 3 );
