<?php
/**
 * Handles the display of the answer form on single 'dame_exercice' pages.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Appends the exercise answer form to the content on single exercise pages.
 *
 * @param string $content The post content.
 * @return string The modified post content.
 */
function dame_display_single_exercice_form( $content ) {
    if ( is_singular( 'dame_exercice' ) ) {
        $exercice_id = get_the_ID();

        ob_start();
        ?>
        <div id="dame-single-exercice-wrapper">
            <form id="dame-exercice-form">
                <input type="hidden" id="dame-exercice-id" value="<?php echo esc_attr( $exercice_id ); ?>">

                <?php
                $question_type = get_post_meta( $exercice_id, '_dame_question_type', true );
                $answers = get_post_meta( $exercice_id, '_dame_answers', true );
                $input_type = $question_type === 'qcm_multiple' ? 'checkbox' : 'radio';

                if ( !empty($answers) && is_array($answers) ) {
                    echo '<div class="dame-answers">';
                    echo '<h4>' . __("Choisissez votre réponse :", "dame") . '</h4>';
                    foreach ($answers as $index => $answer) {
                        ?>
                        <label>
                            <input type="<?php echo $input_type; ?>" name="dame_answer[]" value="<?php echo esc_attr($index); ?>">
                            <?php echo wp_kses_post( dame_chess_pieces_shortcodes_filter( $answer['text'] ) ); ?>
                        </label><br>
                        <?php
                    }
                    echo '</div>';
                }
                ?>

                <button type="button" id="dame-submit-answer" class="button button-primary"><?php _e( 'Valider la réponse', 'dame' ); ?></button>
            </form>
            <div id="dame-exercice-feedback" style="margin-top: 20px;"></div>
            <div id="dame-exercice-solution" style="display:none; border-top: 1px solid #ccc; margin-top: 20px; padding-top: 15px;"></div>
        </div>
        <?php
        $form_html = ob_get_clean();

        $content .= $form_html;
    }
    return $content;
}
add_filter( 'the_content', 'dame_display_single_exercice_form' );

/**
 * Enqueues scripts for the single exercise page.
 */
function dame_enqueue_single_exercice_scripts() {
    if ( is_singular( 'dame_exercice' ) ) {
        wp_enqueue_script(
            'dame-single-exercice',
            plugin_dir_url( __FILE__ ) . '../public/js/single-exercice.js',
            array( 'jquery' ),
            DAME_VERSION,
            true
        );
        wp_localize_script(
            'dame-single-exercice',
            'dame_single_exercice_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'dame_exercice_nonce' ) // Reusing the same nonce
            )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'dame_enqueue_single_exercice_scripts' );
