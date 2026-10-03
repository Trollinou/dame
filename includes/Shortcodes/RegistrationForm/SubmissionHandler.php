<?php
/**
 * Registration Form Submission Handler.
 *
 * @package DAME\Shortcodes\RegistrationForm
 */

declare(strict_types=1);

namespace DAME\Shortcodes\RegistrationForm;

use DateTime;
use DAME\Core\Utils;
use DAME\Services\PDF_Generator;
use DAME\Services\PreInscription_Mailer;

/**
 * Handles AJAX submission, post creation, meta storage, PDF signing and confirmation emails for pre-inscriptions.
 */
class SubmissionHandler {

	/**
	 * Process form submission.
	 */
	public function handle(): void {
		$nonce = isset( $_POST['dame_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dame_pre_inscription_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'La vérification de sécurité a échoué. Veuillez rafraîchir la page.', 'dame' ) ), 403 );
		}

		if ( empty( $_POST['dame_last_name'] ) && ! empty( $_POST['dame_birth_name'] ) ) {
			$_POST['dame_last_name'] = sanitize_text_field( wp_unslash( $_POST['dame_birth_name'] ) );
		}

		$validator = new SubmissionValidator();
		$errors    = $validator->validate( $_POST );

		if ( ! empty( $errors ) ) {
			wp_send_json_error( array( 'message' => implode( '<br>', $errors ) ), 400 );
		}

		$sanitized_data = $this->sanitize_input( $_POST );

		// Minor checks.
		$is_minor = false;
		if ( isset( $sanitized_data['dame_birth_date'] ) ) {
			$birth_date = DateTime::createFromFormat( 'Y-m-d', $sanitized_data['dame_birth_date'] );
			if ( $birth_date ) {
				$today    = new DateTime();
				$age      = $today->diff( $birth_date )->y;
				$is_minor = ( $age < 18 );

				if ( ! $is_minor ) {
					foreach ( $sanitized_data as $key => $value ) {
						if ( str_starts_with( $key, 'dame_legal_rep_' ) ) {
							unset( $sanitized_data[ $key ] );
						}
					}
				}
			}
		}

		$first_name          = $sanitized_data['dame_first_name'] ?? '';
		$last_name           = $sanitized_data['dame_last_name'] ?? '';
		$birth_name          = $sanitized_data['dame_birth_name'] ?? '';
		$effective_last_name = ! empty( $last_name ) ? $last_name : $birth_name;

		// Idempotency lock: avoid duplicate submissions within 15 seconds.
		$lock_key        = 'dame_pre_lock_' . md5( strtolower( (string) $first_name . (string) $effective_last_name . ( $sanitized_data['dame_birth_date'] ?? '' ) . ( $sanitized_data['dame_email'] ?? '' ) ) );
		$cached_response = get_transient( $lock_key );
		if ( false !== $cached_response ) {
			if ( is_array( $cached_response ) ) {
				wp_send_json_success( $cached_response );
			}
			wp_send_json_success( array( 'message' => __( 'La préinscription a bien été enregistrée.', 'dame' ) ) );
		}
		set_transient( $lock_key, '1', 15 );

		// Create Pre-inscription Post.
		$post_title = Utils::format_lastname( (string) $effective_last_name ) . ' ' . Utils::format_firstname( (string) $first_name );

		$post_data = array(
			'post_title'  => $post_title,
			'post_type'   => 'dame_pre_inscription',
			'post_status' => 'pending',
		);
		$post_id   = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => __( 'Erreur lors de la création de la fiche de préinscription.', 'dame' ) . ' ' . $post_id->get_error_message() ) );
		}

		// Bulk save metadata.
		$this->save_metadata( (int) $post_id, $sanitized_data );

		// Process electronic signature if provided.
		$signature_image     = isset( $_POST['signature_image'] ) ? sanitize_text_field( wp_unslash( $_POST['signature_image'] ) ) : '';
		$has_signed_health   = false;
		$has_signed_parental = false;

		if ( ! empty( $signature_image ) && str_starts_with( $signature_image, 'data:image/png;base64,' ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$raw_png = base64_decode( substr( $signature_image, strlen( 'data:image/png;base64,' ) ) );
			if ( $raw_png ) {
				$temp_sig = wp_tempnam( 'sig_' );
				if ( $temp_sig ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
					file_put_contents( $temp_sig, $raw_png );

					$remote_ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
					$audit_data = array(
						'timestamp' => time(),
						'ip'        => $remote_ip,
					);

					$pdf_service = new PDF_Generator();

					if ( isset( $sanitized_data['dame_health_questionnaire'] ) && 'non' === $sanitized_data['dame_health_questionnaire'] ) {
						$stored_health = $pdf_service->save_signed_health_doc( (int) $post_id, $temp_sig, $audit_data );
						if ( $stored_health ) {
							update_post_meta( (int) $post_id, '_dame_doc_health_attestation_path', $stored_health );
							$has_signed_health = true;
						}
					}

					if ( $is_minor ) {
						$stored_parental = $pdf_service->save_signed_parental_doc( (int) $post_id, $temp_sig, $audit_data );
						if ( $stored_parental ) {
							update_post_meta( (int) $post_id, '_dame_doc_parental_auth_path', $stored_parental );
							$has_signed_parental = true;
						}
					}

					update_post_meta( (int) $post_id, '_dame_signature_date', current_time( 'mysql' ) );
					update_post_meta( (int) $post_id, '_dame_signature_ip', $remote_ip );

					if ( file_exists( $temp_sig ) ) {
						wp_delete_file( $temp_sig );
					}
				}
			}
		}

		// Email Notifications.
		$options         = get_option( 'dame_options' );
		$recipient_email = isset( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

		$subject = 'Nouvelle préinscription de ' . $first_name . ' ' . $last_name;
		$body    = "Une nouvelle demande de préinscription a été soumise.\n\nVoici les détails :\n";
		foreach ( $sanitized_data as $key => $value ) {
			if ( ! empty( $value ) ) {
				$label = str_replace( array( 'dame_', '_' ), array( '', ' ' ), $key );
				$label = mb_convert_case( $label, MB_CASE_TITLE, 'UTF-8' );
				$body .= '- ' . $label . ': ' . $value . "\n";
			}
		}
		$headers = array( 'From: ' . $recipient_email );
		wp_mail( $recipient_email, $subject, $body, $headers );

		PreInscription_Mailer::send_adherent_confirmation( (int) $post_id, $sanitized_data );

		// Return JSON response.
		$payment_url  = isset( $options['payment_url'] ) ? $options['payment_url'] : '';
		$sender_email = isset( $options['sender_email'] ) && ! empty( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

		$response_data = array(
			'message'              => sprintf(
				/* translators: 1: first name, 2: last name */
				__( 'La préinscription pour %1$s %2$s a bien été enregistrée.', 'dame' ),
				$first_name,
				$last_name
			),
			'health_questionnaire' => $sanitized_data['dame_health_questionnaire'] ?? '',
			'post_id'              => $post_id,
			'full_name'            => Utils::format_lastname( (string) $effective_last_name ) . ' ' . Utils::format_firstname( (string) $first_name ),
			'nonce'                => wp_create_nonce( 'dame_generate_health_form_' . $post_id ),
			'is_minor'             => $is_minor,
			'payment_url'          => $payment_url,
			'sender_email'         => $sender_email,
			'has_signed_health'    => $has_signed_health,
			'has_signed_parental'  => $has_signed_parental,
		);

		if ( $is_minor ) {
			$response_data['parental_auth_nonce'] = wp_create_nonce( 'dame_generate_parental_auth_' . $post_id );
		}

		set_transient( $lock_key, $response_data, 15 );

		wp_send_json_success( $response_data );
	}

	/**
	 * Sanitizes raw POST input.
	 *
	 * @param array<string, mixed> $raw_data Raw input.
	 * @return array<string, string> Sanitized data.
	 */
	private function sanitize_input( array $raw_data ): array {
		$sanitized_data     = array();
		$fields_to_sanitize = array(
			'dame_first_name',
			'dame_last_name',
			'dame_birth_name',
			'dame_birth_date',
			'dame_license_type',
			'dame_birth_city',
			'dame_sexe',
			'dame_profession',
			'dame_email',
			'dame_phone_number',
			'dame_address_1',
			'dame_address_2',
			'dame_postal_code',
			'dame_city',
			'dame_taille_vetements',
			'dame_legal_rep_1_first_name',
			'dame_legal_rep_1_last_name',
			'dame_legal_rep_1_email',
			'dame_legal_rep_1_phone',
			'dame_legal_rep_1_address_1',
			'dame_legal_rep_1_address_2',
			'dame_legal_rep_1_postal_code',
			'dame_legal_rep_1_city',
			'dame_legal_rep_1_profession',
			'dame_legal_rep_1_date_naissance',
			'dame_legal_rep_1_commune_naissance',
			'dame_legal_rep_2_first_name',
			'dame_legal_rep_2_last_name',
			'dame_legal_rep_2_email',
			'dame_legal_rep_2_phone',
			'dame_legal_rep_2_address_1',
			'dame_legal_rep_2_address_2',
			'dame_legal_rep_2_postal_code',
			'dame_legal_rep_2_city',
			'dame_legal_rep_2_profession',
			'dame_legal_rep_2_date_naissance',
			'dame_legal_rep_2_commune_naissance',
			'dame_health_questionnaire',
		);

		foreach ( $fields_to_sanitize as $field ) {
			if ( isset( $raw_data[ $field ] ) ) {
				if ( str_contains( $field, 'email' ) ) {
					$sanitized_data[ $field ] = sanitize_email( wp_unslash( (string) $raw_data[ $field ] ) );
				} else {
					$sanitized_data[ $field ] = sanitize_text_field( wp_unslash( (string) $raw_data[ $field ] ) );
				}
			}
		}

		$sanitized_data['dame_email_refuses_comms']             = isset( $raw_data['dame_refuses_comms'] ) ? '1' : '0';
		$sanitized_data['dame_legal_rep_1_email_refuses_comms'] = isset( $raw_data['dame_legal_rep_1_refuses_comms'] ) ? '1' : '0';
		$sanitized_data['dame_legal_rep_2_email_refuses_comms'] = isset( $raw_data['dame_legal_rep_2_refuses_comms'] ) ? '1' : '0';

		if ( ! empty( $sanitized_data['dame_first_name'] ) ) {
			$sanitized_data['dame_first_name'] = Utils::format_firstname( $sanitized_data['dame_first_name'] );
		}
		if ( ! empty( $sanitized_data['dame_last_name'] ) ) {
			$sanitized_data['dame_last_name'] = Utils::format_lastname( $sanitized_data['dame_last_name'] );
		}
		if ( ! empty( $sanitized_data['dame_birth_name'] ) ) {
			$sanitized_data['dame_birth_name'] = Utils::format_lastname( $sanitized_data['dame_birth_name'] );
		}
		if ( ! empty( $sanitized_data['dame_legal_rep_1_first_name'] ) ) {
			$sanitized_data['dame_legal_rep_1_first_name'] = Utils::format_firstname( $sanitized_data['dame_legal_rep_1_first_name'] );
		}
		if ( ! empty( $sanitized_data['dame_legal_rep_1_last_name'] ) ) {
			$sanitized_data['dame_legal_rep_1_last_name'] = Utils::format_lastname( $sanitized_data['dame_legal_rep_1_last_name'] );
		}
		if ( ! empty( $sanitized_data['dame_legal_rep_2_first_name'] ) ) {
			$sanitized_data['dame_legal_rep_2_first_name'] = Utils::format_firstname( $sanitized_data['dame_legal_rep_2_first_name'] );
		}
		if ( ! empty( $sanitized_data['dame_legal_rep_2_last_name'] ) ) {
			$sanitized_data['dame_legal_rep_2_last_name'] = Utils::format_lastname( $sanitized_data['dame_legal_rep_2_last_name'] );
		}

		return $sanitized_data;
	}

	/**
	 * Saves post meta data in bulk.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $sanitized_data Sanitized data.
	 */
	private function save_metadata( int $post_id, array $sanitized_data ): void {
		global $wpdb;
		$meta_insert_values       = array();
		$meta_insert_placeholders = array();

		foreach ( $sanitized_data as $key => $value ) {
			if ( 'dame_health_questionnaire' === $key ) {
				continue;
			}
			$meta_insert_values[]       = $post_id;
			$meta_insert_values[]       = '_' . $key;
			$meta_insert_values[]       = maybe_serialize( $value );
			$meta_insert_placeholders[] = '(%d, %s, %s)';
		}

		$health_document_status = \DAME\Enums\HealthDocumentStatus::NONE->value;
		if ( isset( $sanitized_data['dame_health_questionnaire'] ) ) {
			if ( 'oui' === $sanitized_data['dame_health_questionnaire'] ) {
				$health_document_status = \DAME\Enums\HealthDocumentStatus::CERTIFICATE->value;
			} elseif ( 'non' === $sanitized_data['dame_health_questionnaire'] ) {
				$health_document_status = \DAME\Enums\HealthDocumentStatus::ATTESTATION->value;
			}
		}

		$meta_insert_values[]       = $post_id;
		$meta_insert_values[]       = '_dame_health_document';
		$meta_insert_values[]       = $health_document_status;
		$meta_insert_placeholders[] = '(%d, %s, %s)';

		if ( isset( $sanitized_data['dame_health_questionnaire'] ) ) {
			$meta_insert_values[]       = $post_id;
			$meta_insert_values[]       = '_dame_health_questionnaire';
			$meta_insert_values[]       = $sanitized_data['dame_health_questionnaire'];
			$meta_insert_placeholders[] = '(%d, %s, %s)';
		}

		$query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ', ', $meta_insert_placeholders );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( $query, $meta_insert_values ) );
	}
}
