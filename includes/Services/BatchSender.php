<?php
/**
 * Batch Sender Service.
 *
 * @package DAME
 */

declare(strict_types=1);

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
	public function init(): void {
		add_action( 'dame_cron_send_batch', [ $this, 'process_batch' ], 10, 3 );
	}

	/**
	 * Sends a batch of emails via WP-Cron.
	 *
	 * @param int   $message_id  The ID of the message post.
	 * @param array<string, mixed> $emails      An array of email addresses to send to.
	 * @param int   $retry_count The number of times this batch has been retried.
	 */
	public function process_batch( int $message_id, array $emails, int $retry_count = 0 ): void {
		$message_post = get_post( $message_id );
		if ( ! $message_post ) {
			return; // Stop if message post is deleted.
		}

		$all_sent_data = []; // To store [post_id => sent_at] for batch update
		$sent_at_now   = current_time( 'mysql', true );

		// Mark as 'sending' on the first batch.
		$status = get_post_meta( $message_id, '_dame_message_status', true );
		if ( 'scheduled' === $status ) {
			update_post_meta( $message_id, '_dame_message_status', 'sending' );
		}

		$subject = $message_post->post_title;
		$content = apply_filters( 'the_content', $message_post->post_content );

		$options      = get_option( 'dame_options', [] );
		$sender_email = isset( $options['sender_email'] ) && is_email( (string) $options['sender_email'] ) ? (string) $options['sender_email'] : (string) get_option( 'admin_email' );
		$headers      = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . $sender_email . '>',
		);

		// Gestion de la pièce jointe (Préparée une seule fois hors de la boucle)
		$attachments = array();
		$attachment_path = get_post_meta( $message_id, '_dame_message_attachment', true );
		if ( ! empty( $attachment_path ) && is_string( $attachment_path ) && file_exists( $attachment_path ) ) {
			$attachments[] = $attachment_path;
		}

		$failed_emails = array();

		foreach ( $emails as $email ) {
			$personalized_subject = $subject;
			$personalized_content = $content;

			global $wpdb;
			$results = $wpdb->get_results( $wpdb->prepare( "
				SELECT post_id, meta_key
				FROM {$wpdb->postmeta}
				WHERE meta_key IN ('_dame_email', '_dame_legal_rep_1_email', '_dame_legal_rep_2_email', '_dame_contact_email')
				AND LOWER(meta_value) = LOWER(%s)
			", $email ) );

			if ( ! empty( $results ) ) {
				$target_post_ids = [];
				$primary_post_id = 0;
				$target_type     = '';

				// On collecte tous les IDs pour le marquage cumulatif
				foreach ( $results as $row ) {
					$target_post_ids[] = (int) $row->post_id;
					
					// Logique de priorité pour la PERSONNALISATION ([NOM] [PRENOM])
					if ( '_dame_email' === $row->meta_key ) {
						$primary_post_id = (int) $row->post_id;
						$target_type     = 'adherent';
					} elseif ( '_dame_contact_email' === $row->meta_key && 'adherent' !== $target_type ) {
						$primary_post_id = (int) $row->post_id;
						$target_type     = 'contact';
					} elseif ( empty( $target_type ) ) {
						$primary_post_id = (int) $row->post_id;
						$target_type     = str_replace( '_dame_legal_rep_', 'rep', str_replace( '_email', '', (string) $row->meta_key ) );
					}
				}

				$target_post_ids = array_unique( $target_post_ids );

				$nom    = '';
				$prenom = '';
				$birth  = '';

				if ( 'adherent' === $target_type ) {
					$nom    = (string) get_post_meta( $primary_post_id, '_dame_last_name', true );
					$prenom = (string) get_post_meta( $primary_post_id, '_dame_first_name', true );
					$birth  = (string) get_post_meta( $primary_post_id, '_dame_birth_date', true );
				} elseif ( 'contact' === $target_type ) {
					$nom    = (string) get_post_meta( $primary_post_id, '_dame_contact_last_name', true );
					$prenom = (string) get_post_meta( $primary_post_id, '_dame_contact_first_name', true );
				} elseif ( strpos( $target_type, 'rep' ) === 0 ) {
					$rep_num = str_replace( 'rep', '', $target_type );
					$nom    = (string) get_post_meta( $primary_post_id, "_dame_legal_rep_{$rep_num}_last_name", true );
					$prenom = (string) get_post_meta( $primary_post_id, "_dame_legal_rep_{$rep_num}_first_name", true );
				}

				$age = '';
				if ( ! empty( $birth ) ) {
					try {
						$age = (string) ( new DateTime( (string) $birth ) )->diff( new DateTime() )->y;
					} catch ( \Exception $e ) {
						$age = '';
					}
				}

				$search  = [ '[NOM]', '[PRENOM]', '[AGE]' ];
				$replace = [
					esc_html( mb_strtoupper( (string) $nom, 'UTF-8' ) ),
					esc_html( mb_convert_case( (string) $prenom, MB_CASE_TITLE, 'UTF-8' ) ),
					esc_html( (string) $age )
				];

				$personalized_subject = str_replace( $search, $replace, $subject );
				$personalized_content = str_replace( $search, $replace, (string) $content );
			}

			// Pixel et envoi
			$tracking_url = Tracker::get_pixel_url( $message_id, (string) $email );
			$pixel_img    = '<img src="' . esc_url( $tracking_url ) . '" alt="" width="1" height="1" style="display:none; border:0;" />';
			$message_body = '<div style="margin: 1cm;">' . $personalized_content . $pixel_img . '</div>';

			$sent = wp_mail( (string) $email, (string) $personalized_subject, $message_body, $headers, $attachments );
			if ( ! $sent ) {
				$failed_emails[] = $email;
			} else {
				// Success: Collect target IDs for batch marking
				if ( ! empty( $target_post_ids ) ) {
					foreach ( $target_post_ids as $tpid ) {
						$all_sent_data[ (int) $tpid ] = $sent_at_now;
					}
				}
			}
		}

		// Perform batch database updates for performance
		if ( ! empty( $all_sent_data ) ) {
			global $wpdb;
			$values_received = [];
			$values_sent_at  = [];
			$sent_at_key     = "_dame_message_{$message_id}_sent_at";

			foreach ( $all_sent_data as $tpid => $time ) {
				$values_received[] = $wpdb->prepare( '(%d, %s, %s)', $tpid, '_dame_message_received', (string) $message_id );
				$values_sent_at[]  = $wpdb->prepare( '(%d, %s, %s)', $tpid, $sent_at_key, $time );
			}

			if ( count( $values_received ) > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->query( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ',', $values_received ) );
			}
			if ( count( $values_sent_at ) > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->query( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ',', $values_sent_at ) );
			}

			// Clean cache for all affected posts
			foreach ( array_keys( $all_sent_data ) as $tpid ) {
				wp_cache_delete( $tpid, 'post_meta' );
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
