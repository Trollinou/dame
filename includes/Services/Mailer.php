<?php
/**
 * Mailer Service.
 *
 * @package DAME
 */

namespace DAME\Services;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class Mailer
 */
class Mailer {

	/**
	 * Initialize the Mailer service.
	 */
	public function init(): void {
		add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
		add_filter( 'wp_mail_from', array( $this, 'set_from_email' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'set_from_name' ) );
	}

	/**
	 * Configures the PHPMailer instance with SMTP settings from DAME options.
	 *
	 * This function is hooked into `phpmailer_init` and applies to all emails sent via wp_mail.
	 *
	 * @param PHPMailer $phpmailer The PHPMailer object.
	 */
	public function configure_smtp( $phpmailer ): void {
		$options = get_option( 'dame_options', array() );

		// Check if SMTP host is configured (acts as enable_smtp).
		if ( ! empty( $options['smtp_host'] ) ) {
			$phpmailer->isSMTP();
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->Host = $options['smtp_host'];
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->Port = isset( $options['smtp_port'] ) ? (int) $options['smtp_port'] : 465;

			// Handle Authentication
			if ( ! empty( $options['smtp_username'] ) && ! empty( $options['smtp_password'] ) ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$phpmailer->SMTPAuth = true;
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$phpmailer->Username = $options['smtp_username'];
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$phpmailer->Password = $options['smtp_password'];
			}

			// Handle Encryption (map smtp_secure to smtp_encryption)
			if ( isset( $options['smtp_encryption'] ) && 'none' !== $options['smtp_encryption'] ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$phpmailer->SMTPSecure = $options['smtp_encryption'];
			} elseif ( ! isset( $options['smtp_encryption'] ) ) {
				// Default to SSL if not set.
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$phpmailer->SMTPSecure = 'ssl';
			} else {
				// Explicitly set to empty if 'none'.
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$phpmailer->SMTPSecure = '';
			}
		}
	}

	/**
	 * Sets the sender email address.
	 *
	 * @param string $original_email The original sender email.
	 * @return string The configured sender email or the original.
	 */
	public function set_from_email( $original_email ) {
		$options = get_option( 'dame_options', array() );
		if ( ! empty( $options['sender_email'] ) && is_email( $options['sender_email'] ) ) {
			return $options['sender_email'];
		}
		return $original_email;
	}

	/**
	 * Sets the sender name.
	 *
	 * @param string $original_name The original sender name.
	 * @return string The configured sender name or the original.
	 */
	public function set_from_name( $original_name ) {
		$options = get_option( 'dame_options', array() );
		if ( ! empty( $options['sender_name'] ) ) {
			return $options['sender_name'];
		}
		return $original_name;
	}
}
