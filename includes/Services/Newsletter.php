<?php
/**
 * Newsletter Service.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Services;

use DAME\Core\Utils;
use WP_Query;

/**
 * Class Newsletter
 */
class Newsletter {

	/**
	 * Token transient prefix.
	 */
	private const TOKEN_PREFIX = 'dame_nl_confirm_';

	/**
	 * Initialize the service hooks.
	 */
	public function init(): void {
		add_action( 'template_redirect', array( $this, 'handle_confirmation_link' ) );
		add_action( 'wp_footer', array( $this, 'render_confirmation_notice' ) );
	}

	/**
	 * Handles submission of a newsletter subscription request.
	 *
	 * @param string $first_name Subscriber's first name.
	 * @param string $last_name Subscriber's last name.
	 * @param string $email Subscriber's email address.
	 * @return array<string, mixed> Result status and user message.
	 */
	public function handle_subscription( string $first_name, string $last_name, string $email ): array {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return array(
				'success' => false,
				'message' => __( 'Veuillez renseigner une adresse email valide.', 'dame' ),
			);
		}

		$options      = get_option( 'dame_options', array() );
		$contact_type = isset( $options['newsletter_contact_type'] ) ? absint( $options['newsletter_contact_type'] ) : 0;

		// 1. Check if email is already in adherents or legal representatives
		$adherent_id = $this->find_adherent_by_email( $email );
		if ( $adherent_id > 0 ) {
			return array(
				'success' => true,
				'message' => __( 'Votre adresse email est déjà enregistrée dans notre liste d\'adhérents (ou responsables légaux) et reçoit automatiquement nos informations.', 'dame' ),
			);
		}

		// 2. Check if contact already exists and is already in the newsletter group
		if ( $contact_type > 0 && $this->is_contact_subscribed( $email, $contact_type ) ) {
			return array(
				'success' => true,
				'message' => __( 'Votre adresse email est déjà inscrite à la newsletter.', 'dame' ),
			);
		}

		// Check if Double Opt-In is enabled (default is true/1).
		$double_optin = ! isset( $options['newsletter_double_optin'] ) || '1' === (string) $options['newsletter_double_optin'];

		if ( ! $double_optin ) {
			// Direct subscription without double opt-in.
			$contact_id = $this->create_or_update_contact( $first_name, $last_name, $email, $contact_type );

			if ( $contact_id ) {
				$success_msg = ! empty( $options['newsletter_success_message'] )
					? $options['newsletter_success_message']
					: __( 'Votre inscription à la newsletter a bien été prise en compte. Merci !', 'dame' );

				return array(
					'success' => true,
					'message' => $success_msg,
				);
			}

			return array(
				'success' => false,
				'message' => __( 'Une erreur est survenue lors de l\'enregistrement de votre inscription.', 'dame' ),
			);
		}

		// Double Opt-In flow: send confirmation email with a verification link.
		$sent = $this->send_confirmation_email( $first_name, $last_name, $email, $contact_type );

		if ( $sent ) {
			return array(
				'success' => true,
				'message' => __( 'Un email de confirmation vous a été envoyé. Veuillez cliquer sur le lien qu\'il contient pour valider votre inscription.', 'dame' ),
			);
		}

		return array(
			'success' => false,
			'message' => __( 'Impossible d\'envoyer l\'email de confirmation. Veuillez vérifier votre adresse ou réessayer ultérieurement.', 'dame' ),
		);
	}

	/**
	 * Checks if an email exists in Adherents (member email or legal representatives).
	 *
	 * @param string $email Email address to check.
	 * @return int Adherent Post ID or 0 if not found.
	 */
	public function find_adherent_by_email( string $email ): int {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return 0;
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'adherent',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_dame_email',
						'value'   => $email,
						'compare' => '=',
					),
					array(
						'key'     => '_dame_legal_rep_1_email',
						'value'   => $email,
						'compare' => '=',
					),
					array(
						'key'     => '_dame_legal_rep_2_email',
						'value'   => $email,
						'compare' => '=',
					),
				),
			)
		);

		if ( ! empty( $query->posts ) ) {
			return (int) $query->posts[0];
		}

		return 0;
	}

	/**
	 * Checks if a contact with this email is already assigned to the given group without mailing refusal.
	 *
	 * @param string $email Email address.
	 * @param int    $contact_type Term ID.
	 * @return bool True if already subscribed, false otherwise.
	 */
	public function is_contact_subscribed( string $email, int $contact_type = 0 ): bool {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return false;
		}

		$args = array(
			'post_type'      => 'dame_contact',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_dame_contact_email',
					'value'   => $email,
					'compare' => '=',
				),
			),
		);

		if ( $contact_type > 0 ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'dame_contact_type',
					'field'    => 'term_id',
					'terms'    => array( $contact_type ),
				),
			);
		}

		$query = new WP_Query( $args );
		if ( ! empty( $query->posts ) ) {
			$contact_id = (int) $query->posts[0];
			$no_emails  = get_post_meta( $contact_id, '_dame_contact_no_emails', true );
			return '1' !== $no_emails;
		}

		return false;
	}

	/**
	 * Sends a double opt-in confirmation email.
	 *
	 * @param string $first_name First name.
	 * @param string $last_name Last name.
	 * @param string $email Email address.
	 * @param int    $contact_type Term ID in dame_contact_type.
	 * @return bool True if email was sent, false otherwise.
	 */
	public function send_confirmation_email( string $first_name, string $last_name, string $email, int $contact_type = 0 ): bool {
		$options = get_option( 'dame_options', array() );

		// Generate random token and store pending subscription for 48 hours.
		$token = wp_generate_password( 40, false, false );
		$data  = array(
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'email'        => $email,
			'contact_type' => $contact_type,
			'created_at'   => time(),
		);

		set_transient( self::TOKEN_PREFIX . $token, $data, 48 * HOUR_IN_SECONDS );

		$confirm_url = add_query_arg(
			array( 'dame_newsletter_confirm' => $token ),
			home_url( '/' )
		);

		$assoc_name = get_bloginfo( 'name' );

		// Subject template.
		$subject = ! empty( $options['newsletter_confirm_subject'] )
			? (string) $options['newsletter_confirm_subject']
			: __( 'Confirmez votre inscription à notre newsletter', 'dame' );

		// Body template.
		$default_body = "Bonjour {prenom},\n\nNous avons bien reçu votre demande d'inscription à la newsletter de {nom_association}.\n\nPour confirmer et valider votre inscription, veuillez cliquer sur le lien ci-dessous :\n{lien_confirmation}\n\nCe lien est valable pendant 48 heures.\nSi vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.\n\nCordialement,\n{nom_association}";
		$body         = ! empty( $options['newsletter_confirm_body'] )
			? (string) $options['newsletter_confirm_body']
			: $default_body;

		// Replace placeholders.
		$replacements = array(
			'{prenom}'            => Utils::format_firstname( $first_name ),
			'{nom}'               => Utils::format_lastname( $last_name ),
			'{lien_confirmation}' => esc_url_raw( $confirm_url ),
			'{nom_association}'   => $assoc_name,
		);

		$subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $subject );
		$body    = str_replace( array_keys( $replacements ), array_values( $replacements ), $body );

		$headers = array();
		$sender  = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );
		if ( $sender ) {
			$headers[] = 'From: ' . $assoc_name . ' <' . $sender . '>';
		}

		return (bool) wp_mail( $email, $subject, $body, $headers );
	}

	/**
	 * Creates a new Contact or updates an existing one with newsletter information.
	 *
	 * @param string $first_name First name.
	 * @param string $last_name Last name.
	 * @param string $email Email address.
	 * @param int    $contact_type Term ID in dame_contact_type.
	 * @return int Contact Post ID or 0 on failure.
	 */
	public function create_or_update_contact( string $first_name, string $last_name, string $email, int $contact_type = 0 ): int {
		$email      = sanitize_email( $email );
		$first_name = Utils::format_firstname( $first_name );
		$last_name  = Utils::format_lastname( $last_name );

		if ( ! is_email( $email ) ) {
			return 0;
		}

		// Check if a contact with this email already exists.
		$existing = new WP_Query(
			array(
				'post_type'      => 'dame_contact',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'     => '_dame_contact_email',
						'value'   => $email,
						'compare' => '=',
					),
				),
				'fields'         => 'ids',
			)
		);

		$post_id = 0;
		if ( ! empty( $existing->posts ) ) {
			$post_id = absint( $existing->posts[0] );

			$curr_fn = (string) get_post_meta( $post_id, '_dame_contact_first_name', true );
			$curr_ln = (string) get_post_meta( $post_id, '_dame_contact_last_name', true );

			if ( empty( $curr_fn ) && ! empty( $first_name ) ) {
				update_post_meta( $post_id, '_dame_contact_first_name', $first_name );
			}
			if ( empty( $curr_ln ) && ! empty( $last_name ) ) {
				update_post_meta( $post_id, '_dame_contact_last_name', $last_name );
			}
		} else {
			// Create new contact post.
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'dame_contact',
					'post_status' => 'publish',
					'post_title'  => trim( $last_name . ' ' . $first_name ),
				)
			);

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				return 0;
			}

			update_post_meta( $post_id, '_dame_contact_first_name', $first_name );
			update_post_meta( $post_id, '_dame_contact_last_name', $last_name );
			update_post_meta( $post_id, '_dame_contact_email', $email );
		}

		// Ensure emails are accepted (opt-in).
		update_post_meta( $post_id, '_dame_contact_no_emails', '0' );

		// Update standardized title.
		$new_title = Utils::generate_contact_title( $post_id );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $new_title,
				'post_name'  => sanitize_title( $new_title ),
			)
		);

		// Assign contact type taxonomy if specified (appends to existing terms).
		if ( $contact_type > 0 ) {
			wp_set_object_terms( $post_id, array( $contact_type ), 'dame_contact_type', true );
		}

		return $post_id;
	}

	/**
	 * Handles confirmation link verification on frontend.
	 */
	public function handle_confirmation_link(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['dame_newsletter_confirm'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = sanitize_text_field( wp_unslash( $_GET['dame_newsletter_confirm'] ) );
		$data  = get_transient( self::TOKEN_PREFIX . $token );

		if ( false === $data || ! is_array( $data ) ) {
			// Token invalid or expired.
			$redirect_url = add_query_arg( 'dame_nl_status', 'expired', home_url( '/' ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Valid token: create or update the contact.
		$first_name   = isset( $data['first_name'] ) ? (string) $data['first_name'] : '';
		$last_name    = isset( $data['last_name'] ) ? (string) $data['last_name'] : '';
		$email        = isset( $data['email'] ) ? (string) $data['email'] : '';
		$contact_type = isset( $data['contact_type'] ) ? absint( $data['contact_type'] ) : 0;

		$contact_id = $this->create_or_update_contact( $first_name, $last_name, $email, $contact_type );
		delete_transient( self::TOKEN_PREFIX . $token );

		$status       = $contact_id ? 'success' : 'error';
		$redirect_url = add_query_arg( 'dame_nl_status', $status, home_url( '/' ) );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Renders feedback notification notice when returning from confirmation link.
	 */
	public function render_confirmation_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['dame_nl_status'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status  = sanitize_key( wp_unslash( $_GET['dame_nl_status'] ) );
		$options = get_option( 'dame_options', array() );

		if ( 'success' === $status ) {
			$msg = ! empty( $options['newsletter_success_message'] )
				? (string) $options['newsletter_success_message']
				: __( 'Votre inscription à la newsletter a bien été confirmée. Merci !', 'dame' );
			$class = 'dame-nl-notice--success';
			$icon  = '✓';
		} elseif ( 'expired' === $status ) {
			$msg   = __( 'Ce lien de confirmation a expiré ou a déjà été utilisé. Veuillez vous réinscrire.', 'dame' );
			$class = 'dame-nl-notice--warning';
			$icon  = '⚠';
		} else {
			$msg   = __( 'Une erreur est survenue lors de la confirmation de votre inscription.', 'dame' );
			$class = 'dame-nl-notice--error';
			$icon  = '✕';
		}
		?>
		<div id="dame-newsletter-notice-overlay" class="dame-nl-notice-overlay" role="dialog" aria-modal="true" aria-labelledby="dame-nl-notice-title">
			<div class="dame-nl-notice-box <?php echo esc_attr( $class ); ?>">
				<div class="dame-nl-notice-icon"><?php echo esc_html( $icon ); ?></div>
				<div class="dame-nl-notice-content">
					<h3 id="dame-nl-notice-title" class="dame-nl-notice-heading"><?php esc_html_e( 'Inscription Newsletter', 'dame' ); ?></h3>
					<p class="dame-nl-notice-message"><?php echo esc_html( $msg ); ?></p>
				</div>
				<button type="button" class="dame-nl-notice-close" onclick="document.getElementById('dame-newsletter-notice-overlay').remove();" aria-label="<?php esc_attr_e( 'Fermer', 'dame' ); ?>">&times;</button>
			</div>
		</div>
		<?php
	}
}
