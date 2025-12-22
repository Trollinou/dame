<?php
/**
 * Adherent Diverse Info Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\Adherent;

/**
 * Class Diverse
 */
class Diverse {

	/**
	 * Register the meta box.
	 */
	public function register() {
		add_meta_box(
			'dame_diverse_info_metabox',
			__( 'Informations diverses', 'dame' ),
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
		$get_value = function( $field_name ) use ( $post, $transient_data ) {
			return isset( $transient_data[ $field_name ] )
				? $transient_data[ $field_name ]
				: get_post_meta( $post->ID, '_' . $field_name, true );
		};

		$autre_telephone  = $get_value( 'dame_autre_telephone' );
		$taille_vetements = $get_value( 'dame_taille_vetements' );

		$taille_vetements_options = array( 'Non renseigné', '8/10', '10/12', '12/14', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL' );
		if ( ! $taille_vetements || ! in_array( $taille_vetements, $taille_vetements_options, true ) ) {
			$taille_vetements = 'Non renseigné';
		}

		$allergies        = $get_value( 'dame_allergies' );
		$diet             = $get_value( 'dame_diet' );
		$transport        = $get_value( 'dame_transport' );
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
	 * Save the meta box.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['dame_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['dame_metabox_nonce'], 'dame_save_adherent_meta' ) ) {
			return;
		}

		$fields = [
			'dame_autre_telephone' => 'sanitize_text_field',
			'dame_allergies' => 'sanitize_text_field',
			'dame_diet' => 'sanitize_text_field',
			'dame_transport' => 'sanitize_text_field',
			'dame_taille_vetements' => 'sanitize_text_field',
		];

		foreach ( $fields as $field_name => $sanitize_callback ) {
			if ( isset( $_POST[ $field_name ] ) ) {
				$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) );

				if ( 'dame_taille_vetements' === $field_name ) {
					$taille_vetements_options = array( 'Non renseigné', '8/10', '10/12', '12/14', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL' );
					if ( ! in_array( $value, $taille_vetements_options, true ) ) {
						$value = 'Non renseigné';
					}
				}

				update_post_meta( $post_id, '_' . $field_name, $value );
			}
		}
	}
}
