<?php
/**
 * Handles the public-facing shortcodes for the plugin.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Renders the [dame_exercices] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string The shortcode output.
 */
function dame_exercices_shortcode( $atts ) {
    wp_enqueue_script( 'dame-exercices', plugin_dir_url( __FILE__ ) . '../public/js/exercices.js', array( 'jquery' ), DAME_VERSION, true );
    wp_localize_script( 'dame-exercices', 'dame_exercices_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'dame_exercice_nonce' )
    ) );

    ob_start();
    ?>
    <div id="dame-exercices-wrapper">
        <div id="dame-exercices-filters">
            <div class="dame-filter-item">
                <label for="dame-difficulty-filter"><?php _e( 'Difficulté:', 'dame' ); ?></label>
                <select id="dame-difficulty-filter">
                    <option value="any"><?php _e( 'Toutes', 'dame' ); ?></option>
                    <option value="1"><?php _e( '1 - Très facile', 'dame' ); ?></option>
                    <option value="2"><?php _e( '2 - Facile', 'dame' ); ?></option>
                    <option value="3"><?php _e( '3 - Modéré', 'dame' ); ?></option>
                    <option value="4"><?php _e( '4 - Difficile', 'dame' ); ?></option>
                    <option value="5"><?php _e( '5 - Très Difficile', 'dame' ); ?></option>
                    <option value="6"><?php _e( '6 - Expert', 'dame' ); ?></option>
                </select>
            </div>
            <div class="dame-filter-item">
                <label for="dame-category-filter"><?php _e( 'Catégorie:', 'dame' ); ?></label>
                <?php
                wp_dropdown_categories( array(
                    'taxonomy'        => 'dame_chess_category',
                    'name'            => 'dame-category-filter',
                    'id'              => 'dame-category-filter',
                    'show_option_all' => __( 'Toutes les catégories', 'dame' ),
                    'hierarchical'    => true,
                    'value_field'     => 'slug',
                ) );
                ?>
            </div>
            <button id="dame-start-exercices"><?php _e( 'Commencer les exercices', 'dame' ); ?></button>
        </div>

        <div id="dame-exercice-display">
            <!-- Exercise content will be loaded here via AJAX -->
        </div>

        <div id="dame-exercice-score">
            <h3><?php _e( 'Votre Score', 'dame' ); ?></h3>
            <p>
                <?php _e( 'Correct:', 'dame' ); ?> <span id="dame-score-correct">0</span> /
                <?php _e( 'Tentés:', 'dame' ); ?> <span id="dame-score-attempted">0</span>
            </p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'dame_exercices', 'dame_exercices_shortcode' );

/**
 * AJAX handler to fetch a random exercice.
 */
function dame_fetch_exercice_ajax_handler() {
    check_ajax_referer( 'dame_exercice_nonce', 'nonce' );

    $difficulty = isset( $_POST['difficulty'] ) ? sanitize_key( $_POST['difficulty'] ) : 'any';
    $category_slug = isset( $_POST['category'] ) ? sanitize_key( $_POST['category'] ) : 'any';
    $exclude_id = isset( $_POST['exclude'] ) ? intval( $_POST['exclude'] ) : 0;

    $args = array(
        'post_type'      => 'dame_exercice',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'rand',
        'post__not_in'   => array( $exclude_id ),
    );

    $meta_query = array();
    if ( $difficulty !== 'any' ) {
        $meta_query[] = array(
            'key'     => '_dame_difficulty',
            'value'   => $difficulty,
            'compare' => '=',
        );
    }

    $tax_query = array();
    if ( $category_slug !== 'any' && !empty($category_slug) ) {
        $tax_query[] = array(
            'taxonomy' => 'dame_chess_category',
            'field'    => 'slug',
            'terms'    => $category_slug,
        );
    }

    if ( ! empty( $meta_query ) ) {
        $args['meta_query'] = $meta_query;
    }
    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = $tax_query;
    }

    $exercice_query = new WP_Query( $args );

    if ( $exercice_query->have_posts() ) {
        $exercice_query->the_post();
        $exercice_id = get_the_ID();

        ob_start();
        ?>
        <form id="dame-exercice-form">
            <input type="hidden" id="dame-exercice-id" value="<?php echo esc_attr( $exercice_id ); ?>">
            <h2><?php the_title(); ?></h2>
            <div class="dame-exercice-content">
                <?php echo apply_filters( 'the_content', get_the_content() ); ?>
            </div>

            <?php
            $question_type = get_post_meta( $exercice_id, '_dame_question_type', true );
            $answers = get_post_meta( $exercice_id, '_dame_answers', true );
            $input_type = $question_type === 'qcm_multiple' ? 'checkbox' : 'radio';

            if ( !empty($answers) && is_array($answers) ) {
                echo '<div class="dame-answers">';
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

            <button type="button" id="dame-submit-answer"><?php _e( 'Valider la réponse', 'dame' ); ?></button>
            <button type="button" id="dame-next-exercice" style="display:none;"><?php _e( 'Exercice Suivant', 'dame' ); ?></button>
        </form>
        <div id="dame-exercice-solution" style="display:none; border-top: 1px solid #ccc; margin-top: 20px; padding-top: 15px;"></div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html, 'id' => $exercice_id ) );

    } else {
        wp_send_json_error( __( 'Aucun exercice trouvé avec ces critères.', 'dame' ) );
    }

    wp_reset_postdata();
    wp_die();
}
add_action( 'wp_ajax_dame_fetch_exercice', 'dame_fetch_exercice_ajax_handler' );
add_action( 'wp_ajax_nopriv_dame_fetch_exercice', 'dame_fetch_exercice_ajax_handler' );


/**
 * AJAX handler to check the user's answer.
 */
function dame_check_answer_ajax_handler() {
    check_ajax_referer( 'dame_exercice_nonce', 'nonce' );

    if ( !isset($_POST['exercise_id']) ) {
        wp_send_json_error( 'ID d\'exercice manquant.' );
    }

    $exercise_id = intval( $_POST['exercise_id'] );
    parse_str($_POST['answer'], $submitted_data);
    $user_answers_indices = isset($submitted_data['dame_answer']) ? array_map('intval', (array)$submitted_data['dame_answer']) : [];

    $correct_answers_indices = array();
    $all_answers = get_post_meta( $exercise_id, '_dame_answers', true );
    if (is_array($all_answers)) {
        foreach ($all_answers as $index => $answer) {
            if ($answer['correct']) {
                $correct_answers_indices[] = $index;
            }
        }
    }

    // Sort both arrays to ensure proper comparison
    sort($user_answers_indices);
    sort($correct_answers_indices);

    $is_correct = ($user_answers_indices === $correct_answers_indices);

    $solution_html = get_post_meta( $exercise_id, '_dame_solution', true );
    $solution_html = apply_filters('the_content', $solution_html); // Process shortcodes etc.

    $response_data = array(
        'correct' => $is_correct,
        'solution' => $solution_html,
        'user_selected_indices' => $user_answers_indices,
        'correct_indices' => $correct_answers_indices,
    );

    if ($is_correct) {
        $response_data['message'] = __('Bonne réponse !', 'dame');
    } else {
        $response_data['message'] = __('Réponse incorrecte.', 'dame');
        // The textual list of correct answers is no longer sent.
        // This is now handled by highlighting the correct answers directly in the form.
    }

    wp_send_json_success($response_data);

    wp_die();
}
add_action( 'wp_ajax_dame_check_answer', 'dame_check_answer_ajax_handler' );
add_action( 'wp_ajax_nopriv_dame_check_answer', 'dame_check_answer_ajax_handler' );


/**
 * Replaces chess piece shortcodes with their corresponding Unicode characters.
 *
 * This function is hooked into `the_content`, `widget_text_content`, and `comment_text`
 * to ensure that chess piece representations are consistent across the site.
 *
 * @param string $content The content to filter.
 * @return string The filtered content with chess piece shortcodes replaced.
 */
function dame_chess_pieces_shortcodes_filter( $content ) {
    $chess_pieces = array(
        // Pièces Blanches
        '[RB]' => '<span class="dame-chess-piece">♔</span>', // U+2654 - Roi Blanc
        '[DB]' => '<span class="dame-chess-piece">♕</span>', // U+2655 - Dame Blanche
        '[TB]' => '<span class="dame-chess-piece">♖</span>', // U+2656 - Tour Blanche
        '[FB]' => '<span class="dame-chess-piece">♗</span>', // U+2657 - Fou Blanc
        '[CB]' => '<span class="dame-chess-piece">♘</span>', // U+2658 - Cavalier Blanc
        '[PB]' => '<span class="dame-chess-piece">♙</span>', // U+2659 - Pion Blanc
        // Pièces Noires
        '[RN]' => '<span class="dame-chess-piece">♚</span>', // U+265A - Roi Noir
        '[DN]' => '<span class="dame-chess-piece">♛</span>', // U+265B - Dame Noire
        '[TN]' => '<span class="dame-chess-piece">♜</span>', // U+265C - Tour Noire
        '[FN]' => '<span class="dame-chess-piece">♝</span>', // U+265D - Fou Noir
        '[CN]' => '<span class="dame-chess-piece">♞</span>', // U+265E - Cavalier Noir
        '[PN]' => '<span class="dame-chess-piece">♟</span>', // U+265F - Pion Noir
    );

    // Using str_replace is efficient for simple replacements.
    // The keys and values are extracted to be used in the function.
    return str_replace( array_keys( $chess_pieces ), array_values( $chess_pieces ), $content );
}

add_filter( 'the_content', 'dame_chess_pieces_shortcodes_filter' );
add_filter( 'widget_text_content', 'dame_chess_pieces_shortcodes_filter' ); // For block-based widgets
add_filter( 'comment_text', 'dame_chess_pieces_shortcodes_filter' );
