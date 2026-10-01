<?php
/**
 * Registration Form Shortcode.
 *
 * @package DAME\Shortcodes
 */

declare(strict_types=1);

namespace DAME\Shortcodes;

use DAME\Shortcodes\RegistrationForm\FormView;
use DAME\Shortcodes\RegistrationForm\SubmissionHandler;

/**
 * Controller class for the registration form shortcode [dame_fiche_inscription].
 */
class RegistrationForm {

	/**
	 * Initialize the shortcode and AJAX hooks.
	 */
	public function init(): void {
		add_shortcode( 'dame_fiche_inscription', array( $this, 'render' ) );
		add_action( 'wp_ajax_dame_submit_pre_inscription', array( $this, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_dame_submit_pre_inscription', array( $this, 'handle_submission' ) );
	}

	/**
	 * Render the registration form.
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render( $atts = array() ): string {
		$attributes = is_array( $atts ) ? $atts : array();
		return ( new FormView() )->render( $attributes );
	}

	/**
	 * Handle AJAX form submission.
	 */
	public function handle_submission(): void {
		( new SubmissionHandler() )->handle();
	}
}
