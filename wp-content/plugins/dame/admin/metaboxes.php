<?php
/**
 * File for handling custom meta boxes and fields for the Adherent CPT.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Display admin notices for our CPT.
 */
function dame_display_admin_notices() {
	if ( get_transient( 'dame_error_message' ) ) {
		$message = get_transient( 'dame_error_message' );
		delete_transient( 'dame_error_message' );
		echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'dame_display_admin_notices' );

/**
 * Enqueues admin scripts for the plugin.
 *
 * @param string $hook The current admin page.
 */
function dame_enqueue_admin_scripts( $hook ) {
	global $post;
	if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && isset( $post->post_type ) && 'adherent' === $post->post_type ) {
		wp_enqueue_script(
			'dame-main-js',
			plugin_dir_url( __FILE__ ) . 'js/main.js',
			array(),
			DAME_VERSION,
			true
		);
		wp_enqueue_script(
			'dame-geo-autocomplete-js',
			plugin_dir_url( __FILE__ ) . 'js/geo-autocomplete.js',
			array(),
			DAME_VERSION,
			true
		);
		wp_localize_script(
			'dame-main-js',
			'dame_admin_data',
			array(
				'department_region_mapping' => dame_get_department_region_mapping(),
			)
		);
		wp_enqueue_script(
			'dame-autocomplete-js',
			plugin_dir_url( __FILE__ ) . 'js/ign-autocomplete.js',
			array(),
			DAME_VERSION,
			true
		);
	}
}
add_action( 'admin_enqueue_scripts', 'dame_enqueue_admin_scripts' );

/**
 * Adds custom CSS to the admin head for the suggestion box.
 */
function dame_add_admin_styles() {
	$screen = get_current_screen();
	if ( 'adherent' !== $screen->post_type ) {
		return;
	}
	?>
	<style>
		.dame-autocomplete-wrapper {
			position: relative;
		}
		.dame-address-suggestions {
			border: 1px solid #999;
			border-top: none;
			max-height: 150px; /* Show approx 4 lines */
			overflow-y: auto;
			background-color: #fff;
			position: absolute;
			width: 100%;
			z-index: 9999; /* High z-index to appear above other elements */
			box-shadow: 0 3px 5px rgba(0,0,0,0.2);
		}
		.dame-suggestion-item {
			padding: 8px;
			cursor: pointer;
		}
		.dame-suggestion-item:hover {
			background-color: #f1f1f1;
		}
		#dame_birth_date, #dame_membership_date {
			width: 8em;
		}
		.dame-inline-fields {
			display: flex;
			gap: 1em;
		}
		.dame-inline-fields .postal-code {
			width: 8em;
			flex-shrink: 0;
		}
		.dame-inline-fields .city {
			width: 16em;
			flex-shrink: 0;
		}
	</style>
	<?php
}
add_action( 'admin_head-post.php', 'dame_add_admin_styles' );
add_action( 'admin_head-post-new.php', 'dame_add_admin_styles' );


/**
 * Adds the meta boxes for the Adherent CPT.
 */
function dame_add_meta_boxes() {
	add_meta_box(
		'dame_adherent_details_metabox',
		__( 'Informations sur l\'adhérent', 'dame' ),
		'dame_render_adherent_details_metabox',
		'adherent',
		'normal',
		'high'
	);
	add_meta_box(
		'dame_school_info_metabox',
		__( 'Informations Scolaires', 'dame' ),
		'dame_render_school_info_metabox',
		'adherent',
		'normal',
		'default'
	);
	add_meta_box(
		'dame_legal_rep_metabox',
		__( 'Représentants Légaux (si mineur)', 'dame' ),
		'dame_render_legal_rep_metabox',
		'adherent',
		'normal',
		'default'
	);
	add_meta_box(
		'dame_diverse_info_metabox',
		__( 'Informations diverses', 'dame' ),
		'dame_render_diverse_info_metabox',
		'adherent',
		'normal',
		'default'
	);
	add_meta_box(
		'dame_classification_metabox',
		__( 'Classification et Adhésion', 'dame' ),
		'dame_render_classification_metabox',
		'adherent',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'dame_add_meta_boxes' );

/**
 * Renders the meta box for adherent's personal details.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_adherent_details_metabox( $post ) {
	wp_nonce_field( 'dame_save_adherent_meta', 'dame_metabox_nonce' );

	$transient_data = get_transient( 'dame_post_data_' . $post->ID );
	if ( $transient_data ) {
		delete_transient( 'dame_post_data_' . $post->ID );
	}

	$get_value = function( $field_name, $default = '' ) use ( $post, $transient_data ) {
		return isset( $transient_data[ $field_name ] )
			? $transient_data[ $field_name ]
			: get_post_meta( $post->ID, '_' . $field_name, true );
	};

	// Retrieve values using the helper function
	$first_name = $get_value( 'dame_first_name' );
	$last_name = $get_value( 'dame_last_name' );
	$birth_date = $get_value( 'dame_birth_date' );
	$sexe = $get_value( 'dame_sexe', 'Non précisé' );
	if ( ! $sexe ) {
		$sexe = 'Non précisé';
	}
	$license_number = $get_value( 'dame_license_number' );
	$phone = $get_value( 'dame_phone_number' );
	$email = $get_value( 'dame_email' );
	$address_1 = $get_value( 'dame_address_1' );
	$address_2 = $get_value( 'dame_address_2' );
	$postal_code = $get_value( 'dame_postal_code' );
	$city = $get_value( 'dame_city' );
	$country = $get_value( 'dame_country' );
	$region = $get_value( 'dame_region' );
	$department = $get_value( 'dame_department' );
	$birth_postal_code = $get_value( 'dame_birth_postal_code' );
	$birth_city = $get_value( 'dame_birth_city' );
	?>
	<table class="form-table">
		<tr>
			<th><label for="dame_first_name"><?php _e( 'Prénom', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
			<td><input type="text" id="dame_first_name" name="dame_first_name" value="<?php echo esc_attr( $first_name ); ?>" class="regular-text" required="required" /></td>
		</tr>
		<tr>
			<th><label for="dame_last_name"><?php _e( 'Nom', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
			<td><input type="text" id="dame_last_name" name="dame_last_name" value="<?php echo esc_attr( $last_name ); ?>" class="regular-text" required="required" /></td>
		</tr>
		<tr>
			<th><label for="dame_birth_date"><?php _e( 'Date de naissance', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
			<td><input type="date" id="dame_birth_date" name="dame_birth_date" value="<?php echo esc_attr( $birth_date ); ?>" required="required"/></td>
		</tr>
		<tr>
			<th><label for="dame_birth_postal_code"><?php _e( 'Lieu de naissance', 'dame' ); ?></label></th>
			<td>
				<div class="dame-inline-fields dame-autocomplete-wrapper">
					<input type="text" id="dame_birth_postal_code" name="dame_birth_postal_code" value="<?php echo esc_attr( $birth_postal_code ); ?>" placeholder="<?php _e( 'Code Postal', 'dame' ); ?>" class="postal-code" />
					<input type="text" id="dame_birth_city" name="dame_birth_city" value="<?php echo esc_attr( $birth_city ); ?>" placeholder="<?php _e( 'Commune de naissance', 'dame' ); ?>" class="city" />
				</div>
			</td>
		</tr>
		<tr>
			<th><?php _e( 'Sexe', 'dame' ); ?></th>
			<td>
				<label style="margin-right: 15px;"><input type="radio" name="dame_sexe" value="Masculin" <?php checked( $sexe, 'Masculin' ); ?> /> <?php _e( 'Masculin', 'dame' ); ?></label>
				<label style="margin-right: 15px;"><input type="radio" name="dame_sexe" value="Féminin" <?php checked( $sexe, 'Féminin' ); ?> /> <?php _e( 'Féminin', 'dame' ); ?></label>
				<label><input type="radio" name="dame_sexe" value="Non précisé" <?php checked( $sexe, 'Non précisé' ); ?> /> <?php _e( 'Non précisé', 'dame' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><label for="dame_phone_number"><?php _e( 'Numéro de téléphone', 'dame' ); ?></label></th>
			<td><input type="tel" id="dame_phone_number" name="dame_phone_number" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_email"><?php _e( 'Email', 'dame' ); ?></label></th>
			<td><input type="email" id="dame_email" name="dame_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_address_1"><?php _e( 'Adresse (Ligne 1)', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper" style="position: relative;">
					<input type="text" id="dame_address_1" name="dame_address_1" value="<?php echo esc_attr( $address_1 ); ?>" class="regular-text" autocomplete="off" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_address_2"><?php _e( 'Adresse (Ligne 2)', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_address_2" name="dame_address_2" value="<?php echo esc_attr( $address_2 ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_postal_code"><?php _e( 'Code Postal / Ville', 'dame' ); ?></label></th>
			<td>
				<div class="dame-inline-fields">
					<input type="text" id="dame_postal_code" name="dame_postal_code" value="<?php echo esc_attr( $postal_code ); ?>" class="postal-code" placeholder="<?php _e( 'Code Postal', 'dame' ); ?>" />
					<input type="text" id="dame_city" name="dame_city" value="<?php echo esc_attr( $city ); ?>" class="city" placeholder="<?php _e( 'Ville', 'dame' ); ?>" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_country"><?php _e( 'Pays', 'dame' ); ?></label></th>
			<td>
				<select id="dame_country" name="dame_country">
					<?php foreach ( dame_get_country_list() as $code => $name ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
            <th><label for="dame_department"><?php _e( 'Département', 'dame' ); ?></label></th>
			<td>
                <select id="dame_department" name="dame_department">
                    <?php foreach ( dame_get_department_list() as $code => $name ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $department, $code ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
            <th><label for="dame_region"><?php _e( 'Région', 'dame' ); ?></label></th>
			<td>
                <select id="dame_region" name="dame_region">
                    <?php foreach ( dame_get_region_list() as $code => $name ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $region, $code ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Renders the meta box for school information.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_school_info_metabox( $post ) {
	$school_name = get_post_meta( $post->ID, '_dame_school_name', true );
	$school_academy = get_post_meta( $post->ID, '_dame_school_academy', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="dame_school_name"><?php _e( 'Établissement scolaire', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_school_name" name="dame_school_name" value="<?php echo esc_attr( $school_name ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_school_academy"><?php _e( 'Académie', 'dame' ); ?></label></th>
			<td>
				<select id="dame_school_academy" name="dame_school_academy">
					<?php foreach ( dame_get_academy_list() as $code => $name ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $school_academy, $code ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Renders the meta box for legal representative details.
 */
function dame_render_legal_rep_metabox( $post ) {
	// Rep 1
	$rep1_first_name = get_post_meta( $post->ID, '_dame_legal_rep_1_first_name', true );
	$rep1_last_name = get_post_meta( $post->ID, '_dame_legal_rep_1_last_name', true );
	$rep1_email = get_post_meta( $post->ID, '_dame_legal_rep_1_email', true );
	$rep1_phone = get_post_meta( $post->ID, '_dame_legal_rep_1_phone', true );
	$rep1_address_1 = get_post_meta( $post->ID, '_dame_legal_rep_1_address_1', true );
	$rep1_address_2 = get_post_meta( $post->ID, '_dame_legal_rep_1_address_2', true );
	$rep1_postal_code = get_post_meta( $post->ID, '_dame_legal_rep_1_postal_code', true );
	$rep1_city = get_post_meta( $post->ID, '_dame_legal_rep_1_city', true );

	// Rep 2
	$rep2_first_name = get_post_meta( $post->ID, '_dame_legal_rep_2_first_name', true );
	$rep2_last_name = get_post_meta( $post->ID, '_dame_legal_rep_2_last_name', true );
	$rep2_email = get_post_meta( $post->ID, '_dame_legal_rep_2_email', true );
	$rep2_phone = get_post_meta( $post->ID, '_dame_legal_rep_2_phone', true );
	$rep2_address_1 = get_post_meta( $post->ID, '_dame_legal_rep_2_address_1', true );
	$rep2_address_2 = get_post_meta( $post->ID, '_dame_legal_rep_2_address_2', true );
	$rep2_postal_code = get_post_meta( $post->ID, '_dame_legal_rep_2_postal_code', true );
	$rep2_city = get_post_meta( $post->ID, '_dame_legal_rep_2_city', true );
	?>
	<p><?php _e( 'Remplir ces informations si l\'adhérent est mineur. Au moins un représentant est requis.', 'dame' ); ?></p>

	<h4><?php _e( 'Représentant Légal 1', 'dame' ); ?></h4>
	<table class="form-table">
		<tr>
			<th><label for="dame_legal_rep_1_first_name"><?php _e( 'Prénom', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_1_first_name" name="dame_legal_rep_1_first_name" value="<?php echo esc_attr( $rep1_first_name ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_last_name"><?php _e( 'Nom', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_1_last_name" name="dame_legal_rep_1_last_name" value="<?php echo esc_attr( $rep1_last_name ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_email"><?php _e( 'Email', 'dame' ); ?></label></th>
			<td><input type="email" id="dame_legal_rep_1_email" name="dame_legal_rep_1_email" value="<?php echo esc_attr( $rep1_email ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_phone"><?php _e( 'Téléphone', 'dame' ); ?></label></th>
			<td><input type="tel" id="dame_legal_rep_1_phone" name="dame_legal_rep_1_phone" value="<?php echo esc_attr( $rep1_phone ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_address_1"><?php _e( 'Adresse (Ligne 1)', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_legal_rep_1_address_1" name="dame_legal_rep_1_address_1" value="<?php echo esc_attr( $rep1_address_1 ); ?>" class="regular-text" autocomplete="off" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_address_2"><?php _e( 'Adresse (Ligne 2)', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_1_address_2" name="dame_legal_rep_1_address_2" value="<?php echo esc_attr( $rep1_address_2 ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_postal_code"><?php _e( 'Code Postal / Ville', 'dame' ); ?></label></th>
			<td>
				<div class="dame-inline-fields">
					<input type="text" id="dame_legal_rep_1_postal_code" name="dame_legal_rep_1_postal_code" value="<?php echo esc_attr( $rep1_postal_code ); ?>" class="postal-code" placeholder="<?php _e( 'Code Postal', 'dame' ); ?>" />
					<input type="text" id="dame_legal_rep_1_city" name="dame_legal_rep_1_city" value="<?php echo esc_attr( $rep1_city ); ?>" class="city" placeholder="<?php _e( 'Ville', 'dame' ); ?>" />
				</div>
			</td>
		</tr>
	</table>

	<hr>

	<h4><?php _e( 'Représentant Légal 2', 'dame' ); ?></h4>
	<table class="form-table">
		<tr>
			<th><label for="dame_legal_rep_2_first_name"><?php _e( 'Prénom', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_2_first_name" name="dame_legal_rep_2_first_name" value="<?php echo esc_attr( $rep2_first_name ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_last_name"><?php _e( 'Nom', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_2_last_name" name="dame_legal_rep_2_last_name" value="<?php echo esc_attr( $rep2_last_name ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_email"><?php _e( 'Email', 'dame' ); ?></label></th>
			<td><input type="email" id="dame_legal_rep_2_email" name="dame_legal_rep_2_email" value="<?php echo esc_attr( $rep2_email ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_phone"><?php _e( 'Téléphone', 'dame' ); ?></label></th>
			<td><input type="tel" id="dame_legal_rep_2_phone" name="dame_legal_rep_2_phone" value="<?php echo esc_attr( $rep2_phone ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_address_1"><?php _e( 'Adresse (Ligne 1)', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_legal_rep_2_address_1" name="dame_legal_rep_2_address_1" value="<?php echo esc_attr( $rep2_address_1 ); ?>" class="regular-text" autocomplete="off" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_address_2"><?php _e( 'Adresse (Ligne 2)', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_2_address_2" name="dame_legal_rep_2_address_2" value="<?php echo esc_attr( $rep2_address_2 ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_postal_code"><?php _e( 'Code Postal / Ville', 'dame' ); ?></label></th>
			<td>
				<div class="dame-inline-fields">
					<input type="text" id="dame_legal_rep_2_postal_code" name="dame_legal_rep_2_postal_code" value="<?php echo esc_attr( $rep2_postal_code ); ?>" class="postal-code" placeholder="<?php _e( 'Code Postal', 'dame' ); ?>" />
					<input type="text" id="dame_legal_rep_2_city" name="dame_legal_rep_2_city" value="<?php echo esc_attr( $rep2_city ); ?>" class="city" placeholder="<?php _e( 'Ville', 'dame' ); ?>" />
				</div>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Renders the meta box for diverse information.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_diverse_info_metabox( $post ) {
	$autre_telephone  = get_post_meta( $post->ID, '_dame_autre_telephone', true );
	$taille_vetements = get_post_meta( $post->ID, '_dame_taille_vetements', true );
	$allergies        = get_post_meta( $post->ID, '_dame_allergies', true );
	$diet             = get_post_meta( $post->ID, '_dame_diet', true );
	$transport        = get_post_meta( $post->ID, '_dame_transport', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="dame_autre_telephone"><?php _e( 'Autre téléphone', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_autre_telephone" name="dame_autre_telephone" value="<?php echo esc_attr( $autre_telephone ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_taille_vetements"><?php _e( 'Taille vêtements', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_taille_vetements" name="dame_taille_vetements" value="<?php echo esc_attr( $taille_vetements ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_allergies"><?php _e( 'Allergies connues', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_allergies" name="dame_allergies" value="<?php echo esc_attr( $allergies ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_diet"><?php _e( 'Régime alimentaire', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_diet" name="dame_diet" value="<?php echo esc_attr( $diet ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_transport"><?php _e( 'Moyen de locomotion', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_transport" name="dame_transport" value="<?php echo esc_attr( $transport ); ?>" class="regular-text" /></td>
		</tr>
	</table>
	<?php
}

/**
 * Renders the meta box for classification and user linking.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_classification_metabox( $post ) {
	$transient_data = get_transient( 'dame_post_data_' . $post->ID );
	$get_value = function( $field_name, $default = '' ) use ( $post, $transient_data ) {
		$value = isset( $transient_data[ $field_name ] )
			? $transient_data[ $field_name ]
			: get_post_meta( $post->ID, '_' . $field_name, true );
		return $value ?: $default;
	};

	$license_number = $get_value( 'dame_license_number' );
	$membership_status = $get_value( 'dame_membership_status' );
	$status_options = [
		'N' => __( 'Non Adhérent (N)', 'dame' ),
		'A' => __( 'Actif (A)', 'dame' ),
		'E' => __( 'Expiré (E)', 'dame' ),
		'X' => __( 'Ancien (X)', 'dame' ),
	];

	$license_type = get_post_meta( $post->ID, '_dame_license_type', true );
	if ( ! $license_type ) {
		$license_type = 'A';
	}
	$membership_date = get_post_meta( $post->ID, '_dame_membership_date', true );

	$is_junior = get_post_meta( $post->ID, '_dame_is_junior', true );
	$is_pole_excellence = get_post_meta( $post->ID, '_dame_is_pole_excellence', true );
	$is_benevole = get_post_meta( $post->ID, '_dame_is_benevole', true );
	$is_elu_local = get_post_meta( $post->ID, '_dame_is_elu_local', true );

	$linked_user = get_post_meta( $post->ID, '_dame_linked_wp_user', true );
	if ( '' === $linked_user ) {
		$linked_user = -1;
	}

	$arbitre_level = get_post_meta( $post->ID, '_dame_arbitre_level', true );
	if ( ! $arbitre_level ) {
		$arbitre_level = 'Non';
	}
	$arbitre_options = ['Non', 'Jeune', 'Club', 'Open 1', 'Open 2', 'Elite 1', 'Elite 2'];

	?>
	<p>
		<label for="dame_license_number"><strong><?php _e( 'Numéro de licence', 'dame' ); ?></strong></label>
		<input type="text" id="dame_license_number" name="dame_license_number" value="<?php echo esc_attr( $license_number ); ?>" style="width:100%;" placeholder="A12345" pattern="[A-Z][0-9]{5}" />
	</p>
	<p>
		<label for="dame_membership_status"><strong><?php _e( 'État de l\'adhésion', 'dame' ); ?></strong></label>
		<select id="dame_membership_status" name="dame_membership_status" style="width:100%;">
			<?php foreach ( $status_options as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $membership_status, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<div id="dame_membership_details_wrapper">
		<p>
			<label><strong><?php _e( 'Type de licence', 'dame' ); ?></strong></label><br>
			<label style="margin-right: 15px;"><input type="radio" name="dame_license_type" value="A" <?php checked( $license_type, 'A' ); ?> /> A</label>
			<label style="margin-right: 15px;"><input type="radio" name="dame_license_type" value="B" <?php checked( $license_type, 'B' ); ?> /> B</label>
			<label><input type="radio" name="dame_license_type" value="Non précisé" <?php checked( $license_type, 'Non précisé' ); ?> /> <?php _e( 'Non précisé', 'dame' ); ?></label>
		</p>
		<p>
			<label for="dame_membership_date"><strong><?php _e( 'Date d\'adhésion', 'dame' ); ?></strong></label><br>
			<input type="date" id="dame_membership_date" name="dame_membership_date" value="<?php echo esc_attr( $membership_date ); ?>" style="width:100%;" />
		</p>
	</div>
	<hr>
	<p>
		<input type="checkbox" id="dame_is_junior" name="dame_is_junior" value="1" <?php checked( $is_junior, '1' ); ?> />
		<label for="dame_is_junior"><?php _e( 'École d\'échecs', 'dame' ); ?></label>
	</p>
	<p>
		<input type="checkbox" id="dame_is_pole_excellence" name="dame_is_pole_excellence" value="1" <?php checked( $is_pole_excellence, '1' ); ?> />
		<label for="dame_is_pole_excellence"><?php _e( 'Pôle Excellence', 'dame' ); ?></label>
	</p>
	<p>
		<input type="checkbox" id="dame_is_benevole" name="dame_is_benevole" value="1" <?php checked( $is_benevole, '1' ); ?> />
		<label for="dame_is_benevole"><?php _e( 'Bénévole', 'dame' ); ?></label>
	</p>
	<p>
		<input type="checkbox" id="dame_is_elu_local" name="dame_is_elu_local" value="1" <?php checked( $is_elu_local, '1' ); ?> />
		<label for="dame_is_elu_local"><?php _e( 'Elu local', 'dame' ); ?></label>
	</p>
	<hr>
	<p>
		<label for="dame_arbitre_level"><strong><?php _e( 'Niveau d\'arbitre', 'dame' ); ?></strong></label>
		<select id="dame_arbitre_level" name="dame_arbitre_level" style="width:100%;">
			<?php foreach ( $arbitre_options as $option ) : ?>
				<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $arbitre_level, $option ); ?>><?php echo esc_html( $option ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<hr>
	<p><strong><?php _e( 'Lier à un compte WordPress', 'dame' ); ?></strong></p>
	<?php
	// Exclude users who are already linked to another adherent.
	$exclude_users = dame_get_assigned_user_ids( $post->ID );

	// Get the total number of users to check for an edge case.
	$user_count_result = count_users();
	$total_users       = isset( $user_count_result['total_users'] ) ? $user_count_result['total_users'] : 0;

	// Edge case: If all users are assigned and the current member has no user linked,
	// wp_dropdown_users will show nothing. We manually render a disabled dropdown
	// to ensure the "Aucun" option is always visible and explain the situation.
	if ( count( $exclude_users ) >= $total_users && (int) $linked_user <= 0 ) {
		?>
		<select name="dame_linked_wp_user" id="dame_linked_wp_user" disabled="disabled">
			<option value="-1" selected="selected"><?php esc_html_e( 'Aucun', 'dame' ); ?></option>
		</select>
		<p><em><?php esc_html_e( 'Tous les comptes WordPress sont déjà assignés.', 'dame' ); ?></em></p>
		<?php
	} else {
		wp_dropdown_users(
			array(
				'name'              => 'dame_linked_wp_user',
				'id'                => 'dame_linked_wp_user',
				'show_option_none'  => esc_html__( 'Aucun', 'dame' ),
				'option_none_value' => -1,
				'selected'          => $linked_user,
				'exclude'           => $exclude_users,
			)
		);
	}
}


/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */
function dame_save_adherent_meta( $post_id ) {
	// --- Security checks ---
	if ( ! isset( $_POST['dame_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['dame_metabox_nonce'], 'dame_save_adherent_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// --- Validation ---
	$errors = [];
	if ( empty( $_POST['dame_first_name'] ) ) {
		$errors[] = __( 'Le prénom est obligatoire.', 'dame' );
	}
	if ( empty( $_POST['dame_last_name'] ) ) {
		$errors[] = __( 'Le nom est obligatoire.', 'dame' );
	}
	if ( empty( $_POST['dame_birth_date'] ) ) {
		$errors[] = __( 'La date de naissance est obligatoire.', 'dame' );
	}
	if ( ! empty( $_POST['dame_license_number'] ) && ! preg_match( '/^[A-Z][0-9]{5}$/', $_POST['dame_license_number'] ) ) {
		$errors[] = __( 'Le format du numéro de licence est invalide. Il doit être une lettre majuscule suivie de 5 chiffres (ex: A12345).', 'dame' );
	}

	// Email validation
	if ( ! empty( $_POST['dame_email'] ) && ! is_email( $_POST['dame_email'] ) ) {
		$errors[] = __( "Le format de l'email de l'adhérent est invalide.", 'dame' );
	}
	if ( ! empty( $_POST['dame_legal_rep_1_email'] ) && ! is_email( $_POST['dame_legal_rep_1_email'] ) ) {
		$errors[] = __( "Le format de l'email du représentant légal 1 est invalide.", 'dame' );
	}
	if ( ! empty( $_POST['dame_legal_rep_2_email'] ) && ! is_email( $_POST['dame_legal_rep_2_email'] ) ) {
		$errors[] = __( "Le format de l'email du représentant légal 2 est invalide.", 'dame' );
	}

	// Custom validation for active members
	if ( isset( $_POST['dame_membership_status'] ) && 'A' === $_POST['dame_membership_status'] ) {
		if ( empty( $_POST['dame_license_type'] ) ) {
			$errors[] = __( 'Le type de licence est obligatoire pour un membre actif.', 'dame' );
		}
	}


	if ( ! empty( $errors ) ) {
		set_transient( 'dame_error_message', implode( '<br>', $errors ), 10 );

		$post_data_to_save = array();
		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'dame_' ) === 0 ) {
				$post_data_to_save[ $key ] = wp_unslash( $value );
			}
		}
		set_transient( 'dame_post_data_' . $post_id, $post_data_to_save, 60 );

		return;
	}
	delete_transient( 'dame_post_data_' . $post_id );

	// --- Automatic Status Update ---
	if ( ! empty( $_POST['dame_membership_date'] ) && ( ! isset( $_POST['dame_membership_status'] ) || 'N' === $_POST['dame_membership_status'] ) ) {
		$_POST['dame_membership_status'] = 'A';
	}

	// --- Title Generation ---
	$first_name = sanitize_text_field( $_POST['dame_first_name'] );
	$last_name = sanitize_text_field( $_POST['dame_last_name'] );
	$new_title = strtoupper( $last_name ) . ' ' . $first_name;

	if ( get_the_title( $post_id ) !== $new_title ) {
		remove_action( 'save_post_adherent', 'dame_save_adherent_meta' );
		wp_update_post( array(
			'ID'         => $post_id,
			'post_title' => $new_title,
			'post_name'  => sanitize_title( $new_title ), // Also update the slug
		) );
		add_action( 'save_post_adherent', 'dame_save_adherent_meta' );
	}

	// --- Sanitize and Save Data ---
	$fields = [
		'dame_first_name' => 'sanitize_text_field', 'dame_last_name' => 'sanitize_text_field',
		'dame_birth_date' => 'sanitize_text_field', 'dame_license_number' => 'sanitize_text_field',
		'dame_birth_postal_code' => 'sanitize_text_field', 'dame_birth_city' => 'sanitize_text_field',
		'dame_email' => 'sanitize_email', 'dame_address_1' => 'sanitize_text_field',
		'dame_address_2' => 'sanitize_text_field', 'dame_postal_code' => 'sanitize_text_field',
		'dame_city' => 'sanitize_text_field', 'dame_phone_number' => 'sanitize_text_field',
		'dame_membership_date' => 'sanitize_text_field', 'dame_sexe' => 'sanitize_text_field',
		'dame_country' => 'sanitize_text_field', 'dame_region' => 'sanitize_text_field', 'dame_department' => 'sanitize_text_field',
		'dame_school_name' => 'sanitize_text_field', 'dame_school_ academy' => 'sanitize_text_field',

		'dame_legal_rep_1_first_name' => 'sanitize_text_field', 'dame_legal_rep_1_last_name' => 'sanitize_text_field',
		'dame_legal_rep_1_email' => 'sanitize_email', 'dame_legal_rep_1_phone' => 'sanitize_text_field',
		'dame_legal_rep_1_address_1' => 'sanitize_text_field', 'dame_legal_rep_1_address_2' => 'sanitize_text_field',
		'dame_legal_rep_1_postal_code' => 'sanitize_text_field', 'dame_legal_rep_1_city' => 'sanitize_text_field',

		'dame_legal_rep_2_first_name' => 'sanitize_text_field', 'dame_legal_rep_2_last_name' => 'sanitize_text_field',
		'dame_legal_rep_2_email' => 'sanitize_email', 'dame_legal_rep_2_phone' => 'sanitize_text_field',
		'dame_legal_rep_2_address_1' => 'sanitize_text_field', 'dame_legal_rep_2_address_2' => 'sanitize_text_field',
		'dame_legal_rep_2_postal_code' => 'sanitize_text_field', 'dame_legal_rep_2_city' => 'sanitize_text_field',

		'dame_is_junior' => 'absint', 'dame_is_pole_excellence' => 'absint', 'dame_is_benevole' => 'absint', 'dame_is_elu_local' => 'absint',
		'dame_arbitre_level' => 'sanitize_text_field',
		'dame_membership_status' => 'sanitize_text_field',
		'dame_license_type' => 'sanitize_text_field',
		'dame_autre_telephone' => 'sanitize_text_field',
		'dame_taille_vetements' => 'sanitize_text_field',
		'dame_allergies' => 'sanitize_text_field',
		'dame_diet' => 'sanitize_text_field',
		'dame_transport' => 'sanitize_text_field',
	];

	foreach ( $fields as $field_name => $sanitize_callback ) {
		if ( isset( $_POST[ $field_name ] ) ) {
			$value = call_user_func( $sanitize_callback, $_POST[ $field_name ] );
			update_post_meta( $post_id, '_' . $field_name, $value );
		} else {
			// This handles unchecked checkboxes, which are not present in $_POST.
			if ( 'absint' === $sanitize_callback ) {
				update_post_meta( $post_id, '_' . $field_name, 0 );
			}
		}
	}

	// Handle linked WordPress user separately for clarity.
	if ( isset( $_POST['dame_linked_wp_user'] ) ) {
		$linked_user_id = intval( $_POST['dame_linked_wp_user'] );
		if ( $linked_user_id > 0 ) {
			update_post_meta( $post_id, '_dame_linked_wp_user', $linked_user_id );
		} else {
			// If "Aucun" (value -1) or an error occurs, delete the meta key.
			delete_post_meta( $post_id, '_dame_linked_wp_user' );
		}
	}
}
add_action( 'save_post_adherent', 'dame_save_adherent_meta' );
