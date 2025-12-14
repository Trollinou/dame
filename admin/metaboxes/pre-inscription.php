<?php
/**
 * Pre-inscription CPT metaboxes.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

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
	$birth_name         = get_post_meta( $pre_inscription_id, '_dame_birth_name', true );
	$birth_date         = get_post_meta( $pre_inscription_id, '_dame_birth_date', true );
	$matched_adherent_id = 0;

	$name_to_match = ! empty( $birth_name ) ? $birth_name : $last_name;
	$name_key_to_match = ! empty( $birth_name ) ? '_dame_birth_name' : '_dame_last_name';

	if ( $first_name && $name_to_match && $birth_date ) {
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
						'key'     => $name_key_to_match,
						'value'   => $name_to_match,
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
			'Nom de naissance' => array( 'key' => 'dame_birth_name', 'type' => 'text', 'required' => true ),
			'Nom d\'usage' => array( 'key' => 'dame_last_name', 'type' => 'text', 'required' => false ),
			'Prénom' => array( 'key' => 'dame_first_name', 'type' => 'text', 'required' => true ),
			'Sexe' => array( 'key' => 'dame_sexe', 'type' => 'radio', 'options' => array( 'Masculin', 'Féminin', 'Non précisé' ), 'required' => true ),
			'Date de naissance' => array( 'key' => 'dame_birth_date', 'type' => 'date', 'required' => true ),
			'Lieu de naissance' => array( 'key' => 'dame_birth_city', 'type' => 'text_autocomplete' ),
			'Numéro de téléphone' => array( 'key' => 'dame_phone_number', 'type' => 'tel' ),
			'Email' => array( 'key' => 'dame_email', 'type' => 'email' ),
			'Profession' => array( 'key' => 'dame_profession', 'type' => 'text' ),
			'Adresse' => array( 'key' => 'dame_address_1', 'type' => 'text_autocomplete' ),
			'Complément' => array( 'key' => 'dame_address_2', 'type' => 'text' ),
			'Code Postal' => array( 'key' => 'dame_postal_code', 'type' => 'text' ),
			'Ville' => array( 'key' => 'dame_city', 'type' => 'text' ),
			'Taille de vêtements' => array( 'key' => 'dame_taille_vetements', 'type' => 'select', 'options' => array( 'Non renseigné', '8/10', '10/12', '12/14', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL' ) ),
			'Type de licence' => array(
				'key'     => 'dame_license_type',
				'type'    => 'select',
				'options' => array(
					'A' => __( 'Licence A (Cours + Compétition)', 'dame' ),
					'B' => __( 'Licence B (Jeu libre)', 'dame' ),
				),
			),
			'Document de santé' => array( 'key' => 'dame_health_document', 'type' => 'select', 'options_callback' => 'dame_get_health_document_options' ),
		),
		'Représentant Légal 1' => array(
			'Nom de naissance' => array( 'key' => 'dame_legal_rep_1_last_name', 'type' => 'text' ),
			'Prénom' => array( 'key' => 'dame_legal_rep_1_first_name', 'type' => 'text' ),
			'Date de naissance' => array( 'key' => 'dame_legal_rep_1_date_naissance', 'type' => 'date' ),
			'Lieu de naissance' => array( 'key' => 'dame_legal_rep_1_commune_naissance', 'type' => 'text' ),
			'Contrôle d\'honorabilité' => array( 'key' => 'dame_legal_rep_1_honorabilite', 'type' => 'text' ),
			'Numéro de téléphone' => array( 'key' => 'dame_legal_rep_1_phone', 'type' => 'tel' ),
			'Email' => array( 'key' => 'dame_legal_rep_1_email', 'type' => 'email' ),
			'Profession' => array( 'key' => 'dame_legal_rep_1_profession', 'type' => 'text' ),
			'Adresse' => array( 'key' => 'dame_legal_rep_1_address_1', 'type' => 'text_autocomplete' ),
			'Complément' => array( 'key' => 'dame_legal_rep_1_address_2', 'type' => 'text' ),
			'Code Postal' => array( 'key' => 'dame_legal_rep_1_postal_code', 'type' => 'text' ),
			'Ville' => array( 'key' => 'dame_legal_rep_1_city', 'type' => 'text' ),
		),
		'Représentant Légal 2' => array(
			'Nom de naissance' => array( 'key' => 'dame_legal_rep_2_last_name', 'type' => 'text' ),
			'Prénom' => array( 'key' => 'dame_legal_rep_2_first_name', 'type' => 'text' ),
			'Date de naissance' => array( 'key' => 'dame_legal_rep_2_date_naissance', 'type' => 'date' ),
			'Lieu de naissance' => array( 'key' => 'dame_legal_rep_2_commune_naissance', 'type' => 'text' ),
			'Contrôle d\'honorabilité' => array( 'key' => 'dame_legal_rep_2_honorabilite', 'type' => 'text' ),
			'Numéro de téléphone' => array( 'key' => 'dame_legal_rep_2_phone', 'type' => 'tel' ),
			'Email' => array( 'key' => 'dame_legal_rep_2_email', 'type' => 'email' ),
			'Profession' => array( 'key' => 'dame_legal_rep_2_profession', 'type' => 'text' ),
			'Adresse' => array( 'key' => 'dame_legal_rep_2_address_1', 'type' => 'text_autocomplete' ),
			'Complément' => array( 'key' => 'dame_legal_rep_2_address_2', 'type' => 'text' ),
			'Code Postal' => array( 'key' => 'dame_legal_rep_2_postal_code', 'type' => 'text' ),
			'Ville' => array( 'key' => 'dame_legal_rep_2_city', 'type' => 'text' ),
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
			'Nom de naissance'     => 'dame_birth_name',
			'Nom d\'usage'          => 'dame_last_name',
			'Prénom'               => 'dame_first_name',
			'Sexe'                 => 'dame_sexe',
			'Date de naissance'    => 'dame_birth_date',
			'Lieu de naissance'    => 'dame_birth_city',
			'Numéro de téléphone'  => 'dame_phone_number',
			'Email'                => 'dame_email',
			'Profession'           => 'dame_profession',
			'Adresse'              => 'dame_address_1',
			'Complément'           => 'dame_address_2',
			'Code Postal'          => 'dame_postal_code',
			'Ville'                => 'dame_city',
			'Taille de vêtements'  => 'dame_taille_vetements',
			'Type de licence'      => 'dame_license_type',
			'Document de santé'    => 'dame_health_document',
		),
		'Représentant Légal 1' => array(
			'Rep. 1 - Nom de naissance' => 'dame_legal_rep_1_last_name',
			'Rep. 1 - Prénom'           => 'dame_legal_rep_1_first_name',
			'Rep. 1 - Date de naissance' => 'dame_legal_rep_1_date_naissance',
			'Rep. 1 - Lieu de naissance' => 'dame_legal_rep_1_commune_naissance',
			'Rep. 1 - Contrôle d\'honorabilité' => 'dame_legal_rep_1_honorabilite',
			'Rep. 1 - Numéro de téléphone'  => 'dame_legal_rep_1_phone',
			'Rep. 1 - Email'            => 'dame_legal_rep_1_email',
			'Rep. 1 - Profession'       => 'dame_legal_rep_1_profession',
			'Rep. 1 - Adresse'          => 'dame_legal_rep_1_address_1',
			'Rep. 1 - Complément'       => 'dame_legal_rep_1_address_2',
			'Rep. 1 - Code Postal'      => 'dame_legal_rep_1_postal_code',
			'Rep. 1 - Ville'            => 'dame_legal_rep_1_city',
		),
		'Représentant Légal 2' => array(
			'Rep. 2 - Nom de naissance' => 'dame_legal_rep_2_last_name',
			'Rep. 2 - Prénom'           => 'dame_legal_rep_2_first_name',
			'Rep. 2 - Date de naissance' => 'dame_legal_rep_2_date_naissance',
			'Rep. 2 - Lieu de naissance' => 'dame_legal_rep_2_commune_naissance',
			'Rep. 2 - Contrôle d\'honorabilité' => 'dame_legal_rep_2_honorabilite',
			'Rep. 2 - Numéro de téléphone'  => 'dame_legal_rep_2_phone',
			'Rep. 2 - Email'            => 'dame_legal_rep_2_email',
			'Rep. 2 - Profession'       => 'dame_legal_rep_2_profession',
			'Rep. 2 - Adresse'          => 'dame_legal_rep_2_address_1',
			'Rep. 2 - Complément'       => 'dame_legal_rep_2_address_2',
			'Rep. 2 - Code Postal'      => 'dame_legal_rep_2_postal_code',
			'Rep. 2 - Ville'            => 'dame_legal_rep_2_city',
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
	if ( empty( $_POST['dame_last_name'] ) && ! empty( $_POST['dame_birth_name'] ) ) {
		$_POST['dame_last_name'] = $_POST['dame_birth_name'];
	}

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
		'dame_first_name', 'dame_last_name', 'dame_birth_name', 'dame_birth_date', 'dame_license_type', 'dame_birth_city', 'dame_sexe', 'dame_profession',
		'dame_email', 'dame_phone_number', 'dame_address_1', 'dame_address_2', 'dame_postal_code', 'dame_city', 'dame_taille_vetements',
		'dame_health_document', 'dame_legal_rep_1_honorabilite', 'dame_legal_rep_2_honorabilite',
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
			if ( 'dame_birth_name' === $key ) {
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
