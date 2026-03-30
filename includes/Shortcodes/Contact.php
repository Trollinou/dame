<?php
/**
 * Shortcode for the contact form.
 *
 * @package DAME
 */

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
	public function init() {
		add_shortcode( 'dame_contact', [ $this, 'render' ] );
		add_action( 'wp_ajax_dame_submit_contact_form', [ $this, 'handle_submission' ] );
		add_action( 'wp_ajax_nopriv_dame_submit_contact_form', [ $this, 'handle_submission' ] );
	}

	/**
	 * Renders the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string The HTML output of the contact form.
	 */
	public function render( $atts ) {
		// Enqueue the script using the global constant
		wp_enqueue_script( 'dame-contact-form', \DAME_PLUGIN_URL . 'assets/js/public-contact-form.js', [ 'jquery' ], \DAME_VERSION, true );

		// Localize the script with required data
		wp_localize_script( 'dame-contact-form', 'dame_contact_ajax', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'dame_contact_nonce' ),
		] );

		ob_start();
		?>
		<div id="dame-contact-form-wrapper">
			<form id="dame-contact-form" class="dame-form" novalidate>

				<?php wp_nonce_field( 'dame_contact_nonce', 'dame_contact_nonce_field' ); ?>

				<!-- Action -->
				<input type="hidden" name="action" value="dame_submit_contact_form">

				<!-- Honeypot -->
				<div style="display:none;">
					<label for="dame_contact_hp"><?php _e( "Laissez ce champ vide", "dame" ); ?></label>
					<input type="text" id="dame_contact_hp" name="dame_contact_hp" value="">
				</div>

				<p>
					<label for="dame_contact_name"><?php _e( "Nom", "dame" ); ?> <span class="required">*</span></label>
					<input type="text" id="dame_contact_name" name="dame_contact_name" required>
				</p>

				<p>
					<label for="dame_contact_email"><?php _e( "Courriel", "dame" ); ?> <span class="required">*</span></label>
					<input type="email" id="dame_contact_email" name="dame_contact_email" required>
				</p>

				<p>
					<label for="dame_contact_subject"><?php _e( "Sujet", "dame" ); ?> <span class="required">*</span></label>
					<input type="text" id="dame_contact_subject" name="dame_contact_subject" required>
				</p>

				<p>
					<label for="dame_contact_message"><?php _e( "Message", "dame" ); ?> <span class="required">*</span></label>
					<textarea id="dame_contact_message" name="dame_contact_message" rows="5" required></textarea>
				</p>

				<p>
					<button type="submit"><?php _e( "Envoyer", "dame" ); ?></button>
				</p>

				<div id="dame-contact-feedback" style="display:none;"></div>

			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handles the AJAX submission for the contact form.
	 */
	public function handle_submission() {
		// 1. Security Check: Verify nonce
		if ( ! isset( $_POST['dame_contact_nonce_field'] ) || ! wp_verify_nonce( $_POST['dame_contact_nonce_field'], 'dame_contact_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( "La vérification de sécurité a échoué. Veuillez rafraîchir la page.", "dame" ) ], 403 );
		}

		// Honeypot Check
		if ( ! empty( $_POST['dame_contact_hp'] ) ) {
			// Silently fail for bots
			wp_send_json_success( [ 'message' => __( "Votre message a bien été envoyé.", "dame" ) ] );
		}

		// 2. Validation
		$errors          = [];
		$required_fields = [
			'dame_contact_name'    => __( "Le nom est obligatoire.", "dame" ),
			'dame_contact_email'   => __( "Le courriel est obligatoire.", "dame" ),
			'dame_contact_subject' => __( "Le sujet est obligatoire.", "dame" ),
			'dame_contact_message' => __( "Le message est obligatoire.", "dame" ),
		];

		foreach ( $required_fields as $field_key => $error_message ) {
			if ( empty( $_POST[ $field_key ] ) ) {
				$errors[] = $error_message;
			}
		}

		// Email format validation
		if ( ! empty( $_POST['dame_contact_email'] ) && ! is_email( $_POST['dame_contact_email'] ) ) {
			$errors[] = __( "L'adresse de courriel n'est pas valide.", "dame" );
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( [ 'message' => implode( ' ', $errors ) ], 400 );
		}

		// 3. Sanitize Data
		$name    = sanitize_text_field( wp_unslash( $_POST['dame_contact_name'] ) );
		$email   = sanitize_email( wp_unslash( $_POST['dame_contact_email'] ) );
		$subject = sanitize_text_field( wp_unslash( $_POST['dame_contact_subject'] ) );
		$message = sanitize_textarea_field( wp_unslash( $_POST['dame_contact_message'] ) );

		// 4. Send Email
		$options = get_option( 'dame_options' );
		$to      = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

		$email_subject = "Formulaire de contact - " . $subject;

		$body  = "Vous avez reçu un nouveau message depuis le formulaire de contact de votre site.\r\n\r\n";
		$body .= "Nom: " . $name . "\r\n";
		$body .= "Courriel: " . $email . "\r\n";
		$body .= "Sujet: " . $subject . "\r\n";
		$body .= "Message:\r\n" . $message . "\r\n";

		$headers = [ 'From: ' . $name . ' <' . $email . '>' ];

		$sent = wp_mail( $to, $email_subject, $body, $headers );

		if ( $sent ) {
			wp_send_json_success( [ 'message' => __( "Votre message a bien été envoyé.", "dame" ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( "Une erreur s'est produite lors de l'envoi du message.", "dame" ) ] );
		}
	}
}
