<?php
/**
 * Adherent Classification & Checklist Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\Adherent;

/**
 * Class Classification
 */
class Classification {

	/**
	 * Register the meta box.
	 */
	public function register() {
		add_meta_box(
			'dame_classification_metabox',
			__( 'Classification et Adhésion', 'dame' ),
			[ $this, 'render' ],
			'adherent',
			'side',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render( $post ) {
		$transient_data = get_transient( 'dame_post_data_' . $post->ID );
		$get_value = function( $field_name ) use ( $post, $transient_data ) {
			return isset( $transient_data[ $field_name ] )
				? $transient_data[ $field_name ]
				: get_post_meta( $post->ID, '_' . $field_name, true );
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
				$health_doc_options = function_exists( 'dame_get_health_document_options' ) ? dame_get_health_document_options() : [];
				$health_document = get_post_meta( $post->ID, '_dame_health_document', true );
				foreach ( $health_doc_options as $value => $label ) :
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
		$exclude_users = function_exists( 'dame_get_assigned_user_ids' ) ? dame_get_assigned_user_ids( $post->ID ) : [];

		// Get the total number of users to check for an edge case.
		$user_count_result = count_users();
		$total_users       = isset( $user_count_result['total_users'] ) ? $user_count_result['total_users'] : 0;

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
	 * Save the meta box.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['dame_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['dame_metabox_nonce'], 'dame_save_adherent_meta' ) ) {
			return;
		}

		// Validation
		if ( empty( $_POST['dame_license_type'] ) ) {
			// Logic handled by Identity for error transient?
			// We should probably check here too.
			// The single save function pattern is hard to split for shared errors.
			// But Identity already sets transient if license type is missing?
			// No, Identity checks 'dame_license_type' in POST but it is rendered by Classification!
			// This means Identity validates fields that belong to Classification.
			// This is a cross-dependency.
			// My `Identity::save` code:
			// `if ( empty( $_POST['dame_license_type'] ) ) { $errors[] = ... }`?
			// Let's check `Identity.php` I wrote.
			// It DID NOT include 'dame_license_type' in `save` validation. I removed it because I saw it was here.
			// So I must validate it here.
			$errors = [];
			if ( empty( $_POST['dame_license_type'] ) ) {
				$errors[] = __( 'Le type de licence est obligatoire.', 'dame' );
			}
			if ( ! empty( $_POST['dame_license_number'] ) && ! preg_match( '/^[A-Z][0-9]{5}$/', $_POST['dame_license_number'] ) ) {
				$errors[] = __( 'Le format du numéro de licence est invalide. Il doit être une lettre majuscule suivie de 5 chiffres (ex: A12345).', 'dame' );
			}

			if ( ! empty( $errors ) ) {
				$existing_error = get_transient( 'dame_error_message' );
				if ( $existing_error ) {
					$errors_str = $existing_error . '<br>' . implode( '<br>', $errors );
				} else {
					$errors_str = implode( '<br>', $errors );
				}
				set_transient( 'dame_error_message', $errors_str, 10 );

				// We also need to save transient data for THESE fields to prevent data loss.
				$post_data_to_save = get_transient( 'dame_post_data_' . $post_id ) ?: [];
				foreach ( $_POST as $key => $value ) {
					if ( strpos( $key, 'dame_' ) === 0 ) {
						$post_data_to_save[ $key ] = wp_unslash( $value );
					}
				}
				set_transient( 'dame_post_data_' . $post_id, $post_data_to_save, 60 );
				return;
			}
		}

		// Save Meta
		$fields = [
			'dame_license_number' => 'sanitize_text_field',
			'dame_license_type' => 'sanitize_text_field',
			'dame_health_document' => 'sanitize_key',
			'dame_adherent_honorabilite' => 'sanitize_text_field',
			'dame_arbitre_level' => 'sanitize_text_field',
		];

		foreach ( $fields as $field_name => $sanitize_callback ) {
			if ( isset( $_POST[ $field_name ] ) ) {
				$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) );
				update_post_meta( $post_id, '_' . $field_name, $value );
			}
		}

		// Handle Linked User
		if ( isset( $_POST['dame_linked_wp_user'] ) ) {
			$linked_user_id = intval( $_POST['dame_linked_wp_user'] );
			if ( $linked_user_id > 0 ) {
				update_post_meta( $post_id, '_dame_linked_wp_user', $linked_user_id );
			} else {
				delete_post_meta( $post_id, '_dame_linked_wp_user' );
			}
		}

		// Handle Membership Status (Taxonomy)
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
	}
}
