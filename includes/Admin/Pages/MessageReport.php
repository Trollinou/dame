<?php
/**
 * Message Report Page.
 *
 * @package DAME
 */

namespace DAME\Admin\Pages;

/**
 * Class MessageReport
 */
class MessageReport {

	/**
	 * Initialize the page.
	 */
	public function init(): void {

		add_action( 'admin_head', [ $this, 'hide_menu_link' ] );
	}


	/**
	 * Hide the menu link via CSS.
	 */
	public function hide_menu_link(): void {
		echo '<style>
			a[href="admin.php?page=dame-message-report"],
			li:has(> a[href="admin.php?page=dame-message-report"]) {
				display: none !important;
			}
		</style>';
	}

	/**
	 * Render the report page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			return;
		}

		$message_id = isset( $_GET['message_id'] ) ? absint( $_GET['message_id'] ) : 0;
		if ( ! $message_id ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Message invalide.', 'dame' ) . '</p></div>';
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_message_opens';
		
		// 1. Get all recipients for this message
		$recipients = $this->get_formatted_recipients( $message_id );
		
		// 2. Get unique opens count (by email hash)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$unique_opens = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT email_hash) FROM {$table_name} WHERE message_id = %d AND opened_at IS NOT NULL",
			$message_id
		) );

		// 3. Get all open data to mark individual recipients
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$opens_data = $wpdb->get_results( $wpdb->prepare(
			"SELECT recipient_id, opened_at FROM {$table_name} WHERE message_id = %d AND opened_at IS NOT NULL",
			$message_id
		) );

		$opened_recipients = [];
		foreach ( $opens_data as $open ) {
			$opened_recipients[ (int) $open->recipient_id ] = $open->opened_at;
		}

		$message = get_post( $message_id );
		?>
		<div class="wrap">
			<h1><?php printf( esc_html__( 'Rapport d\'ouverture : %s', 'dame' ), esc_html( $message->post_title ) ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Statistiques', 'dame' ); ?></h2>
				<?php
				$total_targets = count( $recipients );
				// Count how many have been sent (sent_at is NOT NULL)
				$sent_count = 0;
				foreach($recipients as $r) {
					if (!empty($r['sent_at'])) $sent_count++;
				}
				$rate = $total_targets > 0 ? round( ( $unique_opens / $total_targets ) * 100, 2 ) : 0;
				?>
				<p>
					<strong><?php esc_html_e( 'Destinataires ciblés :', 'dame' ); ?></strong> <?php echo esc_html( (string) $total_targets ); ?><br>
					<strong><?php esc_html_e( 'Messages expédiés :', 'dame' ); ?></strong> <?php echo esc_html( (string) $sent_count ); ?> / <?php echo esc_html( (string) $total_targets ); ?><br>
					<strong><?php esc_html_e( 'Emails ouverts (uniques) :', 'dame' ); ?></strong> <?php echo esc_html( (string) $unique_opens ); ?><br>
					<strong><?php esc_html_e( 'Taux d\'ouverture :', 'dame' ); ?></strong> <?php echo esc_html( (string) $rate ); ?>%
				</p>
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
							$opened_at = isset( $opened_recipients[ $pid ] ) ? $opened_recipients[ $pid ] : null;
							?>
							<tr>
								<td><?php echo esc_html( $name ); ?></td>
								<td><?php echo esc_html( (string) $email ); ?></td>
								<td>
									<?php 
									if ( ! empty( $sent_at ) ) {
										echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $sent_at . ' UTC' ) ) );
									} else {
										echo '—';
									}
									?>
								</td>
								<td>
									<?php if ( ! empty( $opened_at ) ) : ?>
										<span style="color: green; font-weight: bold;">
											<?php 
											$timestamp = strtotime( $opened_at . ' UTC' );
											printf( esc_html__( 'Ouvert le %s', 'dame' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) ); 
											?>
										</span>
									<?php elseif ( ! empty( $sent_at ) ) : ?>
										<span style="color: #888;"><?php esc_html_e( 'Non lu', 'dame' ); ?></span>
									<?php else : ?>
										<span style="color: #d63638; font-style: italic;"><?php esc_html_e( 'En attente d\'envoi', 'dame' ); ?></span>
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

		// 1. Récupération des données brutes
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT recipient_id, recipient_name as name, recipient_email as email, sent_at 
			FROM {$table_name} 
			WHERE message_id = %d",
			$message_id
		), ARRAY_A );

		if ( empty( $results ) ) {
			return array();
		}

		// 2. CORRECTION : Reconstruction des noms manquants
		// On boucle sur les résultats pour vérifier si le nom est présent
		foreach ( $results as $key => $row ) {
			if ( empty( $row['name'] ) && ! empty( $row['recipient_id'] ) ) {
				$post_id   = (int) $row['recipient_id'];
				$post_type = get_post_type( $post_id );

				// On utilise vos nouvelles fonctions de Utils pour retrouver le nom exact
				if ( 'adherent' === $post_type ) {
					$results[ $key ]['name'] = \DAME\Core\Utils::generate_adherent_title( $post_id );
				} elseif ( 'dame_contact' === $post_type ) {
					$results[ $key ]['name'] = \DAME\Core\Utils::generate_contact_title( $post_id );
				}
			}
			
			// Sécurité : si toujours vide, on met l'email pour éviter une ligne vide
			if ( empty( $results[ $key ]['name'] ) ) {
				$results[ $key ]['name'] = $row['email'];
			}
		}

		// 3. Tri alphabétique (maintenant que les noms sont récupérés)
		uasort( $results, fn( $a, $b ) => strcasecmp( (string) $a['name'], (string) $b['name'] ) );

		return $results;
	}
}
