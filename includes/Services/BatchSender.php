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
		add_action( 'dame_cron_process_queue', [ $this, 'process_queue' ] );
	}

	/**
	 * Processes the global FIFO queue for email sending.
	 * 
	 * Fetches the oldest 20 unsent records across all messages to respect
	 * the global limit of 20 emails per minute.
	 */
	public function process_queue(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_message_opens';
		
		// Get batch size from settings, default to 20
		$options    = get_option( 'dame_options' );
		$batch_size = ! empty( $options['smtp_batch_size'] ) ? absint( $options['smtp_batch_size'] ) : 20;

		// 1. Fetch the oldest unsent rows (First-In, First-Out)
		// ONLY for messages that are currently 'scheduled' or 'sending'
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$pending = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.* FROM {$table_name} t
			INNER JOIN {$wpdb->postmeta} m ON t.message_id = m.post_id
			WHERE t.sent_at IS NULL 
			AND m.meta_key = %s
			AND m.meta_value IN (%s, %s)
			ORDER BY t.id ASC LIMIT %d",
			'_dame_message_status',
			'scheduled',
			'sending',
			$batch_size
		), ARRAY_A );

		if ( empty( $pending ) ) {
			return; // Queue empty or no active message to send
		}

		$sent_at_now = current_time( 'mysql', true );
		$by_message  = [];

		// Group by message_id for efficient processing
		foreach ( $pending as $row ) {
			$by_message[ (int) $row['message_id'] ][] = $row;
		}

		foreach ( $by_message as $mid => $rows ) {
			$message_post = get_post( $mid );
			if ( ! $message_post ) {
				// Message deleted, mark all rows for this message as "sent" (or delete them) to clear queue
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update( $table_name, [ 'sent_at' => $sent_at_now ], [ 'message_id' => $mid, 'sent_at' => null ] );
				continue;
			}

			// Update message status to 'sending' if it's still 'scheduled'
			$status = get_post_meta( $mid, '_dame_message_status', true );
			if ( 'scheduled' === $status ) {
				update_post_meta( $mid, '_dame_message_status', 'sending' );
			}

			$subject     = $message_post->post_title;
			$content     = apply_filters( 'the_content', $message_post->post_content );
			$options     = get_option( 'dame_options' );
			$from_name   = $options['sender_name'] ?? get_bloginfo( 'name' );
			$from_email  = $options['sender_email'] ?? get_option( 'admin_email' );
			$headers     = [
				'Content-Type: text/html; charset=UTF-8',
				"From: {$from_name} <{$from_email}>"
			];
			$attachment  = get_post_meta( $mid, '_dame_message_attachment', true );
			$attachments = ! empty( $attachment ) ? [ $attachment ] : [];

			foreach ( $rows as $row ) {
				$email = $row['recipient_email'];
				$label = $row['recipient_name'];
				
				// Personalization (Extracted from recipient_id)
				$rid    = (int) $row['recipient_id'];
				$nom    = '';
				$prenom = '';
				$age    = '';

				$type = get_post_type( $rid );
				if ( 'adherent' === $type ) {
					// Check if email matches adherent or one of their reps
					$adherent_email = get_post_meta( $rid, '_dame_email', true );
					if ( strtolower( trim( (string) $adherent_email ) ) === strtolower( trim( (string) $email ) ) ) {
						$nom    = (string) get_post_meta( $rid, '_dame_last_name', true );
						if ( empty( $nom ) ) {
							$nom = (string) get_post_meta( $rid, '_dame_birth_name', true );
						}
						$prenom = (string) get_post_meta( $rid, '_dame_first_name', true );
						$birth  = (string) get_post_meta( $rid, '_dame_birth_date', true );
						if ( ! empty( $birth ) ) {
							try {
								$age = (string) ( new DateTime( (string) $birth ) )->diff( new DateTime() )->y;
							} catch ( \Exception $e ) {
								$age = '';
							}
						}
					} else {
						// Check legal representatives
						for ( $i = 1; $i <= 2; $i++ ) {
							$rep_email = get_post_meta( $rid, "_dame_legal_rep_{$i}_email", true );
							if ( strtolower( trim( (string) $rep_email ) ) === strtolower( trim( (string) $email ) ) ) {
								$nom    = (string) get_post_meta( $rid, "_dame_legal_rep_{$i}_last_name", true );
								$prenom = (string) get_post_meta( $rid, "_dame_legal_rep_{$i}_first_name", true );
								break;
							}
						}
					}
				} elseif ( 'dame_contact' === $type ) {
					$nom    = (string) get_post_meta( $rid, '_dame_contact_last_name', true );
					$prenom = (string) get_post_meta( $rid, '_dame_contact_first_name', true );
				}

				if ( empty( $nom ) && empty( $prenom ) ) {
					$search  = [ '[NOM]', '[PRENOM]', '[AGE]' ];
					$replace = [ $label, '', '' ];
				} else {
					$search  = [ '[NOM]', '[PRENOM]', '[AGE]' ];
					$replace = [
						esc_html( \DAME\Core\Utils::format_lastname( (string) $nom ) ),
						esc_html( \DAME\Core\Utils::format_firstname( (string) $prenom ) ),
						esc_html( (string) $age )
					];
				}
				
				$p_subject = str_replace( $search, $replace, $subject );
				$p_content = str_replace( $search, $replace, $content );

				$tracking_url = Tracker::get_pixel_url( $mid, (string) $email );
				$pixel_img    = '<img src="' . esc_url( $tracking_url ) . '" alt="" width="1" height="1" style="display:none; border:0;" />';
				$message_body = '<div style="margin: 1cm;">' . $p_content . $pixel_img . '</div>';

				$sent = wp_mail( (string) $email, (string) $p_subject, $message_body, $headers, $attachments );

				if ( $sent ) {
					// Mark individual row as sent
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->update( $table_name, [ 'sent_at' => $sent_at_now ], [ 'id' => $row['id'] ] );
				}
			}

			// Finalize message status if no more pending for THIS message
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$remaining_for_msg = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE message_id = %d AND sent_at IS NULL", $mid ) );
			if ( 0 === $remaining_for_msg ) {
				update_post_meta( $mid, '_dame_message_status', 'sent' );
				// Update processed batches count for legacy UI
				$total = (int) get_post_meta( $mid, '_dame_scheduled_batches_total', true );
				update_post_meta( $mid, '_dame_scheduled_batches_processed', $total );
			} else {
				// Update progression for legacy UI
				$total = (int) get_post_meta( $mid, '_dame_scheduled_batches_total', true );
				$sent_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE message_id = %d AND sent_at IS NOT NULL", $mid ) );
				// Map sent_count to approximate batch progress
				$processed = $total > 0 ? floor( ( $sent_count / ( $sent_count + $remaining_for_msg ) ) * $total ) : 0;
				update_post_meta( $mid, '_dame_scheduled_batches_processed', (int) $processed );
			}
		}

		// 4. Check if there are more pending across ALL messages
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$has_more = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE sent_at IS NULL" );
		if ( $has_more > 0 ) {
			wp_schedule_single_event( time() + 60, 'dame_cron_process_queue' );
		}
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

		$all_sent_data   = [];
		$legacy_post_ids = [];
 // To store [post_id => sent_at] for batch update
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
			$target_post_ids      = [];

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
					if ( empty( $nom ) ) {
						$nom = (string) get_post_meta( $primary_post_id, '_dame_birth_name', true );
					}
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
					esc_html( \DAME\Core\Utils::format_lastname( (string) $nom ) ),
					esc_html( \DAME\Core\Utils::format_firstname( (string) $prenom ) ),
					esc_html( (string) $age )
				];

				$personalized_subject = str_replace( $search, $replace, $subject );
				$personalized_content = str_replace( $search, $replace, (string) $content );
			}

			$tracking_url = Tracker::get_pixel_url( $message_id, (string) $email );
			$pixel_img    = '<img src="' . esc_url( $tracking_url ) . '" alt="" width="1" height="1" style="display:none; border:0;" />';
			$message_body = '<div style="margin: 1cm;">' . $personalized_content . $pixel_img . '</div>';

			$sent = wp_mail( (string) $email, (string) $personalized_subject, $message_body, $headers, $attachments );
			if ( ! $sent ) {
				$failed_emails[] = $email;
			} else {
				// Success: Collect unique email for batch marking in SQL table
				$all_sent_data[] = [
					'email'   => (string) $email,
					'time'    => $sent_at_now
				];
				
				// Collect ALL IDs associated with this email for legacy marking (batch count)
				foreach ( $target_post_ids as $tpid ) {
					// Mark as received to allow incremental filtering
					add_post_meta( $tpid, '_dame_message_received', (string) $message_id );
					$legacy_post_ids[] = (int) $tpid;
				}
			}
		}

		// Perform batch database updates for performance
		if ( ! empty( $all_sent_data ) ) {
			global $wpdb;
			$table_tracking = $wpdb->prefix . 'dame_message_opens';

			foreach ( $all_sent_data as $entry ) {
				$mail = $entry['email'];
				$time = $entry['time'];

				// Update SQL Tracking (Match only by Message and Email)
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update( 
					$table_tracking, 
					[ 'sent_at' => $time ], 
					[ 'message_id' => $message_id, 'recipient_email' => $mail ],
					[ '%s' ],
					[ '%d', '%s' ]
				);
			}

			// Clean cache for all affected posts
			if ( ! empty( $legacy_post_ids ) ) {
				foreach ( array_unique( $legacy_post_ids ) as $tpid ) {
					wp_cache_delete( $tpid, 'post_meta' );
				}
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
