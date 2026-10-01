<?php
/**
 * Mailing Submission Processor.
 *
 * @package DAME\Admin\Pages\Mailing
 */

declare(strict_types=1);

namespace DAME\Admin\Pages\Mailing;

/**
 * Handles validation, recipient processing, attachment handling and queue dispatching for mailings.
 */
class Processor {

	/**
	 * Process the submitted mailing form.
	 */
	public function process(): void {
		$base_url  = admin_url( 'admin.php?page=dame-mailing' );
		$user_id   = get_current_user_id();
		$state_key = 'dame_mailing_state_' . $user_id;

		$save_state_and_redirect = function ( string $error_code ) use ( $base_url, $state_key ): void {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$data = $_POST;
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_FILES['dame_message_attachment']['name'] ) ) {
				$data['_had_attachment'] = true;
			}
			set_transient( $state_key, $data, 300 );
			wp_safe_redirect( add_query_arg( 'error', $error_code, $base_url ) );
			exit;
		};

		// 1. Security and Permissions.
		$nonce = isset( $_POST['dame_mailing_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_mailing_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dame_mailing_action' ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'nonce', $base_url ) );
			exit;
		}

		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'permission', $base_url ) );
			exit;
		}

		$message_id = isset( $_POST['dame_message_to_send'] ) ? absint( $_POST['dame_message_to_send'] ) : 0;
		if ( ! $message_id ) {
			$save_state_and_redirect( 'invalid_message' );
		}

		$adherent_method = isset( $_POST['dame_adherent_method'] ) ? sanitize_key( (string) $_POST['dame_adherent_method'] ) : 'group';
		$contact_method  = isset( $_POST['dame_contact_method'] ) ? sanitize_key( (string) $_POST['dame_contact_method'] ) : 'group';

		$resolver = new RecipientResolver();
		$target   = $resolver->resolve_target_ids( $_POST );

		if ( ! $target['adherent_criteria_selected'] && ! $target['contact_criteria_selected'] ) {
			$save_state_and_redirect( 'no_criteria' );
		}

		$adherent_ids = $target['adherent_ids'];
		$contact_ids  = $target['contact_ids'];

		if ( empty( $adherent_ids ) && empty( $contact_ids ) ) {
			$save_state_and_redirect( 'no_recipients' );
		}

		// Incremental filtering for group selections.
		if ( 'manual' !== $adherent_method ) {
			$adherent_ids = $resolver->filter_already_received( $adherent_ids, $message_id );
		}
		if ( 'manual' !== $contact_method ) {
			$contact_ids = $resolver->filter_already_received( $contact_ids, $message_id );
		}

		if ( empty( $adherent_ids ) && empty( $contact_ids ) ) {
			$save_state_and_redirect( 'all_already_received' );
		}

		// Email collection and deduplication.
		$email_data       = $resolver->collect_email_data( $adherent_ids, $contact_ids );
		$recipient_emails = array_column( $email_data, 'raw_email' );

		if ( empty( $recipient_emails ) ) {
			$save_state_and_redirect( 'no_valid_emails' );
		}

		// SQL tracking registration.
		$resolver->register_tracking_records( $message_id, $email_data );

		// Attachment handling.
		$attachment_handler = new AttachmentHandler();
		if ( ! $attachment_handler->handle_upload( $message_id ) ) {
			$save_state_and_redirect( 'upload_failed' );
		}

		// Save Message Meta & Schedule Queue.
		$old_count = (int) get_post_meta( $message_id, '_dame_message_recipients_count', true );
		$new_total = $old_count + count( $recipient_emails );

		update_post_meta( $message_id, '_dame_message_status', 'scheduled' );
		update_post_meta( $message_id, '_dame_sent_date', current_time( 'mysql', true ) );
		update_post_meta( $message_id, '_dame_message_recipients_count', $new_total );
		update_post_meta( $message_id, '_dame_adherent_method', $adherent_method );
		update_post_meta( $message_id, '_dame_contact_method', $contact_method );

		$meta = $target['meta'];
		if ( 'group' === $adherent_method ) {
			update_post_meta( $message_id, '_dame_recipient_seasons', $meta['seasons'] );
			update_post_meta( $message_id, '_dame_recipient_groups_saisonnier', $meta['groups_saisonnier'] );
			update_post_meta( $message_id, '_dame_recipient_groups_permanent', $meta['groups_permanent'] );
			update_post_meta( $message_id, '_dame_recipient_gender', $meta['gender'] );
		} else {
			update_post_meta( $message_id, '_dame_manual_recipients', $meta['manual_recipients'] );
		}

		if ( 'group' === $contact_method ) {
			update_post_meta( $message_id, '_dame_recipient_contact_types', $meta['contact_types'] );
			update_post_meta( $message_id, '_dame_recipient_depts', $meta['depts'] );
			update_post_meta( $message_id, '_dame_recipient_regions', $meta['regions'] );
		} else {
			update_post_meta( $message_id, '_dame_manual_contacts', $meta['manual_contacts'] );
		}

		$total_batches = (int) ceil( count( $recipient_emails ) / 20 );
		update_post_meta( $message_id, '_dame_scheduled_batches_total', $total_batches );
		update_post_meta( $message_id, '_dame_scheduled_batches_processed', 0 );

		if ( ! wp_next_scheduled( 'dame_cron_process_queue' ) ) {
			wp_schedule_single_event( time(), 'dame_cron_process_queue' );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'success' => 1,
					'count'   => count( $recipient_emails ),
				),
				$base_url
			)
		);
		exit;
	}
}
