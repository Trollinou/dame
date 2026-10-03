<?php
/**
 * Shortcode for the contact form.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Shortcodes;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Handles the [dame_contact] shortcode and form submission.
 */
class Contact {

	/**
	 * Initializes the shortcode and AJAX handlers.
	 */
	public function init(): void {
		add_shortcode( 'dame_contact', array( $this, 'render' ) );
		add_action( 'wp_ajax_dame_submit_contact_form', array( $this, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_dame_submit_contact_form', array( $this, 'handle_submission' ) );
	}

	/**
	 * Renders the shortcode.
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string The HTML output of the contact form.
	 */
	public function render( $atts = array() ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		// Enqueue public styles.
		wp_enqueue_style(
			'dame-public-styles',
			\DAME_PLUGIN_URL . 'assets/css/public-styles.css',
			array(),
			\DAME_VERSION
		);

		if ( function_exists( 'wp_enqueue_script_module' ) ) {
			wp_enqueue_script_module( 'dame/contact' );
		}

		// Enqueue the script using the global constant (fallback).
		wp_enqueue_script( 'dame-public-contact-form', \DAME_PLUGIN_URL . 'assets/js/public-contact-form.js', array(), \DAME_VERSION, true );

		// Localize the script with required data.
		wp_localize_script(
			'dame-public-contact-form',
			'dame_contact_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'dame_contact_nonce' ),
			)
		);

		$contact_context = array(
			'isSubmitting' => false,
			'status'       => 'idle',
			'message'      => '',
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'dame_contact_nonce' ),
		);

		$context_attr = function_exists( 'wp_interactivity_data_wp_context' )
			? wp_interactivity_data_wp_context( $contact_context )
			: 'data-wp-context=\'' . wp_json_encode( $contact_context ) . '\'';

		ob_start();
		?>
		<div id="dame-public-contact-form-wrapper" class="dame-contact-wrapper" data-wp-interactive="dame/contact" <?php echo $context_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div
				id="dame-contact-feedback"
				class="dame-feedback"
				role="alert"
				data-wp-bind--hidden="!state.hasMessage"
				data-wp-class--dame-feedback--success="state.isSuccess"
				data-wp-class--dame-feedback--error="state.isError"
				data-wp-text="context.message"
				hidden
			></div>

			<form id="dame-public-contact-form" class="dame-form dame-contact-form" novalidate data-wp-on--submit="actions.submitForm">

				<?php wp_nonce_field( 'dame_contact_nonce', 'dame_contact_nonce_field' ); ?>

				<!-- Action -->
				<input type="hidden" name="action" value="dame_submit_contact_form">

				<p>
					<label for="dame_contact_name"><?php esc_html_e( 'Nom', 'dame' ); ?> <span class="required">*</span></label>
					<input type="text" id="dame_contact_name" name="dame_contact_name" required autocomplete="off" autocorrect="off">
				</p>

				<p>
					<label for="dame_contact_email"><?php esc_html_e( 'Courriel', 'dame' ); ?> <span class="required">*</span></label>
					<input type="email" id="dame_contact_email" name="dame_contact_email" required autocomplete="off" autocapitalize="off" autocorrect="off">
				</p>

				<p>
					<label for="dame_contact_subject"><?php esc_html_e( 'Sujet', 'dame' ); ?> <span class="required">*</span></label>
					<input type="text" id="dame_contact_subject" name="dame_contact_subject" required autocomplete="off" autocorrect="off">
				</p>

				<p>
					<label for="dame_contact_message"><?php esc_html_e( 'Message', 'dame' ); ?> <span class="required">*</span></label>
					<textarea id="dame_contact_message" name="dame_contact_message" rows="5" required spellcheck="true"></textarea>
				</p>

				<!-- Anti-spam honeypot -->
				<div class="dame-contact-hp" aria-hidden="true" style="display:none !important;">
					<label for="dame_contact_hp"><?php esc_html_e( 'Laissez ce champ vide', 'dame' ); ?></label>
					<input type="text" id="dame_contact_hp" name="dame_contact_hp" value="" tabindex="-1" autocomplete="new-password">
				</div>

				<p>
					<button type="submit" class="dame-btn dame-btn--primary" data-wp-bind--disabled="state.isBusy">
						<?php esc_html_e( 'Envoyer', 'dame' ); ?>
					</button>
				</p>

			</form>
		</div>
		<?php
		$output = ob_get_clean();
		return false !== $output ? $output : '';
	}

	/**
	 * Handles the AJAX submission for the contact form.
	 */
	public function handle_submission(): void {
		// 1. Security Check: Verify nonce.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$nonce = isset( $_POST['dame_contact_nonce_field'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_contact_nonce_field'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dame_contact_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'La vérification de sécurité a échoué. Veuillez rafraîchir la page.', 'dame' ) ), 403 );
		}

		// Honeypot Check.
		if ( ! empty( $_POST['dame_contact_hp'] ) ) {
			// Silently fail for bots.
			wp_send_json_success( array( 'message' => __( 'Votre message a bien été envoyé.', 'dame' ) ) );
		}

		// 2. Validation.
		$errors          = array();
		$required_fields = array(
			'dame_contact_name'    => __( 'Le nom est obligatoire.', 'dame' ),
			'dame_contact_email'   => __( 'Le courriel est obligatoire.', 'dame' ),
			'dame_contact_subject' => __( 'Le sujet est obligatoire.', 'dame' ),
			'dame_contact_message' => __( 'Le message est obligatoire.', 'dame' ),
		);

		foreach ( $required_fields as $field_key => $error_message ) {
			if ( empty( $_POST[ $field_key ] ) ) {
				$errors[] = $error_message;
			}
		}

		// Email format validation.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$email_input = isset( $_POST['dame_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['dame_contact_email'] ) ) : '';
		if ( ! empty( $email_input ) && ! is_email( $email_input ) ) {
			$errors[] = __( "L'adresse de courriel n'est pas valide.", 'dame' );
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( array( 'message' => implode( ' ', $errors ) ), 400 );
		}

		// 3. Sanitize Data.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$name  = isset( $_POST['dame_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_contact_name'] ) ) : '';
		$email = $email_input;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$subject = isset( $_POST['dame_contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_contact_subject'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$message = isset( $_POST['dame_contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['dame_contact_message'] ) ) : '';

		// 4. Idempotency lock (15s) to avoid duplicate email sending on double-clicks.
		$lock_key = 'dame_contact_lock_' . md5( strtolower( $name . $email . $subject . $message ) );
		if ( get_transient( $lock_key ) ) {
			wp_send_json_success( array( 'message' => __( 'Votre message a bien été envoyé.', 'dame' ) ) );
		}

		$options = get_option( 'dame_options' );
		$to      = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

		$email_subject = 'Formulaire de contact - ' . $subject;

		$body  = "Vous avez reçu un nouveau message depuis le formulaire de contact de votre site.\r\n\r\n";
		$body .= 'Nom: ' . $name . "\r\n";
		$body .= 'Courriel: ' . $email . "\r\n";
		$body .= 'Sujet: ' . $subject . "\r\n";
		$body .= "Message:\r\n" . $message . "\r\n";

		$headers = array( 'From: ' . $name . ' <' . $email . '>' );

		$sent = wp_mail( $to, $email_subject, $body, $headers );

		if ( $sent ) {
			set_transient( $lock_key, '1', 15 );
			wp_send_json_success( array( 'message' => __( 'Votre message a bien été envoyé.', 'dame' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( "Une erreur s'est produite lors de l'envoi du message.", 'dame' ) ) );
		}
	}
}
