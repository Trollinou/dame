<?php
/**
 * Adherent Metabox Manager.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\Adherent;

use DAME\Metaboxes\Adherent\Identity;
use DAME\Metaboxes\Adherent\Legal;
use DAME\Metaboxes\Adherent\School;
use DAME\Metaboxes\Adherent\Diverse;
use DAME\Metaboxes\Adherent\Classification;
use DAME\Metaboxes\Adherent\Groups;
use DAME\Metaboxes\Adherent\Actions;

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

		$school = new School();
		$school->register();

		$legal = new Legal();
		$legal->register();

		$diverse = new Diverse();
		$diverse->register();

		$classification = new Classification();
		$classification->register();

		$groups = new Groups();
		$groups->register();

		$actions = new Actions();
		$actions->register();
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

		$school = new School();
		$school->save( $post_id );

		$legal = new Legal();
		$legal->save( $post_id );

		$diverse = new Diverse();
		$diverse->save( $post_id );

		$classification = new Classification();
		$classification->save( $post_id );

		$groups = new Groups();
		$groups->save( $post_id );

		$actions = new Actions();
		$actions->save( $post_id );
	}
}
