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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_dame_toggle_checkin_ajax', array( $this, 'handle_ajax_toggle_checkin' ) );
	}

	/**
	 * Enqueues assets specifically on the ticketing administration page.
	 *
	 * @param string $hook The current admin hook.
	 */
	public function enqueue_scripts( string $hook ): void {
		if ( ! str_contains( $hook, 'dame-ticketing' ) ) {
			return;
		}

		wp_enqueue_style( 'dame-admin-styles' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;
		if ( $campaign_id > 0 ) {
			wp_enqueue_script(
				'dame-admin-ticketing',
				DAME_PLUGIN_URL . 'assets/js/admin-ticketing.js',
				array(),
				DAME_VERSION,
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);

			$stats = $this->repository->get_campaign_stats( $campaign_id );
			wp_localize_script(
				'dame-admin-ticketing',
				'dame_ticketing_data',
				array(
					'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
					'nonce'          => wp_create_nonce( 'dame_ticketing_ajax_nonce' ),
					'campaignId'     => $campaign_id,
					'totalItems'     => (int) $stats['total_items'],
					'checkedInCount' => (int) $stats['checked_in'],
				)
			);
		}
	}

	/**
	 * Handles AJAX check-in toggle.
	 */
	public function handle_ajax_toggle_checkin(): void {
		check_ajax_referer( 'dame_ticketing_ajax_nonce', '_ajax_nonce' );

		if ( ! current_user_can( 'manage_dame_ticketing' ) && ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Droits insuffisants.', 'dame' ) ), 403 );
		}

		$item_id     = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

		if ( $item_id <= 0 || $campaign_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Paramètres invalides.', 'dame' ) ), 400 );
		}

		$success = $this->repository->set_checked_in( $item_id );
		if ( ! $success ) {
			wp_send_json_error( array( 'message' => __( 'Erreur lors de la mise à jour du pointage.', 'dame' ) ), 500 );
		}

		$stats = $this->repository->get_campaign_stats( $campaign_id );
		$item  = $this->repository->get_item( $item_id );
		$is_in = is_array( $item ) && ! empty( $item['checked_in'] ) ? 1 : 0;

		wp_send_json_success(
			array(
				'item_id'          => $item_id,
				'checked_in'       => $is_in,
				'checked_in_count' => (int) $stats['checked_in'],
				'total_items'      => (int) $stats['total_items'],
			)
		);
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

			// Support multiple tiers passed as array (e.g. tiers[]=Open+A&tiers[]=Open+B).
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( isset( $_GET['tiers'] ) && is_array( $_GET['tiers'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$raw_tiers       = (array) wp_unslash( $_GET['tiers'] );
				$sanitized_tiers = array();
				foreach ( $raw_tiers as $t ) {
					$sanitized_tiers[] = sanitize_text_field( (string) $t );
				}
				if ( ! empty( $sanitized_tiers ) ) {
					$filters['tiers'] = $sanitized_tiers;
				}
			} elseif ( ! empty( $tier ) ) {
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
			$new_cat     = sanitize_text_field( wp_unslash( $_POST['new_contact_category'] ?? '' ) );

			// Create category on the fly if provided.
			if ( ! empty( $new_cat ) ) {
				$term_exists = term_exists( $new_cat, 'dame_contact_type' );
				if ( ! $term_exists ) {
					$new_term = wp_insert_term( $new_cat, 'dame_contact_type' );
					if ( ! is_wp_error( $new_term ) && isset( $new_term['term_id'] ) ) {
						$t_obj = get_term( (int) $new_term['term_id'], 'dame_contact_type' );
						if ( $t_obj instanceof \WP_Term ) {
							$cat_slug = $t_obj->slug;
						}
					}
				} elseif ( is_array( $term_exists ) && isset( $term_exists['term_id'] ) ) {
					$t_obj = get_term( (int) $term_exists['term_id'], 'dame_contact_type' );
					if ( $t_obj instanceof \WP_Term ) {
						$cat_slug = $t_obj->slug;
					}
				}
			}

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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_type = isset( $_GET['form_type'] ) ? sanitize_text_field( wp_unslash( $_GET['form_type'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'start_date';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_order = isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) ? 'asc' : 'desc';

		$filters = array(
			'form_type' => $current_type,
			'orderby'   => $current_orderby,
			'order'     => $current_order,
		);

		$campaigns   = $this->repository->get_all_campaigns( $filters );
		$type_counts = $this->repository->get_campaign_type_counts();

		// Helper to build sort URL.
		$build_sort_url = static function ( string $column ) use ( $current_type, $current_orderby, $current_order ): string {
			$next_order = ( $current_orderby === $column && 'asc' === $current_order ) ? 'desc' : 'asc';
			$args       = array(
				'page'    => 'dame-ticketing',
				'orderby' => $column,
				'order'   => $next_order,
			);
			if ( ! empty( $current_type ) ) {
				$args['form_type'] = $current_type;
			}
			return add_query_arg( $args, admin_url( 'admin.php' ) );
		};

		// Helper to render sort indicator.
		$sort_indicator = static function ( string $column ) use ( $current_orderby, $current_order ): string {
			if ( $current_orderby !== $column ) {
				return '';
			}
			return 'asc' === $current_order ? ' ▲' : ' ▼';
		};

		$base_url = admin_url( 'admin.php?page=dame-ticketing' );
		if ( 'start_date' !== $current_orderby || 'desc' !== $current_order ) {
			$base_url = add_query_arg(
				array(
					'orderby' => $current_orderby,
					'order'   => $current_order,
				),
				$base_url
			);
		}

		$types_config = array(
			'all'        => array(
				'label' => __( 'Tous', 'dame' ),
				'count' => $type_counts['all'] ?? 0,
			),
			'Event'      => array(
				'label' => __( 'Événements', 'dame' ),
				'count' => $type_counts['Event'] ?? 0,
			),
			'Membership' => array(
				'label' => __( 'Adhésions', 'dame' ),
				'count' => $type_counts['Membership'] ?? 0,
			),
			'Donation'   => array(
				'label' => __( 'Dons', 'dame' ),
				'count' => $type_counts['Donation'] ?? 0,
			),
			'Shop'       => array(
				'label' => __( 'Boutique', 'dame' ),
				'count' => $type_counts['Shop'] ?? 0,
			),
		);

		if ( ! empty( $type_counts['Checkout'] ) ) {
			$types_config['Checkout'] = array(
				'label' => __( 'Paiement direct', 'dame' ),
				'count' => $type_counts['Checkout'],
			);
		}
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

			<ul class="subsubsub" style="margin-top:10px; margin-bottom:10px;">
				<?php
				$links = array();
				foreach ( $types_config as $key => $cfg ) {
					if ( 'all' !== $key && 0 === $cfg['count'] ) {
						continue;
					}
					$is_current = ( 'all' === $key && empty( $current_type ) ) || ( $current_type === $key );
					$url        = ( 'all' === $key ) ? remove_query_arg( 'form_type', $base_url ) : add_query_arg( 'form_type', $key, $base_url );
					$class      = $is_current ? 'current' : '';
					$links[]    = sprintf(
						'<li class="%s"><a href="%s" class="%s">%s <span class="count">(%d)</span></a></li>',
						esc_attr( strtolower( $key ) ),
						esc_url( $url ),
						esc_attr( $class ),
						esc_html( $cfg['label'] ),
						(int) $cfg['count']
					);
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo implode( ' | ', $links );
				?>
			</ul>

			<table class="wp-list-table widefat fixed striped" style="margin-top:10px;">
				<thead>
					<tr>
						<th><a href="<?php echo esc_url( $build_sort_url( 'title' ) ); ?>"><strong><?php esc_html_e( 'Titre de la campagne', 'dame' ); ?><?php echo esc_html( $sort_indicator( 'title' ) ); ?></strong></a></th>
						<th style="width:110px;"><a href="<?php echo esc_url( $build_sort_url( 'form_type' ) ); ?>"><strong><?php esc_html_e( 'Type HelloAsso', 'dame' ); ?><?php echo esc_html( $sort_indicator( 'form_type' ) ); ?></strong></a></th>
						<th style="width:170px;"><a href="<?php echo esc_url( $build_sort_url( 'start_date' ) ); ?>"><strong><?php esc_html_e( 'Dates / Période', 'dame' ); ?><?php echo esc_html( $sort_indicator( 'start_date' ) ); ?></strong></a></th>
						<th style="width:110px;"><?php esc_html_e( 'Catégorie DAME', 'dame' ); ?></th>
						<th style="width:130px;"><?php esc_html_e( 'Inscrits / Pointés', 'dame' ); ?></th>
						<th style="width:120px;"><?php esc_html_e( 'Montant collecté', 'dame' ); ?></th>
						<th style="width:140px;"><?php esc_html_e( 'Dernière synchro', 'dame' ); ?></th>
						<th style="width:120px;"><?php esc_html_e( 'Actions', 'dame' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $campaigns ) ) : ?>
						<tr>
							<td colspan="8">
								<?php esc_html_e( 'Aucune campagne enregistrée pour ce filtre. Cliquez sur « Synchroniser les campagnes HelloAsso » ou réinitialisez les filtres.', 'dame' ); ?>
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
									<?php
									$start_raw = ! empty( $camp['start_date'] ) ? (string) $camp['start_date'] : '';
									$end_raw   = ! empty( $camp['end_date'] ) ? (string) $camp['end_date'] : '';
									$start_ts  = ! empty( $start_raw ) ? strtotime( $start_raw ) : false;
									$end_ts    = ! empty( $end_raw ) ? strtotime( $end_raw ) : false;

									if ( false !== $start_ts ) {
										if ( false !== $end_ts && gmdate( 'Y-m-d', $start_ts ) !== gmdate( 'Y-m-d', $end_ts ) ) {
											echo esc_html(
												sprintf(
													/* translators: 1: start date, 2: end date */
													__( 'Du %1$s au %2$s', 'dame' ),
													wp_date( 'd/m/Y', $start_ts ),
													wp_date( 'd/m/Y', $end_ts )
												)
											);
										} else {
											echo esc_html(
												sprintf(
													/* translators: %s: date */
													__( 'Le %s', 'dame' ),
													wp_date( 'd/m/Y', $start_ts )
												)
											);
										}
									} else {
										echo '<span style="color:#888;">—</span>';
									}
									?>
								</td>
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
		$items = $this->repository->get_campaign_items( $campaign_id );

		$contact_types = get_terms(
			array(
				'taxonomy'   => 'dame_contact_type',
				'hide_empty' => false,
			)
		);

		$total_items    = (int) $stats['total_items'];
		$checked_in     = (int) $stats['checked_in'];
		$waiting        = $total_items - $checked_in;
		$pct_checked_in = $total_items > 0 ? (int) round( ( $checked_in / $total_items ) * 100 ) : 0;
		$total_amount   = number_format( $stats['total_amount_cents'] / 100, 2, ',', ' ' );

		$sharly_nonce = wp_create_nonce( 'dame_sharly_export_nonce' );
		?>
		<div class="wrap dame-ticketing-wrap">
			<!-- Hidden nonce for Sharly dynamic URL building -->
			<input type="hidden" id="dame-sharly-nonce" value="<?php echo esc_attr( $sharly_nonce ); ?>" />

			<div style="display:flex; align-items:center; justify-content:space-between; margin-top:10px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dame-ticketing' ) ); ?>" class="button button-secondary">
					← <?php esc_html_e( 'Retour aux billetteries', 'dame' ); ?>
				</a>
				<span class="badge" style="background:#e0e0e0; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">
					<?php echo esc_html( (string) $campaign['campaign_category'] ); ?>
				</span>
			</div>

			<h1 style="margin:12px 0 6px 0;"><?php echo esc_html( (string) $campaign['title'] ); ?></h1>

			<!-- Compact KPI Bar -->
			<div class="dame-ticketing-kpi-bar">
				<div class="dame-kpi-item">
					<span class="dashicons dashicons-groups"></span>
					<span><?php esc_html_e( 'Inscrits :', 'dame' ); ?> <strong><?php echo esc_html( (string) $total_items ); ?></strong></span>
				</div>
				<span class="dame-kpi-separator">•</span>
				<div class="dame-kpi-item dame-kpi-item--success">
					<span class="dashicons dashicons-yes-alt"></span>
					<span><?php esc_html_e( 'Pointés :', 'dame' ); ?> <strong id="dame-kpi-checked-in"><?php echo esc_html( "{$checked_in} ({$pct_checked_in}%)" ); ?></strong></span>
				</div>
				<span class="dame-kpi-separator">•</span>
				<div class="dame-kpi-item">
					<span class="dashicons dashicons-money-alt"></span>
					<span><?php esc_html_e( 'Collecté :', 'dame' ); ?> <strong><?php echo esc_html( "{$total_amount} €" ); ?></strong></span>
				</div>
				<span class="dame-kpi-separator">•</span>
				<div class="dame-kpi-item dame-kpi-item--highlight">
					<span><?php esc_html_e( 'Affichage :', 'dame' ); ?> <strong id="dame-kpi-visible-count"><?php echo esc_html( (string) count( $items ) ); ?></strong> / <?php echo esc_html( (string) $total_items ); ?></span>
				</div>

				<div class="dame-kpi-actions">
					<button type="button" class="button button-primary" id="dame-open-onsite-modal">
						➕ <?php esc_html_e( 'Inscription sur place', 'dame' ); ?>
					</button>

					<!-- Tools Menu -->
					<div class="dame-action-dropdown">
						<button type="button" class="button button-secondary" id="dame-tools-trigger">
							⚙️ <?php esc_html_e( 'Outils', 'dame' ); ?> <span class="dashicons dashicons-arrow-down-alt2" style="font-size:14px; width:14px; height:14px; margin-left:2px;"></span>
						</button>
						<div class="dame-action-menu" id="dame-tools-menu">
							<form method="post" style="margin:0;">
								<?php wp_nonce_field( 'dame_ticketing_action_nonce', 'dame_ticketing_nonce' ); ?>
								<input type="hidden" name="campaign_id" value="<?php echo esc_attr( (string) $campaign_id ); ?>" />
								<input type="hidden" name="dame_sync_items_action" value="1" />
								<button type="submit">
									<span class="dashicons dashicons-update"></span>
									<?php esc_html_e( 'Resynchroniser HelloAsso', 'dame' ); ?>
								</button>
							</form>
							<div class="dame-menu-divider"></div>
							<button type="button" id="dame-open-contacts-modal">
								<span class="dashicons dashicons-admin-users"></span>
								<?php esc_html_e( 'Synchroniser vers Contacts...', 'dame' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Unified Filter & Export Toolbar -->
			<div class="dame-ticketing-toolbar">
				<div class="dame-toolbar-left">
					<!-- Presence status toggle button group -->
					<div class="dame-status-filter-group">
						<button type="button" class="dame-status-btn active" data-status="all">
							<?php esc_html_e( 'Tous', 'dame' ); ?> <span class="count">(<?php echo esc_html( (string) $total_items ); ?>)</span>
						</button>
						<button type="button" class="dame-status-btn" data-status="1">
							✓ <?php esc_html_e( 'Pointés', 'dame' ); ?> <span class="count">(<?php echo esc_html( (string) $checked_in ); ?>)</span>
						</button>
						<button type="button" class="dame-status-btn" data-status="0">
							⏳ <?php esc_html_e( 'En attente', 'dame' ); ?> <span class="count">(<?php echo esc_html( (string) $waiting ); ?>)</span>
						</button>
					</div>

					<!-- Multi-select Tier Dropdown -->
					<?php if ( ! empty( $stats['tiers'] ) ) : ?>
						<div class="dame-tier-dropdown-wrapper">
							<button type="button" class="dame-dropdown-trigger" id="dame-tier-dropdown-trigger">
								<span class="dashicons dashicons-tag"></span>
								<span><?php esc_html_e( 'Tarifs / Tournois', 'dame' ); ?></span>
								<span class="badge" id="dame-tier-badge"><?php esc_html_e( 'Tous', 'dame' ); ?></span>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</button>

							<div class="dame-dropdown-menu" id="dame-tier-dropdown-menu">
								<div class="dame-dropdown-search">
									<input type="text" id="dame-tier-search" placeholder="<?php esc_attr_e( 'Filtrer les tarifs...', 'dame' ); ?>">
								</div>
								<div class="dame-dropdown-actions">
									<button type="button" id="dame-select-all-tiers"><?php esc_html_e( 'Tout sélectionner', 'dame' ); ?></button>
									<button type="button" id="dame-clear-all-tiers"><?php esc_html_e( 'Tout effacer', 'dame' ); ?></button>
								</div>
								<div class="dame-dropdown-list">
									<?php foreach ( $stats['tiers'] as $t_name => $t_count ) : ?>
										<label>
											<input type="checkbox" class="dame-tier-checkbox" value="<?php echo esc_attr( $t_name ); ?>" checked>
											<span class="tier-title" title="<?php echo esc_attr( $t_name ); ?>"><?php echo esc_html( $t_name ); ?></span>
											<span class="tier-count">(<?php echo esc_html( (string) $t_count ); ?>)</span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					<?php endif; ?>

					<!-- Quick Search Input -->
					<div class="dame-search-box">
						<span class="dashicons dashicons-search"></span>
						<input type="search" id="dame-ticketing-search" placeholder="<?php esc_attr_e( 'Rechercher nom, licence, club...', 'dame' ); ?>" autocomplete="off" autocorrect="off">
					</div>
				</div>

				<div class="dame-toolbar-right">
					<!-- Sharly Chess Combined Export -->
					<div class="dame-action-dropdown">
						<button type="button" class="button button-primary" id="dame-export-trigger">
							📥 <?php esc_html_e( 'Exporter Sharly', 'dame' ); ?> (<?php echo esc_html( (string) count( $items ) ); ?>)
							<span class="dashicons dashicons-arrow-down-alt2" style="font-size:14px; width:14px; height:14px; margin-left:4px;"></span>
						</button>
						<div class="dame-action-menu" id="dame-export-menu">
							<a href="#" id="dame-export-filtered">
								<span class="dashicons dashicons-filter"></span>
								<?php esc_html_e( 'Exporter la sélection filtrée', 'dame' ); ?>
							</a>
							<a href="#" id="dame-export-checked">
								<span class="dashicons dashicons-yes-alt"></span>
								<?php esc_html_e( 'Exporter les pointés filtrés', 'dame' ); ?>
							</a>
							<div class="dame-menu-divider"></div>
							<a href="#" id="dame-export-all">
								<span class="dashicons dashicons-admin-site"></span>
								<?php esc_html_e( "Exporter l'intégralité (Tous)", 'dame' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Items List Table -->
			<table class="wp-list-table widefat fixed striped" id="dame-ticketing-table">
				<thead>
					<tr>
						<th style="width:85px;" class="sortable" data-sort="checkin">
							<?php esc_html_e( 'Pointage', 'dame' ); ?><span class="sort-indicator"></span>
						</th>
						<th class="sortable" data-sort="participant">
							<?php esc_html_e( 'Participant', 'dame' ); ?><span class="sort-indicator"> ▲</span>
						</th>
						<th style="width:110px;">
							<?php esc_html_e( 'Licence FFE', 'dame' ); ?>
						</th>
						<th style="width:75px;" class="sortable" data-sort="elo">
							<?php esc_html_e( 'Elo', 'dame' ); ?><span class="sort-indicator"></span>
						</th>
						<th class="sortable" data-sort="club">
							<?php esc_html_e( 'Club', 'dame' ); ?><span class="sort-indicator"></span>
						</th>
						<th class="sortable" data-sort="tier">
							<?php esc_html_e( 'Tarif / Tournoi', 'dame' ); ?><span class="sort-indicator"></span>
						</th>
						<th style="width:110px;" class="sortable" data-sort="amount">
							<?php esc_html_e( 'Payé', 'dame' ); ?><span class="sort-indicator"></span>
						</th>
						<th style="width:105px;">
							<?php esc_html_e( 'Type', 'dame' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $items ) ) : ?>
						<tr>
							<td colspan="8"><?php esc_html_e( 'Aucun participant synchronisé pour le moment.', 'dame' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $items as $it ) : ?>
							<?php
							$is_in        = ! empty( $it['checked_in'] );
							$is_adh       = ! empty( $it['matched_adherent_id'] );
							$tier_val     = ! empty( $it['tier_name'] ) ? (string) $it['tier_name'] : __( 'Standard', 'dame' );
							$full_name    = Utils::format_lastname( (string) $it['participant_last_name'] ) . ' ' . Utils::format_firstname( (string) $it['participant_first_name'] );
							$search_index = $full_name . ' ' . ( $it['license_number'] ?? '' ) . ' ' . ( $it['club_name'] ?? '' ) . ' ' . ( $it['participant_email'] ?? '' );
							?>
							<tr class="dame-participant-row <?php echo $is_in ? 'is-checked-in' : ''; ?>"
								data-item-id="<?php echo esc_attr( (string) $it['id'] ); ?>"
								data-checked-in="<?php echo $is_in ? '1' : '0'; ?>"
								data-tier="<?php echo esc_attr( $tier_val ); ?>"
								data-name="<?php echo esc_attr( $full_name ); ?>"
								data-elo="<?php echo esc_attr( (string) ( $it['elo'] ?? '0' ) ); ?>"
								data-club="<?php echo esc_attr( (string) ( $it['club_name'] ?? '' ) ); ?>"
								data-amount="<?php echo esc_attr( (string) ( $it['amount_cents'] ?? '0' ) ); ?>"
								data-search="<?php echo esc_attr( $search_index ); ?>">
								<td>
									<button type="button"
											class="button dame-checkin-btn <?php echo $is_in ? 'is-present button-primary' : ''; ?>"
											data-item-id="<?php echo esc_attr( (string) $it['id'] ); ?>"
											title="<?php esc_attr_e( 'Cliquer pour basculer la présence', 'dame' ); ?>">
										<?php echo $is_in ? '✓ Présent' : 'Absent'; ?>
									</button>
								</td>
								<td>
									<strong><?php echo esc_html( $full_name ); ?></strong>
									<?php if ( ! empty( $it['participant_email'] ) ) : ?>
										<br><span style="color:#666; font-size:12px;"><?php echo esc_html( (string) $it['participant_email'] ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( (string) ( $it['license_number'] ?? '—' ) ); ?></td>
								<td><strong><?php echo esc_html( isset( $it['elo'] ) && (int) $it['elo'] > 0 ? (string) $it['elo'] : '—' ); ?></strong></td>
								<td><?php echo esc_html( (string) ( $it['club_name'] ?? '—' ) ); ?></td>
								<td><?php echo esc_html( $tier_val ); ?></td>
								<td>
									<strong><?php echo esc_html( number_format( ( (int) $it['amount_cents'] ) / 100, 2, ',', ' ' ) ); ?> €</strong>
									<br><span style="color:#666; font-size:11px;"><?php echo esc_html( (string) $it['payment_method'] ); ?></span>
								</td>
								<td>
									<?php if ( $is_adh ) : ?>
										<a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $it['matched_adherent_id'] . '&action=edit' ) ); ?>" target="_blank" style="color:#2271b1; font-weight:600;">
											♟ <?php esc_html_e( 'Adhérent', 'dame' ); ?>
										</a>
									<?php else : ?>
										<span style="color:#666;"><?php esc_html_e( 'Externe', 'dame' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						<tr id="dame-no-match-row" style="display:none;">
							<td colspan="8" style="text-align:center; padding:15px; color:#666;">
								<?php esc_html_e( 'Aucun participant ne correspond aux critères de recherche et filtres actuels.', 'dame' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Modal 1: On-site Participant Addition -->
			<div class="dame-ticketing-modal-overlay" id="dame-onsite-modal">
				<div class="dame-ticketing-modal" role="dialog" aria-modal="true" aria-labelledby="dame-onsite-title">
					<div class="dame-modal-header">
						<h2 id="dame-onsite-title">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php esc_html_e( 'Ajouter un inscrit sur place (Espèces / Chèque)', 'dame' ); ?>
						</h2>
						<button type="button" class="dame-modal-close-btn" id="dame-close-onsite-modal" aria-label="<?php esc_attr_e( 'Fermer', 'dame' ); ?>">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>
					<form method="post" style="margin:0;">
						<?php wp_nonce_field( 'dame_ticketing_action_nonce', 'dame_ticketing_nonce' ); ?>
						<input type="hidden" name="campaign_id" value="<?php echo esc_attr( (string) $campaign_id ); ?>" />
						<input type="hidden" name="dame_add_onsite_participant" value="1" />

						<div class="dame-modal-body">
							<div class="form-grid">
								<div class="form-group">
									<label for="onsite_last_name"><?php esc_html_e( 'Nom *', 'dame' ); ?></label>
									<input type="text" id="onsite_last_name" name="participant_last_name" required autocomplete="off" autocorrect="off">
								</div>
								<div class="form-group">
									<label for="onsite_first_name"><?php esc_html_e( 'Prénom *', 'dame' ); ?></label>
									<input type="text" id="onsite_first_name" name="participant_first_name" required autocomplete="off" autocorrect="off">
								</div>
								<div class="form-group full-width">
									<label for="onsite_email"><?php esc_html_e( 'Email', 'dame' ); ?></label>
									<input type="email" id="onsite_email" name="participant_email" autocomplete="off" autocorrect="off">
								</div>
								<div class="form-group">
									<label for="onsite_license"><?php esc_html_e( 'N° Licence FFE', 'dame' ); ?></label>
									<input type="text" id="onsite_license" name="license_number" placeholder="ex: A12345" autocomplete="off" autocorrect="off">
								</div>
								<div class="form-group">
									<label for="onsite_elo"><?php esc_html_e( 'Elo', 'dame' ); ?></label>
									<input type="number" id="onsite_elo" name="elo" placeholder="ex: 1540">
								</div>
								<div class="form-group">
									<label for="onsite_club"><?php esc_html_e( 'Club', 'dame' ); ?></label>
									<input type="text" id="onsite_club" name="club_name">
								</div>
								<div class="form-group">
									<label for="onsite_tier"><?php esc_html_e( 'Tarif / Tournoi', 'dame' ); ?></label>
									<input type="text" id="onsite_tier" name="tier_name" list="dame-tiers-datalist" placeholder="ex: Open A">
									<datalist id="dame-tiers-datalist">
										<?php if ( ! empty( $stats['tiers'] ) ) : ?>
											<?php foreach ( array_keys( $stats['tiers'] ) as $tn ) : ?>
												<option value="<?php echo esc_attr( $tn ); ?>">
											<?php endforeach; ?>
										<?php endif; ?>
									</datalist>
								</div>
								<div class="form-group">
									<label for="onsite_amount"><?php esc_html_e( 'Montant (€)', 'dame' ); ?></label>
									<input type="number" step="0.5" min="0" id="onsite_amount" name="amount" value="0">
								</div>
								<div class="form-group">
									<label for="onsite_payment"><?php esc_html_e( 'Règlement', 'dame' ); ?></label>
									<select id="onsite_payment" name="payment_method">
										<option value="Espèces"><?php esc_html_e( 'Espèces', 'dame' ); ?></option>
										<option value="Chèques"><?php esc_html_e( 'Chèques', 'dame' ); ?></option>
										<option value="HelloAsso"><?php esc_html_e( 'HelloAsso (déjà réglé)', 'dame' ); ?></option>
									</select>
								</div>
							</div>
						</div>
						<div class="dame-modal-footer">
							<button type="button" class="button button-secondary" id="dame-cancel-onsite-modal">
								<?php esc_html_e( 'Annuler', 'dame' ); ?>
							</button>
							<button type="submit" class="button button-primary">
								<?php esc_html_e( 'Valider et Pointer présent', 'dame' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>

			<!-- Modal 2: Contacts Export & Category Creation -->
			<div class="dame-ticketing-modal-overlay" id="dame-contacts-modal">
				<div class="dame-ticketing-modal" role="dialog" aria-modal="true" aria-labelledby="dame-contacts-title">
					<div class="dame-modal-header">
						<h2 id="dame-contacts-title">
							<span class="dashicons dashicons-admin-users"></span>
							<?php esc_html_e( 'Synchroniser les joueurs externes vers Contacts', 'dame' ); ?>
						</h2>
						<button type="button" class="dame-modal-close-btn" id="dame-close-contacts-modal" aria-label="<?php esc_attr_e( 'Fermer', 'dame' ); ?>">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>
					<form method="post" style="margin:0;">
						<?php wp_nonce_field( 'dame_ticketing_action_nonce', 'dame_ticketing_nonce' ); ?>
						<input type="hidden" name="campaign_id" value="<?php echo esc_attr( (string) $campaign_id ); ?>" />
						<input type="hidden" name="dame_sync_external_contacts" value="1" />

						<div class="dame-modal-body">
							<p style="margin-top:0; color:#50575e;">
								<?php esc_html_e( 'Cette action permet d’exporter tous les participants externes (non adhérents du club) dans l’annuaire Contacts de DAME pour vos futures communications.', 'dame' ); ?>
							</p>

							<div class="form-group" style="margin-bottom:14px;">
								<label for="contact_category_select"><strong><?php esc_html_e( 'Catégorie de contact existante :', 'dame' ); ?></strong></label>
								<select name="contact_category" id="contact_category_select" style="width:100%; margin-top:4px;">
									<option value=""><?php esc_html_e( '-- Sélectionner une catégorie existante --', 'dame' ); ?></option>
									<?php if ( is_array( $contact_types ) ) : ?>
										<?php foreach ( $contact_types as $ct ) : ?>
											<option value="<?php echo esc_attr( $ct->slug ); ?>"><?php echo esc_html( $ct->name ); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>

							<div style="text-align:center; margin:10px 0; color:#646970; font-weight:600;">
								<?php esc_html_e( '— OU —', 'dame' ); ?>
							</div>

							<div class="form-group">
								<label for="new_contact_category_input"><strong><?php esc_html_e( 'Créer une nouvelle catégorie à la volée :', 'dame' ); ?></strong></label>
								<input type="text" name="new_contact_category" id="new_contact_category_input" placeholder="<?php echo esc_attr( (string) $campaign['title'] ); ?>" style="width:100%; margin-top:4px;">
								<p class="description" style="margin-top:4px; font-size:11px; color:#646970;">
									<?php esc_html_e( 'Si renseigné, cette nouvelle catégorie sera automatiquement créée et associée aux contacts exportés.', 'dame' ); ?>
								</p>
							</div>
						</div>
						<div class="dame-modal-footer">
							<button type="button" class="button button-secondary" id="dame-cancel-contacts-modal">
								<?php esc_html_e( 'Annuler', 'dame' ); ?>
							</button>
							<button type="submit" class="button button-primary">
								<?php esc_html_e( 'Lancer la synchronisation', 'dame' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>

		</div>
		<?php
	}
}
