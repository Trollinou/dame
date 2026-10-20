<?php
/**
 * Message Report Page.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin\Pages;

/**
 * Class MessageReport
 */
class MessageReport {

	/**
	 * Initialize the page hooks.
	 */
	public function init(): void {
		add_action( 'admin_post_dame_purge_message_queue', array( $this, 'handle_purge_queue' ) );
		add_action( 'admin_action_dame_purge_message_queue', array( $this, 'handle_purge_queue' ) );
	}

	/**
	 * Render the report page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin report page view.
		$message_id = isset( $_GET['message_id'] ) ? absint( $_GET['message_id'] ) : 0;
		if ( ! $message_id ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Message invalide.', 'dame' ) . '</p></div>';
			return;
		}

		// 1. Get all recipients for this message.
		$recipients = $this->get_formatted_recipients( $message_id );

		// 2. Get unique opens count and open data via Repository.
		$tracking_repo     = new \DAME\Repositories\TrackingRepository();
		$unique_opens      = $tracking_repo->get_unique_opens_count( $message_id );
		$opened_recipients = $tracking_repo->get_opened_recipients_map( $message_id );

		$message    = get_post( $message_id );
		$post_title = $message instanceof \WP_Post ? $message->post_title : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$purged_count = isset( $_GET['purged'] ) ? absint( $_GET['purged'] ) : null;
		?>
		<div class="wrap">
			<h1>
			<?php
			// translators: %s is the message post title.
			echo esc_html( sprintf( __( 'Rapport d\'ouverture : %s', 'dame' ), $post_title ) );
			?>
			</h1>

			<?php if ( null !== $purged_count ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
					<?php
					// translators: %d is the number of purged emails.
					echo esc_html( sprintf( _n( '%d email en attente a été purgé avec succès de la file d\'envoi.', '%d emails en attente ont été purgés avec succès de la file d\'envoi.', $purged_count, 'dame' ), $purged_count ) );
					?>
					</p>
				</div>
			<?php endif; ?>

			<div class="card">
				<h2><?php esc_html_e( 'Statistiques', 'dame' ); ?></h2>
				<?php
				$total_targets = count( $recipients );
				// Count how many have been sent (sent_at is NOT NULL).
				$sent_count = 0;
				foreach ( $recipients as $r ) {
					if ( ! empty( $r['sent_at'] ) ) {
						++$sent_count;
					}
				}
				$pending_count = $total_targets - $sent_count;
				$rate          = $total_targets > 0 ? round( ( $unique_opens / $total_targets ) * 100, 2 ) : 0;
				?>
				<p>
					<strong><?php esc_html_e( 'Destinataires ciblés :', 'dame' ); ?></strong> <?php echo esc_html( (string) $total_targets ); ?><br>
					<strong><?php esc_html_e( 'Messages expédiés :', 'dame' ); ?></strong> <?php echo esc_html( (string) $sent_count ); ?> / <?php echo esc_html( (string) $total_targets ); ?><br>
					<strong><?php esc_html_e( 'Emails ouverts (uniques) :', 'dame' ); ?></strong> <?php echo esc_html( (string) $unique_opens ); ?><br>
					<strong><?php esc_html_e( 'Taux d\'ouverture :', 'dame' ); ?></strong> <?php echo esc_html( (string) $rate ); ?>%
				</p>

				<?php if ( $pending_count > 0 ) : ?>
					<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ccd0d4;">
						<?php
						// translators: %d is the number of pending emails.
						$confirm_msg = sprintf( __( 'Êtes-vous sûr de vouloir purger la file d\'envoi pour ce message ? %d email(s) en attente seront définitivement annulés.', 'dame' ), $pending_count );
						?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block;" onsubmit="return confirm('<?php echo esc_js( $confirm_msg ); ?>');">
							<input type="hidden" name="action" value="dame_purge_message_queue">
							<input type="hidden" name="message_id" value="<?php echo esc_attr( (string) $message_id ); ?>">
							<?php wp_nonce_field( 'dame_purge_message_queue_' . $message_id, 'dame_purge_nonce' ); ?>
							<button type="submit" class="button button-secondary" style="color: #b32d2e; border-color: #b32d2e;">
								<span class="dashicons dashicons-trash" style="vertical-align: middle; font-size: 17px; line-height: 1.4;"></span>
								<?php
								// translators: %d is the number of pending emails.
								echo esc_html( sprintf( __( 'Purger la file d\'envoi (%d en attente)', 'dame' ), $pending_count ) );
								?>
							</button>
						</form>
						<p class="description" style="margin-top: 5px;">
							<?php esc_html_e( 'Supprime les emails en attente de la file d\'envoi. Les messages déjà expédiés et le suivi d\'ouverture sont préservés.', 'dame' ); ?>
						</p>
					</div>
				<?php endif; ?>
			</div>

			<br>

			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Nom (Adhérent / Responsable)', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Email', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Date d\'envoi', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Statut', 'dame' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $recipients ) ) : ?>
						<?php foreach ( $recipients as $data ) : ?>
							<?php
							$name      = $data['name'];
							$email     = $data['email'];
							$sent_at   = $data['sent_at'];
							$pid       = $data['recipient_id'];
							$opened_at = ! empty( $data['opened_at'] ) ? $data['opened_at'] : ( isset( $opened_recipients[ $pid ] ) ? $opened_recipients[ $pid ] : null );
							?>
							<tr>
								<td><?php echo esc_html( $name ); ?></td>
								<td><?php echo esc_html( (string) $email ); ?></td>
								<td>
									<?php
									if ( ! empty( $sent_at ) ) {
										$sent_ts = strtotime( $sent_at . ' UTC' );
										$fmt     = false !== $sent_ts ? (string) wp_date( (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' ), $sent_ts ) : '';
										echo esc_html( $fmt );
									} else {
										echo '—';
									}
									?>
								</td>
								<td>
									<?php if ( ! empty( $opened_at ) ) : ?>
										<span class="dame-status-badge dame-status-badge--read">
											<?php
											$timestamp = strtotime( $opened_at . ' UTC' );
											$date_fmt  = false !== $timestamp ? (string) wp_date( (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' ), $timestamp ) : '';
											// translators: %s is the formatted date.
											echo esc_html( sprintf( __( 'Ouvert le %s', 'dame' ), $date_fmt ) );
											?>
										</span>
									<?php elseif ( ! empty( $sent_at ) ) : ?>
										<span class="dame-status-badge dame-status-badge--unread"><?php esc_html_e( 'Non lu', 'dame' ); ?></span>
									<?php else : ?>
										<span class="dame-status-badge dame-status-badge--pending"><?php esc_html_e( 'En attente d\'envoi', 'dame' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="4"><?php esc_html_e( 'Aucun destinataire trouvé ou liste non disponible pour ce message.', 'dame' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Retrieves the list of recipients with strictly formatted names and sorted.
	 *
	 * This method is cumulative: it looks for all posts (Adherents and Contacts)
	 * that have received this specific message.
	 *
	 * @param int $message_id The message ID.
	 * @return array<string, mixed> Sorted array of recipients [email => formatted_name].
	 */
	private function get_formatted_recipients( $message_id ) {
		$message_id = absint( $message_id );
		if ( ! $message_id ) {
			return array();
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_message_opens';

		// 1. Récupération des données brutes.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT recipient_id, recipient_name as name, recipient_email as email, sent_at, opened_at FROM %i WHERE message_id = %d',
				$table_name,
				$message_id
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return array();
		}

		// 2. CORRECTION : Reconstruction des noms manquants.
		// On boucle sur les résultats pour vérifier si le nom est présent.
		foreach ( $results as $key => $row ) {
			if ( empty( $row['name'] ) && ! empty( $row['recipient_id'] ) ) {
				$post_id   = (int) $row['recipient_id'];
				$post_type = get_post_type( $post_id );

				// On utilise vos nouvelles fonctions de Utils pour retrouver le nom exact.
				if ( 'adherent' === $post_type ) {
					$results[ $key ]['name'] = \DAME\Core\Utils::generate_adherent_title( $post_id );
				} elseif ( 'dame_contact' === $post_type ) {
					$results[ $key ]['name'] = \DAME\Core\Utils::generate_contact_title( $post_id );
				}
			}

			// Sécurité : si toujours vide, on met l'email pour éviter une ligne vide.
			if ( empty( $results[ $key ]['name'] ) ) {
				$results[ $key ]['name'] = $row['email'];
			}
		}

		// 3. Tri alphabétique (maintenant que les noms sont récupérés).
		uasort( $results, fn( $a, $b ) => strcasecmp( (string) $a['name'], (string) $b['name'] ) );

		return $results;
	}

	/**
	 * Handles purging of pending unsent emails for a message.
	 */
	public function handle_purge_queue(): void {
		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			wp_die( esc_html__( 'Droits insuffisants pour effectuer cette action.', 'dame' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$message_id = isset( $_POST['message_id'] ) ? absint( $_POST['message_id'] ) : ( isset( $_GET['message_id'] ) ? absint( $_GET['message_id'] ) : 0 );
		if ( ! $message_id ) {
			wp_die( esc_html__( 'Message invalide.', 'dame' ) );
		}

		check_admin_referer( 'dame_purge_message_queue_' . $message_id, 'dame_purge_nonce' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_message_opens';

		// 1. Count pending records before deletion.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$pending_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE message_id = %d AND sent_at IS NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$message_id
			)
		);

		// 2. Delete pending records only (preserving already sent and opened records).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE message_id = %d AND sent_at IS NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$message_id
			)
		);

		// 3. Clear any scheduled retry or batch events in WP-Cron for this message.
		$crons = _get_cron_array();
		if ( is_array( $crons ) ) {
			foreach ( $crons as $timestamp => $hooks ) {
				if ( is_array( $hooks ) && isset( $hooks['dame_cron_send_batch'] ) && is_array( $hooks['dame_cron_send_batch'] ) ) {
					foreach ( $hooks['dame_cron_send_batch'] as $event ) {
						if ( isset( $event['args'][0] ) && (int) $event['args'][0] === $message_id ) {
							wp_unschedule_event( (int) $timestamp, 'dame_cron_send_batch', array_values( (array) $event['args'] ) );
						}
					}
				}
			}
		}

		// 4. Update message post metadata.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$sent_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE message_id = %d AND sent_at IS NOT NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$message_id
			)
		);

		if ( $sent_count > 0 ) {
			update_post_meta( $message_id, '_dame_message_status', 'sent' );
			update_post_meta( $message_id, '_dame_message_recipients_count', $sent_count );
			$total_batches = (int) get_post_meta( $message_id, '_dame_scheduled_batches_total', true );
			update_post_meta( $message_id, '_dame_scheduled_batches_processed', $total_batches );
		} else {
			// No emails sent at all: revert status to draft and reset counters.
			update_post_meta( $message_id, '_dame_message_status', 'draft' );
			update_post_meta( $message_id, '_dame_message_recipients_count', 0 );
			update_post_meta( $message_id, '_dame_scheduled_batches_total', 0 );
			update_post_meta( $message_id, '_dame_scheduled_batches_processed', 0 );
		}

		// 5. If no more pending emails globally across all messages, unschedule queue cron.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$global_pending = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE sent_at IS NULL" );
		if ( 0 === $global_pending ) {
			wp_clear_scheduled_hook( 'dame_cron_process_queue' );
		}

		// 6. Redirect back to the report page with notification.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'dame-message-report',
					'message_id' => $message_id,
					'purged'     => $pending_count,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
