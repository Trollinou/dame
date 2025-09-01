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

/**
 * Renders the [dame_fiche_inscription] shortcode for the pre-registration form.
 *
 * @param array $atts Shortcode attributes.
 * @return string The shortcode output.
 */
function dame_fiche_inscription_shortcode( $atts ) {
	// Enqueue scripts and styles for the form
	wp_enqueue_style( 'dame-public-styles', plugin_dir_url( __FILE__ ) . '../public/css/dame-public-styles.css', array(), DAME_VERSION );

	wp_enqueue_script( 'dame-geo-autocomplete', plugin_dir_url( __FILE__ ) . '../admin/js/geo-autocomplete.js', array(), DAME_VERSION, true );
	wp_enqueue_script( 'dame-ign-autocomplete', plugin_dir_url( __FILE__ ) . '../admin/js/ign-autocomplete.js', array(), DAME_VERSION, true );
	wp_enqueue_script( 'dame-pre-inscription', plugin_dir_url( __FILE__ ) . '../public/js/pre-inscription-form.js', array( 'dame-geo-autocomplete', 'dame-ign-autocomplete' ), DAME_VERSION, true );

	wp_localize_script(
		'dame-pre-inscription',
		'dame_pre_inscription_ajax',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		)
	);

	ob_start();
	?>
	<div id="dame-pre-inscription-form-wrapper">
		<div id="dame-form-messages" style="display:none; padding: 1em; margin-bottom: 1em;"></div>
		<form id="dame-pre-inscription-form" class="dame-form">

			<?php wp_nonce_field( 'dame_pre_inscription_nonce', 'dame_nonce' ); ?>

			<h3><?php _e( "Informations sur l'adhérent", 'dame' ); ?></h3>

			<p>
				<label for="dame_first_name"><?php _e( 'Prénom', 'dame' ); ?> <span class="required">*</span></label>
				<input type="text" id="dame_first_name" name="dame_first_name" required>
			</p>
			<p>
				<label for="dame_last_name"><?php _e( 'Nom', 'dame' ); ?> <span class="required">*</span></label>
				<input type="text" id="dame_last_name" name="dame_last_name" required>
			</p>
			<p>
				<label for="dame_birth_date"><?php _e( 'Date de naissance', 'dame' ); ?> <span class="required">*</span></label>
				<input type="date" id="dame_birth_date" name="dame_birth_date" required>
			</p>
			<p>
				<label for="dame_license_type"><?php _e( 'Type de licence', 'dame' ); ?> <span class="required">*</span></label>
				<select id="dame_license_type" name="dame_license_type" required>
					<option value="A"><?php _e( 'Licence A (Cours + Compétition)', 'dame' ); ?></option>
					<option value="B"><?php _e( 'Licence B (Jeu libre)', 'dame' ); ?></option>
				</select>
			</p>

			<p>
				<label for="dame_birth_city"><?php _e( 'Commune de naissance', 'dame' ); ?></label>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_birth_city" name="dame_birth_city" class="regular-text">
				</div>
			</p>
			<p>
				<label><?php _e( 'Sexe', 'dame' ); ?> <span class="required">*</span></label>
				<br>
				<label><input type="radio" name="dame_sexe" value="Masculin" checked required> <?php _e( 'Masculin', 'dame' ); ?></label>
				<label><input type="radio" name="dame_sexe" value="Féminin"> <?php _e( 'Féminin', 'dame' ); ?></label>
				<label><input type="radio" name="dame_sexe" value="Non précisé"> <?php _e( 'Non précisé', 'dame' ); ?></label>
			</p>
			<p>
				<label for="dame_email"><?php _e( 'Email', 'dame' ); ?> <span class="required">*</span></label>
				<input type="email" id="dame_email" name="dame_email" required>
			</p>
			<p>
				<label for="dame_phone_number"><?php _e( 'Numéro de téléphone', 'dame' ); ?> <span class="required">*</span></label>
				<input type="tel" id="dame_phone_number" name="dame_phone_number" required>
			</p>
			<p>
				<label for="dame_address_1"><?php _e( 'Adresse', 'dame' ); ?> <span class="required">*</span></label>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_address_1" name="dame_address_1" required>
				</div>
			</p>
			<p>
				<label for="dame_address_2"><?php _e( 'Complément', 'dame' ); ?></label>
				<input type="text" id="dame_address_2" name="dame_address_2">
			</p>
			<p>
				<label for="dame_postal_code"><?php _e( 'Code Postal', 'dame' ); ?></label>
				<input type="text" id="dame_postal_code" name="dame_postal_code" style="width: 8em;">
			</p>
			<p>
				<label for="dame_city"><?php _e( 'Ville', 'dame' ); ?> <span class="required">*</span></label>
				<input type="text" id="dame_city" name="dame_city" required>
			</p>
			<p>
				<label for="dame_taille_vetements"><?php _e( 'Taille de vêtements', 'dame' ); ?></label>
				<select id="dame_taille_vetements" name="dame_taille_vetements">
					<?php
					$taille_vetements_options = array( 'Non renseigné', '8/10', '10/12', '12/14', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL' );
					foreach ( $taille_vetements_options as $option ) {
						echo '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
					}
					?>
				</select>
			</p>

			<div id="dame-dynamic-fields" style="display:none;">
				<div id="dame-adherent-majeur-fields" style="display:none;">
					<h3><?php _e( 'Informations complémentaires (Majeur)', 'dame' ); ?></h3>
					<p>
						<label for="dame_profession"><?php _e( 'Profession', 'dame' ); ?></label>
						<input type="text" id="dame_profession" name="dame_profession">
					</p>
				</div>

				<div id="dame-adherent-mineur-fields" style="display:none;">
					<h3 style="display: flex; align-items: center; flex-wrap: wrap;"><?php _e( 'Représentant Légal 1', 'dame' ); ?>
						<button type="button" class="dame-copy-button" data-rep-id="1" style="background-color: #3ec0f0; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 5px; white-space: nowrap; font-size: 13px; margin-left: 10px;"><?php _e( '✂️ Reporter les données de l\'Adhérent ✂️', 'dame' ); ?></button>
					</h3>
					<p><label for="dame_legal_rep_1_first_name"><?php _e( 'Prénom', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_first_name" name="dame_legal_rep_1_first_name"></p>
					<p><label for="dame_legal_rep_1_last_name"><?php _e( 'Nom', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_last_name" name="dame_legal_rep_1_last_name"></p>
					<p><label for="dame_legal_rep_1_email"><?php _e( 'Email', 'dame' ); ?></label><input type="email" id="dame_legal_rep_1_email" name="dame_legal_rep_1_email"></p>
					<p><label for="dame_legal_rep_1_phone"><?php _e( 'Numéro de téléphone', 'dame' ); ?></label><input type="tel" id="dame_legal_rep_1_phone" name="dame_legal_rep_1_phone"></p>
					<p><label for="dame_legal_rep_1_address_1"><?php _e( 'Adresse', 'dame' ); ?></label><div class="dame-autocomplete-wrapper"><input type="text" id="dame_legal_rep_1_address_1" name="dame_legal_rep_1_address_1"></div></p>
					<p><label for="dame_legal_rep_1_address_2"><?php _e( 'Complément', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_address_2" name="dame_legal_rep_1_address_2"></p>
					<p><label for="dame_legal_rep_1_postal_code"><?php _e( 'Code Postal', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_postal_code" name="dame_legal_rep_1_postal_code"></p>
					<p><label for="dame_legal_rep_1_city"><?php _e( 'Ville', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_city" name="dame_legal_rep_1_city"></p>
					<p><label for="dame_legal_rep_1_profession"><?php _e( 'Profession', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_profession" name="dame_legal_rep_1_profession"></p>
					<p><em><?php _e( 'Dans le cadre de notre politique de prévention des violences sexistes et sexuelles, nous demandons aux parents susceptibles d’accompagner des mineurs de se soumettre à un contrôle d’honorabilité. À cette fin, nous vous remercions de bien vouloir renseigner les deux champs ci-dessous si vous êtes concerné.', 'dame' ); ?></em></p>
					<p><label for="dame_legal_rep_1_date_naissance"><?php _e( 'Date de naissance', 'dame' ); ?></label><input type="date" id="dame_legal_rep_1_date_naissance" name="dame_legal_rep_1_date_naissance"></p>
					<p><label for="dame_legal_rep_1_commune_naissance"><?php _e( 'Commune de naissance', 'dame' ); ?></label><div class="dame-autocomplete-wrapper"><input type="text" id="dame_legal_rep_1_commune_naissance" name="dame_legal_rep_1_commune_naissance"></div></p>

					<h3 style="display: flex; align-items: center; flex-wrap: wrap;"><?php _e( 'Représentant Légal 2', 'dame' ); ?>
						<button type="button" class="dame-copy-button" data-rep-id="2" style="background-color: #3ec0f0; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 5px; white-space: nowrap; font-size: 13px; margin-left: 10px;"><?php _e( '✂️ Reporter les données de l\'Adhérent ✂️', 'dame' ); ?></button>
					</h3>
					<p><label for="dame_legal_rep_2_first_name"><?php _e( 'Prénom', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_first_name" name="dame_legal_rep_2_first_name"></p>
					<p><label for="dame_legal_rep_2_last_name"><?php _e( 'Nom', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_last_name" name="dame_legal_rep_2_last_name"></p>
					<p><label for="dame_legal_rep_2_email"><?php _e( 'Email', 'dame' ); ?></label><input type="email" id="dame_legal_rep_2_email" name="dame_legal_rep_2_email"></p>
					<p><label for="dame_legal_rep_2_phone"><?php _e( 'Numéro de téléphone', 'dame' ); ?></label><input type="tel" id="dame_legal_rep_2_phone" name="dame_legal_rep_2_phone"></p>
					<p><label for="dame_legal_rep_2_address_1"><?php _e( 'Adresse', 'dame' ); ?></label><div class="dame-autocomplete-wrapper"><input type="text" id="dame_legal_rep_2_address_1" name="dame_legal_rep_2_address_1"></div></p>
					<p><label for="dame_legal_rep_2_address_2"><?php _e( 'Complément', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_address_2" name="dame_legal_rep_2_address_2"></p>
					<p><label for="dame_legal_rep_2_postal_code"><?php _e( 'Code Postal', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_postal_code" name="dame_legal_rep_2_postal_code"></p>
					<p><label for="dame_legal_rep_2_city"><?php _e( 'Ville', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_city" name="dame_legal_rep_2_city"></p>
					<p><label for="dame_legal_rep_2_profession"><?php _e( 'Profession', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_profession" name="dame_legal_rep_2_profession"></p>
					<p><em><?php _e( 'Dans le cadre de notre politique de prévention des violences sexistes et sexuelles, nous demandons aux parents susceptibles d’accompagner des mineurs de se soumettre à un contrôle d’honorabilité. À cette fin, nous vous remercions de bien vouloir renseigner les deux champs ci-dessous si vous êtes concerné.', 'dame' ); ?></em></p>
					<p><label for="dame_legal_rep_2_date_naissance"><?php _e( 'Date de naissance', 'dame' ); ?></label><input type="date" id="dame_legal_rep_2_date_naissance" name="dame_legal_rep_2_date_naissance"></p>
					<p><label for="dame_legal_rep_2_commune_naissance"><?php _e( 'Commune de naissance', 'dame' ); ?></label><div class="dame-autocomplete-wrapper"><input type="text" id="dame_legal_rep_2_commune_naissance" name="dame_legal_rep_2_commune_naissance"></div></p>
				</div>
			</div>

			<h3>
				<?php _e( "Questionnaire de santé", 'dame' ); ?>
				<span id="health-questionnaire-link-container" style="display: none; margin-left: 10px; font-weight: normal;">
					<a href="#" id="health-questionnaire-link" target="_blank" style="font-size: initial; color: blue; text-decoration: underline;"></a>
				</span>
			</h3>
			<p>
				<label><input type="radio" name="dame_health_questionnaire" value="non" required> <?php _e( "J’ai répondu NON partout", 'dame' ); ?></label>
				<br>
				<label><input type="radio" name="dame_health_questionnaire" value="oui"> <?php _e( "J’ai au moins une réponse à OUI", 'dame' ); ?></label>
			</p>

			<p>
				<em><?php _e( "En validant ma préinscription, je reconnais avoir pris connaissance du règlement intérieur de l’Association Échiquier Lédonien et m’engage à le respecter..", 'dame' ); ?></em>
			</p>

			<p>
				<button type="submit"><?php _e( 'Valider ma préinscription', 'dame' ); ?></button>
			</p>

		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'dame_fiche_inscription', 'dame_fiche_inscription_shortcode' );

/**
 * AJAX handler for the pre-inscription form submission.
 */
function dame_handle_pre_inscription_submission() {
	// 1. Security Check: Verify nonce
	if ( ! isset( $_POST['dame_nonce'] ) || ! wp_verify_nonce( $_POST['dame_nonce'], 'dame_pre_inscription_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( "La vérification de sécurité a échoué. Veuillez rafraîchir la page.", 'dame' ) ), 403 );
	}

	// 2. Validation
	$errors = array();
	$required_fields = array(
		'dame_first_name'           => __( "Le prénom est obligatoire.", 'dame' ),
		'dame_last_name'            => __( "Le nom est obligatoire.", 'dame' ),
		'dame_birth_date'           => __( "La date de naissance est obligatoire.", 'dame' ),
		'dame_license_type'         => __( "Le type de licence est obligatoire.", 'dame' ),
		'dame_sexe'                 => __( "Le sexe est obligatoire.", 'dame' ),
		'dame_email'                => __( "L'email est obligatoire.", 'dame' ),
		'dame_phone_number'         => __( "Le numéro de téléphone est obligatoire.", 'dame' ),
		'dame_address_1'            => __( "L'adresse est obligatoire.", 'dame' ),
		'dame_city'                 => __( "La ville est obligatoire.", 'dame' ),
		'dame_health_questionnaire' => __( "La réponse au questionnaire de santé est obligatoire.", 'dame' ),
	);

	foreach ( $required_fields as $field_key => $error_message ) {
		if ( empty( $_POST[ $field_key ] ) ) {
			$errors[] = $error_message;
		}
	}

	// Conditional validation for minors
	if ( ! empty( $_POST['dame_birth_date'] ) ) {
		$birth_date = DateTime::createFromFormat( 'Y-m-d', $_POST['dame_birth_date'] );
		if ( $birth_date ) {
			$today = new DateTime();
			$age   = $today->diff( $birth_date )->y;

			if ( $age < 18 ) {
				$rep1_required_fields = array(
					'dame_legal_rep_1_first_name' => __( "Le prénom du représentant légal 1 est obligatoire.", 'dame' ),
					'dame_legal_rep_1_last_name'  => __( "Le nom du représentant légal 1 est obligatoire.", 'dame' ),
					'dame_legal_rep_1_email'      => __( "L'email du représentant légal 1 est obligatoire.", 'dame' ),
					'dame_legal_rep_1_phone'      => __( "Le téléphone du représentant légal 1 est obligatoire.", 'dame' ),
					'dame_legal_rep_1_address_1'  => __( "L'adresse du représentant légal 1 est obligatoire.", 'dame' ),
					'dame_legal_rep_1_city'       => __( "La ville du représentant légal 1 est obligatoire.", 'dame' ),
				);

				foreach ( $rep1_required_fields as $field_key => $error_message ) {
					if ( empty( $_POST[ $field_key ] ) ) {
						$errors[] = $error_message;
					}
				}
			}
		}
	}

	// Email format validation (only if not empty, required check is above)
	if ( ! empty( $_POST['dame_email'] ) && ! is_email( $_POST['dame_email'] ) ) {
		$errors[] = __( "L'adresse email de l'adhérent n'est pas valide.", 'dame' );
	}
	if ( ! empty( $_POST['dame_legal_rep_1_email'] ) && ! is_email( $_POST['dame_legal_rep_1_email'] ) ) {
		$errors[] = __( "L'adresse email du représentant légal 1 n'est pas valide.", 'dame' );
	}
	if ( ! empty( $_POST['dame_legal_rep_2_email'] ) && ! is_email( $_POST['dame_legal_rep_2_email'] ) ) {
		$errors[] = __( "L'adresse email du représentant légal 2 n'est pas valide.", 'dame' );
	}

	if ( ! empty( $errors ) ) {
		wp_send_json_error( array( 'message' => implode( '<br>', $errors ) ), 400 );
	}

	// 3. Sanitize Data
	$sanitized_data = array();
	$fields_to_sanitize = array(
		'dame_first_name', 'dame_last_name', 'dame_birth_date', 'dame_license_type', 'dame_birth_city', 'dame_sexe', 'dame_profession',
		'dame_email', 'dame_phone_number', 'dame_address_1', 'dame_address_2', 'dame_postal_code', 'dame_city', 'dame_taille_vetements',
		'dame_legal_rep_1_first_name', 'dame_legal_rep_1_last_name', 'dame_legal_rep_1_email', 'dame_legal_rep_1_phone',
		'dame_legal_rep_1_address_1', 'dame_legal_rep_1_address_2', 'dame_legal_rep_1_postal_code', 'dame_legal_rep_1_city', 'dame_legal_rep_1_profession',
		'dame_legal_rep_1_date_naissance', 'dame_legal_rep_1_commune_naissance',
		'dame_legal_rep_2_first_name', 'dame_legal_rep_2_last_name', 'dame_legal_rep_2_email', 'dame_legal_rep_2_phone',
		'dame_legal_rep_2_address_1', 'dame_legal_rep_2_address_2', 'dame_legal_rep_2_postal_code', 'dame_legal_rep_2_city', 'dame_legal_rep_2_profession',
		'dame_legal_rep_2_date_naissance', 'dame_legal_rep_2_commune_naissance',
		'dame_health_questionnaire',
	);

	foreach ( $fields_to_sanitize as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			if ( strpos( $field, 'email' ) !== false ) {
				$sanitized_data[ $field ] = sanitize_email( $_POST[ $field ] );
			} else {
				$sanitized_data[ $field ] = sanitize_text_field( $_POST[ $field ] );
			}
		}
	}

	// Determine if member is a minor and clean up data accordingly.
	$is_minor = false;
	if ( isset( $sanitized_data['dame_birth_date'] ) ) {
		$birth_date = DateTime::createFromFormat( 'Y-m-d', $sanitized_data['dame_birth_date'] );
		if ( $birth_date ) {
			$today    = new DateTime();
			$age      = $today->diff( $birth_date )->y;
			$is_minor = ( $age < 18 );

			if ( ! $is_minor ) {
				// If the member is an adult, remove any legal representative data that might have been submitted.
				foreach ( $sanitized_data as $key => $value ) {
					if ( strpos( $key, 'dame_legal_rep_' ) === 0 ) {
						unset( $sanitized_data[ $key ] );
					}
				}
			}
		}
	}

	// 4. Create Pre-inscription Post
	$post_title = strtoupper( $sanitized_data['dame_last_name'] ) . ' ' . $sanitized_data['dame_first_name'];
	$post_data = array(
		'post_title'  => $post_title,
		'post_type'   => 'dame_pre_inscription',
		'post_status' => 'pending',
	);
	$post_id = wp_insert_post( $post_data, true );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => __( "Erreur lors de la création de la fiche de préinscription.", 'dame' ) . ' ' . $post_id->get_error_message() ) );
	}

	// 5. Save Meta Data
	foreach ( $sanitized_data as $key => $value ) {
		// Skip direct save of health_questionnaire, it will be mapped and saved below.
		if ( 'dame_health_questionnaire' === $key ) {
			continue;
		}
		update_post_meta( $post_id, '_' . $key, $value );
	}

	// Map and save the health document status
	$health_document_status = 'none'; // Default value
	if ( isset( $sanitized_data['dame_health_questionnaire'] ) ) {
		if ( 'oui' === $sanitized_data['dame_health_questionnaire'] ) {
			$health_document_status = 'certificate';
		} elseif ( 'non' === $sanitized_data['dame_health_questionnaire'] ) {
			$health_document_status = 'attestation';
		}
	}
	update_post_meta( $post_id, '_dame_health_document', $health_document_status );


	// 6. Send Email Notification
	$options = get_option( 'dame_options' );
	$recipient_email = isset( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

	$subject = "Nouvelle préinscription de " . $sanitized_data['dame_first_name'] . ' ' . $sanitized_data['dame_last_name'];
	$body    = "Une nouvelle demande de préinscription a été soumise.\n\n";
	$body   .= "Voici les détails :\n";
	foreach ( $sanitized_data as $key => $value ) {
		if ( ! empty( $value ) ) {
			$label = str_replace( array( 'dame_', '_' ), array( '', ' ' ), $key );
			$label = ucwords( $label );
			$body .= "- " . $label . ": " . $value . "\n";
		}
	}
	$headers = array( 'From: ' . $recipient_email );

	wp_mail( $recipient_email, $subject, $body, $headers );

	// 7. Return Success Message
	$options = get_option( 'dame_options' );
	$payment_url = isset( $options['payment_url'] ) ? $options['payment_url'] : '';
	$sender_email = isset( $options['sender_email'] ) && ! empty( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

	$response_data = array(
		'message'            => sprintf(
			__( "La préinscription pour %s %s a bien été enregistrée.", 'dame' ),
			$sanitized_data['dame_first_name'],
			$sanitized_data['dame_last_name']
		),
		'health_questionnaire' => $sanitized_data['dame_health_questionnaire'], // 'oui' or 'non' for the JS logic
		'post_id'            => $post_id,
		'full_name'          => strtoupper( $sanitized_data['dame_last_name'] ) . ' ' . $sanitized_data['dame_first_name'],
		'nonce'              => wp_create_nonce( 'dame_generate_health_form_' . $post_id ),
		'is_minor'           => $is_minor,
		'payment_url'        => $payment_url,
		'sender_email'       => $sender_email,
	);

	if ( $is_minor ) {
		$response_data['parental_auth_nonce'] = wp_create_nonce( 'dame_generate_parental_auth_' . $post_id );
	}

	wp_send_json_success( $response_data );
}
add_action( 'wp_ajax_dame_submit_pre_inscription', 'dame_handle_pre_inscription_submission' );
add_action( 'wp_ajax_nopriv_dame_submit_pre_inscription', 'dame_handle_pre_inscription_submission' );


add_filter( 'the_content', 'dame_chess_pieces_shortcodes_filter' );
add_filter( 'widget_text_content', 'dame_chess_pieces_shortcodes_filter' ); // For block-based widgets
add_filter( 'comment_text', 'dame_chess_pieces_shortcodes_filter' );
