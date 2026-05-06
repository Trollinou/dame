<?php
/**
 * Message Admin Actions.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin\Actions;

use WP_Post;

/**
 * Class Message
 */
class Message {

	/**
	 * Initialize the actions.
	 */
	public function init(): void {
		add_filter( 'post_row_actions', [ $this, 'add_duplicate_link' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'add_to_post_link' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'add_reset_link' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'add_force_sent_link' ], 10, 2 );
		add_action( 'admin_action_dame_duplicate', [ $this, 'handle_duplicate' ] );
		add_action( 'admin_action_dame_to_post', [ $this, 'handle_to_post' ] );
		add_action( 'admin_action_dame_reset_send', [ $this, 'handle_reset' ] );
		add_action( 'admin_action_dame_force_sent', [ $this, 'handle_force_sent' ] );
	}

	/**
	 * Add "Duplicate" link to row actions.
	 *
	 * @param array<string, mixed> $actions Existing actions.
	 * @param \WP_Post $post    Current post.
	 * @return array<string, mixed> Modified actions.
	 */
	public function add_duplicate_link( $actions, $post ): array {
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
	 * Add "Dupliquer en tant qu'article" link to row actions.
	 *
	 * @param array<string, mixed> $actions Existing actions.
	 * @param \WP_Post $post    Current post.
	 * @return array<string, mixed> Modified actions.
	 */
	public function add_to_post_link( array $actions, WP_Post $post ): array {
		if ( 'dame_message' !== $post->post_type ) {
			return $actions;
		}

		// Option demandée : transformer un message à l'état publié (ou autre) en article.
		// On limite aux messages qui peuvent être édités.
		if ( ! current_user_can( 'edit_dame_messages' ) || ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin.php?action=dame_to_post&post=' . $post->ID ),
			'dame_to_post_' . $post->ID
		);

		$actions['dame_to_post'] = '<a href="' . esc_url( $url ) . '" title="' . esc_attr__( 'Dupliquer ce message en tant qu\'article', 'dame' ) . '">' . esc_html__( 'Dupliquer en article', 'dame' ) . '</a>';

		return $actions;
	}

	/**
	 * Add "Reset envoi" link to row actions.
	 *
	 * @param array<string, mixed>    $actions Existing actions.
	 * @param \WP_Post $post    Current post.
	 * @return array<string, mixed> Modified actions.
	 */
	 public function add_reset_link( $actions, $post ): array {

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
		$post = $this->get_verified_post( 'dame_duplicate' );

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
	 * Handle duplication to post.
	 */
	public function handle_to_post(): void {
		$post = $this->get_verified_post( 'dame_to_post', [ 'edit_dame_messages', 'edit_posts' ] );

		$new_post_args = array(
			'post_title'   => $post->post_title,
			'post_content' => $post->post_content,
			'post_status'  => 'draft',
			'post_type'    => 'post',
			'post_author'  => get_current_user_id(),
		);

		// Si le message a été envoyé, on récupère sa date d'envoi
		$status = get_post_meta( $post->ID, '_dame_message_status', true );
		if ( 'sent' === $status ) {
			$sent_date = get_post_meta( $post->ID, '_dame_sent_date', true );
			if ( ! empty( $sent_date ) ) {
				// La date peut être un timestamp ou une chaîne Y-m-d H:i:s
				if ( is_numeric( $sent_date ) ) {
					$new_post_args['post_date'] = gmdate( 'Y-m-d H:i:s', (int) $sent_date );
				} else {
					$new_post_args['post_date'] = $sent_date;
				}
			}
		}

		$new_post_id = wp_insert_post( $new_post_args );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( __( 'Erreur lors de la création de l\'article : ', 'dame' ) . esc_html( $new_post_id->get_error_message() ) );
		}

		// Suppression des catégories par défaut si nécessaire pour respecter "aucune catégorie n'est positionné"
		// Bien que WP en mette une par défaut, on vide pour s'assurer que l'utilisateur choisisse la sienne.
		wp_set_post_categories( $new_post_id, array() );

		// Redirect to the edit screen of the new article.
		wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
		exit;
	}

	/**
	 * Handle reset of message send data.
	 */
	public function handle_reset(): void {
		$post = $this->get_verified_post( 'dame_reset_send' );
		$post_id = $post->ID;

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

		// Also purge the individual send dates
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete(
			$wpdb->postmeta,
			[ 'meta_key' => "_dame_message_{$post_id}_sent_at" ],
			[ '%s' ]
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

	/**
	 * Add "Forcer Envoyé" link.
	 * 
	 * @param array<string, mixed> $actions Existing actions.
	 * @param \WP_Post $post Current post.
	 * @return array<string, mixed> Modified actions.
	 */
	public function add_force_sent_link( $actions, $post ): array {
		if ( 'dame_message' !== $post->post_type || ! current_user_can( 'edit_dame_messages' ) ) {
			return $actions;
		}

		$status = get_post_meta( $post->ID, '_dame_message_status', true );
		if ( 'sending' !== $status && 'scheduled' !== $status ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin.php?action=dame_force_sent&post=' . $post->ID ),
			'dame_force_sent_' . $post->ID
		);

		$actions['force_sent'] = sprintf(
			'<a href="%s" style="color: #0073aa;">%s</a>',
			esc_url( $url ),
			esc_html__( 'Terminer l\'envoi', 'dame' )
		);

		return $actions;
	}

	/**
	 * Handle manual completion.
	 */
	public function handle_force_sent(): void {
		$post = $this->get_verified_post( 'dame_force_sent' );
		$post_id = $post->ID;

		$total = (int) get_post_meta( $post_id, '_dame_scheduled_batches_total', true );
		update_post_meta( $post_id, '_dame_message_status', 'sent' );
		update_post_meta( $post_id, '_dame_scheduled_batches_processed', $total );

		wp_redirect( admin_url( 'edit.php?post_type=dame_message' ) );
		exit;
	}

	/**
	 * Verify the request and get the message post object.
	 *
	 * @param string               $nonce_action The base name for the nonce action.
	 * @param string|array<string> $capabilities Required capabilities.
	 * @return WP_Post The validated message post.
	 */
	private function get_verified_post( string $nonce_action, $capabilities = 'edit_dame_messages' ): WP_Post {
		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) ) || ( ! isset( $_GET['_wpnonce'] ) ) ) {
			wp_die( esc_html__( 'Données manquantes.', 'dame' ) );
		}

		$post_id = ( isset( $_GET['post'] ) ? absint( $_GET['post'] ) : absint( $_POST['post'] ) );

		if ( ! wp_verify_nonce( (string) $_REQUEST['_wpnonce'], $nonce_action . '_' . $post_id ) ) {
			wp_die( esc_html__( 'Vérification de sécurité échouée.', 'dame' ) );
		}

		$caps = (array) $capabilities;
		foreach ( $caps as $cap ) {
			if ( ! current_user_can( $cap ) ) {
				wp_die( esc_html__( 'Permission refusée.', 'dame' ) );
			}
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || 'dame_message' !== $post->post_type ) {
			wp_die( esc_html__( 'Message introuvable.', 'dame' ) );
		}

		return $post;
	}
}
