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
	public function register(): void {
		// Section SMTP
		add_settings_section(
			'dame_mailing_section',
			__( 'Configuration SMTP', 'dame' ),
			'__return_empty_string',
			'dame_mailing_section_group'
		);

		$fields = array(
			'sender_email'    => __( 'Email de l\'expéditeur', 'dame' ),
			'smtp_host'       => __( 'Serveur SMTP', 'dame' ),
			'smtp_port'       => __( 'Port SMTP', 'dame' ),
			'smtp_encryption' => __( 'Chiffrement', 'dame' ),
			'smtp_username'   => __( 'Utilisateur SMTP', 'dame' ),
			'smtp_password'   => __( 'Mot de passe SMTP', 'dame' ),
			'smtp_batch_size' => __( 'Taille du lot (emails/minute)', 'dame' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				'dame_' . $key,
				$label,
				array( $this, 'render_field' ),
				'dame_mailing_section_group',
				'dame_mailing_section',
				array( 'key' => $key )
			);
		}

		// Section Newsletter
		add_settings_section(
			'dame_newsletter_section',
			__( 'Inscription à la Newsletter', 'dame' ),
			array( $this, 'render_newsletter_section_description' ),
			'dame_mailing_section_group'
		);

		$newsletter_fields = array(
			'newsletter_contact_type'    => __( 'Groupe de contact assigné', 'dame' ),
			'newsletter_double_optin'    => __( 'Validation par email (Double Opt-In)', 'dame' ),
			'newsletter_confirm_subject' => __( 'Sujet de l\'email de confirmation', 'dame' ),
			'newsletter_confirm_body'    => __( 'Contenu de l\'email de confirmation', 'dame' ),
			'newsletter_success_message' => __( 'Message de succès après confirmation', 'dame' ),
		);

		foreach ( $newsletter_fields as $key => $label ) {
			add_settings_field(
				'dame_' . $key,
				$label,
				array( $this, 'render_field' ),
				'dame_mailing_section_group',
				'dame_newsletter_section',
				array( 'key' => $key )
			);
		}
	}

	/**
	 * Render newsletter section description.
	 */
	public function render_newsletter_section_description(): void {
		echo '<p class="description">' . esc_html__( 'Configurez les paramètres du formulaire d\'inscription à la newsletter (shortcode [dame_newsletter]). Vous pouvez personnaliser les textes par défaut.', 'dame' ) . '</p>';
	}

	/**
	 * Render field callback.
	 *
	 * @param array<string, mixed> $args Arguments.
	 */
	public function render_field( $args ): void {
		$key     = $args['key'];
		$options = get_option( 'dame_options' );
		$value   = isset( $options[ $key ] ) ? $options[ $key ] : '';

		if ( 'smtp_encryption' === $key ) {
			$encryption = ! empty( $value ) ? $value : 'tls';
			echo '<select name="dame_options[smtp_encryption]">';
			echo '<option value="none" ' . selected( $encryption, 'none', false ) . '>Aucun</option>';
			echo '<option value="ssl" ' . selected( $encryption, 'ssl', false ) . '>SSL</option>';
			echo '<option value="tls" ' . selected( $encryption, 'tls', false ) . '>TLS</option>';
			echo '</select>';
			return;
		}

		if ( 'newsletter_contact_type' === $key ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'dame_contact_type',
					'hide_empty' => false,
				)
			);
			echo '<select name="dame_options[newsletter_contact_type]">';
			echo '<option value="">' . esc_html__( '— Sélectionner un groupe de contact —', 'dame' ) . '</option>';
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( (string) $term->term_id ),
						selected( (string) $value, (string) $term->term_id, false ),
						esc_html( $term->name )
					);
				}
			}
			echo '</select>';
			echo '<p class="description">' . esc_html__( 'Le groupe de contact qui sera automatiquement attribué aux personnes s\'inscrivant à la newsletter.', 'dame' ) . '</p>';
			return;
		}

		if ( 'newsletter_double_optin' === $key ) {
			// Par défaut coché (1) si l'option n'a jamais été enregistrée
			$is_checked = ( '' === $value || '1' === (string) $value || true === $value );
			echo '<label>';
			echo '<input type="checkbox" name="dame_options[newsletter_double_optin]" value="1" ' . checked( $is_checked, true, false ) . ' /> ';
			echo esc_html__( 'Exiger la confirmation de l\'email par un lien sécurisé envoyé au visiteur (recommandé RGPD & anti-spam)', 'dame' );
			echo '</label>';
			return;
		}

		if ( 'newsletter_confirm_subject' === $key ) {
			$placeholder = __( 'Confirmez votre inscription à notre newsletter', 'dame' );
			$display_val = ! empty( $value ) ? $value : '';
			echo '<input type="text" name="dame_options[newsletter_confirm_subject]" value="' . esc_attr( $display_val ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text" />';
			echo '<p class="description">' . esc_html__( 'Laissez vide pour utiliser le sujet par défaut.', 'dame' ) . '</p>';
			return;
		}

		if ( 'newsletter_confirm_body' === $key ) {
			$default_body = "Bonjour {prenom},\n\nNous avons bien reçu votre demande d'inscription à la newsletter de {nom_association}.\n\nPour confirmer et valider votre inscription, veuillez cliquer sur le lien ci-dessous :\n{lien_confirmation}\n\nCe lien est valable pendant 48 heures.\nSi vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.\n\nCordialement,\n{nom_association}";
			$display_val  = ! empty( $value ) ? $value : '';
			echo '<textarea name="dame_options[newsletter_confirm_body]" rows="7" cols="60" class="large-text code" placeholder="' . esc_attr( $default_body ) . '">' . esc_textarea( $display_val ) . '</textarea>';
			echo '<p class="description">' . esc_html__( 'Variables disponibles : {prenom}, {nom}, {lien_confirmation}, {nom_association}. Laissez vide pour utiliser le texte par défaut.', 'dame' ) . '</p>';
			return;
		}

		if ( 'newsletter_success_message' === $key ) {
			$placeholder = __( 'Votre inscription à la newsletter a bien été confirmée. Merci !', 'dame' );
			$display_val = ! empty( $value ) ? $value : '';
			echo '<input type="text" name="dame_options[newsletter_success_message]" value="' . esc_attr( $display_val ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text" />';
			echo '<p class="description">' . esc_html__( 'Message affiché au visiteur sur le site après avoir validé son inscription.', 'dame' ) . '</p>';
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
	public function render(): void {
		do_settings_sections( 'dame_mailing_section_group' );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array<string, mixed> $input New input.
	 * @param array<string, mixed> $existing_options Existing options.
	 * @return array<string, mixed> Sanitized options.
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
		if ( isset( $input['smtp_encryption'] ) && in_array( $input['smtp_encryption'], array( 'none', 'ssl', 'tls' ), true ) ) {
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

		// Newsletter settings sanitation
		if ( isset( $input['newsletter_contact_type'] ) ) {
			$existing_options['newsletter_contact_type'] = absint( $input['newsletter_contact_type'] );
		}

		$existing_options['newsletter_double_optin'] = isset( $input['newsletter_double_optin'] ) ? '1' : '0';

		if ( isset( $input['newsletter_confirm_subject'] ) ) {
			$existing_options['newsletter_confirm_subject'] = sanitize_text_field( $input['newsletter_confirm_subject'] );
		}
		if ( isset( $input['newsletter_confirm_body'] ) ) {
			$existing_options['newsletter_confirm_body'] = sanitize_textarea_field( $input['newsletter_confirm_body'] );
		}
		if ( isset( $input['newsletter_success_message'] ) ) {
			$existing_options['newsletter_success_message'] = sanitize_text_field( $input['newsletter_success_message'] );
		}

		return $existing_options;
	}
}
