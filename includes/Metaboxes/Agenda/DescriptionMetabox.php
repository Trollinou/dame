<?php
/**
 * Agenda Description Metabox.
 *
 * @package DAME\Metaboxes\Agenda
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Agenda;

use WP_Post;

/**
 * Renders the Description and Competition settings metabox for Agenda events.
 */
class DescriptionMetabox {

	/**
	 * Renders the meta box for the agenda's description.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render( WP_Post $post ): void {
		$transient_data = get_transient( 'dame_agenda_post_data_' . $post->ID );

		$get_value = function ( string $field_name, string $default_val = '' ) use ( $post, $transient_data ): string {
			$meta_key = 'dame_' . $field_name;
			if ( is_array( $transient_data ) && isset( $transient_data[ $meta_key ] ) ) {
				return esc_attr( (string) $transient_data[ $meta_key ] );
			}
			$val = (string) get_post_meta( $post->ID, '_' . $meta_key, true );
			return '' !== $val ? $val : $default_val;
		};

		$competition_type  = $get_value( 'competition_type', '' );
		$competition_level = $get_value( 'competition_level', 'departementale' );
		$description       = $get_value( 'agenda_description' );
		?>
		<table class="form-table">
			<tr>
				<th><label><?php esc_html_e( 'Type de compétition', 'dame' ); ?> <span class="dame-required">*</span></label></th>
				<td>
					<div class="dame-radio-group">
						<label><input type="radio" name="dame_competition_type" value="non" <?php checked( $competition_type, 'non' ); ?> required> <?php esc_html_e( 'Non', 'dame' ); ?></label>
						<label><input type="radio" name="dame_competition_type" value="individuelle" <?php checked( $competition_type, 'individuelle' ); ?>> <?php esc_html_e( 'Individuelle', 'dame' ); ?></label>
						<label><input type="radio" name="dame_competition_type" value="equipe" <?php checked( $competition_type, 'equipe' ); ?>> <?php esc_html_e( 'Par équipe', 'dame' ); ?></label>
					</div>
				</td>
			</tr>
			<tr id="dame_competition_level_wrapper">
				<th><label><?php esc_html_e( 'Niveau de compétition', 'dame' ); ?></label></th>
				<td>
					<div class="dame-radio-group">
						<label><input type="radio" name="dame_competition_level" value="departementale" <?php checked( $competition_level, 'departementale' ); ?>> <?php esc_html_e( 'Départementale', 'dame' ); ?></label>
						<label><input type="radio" name="dame_competition_level" value="regionale" <?php checked( $competition_level, 'regionale' ); ?>> <?php esc_html_e( 'Régionale', 'dame' ); ?></label>
						<label><input type="radio" name="dame_competition_level" value="nationale" <?php checked( $competition_level, 'nationale' ); ?>> <?php esc_html_e( 'Nationale', 'dame' ); ?></label>
					</div>
				</td>
			</tr>
		</table>
		<?php

		wp_editor(
			$description,
			'dame_agenda_description',
			array(
				'textarea_name' => 'dame_agenda_description',
				'teeny'         => false,
				'media_buttons' => false,
				'textarea_rows' => 5,
				'quicktags'     => false,
				'tinymce'       => array(
					'toolbar1' => 'undo redo | cut copy pastetext | bold italic underline strikethrough | bullist numlist | alignleft aligncenter alignright | forecolor formatselect | removeformat',
					'toolbar2' => '',
					'toolbar3' => '',
				),
			)
		);
	}
}
