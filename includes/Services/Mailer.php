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
	public function init() {
		add_action( 'phpmailer_init', [ $this, 'configure_smtp' ] );
	}

	/**
	 * Configures the PHPMailer instance with SMTP settings from DAME options.
	 *
	 * This function is hooked into `phpmailer_init` and applies to all emails sent via wp_mail.
	 *
	 * @param PHPMailer $phpmailer The PHPMailer object.
	 */
	public function configure_smtp( $phpmailer ) {
		$options = get_option( 'dame_options', [] );

		// Configure SMTP if settings are provided.
		if ( ! empty( $options['smtp_host'] ) && ! empty( $options['smtp_username'] ) && ! empty( $options['smtp_password'] ) ) {
			$phpmailer->isSMTP();
			$phpmailer->Host     = $options['smtp_host'];
			$phpmailer->SMTPAuth = true;
			$phpmailer->Port     = isset( $options['smtp_port'] ) ? (int) $options['smtp_port'] : 465;
			$phpmailer->Username = $options['smtp_username'];
			$phpmailer->Password = $options['smtp_password'];

			if ( isset( $options['smtp_encryption'] ) && 'none' !== $options['smtp_encryption'] ) {
				$phpmailer->SMTPSecure = $options['smtp_encryption'];
			} elseif ( ! isset( $options['smtp_encryption'] ) ) {
				// Default to SSL if not set.
				$phpmailer->SMTPSecure = 'ssl';
			} else {
				// Explicitly set to empty if 'none'.
				$phpmailer->SMTPSecure = '';
			}
		}
	}
}
