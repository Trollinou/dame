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
		<form id="dame-pre-inscription-form" class="dame-form" novalidate>

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
				<label for="dame_birth_city"><?php _e( 'Commune de naissance', 'dame' ); ?> <span id="dame_birth_city_required_indicator" class="required" style="display: none;">*</span></label>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_birth_city" name="dame_birth_city" class="regular-text">
				</div>
			</p>
			<p>
				<label><?php _e( 'Sexe', 'dame' ); ?> <span class="required">*</span></label>
				<label style="margin-left: 15px; display: inline-block;"><input type="radio" name="dame_sexe" value="Masculin" checked required> <?php _e( 'Masculin', 'dame' ); ?></label>
				<label style="margin-left: 15px; display: inline-block;"><input type="radio" name="dame_sexe" value="Féminin"> <?php _e( 'Féminin', 'dame' ); ?></label>
				<label style="margin-left: 15px; display: inline-block;"><input type="radio" name="dame_sexe" value="Non précisé"> <?php _e( 'Non précisé', 'dame' ); ?></label>
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
					<h4><?php _e( 'Informations complémentaires (Majeur)', 'dame' ); ?></h4>
					<p>
						<label for="dame_profession"><?php _e( 'Profession', 'dame' ); ?></label>
						<input type="text" id="dame_profession" name="dame_profession">
					</p>
				</div>

				<div id="dame-adherent-mineur-fields" style="display:none;">
					<h4 style="display: flex; align-items: center; flex-wrap: wrap;"><?php _e( 'Représentant Légal 1', 'dame' ); ?>
						<button type="button" class="dame-copy-button" data-rep-id="1" style="background-color: #3ec0f0; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 5px; white-space: nowrap; font-size: 13px; margin-left: 10px;"><?php _e( '✂️ Recopier les données de l\'Adhérent ✂️', 'dame' ); ?></button>
					</h4>
					<p><label for="dame_legal_rep_1_first_name"><?php _e( 'Prénom', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><input type="text" id="dame_legal_rep_1_first_name" name="dame_legal_rep_1_first_name"></p>
					<p><label for="dame_legal_rep_1_last_name"><?php _e( 'Nom', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><input type="text" id="dame_legal_rep_1_last_name" name="dame_legal_rep_1_last_name"></p>
					<p><label for="dame_legal_rep_1_email"><?php _e( 'Email', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><input type="email" id="dame_legal_rep_1_email" name="dame_legal_rep_1_email"></p>
					<p><label for="dame_legal_rep_1_phone"><?php _e( 'Numéro de téléphone', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><input type="tel" id="dame_legal_rep_1_phone" name="dame_legal_rep_1_phone"></p>
					<p><label for="dame_legal_rep_1_address_1"><?php _e( 'Adresse', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><div class="dame-autocomplete-wrapper"><input type="text" id="dame_legal_rep_1_address_1" name="dame_legal_rep_1_address_1"></div></p>
					<p><label for="dame_legal_rep_1_address_2"><?php _e( 'Complément', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_address_2" name="dame_legal_rep_1_address_2"></p>
					<p><label for="dame_legal_rep_1_postal_code"><?php _e( 'Code Postal', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_postal_code" name="dame_legal_rep_1_postal_code"></p>
					<p><label for="dame_legal_rep_1_city"><?php _e( 'Ville', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><input type="text" id="dame_legal_rep_1_city" name="dame_legal_rep_1_city"></p>
					<p><label for="dame_legal_rep_1_profession"><?php _e( 'Profession', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_profession" name="dame_legal_rep_1_profession"></p>
					<p><em><?php _e( 'Dans le cadre de notre politique de prévention des violences sexistes et sexuelles, nous demandons aux parents susceptibles d’accompagner des mineurs de se soumettre à un contrôle d’honorabilité. À cette fin, nous vous remercions de bien vouloir renseigner les deux champs ci-dessous si vous êtes concerné.', 'dame' ); ?></em></p>
					<p><label for="dame_legal_rep_1_date_naissance"><?php _e( 'Date de naissance', 'dame' ); ?></label><input type="date" id="dame_legal_rep_1_date_naissance" name="dame_legal_rep_1_date_naissance"></p>
					<p><label for="dame_legal_rep_1_commune_naissance"><?php _e( 'Commune de naissance', 'dame' ); ?></label><div class="dame-autocomplete-wrapper"><input type="text" id="dame_legal_rep_1_commune_naissance" name="dame_legal_rep_1_commune_naissance"></div></p>

					<h4 style="display: flex; align-items: center; flex-wrap: wrap;"><?php _e( 'Représentant Légal 2', 'dame' ); ?>
						<button type="button" class="dame-copy-button" data-rep-id="2" style="background-color: #3ec0f0; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 5px; white-space: nowrap; font-size: 13px; margin-left: 10px;"><?php _e( '✂️ Recopier les données de l\'Adhérent ✂️', 'dame' ); ?></button>
					</h4>
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

			<h4>
				<?php _e( "Questionnaire de santé", 'dame' ); ?>
				<span id="health-questionnaire-link-container" style="display: none; margin-left: 10px; font-weight: normal;">
					<a href="#" id="health-questionnaire-link" target="_blank" style="font-size: initial; color: blue; text-decoration: underline;"></a>
				</span>
			</h4>
			<p>
				<label><input type="radio" name="dame_health_questionnaire" value="non" required> <?php _e( "J’ai répondu NON partout", 'dame' ); ?></label>
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
			} else {
				// For adults, birth city is required for the honorability check.
				if ( empty( $_POST['dame_birth_city'] ) ) {
					$errors[] = __( "La commune de naissance est obligatoire pour les personnes majeures.", 'dame' );
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
				$sanitized_data[ $field ] = sanitize_email( wp_unslash( $_POST[ $field ] ) );
			} else {
				$sanitized_data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			}
		}
	}

	// Format names after sanitization.
	if ( ! empty( $sanitized_data['dame_first_name'] ) ) {
		$sanitized_data['dame_first_name'] = dame_format_firstname( $sanitized_data['dame_first_name'] );
	}
	if ( ! empty( $sanitized_data['dame_last_name'] ) ) {
		$sanitized_data['dame_last_name'] = dame_format_lastname( $sanitized_data['dame_last_name'] );
	}
	if ( ! empty( $sanitized_data['dame_legal_rep_1_first_name'] ) ) {
		$sanitized_data['dame_legal_rep_1_first_name'] = dame_format_firstname( $sanitized_data['dame_legal_rep_1_first_name'] );
	}
	if ( ! empty( $sanitized_data['dame_legal_rep_1_last_name'] ) ) {
		$sanitized_data['dame_legal_rep_1_last_name'] = dame_format_lastname( $sanitized_data['dame_legal_rep_1_last_name'] );
	}
	if ( ! empty( $sanitized_data['dame_legal_rep_2_first_name'] ) ) {
		$sanitized_data['dame_legal_rep_2_first_name'] = dame_format_firstname( $sanitized_data['dame_legal_rep_2_first_name'] );
	}
	if ( ! empty( $sanitized_data['dame_legal_rep_2_last_name'] ) ) {
		$sanitized_data['dame_legal_rep_2_last_name'] = dame_format_lastname( $sanitized_data['dame_legal_rep_2_last_name'] );
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


/**
 * Renders the [dame_contact] shortcode for the contact form.
 *
 * @param array $atts Shortcode attributes.
 * @return string The shortcode output.
 */
function dame_contact_shortcode( $atts ) {
    // Enqueue the script
    wp_enqueue_script( 'dame-contact-form', plugin_dir_url( __FILE__ ) . '../public/js/contact-form.js', array( 'jquery' ), DAME_VERSION, true );

    // Localize the script with new data
    wp_localize_script( 'dame-contact-form', 'dame_contact_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'dame_contact_nonce' ),
    ) );

    ob_start();
    ?>
    <div id="dame-contact-form-wrapper">
        <form id="dame-contact-form" class="dame-form" novalidate>

            <?php wp_nonce_field( 'dame_contact_nonce', 'dame_contact_nonce_field' ); ?>

            <p>
                <label for="dame_contact_name"><?php _e( 'Nom', 'dame' ); ?> <span class="required">*</span></label>
                <input type="text" id="dame_contact_name" name="dame_contact_name" required>
            </p>

            <p>
                <label for="dame_contact_email"><?php _e( 'Courriel', 'dame' ); ?> <span class="required">*</span></label>
                <input type="email" id="dame_contact_email" name="dame_contact_email" required>
            </p>

            <p>
                <label for="dame_contact_subject"><?php _e( 'Sujet', 'dame' ); ?> <span class="required">*</span></label>
                <input type="text" id="dame_contact_subject" name="dame_contact_subject" required>
            </p>

            <p>
                <label for="dame_contact_message"><?php _e( 'Message', 'dame' ); ?> <span class="required">*</span></label>
                <textarea id="dame_contact_message" name="dame_contact_message" rows="5" required></textarea>
            </p>

            <p>
                <button type="submit"><?php _e( 'Envoyer', 'dame' ); ?></button>
                <span id="dame-contact-form-messages" style="margin-left: 10px;"></span>
            </p>

        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'dame_contact', 'dame_contact_shortcode' );

/**
 * AJAX handler for the contact form submission.
 */
function dame_handle_contact_form_submission() {
    // 1. Security Check: Verify nonce
    if ( ! isset( $_POST['dame_contact_nonce_field'] ) || ! wp_verify_nonce( $_POST['dame_contact_nonce_field'], 'dame_contact_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( "La vérification de sécurité a échoué. Veuillez rafraîchir la page.", 'dame' ) ), 403 );
    }

    // 2. Validation
    $errors = array();
    $required_fields = array(
        'dame_contact_name'    => __( "Le nom est obligatoire.", 'dame' ),
        'dame_contact_email'   => __( "Le courriel est obligatoire.", 'dame' ),
        'dame_contact_subject' => __( "Le sujet est obligatoire.", 'dame' ),
        'dame_contact_message' => __( "Le message est obligatoire.", 'dame' ),
    );

    foreach ( $required_fields as $field_key => $error_message ) {
        if ( empty( $_POST[ $field_key ] ) ) {
            $errors[] = $error_message;
        }
    }

    // Email format validation
    if ( ! empty( $_POST['dame_contact_email'] ) && ! is_email( $_POST['dame_contact_email'] ) ) {
        $errors[] = __( "L'adresse de courriel n'est pas valide.", 'dame' );
    }

    if ( ! empty( $errors ) ) {
        wp_send_json_error( array( 'message' => implode( ' ', $errors ) ), 400 );
    }

    // 3. Sanitize Data
    $name    = sanitize_text_field( wp_unslash( $_POST['dame_contact_name'] ) );
    $email   = sanitize_email( wp_unslash( $_POST['dame_contact_email'] ) );
    $subject = sanitize_text_field( wp_unslash( $_POST['dame_contact_subject'] ) );
    $message = sanitize_textarea_field( wp_unslash( $_POST['dame_contact_message'] ) );

    // 4. Send Email
    $options = get_option( 'dame_options' );
    $to = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

    $email_subject = "Formulaire de contact - " . $subject;

    $body  = "Vous avez reçu un nouveau message depuis le formulaire de contact de votre site." . "\r\n\r\n";
    $body .= "Nom: " . $name . "\r\n";
    $body .= "Courriel: " . $email . "\r\n";
    $body .= "Sujet: " . $subject . "\r\n";
    $body .= "Message: " . "\r\n" . $message . "\r\n";

    $headers = array( 'From: ' . $name . ' <' . $email . '>' );

    $sent = wp_mail( $to, $email_subject, $body, $headers );

    if ( $sent ) {
        wp_send_json_success( array( 'message' => __( "Votre message a bien été envoyé.", 'dame' ) ) );
    } else {
        wp_send_json_error( array( 'message' => __( "Une erreur s'est produite lors de l'envoi du message.", 'dame' ) ) );
    }
}
add_action( 'wp_ajax_dame_contact_submit', 'dame_handle_contact_form_submission' );
add_action( 'wp_ajax_nopriv_dame_contact_submit', 'dame_handle_contact_form_submission' );

/**
 * Renders a hierarchical checklist of categories for the agenda filter.
 *
 * This function recursively generates a nested list of category checkboxes,
 * preserving the parent-child hierarchy.
 *
 * @param array $categories Array of category term objects.
 * @param int   $parent_id  The ID of the parent category to start from.
 */
if ( ! function_exists( 'dame_render_agenda_category_checklist' ) ) {
	function dame_render_agenda_category_checklist( $categories, $parent_id = 0 ) {
		// Find categories that are children of the current parent_id.
		$children = array();
		foreach ( $categories as $category ) {
			if ( $category->parent == $parent_id ) {
				$children[] = $category;
			}
		}

		// If no children are found, stop the recursion.
		if ( empty( $children ) ) {
			return;
		}

		// Start a new list for the children.
		echo '<ul>';

		foreach ( $children as $category ) {
			$term_meta = get_option( 'taxonomy_' . $category->term_id );
			$color     = ! empty( $term_meta['color'] ) ? $term_meta['color'] : '#ccc';
			?>
			<li>
				<label>
					<input type="checkbox" class="dame-agenda-cat-filter" value="<?php echo esc_attr( $category->term_id ); ?>" checked>
					<span class="dame-agenda-cat-color" style="background-color: <?php echo esc_attr( $color ); ?>"></span>
					<?php echo esc_html( $category->name ); ?>
				</label>
				<?php
				// Recursively call the function for the current category to render its children.
				dame_render_agenda_category_checklist( $categories, $category->term_id );
				?>
			</li>
			<?php
		}

		echo '</ul>';
	}
}


/**
 * Renders the [dame_agenda] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string The shortcode output.
 */
function dame_agenda_shortcode( $atts ) {
    // Enqueue scripts and styles
    wp_enqueue_style( 'dame-agenda-style', plugin_dir_url( __FILE__ ) . '../public/css/agenda.css', array(), DAME_VERSION );
    wp_enqueue_script( 'dame-agenda-script', plugin_dir_url( __FILE__ ) . '../public/js/agenda.js', array( 'jquery' ), DAME_VERSION, true );

    // Get WordPress's start_of_week option
    $start_of_week = intval( get_option( 'start_of_week', 1 ) ); // Default to Monday

    // Create the full weekdays array
    $weekdays = array(
        __( 'Dim', 'dame' ), __( 'Lun', 'dame' ), __( 'Mar', 'dame' ),
        __( 'Mer', 'dame' ), __( 'Jeu', 'dame' ), __( 'Ven', 'dame' ),
        __( 'Sam', 'dame' ),
    );

    // Reorder the weekdays array based on the start_of_week setting
    $ordered_weekdays = array();
    for ( $i = 0; $i < 7; $i++ ) {
        $day_index = ( $start_of_week + $i ) % 7;
        $ordered_weekdays[] = $weekdays[ $day_index ];
    }

    // Localize script with ajax url, nonce, and dynamic i18n values
    wp_localize_script( 'dame-agenda-script', 'dame_agenda_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'dame_agenda_nonce' ),
        'start_of_week' => $start_of_week,
        'i18n'     => array(
            'all_day' => __( 'Toute la journée', 'dame' ),
            'months'  => array(
                __( 'Janvier', 'dame' ), __( 'Février', 'dame' ), __( 'Mars', 'dame' ),
                __( 'Avril', 'dame' ), __( 'Mai', 'dame' ), __( 'Juin', 'dame' ),
                __( 'Juillet', 'dame' ), __( 'Août', 'dame' ), __( 'Septembre', 'dame' ),
                __( 'Octobre', 'dame' ), __( 'Novembre', 'dame' ), __( 'Décembre', 'dame' ),
            ),
            'weekdays_short' => $ordered_weekdays,
        ),
    ) );

    // Get all agenda categories for the filter
    $categories = get_terms( array(
        'taxonomy'   => 'dame_agenda_category',
        'hide_empty' => false,
    ) );

    ob_start();
    ?>
    <div id="dame-agenda-wrapper">
        <div class="dame-agenda-header">
            <div class="dame-agenda-primary-controls">
                <div class="dame-agenda-month-display">
                    <h2 id="dame-agenda-current-month" class="dame-agenda-month-picker-toggle"></h2>
                    <div id="dame-month-year-selector" style="display: none;">
                        <div class="dame-month-year-selector-header">
                            <button id="dame-selector-prev-year">&lt;&lt;</button>
                            <span id="dame-selector-year"></span>
                            <button id="dame-selector-next-year">&gt;&gt;</button>
                        </div>
                        <div class="dame-month-grid"></div>
                    </div>
                </div>
                <div class="dame-agenda-nav-buttons">
                    <button id="dame-agenda-prev-month" class="button">&lt;</button>
                    <button id="dame-agenda-today" class="button">
                        <span class="dame-desktop-text"><?php _e( 'Aujourd\'hui', 'dame' ); ?></span>
                        <span class="dame-mobile-text"><?php _e( 'Auj.', 'dame' ); ?></span>
                    </button>
                    <button id="dame-agenda-next-month" class="button">&gt;</button>
                </div>
            </div>

            <div class="dame-agenda-secondary-controls">
                <div class="dame-agenda-search">
                    <label for="dame-agenda-search-input" class="screen-reader-text"><?php _e( 'Rechercher un événement', 'dame' ); ?></label>
                    <input type="search" id="dame-agenda-search-input" placeholder="<?php _e( 'Rechercher...', 'dame' ); ?>">
                </div>
                <div class="dame-agenda-filter">
                    <button id="dame-agenda-filter-toggle" class="button"><?php _e( 'Filtres', 'dame' ); ?></button>
                    <div id="dame-agenda-filter-panel" style="display: none;">
                        <h5><?php _e( 'Catégories', 'dame' ); ?></h5>
                        <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                            <?php dame_render_agenda_category_checklist( $categories ); ?>
                        <?php else : ?>
                            <p><?php _e( 'Aucune catégorie d\'événement trouvée.', 'dame' ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div id="dame-calendar-container">
            <div class="dame-calendar-weekdays"></div>
            <div id="dame-calendar-grid"></div>
        </div>
        <div id="dame-event-tooltip" class="dame-tooltip" style="display: none;"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'dame_agenda', 'dame_agenda_shortcode' );

/**
 * Adds the necessary JOIN clauses to the WP_Query for searching in custom fields and taxonomies.
 *
 * @param string   $join  The JOIN clause of the query.
 * @param WP_Query $query The instance of WP_Query.
 * @return string The modified JOIN clause.
 */
function dame_agenda_search_join( $join, $query ) {
    global $wpdb;
    if ( ! empty( $query->get( 'dame_search' ) ) ) {
        // Use LEFT JOIN to include posts that may not have a description or category.
        // Join for the description meta field.
        $join .= $wpdb->prepare(
            " LEFT JOIN {$wpdb->postmeta} AS dame_desc_meta ON {$wpdb->posts}.ID = dame_desc_meta.post_id AND dame_desc_meta.meta_key = %s",
            '_dame_agenda_description'
        );
        // Joins for the category name.
        $join .= " LEFT JOIN {$wpdb->term_relationships} AS dame_tr ON {$wpdb->posts}.ID = dame_tr.object_id";
        $join .= " LEFT JOIN {$wpdb->term_taxonomy} AS dame_tt ON dame_tr.term_taxonomy_id = dame_tt.term_taxonomy_id";
        $join .= " LEFT JOIN {$wpdb->terms} AS dame_t ON dame_tt.term_id = dame_t.term_id";
    }
    return $join;
}


/**
 * Adds the WHERE clauses to the WP_Query for searching in title, description, and category.
 *
 * @param string   $where The WHERE clause of the query.
 * @param WP_Query $query The instance of WP_Query.
 * @return string The modified WHERE clause.
 */
function dame_agenda_search_where( $where, $query ) {
    global $wpdb;
    $search_term = $query->get( 'dame_search' );
    if ( ! empty( $search_term ) ) {
        $search_term_like = '%' . $wpdb->esc_like( $search_term ) . '%';

        // Build the OR conditions for the search.
        $search_where = $wpdb->prepare(
            "(
                {$wpdb->posts}.post_title LIKE %s
                OR dame_desc_meta.meta_value LIKE %s
                OR (dame_tt.taxonomy = 'dame_agenda_category' AND dame_t.name LIKE %s)
            )",
            $search_term_like,
            $search_term_like,
            $search_term_like
        );

        // Append our custom search conditions to the main WHERE clause.
        $where .= " AND ( " . $search_where . " )";
    }
    return $where;
}

/**
 * Ensures that the query returns distinct results.
 *
 * @param string   $distinct The DISTINCT clause of the query.
 * @param WP_Query $query    The instance of WP_Query.
 * @return string The modified DISTINCT clause.
 */
function dame_agenda_search_distinct( $distinct, $query ) {
    if ( ! empty( $query->get( 'dame_search' ) ) ) {
        return 'DISTINCT';
    }
    return $distinct;
}

/**
 * AJAX handler to fetch agenda events.
 */
function dame_get_agenda_events() {
    check_ajax_referer( 'dame_agenda_nonce', 'nonce' );

	// Get and validate the start and end dates from the AJAX request.
	$start_date_str = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
	$end_date_str   = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

	// Basic validation for YYYY-MM-DD format.
	$date_regex = '/^\d{4}-\d{2}-\d{2}$/';
	if ( ! preg_match( $date_regex, $start_date_str ) || ! preg_match( $date_regex, $end_date_str ) ) {
		wp_send_json_error( 'Invalid date format provided.' );
	}

    $categories = isset( $_POST['categories'] ) ? array_map( 'intval', $_POST['categories'] ) : array();
	$unchecked_categories = isset( $_POST['unchecked_categories'] ) ? array_map( 'intval', $_POST['unchecked_categories'] ) : array();
    $search_term = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

    // Show private events only to authorized users.
    // Role slugs: 'staff' (Membre du Bureau), 'administrator', 'editor', 'entraineur'.
    $post_status = array( 'publish' );
    $allowed_roles = array( 'staff', 'administrator', 'editor', 'entraineur' );
    $current_user = wp_get_current_user();

    if ( array_intersect( $allowed_roles, $current_user->roles ) ) {
        $post_status[] = 'private';
    }

    $args = array(
        'post_type'      => 'dame_agenda',
        'post_status'    => $post_status,
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_dame_start_date', // Event starts on or before the grid end date.
                'value'   => $end_date_str,
                'compare' => '<=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => '_dame_end_date',   // Event ends on or after the grid start date.
                'value'   => $start_date_str,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
        'orderby' => 'meta_value',
        'meta_key' => '_dame_start_date',
        'order' => 'ASC',
    );

	// If all category filters are unchecked, no events should be returned.
	// We check if the checked categories array is empty AND the unchecked categories array is not.
	// This ensures that if there are no categories at all, it doesn't trigger this logic.
	if ( empty( $categories ) && ! empty( $unchecked_categories ) ) {
		// Set a condition that cannot be met to return no posts
		$args['post__in'] = array( 0 );
	} else {
		$tax_query = array(
			'relation' => 'AND',
		);

		if ( ! empty( $categories ) ) {
			$tax_query[] = array(
				'taxonomy' => 'dame_agenda_category',
				'field'    => 'term_id',
				'terms'    => $categories,
				'include_children' => true, // Default, but explicit for clarity
			);
		}

		if ( ! empty( $unchecked_categories ) ) {
			$tax_query[] = array(
				'taxonomy' => 'dame_agenda_category',
				'field'    => 'term_id',
				'terms'    => $unchecked_categories,
				'operator' => 'NOT IN',
			);
		}

		// Only add the tax_query if there are conditions in it
		if ( count( $tax_query ) > 1 ) {
			$args['tax_query'] = $tax_query;
		}
	}

    if ( ! empty( $search_term ) ) {
        // Use a custom query var to trigger the search filters.
        $args['dame_search'] = $search_term;

        // Add the filters to modify the query.
        add_filter( 'posts_join', 'dame_agenda_search_join', 10, 2 );
        add_filter( 'posts_where', 'dame_agenda_search_where', 10, 2 );
        add_filter( 'posts_distinct', 'dame_agenda_search_distinct', 10, 2 );
    }

    $query = new WP_Query( $args );

    // Remove the filters immediately after the query to avoid affecting other queries on the site.
    if ( ! empty( $search_term ) ) {
        remove_filter( 'posts_join', 'dame_agenda_search_join', 10, 2 );
        remove_filter( 'posts_where', 'dame_agenda_search_where', 10, 2 );
        remove_filter( 'posts_distinct', 'dame_agenda_search_distinct', 10, 2 );
    }
    $events = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
            $term = get_the_terms( $post_id, 'dame_agenda_category' );
            $term_id = !empty($term) ? $term[0]->term_id : 0;
            $term_meta = get_option( "taxonomy_$term_id" );
            $color = ! empty( $term_meta['color'] ) ? $term_meta['color'] : '#ccc';

			$start_date = get_post_meta( $post_id, '_dame_start_date', true );
			$end_date   = get_post_meta( $post_id, '_dame_end_date', true );
			$status     = get_post_status( $post_id );

			$event_data = array(
				'id'          => $post_id,
				'title'       => get_the_title(),
				'status'      => $status,
				'url'         => get_permalink(),
				'start_date'  => $start_date,
				'start_time'  => get_post_meta( $post_id, '_dame_start_time', true ),
				'end_date'    => $end_date,
				'end_time'    => get_post_meta( $post_id, '_dame_end_time', true ),
				'all_day'     => get_post_meta( $post_id, '_dame_all_day', true ),
				'location'    => get_post_meta( $post_id, '_dame_location_name', true ),
				'description' => get_post_meta( $post_id, '_dame_agenda_description', true ),
				'color'       => $color,
				'category'    => ! empty( $term ) ? $term[0]->name : '',
			);

			// For multi-day events, determine the best contrasting text color.
			if ( $start_date !== $end_date ) {
				$event_data['text_color'] = dame_get_text_color_based_on_bg( $color );
			} else {
				// For single-day public events, lighten the background color.
				if ( 'private' !== $status ) {
					$event_data['background_color'] = dame_lighten_color( $color, 0.75 );
				}
			}

			$events[] = $event_data;
        }
    }

    wp_reset_postdata();
    wp_send_json_success( $events );
}
add_action( 'wp_ajax_dame_get_agenda_events', 'dame_get_agenda_events' );
add_action( 'wp_ajax_nopriv_dame_get_agenda_events', 'dame_get_agenda_events' );

/**
 * Renders the [dame_liste_agenda] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string The shortcode output.
 */
function dame_liste_agenda_shortcode( $atts ) {
	// Enqueue the specific stylesheet for the agenda list.
	wp_enqueue_style( 'dame-agenda-style', plugin_dir_url( __FILE__ ) . '../public/css/agenda.css', array(), DAME_VERSION );

    $atts = shortcode_atts( array(
        'nombre' => 4,
    ), $atts, 'dame_liste_agenda' );

    $nombre = intval( $atts['nombre'] );

    $today = date( 'Y-m-d' );

    // Show private events only to authorized users.
    // Role slugs: 'staff' (Membre du Bureau), 'administrator', 'editor', 'entraineur'.
    $post_status = array( 'publish' );
    $allowed_roles = array( 'staff', 'administrator', 'editor', 'entraineur' );
    $current_user = wp_get_current_user();

    if ( array_intersect( $allowed_roles, $current_user->roles ) ) {
        $post_status[] = 'private';
    }

    $args = array(
        'post_type'      => 'dame_agenda',
        'post_status'    => $post_status,
        'posts_per_page' => $nombre,
        'meta_key'       => '_dame_start_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => '_dame_start_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
    );

    $query = new WP_Query( $args );

    if ( ! $query->have_posts() ) {
        return '<p>' . __( 'Aucun événement à venir.', 'dame' ) . '</p>';
    }

    ob_start();
    ?>
    <div class="dame-liste-agenda-wrapper">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <?php
            $post_id = get_the_ID();
            $start_date_str = get_post_meta( $post_id, '_dame_start_date', true );
            $end_date_str = get_post_meta( $post_id, '_dame_end_date', true );
            $start_time = get_post_meta( $post_id, '_dame_start_time', true );
            $end_time = get_post_meta( $post_id, '_dame_end_time', true );
            $all_day = get_post_meta( $post_id, '_dame_all_day', true );

            $start_date = new DateTime( $start_date_str );
            $end_date = new DateTime( $end_date_str );

            $day_of_week = date_i18n( 'D', $start_date->getTimestamp() );
            $day_number  = $start_date->format( 'd' );
            $month_abbr  = date_i18n( 'M', $start_date->getTimestamp() );

            $date_display = date_i18n( 'j F Y', $start_date->getTimestamp() );
            if ( $start_date_str !== $end_date_str ) {
                $date_display = date_i18n( 'j F Y', $start_date->getTimestamp() ) . ' - ' . date_i18n( 'j F Y', $end_date->getTimestamp() );
            }

            $is_private = get_post_status( $post_id ) === 'private';
            $date_circle_style = $is_private ? 'style="background-color: #c9a0dc;"' : '';
            ?>
            <div class="dame-liste-agenda-item">
                <div class="dame-liste-agenda-date-icon">
                    <div class="date-circle" <?php echo $date_circle_style; ?>>
                        <span class="day-of-week"><?php echo esc_html( strtoupper( $day_of_week ) ); ?></span>
                        <span class="day-number"><?php echo esc_html( $day_number ); ?></span>
                        <span class="month-abbr"><?php echo esc_html( strtoupper( $month_abbr ) ); ?></span>
                    </div>
                </div>
                <div class="dame-liste-agenda-details">
                    <h4 class="event-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_post_field( 'post_title', get_the_ID() ) ); ?></a></h4>
                    <p class="event-date"><?php echo esc_html( $date_display ); ?></p>
                    <?php if ( ! $all_day ) : ?>
                        <p class="event-time"><?php echo esc_html( $start_time . ' - ' . $end_time ); ?></p>
                    <?php endif; ?>
                    <?php
                    $description = get_post_meta( get_the_ID(), '_dame_agenda_description', true );
                    if ( ! empty( $description ) ) :
                        $truncated_description = '';
                        $permalink = get_permalink();
                        $read_more_link = '&nbsp;<a href="' . esc_url( $permalink ) . '" class="dame-read-more">...</a>';

                        // Regex to find trailing <br> tags, whitespace, and &nbsp;
                        $cleanup_regex = '/(?:<br\s*\/?>|\s|&nbsp;)*$/i';

                        // Find the position of the first closing paragraph tag
                        $first_p_closing_pos = strpos( $description, '</p>' );

                        if ( $first_p_closing_pos !== false ) {
                            // Paragraph tag exists.
                            $first_paragraph_content = substr( $description, 0, $first_p_closing_pos );
                            $rest_of_description = substr( $description, $first_p_closing_pos + strlen('</p>') );

                            if ( trim( $rest_of_description ) !== '' ) {
                                // More content exists after the first paragraph.
                                $cleaned_content = preg_replace( $cleanup_regex, '', $first_paragraph_content );
                                $truncated_description = $cleaned_content . $read_more_link . '</p>';
                            } else {
                                // Only one paragraph, so display the whole description.
                                $truncated_description = $description;
                            }
                        } else {
                            // No paragraph tags, fall back to truncating by the first line break.
                            $lines = explode( "\n", $description, 2 );
                            $first_line = $lines[0];

                            if ( isset( $lines[1] ) && trim( $lines[1] ) !== '' ) {
                                // More lines exist.
                                $cleaned_line = preg_replace( $cleanup_regex, '', $first_line );
                                $truncated_description = $cleaned_line . $read_more_link;
                            } else {
                                $truncated_description = $first_line;
                            }
                        }
                    ?>
                        <div class="event-description"><?php echo apply_filters( 'the_content', $truncated_description ); ?></div>
                    <?php endif; ?>
                </div>
                 <div class="dame-liste-agenda-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'dame_liste_agenda', 'dame_liste_agenda_shortcode' );
