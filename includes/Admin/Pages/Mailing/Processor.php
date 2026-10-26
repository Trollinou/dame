<?php
/**
 * Mailing Submission Processor.
 *
 * @package DAME\Admin\Pages\Mailing
 */

declare(strict_types=1);

namespace DAME\Admin\Pages\Mailing;

use DAME\API\Tracker;

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

		$raw_priority = isset( $_POST['dame_sending_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_sending_priority'] ) ) : '';
		if ( '' === $raw_priority || ! in_array( (int) $raw_priority, array( 10, 20 ), true ) ) {
			$save_state_and_redirect( 'no_priority' );
		}
		$priority = (int) $raw_priority;

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
		$email_data = $resolver->collect_email_data( $adherent_ids, $contact_ids );

		// For group selections, also filter out any emails that were already sent/scheduled for this message.
		if ( 'manual' !== $adherent_method && 'manual' !== $contact_method ) {
			$email_data = $resolver->filter_already_sent_emails( $email_data, $message_id );
		}

		$recipient_emails = array_column( $email_data, 'raw_email' );

		if ( empty( $recipient_emails ) ) {
			$save_state_and_redirect( 'all_already_received' );
		}

		// Attachment handling.
		$attachment_handler = new AttachmentHandler();
		if ( ! $attachment_handler->handle_upload( $message_id ) ) {
			$save_state_and_redirect( 'upload_failed' );
		}

		// Prepare attachments list.
		$attachments     = array();
		$attachment_path = get_post_meta( $message_id, '_dame_message_attachment', true );
		if ( ! empty( $attachment_path ) && is_string( $attachment_path ) ) {
			$norm_att = wp_normalize_path( $attachment_path );
			if ( file_exists( $norm_att ) ) {
				$attachments[] = $norm_att;
			} elseif ( file_exists( $attachment_path ) ) {
				$attachments[] = $attachment_path;
			}
		}

		$message_post = get_post( $message_id );
		if ( ! $message_post ) {
			$save_state_and_redirect( 'invalid_message' );
		}

		$base_subject = $message_post->post_title;
		$raw_content  = apply_filters( 'the_content', $message_post->post_content );
		$base_content = is_string( $raw_content ) ? $raw_content : (string) $message_post->post_content;

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'X-Queue-Priority: ' . $priority,
			'X-Queue-Batch: dame-message-' . $message_id,
		);

		$sent_at_now      = current_time( 'mysql', true );
		$sent_count       = 0;
		$sent_emails_data = array();

		// Dispatch via standard wp_mail() (transparently queued/handled by EG Mail).
		foreach ( $email_data as $info ) {
			$email = $info['raw_email'];
			$label = implode( ', ', array_unique( $info['names'] ) );
			$rid   = (int) $info['id'];

			$personalized = $resolver->personalize_message( $rid, $email, $label, $base_subject, $base_content );
			$tracking_url = Tracker::get_pixel_url( $message_id, $email );
			$message_body = Tracker::prepare_email_body( $personalized['content'], $tracking_url );

			$sent = wp_mail( $email, $personalized['subject'], $message_body, $headers, $attachments );
			if ( $sent ) {
				$sent_emails_data[ strtolower( $email ) ] = $info;
				++$sent_count;
			}
		}

		if ( ! empty( $sent_emails_data ) ) {
			$resolver->register_tracking_records( $message_id, $sent_emails_data, $sent_at_now );
		}

		// Update Message Post Metadata.
		$old_count = (int) get_post_meta( $message_id, '_dame_message_recipients_count', true );
		$new_total = $old_count + $sent_count;

		update_post_meta( $message_id, '_dame_message_status', 'sent' );
		update_post_meta( $message_id, '_dame_sent_date', $sent_at_now );
		update_post_meta( $message_id, '_dame_sending_priority', $priority );
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

		wp_safe_redirect(
			add_query_arg(
				array(
					'success' => 1,
					'count'   => $sent_count,
				),
				$base_url
			)
		);
		exit;
	}
}
