<?php
/**
 * Handles lesson completion tracking.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Adds a "Mark as Completed" button to the end of a lesson's content.
 *
 * @param string $content The post content.
 * @return string The modified post content.
 */
function dame_add_lesson_completion_button( $content ) {
    // Check if it's a single 'dame_lecon' and user has access
    if ( is_singular( 'dame_lecon' ) && is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $user_roles = (array) $current_user->roles;
        $allowed_roles = array( 'membre', 'entraineur', 'administrator' );
        $has_access = count( array_intersect( $user_roles, $allowed_roles ) ) > 0;

        if ( $has_access ) {
            $lesson_id = get_the_ID();
            $completed_lessons = get_user_meta( get_current_user_id(), 'dame_completed_lessons', true );

            if ( ! is_array( $completed_lessons ) ) {
                $completed_lessons = array();
            }

            if ( in_array( $lesson_id, $completed_lessons ) ) {
                // Lesson is already completed
                $button = '<p class="dame-lesson-completed">' . __( "Vous avez déjà terminé cette leçon.", "dame" ) . '</p>';
            } else {
                // Lesson not completed, show the button
                $button = '<button id="dame-complete-lesson-btn" data-lesson-id="' . esc_attr( $lesson_id ) . '">' . __( "Marquer comme terminée", "dame" ) . '</button>';
                $button .= '<div id="dame-lesson-completion-feedback"></div>';
                // We'll need to add JS for this button to work.
            }
            $content .= $button;
        }
    }
    return $content;
}
add_filter( 'the_content', 'dame_add_lesson_completion_button' );

/**
 * Enqueue scripts for lesson completion.
 */
function dame_enqueue_lesson_completion_scripts() {
    if ( is_singular( 'dame_lecon' ) ) {
        wp_enqueue_script( 'dame-lesson-completion', plugin_dir_url( __FILE__ ) . '../public/js/lesson-completion.js', array( 'jquery' ), DAME_VERSION, true );
        wp_localize_script( 'dame-lesson-completion', 'dame_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dame_complete_lesson_nonce' )
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'dame_enqueue_lesson_completion_scripts' );

/**
 * AJAX handler for marking a lesson as complete.
 */
function dame_complete_lesson_ajax_handler() {
    // Check nonce
    check_ajax_referer( 'dame_complete_lesson_nonce', 'nonce' );

    if ( isset( $_POST['lesson_id'] ) && is_user_logged_in() ) {
        $lesson_id = intval( $_POST['lesson_id'] );
        $user_id = get_current_user_id();

        $completed_lessons = get_user_meta( $user_id, 'dame_completed_lessons', true );
        if ( ! is_array( $completed_lessons ) ) {
            $completed_lessons = array();
        }

        if ( ! in_array( $lesson_id, $completed_lessons ) ) {
            $completed_lessons[] = $lesson_id;
            update_user_meta( $user_id, 'dame_completed_lessons', $completed_lessons );
            wp_send_json_success( __( "Leçon marquée comme terminée !", "dame" ) );
        } else {
            wp_send_json_error( __( "Leçon déjà terminée.", "dame" ) );
        }
    }
    wp_die();
}
add_action( 'wp_ajax_dame_complete_lesson', 'dame_complete_lesson_ajax_handler' );
