<?php
/**
 * Paiements Tab.
 *
 * @package DAME
 */

namespace DAME\Admin\Settings\Tabs;

/**
 * Class Paiements
 */
class Paiements {

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Paiements', 'dame' );
	}

	/**
	 * Register settings.
	 */
	public function register() {
		add_settings_section(
			'dame_payment_section',
			__( 'Configuration des paiements', 'dame' ),
			'__return_empty_string',
			'dame_payment_section_group'
		);

		add_settings_field(
			'dame_payment_url',
			__( 'URL de paiement (HelloAsso, etc.)', 'dame' ),
			[ $this, 'render_url_field' ],
			'dame_payment_section_group',
			'dame_payment_section'
		);
	}

	/**
	 * Render URL field.
	 */
	public function render_url_field() {
		$options = get_option( 'dame_options' );
		$value = isset( $options['payment_url'] ) ? $options['payment_url'] : '';
		echo '<input type="url" name="dame_options[payment_url]" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	/**
	 * Render the tab content.
	 */
	public function render() {
		do_settings_sections( 'dame_payment_section_group' );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input New input.
	 * @param array $existing_options Existing options.
	 * @return array Sanitized options.
	 */
	public function sanitize( $input, $existing_options ) {
		if ( isset( $input['payment_url'] ) ) {
			$existing_options['payment_url'] = esc_url_raw( $input['payment_url'] );
		}
		return $existing_options;
	}
}
