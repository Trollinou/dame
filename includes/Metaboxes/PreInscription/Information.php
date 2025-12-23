<?php
/**
 * Adherent Information Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\PreInscription;

use DAME\Services\Data_Provider;

/**
 * Class Information
 */
class Information {

	/**
	 * Initialize the metabox.
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_box' ] );
		add_action( 'save_post', [ $this, 'save' ] );
	}

	/**
	 * Add the meta box.
	 */
	public function add_box() {
		add_meta_box(
			'dame_pre_inscription_information',
			__( 'Informations de l\'adhérent', 'dame' ),
			[ $this, 'render' ],
			'dame_pre_inscription',
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render( $post ) {
		wp_nonce_field( 'dame_save_pre_inscription_info', 'dame_pre_inscription_info_nonce' );

		// Helper to get value
		$get_val = function( $key ) use ( $post ) {
			return get_post_meta( $post->ID, $key, true );
		};

		?>
		<div class="dame-metabox-section">
			<h4><?php _e( 'Identité', 'dame' ); ?></h4>
			<p>
				<label for="dame_birth_name"><strong><?php _e( 'Nom de naissance :', 'dame' ); ?></strong></label><br>
				<input type="text" name="dame_birth_name" id="dame_birth_name" value="<?php echo esc_attr( $get_val( '_dame_birth_name' ) ); ?>" class="regular-text">
			</p>
			<p>
				<label for="dame_last_name"><strong><?php _e( 'Nom d\'usage :', 'dame' ); ?></strong></label><br>
				<input type="text" name="dame_last_name" id="dame_last_name" value="<?php echo esc_attr( $get_val( '_dame_last_name' ) ); ?>" class="regular-text">
			</p>
			<p>
				<label for="dame_first_name"><strong><?php _e( 'Prénom :', 'dame' ); ?></strong></label><br>
				<input type="text" name="dame_first_name" id="dame_first_name" value="<?php echo esc_attr( $get_val( '_dame_first_name' ) ); ?>" class="regular-text">
			</p>
			<p>
				<label for="dame_sexe"><strong><?php _e( 'Sexe :', 'dame' ); ?></strong></label><br>
				<select name="dame_sexe" id="dame_sexe">
					<?php
					$sexe = $get_val( '_dame_sexe' );
					$options = [ 'Masculin', 'Féminin', 'Non précisé' ];
					foreach ( $options as $opt ) {
						echo '<option value="' . esc_attr( $opt ) . '" ' . selected( $sexe, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
					}
					?>
				</select>
			</p>
			<p>
				<label for="dame_birth_date"><strong><?php _e( 'Date de naissance :', 'dame' ); ?></strong></label><br>
				<input type="date" name="dame_birth_date" id="dame_birth_date" value="<?php echo esc_attr( $get_val( '_dame_birth_date' ) ); ?>">
			</p>
			<p>
				<label for="dame_birth_city"><strong><?php _e( 'Ville de naissance :', 'dame' ); ?></strong></label><br>
				<input type="text" name="dame_birth_city" id="dame_birth_city" value="<?php echo esc_attr( $get_val( '_dame_birth_city' ) ); ?>" class="regular-text dame-js-birth-city">
			</p>
		</div>

		<hr>

		<div class="dame-metabox-section">
			<h4><?php _e( 'Contact', 'dame' ); ?></h4>
			<p>
				<label for="dame_email"><strong><?php _e( 'Email :', 'dame' ); ?></strong></label><br>
				<input type="email" name="dame_email" id="dame_email" value="<?php echo esc_attr( $get_val( '_dame_email' ) ); ?>" class="regular-text">
			</p>
			<p>
				<label for="dame_phone_number"><strong><?php _e( 'Téléphone :', 'dame' ); ?></strong></label><br>
				<input type="text" name="dame_phone_number" id="dame_phone_number" value="<?php echo esc_attr( $get_val( '_dame_phone_number' ) ); ?>" class="regular-text">
			</p>
			<p>
				<label for="dame_address_1"><strong><?php _e( 'Adresse :', 'dame' ); ?></strong></label><br>
				<input type="text" name="dame_address_1" id="dame_address_1" value="<?php echo esc_attr( $get_val( '_dame_address_1' ) ); ?>" class="large-text dame-js-address"><br>
				<input type="text" name="dame_address_2" id="dame_address_2" value="<?php echo esc_attr( $get_val( '_dame_address_2' ) ); ?>" class="large-text" placeholder="<?php _e( 'Complément', 'dame' ); ?>">
			</p>
			<p>
				<label for="dame_postal_code"><strong><?php _e( 'Code Postal :', 'dame' ); ?></strong></label><br>
				<input type="text" name="dame_postal_code" id="dame_postal_code" value="<?php echo esc_attr( $get_val( '_dame_postal_code' ) ); ?>" class="dame-js-zip">
			</p>
			<p>
				<label for="dame_city"><strong><?php _e( 'Ville :', 'dame' ); ?></strong></label><br>
				<input type="text" name="dame_city" id="dame_city" value="<?php echo esc_attr( $get_val( '_dame_city' ) ); ?>" class="regular-text dame-js-city">
			</p>
			<p>
				<label for="dame_profession"><strong><?php _e( 'Profession :', 'dame' ); ?></strong></label><br>
				<input type="text" name="dame_profession" id="dame_profession" value="<?php echo esc_attr( $get_val( '_dame_profession' ) ); ?>" class="regular-text">
			</p>
		</div>

		<hr>

		<div class="dame-metabox-section">
			<h4><?php _e( 'Club', 'dame' ); ?></h4>
			<p>
				<label for="dame_license_type"><strong><?php _e( 'Type de licence :', 'dame' ); ?></strong></label><br>
				<select name="dame_license_type" id="dame_license_type">
					<option value="A" <?php selected( $get_val( '_dame_license_type' ), 'A' ); ?>><?php _e( 'Licence A', 'dame' ); ?></option>
					<option value="B" <?php selected( $get_val( '_dame_license_type' ), 'B' ); ?>><?php _e( 'Licence B', 'dame' ); ?></option>
				</select>
			</p>
			<p>
				<label for="dame_taille_vetements"><strong><?php _e( 'Taille de vêtements :', 'dame' ); ?></strong></label><br>
				<select name="dame_taille_vetements" id="dame_taille_vetements">
					<?php
					$current_size = $get_val( '_dame_taille_vetements' );
					$sizes = Data_Provider::get_clothing_sizes();
					foreach ( $sizes as $size ) {
						echo '<option value="' . esc_attr( $size ) . '" ' . selected( $current_size, $size, false ) . '>' . esc_html( $size ) . '</option>';
					}
					?>
				</select>
			</p>
			<p>
				<label for="dame_health_document"><strong><?php _e( 'Document de santé :', 'dame' ); ?></strong></label><br>
				<select name="dame_health_document" id="dame_health_document">
					<?php
					$current_doc = $get_val( '_dame_health_document' );
					$docs = Data_Provider::get_health_document_options();
					foreach ( $docs as $key => $label ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $current_doc, $key, false ) . '>' . esc_html( $label ) . '</option>';
					}
					?>
				</select>
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['dame_pre_inscription_info_nonce'] ) || ! wp_verify_nonce( $_POST['dame_pre_inscription_info_nonce'], 'dame_save_pre_inscription_info' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( 'dame_pre_inscription' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = [
			'dame_birth_name', 'dame_last_name', 'dame_first_name', 'dame_sexe', 'dame_birth_date', 'dame_birth_city',
			'dame_email', 'dame_phone_number', 'dame_address_1', 'dame_address_2', 'dame_postal_code', 'dame_city', 'dame_profession',
			'dame_license_type', 'dame_taille_vetements', 'dame_health_document'
		];

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
	}
}
