<?php
/**
 * Pre-inscription Mailer Service.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Services;

use DAME\Core\Utils;
use DAME\Services\Document_Storage;

/**
 * Class PreInscription_Mailer
 * Handles email confirmations and notifications for pre-inscriptions via BatchSender queue.
 */
class PreInscription_Mailer {

	/**
	 * Queues a confirmation email to the adherent and legal representatives in the global BatchSender queue.
	 *
	 * @param int                  $post_id        Pre-inscription Post ID.
	 * @param array<string, mixed> $sanitized_data Form data.
	 * @param bool                 $is_update      Whether this is an update or a new submission.
	 * @return bool True if queued successfully, false otherwise.
	 */
	public static function send_adherent_confirmation( int $post_id, array $sanitized_data, bool $is_update = false ): bool {
		$recipients = array();

		// 1. Collect recipient emails (adherent and legal reps).
		$adh_email = isset( $sanitized_data['dame_email'] ) ? (string) $sanitized_data['dame_email'] : (string) get_post_meta( $post_id, '_dame_email', true );
		if ( ! empty( $adh_email ) && is_email( $adh_email ) ) {
			$recipients[] = $adh_email;
		}

		$rep1_email = isset( $sanitized_data['dame_legal_rep_1_email'] ) ? (string) $sanitized_data['dame_legal_rep_1_email'] : (string) get_post_meta( $post_id, '_dame_legal_rep_1_email', true );
		if ( ! empty( $rep1_email ) && is_email( $rep1_email ) ) {
			$recipients[] = $rep1_email;
		}

		$rep2_email = isset( $sanitized_data['dame_legal_rep_2_email'] ) ? (string) $sanitized_data['dame_legal_rep_2_email'] : (string) get_post_meta( $post_id, '_dame_legal_rep_2_email', true );
		if ( ! empty( $rep2_email ) && is_email( $rep2_email ) ) {
			$recipients[] = $rep2_email;
		}

		$recipients = array_values( array_unique( $recipients ) );
		if ( empty( $recipients ) ) {
			return false;
		}

		// 2. Prepare identity labels.
		$nom_raw = '';
		if ( ! empty( $sanitized_data['dame_last_name'] ) ) {
			$nom_raw = (string) $sanitized_data['dame_last_name'];
		} elseif ( ! empty( $sanitized_data['dame_birth_name'] ) ) {
			$nom_raw = (string) $sanitized_data['dame_birth_name'];
		} else {
			$meta_last = (string) get_post_meta( $post_id, '_dame_last_name', true );
			$nom_raw   = ! empty( $meta_last ) ? $meta_last : (string) get_post_meta( $post_id, '_dame_birth_name', true );
		}

		$prenom_raw = isset( $sanitized_data['dame_first_name'] ) ? (string) $sanitized_data['dame_first_name'] : (string) get_post_meta( $post_id, '_dame_first_name', true );

		// 3. Collect attachments (signed health attestation & parental authorization).
		$attachments = array();

		$health_doc = (string) get_post_meta( $post_id, '_dame_doc_health_attestation_path', true );
		if ( ! empty( $health_doc ) ) {
			$abs_health = Document_Storage::get_absolute_path( $health_doc );
			if ( $abs_health && file_exists( $abs_health ) ) {
				$attachments[] = $abs_health;
			}
		}

		$parental_doc = (string) get_post_meta( $post_id, '_dame_doc_parental_auth_path', true );
		if ( ! empty( $parental_doc ) ) {
			$abs_parental = Document_Storage::get_absolute_path( $parental_doc );
			if ( $abs_parental && file_exists( $abs_parental ) ) {
				$attachments[] = $abs_parental;
			}
		}

		// 4. Build email content using standard tags [NOM], [PRENOM], [CIVILITE].
		$options     = get_option( 'dame_options', array() );
		$sender_name = ! empty( $options['sender_name'] ) ? (string) $options['sender_name'] : get_bloginfo( 'name' );
		$payment_url = isset( $options['payment_url'] ) ? trim( (string) $options['payment_url'] ) : '';

		$subject = $is_update
			? sprintf( '[%s] Mise à jour de votre préinscription — [PRENOM] [NOM]', $sender_name )
			: sprintf( '[%s] Confirmation de votre demande de préinscription — [PRENOM] [NOM]', $sender_name );

		$health_q = '';
		if ( isset( $sanitized_data['dame_health_questionnaire'] ) ) {
			$health_q = (string) $sanitized_data['dame_health_questionnaire'];
		} else {
			$health_q = (string) get_post_meta( $post_id, '_dame_health_questionnaire', true );
		}

		if ( empty( $health_q ) ) {
			$health_doc = (string) get_post_meta( $post_id, '_dame_health_document', true );
			if ( 'certificate' === $health_doc ) {
				$health_q = 'oui';
			} elseif ( 'attestation' === $health_doc ) {
				$health_q = 'non';
			}
		}

		$body_lines   = array();
		$body_lines[] = '<p>Bonjour [CIVILITE] [NOM],</p>';
		if ( $is_update ) {
			$body_lines[] = '<p>Nous vous confirmons la bonne prise en compte de la mise à jour de la préinscription pour <strong>[PRENOM] [NOM]</strong>.</p>';
		} else {
			$body_lines[] = '<p>Nous avons bien reçu votre demande de préinscription pour <strong>[PRENOM] [NOM]</strong>.</p>';
		}

		if ( 'oui' === strtolower( $health_q ) ) {
			$body_lines[] = '<p><strong>IMPORTANT :</strong> Vous avez indiqué avoir répondu &laquo;&nbsp;OUI&nbsp;&raquo; au questionnaire de santé. Votre adhésion ne pourra être définitivement validée par le club qu\'après obtention de votre certificat médical (daté de moins de 6 mois attestant l\'aptitude à la pratique des échecs) ainsi que de votre règlement.</p>';
		} else {
			$body_lines[] = '<p><strong>IMPORTANT :</strong> Votre adhésion sera définitivement traitée et validée par le club dès la réception de votre règlement.</p>';
		}

		if ( ! empty( $payment_url ) ) {
			$body_lines[] = '<p>Pour régler votre cotisation en ligne dès maintenant, vous pouvez utiliser notre plateforme de paiement sécurisée :<br><a href="' . esc_url( $payment_url ) . '">' . esc_html( $payment_url ) . '</a></p>';
		}

		$body_lines[] = '<p>Si vous réglez par un autre moyen (chèque, espèces, virement, Pass\'Sport, etc.), merci de vous rapprocher des responsables du club.</p>';

		if ( ! empty( $attachments ) ) {
			$body_lines[] = '<p>Vous trouverez en pièce jointe de cet e-mail la copie des documents complétés et signés électroniquement lors de votre démarche.</p>';
		}

		$body_lines[] = '<p>À très bientôt,<br>L\'équipe de ' . esc_html( $sender_name ) . '</p>';

		$body_html = implode( "\n", $body_lines );

		// 5. Create private dame_message post for queue processing.
		$message_data = array(
			'post_title'   => $subject,
			'post_content' => $body_html,
			'post_type'    => 'dame_message',
			'post_status'  => 'private',
		);
		$message_id   = wp_insert_post( $message_data );

		if ( is_wp_error( $message_id ) || ! ( $message_id > 0 ) ) {
			return false;
		}

		update_post_meta( $message_id, '_dame_message_status', 'scheduled' );
		update_post_meta( $message_id, '_dame_message_type', 'pre_inscription_confirmation' );
		update_post_meta( $message_id, '_dame_message_recipients_count', count( $recipients ) );
		update_post_meta( $message_id, '_dame_pre_inscription_id', $post_id );

		if ( ! empty( $attachments ) ) {
			update_post_meta( $message_id, '_dame_message_attachments', $attachments );
		}

		// 6. Insert into tracking / queue table.
		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_message_opens';
		$label      = Utils::format_firstname( (string) $prenom_raw ) . ' ' . Utils::format_lastname( (string) $nom_raw );

		foreach ( $recipients as $recipient_email ) {
			$hash = md5( strtolower( trim( $recipient_email ) ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$table_name,
				array(
					'message_id'      => $message_id,
					'recipient_id'    => $post_id,
					'recipient_name'  => $label,
					'recipient_email' => $recipient_email,
					'email_hash'      => $hash,
				),
				array( '%d', '%d', '%s', '%s', '%s' )
			);
		}

		// 7. Schedule immediate background queue processing if not scheduled.
		if ( ! wp_next_scheduled( 'dame_cron_process_queue' ) ) {
			wp_schedule_single_event( time(), 'dame_cron_process_queue' );
		}

		if ( function_exists( 'spawn_cron' ) ) {
			spawn_cron();
		}

		return true;
	}
}
