<?php
/**
 * File for handling PHPMailer SMTP configuration.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Configures the PHPMailer instance with SMTP settings from DAME options.
 *
 * This function is hooked into `phpmailer_init` and applies to all emails sent via wp_mail.
 *
 * @param PHPMailer $phpmailer The PHPMailer object.
 */
function dame_configure_smtp( $phpmailer ) {
	$options = get_option( 'dame_options' );

	// Configure SMTP if settings are provided.
	if ( ! empty( $options['smtp_host'] ) && ! empty( $options['smtp_username'] ) && ! empty( $options['smtp_password'] ) ) {
		$phpmailer->isSMTP();
		$phpmailer->Host       = $options['smtp_host'];
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Port       = isset( $options['smtp_port'] ) ? (int) $options['smtp_port'] : 465;
		$phpmailer->Username   = $options['smtp_username'];
		$phpmailer->Password   = $options['smtp_password'];
		$phpmailer->SMTPSecure = isset( $options['smtp_encryption'] ) && 'none' !== $options['smtp_encryption'] ? $options['smtp_encryption'] : 'ssl';
	}
}
add_action( 'phpmailer_init', 'dame_configure_smtp' );
