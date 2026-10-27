<?php
/**
 * Billetterie Admin Controller.
 *
 * @package DAME\Admin\Pages
 */

declare(strict_types=1);

namespace DAME\Admin\Pages;

use DAME\Repositories\TicketingRepository;
use DAME\Services\HelloAsso\Synchronizer;
use DAME\Services\HelloAsso\SharlyExporter;
use DAME\Core\Utils;

/**
 * Class Ticketing
 */
class Ticketing {

	/**
	 * Repository.
	 *
	 * @var TicketingRepository
	 */
	private TicketingRepository $repository;

	/**
	 * Synchronizer.
	 *
	 * @var Synchronizer
	 */
	private Synchronizer $synchronizer;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository   = new TicketingRepository();
		$this->synchronizer = new Synchronizer( $this->repository );
	}

	/**
	 * Initialize actions.
	 */
	public function init(): void {
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
	}

	/**
	 * Handles POST actions (sync, checkin, export, onsite add).
	 */
	public function handle_actions(): void {
		if ( ! current_user_can( 'manage_dame_ticketing' ) && ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// 1. Sharly Chess CSV Export.
		if ( isset( $_GET['dame_export_sharly_chess'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'dame_sharly_export_nonce' ) ) {
			$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;
			$only_in     = isset( $_GET['only_checked_in'] ) && '1' === $_GET['only_checked_in'];
			$tier        = isset( $_GET['tier'] ) ? sanitize_text_field( wp_unslash( $_GET['tier'] ) ) : '';

			$filters = array();
			if ( $only_in ) {
				$filters['checked_in'] = true;
			}
			if ( ! empty( $tier ) ) {
				$filters['tier'] = $tier;
			}

			$items    = $this->repository->get_campaign_items( $campaign_id, $filters );
			$csv_data = SharlyExporter::generate_csv( $items );

			$filename = 'sharly_chess_export_' . $campaign_id . ( $only_in ? '_presents' : '' ) . '.csv';

			header( 'Content-Type: text/csv; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $csv_data;
			exit;
		}

		// 2. Synchronize all campaigns.
		if ( isset( $_POST['dame_sync_campaigns_action'], $_POST['dame_ticketing_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_ticketing_nonce'] ) ), 'dame_ticketing_action_nonce' ) ) {
			$res = $this->synchronizer->sync_campaigns();
			set_transient( 'dame_ticketing_notice', $res, 30 );
			wp_safe_redirect( admin_url( 'admin.php?page=dame-ticketing' ) );
			exit;
		}

		// 3. Synchronize items for a specific campaign.
		if ( isset( $_POST['dame_sync_items_action'], $_POST['dame_ticketing_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_ticketing_nonce'] ) ), 'dame_ticketing_action_nonce' ) ) {
			$campaign_id = absint( $_POST['campaign_id'] ?? 0 );
			$res         = $this->synchronizer->sync_campaign_items( $campaign_id );
			set_transient( 'dame_ticketing_notice', $res, 30 );
			wp_safe_redirect( admin_url( 'admin.php?page=dame-ticketing&campaign_id=' . $campaign_id ) );
			exit;
		}

		// 4. Toggle check-in status.
		if ( isset( $_GET['dame_toggle_checkin'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'dame_toggle_checkin_nonce' ) ) {
			$item_id     = absint( $_GET['item_id'] ?? 0 );
			$campaign_id = absint( $_GET['campaign_id'] ?? 0 );
			$this->repository->set_checked_in( $item_id );
			wp_safe_redirect( admin_url( 'admin.php?page=dame-ticketing&campaign_id=' . $campaign_id ) );
			exit;
		}

		// 5. Add participant on site.
		if ( isset( $_POST['dame_add_onsite_participant'], $_POST['dame_ticketing_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_ticketing_nonce'] ) ), 'dame_ticketing_action_nonce' ) ) {
			$campaign_id = absint( $_POST['campaign_id'] ?? 0 );
			$first_name  = sanitize_text_field( wp_unslash( $_POST['participant_first_name'] ?? '' ) );
			$last_name   = sanitize_text_field( wp_unslash( $_POST['participant_last_name'] ?? '' ) );
			$email       = sanitize_email( wp_unslash( $_POST['participant_email'] ?? '' ) );
			$tier        = sanitize_text_field( wp_unslash( $_POST['tier_name'] ?? '' ) );
			$raw_amount  = isset( $_POST['amount'] ) ? sanitize_text_field( wp_unslash( $_POST['amount'] ) ) : '0';
			$amount      = (int) ( ( (float) $raw_amount ) * 100 );
			$method      = sanitize_text_field( wp_unslash( $_POST['payment_method'] ?? 'Espèces' ) );
			$license     = strtoupper( str_replace( ' ', '', sanitize_text_field( wp_unslash( $_POST['license_number'] ?? '' ) ) ) );
			$club        = sanitize_text_field( wp_unslash( $_POST['club_name'] ?? '' ) );
			$elo         = ! empty( $_POST['elo'] ) ? (int) $_POST['elo'] : null;

			if ( ! empty( $last_name ) && ! empty( $first_name ) ) {
				$match = \DAME\Services\HelloAsso\Matcher::match_and_enrich(
					array(
						'first_name'  => $first_name,
						'last_name'   => $last_name,
						'email'       => $email,
						'payer_email' => $email,
						'license'     => $license,
					)
				);

				$this->repository->save_item(
					array(
						'campaign_id'                => $campaign_id,
						'source'                     => 'onsite',
						'status'                     => 'Processed',
						'participant_first_name'     => $first_name,
						'participant_last_name'      => $last_name,
						'participant_email'          => $email,
						'payer_first_name'           => $first_name,
						'payer_last_name'            => $last_name,
						'payer_email'                => $email,
						'tier_name'                  => $tier,
						'amount_cents'               => $amount,
						'payment_method'             => $method,
						'license_number'             => ! empty( $match['enriched_data']['license_number'] ) ? (string) $match['enriched_data']['license_number'] : $license,
						'fide_id'                    => (string) ( $match['enriched_data']['fide_id'] ?? '' ),
						'elo'                        => isset( $match['enriched_data']['elo'] ) ? (int) $match['enriched_data']['elo'] : $elo,
						'club_name'                  => ! empty( $match['enriched_data']['club_name'] ) ? (string) $match['enriched_data']['club_name'] : $club,
						'matched_adherent_id'        => $match['matched_adherent_id'],
						'matched_pre_inscription_id' => $match['matched_pre_inscription_id'],
						'matched_contact_id'         => $match['matched_contact_id'],
						'checked_in'                 => 1, // On site additions are present by default.
						'checked_in_at'              => current_time( 'mysql' ),
					)
				);

				set_transient(
					'dame_ticketing_notice',
					array(
						'success' => true,
						'message' => __( 'Participant sur place ajouté et pointé présent avec succès.', 'dame' ),
					),
					30
				);
			}

			wp_safe_redirect( admin_url( 'admin.php?page=dame-ticketing&campaign_id=' . $campaign_id ) );
			exit;
		}

		// 6. Synchronize external participants to Contacts.
		if ( isset( $_POST['dame_sync_external_contacts'], $_POST['dame_ticketing_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_ticketing_nonce'] ) ), 'dame_ticketing_action_nonce' ) ) {
			$campaign_id = absint( $_POST['campaign_id'] ?? 0 );
			$cat_slug    = sanitize_key( wp_unslash( $_POST['contact_category'] ?? '' ) );

			$items   = $this->repository->get_campaign_items( $campaign_id );
			$created = 0;
			$updated = 0;

			foreach ( $items as $it ) {
				// Skip if matched with club adherent.
				if ( ! empty( $it['matched_adherent_id'] ) ) {
					continue;
				}

				$email = sanitize_email( (string) $it['participant_email'] );
				$last  = (string) $it['participant_last_name'];
				$first = (string) $it['participant_first_name'];
				$org   = (string) ( $it['club_name'] ?? '' );

				if ( empty( $last ) && empty( $first ) ) {
					continue;
				}

				$existing_id = (int) ( $it['matched_contact_id'] ?? 0 );
				if ( $existing_id > 0 ) {
					if ( ! empty( $cat_slug ) ) {
						wp_set_object_terms( $existing_id, $cat_slug, 'dame_contact_type', true );
						++$updated;
					}
				} else {
					$title = trim( Utils::format_lastname( $last ) . ' ' . Utils::format_firstname( $first ) );
					$cid   = wp_insert_post(
						array(
							'post_type'   => 'dame_contact',
							'post_title'  => $title,
							'post_status' => 'publish',
						)
					);
					if ( $cid && ! is_wp_error( $cid ) ) {
						update_post_meta( $cid, '_dame_contact_last_name', Utils::format_lastname( $last ) );
						update_post_meta( $cid, '_dame_contact_first_name', Utils::format_firstname( $first ) );
						update_post_meta( $cid, '_dame_contact_email', $email );
						update_post_meta( $cid, '_dame_contact_organization', $org );
						update_post_meta( $cid, '_dame_contact_role', __( 'Participant tournoi', 'dame' ) );
						update_post_meta( $cid, '_dame_contact_no_emails', '0' );

						if ( ! empty( $cat_slug ) ) {
							wp_set_object_terms( $cid, $cat_slug, 'dame_contact_type', true );
						}
						++$created;
					}
				}
			}

			set_transient(
				'dame_ticketing_notice',
				array(
					'success' => true,
					'message' => sprintf(
						/* translators: 1: created, 2: updated */
						__( 'Synchronisation Contacts terminée : %1$d créés, %2$d mis à jour.', 'dame' ),
						$created,
						$updated
					),
				),
				30
			);

			wp_safe_redirect( admin_url( 'admin.php?page=dame-ticketing&campaign_id=' . $campaign_id ) );
			exit;
		}
	}

	/**
	 * Main render entry point.
	 */
	public function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;

		$notice = get_transient( 'dame_ticketing_notice' );
		if ( is_array( $notice ) ) {
			$class = ! empty( $notice['success'] ) ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( (string) $notice['message'] ) . '</p></div>';
			delete_transient( 'dame_ticketing_notice' );
		}

		if ( $campaign_id > 0 ) {
			$this->render_campaign_detail( $campaign_id );
		} else {
			$this->render_campaigns_list();
		}
	}

	/**
	 * Renders campaigns list table.
	 */
	private function render_campaigns_list(): void {
		$campaigns = $this->repository->get_all_campaigns();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Billetterie & Inscriptions HelloAsso', 'dame' ); ?></h1>
			
			<form method="post" style="display:inline-block; margin-left:10px;">
				<?php wp_nonce_field( 'dame_ticketing_action_nonce', 'dame_ticketing_nonce' ); ?>
				<input type="hidden" name="dame_sync_campaigns_action" value="1" />
				<?php submit_button( __( '↻ Synchroniser les campagnes HelloAsso', 'dame' ), 'secondary', 'submit', false ); ?>
			</form>

			<p class="description" style="margin-top:10px;">
				<?php esc_html_e( 'Retrouvez ici toutes vos campagnes de billetterie, tournois et adhésions synchronisées en temps réel.', 'dame' ); ?>
			</p>

			<table class="wp-list-table widefat fixed striped" style="margin-top:15px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Titre de la campagne', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Type HelloAsso', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Catégorie DAME', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Inscrits / Pointés', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Montant collecté', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Dernière synchro', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'dame' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $campaigns ) ) : ?>
						<tr>
							<td colspan="7">
								<?php esc_html_e( 'Aucune campagne enregistrée. Cliquez sur « Synchroniser les campagnes HelloAsso » ou configurez votre Webhook.', 'dame' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $campaigns as $camp ) : ?>
							<?php
							$stats   = $this->repository->get_campaign_stats( (int) $camp['id'] );
							$det_url = admin_url( 'admin.php?page=dame-ticketing&campaign_id=' . (int) $camp['id'] );
							?>
							<tr>
								<td>
									<strong><a href="<?php echo esc_url( $det_url ); ?>"><?php echo esc_html( (string) $camp['title'] ); ?></a></strong>
									<br><span style="color:#666; font-size:12px;">slug: <?php echo esc_html( (string) $camp['form_slug'] ); ?></span>
								</td>
								<td><?php echo esc_html( (string) $camp['form_type'] ); ?></td>
								<td>
									<span class="badge" style="background:#e0e0e0; padding:3px 8px; border-radius:4px; font-size:12px;">
										<?php echo esc_html( (string) $camp['campaign_category'] ); ?>
									</span>
								</td>
								<td>
									<strong><?php echo esc_html( (string) $stats['total_items'] ); ?></strong>
									<span style="color:#2271b1;"> (<?php echo esc_html( (string) $stats['checked_in'] ); ?> pointés)</span>
								</td>
								<td><strong><?php echo esc_html( number_format( $stats['total_amount_cents'] / 100, 2, ',', ' ' ) ); ?> €</strong></td>
								<td><?php echo esc_html( (string) ( $camp['last_sync_at'] ?? '—' ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( $det_url ); ?>" class="button button-small">
										<?php esc_html_e( 'Gérer les inscrits', 'dame' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Renders detailed campaign view with ticket items and actions.
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	private function render_campaign_detail( int $campaign_id ): void {
		$campaign = $this->repository->get_campaign( $campaign_id );
		if ( ! $campaign ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Campagne introuvable.', 'dame' ) . '</p></div>';
			return;
		}

		$stats = $this->repository->get_campaign_stats( $campaign_id );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter_tier = isset( $_GET['filter_tier'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_tier'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter_checked = isset( $_GET['filter_checked'] ) ? sanitize_key( wp_unslash( $_GET['filter_checked'] ) ) : '';

		$query_filters = array();
		if ( '1' === $filter_checked ) {
			$query_filters['checked_in'] = true;
		} elseif ( '0' === $filter_checked ) {
			$query_filters['checked_in'] = false;
		}
		if ( ! empty( $filter_tier ) ) {
			$query_filters['tier'] = $filter_tier;
		}

		$items = $this->repository->get_campaign_items( $campaign_id, $query_filters );

		$contact_types = get_terms(
			array(
				'taxonomy'   => 'dame_contact_type',
				'hide_empty' => false,
			)
		);
		?>
		<div class="wrap">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dame-ticketing' ) ); ?>" class="button button-secondary" style="margin-bottom:10px;">
				← <?php esc_html_e( 'Retour aux billetteries', 'dame' ); ?>
			</a>

			<h1><?php echo esc_html( (string) $campaign['title'] ); ?></h1>

			<!-- KPI Bar -->
			<div style="display:flex; gap:15px; margin:20px 0;">
				<div style="flex:1; background:#fff; border:1px solid #ccd0d4; padding:15px; border-radius:4px;">
					<span style="font-size:12px; color:#666; text-transform:uppercase;"><?php esc_html_e( 'Inscrits Totaux', 'dame' ); ?></span>
					<h2 style="margin:5px 0 0;"><?php echo esc_html( (string) $stats['total_items'] ); ?></h2>
				</div>
				<div style="flex:1; background:#fff; border:1px solid #ccd0d4; padding:15px; border-radius:4px; border-left:4px solid #46b450;">
					<span style="font-size:12px; color:#666; text-transform:uppercase;"><?php esc_html_e( 'Pointés Présents', 'dame' ); ?></span>
					<h2 style="margin:5px 0 0; color:#46b450;"><?php echo esc_html( (string) $stats['checked_in'] ); ?></h2>
				</div>
				<div style="flex:1; background:#fff; border:1px solid #ccd0d4; padding:15px; border-radius:4px;">
					<span style="font-size:12px; color:#666; text-transform:uppercase;"><?php esc_html_e( 'Total Collecté', 'dame' ); ?></span>
					<h2 style="margin:5px 0 0;"><?php echo esc_html( number_format( $stats['total_amount_cents'] / 100, 2, ',', ' ' ) ); ?> €</h2>
				</div>
			</div>

			<!-- Actions Bar -->
			<div style="background:#fff; border:1px solid #ccd0d4; padding:15px; border-radius:4px; margin-bottom:20px;">
				<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
					
					<!-- Left: Sharly Chess Export -->
					<div>
						<strong><?php esc_html_e( 'Export Sharly Chess :', 'dame' ); ?></strong>
						<?php
						$export_all_url = wp_nonce_url(
							admin_url( 'admin.php?page=dame-ticketing&dame_export_sharly_chess=1&campaign_id=' . $campaign_id . ( ! empty( $filter_tier ) ? '&tier=' . rawurlencode( $filter_tier ) : '' ) ),
							'dame_sharly_export_nonce'
						);
						$export_in_url  = wp_nonce_url(
							admin_url( 'admin.php?page=dame-ticketing&dame_export_sharly_chess=1&only_checked_in=1&campaign_id=' . $campaign_id . ( ! empty( $filter_tier ) ? '&tier=' . rawurlencode( $filter_tier ) : '' ) ),
							'dame_sharly_export_nonce'
						);
						?>
						<a href="<?php echo esc_url( $export_all_url ); ?>" class="button button-primary" style="margin-left:5px;">
							📥 <?php esc_html_e( 'Exporter CSV (Tous)', 'dame' ); ?>
						</a>
						<a href="<?php echo esc_url( $export_in_url ); ?>" class="button button-secondary" style="margin-left:5px;">
							✓ <?php esc_html_e( 'Exporter CSV (Pointés présents uniquement)', 'dame' ); ?>
						</a>
					</div>

					<!-- Right: Resync & Contact Sync -->
					<div style="display:flex; gap:10px;">
						<form method="post" style="display:inline;">
							<?php wp_nonce_field( 'dame_ticketing_action_nonce', 'dame_ticketing_nonce' ); ?>
							<input type="hidden" name="campaign_id" value="<?php echo esc_attr( (string) $campaign_id ); ?>" />
							<input type="hidden" name="dame_sync_items_action" value="1" />
							<?php submit_button( __( '↻ Resynchroniser HelloAsso', 'dame' ), 'secondary', 'submit', false ); ?>
						</form>
					</div>

				</div>

				<hr style="margin:15px 0;">

				<!-- Sync to contacts form -->
				<form method="post" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
					<?php wp_nonce_field( 'dame_ticketing_action_nonce', 'dame_ticketing_nonce' ); ?>
					<input type="hidden" name="campaign_id" value="<?php echo esc_attr( (string) $campaign_id ); ?>" />
					<input type="hidden" name="dame_sync_external_contacts" value="1" />
					<label for="contact_category"><strong><?php esc_html_e( 'Synchroniser les joueurs externes vers Contacts :', 'dame' ); ?></strong></label>
					<select name="contact_category" id="contact_category" required>
						<option value=""><?php esc_html_e( '-- Choisir une catégorie cible --', 'dame' ); ?></option>
						<?php if ( is_array( $contact_types ) ) : ?>
							<?php foreach ( $contact_types as $ct ) : ?>
								<option value="<?php echo esc_attr( $ct->slug ); ?>"><?php echo esc_html( $ct->name ); ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
					<?php submit_button( __( 'Créer/Mettre à jour les Contacts', 'dame' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<!-- Add on-site participant form -->
			<details style="background:#fff; border:1px solid #ccd0d4; padding:15px; border-radius:4px; margin-bottom:20px;">
				<summary style="font-weight:600; cursor:pointer;">➕ <?php esc_html_e( 'Ajouter un inscrit sur place (Espèces / Chèque)', 'dame' ); ?></summary>
				<form method="post" style="margin-top:15px;">
					<?php wp_nonce_field( 'dame_ticketing_action_nonce', 'dame_ticketing_nonce' ); ?>
					<input type="hidden" name="campaign_id" value="<?php echo esc_attr( (string) $campaign_id ); ?>" />
					<input type="hidden" name="dame_add_onsite_participant" value="1" />
					<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:10px;">
						<p>
							<label><strong><?php esc_html_e( 'Nom *', 'dame' ); ?></strong></label><br>
							<input type="text" name="participant_last_name" required style="width:100%;">
						</p>
						<p>
							<label><strong><?php esc_html_e( 'Prénom *', 'dame' ); ?></strong></label><br>
							<input type="text" name="participant_first_name" required style="width:100%;">
						</p>
						<p>
							<label><strong><?php esc_html_e( 'Email', 'dame' ); ?></strong></label><br>
							<input type="email" name="participant_email" style="width:100%;">
						</p>
						<p>
							<label><strong><?php esc_html_e( 'N° Licence FFE', 'dame' ); ?></strong></label><br>
							<input type="text" name="license_number" placeholder="ex: A12345" style="width:100%;">
						</p>
						<p>
							<label><strong><?php esc_html_e( 'Club', 'dame' ); ?></strong></label><br>
							<input type="text" name="club_name" style="width:100%;">
						</p>
						<p>
							<label><strong><?php esc_html_e( 'Elo', 'dame' ); ?></strong></label><br>
							<input type="number" name="elo" style="width:100%;">
						</p>
						<p>
							<label><strong><?php esc_html_e( 'Tarif / Tournoi', 'dame' ); ?></strong></label><br>
							<input type="text" name="tier_name" placeholder="ex: Open A" style="width:100%;">
						</p>
						<p>
							<label><strong><?php esc_html_e( 'Montant (€)', 'dame' ); ?></strong></label><br>
							<input type="number" step="0.5" min="0" name="amount" value="0" style="width:100%;">
						</p>
						<p>
							<label><strong><?php esc_html_e( 'Règlement', 'dame' ); ?></strong></label><br>
							<select name="payment_method" style="width:100%;">
								<option value="Espèces"><?php esc_html_e( 'Espèces', 'dame' ); ?></option>
								<option value="Chèques"><?php esc_html_e( 'Chèques', 'dame' ); ?></option>
								<option value="HelloAsso"><?php esc_html_e( 'HelloAsso (déjà réglé)', 'dame' ); ?></option>
							</select>
						</p>
					</div>
					<p style="margin-top:10px;">
						<?php submit_button( __( 'Valider et Pointer présent', 'dame' ), 'primary', 'submit', false ); ?>
					</p>
				</form>
			</details>

			<!-- Filter Bar -->
			<div style="margin-bottom:15px; display:flex; gap:10px; align-items:center;">
				<span><strong><?php esc_html_e( 'Filtrer :', 'dame' ); ?></strong></span>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dame-ticketing&campaign_id=' . $campaign_id ) ); ?>" class="button <?php echo empty( $filter_tier ) && empty( $filter_checked ) ? 'button-primary' : ''; ?>">
					<?php esc_html_e( 'Tous', 'dame' ); ?> (<?php echo esc_html( (string) $stats['total_items'] ); ?>)
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dame-ticketing&campaign_id=' . $campaign_id . '&filter_checked=1' . ( ! empty( $filter_tier ) ? '&filter_tier=' . rawurlencode( $filter_tier ) : '' ) ) ); ?>" class="button <?php echo '1' === $filter_checked ? 'button-primary' : ''; ?>">
					✓ <?php esc_html_e( 'Pointés uniquement', 'dame' ); ?> (<?php echo esc_html( (string) $stats['checked_in'] ); ?>)
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dame-ticketing&campaign_id=' . $campaign_id . '&filter_checked=0' . ( ! empty( $filter_tier ) ? '&filter_tier=' . rawurlencode( $filter_tier ) : '' ) ) ); ?>" class="button <?php echo '0' === $filter_checked ? 'button-primary' : ''; ?>">
					⏳ <?php esc_html_e( 'En attente de pointage', 'dame' ); ?> (<?php echo esc_html( (string) ( $stats['total_items'] - $stats['checked_in'] ) ); ?>)
				</a>

				<?php if ( ! empty( $stats['tiers'] ) ) : ?>
					<span style="margin-left:15px;"><strong><?php esc_html_e( 'Par Tarif :', 'dame' ); ?></strong></span>
					<?php foreach ( $stats['tiers'] as $t_name => $t_count ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dame-ticketing&campaign_id=' . $campaign_id . '&filter_tier=' . rawurlencode( $t_name ) . ( ! empty( $filter_checked ) ? '&filter_checked=' . $filter_checked : '' ) ) ); ?>" class="button <?php echo $filter_tier === $t_name ? 'button-primary' : ''; ?>">
							<?php echo esc_html( $t_name ); ?> (<?php echo esc_html( (string) $t_count ); ?>)
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<!-- Items List Table -->
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width:80px;"><?php esc_html_e( 'Pointage', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Participant', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Licence FFE', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Elo', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Club', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Tarif / Tournoi', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Payé', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Type', 'dame' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $items ) ) : ?>
						<tr>
							<td colspan="8"><?php esc_html_e( 'Aucun participant ne correspond aux critères sélectionnés.', 'dame' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $items as $it ) : ?>
							<?php
							$checkin_url = wp_nonce_url(
								admin_url( 'admin.php?page=dame-ticketing&dame_toggle_checkin=1&item_id=' . (int) $it['id'] . '&campaign_id=' . $campaign_id ),
								'dame_toggle_checkin_nonce'
							);
							$is_in       = ! empty( $it['checked_in'] );
							$is_adh      = ! empty( $it['matched_adherent_id'] );
							?>
							<tr style="<?php echo $is_in ? 'background-color:#f0f9eb;' : ''; ?>">
								<td>
									<a href="<?php echo esc_url( $checkin_url ); ?>" class="button <?php echo $is_in ? 'button-primary' : ''; ?>" title="<?php esc_attr_e( 'Cliquer pour basculer la présence', 'dame' ); ?>">
										<?php echo $is_in ? '✓ Présent' : 'Absent'; ?>
									</a>
								</td>
								<td>
									<strong><?php echo esc_html( Utils::format_lastname( (string) $it['participant_last_name'] ) . ' ' . Utils::format_firstname( (string) $it['participant_first_name'] ) ); ?></strong>
									<?php if ( ! empty( $it['participant_email'] ) ) : ?>
										<br><span style="color:#666; font-size:12px;"><?php echo esc_html( (string) $it['participant_email'] ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( (string) ( $it['license_number'] ?? '—' ) ); ?></td>
								<td><?php echo esc_html( isset( $it['elo'] ) && (int) $it['elo'] > 0 ? (string) $it['elo'] : '—' ); ?></td>
								<td><?php echo esc_html( (string) ( $it['club_name'] ?? '—' ) ); ?></td>
								<td><?php echo esc_html( (string) ( $it['tier_name'] ?? '—' ) ); ?></td>
								<td>
									<strong><?php echo esc_html( number_format( ( (int) $it['amount_cents'] ) / 100, 2, ',', ' ' ) ); ?> €</strong>
									<br><span style="color:#666; font-size:11px;"><?php echo esc_html( (string) $it['payment_method'] ); ?></span>
								</td>
								<td>
									<?php if ( $is_adh ) : ?>
										<a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $it['matched_adherent_id'] . '&action=edit' ) ); ?>" target="_blank" style="color:#2271b1; font-weight:600;">
											♟ <?php esc_html_e( 'Adhérent Club', 'dame' ); ?>
										</a>
									<?php else : ?>
										<span style="color:#666;"><?php esc_html_e( 'Externe', 'dame' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
