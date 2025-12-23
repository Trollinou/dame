<?php
/**
 * Legal Guardians Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\PreInscription;

/**
 * Class LegalGuardians
 */
class LegalGuardians {

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
			'dame_pre_inscription_legal_guardians',
			__( 'Représentants Légaux', 'dame' ),
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
		wp_nonce_field( 'dame_save_pre_inscription_legal', 'dame_pre_inscription_legal_nonce' );

		$get_val = function( $key ) use ( $post ) {
			return get_post_meta( $post->ID, $key, true );
		};

		for ( $i = 1; $i <= 2; $i++ ) {
			?>
			<div class="dame-metabox-section">
				<h4><?php printf( __( 'Représentant Légal %d', 'dame' ), $i ); ?></h4>
				<p>
					<label><strong><?php _e( 'Nom de naissance :', 'dame' ); ?></strong></label><br>
					<input type="text" name="dame_legal_rep_<?php echo $i; ?>_last_name" value="<?php echo esc_attr( $get_val( "_dame_legal_rep_{$i}_last_name" ) ); ?>" class="regular-text">
				</p>
				<p>
					<label><strong><?php _e( 'Prénom :', 'dame' ); ?></strong></label><br>
					<input type="text" name="dame_legal_rep_<?php echo $i; ?>_first_name" value="<?php echo esc_attr( $get_val( "_dame_legal_rep_{$i}_first_name" ) ); ?>" class="regular-text">
				</p>
				<p>
					<label><strong><?php _e( 'Date de naissance :', 'dame' ); ?></strong></label><br>
					<input type="date" name="dame_legal_rep_<?php echo $i; ?>_date_naissance" value="<?php echo esc_attr( $get_val( "_dame_legal_rep_{$i}_date_naissance" ) ); ?>">
				</p>
				<p>
					<label><strong><?php _e( 'Ville de naissance :', 'dame' ); ?></strong></label><br>
					<input type="text" name="dame_legal_rep_<?php echo $i; ?>_commune_naissance" value="<?php echo esc_attr( $get_val( "_dame_legal_rep_{$i}_commune_naissance" ) ); ?>" class="regular-text dame-js-birth-city">
				</p>
				<p>
					<label><strong><?php _e( 'Téléphone :', 'dame' ); ?></strong></label><br>
					<input type="text" name="dame_legal_rep_<?php echo $i; ?>_phone" value="<?php echo esc_attr( $get_val( "_dame_legal_rep_{$i}_phone" ) ); ?>" class="regular-text">
				</p>
				<p>
					<label><strong><?php _e( 'Email :', 'dame' ); ?></strong></label><br>
					<input type="email" name="dame_legal_rep_<?php echo $i; ?>_email" value="<?php echo esc_attr( $get_val( "_dame_legal_rep_{$i}_email" ) ); ?>" class="regular-text">
				</p>
				<p>
					<label><strong><?php _e( 'Profession :', 'dame' ); ?></strong></label><br>
					<input type="text" name="dame_legal_rep_<?php echo $i; ?>_profession" value="<?php echo esc_attr( $get_val( "_dame_legal_rep_{$i}_profession" ) ); ?>" class="regular-text">
				</p>
				<p>
					<label><strong><?php _e( 'Adresse :', 'dame' ); ?></strong></label><br>
					<input type="text" name="dame_legal_rep_<?php echo $i; ?>_address_1" value="<?php echo esc_attr( $get_val( "_dame_legal_rep_{$i}_address_1" ) ); ?>" class="large-text dame-js-address"><br>
					<input type="text" name="dame_legal_rep_<?php echo $i; ?>_address_2" value="<?php echo esc_attr( $get_val( "_dame_legal_rep_{$i}_address_2" ) ); ?>" class="large-text" placeholder="<?php _e( 'Complément', 'dame' ); ?>">
				</p>
				<p>
					<label><strong><?php _e( 'Code Postal :', 'dame' ); ?></strong></label><br>
					<input type="text" name="dame_legal_rep_<?php echo $i; ?>_postal_code" value="<?php echo esc_attr( $get_val( "_dame_legal_rep_{$i}_postal_code" ) ); ?>" class="dame-js-zip">
				</p>
				<p>
					<label><strong><?php _e( 'Ville :', 'dame' ); ?></strong></label><br>
					<input type="text" name="dame_legal_rep_<?php echo $i; ?>_city" value="<?php echo esc_attr( $get_val( "_dame_legal_rep_{$i}_city" ) ); ?>" class="regular-text dame-js-city">
				</p>
			</div>
			<?php if ( $i === 1 ) echo '<hr>'; ?>
			<?php
		}
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['dame_pre_inscription_legal_nonce'] ) || ! wp_verify_nonce( $_POST['dame_pre_inscription_legal_nonce'], 'dame_save_pre_inscription_legal' ) ) {
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
			'last_name', 'first_name', 'date_naissance', 'commune_naissance', 'phone', 'email', 'profession',
			'address_1', 'address_2', 'postal_code', 'city'
		];

		for ( $i = 1; $i <= 2; $i++ ) {
			foreach ( $fields as $field_suffix ) {
				$field_name = "dame_legal_rep_{$i}_{$field_suffix}";
				if ( isset( $_POST[ $field_name ] ) ) {
					// Use specific sanitization for email
					if ( 'email' === $field_suffix ) {
						update_post_meta( $post_id, '_' . $field_name, sanitize_email( $_POST[ $field_name ] ) );
					} else {
						update_post_meta( $post_id, '_' . $field_name, sanitize_text_field( $_POST[ $field_name ] ) );
					}
				}
			}
		}
	}
}
