<?php
/**
 * HelloAsso Webhook Notification Processor.
 *
 * @package DAME\Services\HelloAsso
 */

declare(strict_types=1);

namespace DAME\Services\HelloAsso;

use DAME\Repositories\TicketingRepository;

/**
 * Class WebhookProcessor
 */
class WebhookProcessor {

	/**
	 * Ticketing repository instance.
	 *
	 * @var TicketingRepository
	 */
	private TicketingRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param TicketingRepository|null $repository Optional repository.
	 */
	public function __construct( ?TicketingRepository $repository = null ) {
		$this->repository = $repository ?? new TicketingRepository();
	}

	/**
	 * Processes an incoming webhook payload.
	 *
	 * Implements a short transient lock to enforce strict server-side idempotence.
	 *
	 * @param array<string, mixed> $payload Decoded JSON payload.
	 * @return array{success: bool, processed_items: int, message: string}
	 */
	public function process( array $payload ): array {
		$event_type = (string) ( $payload['eventType'] ?? '' );
		$data       = $payload['data'] ?? array();

		if ( ! is_array( $data ) || empty( $data ) ) {
			return array(
				'success'         => false,
				'processed_items' => 0,
				'message'         => __( 'Données de webhook invalides.', 'dame' ),
			);
		}

		// Calculate fingerprint lock for idempotence (15 seconds lock).
		$payload_fingerprint = 'dame_ha_lock_' . md5( (string) wp_json_encode( $payload ) );
		if ( get_transient( $payload_fingerprint ) ) {
			return array(
				'success'         => true,
				'processed_items' => 0,
				'message'         => __( 'Événement déjà en cours de traitement (idempotence).', 'dame' ),
			);
		}
		set_transient( $payload_fingerprint, '1', 15 );

		$processed = 0;

		switch ( $event_type ) {
			case 'Order':
				$processed = $this->process_order_event( $data );
				break;

			case 'Payment':
				$processed = $this->process_payment_event( $data );
				break;

			case 'Form':
				$this->process_form_event( $data );
				break;

			default:
				// Fallback: check if items or payer are present in generic payload.
				if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
					$processed = $this->process_order_event( $data );
				}
				break;
		}

		return array(
			'success'         => true,
			'processed_items' => $processed,
			'message'         => sprintf(
				/* translators: %d: items count */
				__( '%d billet(s) traité(s) avec succès.', 'dame' ),
				$processed
			),
		);
	}

	/**
	 * Processes a Form update event to keep campaign metadata up to date.
	 *
	 * @param array<string, mixed> $form Form data.
	 */
	private function process_form_event( array $form ): void {
		$slug = (string) ( $form['formSlug'] ?? '' );
		$type = (string) ( $form['formType'] ?? 'Event' );

		if ( empty( $slug ) ) {
			return;
		}

		$this->repository->get_or_create_campaign(
			$slug,
			$type,
			array(
				'title'        => (string) ( $form['title'] ?? $slug ),
				'start_date'   => $form['startDate'] ?? null,
				'end_date'     => $form['endDate'] ?? null,
				'last_sync_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Processes an Order event containing items (tickets).
	 *
	 * @param array<string, mixed> $order Order data.
	 * @return int Number of processed items.
	 */
	private function process_order_event( array $order ): int {
		$form_slug = (string) ( $order['formSlug'] ?? '' );
		$form_type = (string) ( $order['formType'] ?? 'Event' );

		if ( empty( $form_slug ) ) {
			return 0;
		}

		// Ensure campaign exists.
		$campaign_id = $this->repository->get_or_create_campaign(
			$form_slug,
			$form_type,
			array(
				'title'        => (string) ( $order['formName'] ?? $form_slug ),
				'last_sync_at' => current_time( 'mysql' ),
			)
		);

		if ( $campaign_id <= 0 ) {
			return 0;
		}

		$payer        = is_array( $order['payer'] ?? null ) ? $order['payer'] : array();
		$payer_first  = (string) ( $payer['firstName'] ?? '' );
		$payer_last   = (string) ( $payer['lastName'] ?? '' );
		$payer_email  = (string) ( $payer['email'] ?? '' );
		$order_id     = isset( $order['id'] ) ? (int) $order['id'] : null;
		$order_date   = isset( $order['date'] ) ? sanitize_text_field( (string) $order['date'] ) : current_time( 'mysql' );
		$order_status = (string) ( $order['status'] ?? 'Processed' );

		$items = is_array( $order['items'] ?? null ) ? $order['items'] : array();
		$count = 0;

		foreach ( $items as $raw_item ) {
			if ( ! is_array( $raw_item ) ) {
				continue;
			}

			$ha_item_id = isset( $raw_item['id'] ) ? (int) $raw_item['id'] : null;

			// Participant details (fallback to payer if empty).
			$part_first = trim( (string) ( $raw_item['user']['firstName'] ?? $payer_first ) );
			$part_last  = trim( (string) ( $raw_item['user']['lastName'] ?? $payer_last ) );
			$part_email = trim( (string) ( $raw_item['user']['email'] ?? $payer_email ) );

			// Extract custom chess fields.
			$custom_fields = $raw_item['customFields'] ?? array();
			$chess_data    = FieldExtractor::extract_chess_data( $custom_fields );

			// Multi-criteria matching with Adherents, PreInscriptions and Contacts.
			$match = Matcher::match_and_enrich(
				array(
					'first_name'  => $part_first,
					'last_name'   => $part_last,
					'email'       => $part_email,
					'payer_email' => $payer_email,
					'license'     => $chess_data['license_number'],
				)
			);

			// Merge enriched adherent data if matched.
			$final_license   = ! empty( $match['enriched_data']['license_number'] ) ? (string) $match['enriched_data']['license_number'] : $chess_data['license_number'];
			$final_fide_id   = ! empty( $match['enriched_data']['fide_id'] ) ? (string) $match['enriched_data']['fide_id'] : $chess_data['fide_id'];
			$final_club_name = ! empty( $match['enriched_data']['club_name'] ) ? (string) $match['enriched_data']['club_name'] : $chess_data['club_name'];
			$final_elo       = isset( $match['enriched_data']['elo'] ) ? (int) $match['enriched_data']['elo'] : $chess_data['elo'];
			$final_birth     = ! empty( $match['enriched_data']['participant_birth_date'] ) ? (string) $match['enriched_data']['participant_birth_date'] : $chess_data['birth_date'];
			$final_phone     = ! empty( $match['enriched_data']['phone'] ) ? (string) $match['enriched_data']['phone'] : $chess_data['phone'];

			$amount_cents = isset( $raw_item['amount'] ) ? (int) $raw_item['amount'] : ( isset( $raw_item['price'] ) ? (int) $raw_item['price'] : 0 );

			$item_record = array(
				'campaign_id'                => $campaign_id,
				'ha_item_id'                 => $ha_item_id,
				'ha_order_id'                => $order_id,
				'source'                     => 'helloasso',
				'status'                     => $order_status,
				'participant_first_name'     => $part_first,
				'participant_last_name'      => $part_last,
				'participant_email'          => $part_email,
				'participant_birth_date'     => $final_birth,
				'payer_first_name'           => $payer_first,
				'payer_last_name'            => $payer_last,
				'payer_email'                => $payer_email,
				'tier_name'                  => (string) ( $raw_item['name'] ?? '' ),
				'amount_cents'               => $amount_cents,
				'payment_method'             => 'HelloAsso',
				'order_date'                 => $order_date,
				'license_number'             => $final_license,
				'fide_id'                    => $final_fide_id,
				'elo'                        => $final_elo,
				'club_name'                  => $final_club_name,
				'phone'                      => $final_phone,
				'custom_fields_json'         => $raw_item['customFields'] ?? null,
				'matched_adherent_id'        => $match['matched_adherent_id'],
				'matched_pre_inscription_id' => $match['matched_pre_inscription_id'],
				'matched_contact_id'         => $match['matched_contact_id'],
			);

			$saved_id = $this->repository->save_item( $item_record );
			if ( $saved_id > 0 ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Processes a single Payment event.
	 *
	 * @param array<string, mixed> $payment Payment data.
	 * @return int Number of processed items.
	 */
	private function process_payment_event( array $payment ): int {
		// If payment payload contains order data, delegate to order event.
		if ( isset( $payment['order'] ) && is_array( $payment['order'] ) ) {
			return $this->process_order_event( $payment['order'] );
		}

		return 0;
	}
}
