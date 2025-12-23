<?php
/**
 * Sauvegarde Tab.
 *
 * @package DAME
 */

namespace DAME\Admin\Settings\Tabs;

/**
 * Class Sauvegarde
 */
class Sauvegarde {

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Sauvegarde', 'dame' );
	}

	/**
	 * Register settings.
	 */
	public function register() {
		add_settings_section(
			'dame_backup_section',
			__( 'Sauvegardes automatiques', 'dame' ),
			'__return_empty_string',
			'dame_backup_section_group'
		);

		add_settings_field(
			'dame_backup_time',
			__( 'Heure de sauvegarde (HH:MM)', 'dame' ),
			[ $this, 'render_time_field' ],
			'dame_backup_section_group',
			'dame_backup_section'
		);
	}

	/**
	 * Render time field.
	 */
	public function render_time_field() {
		$options = get_option( 'dame_options' );
		$value = isset( $options['backup_time'] ) ? $options['backup_time'] : '';
		echo '<input type="time" name="dame_options[backup_time]" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	/**
	 * Render the tab content.
	 */
	public function render() {
		do_settings_sections( 'dame_backup_section_group' );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input New input.
	 * @param array $existing_options Existing options.
	 * @return array Sanitized options.
	 */
	public function sanitize( $input, $existing_options ) {
		if ( isset( $input['backup_time'] ) ) {
			$time = trim( $input['backup_time'] );
			if ( preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time ) ) {
				$existing_options['backup_time'] = $time;
			} else {
				$existing_options['backup_time'] = '';
			}
		}
		return $existing_options;
	}
}
