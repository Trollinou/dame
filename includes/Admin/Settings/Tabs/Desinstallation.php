<?php
/**
 * Desinstallation Tab.
 *
 * @package DAME
 */

namespace DAME\Admin\Settings\Tabs;

/**
 * Class Desinstallation
 */
class Desinstallation {

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Désinstallation', 'dame' );
	}

	/**
	 * Register settings.
	 */
	public function register() {
		add_settings_section(
			'dame_uninstall_section',
			__( 'Zone de danger', 'dame' ),
			'__return_empty_string',
			'dame_uninstall_section_group'
		);

		add_settings_field(
			'dame_delete_on_uninstall',
			__( 'Suppression des données', 'dame' ),
			[ $this, 'render_delete_field' ],
			'dame_uninstall_section_group',
			'dame_uninstall_section'
		);
	}

	/**
	 * Render delete field.
	 */
	public function render_delete_field() {
		$options = get_option( 'dame_options' );
		$checked = isset( $options['delete_on_uninstall'] ) && $options['delete_on_uninstall'] ? 'checked' : '';
		echo '<input type="checkbox" name="dame_options[delete_on_uninstall]" value="1" ' . esc_attr( $checked ) . ' /> ' . esc_html__( 'Supprimer toutes les données lors de la désinstallation du plugin.', 'dame' );
		echo '<p class="description" style="color:red;">' . esc_html__( 'Attention : Cette action est irréversible. Toutes les tables et options seront supprimées.', 'dame' ) . '</p>';
	}

	/**
	 * Render the tab content.
	 */
	public function render() {
		do_settings_sections( 'dame_uninstall_section_group' );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input New input.
	 * @param array $existing_options Existing options.
	 * @return array Sanitized options.
	 */
	public function sanitize( $input, $existing_options ) {
		$existing_options['delete_on_uninstall'] = isset( $input['delete_on_uninstall'] ) ? 1 : 0;
		return $existing_options;
	}
}
