<?php
/**
 * Adherent CPT metaboxes.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

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
		'high'
	);
	add_meta_box(
		'dame_special_actions_metabox',
		__( 'Actions spéciales', 'dame' ),
		'dame_render_special_actions_metabox',
		'adherent',
		'side',
		'low'
	);

	// Remove the default taxonomy metabox and add our custom one with checkboxes.
	remove_meta_box( 'dame_groupdiv', 'adherent', 'side' );
	add_meta_box(
		'dame_group_checklist_metabox',
		__( 'Groupes', 'dame' ),
		'dame_render_group_checklist_metabox',
		'adherent',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'dame_add_meta_boxes' );


/**
 * Renders the custom meta box for group selection as a checklist.
 *
 * This function mimics the default category metabox, providing a user-friendly
 * checklist of all available "Groupe" terms.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_group_checklist_metabox( $post ) {
	$taxonomy = 'dame_group';
	?>
	<div id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>" class="categorydiv">
		<ul id="<?php echo esc_attr( $taxonomy ); ?>-tabs" class="category-tabs">
			<li class="tabs"><a href="#<?php echo esc_attr( $taxonomy ); ?>-all"><?php echo esc_html__( 'Tous les groupes', 'dame' ); ?></a></li>
		</ul>

		<div id="<?php echo esc_attr( $taxonomy ); ?>-all" class="tabs-panel" style="display: block;">
			<ul id="<?php echo esc_attr( $taxonomy ); ?>checklist" data-wp-lists="list:<?php echo esc_attr( $taxonomy ); ?>" class="categorychecklist form-no-clear">
				<?php
				wp_terms_checklist(
					$post->ID,
					array(
						'taxonomy'      => $taxonomy,
						'popular_cats'  => false,
						'checked_ontop' => false, // Keep alphabetical order.
					)
				);
				?>
			</ul>
		</div>
		<?php
		$tax_obj = get_taxonomy( $taxonomy );
		if ( current_user_can( $tax_obj->cap->manage_terms ) ) {
			echo '<p style="margin-top:1em;"><a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy ) ) . '">' . esc_html( $tax_obj->labels->add_new_item ) . '</a></p>';
		}
		?>
	</div>
	<?php
}


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
 * Open the "Groupes" metabox by default by removing the 'closed' class.
 *
 * @param array $classes An array of postbox classes.
 * @return array The modified array of classes.
 */
function dame_open_group_metabox_by_default( $classes ) {
	// We check the current screen to make sure we're only affecting the intended metabox.
	if ( get_current_screen()->id === 'adherent' ) {
		$classes = array_diff( $classes, array( 'closed' ) );
	}
	return $classes;
}
// Use the new custom metabox ID.
add_filter( 'postbox_classes_adherent_dame_group_checklist_metabox', 'dame_open_group_metabox_by_default' );



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
	$birth_name = $get_value( 'dame_birth_name' );
	$last_name = $get_value( 'dame_last_name' );
	$first_name = $get_value( 'dame_first_name' );
	$sexe = $get_value( 'dame_sexe', 'Non précisé' );
	if ( ! $sexe ) {
		$sexe = 'Non précisé';
	}
	$birth_date = $get_value( 'dame_birth_date' );
	$birth_city = $get_value( 'dame_birth_city' );
	$phone = $get_value( 'dame_phone_number' );
	$email = $get_value( 'dame_email' );
	$email_refuses_comms = $get_value( 'dame_email_refuses_comms' );
	$profession = $get_value( 'dame_profession' );
	$address_1 = $get_value( 'dame_address_1' );
	$address_2 = $get_value( 'dame_address_2' );
	$postal_code = $get_value( 'dame_postal_code' );
	$city = $get_value( 'dame_city' );
	$country = $get_value( 'dame_country' );
	$region = $get_value( 'dame_region' );
	$department = $get_value( 'dame_department' );
	?>
	<table class="form-table">
		<tr>
			<th><label for="dame_birth_name"><?php _e( 'Nom de naissance', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
			<td><input type="text" id="dame_birth_name" name="dame_birth_name" value="<?php echo esc_attr( $birth_name ); ?>" class="regular-text" required="required" /></td>
		</tr>
		<tr>
			<th><label for="dame_last_name"><?php _e( 'Nom d\'usage', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_last_name" name="dame_last_name" value="<?php echo esc_attr( $last_name ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_first_name"><?php _e( 'Prénom', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
			<td><input type="text" id="dame_first_name" name="dame_first_name" value="<?php echo esc_attr( $first_name ); ?>" class="regular-text" required="required" /></td>
		</tr>
		<tr>
			<th><?php _e( 'Sexe', 'dame' ); ?> <span class="description">(obligatoire)</span></th>
			<td>
				<label style="margin-right: 15px;"><input type="radio" name="dame_sexe" value="Masculin" <?php checked( $sexe, 'Masculin' ); ?> required="required"/> <?php _e( 'Masculin', 'dame' ); ?></label>
				<label style="margin-right: 15px;"><input type="radio" name="dame_sexe" value="Féminin" <?php checked( $sexe, 'Féminin' ); ?> /> <?php _e( 'Féminin', 'dame' ); ?></label>
				<label><input type="radio" name="dame_sexe" value="Non précisé" <?php checked( $sexe, 'Non précisé' ); ?> /> <?php _e( 'Non précisé', 'dame' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><label for="dame_birth_date"><?php _e( 'Date de naissance', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
			<td><input type="date" id="dame_birth_date" name="dame_birth_date" value="<?php echo esc_attr( $birth_date ); ?>" required="required"/></td>
		</tr>
		<tr>
			<th><label for="dame_birth_city"><?php _e( 'Lieu de naissance', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_birth_city" name="dame_birth_city" value="<?php echo esc_attr( $birth_city ); ?>" placeholder="<?php _e( 'Lieu de naissance (Code)', 'dame' ); ?>" class="regular-text" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_phone_number"><?php _e( 'Numéro de téléphone', 'dame' ); ?></label></th>
			<td><input type="tel" id="dame_phone_number" name="dame_phone_number" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
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
			<th><label for="dame_profession"><?php _e( 'Profession', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_profession" name="dame_profession" value="<?php echo esc_attr( $profession ); ?>" class="regular-text" /></td>
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
			<th><label for="dame_legal_rep_1_last_name"><?php _e( 'Nom de naissance', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_1_last_name" name="dame_legal_rep_1_last_name" value="<?php echo esc_attr( $rep1_last_name ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_first_name"><?php _e( 'Prénom', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_1_first_name" name="dame_legal_rep_1_first_name" value="<?php echo esc_attr( $rep1_first_name ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_date_naissance"><?php _e( 'Date de naissance', 'dame' ); ?></label></th>
			<td><input type="date" id="dame_legal_rep_1_date_naissance" name="dame_legal_rep_1_date_naissance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_legal_rep_1_date_naissance', true ) ); ?>" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_1_commune_naissance"><?php _e( 'Lieu de naissance', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_legal_rep_1_commune_naissance" name="dame_legal_rep_1_commune_naissance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_legal_rep_1_commune_naissance', true ) ); ?>" class="regular-text" />
				</div>
			</td>
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
			<th><label for="dame_legal_rep_1_phone"><?php _e( 'Numéro de téléphone', 'dame' ); ?></label></th>
			<td><input type="tel" id="dame_legal_rep_1_phone" name="dame_legal_rep_1_phone" value="<?php echo esc_attr( $rep1_phone ); ?>" class="regular-text" /></td>
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
			<th><label for="dame_legal_rep_1_profession"><?php _e( 'Profession', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_1_profession" name="dame_legal_rep_1_profession" value="<?php echo esc_attr( $rep1_profession ); ?>" class="regular-text" /></td>
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
			<th><label for="dame_legal_rep_2_last_name"><?php _e( 'Nom de naissance', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_2_last_name" name="dame_legal_rep_2_last_name" value="<?php echo esc_attr( $rep2_last_name ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_first_name"><?php _e( 'Prénom', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_2_first_name" name="dame_legal_rep_2_first_name" value="<?php echo esc_attr( $rep2_first_name ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_date_naissance"><?php _e( 'Date de naissance', 'dame' ); ?></label></th>
			<td><input type="date" id="dame_legal_rep_2_date_naissance" name="dame_legal_rep_2_date_naissance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_legal_rep_2_date_naissance', true ) ); ?>" /></td>
		</tr>
		<tr>
			<th><label for="dame_legal_rep_2_commune_naissance"><?php _e( 'Lieu de naissance', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper">
					<input type="text" id="dame_legal_rep_2_commune_naissance" name="dame_legal_rep_2_commune_naissance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_legal_rep_2_commune_naissance', true ) ); ?>" class="regular-text" />
				</div>
			</td>
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
			<th><label for="dame_legal_rep_2_phone"><?php _e( 'Numéro de téléphone', 'dame' ); ?></label></th>
			<td><input type="tel" id="dame_legal_rep_2_phone" name="dame_legal_rep_2_phone" value="<?php echo esc_attr( $rep2_phone ); ?>" class="regular-text" /></td>
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
			<th><label for="dame_legal_rep_2_profession"><?php _e( 'Profession', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_legal_rep_2_profession" name="dame_legal_rep_2_profession" value="<?php echo esc_attr( $rep2_profession ); ?>" class="regular-text" /></td>
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
	<hr>
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
	?>
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
	if ( empty( $_POST['dame_birth_name'] ) ) {
		$errors[] = __( 'Le nom de naissance est obligatoire.', 'dame' );
	}
	// Si le nom d'usage est vide, on y copie le nom de naissance.
	if ( empty( $_POST['dame_last_name'] ) ) {
		$_POST['dame_last_name'] = $_POST['dame_birth_name'];
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
		'dame_first_name' => 'sanitize_text_field', 'dame_last_name' => 'sanitize_text_field', 'dame_birth_name' => 'sanitize_text_field',
		'dame_birth_date' => 'sanitize_text_field', 'dame_license_number' => 'sanitize_text_field',
		'dame_birth_city' => 'sanitize_text_field',
		'dame_email' => 'sanitize_email', 'dame_address_1' => 'sanitize_text_field',
		'dame_address_2' => 'sanitize_text_field', 'dame_postal_code' => 'sanitize_text_field',
		'dame_city' => 'sanitize_text_field', 'dame_phone_number' => 'sanitize_text_field',
		'dame_sexe' => 'sanitize_text_field',
		'dame_profession' => 'sanitize_text_field',
		'dame_country' => 'sanitize_text_field', 'dame_region' => 'sanitize_text_field', 'dame_department' => 'sanitize_text_field',
		'dame_school_name' => 'sanitize_text_field', 'dame_school_academy' => 'sanitize_text_field',

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
			if ( 'dame_birth_name' === $field_name ) {
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
