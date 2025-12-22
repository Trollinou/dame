<?php
/**
 * Association Tab.
 *
 * @package DAME
 */

namespace DAME\Admin\Settings\Tabs;

/**
 * Class Association
 */
class Association {

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Association', 'dame' );
	}

	/**
	 * Register settings.
	 */
	public function register() {
		add_settings_section(
			'dame_association_section',
			__( "Informations de l'association", 'dame' ),
			[ $this, 'section_callback' ],
			'dame_association_section_group'
		);

		$fields = [
			'assoc_address_1' => __( 'Adresse', 'dame' ),
			'assoc_address_2' => __( 'Complément', 'dame' ),
			'assoc_postal_code' => __( 'Code Postal', 'dame' ),
			'assoc_city' => __( 'Ville', 'dame' ),
			'assoc_latitude' => __( 'Latitude', 'dame' ),
			'assoc_longitude' => __( 'Longitude', 'dame' ),
		];

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				'dame_' . $key,
				$label,
				[ $this, 'render_field' ],
				'dame_association_section_group',
				'dame_association_section',
				[ 'key' => $key ]
			);
		}
	}

	/**
	 * Section callback.
	 */
	public function section_callback() {
		echo '<p>' . esc_html__( "Saisir ici les informations relatives à l'adresse de l'association. L'autocomplétion est activée sur le champ Adresse.", 'dame' ) . '</p>';
	}

	/**
	 * Render field callback.
	 *
	 * @param array $args Arguments.
	 */
	public function render_field( $args ) {
		$key = $args['key'];
		$options = get_option( 'dame_options' );
		$value = isset( $options[ $key ] ) ? $options[ $key ] : '';

		$wrapper_start = '';
		$wrapper_end = '';
		$readonly = '';
		$class = 'regular-text';
		$extra_attr = '';

		if ( 'assoc_address_1' === $key ) {
			$wrapper_start = '<div class="dame-autocomplete-wrapper" style="position: relative;">';
			$wrapper_end = '</div>';
			$extra_attr = 'autocomplete="off"';
		} elseif ( 'assoc_latitude' === $key || 'assoc_longitude' === $key ) {
			$readonly = 'readonly="readonly"';
		}

		echo $wrapper_start; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<input type="text" id="dame_' . esc_attr( $key ) . '" name="dame_options[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="' . esc_attr( $class ) . '" ' . $readonly . ' ' . $extra_attr . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $wrapper_end; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the tab content.
	 */
	public function render() {
		do_settings_sections( 'dame_association_section_group' );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input New input.
	 * @param array $existing_options Existing options.
	 * @return array Sanitized options.
	 */
	public function sanitize( $input, $existing_options ) {
		$fields = [ 'assoc_address_1', 'assoc_address_2', 'assoc_postal_code', 'assoc_city', 'assoc_latitude', 'assoc_longitude' ];
		foreach ( $fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$existing_options[ $field ] = sanitize_text_field( $input[ $field ] );
			}
		}
		return $existing_options;
	}
}
