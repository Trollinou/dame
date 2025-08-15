<?php
/**
 * Handles the display of content on single 'dame_cours' pages.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Appends the list of course items to the content on single course pages.
 *
 * @param string $content The post content.
 * @return string The modified post content.
 */
function dame_display_single_course_content( $content ) {
    if ( is_singular( 'dame_cours' ) ) {
        $course_id = get_the_ID();
        $course_difficulty = get_post_meta( $course_id, '_dame_difficulty', true );
        $course_items_raw = get_post_meta( $course_id, '_dame_course_items', true );

        if ( ! empty( $course_items_raw ) && is_array( $course_items_raw ) ) {
            $items_html = '';
            foreach ( $course_items_raw as $item ) {
                $item_id = $item['id'];
                $item_difficulty = get_post_meta( $item_id, '_dame_difficulty', true );

                // Only display if difficulty matches.
                if ( $course_difficulty && $course_difficulty == $item_difficulty ) {
                    $post_obj = get_post( $item_id );
                    if ( $post_obj ) {
                        $post_type_name = 'dame_' . $item['type'];
                        $post_type_obj = get_post_type_object( $post_type_name );
                        $type_label = $post_type_obj ? $post_type_obj->labels->singular_name : ucfirst( $item['type'] );

                        $items_html .= '<li>';
                        $items_html .= '<a href="' . esc_url( get_permalink( $post_obj->ID ) ) . '">' . esc_html( $post_obj->post_title ) . '</a>';
                        $items_html .= ' <span class="dame-course-item-type">(' . esc_html( $type_label ) . ')</span>';
                        $items_html .= '</li>';
                    }
                }
            }

            if ( ! empty( $items_html ) ) {
                $course_html = '<div class="dame-course-content-list">';
                $course_html .= '<h3>' . __( 'Contenu du cours', 'dame' ) . '</h3>';
                $course_html .= '<ol>' . $items_html . '</ol>';
                $course_html .= '</div>';
                $content .= $course_html;
            }
        }
    }
    return $content;
}
add_filter( 'the_content', 'dame_display_single_course_content' );
