<?php
/**
 * Anniversaires Tab.
 *
 * @package DAME
 */

namespace DAME\Admin\Settings\Tabs;

/**
 * Class Anniversaires
 */
class Anniversaires {

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Anniversaires', 'dame' );
	}

	/**
	 * Register settings.
	 */
	public function register() {
		add_settings_section(
			'dame_birthday_section',
			__( "Configuration des emails d'anniversaire", 'dame' ),
			'__return_empty_string',
			'dame_birthday_section_group'
		);

		add_settings_field(
			'dame_birthday_emails_enabled',
			__( 'Activer les emails', 'dame' ),
			[ $this, 'render_enabled_field' ],
			'dame_birthday_section_group',
			'dame_birthday_section'
		);

		add_settings_field(
			'dame_birthday_article_slug',
			__( 'Slug de l\'article (Modèle)', 'dame' ),
			[ $this, 'render_slug_field' ],
			'dame_birthday_section_group',
			'dame_birthday_section'
		);
	}

	/**
	 * Render enabled field.
	 */
	public function render_enabled_field() {
		$options = get_option( 'dame_options' );
		$checked = isset( $options['birthday_emails_enabled'] ) && $options['birthday_emails_enabled'] ? 'checked' : '';
		echo '<input type="checkbox" name="dame_options[birthday_emails_enabled]" value="1" ' . esc_attr( $checked ) . ' /> ' . esc_html__( 'Envoyer automatiquement un email le jour de l\'anniversaire.', 'dame' );
	}

	/**
	 * Render slug field.
	 */
	public function render_slug_field() {
		$options = get_option( 'dame_options' );
		$value = isset( $options['birthday_article_slug'] ) ? $options['birthday_article_slug'] : '';
		echo '<input type="text" name="dame_options[birthday_article_slug]" value="' . esc_attr( $value ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Slug de l\'article ou de la page à utiliser comme contenu de l\'email.', 'dame' ) . '</p>';
	}

	/**
	 * Render the tab content.
	 */
	public function render() {
		do_settings_sections( 'dame_birthday_section_group' );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input New input.
	 * @param array $existing_options Existing options.
	 * @return array Sanitized options.
	 */
	public function sanitize( $input, $existing_options ) {
		$existing_options['birthday_emails_enabled'] = isset( $input['birthday_emails_enabled'] ) ? 1 : 0;
		if ( isset( $input['birthday_article_slug'] ) ) {
			$existing_options['birthday_article_slug'] = sanitize_text_field( $input['birthday_article_slug'] );
		}
		return $existing_options;
	}
}
