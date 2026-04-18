<?php
/**
 * Batch Sender Service.
 *
 * @package DAME
 */

namespace DAME\Services;

use DateTime;
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
			$personalized_subject = $subject;
			$personalized_content = $content;

			global $wpdb;
			$results = $wpdb->get_results( $wpdb->prepare( "
				SELECT post_id, meta_key
				FROM {$wpdb->postmeta}
				WHERE meta_key IN ('_dame_email', '_dame_legal_rep_1_email', '_dame_legal_rep_2_email')
				AND meta_value = %s
			", $email ) );

			if ( ! empty( $results ) ) {
				$target_post_id = 0;
				$target_type    = '';

				// Règle de priorité : on cherche d'abord l'adhérent
				foreach ( $results as $row ) {
					if ( '_dame_email' === $row->meta_key ) {
						$target_post_id = $row->post_id;
						$target_type    = 'adherent';
						break; // Priorité absolue trouvée
					}
					if ( '_dame_legal_rep_1_email' === $row->meta_key ) {
						$target_post_id = $row->post_id;
						$target_type    = 'rep1';
					} elseif ( '_dame_legal_rep_2_email' === $row->meta_key && empty( $target_type ) ) {
						$target_post_id = $row->post_id;
						$target_type    = 'rep2';
					}
				}

				$nom    = '';
				$prenom = '';
				$birth  = '';

				if ( 'adherent' === $target_type ) {
					$nom    = get_post_meta( $target_post_id, '_dame_last_name', true );
					$prenom = get_post_meta( $target_post_id, '_dame_first_name', true );
					$birth  = get_post_meta( $target_post_id, '_dame_birth_date', true );
				} elseif ( 'rep1' === $target_type ) {
					$nom    = get_post_meta( $target_post_id, '_dame_legal_rep_1_last_name', true );
					$prenom = get_post_meta( $target_post_id, '_dame_legal_rep_1_first_name', true );
				} elseif ( 'rep2' === $target_type ) {
					$nom    = get_post_meta( $target_post_id, '_dame_legal_rep_2_last_name', true );
					$prenom = get_post_meta( $target_post_id, '_dame_legal_rep_2_first_name', true );
				}

				$age = '';
				if ( ! empty( $birth ) ) {
					try {
						$age = ( new DateTime( $birth ) )->diff( new DateTime() )->y;
					} catch ( \Exception $e ) {
						$age = '';
					}
				}

				$search  = [ '[NOM]', '[PRENOM]', '[AGE]' ];
				$replace = [
					mb_strtoupper( $nom, 'UTF-8' ),
					mb_convert_case( $prenom, MB_CASE_TITLE, 'UTF-8' ),
					$age
				];

				$personalized_subject = str_replace( $search, $replace, $subject );
				$personalized_content = str_replace( $search, $replace, $content );
			}

			// Pixel et envoi
			$tracking_url = Tracker::get_pixel_url( $message_id, $email );
			$pixel_img    = '<img src="' . esc_url( $tracking_url ) . '" alt="" width="1" height="1" style="display:none; border:0;" />';
			$message_body = '<div style="margin: 1cm;">' . $personalized_content . $pixel_img . '</div>';

			// Gestion de la pièce jointe
			$attachments = array();
			$attachment_path = get_post_meta( $message_id, '_dame_message_attachment', true );
			if ( ! empty( $attachment_path ) && file_exists( $attachment_path ) ) {
				$attachments[] = $attachment_path;
			}

			$sent = wp_mail( $email, $personalized_subject, $message_body, $headers, $attachments );
			if ( ! $sent ) {
				$failed_emails[] = $email;
			}
			sleep( 3 ); // Sleep 3 seconds to smooth server load.
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
