<?php
/**
 * HelloAsso Ticketing Inscriptions Metabox for Agenda events.
 *
 * @package DAME\Metaboxes\Agenda
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Agenda;

use WP_Post;
use DAME\Repositories\TicketingRepository;
use DAME\Core\Utils;

/**
 * Class TicketingMetabox
 */
class TicketingMetabox {

	/**
	 * Repository.
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
	 * Renders the metabox.
	 *
	 * @param WP_Post $post Current event post.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'dame_agenda_ticketing_nonce_action', 'dame_agenda_ticketing_nonce' );

		$linked_campaign_id = (int) get_post_meta( $post->ID, '_dame_linked_ha_campaign_id', true );
		$all_campaigns      = $this->repository->get_all_campaigns();
		?>
		<div class="dame-agenda-ticketing-wrapper">
			<p>
				<label for="dame_linked_ha_campaign_id"><strong><?php esc_html_e( 'Campagne HelloAsso / Billetterie liée :', 'dame' ); ?></strong></label><br>
				<select name="dame_linked_ha_campaign_id" id="dame_linked_ha_campaign_id" style="width: 100%; max-width: 400px; margin-top: 5px;">
					<option value="0"><?php esc_html_e( '-- Aucune billetterie liée --', 'dame' ); ?></option>
					<?php foreach ( $all_campaigns as $camp ) : ?>
						<option value="<?php echo esc_attr( (string) $camp['id'] ); ?>" <?php selected( (int) $camp['id'], $linked_campaign_id ); ?>>
							<?php echo esc_html( (string) $camp['title'] . ' (' . (string) $camp['form_type'] . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<?php if ( $linked_campaign_id > 0 ) : ?>
				<?php
				$campaign = $this->repository->get_campaign( $linked_campaign_id );
				$stats    = $this->repository->get_campaign_stats( $linked_campaign_id );
				$items    = $this->repository->get_campaign_items( $linked_campaign_id );
				?>
				<hr style="margin: 15px 0;">

				<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
					<div>
						<strong><?php esc_html_e( 'Inscrits Billetterie / Stage :', 'dame' ); ?></strong>
						<span style="margin-left: 10px; color: #2271b1; font-weight:600;">
							<?php
							printf(
								/* translators: 1: total items, 2: checked in */
								esc_html__( '%1$d inscrits (%2$d pointés)', 'dame' ),
								(int) $stats['total_items'],
								(int) $stats['checked_in']
							);
							?>
						</span>
					</div>
					<div>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dame-ticketing&campaign_id=' . $linked_campaign_id ) ); ?>" target="_blank" class="button button-secondary">
							<?php esc_html_e( 'Ouvrir dans la Billetterie ↗', 'dame' ); ?>
						</a>
					</div>
				</div>

				<?php if ( empty( $items ) ) : ?>
					<p style="color: #666; font-style: italic;"><?php esc_html_e( 'Aucun inscrit pour le moment sur cette campagne.', 'dame' ); ?></p>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
						<thead>
							<tr>
								<th style="width: 70px;"><?php esc_html_e( 'Présent', 'dame' ); ?></th>
								<th><?php esc_html_e( 'Participant', 'dame' ); ?></th>
								<th><?php esc_html_e( 'Club / Origine', 'dame' ); ?></th>
								<th><?php esc_html_e( 'Licence FFE', 'dame' ); ?></th>
								<th><?php esc_html_e( 'Tarif / Formule', 'dame' ); ?></th>
								<th><?php esc_html_e( 'Statut Club', 'dame' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $items as $it ) : ?>
								<?php
								$is_in  = ! empty( $it['checked_in'] );
								$is_adh = ! empty( $it['matched_adherent_id'] );
								?>
								<tr style="<?php echo $is_in ? 'background-color:#f0f9eb;' : ''; ?>">
									<td>
										<?php if ( $is_in ) : ?>
											<span style="color: #46b450; font-weight: bold;">✓ OUI</span>
										<?php else : ?>
											<span style="color: #999;">NON</span>
										<?php endif; ?>
									</td>
									<td>
										<strong><?php echo esc_html( Utils::format_lastname( (string) $it['participant_last_name'] ) . ' ' . Utils::format_firstname( (string) $it['participant_first_name'] ) ); ?></strong>
										<?php if ( ! empty( $it['participant_email'] ) ) : ?>
											<br><span style="color: #666; font-size: 11px;"><?php echo esc_html( (string) $it['participant_email'] ); ?></span>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( (string) ( $it['club_name'] ?? '—' ) ); ?></td>
									<td><?php echo esc_html( (string) ( $it['license_number'] ?? '—' ) ); ?></td>
									<td><?php echo esc_html( (string) ( $it['tier_name'] ?? '—' ) ); ?></td>
									<td>
										<?php if ( $is_adh ) : ?>
											<span style="color: #2271b1; font-weight:600;">♟ <?php esc_html_e( 'Adhérent Club', 'dame' ); ?></span>
										<?php else : ?>
											<span style="color: #777;"><?php esc_html_e( 'Externe / Ligue', 'dame' ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Saves the linked campaign ID on agenda event save.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( int $post_id ): void {
		$nonce = isset( $_POST['dame_agenda_ticketing_nonce'] ) ? sanitize_key( wp_unslash( $_POST['dame_agenda_ticketing_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dame_agenda_ticketing_nonce_action' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['dame_linked_ha_campaign_id'] ) ) {
			$campaign_id = absint( $_POST['dame_linked_ha_campaign_id'] );
			if ( $campaign_id > 0 ) {
				update_post_meta( $post_id, '_dame_linked_ha_campaign_id', $campaign_id );
				$this->repository->update_campaign( $campaign_id, array( 'linked_agenda_id' => $post_id ) );
			} else {
				$old_id = (int) get_post_meta( $post_id, '_dame_linked_ha_campaign_id', true );
				delete_post_meta( $post_id, '_dame_linked_ha_campaign_id' );
				if ( $old_id > 0 ) {
					$this->repository->update_campaign( $old_id, array( 'linked_agenda_id' => null ) );
				}
			}
		}
	}
}

