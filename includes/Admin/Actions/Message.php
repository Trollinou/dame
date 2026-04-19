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
	public function init(): void {
		add_filter( 'post_row_actions', [ $this, 'add_duplicate_link' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'add_reset_link' ], 10, 2 );
		add_action( 'admin_action_dame_duplicate', [ $this, 'handle_duplicate' ] );
		add_action( 'admin_action_dame_reset_send', [ $this, 'handle_reset' ] );
	}

	/**
	 * Add "Duplicate" link to row actions.
	 *
	 * @param array<string, mixed> $actions Existing actions.
	 * @param \WP_Post $post    Current post.
	 * @return array<string, mixed> Modified actions.
	 */
	public function add_duplicate_link( $actions, $post ): void {
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
	 * Add "Reset envoi" link to row actions.
	 *
	 * @param array<string, mixed> $actions Existing actions.
	 * @param \WP_Post $post    Current post.
	 * @return array<string, mixed> Modified actions.
	 */
	public function add_reset_link( $actions, $post ): void {
		if ( 'dame_message' !== $post->post_type || ! current_user_can( 'edit_dame_messages' ) ) {
			return $actions;
		}

		$status = get_post_meta( $post->ID, '_dame_message_status', true );
		if ( empty( $status ) || 'draft' === $status ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin.php?action=dame_reset_send&post=' . $post->ID ),
			'dame_reset_send_' . $post->ID
		);

		$actions['reset_send'] = sprintf(
			'<a href="%s" class="dame-reset-send" onclick="return confirm(\'%s\');" style="color: #d63638;">%s</a>',
			esc_url( $url ),
			esc_js( __( 'Êtes-vous sûr de vouloir réinitialiser l\'envoi de ce message ? Cela effacera l\'historique des destinataires pour ce message (permettant un renvoi complet) et remettra les compteurs à zéro.', 'dame' ) ),
			esc_html__( 'Reset envoi', 'dame' )
		);

		return $actions;
	}

	/**
	 * Handle duplication.
	 */
	public function handle_duplicate(): void {
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

	/**
	 * Handle reset of message send data.
	 */
	public function handle_reset(): void {
		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) ) || ( ! isset( $_GET['_wpnonce'] ) ) ) {
			wp_die( __( 'Données manquantes pour le reset.', 'dame' ) );
		}

		$post_id = ( isset( $_GET['post'] ) ? absint( $_GET['post'] ) : absint( $_POST['post'] ) );

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'dame_reset_send_' . $post_id ) ) {
			wp_die( __( 'Vérification de sécurité échouée.', 'dame' ) );
		}

		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			wp_die( __( 'Permission refusée.', 'dame' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || 'dame_message' !== $post->post_type ) {
			wp_die( __( 'Message introuvable.', 'dame' ) );
		}

		// 1. Reset message metadata
		update_post_meta( $post_id, '_dame_message_status', 'publish' );
		update_post_meta( $post_id, '_dame_message_recipients_count', 0 );
		update_post_meta( $post_id, '_dame_scheduled_batches_processed', 0 );
		update_post_meta( $post_id, '_dame_scheduled_batches_total', 0 );
		
		// Clear selection criteria
		delete_post_meta( $post_id, '_dame_recipient_method' );
		delete_post_meta( $post_id, '_dame_recipient_seasons' );
		delete_post_meta( $post_id, '_dame_recipient_groups_saisonnier' );
		delete_post_meta( $post_id, '_dame_recipient_groups_permanent' );
		delete_post_meta( $post_id, '_dame_recipient_contact_types' );
		delete_post_meta( $post_id, '_dame_recipient_depts' );
		delete_post_meta( $post_id, '_dame_recipient_regions' );
		delete_post_meta( $post_id, '_dame_recipient_gender' );
		delete_post_meta( $post_id, '_dame_manual_recipients' );
		delete_post_meta( $post_id, '_dame_manual_contacts' );
		delete_post_meta( $post_id, '_dame_adherent_method' );
		delete_post_meta( $post_id, '_dame_contact_method' );

		// 2. Purge global recipients history for this message (on adherents and contacts)
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete(
			$wpdb->postmeta,
			[
				'meta_key'   => '_dame_message_received',
				'meta_value' => (string) $post_id,
			],
			[ '%s', '%s' ]
		);

		// 3. Purge tracking data (opens)
		$table_opens = $wpdb->prefix . 'dame_message_opens';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete(
			$table_opens,
			[ 'message_id' => $post_id ],
			[ '%d' ]
		);

		// Redirect back with success message.
		wp_redirect( admin_url( 'edit.php?post_type=dame_message&reset_done=1' ) );
		exit;
	}
}
