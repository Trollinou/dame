<?php
/**
 * Adherent Identity Metabox.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Adherent;

use WP_Post;

/**
 * Class Identity
 */
class Identity {

	/**
	 * Register the meta box.
	 */
	public function register(): void {
		add_meta_box(
			'dame_adherent_details_metabox',
			__( 'Informations sur l\'adhérent', 'dame' ),
			array( $this, 'render' ),
			'adherent',
			'normal',
			'high'
		);
	}

	/**
	 * Render the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'dame_save_adherent_meta', 'dame_metabox_nonce' );

		$transient_data = get_transient( 'dame_post_data_' . $post->ID );

		$user_id  = get_current_user_id();
		$list_url = $user_id ? (string) get_user_meta( $user_id, 'dame_last_adherent_list_url', true ) : '';
		if ( empty( $list_url ) ) {
			$list_url = admin_url( 'edit.php?post_type=adherent' );
		} else {
			$list_url = admin_url( ltrim( str_replace( '/wp-admin/', '', $list_url ), '/' ) );
		}

		?>
		<style>
			.dame-back-link {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				text-decoration: none;
				color: #1e293b;
				background-color: #f8fafc;
				border: 1px solid #cbd5e1;
				border-radius: 6px;
				padding: 8px 16px;
				font-weight: 500;
				font-size: 13px;
				transition: all 0.15s ease-in-out;
				box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
			}
			.dame-back-link:hover {
				background-color: #f1f5f9;
				border-color: #94a3b8;
				color: #0f172a;
			}
			.dame-back-link:hover .dashicons {
				transform: translateX(-3px);
				color: #0f172a;
			}
			.dame-back-link .dashicons {
				font-size: 18px;
				width: 18px;
				height: 18px;
				line-height: 18px;
				margin: 0;
				color: #64748b;
				transition: transform 0.15s ease-in-out;
			}
		</style>
		<div style="margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
			<a href="<?php echo esc_url( $list_url ); ?>" class="dame-back-link">
				<span class="dashicons dashicons-arrow-left-alt"></span>
				<span style="line-height: 1;"><?php esc_html_e( 'Retour à la liste filtrée', 'dame' ); ?></span>
			</a>
		</div>
		<?php

		$get_value = function ( $field_name, $default_val = '' ) use ( $post, $transient_data ) {
			return isset( $transient_data[ $field_name ] )
				? $transient_data[ $field_name ]
				: get_post_meta( $post->ID, '_' . $field_name, true );
		};

		// Retrieve values using the helper function
		$birth_name          = $get_value( 'dame_birth_name' );
		$last_name           = $get_value( 'dame_last_name' );
		$first_name          = $get_value( 'dame_first_name' );
		$sexe                = $get_value( 'dame_sexe', 'Non précisé' );
		$birth_date          = $get_value( 'dame_birth_date' );
		$birth_city          = $get_value( 'dame_birth_city' );
		$phone               = $get_value( 'dame_phone_number' );
		$email               = $get_value( 'dame_email' );
		$profession          = $get_value( 'dame_profession' );
		$address_1           = $get_value( 'dame_address_1' );
		$address_2           = $get_value( 'dame_address_2' );
		$postal_code         = $get_value( 'dame_postal_code' );
		$city                = $get_value( 'dame_city' );
		$country             = $get_value( 'dame_country', 'FR' );
		$region              = $get_value( 'dame_region', 'BFC' );
		$department          = $get_value( 'dame_department', '39' );
		$email_refuses_comms = $get_value( 'dame_email_refuses_comms', '0' );
		$latitude            = $get_value( 'dame_latitude' );
		$longitude           = $get_value( 'dame_longitude' );
		$distance            = $get_value( 'dame_distance' );
		$travel_time         = $get_value( 'dame_travel_time' );

		?>
		<!-- Hidden fields for geocoding -->
		<input type="hidden" id="dame_latitude" name="dame_latitude" value="<?php echo esc_attr( $latitude ); ?>" class="dame-js-lat" data-group="adherent" />
		<input type="hidden" id="dame_longitude" name="dame_longitude" value="<?php echo esc_attr( $longitude ); ?>" class="dame-js-long dame-js-lng" data-group="adherent" />
		<input type="hidden" id="dame_distance" name="dame_distance" value="<?php echo esc_attr( $distance ); ?>" class="dame-js-dist" data-group="adherent" />
		<input type="hidden" id="dame_travel_time" name="dame_travel_time" value="<?php echo esc_attr( $travel_time ); ?>" class="dame-js-time" data-group="adherent" />

		<table class="form-table">
			<tr>
				<th><label for="dame_birth_name"><?php esc_html_e( 'Nom de naissance', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
				<td><input type="text" id="dame_birth_name" name="dame_birth_name" value="<?php echo esc_attr( $birth_name ); ?>" class="regular-text" required="required" /></td>
			</tr>
			<tr>
				<th><label for="dame_last_name"><?php esc_html_e( 'Nom d\'usage', 'dame' ); ?></label></th>
				<td><input type="text" id="dame_last_name" name="dame_last_name" value="<?php echo esc_attr( $last_name ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="dame_first_name"><?php esc_html_e( 'Prénom', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
				<td><input type="text" id="dame_first_name" name="dame_first_name" value="<?php echo esc_attr( $first_name ); ?>" class="regular-text" required="required" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Sexe', 'dame' ); ?> <span class="description">(obligatoire)</span></th>
				<td>
					<label style="margin-right: 15px;"><input type="radio" name="dame_sexe" value="Masculin" <?php checked( $sexe, 'Masculin' ); ?> required="required"/> <?php esc_html_e( 'Masculin', 'dame' ); ?></label>
					<label style="margin-right: 15px;"><input type="radio" name="dame_sexe" value="Féminin" <?php checked( $sexe, 'Féminin' ); ?> /> <?php esc_html_e( 'Féminin', 'dame' ); ?></label>
					<label><input type="radio" name="dame_sexe" value="Non précisé" <?php checked( $sexe, 'Non précisé' ); ?> /> <?php esc_html_e( 'Non précisé', 'dame' ); ?></label>
				</td>
			</tr>
			<tr>
				<th><label for="dame_birth_date"><?php esc_html_e( 'Date de naissance', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
				<td><input type="date" id="dame_birth_date" name="dame_birth_date" value="<?php echo esc_attr( $birth_date ); ?>" required="required"/></td>
			</tr>
			<tr>
				<th><label for="dame_birth_city"><?php esc_html_e( 'Lieu de naissance', 'dame' ); ?></label></th>
				<td>
					<div class="dame-autocomplete-wrapper">
						<input type="text" id="dame_birth_city" name="dame_birth_city" value="<?php echo esc_attr( $birth_city ); ?>" placeholder="<?php esc_attr_e( 'Lieu de naissance (Code)', 'dame' ); ?>" class="regular-text dame-js-birth-city" />
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="dame_phone_number"><?php esc_html_e( 'Numéro de téléphone', 'dame' ); ?></label></th>
				<td><input type="tel" id="dame_phone_number" name="dame_phone_number" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="dame_email"><?php esc_html_e( 'Email', 'dame' ); ?></label></th>
				<td>
					<input type="email" id="dame_email" name="dame_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" />
					<label>
						<input type="checkbox" name="dame_email_refuses_comms" value="1" <?php checked( $email_refuses_comms, '1' ); ?> />
						<?php esc_html_e( 'Refus mailing', 'dame' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="dame_profession"><?php esc_html_e( 'Profession', 'dame' ); ?></label></th>
				<td><input type="text" id="dame_profession" name="dame_profession" value="<?php echo esc_attr( $profession ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="dame_address_1"><?php esc_html_e( 'Adresse', 'dame' ); ?></label></th>
				<td>
					<div class="dame-autocomplete-wrapper" style="position: relative;">
						<input type="text" id="dame_address_1" name="dame_address_1" value="<?php echo esc_attr( $address_1 ); ?>" class="regular-text dame-js-address" data-group="adherent" autocomplete="off" />
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="dame_address_2"><?php esc_html_e( 'Complément', 'dame' ); ?></label></th>
				<td><input type="text" id="dame_address_2" name="dame_address_2" value="<?php echo esc_attr( $address_2 ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="dame_postal_code"><?php esc_html_e( 'Code Postal / Ville', 'dame' ); ?></label></th>
				<td>
					<div class="dame-inline-fields">
						<input type="text" id="dame_postal_code" name="dame_postal_code" value="<?php echo esc_attr( $postal_code ); ?>" class="postal-code dame-js-zip" data-group="adherent" placeholder="<?php esc_attr_e( 'Code Postal', 'dame' ); ?>" />
						<input type="text" id="dame_city" name="dame_city" value="<?php echo esc_attr( $city ); ?>" class="city dame-js-city" data-group="adherent" placeholder="<?php esc_attr_e( 'Ville', 'dame' ); ?>" />
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="dame_country"><?php esc_html_e( 'Pays', 'dame' ); ?></label></th>
				<td>
					<select id="dame_country" name="dame_country">
						<?php foreach ( \DAME\Services\Data_Provider::get_countries() as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="dame_department"><?php esc_html_e( 'Département', 'dame' ); ?></label></th>
				<td>
					<select id="dame_department" name="dame_department" class="dame-js-dept" data-group="adherent">
						<?php foreach ( \DAME\Services\Data_Provider::get_departments() as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $department, $code ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="dame_region"><?php esc_html_e( 'Région', 'dame' ); ?></label></th>
				<td>
					<select id="dame_region" name="dame_region" class="dame-js-region" data-group="adherent">
						<?php foreach ( \DAME\Services\Data_Provider::get_regions() as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $region, $code ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
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
	public function save( $post_id ): void {
		$nonce = isset( $_POST['dame_metabox_nonce'] ) ? sanitize_key( wp_unslash( $_POST['dame_metabox_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dame_save_adherent_meta' ) ) {
			return;
		}

		$errors     = array();
		$first_name = isset( $_POST['dame_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_first_name'] ) ) : '';
		if ( empty( $first_name ) ) {
			$errors[] = __( 'Le prénom est obligatoire.', 'dame' );
		}
		$birth_name = isset( $_POST['dame_birth_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_birth_name'] ) ) : '';
		if ( empty( $birth_name ) ) {
			$errors[] = __( 'Le nom de naissance est obligatoire.', 'dame' );
		}
		// If usage name is empty, copy birth name.
		$last_name = isset( $_POST['dame_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_last_name'] ) ) : '';
		if ( empty( $last_name ) && ! empty( $birth_name ) ) {
			$_POST['dame_last_name'] = $birth_name;
		}
		$birth_date = isset( $_POST['dame_birth_date'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_birth_date'] ) ) : '';
		if ( empty( $birth_date ) ) {
			$errors[] = __( 'La date de naissance est obligatoire.', 'dame' );
		}

		// Email validation
		$email = isset( $_POST['dame_email'] ) ? sanitize_email( wp_unslash( $_POST['dame_email'] ) ) : '';
		if ( ! empty( $email ) && ! is_email( $email ) ) {
			$errors[] = __( "Le format de l'email de l'adhérent est invalide.", 'dame' );
		}

		if ( ! empty( $errors ) ) {
			set_transient( 'dame_error_message', implode( '<br>', $errors ), 10 );

			// Save posted data to transient to repopulate form
			$post_data_to_save = array();
			foreach ( $_POST as $key => $value ) {
				if ( strpos( $key, 'dame_' ) === 0 ) {
					$post_data_to_save[ $key ] = sanitize_text_field( wp_unslash( $value ) );
				}
			}
			set_transient( 'dame_post_data_' . $post_id, $post_data_to_save, 60 );
			return;
		}

		// Title Generation
		$new_title = \DAME\Core\Utils::generate_adherent_title( $post_id );

		if ( get_the_title( $post_id ) !== $new_title ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $new_title,
					'post_name'  => sanitize_title( $new_title ),
				)
			);
		}

		// Save Fields
		$fields = $this->get_meta_fields();

		foreach ( $fields as $field_name => $sanitize_callback ) {
			if ( isset( $_POST[ $field_name ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized dynamically below via $sanitize_callback.
				$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) );

				if ( 'dame_first_name' === $field_name ) {
					$value = \DAME\Core\Utils::format_firstname( $value );
				}
				if ( 'dame_last_name' === $field_name || 'dame_birth_name' === $field_name ) {
					$value = \DAME\Core\Utils::format_lastname( $value );
				}

				update_post_meta( $post_id, '_' . $field_name, $value );
			} elseif ( 'absint' === $sanitize_callback ) {
					update_post_meta( $post_id, '_' . $field_name, 0 );
			}
		}
	}

	/**
	 * Returns the list of meta fields and their sanitization callbacks.
	 *
	 * @return array<string, string>
	 */
	private function get_meta_fields(): array {
		return array(
			'dame_first_name'          => 'sanitize_text_field',
			'dame_last_name'           => 'sanitize_text_field',
			'dame_birth_name'          => 'sanitize_text_field',
			'dame_birth_date'          => 'sanitize_text_field',
			'dame_birth_city'          => 'sanitize_text_field',
			'dame_email'               => 'sanitize_email',
			'dame_address_1'           => 'sanitize_text_field',
			'dame_address_2'           => 'sanitize_text_field',
			'dame_postal_code'         => 'sanitize_text_field',
			'dame_city'                => 'sanitize_text_field',
			'dame_phone_number'        => 'sanitize_text_field',
			'dame_sexe'                => 'sanitize_text_field',
			'dame_profession'          => 'sanitize_text_field',
			'dame_country'             => 'sanitize_text_field',
			'dame_region'              => 'sanitize_text_field',
			'dame_department'          => 'sanitize_text_field',
			'dame_email_refuses_comms' => 'absint',
			'dame_latitude'            => 'sanitize_text_field',
			'dame_longitude'           => 'sanitize_text_field',
			'dame_distance'            => 'sanitize_text_field',
			'dame_travel_time'         => 'sanitize_text_field',
		);
	}
}
