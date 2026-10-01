<?php
/**
 * Mailing Page Controller.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin\Pages;

use DAME\Admin\Pages\Mailing\FormRenderer;
use DAME\Admin\Pages\Mailing\Processor;
use DAME\Services\Data_Provider;

/**
 * Controller class for the admin Mailing page.
 */
class Mailing {

	/**
	 * Initialize the page hooks.
	 */
	public function init(): void {
		add_action( 'admin_post_dame_process_mailing', array( $this, 'process_mailing' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts and localized data.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( string $hook ): void {
		if ( ! str_contains( $hook, 'dame-mailing' ) ) {
			return;
		}

		wp_enqueue_script(
			'dame-admin-mailing',
			DAME_PLUGIN_URL . 'assets/js/admin-mailing.js',
			array(),
			DAME_VERSION,
			true
		);

		// Prepare Region -> Departments mapping for JS.
		$regions        = Data_Provider::get_regions();
		$region_mapping = array();
		foreach ( array_keys( $regions ) as $code ) {
			if ( 'NA' === $code ) {
				continue;
			}
			$region_mapping[ $code ] = Data_Provider::get_departments_by_region( $code );
		}

		wp_localize_script(
			'dame-admin-mailing',
			'dameMailingData',
			array(
				'regionMapping' => $region_mapping,
			)
		);

		wp_enqueue_style(
			'dame-admin-styles',
			DAME_PLUGIN_URL . 'assets/css/admin-styles.css',
			array(),
			DAME_VERSION
		);
	}

	/**
	 * Render the Mailing page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			return;
		}

		$user_id     = get_current_user_id();
		$state_key   = 'dame_mailing_state_' . $user_id;
		$saved_state = get_transient( $state_key );

		if ( false !== $saved_state ) {
			delete_transient( $state_key );
		}
		$state_array = is_array( $saved_state ) ? $saved_state : array();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$success = isset( $_GET['success'] ) ? absint( $_GET['success'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$count = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$error = isset( $_GET['error'] ) ? sanitize_key( (string) $_GET['error'] ) : '';

		( new FormRenderer() )->render( $state_array, $success, $count, $error );
	}

	/**
	 * Process mailing form submission.
	 */
	public function process_mailing(): void {
		( new Processor() )->process();
	}
}
