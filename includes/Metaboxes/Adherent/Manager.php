<?php
/**
 * Adherent Metabox Manager.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\Adherent;

use DAME\Metaboxes\Adherent\Identity;
use DAME\Metaboxes\Adherent\Legal;

/**
 * Class Manager
 */
class Manager {

	/**
	 * Initialize the manager.
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_post' ] );
	}

	/**
	 * Register meta boxes.
	 */
	public function add_meta_boxes() {
		$identity = new Identity();
		$identity->register();

		$legal = new Legal();
		$legal->register();
	}

	/**
	 * Save meta boxes.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_post( $post_id ) {
		// Common security checks.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( 'adherent' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Delegate saving to components.
		$identity = new Identity();
		$identity->save( $post_id );

		$legal = new Legal();
		$legal->save( $post_id );
	}
}
