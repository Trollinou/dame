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
        $course_items_raw = get_post_meta( get_the_ID(), '_dame_course_items', true );

        if ( ! empty( $course_items_raw ) && is_array( $course_items_raw ) ) {
            $course_html = '<div class="dame-course-content-list">';
            $course_html .= '<h3>' . __( 'Contenu du cours', 'dame' ) . '</h3>';
            $course_html .= '<ol>';

            foreach ( $course_items_raw as $item ) {
                $post_obj = get_post( $item['id'] );
                if ( $post_obj ) {
                    $post_type_obj = get_post_type_object( $item['type'] );
                    $type_label = $post_type_obj ? $post_type_obj->labels->singular_name : ucfirst( $item['type'] );

                    $course_html .= '<li>';
                    $course_html .= '<a href="' . esc_url( get_permalink( $post_obj->ID ) ) . '">' . esc_html( $post_obj->post_title ) . '</a>';
                    $course_html .= ' <span class="dame-course-item-type">(' . esc_html( $type_label ) . ')</span>';
                    $course_html .= '</li>';
                }
            }

            $course_html .= '</ol>';
            $course_html .= '</div>';

            $content .= $course_html;
        }
    }
    return $content;
}
add_filter( 'the_content', 'dame_display_single_course_content' );
