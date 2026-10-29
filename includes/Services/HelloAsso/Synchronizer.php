<?php
/**
 * HelloAsso Inscriptions Synchronizer via API v5.
 *
 * @package DAME\Services\HelloAsso
 */

declare(strict_types=1);

namespace DAME\Services\HelloAsso;

use DAME\Repositories\TicketingRepository;
use DAME\Core\Utils;
use WP_Error;

/**
 * Class Synchronizer
 */
class Synchronizer {

	/**
	 * Repository.
	 *
	 * @var TicketingRepository
	 */
	private TicketingRepository $repository;

	/**
	 * Webhook processor (used for normalized ingestion of items).
	 *
	 * @var WebhookProcessor
	 */
	private WebhookProcessor $processor;

	/**
	 * Constructor.
	 *
	 * @param TicketingRepository|null $repository Optional repository.
	 * @param WebhookProcessor|null    $processor  Optional processor.
	 */
	public function __construct( ?TicketingRepository $repository = null, ?WebhookProcessor $processor = null ) {
		$this->repository = $repository ?? new TicketingRepository();
		$this->processor  = $processor ?? new WebhookProcessor( $this->repository );
	}

	/**
	 * Synchronizes all forms/campaigns from HelloAsso for the configured organization.
	 *
	 * @return array{success: bool, campaigns_synced: int, message: string}
	 */
	public function sync_campaigns(): array {
		$cfg = Client::get_config();
		if ( ! Client::is_configured() ) {
			return array(
				'success'          => false,
				'campaigns_synced' => 0,
				'message'          => __( 'Identifiants API HelloAsso non configurés.', 'dame' ),
			);
		}

		$endpoint = sprintf( 'organizations/%s/forms', rawurlencode( $cfg['org_slug'] ) );
		$response = Client::get(
			$endpoint,
			array(
				'pageIndex' => 1,
				'pageSize'  => 50,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'          => false,
				'campaigns_synced' => 0,
				'message'          => $response->get_error_message(),
			);
		}

		$forms = $response['data'] ?? array();
		if ( ! is_array( $forms ) ) {
			return array(
				'success'          => true,
				'campaigns_synced' => 0,
				'message'          => __( 'Aucun formulaire trouvé sur HelloAsso.', 'dame' ),
			);
		}

		$count = 0;
		foreach ( $forms as $form ) {
			$slug = (string) ( $form['formSlug'] ?? '' );
			$type = (string) ( $form['formType'] ?? 'Event' );
			if ( empty( $slug ) ) {
				continue;
			}

			$start_raw  = ! empty( $form['startDate'] ) ? (string) $form['startDate'] : null;
			$end_raw    = ! empty( $form['endDate'] ) ? (string) $form['endDate'] : null;
			$start_date = Utils::normalize_iso_datetime( $start_raw );
			$end_date   = Utils::normalize_iso_datetime( $end_raw );

			$id = $this->repository->get_or_create_campaign(
				$slug,
				$type,
				array(
					'title'        => (string) ( $form['title'] ?? $slug ),
					'start_date'   => $start_date,
					'end_date'     => $end_date,
					'last_sync_at' => current_time( 'mysql' ),
				)
			);
			if ( $id > 0 ) {
				++$count;
			}
		}

		return array(
			'success'          => true,
			'campaigns_synced' => $count,
			'message'          => sprintf(
				/* translators: %d: count */
				__( '%d campagne(s) synchronisée(s) depuis HelloAsso.', 'dame' ),
				$count
			),
		);
	}

	/**
	 * Synchronizes orders and ticket items for a specific campaign.
	 *
	 * @param int $campaign_id Campaign ID in DAME.
	 * @return array{success: bool, items_synced: int, message: string}
	 */
	public function sync_campaign_items( int $campaign_id ): array {
		$campaign = $this->repository->get_campaign( $campaign_id );
		if ( ! $campaign ) {
			return array(
				'success'      => false,
				'items_synced' => 0,
				'message'      => __( 'Campagne introuvable.', 'dame' ),
			);
		}

		$cfg = Client::get_config();
		if ( ! Client::is_configured() ) {
			return array(
				'success'      => false,
				'items_synced' => 0,
				'message'      => __( 'Identifiants API HelloAsso non configurés.', 'dame' ),
			);
		}

		$form_type = rawurlencode( (string) $campaign['form_type'] );
		$form_slug = rawurlencode( (string) $campaign['form_slug'] );

		// Retrieve items for this form via HelloAsso API v5: organizations/{orgSlug}/forms/{formType}/{formSlug}/items
		$endpoint = sprintf( 'organizations/%s/forms/%s/%s/items', rawurlencode( $cfg['org_slug'] ), $form_type, $form_slug );
		$response = Client::get(
			$endpoint,
			array(
				'pageIndex' => 1,
				'pageSize'  => 100,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'      => false,
				'items_synced' => 0,
				'message'      => $response->get_error_message(),
			);
		}

		$items = $response['data'] ?? array();
		if ( ! is_array( $items ) ) {
			return array(
				'success'      => true,
				'items_synced' => 0,
				'message'      => __( 'Aucun billet trouvé pour cette campagne.', 'dame' ),
			);
		}

		// Re-package into order structure expected by WebhookProcessor.
		$fake_order_payload = array(
			'eventType' => 'Order',
			'data'      => array(
				'formSlug' => $campaign['form_slug'],
				'formType' => $campaign['form_type'],
				'formName' => $campaign['title'],
				'items'    => $items,
			),
		);

		$result = $this->processor->process( $fake_order_payload );

		$this->repository->update_campaign(
			$campaign_id,
			array( 'last_sync_at' => current_time( 'mysql' ) )
		);

		return array(
			'success'      => true,
			'items_synced' => $result['processed_items'],
			'message'      => sprintf(
				/* translators: %d: count */
				__( '%d billet(s) synchronisé(s) depuis HelloAsso.', 'dame' ),
				$result['processed_items']
			),
		);
	}
}
