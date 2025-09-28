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
	if ( get_transient( 'dame_success_message' ) ) {
		$message = get_transient( 'dame_success_message' );
		delete_transient( 'dame_success_message' );
		echo '<div class="updated"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	if ( get_transient( 'dame_error_message' ) ) {
		$message = get_transient( 'dame_error_message' );
		delete_transient( 'dame_error_message' );
		echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	if ( isset( $_GET['message'] ) && '101' === $_GET['message'] ) {
		$screen = get_current_screen();
		if ( $screen && 'edit-dame_pre_inscription' === $screen->id ) {
			echo '<div class="updated"><p>' . esc_html__( 'La préinscription a été supprimée.', 'dame' ) . '</p></div>';
		}
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
	if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && isset( $post->post_type ) && in_array( $post->post_type, array( 'adherent', 'dame_pre_inscription', 'dame_agenda' ), true ) ) {
		wp_enqueue_script(
			'dame-main-js',
			plugin_dir_url( __FILE__ ) . 'js/main.js',
			array( 'jquery' ),
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
	} elseif ( 'adherent_page_dame-mailing' === $hook ) {
		// Ensure main.js is enqueued for the mailing page as well
		wp_enqueue_script(
			'dame-main-js',
			plugin_dir_url( __FILE__ ) . 'js/main.js',
			array(),
			DAME_VERSION,
			true
		);

		// Localize data for the mailing page filter
		wp_localize_script(
			'dame-main-js',
			'dame_mailing_data',
			array(
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'dame_filter_articles_nonce' ),
				'no_articles_found' => __( "Aucun article ne correspond aux filtres sélectionnés.", 'dame' ),
				'generic_error'     => __( "Une erreur est survenue lors de la récupération des articles.", 'dame' ),
			)
		);
	}
    if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && isset( $post->post_type ) && in_array( $post->post_type, array( 'dame_pre_inscription' ), true ) ) {
        wp_enqueue_style(
            'dame-admin-styles',
            plugin_dir_url( __FILE__ ) . 'css/admin-styles.css',
            array(),
            DAME_VERSION
        );
    }

}
add_action( 'admin_enqueue_scripts', 'dame_enqueue_admin_scripts' );

/**
 * Adds custom CSS to the admin head for the suggestion box.
 */
function dame_add_admin_styles() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array( 'adherent', 'dame_pre_inscription' ), true ) ) {
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
 * Renders the meta box for special actions.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_special_actions_metabox( $post ) {
	// Get the current season tag ID from options.
	$current_season_tag_id = get_option( 'dame_current_season_tag_id' );
	if ( ! $current_season_tag_id ) {
		echo '<p>' . esc_html__( "L'action est indisponible car la saison actuelle n'est pas configurée.", 'dame' ) . '</p>';
		return;
	}

	// Get all 'dame_saison_adhesion' terms for the current post.
	$terms = wp_get_object_terms( $post->ID, 'dame_saison_adhesion' );

	// Check the condition: exactly one term, and it must be the current season's term.
	$is_eligible = false;
	if ( ! is_wp_error( $terms ) && 1 === count( $terms ) ) {
		if ( (int) $terms[0]->term_id === (int) $current_season_tag_id ) {
			$is_eligible = true;
		}
	}

	if ( $is_eligible ) {
		// Security nonce.
		wp_nonce_field( 'dame_revert_to_pre_inscription_action', 'dame_revert_nonce' );
		?>
		<p><?php esc_html_e( "Cette action va supprimer cet adhérent et créer une nouvelle pré-inscription avec ses données. L'adhérent disparaîtra de la liste des adhérents.", 'dame' ); ?></p>
		<button type="submit" name="dame_revert_to_pre_inscription" value="revert" class="button button-secondary">
			<?php esc_html_e( "Annuler et renvoyer en pré-inscription", 'dame' ); ?>
		</button>
		<script>
			// Add a confirmation dialog to prevent accidental clicks.
			document.addEventListener('DOMContentLoaded', function() {
				const revertButton = document.querySelector('button[name="dame_revert_to_pre_inscription"]');
				if (revertButton) {
					revertButton.addEventListener('click', function(e) {
						if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir annuler cette adhésion et renvoyer cette personne en pré-inscription ? Cette action est irréversible.', 'dame' ) ); ?>")) {
							e.preventDefault();
						}
					});
				}
			});
		</script>
		<?php
	} else {
		echo '<p>' . esc_html__( "Cette action n'est disponible que pour les adhérents qui ont uniquement l'adhésion de la saison en cours.", 'dame' ) . '</p>';
	}
}


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
	add_meta_box(
		'dame_special_actions_metabox',
		__( 'Actions spéciales', 'dame' ),
		'dame_render_special_actions_metabox',
		'adherent',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'dame_add_meta_boxes' );

/**
 * Close the "Saisons d'adhésion" metabox by default by adding the 'closed' class.
 *
 * @param array $classes An array of postbox classes.
 * @return array The modified array of classes.
 */
function dame_close_saisons_metabox_by_default( $classes ) {
	// We check the current screen to make sure we're only affecting the intended metabox.
	if ( get_current_screen()->id === 'adherent' ) {
		$classes[] = 'closed';
	}
	return $classes;
}
// The default metabox ID for a non-hierarchical taxonomy is 'tagsdiv-{taxonomy_slug}'.
add_filter( 'postbox_classes_adherent_tagsdiv-dame_saison_adhesion', 'dame_close_saisons_metabox_by_default' );

/**
 * Close the "Actions spéciales" metabox by default.
 *
 * @param array $classes An array of postbox classes.
 * @return array The modified array of classes.
 */
function dame_close_special_actions_metabox_by_default( $classes ) {
	if ( get_current_screen()->id === 'adherent' ) {
		$classes[] = 'closed';
	}
	return $classes;
}
add_filter( 'postbox_classes_adherent_dame_special_actions_metabox', 'dame_close_special_actions_metabox_by_default' );


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
	$email_refuses_comms = $get_value( 'dame_email_refuses_comms' );
	$address_1 = $get_value( 'dame_address_1' );
	$address_2 = $get_value( 'dame_address_2' );
	$postal_code = $get_value( 'dame_postal_code' );
	$city = $get_value( 'dame_city' );
	$country = $get_value( 'dame_country' );
	$region = $get_value( 'dame_region' );
	$department = $get_value( 'dame_department' );
	$birth_city = $get_value( 'dame_birth_city' );
	$profession = $get_value( 'dame_profession' );
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
			<th><label for="dame_birth_city"><?php _e( 'Lieu de naissance', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_birth_city" name="dame_birth_city" value="<?php echo esc_attr( $birth_city ); ?>" placeholder="<?php _e( 'Commune de naissance (Code)', 'dame' ); ?>" class="regular-text" />
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
			<th><label for="dame_profession"><?php _e( 'Profession', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_profession" name="dame_profession" value="<?php echo esc_attr( $profession ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_email"><?php _e( 'Email', 'dame' ); ?></label></th>
			<td>
				<input type="email" id="dame_email" name="dame_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" />
				<label>
					<input type="checkbox" name="dame_email_refuses_comms" value="1" <?php checked( $email_refuses_comms, '1' ); ?> />
					<?php _e( 'Refus mailing', 'dame' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><label for="dame_phone_number"><?php _e( 'Numéro de téléphone', 'dame' ); ?></label></th>
			<td><input type="tel" id="dame_phone_number" name="dame_phone_number" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_address_1"><?php _e( 'Adresse', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper" style="position: relative;">
					<input type="text" id="dame_address_1" name="dame_address_1" value="<?php echo esc_attr( $address_1 ); ?>" class="regular-text" autocomplete="off" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_address_2"><?php _e( 'Complément', 'dame' ); ?></label></th>
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
	$rep1_email_refuses_comms = get_post_meta( $post->ID, '_dame_legal_rep_1_email_refuses_comms', true );
	$rep1_phone = get_post_meta( $post->ID, '_dame_legal_rep_1_phone', true );
	$rep1_profession = get_post_meta( $post->ID, '_dame_legal_rep_1_profession', true );
	$rep1_address_1 = get_post_meta( $post->ID, '_dame_legal_rep_1_address_1', true );
	$rep1_address_2 = get_post_meta( $post->ID, '_dame_legal_rep_1_address_2', true );
	$rep1_postal_code = get_post_meta( $post->ID, '_dame_legal_rep_1_postal_code', true );
	$rep1_city = get_post_meta( $post->ID, '_dame_legal_rep_1_city', true );

	// Rep 2
	$rep2_first_name = get_post_meta( $post->ID, '_dame_legal_rep_2_first_name', true );
	$rep2_last_name = get_post_meta( $post->ID, '_dame_legal_rep_2_last_name', true );
	$rep2_email = get_post_meta( $post->ID, '_dame_legal_rep_2_email', true );
	$rep2_email_refuses_comms = get_post_meta( $post->ID, '_dame_legal_rep_2_email_refuses_comms', true );
	$rep2_phone = get_post_meta( $post->ID, '_dame_legal_rep_2_phone', true );
	$rep2_profession = get_post_meta( $post->ID, '_dame_legal_rep_2_profession', true );
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
			<th><label for="dame_legal_rep_1_profession"><?php _e( 'Profession', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_1_profession" name="dame_legal_rep_1_profession" value="<?php echo esc_attr( $rep1_profession ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_honorabilite"><?php _e( 'Contrôle d\'honorabilité', 'dame' ); ?></label></th>
			<td>
				<select id="dame_legal_rep_1_honorabilite" name="dame_legal_rep_1_honorabilite">
					<?php
					$honorabilite1_options = array( 'Non requis', 'En cours', 'Favorable', 'Défavorable' );
					$selected_honorabilite1 = get_post_meta( $post->ID, '_dame_legal_rep_1_honorabilite', true ) ?: 'Non requis';
					foreach ( $honorabilite1_options as $option ) :
						?>
						<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $selected_honorabilite1, $option ); ?>><?php echo esc_html( $option ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_date_naissance"><?php _e( 'Date de naissance', 'dame' ); ?></label></th>
			<td><input type="date" id="dame_legal_rep_1_date_naissance" name="dame_legal_rep_1_date_naissance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_legal_rep_1_date_naissance', true ) ); ?>" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_commune_naissance"><?php _e( 'Commune de naissance', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_legal_rep_1_commune_naissance" name="dame_legal_rep_1_commune_naissance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_legal_rep_1_commune_naissance', true ) ); ?>" class="regular-text" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_email"><?php _e( 'Email', 'dame' ); ?></label></th>
			<td>
				<input type="email" id="dame_legal_rep_1_email" name="dame_legal_rep_1_email" value="<?php echo esc_attr( $rep1_email ); ?>" class="regular-text" />
				<label>
					<input type="checkbox" name="dame_legal_rep_1_email_refuses_comms" value="1" <?php checked( $rep1_email_refuses_comms, '1' ); ?> />
					<?php _e( 'Refus mailing', 'dame' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_phone"><?php _e( 'Téléphone', 'dame' ); ?></label></th>
			<td><input type="tel" id="dame_legal_rep_1_phone" name="dame_legal_rep_1_phone" value="<?php echo esc_attr( $rep1_phone ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_address_1"><?php _e( 'Adresse', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_legal_rep_1_address_1" name="dame_legal_rep_1_address_1" value="<?php echo esc_attr( $rep1_address_1 ); ?>" class="regular-text" autocomplete="off" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_address_2"><?php _e( 'Complément', 'dame' ); ?></label></th>
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
			<th><label for="dame_legal_rep_2_profession"><?php _e( 'Profession', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_2_profession" name="dame_legal_rep_2_profession" value="<?php echo esc_attr( $rep2_profession ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_honorabilite"><?php _e( 'Contrôle d\'honorabilité', 'dame' ); ?></label></th>
			<td>
				<select id="dame_legal_rep_2_honorabilite" name="dame_legal_rep_2_honorabilite">
					<?php
					$honorabilite2_options = array( 'Non requis', 'En cours', 'Favorable', 'Défavorable' );
					$selected_honorabilite2 = get_post_meta( $post->ID, '_dame_legal_rep_2_honorabilite', true ) ?: 'Non requis';
					foreach ( $honorabilite2_options as $option ) :
						?>
						<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $selected_honorabilite2, $option ); ?>><?php echo esc_html( $option ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_date_naissance"><?php _e( 'Date de naissance', 'dame' ); ?></label></th>
			<td><input type="date" id="dame_legal_rep_2_date_naissance" name="dame_legal_rep_2_date_naissance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_legal_rep_2_date_naissance', true ) ); ?>" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_commune_naissance"><?php _e( 'Commune de naissance', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_legal_rep_2_commune_naissance" name="dame_legal_rep_2_commune_naissance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_legal_rep_2_commune_naissance', true ) ); ?>" class="regular-text" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_email"><?php _e( 'Email', 'dame' ); ?></label></th>
			<td>
				<input type="email" id="dame_legal_rep_2_email" name="dame_legal_rep_2_email" value="<?php echo esc_attr( $rep2_email ); ?>" class="regular-text" />
				<label>
					<input type="checkbox" name="dame_legal_rep_2_email_refuses_comms" value="1" <?php checked( $rep2_email_refuses_comms, '1' ); ?> />
					<?php _e( 'Refus mailing', 'dame' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_phone"><?php _e( 'Téléphone', 'dame' ); ?></label></th>
			<td><input type="tel" id="dame_legal_rep_2_phone" name="dame_legal_rep_2_phone" value="<?php echo esc_attr( $rep2_phone ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_address_1"><?php _e( 'Adresse', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_legal_rep_2_address_1" name="dame_legal_rep_2_address_1" value="<?php echo esc_attr( $rep2_address_1 ); ?>" class="regular-text" autocomplete="off" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_address_2"><?php _e( 'Complément', 'dame' ); ?></label></th>
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
	$taille_vetements_options = array( 'Non renseigné', '8/10', '10/12', '12/14', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL' );
	if ( ! $taille_vetements || ! in_array( $taille_vetements, $taille_vetements_options, true ) ) {
		$taille_vetements = 'Non renseigné';
	}
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
			<td>
				<select id="dame_taille_vetements" name="dame_taille_vetements">
					<?php foreach ( $taille_vetements_options as $option ) : ?>
						<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $taille_vetements, $option ); ?>><?php echo esc_html( $option ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
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

	$license_type = get_post_meta( $post->ID, '_dame_license_type', true );
	if ( ! $license_type ) {
		$license_type = 'A';
	}

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
	<div>
		<?php
		// --- Display current status and season history ---
		$current_season_tag_id = get_option( 'dame_current_season_tag_id' );

		// --- Add a simple control to set Active/Inactive status ---
		echo '<p>';
		echo '<label for="dame_membership_status_control"><strong>' . esc_html__( 'Adhésion pour la saison actuelle', 'dame' ) . '</strong></label>';
		echo '<select id="dame_membership_status_control" name="dame_membership_status_control" style="width:100%;">';
		$is_active = ( $current_season_tag_id && has_term( (int) $current_season_tag_id, 'dame_saison_adhesion', $post->ID ) );
		echo '<option value="active" ' . selected( $is_active, true, false ) . '>' . esc_html__( 'Actif', 'dame' ) . '</option>';
		echo '<option value="inactive" ' . selected( $is_active, false, false ) . '>' . esc_html__( 'Non adhérent', 'dame' ) . '</option>';
		echo '</select>';
		echo '</p>';

		$saisons = get_the_terms( $post->ID, 'dame_saison_adhesion' );
		if ( ! empty( $saisons ) && ! is_wp_error( $saisons ) ) {
			// Check if there are any past seasons to display
			$past_saisons = array_filter(
				$saisons,
				function( $saison ) use ( $current_season_tag_id ) {
					return $saison->term_id !== (int) $current_season_tag_id;
				}
			);

			if ( ! empty( $past_saisons ) ) {
				echo '<p style="margin-top: 10px; margin-bottom: 5px;"><strong>' . esc_html__( 'Historique des saisons :', 'dame' ) . '</strong></p>';
				echo '<div>';
				foreach ( $past_saisons as $saison ) {
					echo '<span style="display: inline-block; background-color: #e0e0e0; color: #333; padding: 2px 8px; margin: 2px 2px 2px 0; border-radius: 4px; font-size: 0.9em;">' . esc_html( $saison->name ) . '</span>';
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</div>';
			}
		}
		?>
	</div>
	<hr>
	<p>
		<label for="dame_license_number"><strong><?php _e( 'Numéro de licence', 'dame' ); ?></strong></label>
		<input type="text" id="dame_license_number" name="dame_license_number" value="<?php echo esc_attr( $license_number ); ?>" style="width:100%;" placeholder="A12345" pattern="[A-Z][0-9]{5}" />
	</p>
	<p>
		<label><strong><?php _e( 'Type de licence', 'dame' ); ?></strong></label><br>
		<label style="margin-right: 15px;"><input type="radio" name="dame_license_type" value="A" <?php checked( $license_type, 'A' ); ?> /> <?php _e( 'Licence A (Cours + Compétition)', 'dame' ); ?></label>
		<br>
		<label style="margin-right: 15px;"><input type="radio" name="dame_license_type" value="B" <?php checked( $license_type, 'B' ); ?> /> <?php _e( 'Licence B (Jeu libre)', 'dame' ); ?></label>
		<br>
		<label><input type="radio" name="dame_license_type" value="Non précisé" <?php checked( $license_type, 'Non précisé' ); ?> /> <?php _e( 'Non précisé', 'dame' ); ?></label>
	</p>
	<p>
		<label for="dame_health_document"><strong><?php _e( 'Document de santé', 'dame' ); ?></strong></label>
		<select id="dame_health_document" name="dame_health_document" style="width:100%;">
			<?php
			$health_document = get_post_meta( $post->ID, '_dame_health_document', true );
			foreach ( dame_get_health_document_options() as $value => $label ) :
				?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $health_document, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
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
		<label for="dame_adherent_honorabilite"><strong><?php _e( 'Contrôle d\'honorabilité', 'dame' ); ?></strong></label>
		<select id="dame_adherent_honorabilite" name="dame_adherent_honorabilite" style="width:100%;">
			<?php
			$honorabilite_options = array( 'Non requis', 'En cours', 'Favorable', 'Défavorable' );
			$selected_honorabilite = get_post_meta( $post->ID, '_dame_adherent_honorabilite', true ) ?: 'Non requis';
			foreach ( $honorabilite_options as $option ) :
				?>
				<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $selected_honorabilite, $option ); ?>><?php echo esc_html( $option ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
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
 * Handles the special action of reverting a member to a pre-inscription.
 *
 * @param int $post_id The ID of the post being saved.
 */
function dame_handle_revert_to_pre_inscription( $post_id ) {
	// 1. Check if our button was clicked.
	if ( ! isset( $_POST['dame_revert_to_pre_inscription'] ) || 'revert' !== $_POST['dame_revert_to_pre_inscription'] ) {
		return;
	}

	// 2. Security: Verify nonce.
	if ( ! isset( $_POST['dame_revert_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['dame_revert_nonce'] ), 'dame_revert_to_pre_inscription_action' ) ) {
		return;
	}

	// 3. Security: Check user capabilities.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// 4. Server-side condition check.
	$current_season_tag_id = get_option( 'dame_current_season_tag_id' );
	$terms                   = wp_get_object_terms( $post_id, 'dame_saison_adhesion' );
	$is_eligible             = false;
	if ( ! is_wp_error( $terms ) && 1 === count( $terms ) && (int) $terms[0]->term_id === (int) $current_season_tag_id ) {
		$is_eligible = true;
	}

	if ( ! $is_eligible ) {
		return; // Should not happen if UI is correct, but important security check.
	}

	// 5. Unhook this function to prevent infinite loops.
	remove_action( 'save_post_adherent', 'dame_handle_revert_to_pre_inscription', 10 );

	// 6. Gather all metadata from the adherent.
	$adherent_meta_raw    = get_post_meta( $post_id );
	$pre_inscription_meta = array();
	foreach ( $adherent_meta_raw as $key => $value ) {
		if ( strpos( $key, '_dame_' ) === 0 ) {
			$pre_inscription_meta[ $key ] = maybe_unserialize( $value[0] );
		}
	}

	// 7. Create the new pre-inscription post.
	$pre_inscription_post_data = array(
		'post_title'  => get_the_title( $post_id ),
		'post_type'   => 'dame_pre_inscription',
		'post_status' => 'publish',
		'meta_input'  => $pre_inscription_meta,
	);
	$new_post_id               = wp_insert_post( $pre_inscription_post_data, true );

	// 8. If creation was successful, delete the old adherent and redirect.
	if ( ! is_wp_error( $new_post_id ) ) {
		wp_delete_post( $post_id, true ); // true = force delete, bypass trash.

		// Add a transient for a success notice.
		set_transient( 'dame_success_message', __( 'L\'adhérent a bien été renvoyé en pré-inscription.', 'dame' ), 5 );

		// Redirect to the pre-inscriptions list.
		wp_safe_redirect( admin_url( 'edit.php?post_type=dame_pre_inscription' ) );
		exit;
	}

	// 9. Re-hook the function if the action failed.
	add_action( 'save_post_adherent', 'dame_handle_revert_to_pre_inscription', 10 );
}
add_action( 'save_post_adherent', 'dame_handle_revert_to_pre_inscription', 10 );


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
	if ( empty( $_POST['dame_license_type'] ) ) {
		$errors[] = __( 'Le type de licence est obligatoire.', 'dame' );
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

	// --- Title Generation ---
	$first_name = sanitize_text_field( wp_unslash( $_POST['dame_first_name'] ) );
	$last_name  = sanitize_text_field( wp_unslash( $_POST['dame_last_name'] ) );
	$new_title  = dame_format_lastname( $last_name ) . ' ' . dame_format_firstname( $first_name );

	if ( get_the_title( $post_id ) !== $new_title ) {
		remove_action( 'save_post_adherent', 'dame_save_adherent_meta' );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $new_title,
				'post_name'  => sanitize_title( $new_title ), // Also update the slug
			)
		);
		add_action( 'save_post_adherent', 'dame_save_adherent_meta' );
	}

	// --- Handle Membership Status Control ---
	if ( isset( $_POST['dame_membership_status_control'] ) ) {
		$current_season_tag_id = get_option( 'dame_current_season_tag_id' );
		if ( $current_season_tag_id ) {
			$status_action = sanitize_key( $_POST['dame_membership_status_control'] );
			if ( 'active' === $status_action ) {
				wp_add_object_terms( $post_id, (int) $current_season_tag_id, 'dame_saison_adhesion' );
			} elseif ( 'inactive' === $status_action ) {
				wp_remove_object_terms( $post_id, (int) $current_season_tag_id, 'dame_saison_adhesion' );
			}
		}
	}

	// --- Sanitize and Save Data ---
	// Validate clothing size
	if ( isset( $_POST['dame_taille_vetements'] ) ) {
		$taille_vetements_options = array( 'Non renseigné', '8/10', '10/12', '12/14', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL' );
		if ( ! in_array( $_POST['dame_taille_vetements'], $taille_vetements_options, true ) ) {
			$_POST['dame_taille_vetements'] = 'Non renseigné';
		}
	}

	$fields = [
		'dame_first_name' => 'sanitize_text_field', 'dame_last_name' => 'sanitize_text_field',
		'dame_birth_date' => 'sanitize_text_field', 'dame_license_number' => 'sanitize_text_field',
		'dame_birth_city' => 'sanitize_text_field',
		'dame_email' => 'sanitize_email', 'dame_address_1' => 'sanitize_text_field',
		'dame_address_2' => 'sanitize_text_field', 'dame_postal_code' => 'sanitize_text_field',
		'dame_city' => 'sanitize_text_field', 'dame_phone_number' => 'sanitize_text_field',
		'dame_sexe' => 'sanitize_text_field',
		'dame_profession' => 'sanitize_text_field',
		'dame_country' => 'sanitize_text_field', 'dame_region' => 'sanitize_text_field', 'dame_department' => 'sanitize_text_field',
		'dame_school_name' => 'sanitize_text_field', 'dame_school_ academy' => 'sanitize_text_field',

		'dame_legal_rep_1_first_name' => 'sanitize_text_field', 'dame_legal_rep_1_last_name' => 'sanitize_text_field',
		'dame_legal_rep_1_profession' => 'sanitize_text_field', 'dame_legal_rep_1_honorabilite' => 'sanitize_text_field',
		'dame_legal_rep_1_date_naissance' => 'sanitize_text_field', 'dame_legal_rep_1_commune_naissance' => 'sanitize_text_field',
		'dame_legal_rep_1_email' => 'sanitize_email', 'dame_legal_rep_1_phone' => 'sanitize_text_field',
		'dame_legal_rep_1_address_1' => 'sanitize_text_field', 'dame_legal_rep_1_address_2' => 'sanitize_text_field',
		'dame_legal_rep_1_postal_code' => 'sanitize_text_field', 'dame_legal_rep_1_city' => 'sanitize_text_field',

		'dame_legal_rep_2_first_name' => 'sanitize_text_field', 'dame_legal_rep_2_last_name' => 'sanitize_text_field',
		'dame_legal_rep_2_profession' => 'sanitize_text_field', 'dame_legal_rep_2_honorabilite' => 'sanitize_text_field',
		'dame_legal_rep_2_date_naissance' => 'sanitize_text_field', 'dame_legal_rep_2_commune_naissance' => 'sanitize_text_field',
		'dame_legal_rep_2_email' => 'sanitize_email', 'dame_legal_rep_2_phone' => 'sanitize_text_field',
		'dame_legal_rep_2_address_1' => 'sanitize_text_field', 'dame_legal_rep_2_address_2' => 'sanitize_text_field',
		'dame_legal_rep_2_postal_code' => 'sanitize_text_field', 'dame_legal_rep_2_city' => 'sanitize_text_field',

		'dame_email_refuses_comms' => 'absint',
		'dame_legal_rep_1_email_refuses_comms' => 'absint',
		'dame_legal_rep_2_email_refuses_comms' => 'absint',
		'dame_is_junior' => 'absint', 'dame_is_pole_excellence' => 'absint', 'dame_is_benevole' => 'absint', 'dame_is_elu_local' => 'absint',
		'dame_adherent_honorabilite' => 'sanitize_text_field', 'dame_arbitre_level' => 'sanitize_text_field',
		'dame_license_type' => 'sanitize_text_field',
		'dame_health_document' => 'sanitize_key',
		'dame_autre_telephone' => 'sanitize_text_field',
		'dame_taille_vetements' => 'sanitize_text_field',
		'dame_allergies' => 'sanitize_text_field',
		'dame_diet' => 'sanitize_text_field',
		'dame_transport' => 'sanitize_text_field',
	];

	foreach ( $fields as $field_name => $sanitize_callback ) {
		if ( isset( $_POST[ $field_name ] ) ) {
			$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) );

			// Format names after sanitization.
			if ( 'dame_first_name' === $field_name || 'dame_legal_rep_1_first_name' === $field_name || 'dame_legal_rep_2_first_name' === $field_name ) {
				$value = dame_format_firstname( $value );
			}
			if ( 'dame_last_name' === $field_name || 'dame_legal_rep_1_last_name' === $field_name || 'dame_legal_rep_2_last_name' === $field_name ) {
				$value = dame_format_lastname( $value );
			}

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

/**
 * Adds the meta boxes for the Agenda CPT.
 */
function dame_add_agenda_meta_boxes() {
    add_meta_box(
        'dame_agenda_description_metabox',
        __( 'Description', 'dame' ),
        'dame_render_agenda_description_metabox',
        'dame_agenda',
        'normal',
        'high'
    );
    add_meta_box(
        'dame_agenda_details_metabox',
        __( 'Détails de l\'événement', 'dame' ),
        'dame_render_agenda_details_metabox',
        'dame_agenda',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'dame_add_agenda_meta_boxes' );

/**
 * Renders the meta box for the agenda's description.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_agenda_description_metabox( $post ) {
	// Check for transient data in case of a validation error on save.
	$transient_data = get_transient( 'dame_agenda_post_data_' . $post->ID );
	$description    = '';
	if ( $transient_data && isset( $transient_data['dame_agenda_description'] ) ) {
		$description = $transient_data['dame_agenda_description'];
		// Don't delete the transient here; let the main details metabox handle it
		// to ensure all metaboxes can access it.
	} else {
		$description = get_post_meta( $post->ID, '_dame_agenda_description', true );
	}

	wp_editor(
        $description,
        'dame_agenda_description',
        array(
            'textarea_name' => 'dame_agenda_description',
            'teeny'         => true,
            'media_buttons' => false,
            'textarea_rows' => 5,
            'tinymce'       => array(
                'toolbar1' => 'bold,italic',
                'toolbar2' => '',
                'toolbar3' => '',
            ),
        )
    );
}

/**
 * Renders the meta box for agenda details.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_agenda_details_metabox( $post ) {
    wp_nonce_field( 'dame_save_agenda_meta', 'dame_agenda_metabox_nonce' );

	// Check for transient data in case of a validation error on save.
	$transient_data = get_transient( 'dame_agenda_post_data_' . $post->ID );
	if ( $transient_data ) {
		// Clean up the transient so it's only used once.
		delete_transient( 'dame_agenda_post_data_' . $post->ID );
	}

	// Helper function to get value from transient first, then from post meta.
	$get_value = function( $field_name, $default = '' ) use ( $post, $transient_data ) {
		// For fields like 'dame_start_date', the key in $_POST is 'dame_start_date'.
		// In post meta, it's '_dame_start_date'. The transient stores it without the underscore.
		return isset( $transient_data[ $field_name ] )
			? esc_attr( $transient_data[ $field_name ] )
			: get_post_meta( $post->ID, '_' . $field_name, true );
	};

	$start_date    = $get_value( 'dame_start_date' );
	$start_time    = $get_value( 'dame_start_time' );
	$end_date      = $get_value( 'dame_end_date' );
	$end_time      = $get_value( 'dame_end_time' );
	$all_day       = $get_value( 'dame_all_day' );
	$location_name = $get_value( 'dame_location_name' );
	$address_1     = $get_value( 'dame_address_1' );
	$address_2     = $get_value( 'dame_address_2' );
	$postal_code   = $get_value( 'dame_postal_code' );
	$city          = $get_value( 'dame_city' );
    ?>
    <table class="form-table">
        <!-- Date and Time Fields -->
        <tr>
            <th><label for="dame_all_day"><?php _e( 'Journée entière', 'dame' ); ?></label></th>
            <td>
                <input type="checkbox" id="dame_all_day" name="dame_all_day" value="1" <?php checked( $all_day, '1' ); ?> />
            </td>
        </tr>
        <tr>
            <th><label for="dame_start_date"><?php _e( 'Date de début', 'dame' ); ?></label></th>
            <td>
                <input type="date" id="dame_start_date" name="dame_start_date" value="<?php echo esc_attr( $start_date ); ?>" />
                <span class="dame-time-fields <?php if ( $all_day ) echo 'hidden'; ?>">
                    <label for="dame_start_time" class="screen-reader-text"><?php _e( 'Heure de début', 'dame' ); ?></label>
                    <select id="dame_start_time" name="dame_start_time">
                        <?php
                        for ( $h = 0; $h < 24; $h++ ) {
                            for ( $m = 0; $m < 60; $m += 15 ) {
                                $time_val = sprintf( '%02d:%02d', $h, $m );
                                echo '<option value="' . esc_attr( $time_val ) . '" ' . selected( $start_time, $time_val, false ) . '>' . esc_html( $time_val ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </span>
            </td>
        </tr>
        <tr>
            <th><label for="dame_end_date"><?php _e( 'Date de fin', 'dame' ); ?></label></th>
            <td>
                <input type="date" id="dame_end_date" name="dame_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
                 <span class="dame-time-fields <?php if ( $all_day ) echo 'hidden'; ?>">
                    <label for="dame_end_time" class="screen-reader-text"><?php _e( 'Heure de fin', 'dame' ); ?></label>
                    <select id="dame_end_time" name="dame_end_time">
                        <?php
                        for ( $h = 0; $h < 24; $h++ ) {
                            for ( $m = 0; $m < 60; $m += 15 ) {
                                $time_val = sprintf( '%02d:%02d', $h, $m );
                                echo '<option value="' . esc_attr( $time_val ) . '" ' . selected( $end_time, $time_val, false ) . '>' . esc_html( $time_val ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </span>
            </td>
        </tr>

        <!-- Location Fields -->
        <tr>
            <th colspan="2"><h4><?php _e( 'Lieu', 'dame' ); ?></h4></th>
        </tr>
        <tr>
            <th><label for="dame_location_name"><?php _e( 'Intitulé du lieu', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_location_name" name="dame_location_name" value="<?php echo esc_attr( $location_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
			<th><label for="dame_address_1"><?php _e( 'Adresse', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper" style="position: relative;">
					<input type="text" id="dame_address_1" name="dame_address_1" value="<?php echo esc_attr( $address_1 ); ?>" class="regular-text" autocomplete="off" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_address_2"><?php _e( 'Complément', 'dame' ); ?></label></th>
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
        <tr style="display:none;">
            <th><label for="dame_latitude"><?php _e( 'Latitude', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_latitude" name="dame_latitude" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_latitude', true ) ); ?>" /></td>
        </tr>
        <tr style="display:none;">
            <th><label for="dame_longitude"><?php _e( 'Longitude', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_longitude" name="dame_longitude" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_longitude', true ) ); ?>" /></td>
        </tr>
    </table>
    <script>
    jQuery(document).ready(function($) {
        function toggleTimeFields() {
            if ($('#dame_all_day').is(':checked')) {
                $('.dame-time-fields').hide();
            } else {
                $('.dame-time-fields').show();
            }
        }
        toggleTimeFields(); // Initial check
        $('#dame_all_day').on('change', toggleTimeFields);
    });
    </script>
    <style>
        .dame-time-fields.hidden { display: none; }
    </style>
    <?php
}

/**
 * Save meta box content for Agenda CPT.
 *
 * @param int $post_id Post ID
 */
function dame_save_agenda_meta( $post_id ) {
    // --- Security checks ---
    if ( ! isset( $_POST['dame_agenda_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['dame_agenda_metabox_nonce'], 'dame_save_agenda_meta' ) ) {
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
    if ( empty( $_POST['dame_start_date'] ) ) {
        $errors[] = __( 'La date de début est obligatoire.', 'dame' );
    }
    if ( empty( $_POST['dame_end_date'] ) ) {
        $errors[] = __( 'La date de fin est obligatoire.', 'dame' );
    }
    if ( empty( $_POST['tax_input']['dame_agenda_category'] ) || ( is_array( $_POST['tax_input']['dame_agenda_category'] ) && count( array_filter( $_POST['tax_input']['dame_agenda_category'] ) ) === 0 ) ) {
        $errors[] = __( 'La catégorie est obligatoire.', 'dame' );
    }

    if ( ! empty( $errors ) ) {
        set_transient( 'dame_error_message', implode( '<br>', $errors ), 10 );

        // Store submitted data in a transient to repopulate the form
        $post_data_to_save = array();
        foreach ( $_POST as $key => $value ) {
            if ( strpos( $key, 'dame_' ) === 0 || $key === 'tax_input' ) {
                $post_data_to_save[ $key ] = wp_unslash( $value );
            }
        }
        set_transient( 'dame_agenda_post_data_' . $post_id, $post_data_to_save, 60 );


        // Unhook this function to prevent infinite loops
        remove_action( 'save_post_dame_agenda', 'dame_save_agenda_meta' );

        // Update the post to be a draft
        wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );

        // Re-hook the function
        add_action( 'save_post_dame_agenda', 'dame_save_agenda_meta' );
        return;
    }

    // If we are here, it means there are no errors, so we can delete any transient data
    delete_transient( 'dame_agenda_post_data_' . $post_id );

    // --- Sanitize and Save Data ---
    $fields = [
        'dame_start_date'    => 'sanitize_text_field',
        'dame_start_time'    => 'sanitize_text_field',
        'dame_end_date'      => 'sanitize_text_field',
        'dame_end_time'      => 'sanitize_text_field',
        'dame_all_day'       => 'absint',
        'dame_location_name' => 'sanitize_text_field',
        'dame_address_1'     => 'sanitize_text_field',
        'dame_address_2'     => 'sanitize_text_field',
        'dame_postal_code'   => 'sanitize_text_field',
        'dame_city'          => 'sanitize_text_field',
        'dame_latitude'      => 'sanitize_text_field',
        'dame_longitude'     => 'sanitize_text_field',
        'dame_agenda_description' => 'wp_kses_post',
    ];

    foreach ( $fields as $field_name => $sanitize_callback ) {
        if ( isset( $_POST[ $field_name ] ) ) {
            $value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) );
            update_post_meta( $post_id, '_' . $field_name, $value );
        } else {
            if ( 'absint' === $sanitize_callback ) {
                update_post_meta( $post_id, '_' . $field_name, 0 );
            }
        }
    }
}
add_action( 'save_post_dame_agenda', 'dame_save_agenda_meta' );


// =================================================================
// == PRE-INSCRIPTION METABOXES
// =================================================================

/**
 * Adds the meta boxes for the Pre-inscription CPT.
 * This function is hooked into 'add_meta_boxes'.
 */
function dame_add_pre_inscription_meta_boxes() {
	$screen = get_current_screen();
	if ( ! $screen || 'dame_pre_inscription' !== $screen->post_type ) {
		return;
	}

	add_meta_box(
		'dame_pre_inscription_details_metabox',
		__( 'Détails de la Préinscription', 'dame' ),
		'dame_render_pre_inscription_details_metabox',
		'dame_pre_inscription',
		'normal',
		'high'
	);

	// --- Matching Logic ---
	$pre_inscription_id = get_the_ID();
	$first_name         = get_post_meta( $pre_inscription_id, '_dame_first_name', true );
	$last_name          = get_post_meta( $pre_inscription_id, '_dame_last_name', true );
	$birth_date         = get_post_meta( $pre_inscription_id, '_dame_birth_date', true );
	$matched_adherent_id = 0;

	if ( $first_name && $last_name && $birth_date ) {
		$adherent_query = new WP_Query(
			array(
				'post_type'      => 'adherent',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_dame_first_name',
						'value'   => $first_name,
						'compare' => '=',
					),
					array(
						'key'     => '_dame_last_name',
						'value'   => $last_name,
						'compare' => '=',
					),
					array(
						'key'     => '_dame_birth_date',
						'value'   => $birth_date,
						'compare' => '=',
					),
				),
				'fields'         => 'ids',
			)
		);

		if ( $adherent_query->have_posts() ) {
			$matched_adherent_id = $adherent_query->posts[0];
		}
	}
	// --- End Matching Logic ---

	add_meta_box(
		'dame_pre_inscription_actions_metabox',
		__( 'Actions de Validation', 'dame' ),
		'dame_render_pre_inscription_actions_metabox',
		'dame_pre_inscription',
		'side',
		'high',
		array( 'matched_id' => $matched_adherent_id )
	);

	if ( $matched_adherent_id ) {
		add_meta_box(
			'dame_pre_inscription_reconciliation_metabox',
			__( 'Rapprochement avec un adhérent existant', 'dame' ),
			'dame_render_pre_inscription_reconciliation_metabox',
			'dame_pre_inscription',
			'normal',
			'high',
			array( 'matched_id' => $matched_adherent_id )
		);
	}
}
add_action( 'add_meta_boxes', 'dame_add_pre_inscription_meta_boxes' );


/**
 * Renders the meta box for pre-inscription actions.
 *
 * @param WP_Post $post The post object.
 * @param array   $metabox The metabox arguments.
 */
function dame_render_pre_inscription_actions_metabox( $post, $metabox ) {
	$matched_id = $metabox['args']['matched_id'];
	wp_nonce_field( 'dame_pre_inscription_process_action', 'dame_pre_inscription_action_nonce' );
	?>
	<div class="dame-actions-wrapper">
		<?php if ( $matched_id ) : ?>
			<input type="hidden" name="dame_matched_adherent_id" value="<?php echo esc_attr( $matched_id ); ?>" />
			<p><strong><span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php _e( 'Adhérent existant trouvé !', 'dame' ); ?></strong></p>
			<p>
				<button type="submit" name="dame_pre_inscription_action" value="validate_update" class="button button-primary button-large"><?php _e( "Mettre à jour l'adhérent", 'dame' ); ?></button>
			</p>
		<?php else : ?>
			<p><strong><?php _e( "Valider cette préinscription ?", 'dame' ); ?></strong></p>
			<p>
				<button type="submit" name="dame_pre_inscription_action" value="validate_new" class="button button-primary button-large"><?php _e( "Valider et Créer Adhérent", 'dame' ); ?></button>
			</p>
		<?php endif; ?>
		<hr>
		<p>
			<button type="submit" name="dame_pre_inscription_action" value="delete" class="button button-secondary button-large dame-delete-button" formnovalidate><?php _e( "Supprimer la Préinscription", 'dame' ); ?></button>
		</p>
	</div>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const deleteButton = document.querySelector('.dame-delete-button');
			if (deleteButton) {
				deleteButton.addEventListener('click', function(e) {
					if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir supprimer définitivement cette préinscription ? Cette action est irréversible.', 'dame' ) ); ?>")) {
						e.preventDefault();
					}
				});
			}
		});
	</script>
	<?php
}

/**
 * Renders the meta box for pre-inscription details.
 * All fields are disabled as they are for review, not editing.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_pre_inscription_details_metabox( $post ) {
	wp_nonce_field( 'dame_save_pre_inscription_meta', 'dame_pre_inscription_metabox_nonce' );

	$get_value = function( $field_name ) use ( $post ) {
		return get_post_meta( $post->ID, '_' . $field_name, true );
	};

	// To avoid repetition, we define field groups.
	$field_groups = array(
		'Informations Adhérent' => array(
			'Prénom' => array( 'key' => 'dame_first_name', 'type' => 'text', 'required' => true ),
			'Nom' => array( 'key' => 'dame_last_name', 'type' => 'text', 'required' => true ),
			'Date de naissance' => array( 'key' => 'dame_birth_date', 'type' => 'date', 'required' => true ),
			'Type de licence' => array(
				'key'     => 'dame_license_type',
				'type'    => 'select',
				'options' => array(
					'A' => __( 'Licence A (Cours + Compétition)', 'dame' ),
					'B' => __( 'Licence B (Jeu libre)', 'dame' ),
				),
			),
			'Document de santé' => array( 'key' => 'dame_health_document', 'type' => 'select', 'options_callback' => 'dame_get_health_document_options' ),
			'Commune de naissance' => array( 'key' => 'dame_birth_city', 'type' => 'text_autocomplete' ),
			'Sexe' => array( 'key' => 'dame_sexe', 'type' => 'radio', 'options' => array( 'Masculin', 'Féminin', 'Non précisé' ) ),
			'Email' => array( 'key' => 'dame_email', 'type' => 'email' ),
			'Numéro de téléphone' => array( 'key' => 'dame_phone_number', 'type' => 'tel' ),
			'Adresse' => array( 'key' => 'dame_address_1', 'type' => 'text_autocomplete' ),
			'Complément' => array( 'key' => 'dame_address_2', 'type' => 'text' ),
			'Code Postal' => array( 'key' => 'dame_postal_code', 'type' => 'text' ),
			'Ville' => array( 'key' => 'dame_city', 'type' => 'text' ),
			'Taille de vêtements' => array( 'key' => 'dame_taille_vetements', 'type' => 'select', 'options' => array( 'Non renseigné', '8/10', '10/12', '12/14', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL' ) ),
			'Profession' => array( 'key' => 'dame_profession', 'type' => 'text' ),
		),
		'Représentant Légal 1' => array(
			'Prénom' => array( 'key' => 'dame_legal_rep_1_first_name', 'type' => 'text' ),
			'Nom' => array( 'key' => 'dame_legal_rep_1_last_name', 'type' => 'text' ),
			'Email' => array( 'key' => 'dame_legal_rep_1_email', 'type' => 'email' ),
			'Téléphone' => array( 'key' => 'dame_legal_rep_1_phone', 'type' => 'tel' ),
			'Adresse' => array( 'key' => 'dame_legal_rep_1_address_1', 'type' => 'text_autocomplete' ),
			'Complément' => array( 'key' => 'dame_legal_rep_1_address_2', 'type' => 'text' ),
			'Code Postal' => array( 'key' => 'dame_legal_rep_1_postal_code', 'type' => 'text' ),
			'Ville' => array( 'key' => 'dame_legal_rep_1_city', 'type' => 'text' ),
			'Profession' => array( 'key' => 'dame_legal_rep_1_profession', 'type' => 'text' ),
			'Date de naissance' => array( 'key' => 'dame_legal_rep_1_date_naissance', 'type' => 'date' ),
			'Commune de naissance' => array( 'key' => 'dame_legal_rep_1_commune_naissance', 'type' => 'text' ),
		),
		'Représentant Légal 2' => array(
			'Prénom' => array( 'key' => 'dame_legal_rep_2_first_name', 'type' => 'text' ),
			'Nom' => array( 'key' => 'dame_legal_rep_2_last_name', 'type' => 'text' ),
			'Email' => array( 'key' => 'dame_legal_rep_2_email', 'type' => 'email' ),
			'Téléphone' => array( 'key' => 'dame_legal_rep_2_phone', 'type' => 'tel' ),
			'Adresse' => array( 'key' => 'dame_legal_rep_2_address_1', 'type' => 'text_autocomplete' ),
			'Complément' => array( 'key' => 'dame_legal_rep_2_address_2', 'type' => 'text' ),
			'Code Postal' => array( 'key' => 'dame_legal_rep_2_postal_code', 'type' => 'text' ),
			'Ville' => array( 'key' => 'dame_legal_rep_2_city', 'type' => 'text' ),
			'Profession' => array( 'key' => 'dame_legal_rep_2_profession', 'type' => 'text' ),
			'Date de naissance' => array( 'key' => 'dame_legal_rep_2_date_naissance', 'type' => 'date' ),
			'Commune de naissance' => array( 'key' => 'dame_legal_rep_2_commune_naissance', 'type' => 'text' ),
		),
	);

	foreach ( $field_groups as $group_label => $fields ) {
		echo '<h3>' . esc_html__( $group_label, 'dame' ) . '</h3>';
		echo '<table class="form-table">';
		foreach ( $fields as $label => $config ) {
			$key = $config['key'];
			$value = $get_value( $key );
			$required = isset( $config['required'] ) && $config['required'] ? ' <span class="description">(obligatoire)</span>' : '';

			echo '<tr>';
			echo '<th><label for="' . esc_attr( $key ) . '">' . esc_html__( $label, 'dame' ) . $required . '</label></th>';
			echo '<td>';

			if ( 'select' === $config['type'] ) {
				$options = array();
				if ( isset( $config['options_callback'] ) && function_exists( $config['options_callback'] ) ) {
					$options = call_user_func( $config['options_callback'] );
				} elseif ( isset( $config['options'] ) ) {
					$options = $config['options'];
				}

				echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
				foreach ( $options as $option_value => $option_label ) {
					// If options is not associative, use label as value
					$val = is_int( $option_value ) ? $option_label : $option_value;
					echo '<option value="' . esc_attr( $val ) . '" ' . selected( $value, $val, false ) . '>' . esc_html( $option_label ) . '</option>';
				}
				echo '</select>';
			} elseif ( 'radio' === $config['type'] ) {
				foreach ( $config['options'] as $option ) {
					echo '<label style="margin-right: 15px;"><input type="radio" name="' . esc_attr( $key ) . '" value="' . esc_attr( $option ) . '" ' . checked( $value, $option, false ) . ' /> ' . esc_html( $option ) . '</label>';
				}
			} else {
				$type = ( 'text_autocomplete' === $config['type'] ) ? 'text' : $config['type'];
				$is_autocomplete = ( 'text_autocomplete' === $config['type'] );

				if ( $is_autocomplete ) {
					echo '<div class="dame-autocomplete-wrapper">';
				}
				echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
				if ( $is_autocomplete ) {
					echo '</div>';
				}
			}

			echo '</td></tr>';
		}
		echo '</table>';
	}
}

/**
 * Renders the meta box for reconciling a pre-inscription with an existing member.
 *
 * @param WP_Post $post The post object.
 * @param array   $metabox The metabox arguments.
 */
function dame_render_pre_inscription_reconciliation_metabox( $post, $metabox ) {
	$pre_inscription_id = $post->ID;
	$matched_id         = $metabox['args']['matched_id'];

	// Define all possible fields to ensure a comprehensive comparison.
	$all_fields = array(
		'Informations Principales' => array(
			'Prénom'               => 'dame_first_name',
			'Nom'                  => 'dame_last_name',
			'Date de naissance'    => 'dame_birth_date',
			'Type de licence'      => 'dame_license_type',
			'Commune de naissance' => 'dame_birth_city',
			'Sexe'                 => 'dame_sexe',
			'Email'                => 'dame_email',
			'Numéro de téléphone'  => 'dame_phone_number',
			'Adresse'              => 'dame_address_1',
			'Complément'           => 'dame_address_2',
			'Code Postal'          => 'dame_postal_code',
			'Ville'                => 'dame_city',
			'Taille de vêtements'  => 'dame_taille_vetements',
			'Profession'           => 'dame_profession',
			'Document de santé'    => 'dame_health_document',
		),
		'Représentant Légal 1' => array(
			'Rep. 1 - Prénom'     => 'dame_legal_rep_1_first_name',
			'Rep. 1 - Nom'        => 'dame_legal_rep_1_last_name',
			'Rep. 1 - Email'      => 'dame_legal_rep_1_email',
			'Rep. 1 - Téléphone'  => 'dame_legal_rep_1_phone',
			'Rep. 1 - Adresse'    => 'dame_legal_rep_1_address_1',
			'Rep. 1 - Complément' => 'dame_legal_rep_1_address_2',
			'Rep. 1 - Code Postal' => 'dame_legal_rep_1_postal_code',
			'Rep. 1 - Ville'      => 'dame_legal_rep_1_city',
			'Rep. 1 - Profession' => 'dame_legal_rep_1_profession',
			'Rep. 1 - Date de naissance' => 'dame_legal_rep_1_date_naissance',
			'Rep. 1 - Commune de naissance' => 'dame_legal_rep_1_commune_naissance',
		),
		'Représentant Légal 2' => array(
			'Rep. 2 - Prénom'     => 'dame_legal_rep_2_first_name',
			'Rep. 2 - Nom'        => 'dame_legal_rep_2_last_name',
			'Rep. 2 - Email'      => 'dame_legal_rep_2_email',
			'Rep. 2 - Téléphone'  => 'dame_legal_rep_2_phone',
			'Rep. 2 - Adresse'    => 'dame_legal_rep_2_address_1',
			'Rep. 2 - Complément' => 'dame_legal_rep_2_address_2',
			'Rep. 2 - Code Postal' => 'dame_legal_rep_2_postal_code',
			'Rep. 2 - Ville'      => 'dame_legal_rep_2_city',
			'Rep. 2 - Profession' => 'dame_legal_rep_2_profession',
			'Rep. 2 - Date de naissance' => 'dame_legal_rep_2_date_naissance',
			'Rep. 2 - Commune de naissance' => 'dame_legal_rep_2_commune_naissance',
		),
	);
	?>
	<p><?php printf( __( 'Correspondance trouvée avec l\'adhérent <a href="%s" target="_blank">#%d</a>.', 'dame' ), esc_url( get_edit_post_link( $matched_id ) ), (int) $matched_id ); ?></p>
	<table class="wp-list-table widefat fixed striped dame-reconciliation-table">
		<thead>
			<tr>
				<th style="width: 25%;"><?php _e( 'Champ', 'dame' ); ?></th>
				<th style="width: 37.5%;"><?php _e( 'Donnée de la Préinscription', 'dame' ); ?></th>
				<th style="width: 37.5%;"><?php _e( 'Donnée de l\'Adhérent Existant', 'dame' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $all_fields as $group_label => $fields ) {
				$has_data_in_group = false;
				foreach ( $fields as $key_suffix ) {
					if ( ! empty( get_post_meta( $pre_inscription_id, '_' . $key_suffix, true ) ) ) {
						$has_data_in_group = true;
						break;
					}
				}

				if ( ! $has_data_in_group ) {
					continue;
				}

				?>
				<tr class="heading">
					<th colspan="3"><strong><?php echo esc_html( $group_label ); ?></strong></th>
				</tr>
				<?php
				foreach ( $fields as $label => $key_suffix ) {
					$pre_inscription_value = get_post_meta( $pre_inscription_id, '_' . $key_suffix, true );
					$adherent_value        = get_post_meta( $matched_id, '_' . $key_suffix, true );

					// Special display formatting for certain fields
					if ( 'dame_birth_date' === $key_suffix ) {
						if ( $date = DateTime::createFromFormat( 'Y-m-d', $pre_inscription_value ) ) { $pre_inscription_value = $date->format( 'd/m/Y' ); }
						if ( $date = DateTime::createFromFormat( 'Y-m-d', $adherent_value ) ) { $adherent_value = $date->format( 'd/m/Y' ); }
					} elseif ( 'dame_health_document' === $key_suffix ) {
						$options = dame_get_health_document_options();
						$pre_inscription_value = isset( $options[ $pre_inscription_value ] ) ? $options[ $pre_inscription_value ] : $pre_inscription_value;
						$adherent_value = isset( $options[ $adherent_value ] ) ? $options[ $adherent_value ] : $adherent_value;
					}


					// Show all fields from the pre-inscription, even if empty, to be comprehensive.
					$highlight_class = ( (string) $pre_inscription_value !== (string) $adherent_value ) ? 'dame-highlight-diff' : '';
					?>
					<tr class="<?php echo esc_attr( $highlight_class ); ?>">
						<td><strong><?php echo esc_html( $label ); ?></strong></td>
						<td><?php echo esc_html( $pre_inscription_value ); ?></td>
						<td><?php echo esc_html( $adherent_value ); ?></td>
					</tr>
					<?php
				}
			}
			?>
		</tbody>
	</table>
	<?php
}


/**
 * AJAX handler to get available lessons and exercises for the course builder.
 */

/**
 * Processes the actions from the pre-inscription metabox.
 * Hooked to save_post, it handles the creation, update, or deletion based on the button clicked.
 *
 * @param int $post_id The ID of the post being saved.
 */
function dame_process_pre_inscription_actions( $post_id ) {
	// Security check for standard save
	if ( ! isset( $_POST['dame_pre_inscription_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['dame_pre_inscription_metabox_nonce'], 'dame_save_pre_inscription_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Unhook this function to prevent infinite loops during post updates.
	remove_action( 'save_post_dame_pre_inscription', 'dame_process_pre_inscription_actions' );

	// --- Step 1: Always save the submitted fields first ---
	// This ensures any edits made by the admin are saved before further action.
	$first_name = sanitize_text_field( wp_unslash( $_POST['dame_first_name'] ) );
	$last_name  = sanitize_text_field( wp_unslash( $_POST['dame_last_name'] ) );
	if ( $first_name && $last_name ) {
		$new_title = dame_format_lastname( $last_name ) . ' ' . dame_format_firstname( $first_name );
		if ( get_the_title( $post_id ) !== $new_title ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $new_title,
				)
			);
		}
	}

	$all_field_keys = array(
		'dame_first_name', 'dame_last_name', 'dame_birth_date', 'dame_license_type', 'dame_birth_city', 'dame_sexe', 'dame_profession',
		'dame_email', 'dame_phone_number', 'dame_address_1', 'dame_address_2', 'dame_postal_code', 'dame_city', 'dame_taille_vetements',
		'dame_health_document',
		'dame_legal_rep_1_first_name', 'dame_legal_rep_1_last_name', 'dame_legal_rep_1_email', 'dame_legal_rep_1_phone',
		'dame_legal_rep_1_address_1', 'dame_legal_rep_1_address_2', 'dame_legal_rep_1_postal_code', 'dame_legal_rep_1_city', 'dame_legal_rep_1_profession',
		'dame_legal_rep_1_date_naissance', 'dame_legal_rep_1_commune_naissance',
		'dame_legal_rep_2_first_name', 'dame_legal_rep_2_last_name', 'dame_legal_rep_2_email', 'dame_legal_rep_2_phone',
		'dame_legal_rep_2_address_1', 'dame_legal_rep_2_address_2', 'dame_legal_rep_2_postal_code', 'dame_legal_rep_2_city', 'dame_legal_rep_2_profession',
		'dame_legal_rep_2_date_naissance', 'dame_legal_rep_2_commune_naissance',
	);
	foreach ( $all_field_keys as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$value = strpos( $key, 'email' ) !== false ? sanitize_email( wp_unslash( $_POST[ $key ] ) ) : sanitize_text_field( wp_unslash( $_POST[ $key ] ) );

			// Format names after sanitization.
			if ( 'dame_first_name' === $key || 'dame_legal_rep_1_first_name' === $key || 'dame_legal_rep_2_first_name' === $key ) {
				$value = dame_format_firstname( $value );
			}
			if ( 'dame_last_name' === $key || 'dame_legal_rep_1_last_name' === $key || 'dame_legal_rep_2_last_name' === $key ) {
				$value = dame_format_lastname( $value );
			}
			update_post_meta( $post_id, '_' . $key, $value );
		}
	}

	// --- Step 2: Check if a custom action button was pressed ---
	if ( isset( $_POST['dame_pre_inscription_action'] ) ) {
		// Nonce check for the custom action is also required.
		if ( ! isset( $_POST['dame_pre_inscription_action_nonce'] ) || ! wp_verify_nonce( $_POST['dame_pre_inscription_action_nonce'], 'dame_pre_inscription_process_action' ) ) {
			return;
		}
		$action = sanitize_key( $_POST['dame_pre_inscription_action'] );

		switch ( $action ) {
			case 'delete':
				wp_delete_post( $post_id, true );
				wp_safe_redirect( admin_url( 'edit.php?post_type=dame_pre_inscription&message=101' ) ); // Custom message
				exit;

			case 'validate_new':
			case 'validate_update':
				// Data is already saved, so we can read it fresh from the DB.
				$pre_inscription_meta = get_post_meta( $post_id );
				$adherent_meta = array();
				foreach ( $pre_inscription_meta as $key => $value ) {
					if ( strpos( $key, '_dame_' ) === 0 ) {
						$adherent_meta[ $key ] = maybe_unserialize( $value[0] );
					}
				}

				$post_title = get_the_title( $post_id );
				$adherent_id = 0;
				$redirect_message = 0;

				if ( 'validate_new' === $action ) {
					$adherent_post_data = array(
						'post_title'  => $post_title,
						'post_type'   => 'adherent',
						'post_status' => 'publish',
						'meta_input'  => $adherent_meta,
					);
					$adherent_id = wp_insert_post( $adherent_post_data, true );
					$redirect_message = 6; // Post published.
				} else { // validate_update
					$adherent_id = isset( $_POST['dame_matched_adherent_id'] ) ? absint( $_POST['dame_matched_adherent_id'] ) : 0;
					if ( ! $adherent_id ) {
						// Fallback: treat as new if ID is missing.
						$adherent_id = wp_insert_post( array( 'post_title' => $post_title, 'post_type' => 'adherent', 'post_status' => 'publish', 'meta_input' => $adherent_meta ) );
					} else {
						// Update existing adherent
						wp_update_post( array( 'ID' => $adherent_id, 'post_title' => $post_title ) );
						foreach ( $adherent_meta as $key => $value ) {
							update_post_meta( $adherent_id, $key, $value );
						}
						$redirect_message = 1; // Post updated.
					}
				}

				if ( is_wp_error( $adherent_id ) ) {
					return; // Error handling can be improved with an admin notice.
				}

				// Set adherent to 'Active' for the current season
				$current_season_tag_id = get_option( 'dame_current_season_tag_id' );
				if ( $current_season_tag_id ) {
					wp_add_object_terms( $adherent_id, (int) $current_season_tag_id, 'dame_saison_adhesion' );
				}

				// Delete the pre-inscription post
				wp_delete_post( $post_id, true );

				// Redirect to the adherent's edit page
				$redirect_url = get_edit_post_link( $adherent_id, 'raw' );
				wp_safe_redirect( add_query_arg( 'message', $redirect_message, $redirect_url ) );
				exit;
		}
	}

	// Re-hook the function if no action was taken that resulted in an exit.
	add_action( 'save_post_dame_pre_inscription', 'dame_process_pre_inscription_actions' );
}
add_action( 'save_post_dame_pre_inscription', 'dame_process_pre_inscription_actions' );
