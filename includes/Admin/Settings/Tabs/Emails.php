<?php
/**
 * Emails Tab.
 *
 * @package DAME
 */

namespace DAME\Admin\Settings\Tabs;

/**
 * Class Emails
 */
class Emails {

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Emails', 'dame' );
	}

	/**
	 * Register settings.
	 */
	public function register() {
		add_settings_section(
			'dame_mailing_section',
			__( 'Configuration SMTP', 'dame' ),
			'__return_empty_string',
			'dame_mailing_section_group'
		);

		$fields = [
			'sender_email' => __( 'Email de l\'expéditeur', 'dame' ),
			'smtp_host' => __( 'Serveur SMTP', 'dame' ),
			'smtp_port' => __( 'Port SMTP', 'dame' ),
			'smtp_encryption' => __( 'Chiffrement', 'dame' ),
			'smtp_username' => __( 'Utilisateur SMTP', 'dame' ),
			'smtp_password' => __( 'Mot de passe SMTP', 'dame' ),
			'smtp_batch_size' => __( 'Taille du lot (emails/minute)', 'dame' ),
		];

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				'dame_' . $key,
				$label,
				[ $this, 'render_field' ],
				'dame_mailing_section_group',
				'dame_mailing_section',
				[ 'key' => $key ]
			);
		}
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

		if ( 'smtp_encryption' === $key ) {
			$encryption = $value ?: 'tls';
			echo '<select name="dame_options[smtp_encryption]">';
			echo '<option value="none" ' . selected( $encryption, 'none', false ) . '>Aucun</option>';
			echo '<option value="ssl" ' . selected( $encryption, 'ssl', false ) . '>SSL</option>';
			echo '<option value="tls" ' . selected( $encryption, 'tls', false ) . '>TLS</option>';
			echo '</select>';
			return;
		}

		$type = 'text';
		if ( 'sender_email' === $key ) {
			$type = 'email';
		} elseif ( 'smtp_port' === $key || 'smtp_batch_size' === $key ) {
			$type = 'number';
		} elseif ( 'smtp_password' === $key ) {
			$type = 'password';
		}

		echo '<input type="' . esc_attr( $type ) . '" name="dame_options[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	/**
	 * Render the tab content.
	 */
	public function render() {
		do_settings_sections( 'dame_mailing_section_group' );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input New input.
	 * @param array $existing_options Existing options.
	 * @return array Sanitized options.
	 */
	public function sanitize( $input, $existing_options ) {
		if ( isset( $input['sender_email'] ) ) {
			$existing_options['sender_email'] = sanitize_email( $input['sender_email'] );
		}
		if ( isset( $input['smtp_host'] ) ) {
			$existing_options['smtp_host'] = sanitize_text_field( $input['smtp_host'] );
		}
		if ( isset( $input['smtp_port'] ) ) {
			$existing_options['smtp_port'] = absint( $input['smtp_port'] );
		}
		if ( isset( $input['smtp_encryption'] ) && in_array( $input['smtp_encryption'], [ 'none', 'ssl', 'tls' ], true ) ) {
			$existing_options['smtp_encryption'] = $input['smtp_encryption'];
		}
		if ( isset( $input['smtp_username'] ) ) {
			$existing_options['smtp_username'] = sanitize_text_field( $input['smtp_username'] );
		}
		if ( ! empty( $input['smtp_password'] ) ) {
			$existing_options['smtp_password'] = trim( $input['smtp_password'] );
		}
		if ( isset( $input['smtp_batch_size'] ) ) {
			$existing_options['smtp_batch_size'] = absint( $input['smtp_batch_size'] );
		}
		return $existing_options;
	}
}
