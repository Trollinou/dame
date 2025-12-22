<?php
/**
 * Adherent School Info Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\Adherent;

/**
 * Class School
 */
class School {

	/**
	 * Register the meta box.
	 */
	public function register() {
		add_meta_box(
			'dame_school_info_metabox',
			__( 'Informations Scolaires', 'dame' ),
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

		$school_name = $get_value( 'dame_school_name' );
		$school_academy = $get_value( 'dame_school_academy' );
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
						<?php
						$academies = function_exists( 'dame_get_academy_list' ) ? dame_get_academy_list() : [];
						foreach ( $academies as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $school_academy, $code ); ?>><?php echo esc_html( $name ); ?></option>
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
	public function save( $post_id ) {
		if ( ! isset( $_POST['dame_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['dame_metabox_nonce'], 'dame_save_adherent_meta' ) ) {
			return;
		}

		$fields = [
			'dame_school_name' => 'sanitize_text_field',
			'dame_school_academy' => 'sanitize_text_field',
		];

		foreach ( $fields as $field_name => $sanitize_callback ) {
			if ( isset( $_POST[ $field_name ] ) ) {
				$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) );
				update_post_meta( $post_id, '_' . $field_name, $value );
			}
		}
	}
}
