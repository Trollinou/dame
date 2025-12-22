<?php
/**
 * Adherent Legal Representative Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\Adherent;

/**
 * Class Legal
 */
class Legal {

	/**
	 * Register the meta box.
	 */
	public function register() {
		add_meta_box(
			'dame_legal_rep_metabox',
			__( 'Représentants Légaux (si mineur)', 'dame' ),
			[ $this, 'render' ],
			'adherent',
			'normal',
			'default'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render( $post ) {
		$transient_data = get_transient( 'dame_post_data_' . $post->ID );

		$get_value = function( $field_name, $default = '' ) use ( $post, $transient_data ) {
			return isset( $transient_data[ $field_name ] )
				? $transient_data[ $field_name ]
				: get_post_meta( $post->ID, '_' . $field_name, true );
		};

		// Rep 1
		$rep1_first_name = $get_value( 'dame_legal_rep_1_first_name' );
		$rep1_last_name = $get_value( 'dame_legal_rep_1_last_name' );
		$rep1_email = $get_value( 'dame_legal_rep_1_email' );
		$rep1_email_refuses_comms = $get_value( 'dame_legal_rep_1_email_refuses_comms' );
		$rep1_phone = $get_value( 'dame_legal_rep_1_phone' );
		$rep1_profession = $get_value( 'dame_legal_rep_1_profession' );
		$rep1_address_1 = $get_value( 'dame_legal_rep_1_address_1' );
		$rep1_address_2 = $get_value( 'dame_legal_rep_1_address_2' );
		$rep1_postal_code = $get_value( 'dame_legal_rep_1_postal_code' );
		$rep1_city = $get_value( 'dame_legal_rep_1_city' );

		// Rep 2
		$rep2_first_name = $get_value( 'dame_legal_rep_2_first_name' );
		$rep2_last_name = $get_value( 'dame_legal_rep_2_last_name' );
		$rep2_email = $get_value( 'dame_legal_rep_2_email' );
		$rep2_email_refuses_comms = $get_value( 'dame_legal_rep_2_email_refuses_comms' );
		$rep2_phone = $get_value( 'dame_legal_rep_2_phone' );
		$rep2_profession = $get_value( 'dame_legal_rep_2_profession' );
		$rep2_address_1 = $get_value( 'dame_legal_rep_2_address_1' );
		$rep2_address_2 = $get_value( 'dame_legal_rep_2_address_2' );
		$rep2_postal_code = $get_value( 'dame_legal_rep_2_postal_code' );
		$rep2_city = $get_value( 'dame_legal_rep_2_city' );
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
						<input type="text" id="dame_legal_rep_1_commune_naissance" name="dame_legal_rep_1_commune_naissance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_legal_rep_1_commune_naissance', true ) ); ?>" class="regular-text dame-js-birth-city" />
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
						<input type="text" id="dame_legal_rep_1_address_1" name="dame_legal_rep_1_address_1" value="<?php echo esc_attr( $rep1_address_1 ); ?>" class="regular-text dame-js-address" data-group="rep1" autocomplete="off" />
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
						<input type="text" id="dame_legal_rep_1_postal_code" name="dame_legal_rep_1_postal_code" value="<?php echo esc_attr( $rep1_postal_code ); ?>" class="postal-code dame-js-zip" data-group="rep1" placeholder="<?php _e( 'Code Postal', 'dame' ); ?>" />
						<input type="text" id="dame_legal_rep_1_city" name="dame_legal_rep_1_city" value="<?php echo esc_attr( $rep1_city ); ?>" class="city dame-js-city" data-group="rep1" placeholder="<?php _e( 'Ville', 'dame' ); ?>" />
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
						<input type="text" id="dame_legal_rep_2_commune_naissance" name="dame_legal_rep_2_commune_naissance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_legal_rep_2_commune_naissance', true ) ); ?>" class="regular-text dame-js-birth-city" />
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
						<input type="text" id="dame_legal_rep_2_address_1" name="dame_legal_rep_2_address_1" value="<?php echo esc_attr( $rep2_address_1 ); ?>" class="regular-text dame-js-address" data-group="rep2" autocomplete="off" />
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
						<input type="text" id="dame_legal_rep_2_postal_code" name="dame_legal_rep_2_postal_code" value="<?php echo esc_attr( $rep2_postal_code ); ?>" class="postal-code dame-js-zip" data-group="rep2" placeholder="<?php _e( 'Code Postal', 'dame' ); ?>" />
						<input type="text" id="dame_legal_rep_2_city" name="dame_legal_rep_2_city" value="<?php echo esc_attr( $rep2_city ); ?>" class="city dame-js-city" data-group="rep2" placeholder="<?php _e( 'Ville', 'dame' ); ?>" />
					</div>
				</td>
			</tr>
		</table>
		<?php
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

		$errors = [];
		if ( ! empty( $_POST['dame_legal_rep_1_email'] ) && ! is_email( $_POST['dame_legal_rep_1_email'] ) ) {
			$errors[] = __( "Le format de l'email du représentant légal 1 est invalide.", 'dame' );
		}
		if ( ! empty( $_POST['dame_legal_rep_2_email'] ) && ! is_email( $_POST['dame_legal_rep_2_email'] ) ) {
			$errors[] = __( "Le format de l'email du représentant légal 2 est invalide.", 'dame' );
		}

		if ( ! empty( $errors ) ) {
			// Append errors if existing? No simple way. Just set it.
			// Ideally we concatenate.
			$existing_error = get_transient( 'dame_error_message' );
			if ( $existing_error ) {
				$errors_str = $existing_error . '<br>' . implode( '<br>', $errors );
			} else {
				$errors_str = implode( '<br>', $errors );
			}
			set_transient( 'dame_error_message', $errors_str, 10 );

			// Save posted data
			$post_data_to_save = array();
			foreach ( $_POST as $key => $value ) {
				if ( strpos( $key, 'dame_' ) === 0 ) {
					$post_data_to_save[ $key ] = wp_unslash( $value );
				}
			}
			set_transient( 'dame_post_data_' . $post_id, $post_data_to_save, 60 );
			return;
		}

		$fields = [
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

			'dame_legal_rep_1_email_refuses_comms' => 'absint',
			'dame_legal_rep_2_email_refuses_comms' => 'absint',
		];

		foreach ( $fields as $field_name => $sanitize_callback ) {
			if ( isset( $_POST[ $field_name ] ) ) {
				$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) );

				if ( function_exists( 'dame_format_firstname' ) && ( 'dame_legal_rep_1_first_name' === $field_name || 'dame_legal_rep_2_first_name' === $field_name ) ) {
					$value = dame_format_firstname( $value );
				}
				if ( function_exists( 'dame_format_lastname' ) && ( 'dame_legal_rep_1_last_name' === $field_name || 'dame_legal_rep_2_last_name' === $field_name ) ) {
					$value = dame_format_lastname( $value );
				}

				update_post_meta( $post_id, '_' . $field_name, $value );
			} else {
				if ( 'absint' === $sanitize_callback ) {
					update_post_meta( $post_id, '_' . $field_name, 0 );
				}
			}
		}
	}
}
