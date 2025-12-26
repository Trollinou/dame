<?php
/**
 * Message Actions.
 *
 * @package DAME
 */

namespace DAME\Admin\Actions;

/**
 * Class Message
 */
class Message {

	/**
	 * Initialize actions.
	 */
	public function init() {
		add_filter( 'post_row_actions', [ $this, 'add_duplicate_link' ], 10, 2 );
		add_action( 'admin_action_dame_duplicate', [ $this, 'handle_duplicate' ] );
	}

	/**
	 * Add "Duplicate" link to row actions.
	 *
	 * @param array    $actions Existing actions.
	 * @param \WP_Post $post    Current post.
	 * @return array Modified actions.
	 */
	public function add_duplicate_link( $actions, $post ) {
		if ( 'dame_message' !== $post->post_type ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin.php?action=dame_duplicate&post=' . $post->ID ),
			'dame_duplicate_' . $post->ID
		);

		$actions['duplicate'] = '<a href="' . esc_url( $url ) . '" title="' . esc_attr__( 'Dupliquer ce message', 'dame' ) . '">' . esc_html__( 'Dupliquer', 'dame' ) . '</a>';

		return $actions;
	}

	/**
	 * Handle duplication.
	 */
	public function handle_duplicate() {
		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) ) || ( ! isset( $_GET['_wpnonce'] ) ) ) {
			wp_die( __( 'Données manquantes pour la duplication.', 'dame' ) );
		}

		$post_id = ( isset( $_GET['post'] ) ? absint( $_GET['post'] ) : absint( $_POST['post'] ) );

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'dame_duplicate_' . $post_id ) ) {
			wp_die( __( 'Vérification de sécurité échouée.', 'dame' ) );
		}

		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			wp_die( __( 'Permission refusée.', 'dame' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || 'dame_message' !== $post->post_type ) {
			wp_die( __( 'Message introuvable.', 'dame' ) );
		}

		$new_post_args = array(
			'post_title'   => $post->post_title . ' (Copie)',
			'post_content' => $post->post_content,
			'post_status'  => 'draft',
			'post_type'    => 'dame_message',
			'post_author'  => get_current_user_id(),
		);

		$new_post_id = wp_insert_post( $new_post_args );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( __( 'Erreur lors de la création du nouveau message : ', 'dame' ) . esc_html( $new_post_id->get_error_message() ) );
		}

		// Redirect to the edit screen of the new post.
		wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
		exit;
	}
}
