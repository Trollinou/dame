<?php
/**
 * Batch Sender Service.
 *
 * @package DAME
 */

namespace DAME\Services;

use DAME\API\Tracker;

/**
 * Class BatchSender
 */
class BatchSender {

	/**
	 * Initialize the BatchSender.
	 */
	public function init() {
		add_action( 'dame_cron_send_batch', [ $this, 'process_batch' ], 10, 3 );
	}

	/**
	 * Sends a batch of emails via WP-Cron.
	 *
	 * @param int   $message_id  The ID of the message post.
	 * @param array $emails      An array of email addresses to send to.
	 * @param int   $retry_count The number of times this batch has been retried.
	 */
	public function process_batch( $message_id, $emails, $retry_count = 0 ) {
		$message_post = get_post( $message_id );
		if ( ! $message_post ) {
			return; // Stop if message post is deleted.
		}

		// Mark as 'sending' on the first batch.
		$status = get_post_meta( $message_id, '_dame_message_status', true );
		if ( 'scheduled' === $status ) {
			update_post_meta( $message_id, '_dame_message_status', 'sending' );
		}

		$subject = $message_post->post_title;
		$content = apply_filters( 'the_content', $message_post->post_content );

		$options      = get_option( 'dame_options', [] );
		$sender_email = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );
		$headers      = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . $sender_email . '>',
		);

		$failed_emails = array();

		foreach ( $emails as $email ) {
			$tracking_url = Tracker::get_pixel_url( $message_id, $email );
			$pixel_img    = '<img src="' . esc_url( $tracking_url ) . '" alt="" width="1" height="1" style="display:none; border:0;" />';

			$message_body = '<div style="margin: 1cm;">' . $content . $pixel_img . '</div>';

			$sent = wp_mail( $email, $subject, $message_body, $headers );
			if ( ! $sent ) {
				$failed_emails[] = $email;
			}
		}

		// Handle failures with a retry mechanism.
		if ( ! empty( $failed_emails ) && $retry_count < 3 ) {
			wp_schedule_single_event(
				time() + 60, // Retry in 1 minute.
				'dame_cron_send_batch',
				array(
					$message_id,
					$failed_emails,
					$retry_count + 1,
				)
			);
		}

		// Update progress tracking meta.
		// Only increment processed batches if this is the first attempt,
		// otherwise we're just retrying a previously counted batch.
		if ( 0 === $retry_count ) {
			$processed_batches = (int) get_post_meta( $message_id, '_dame_scheduled_batches_processed', true );
			$total_batches     = (int) get_post_meta( $message_id, '_dame_scheduled_batches_total', true );

			$processed_batches++;
			update_post_meta( $message_id, '_dame_scheduled_batches_processed', $processed_batches );

			// If all batches are done, mark as sent.
			if ( $processed_batches >= $total_batches ) {
				update_post_meta( $message_id, '_dame_message_status', 'sent' );
			}
		}
	}
}
