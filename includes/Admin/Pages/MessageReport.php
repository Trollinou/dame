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
	public function init() {

		add_action( 'admin_head', [ $this, 'hide_menu_link' ] );
	}


	/**
	 * Hide the menu link via CSS.
	 */
	public function hide_menu_link() {
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
	public function render() {
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
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$opens = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE message_id = %d", $message_id ) );

		// Index opens by hash for faster lookup
		$opens_by_hash = array();
		foreach ( $opens as $open ) {
			// Store earliest open if multiple? Or list of opens?
			// The request says "Nom | Email | Date d'ouverture (ou Non lu)".
			// So we prioritize the first open or just 'Yes'.
			// Let's store the object.
			if ( ! isset( $opens_by_hash[ $open->email_hash ] ) ) {
				$opens_by_hash[ $open->email_hash ] = $open;
			}
		}

		$recipients = $this->get_formatted_recipients( $message_id );
		$message = get_post( $message_id );
		?>
		<div class="wrap">
			<h1><?php printf( esc_html__( 'Rapport d\'ouverture : %s', 'dame' ), esc_html( $message->post_title ) ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Statistiques', 'dame' ); ?></h2>
				<?php
				$total_sent = count( $recipients );
				$unique_opens = count( $opens_by_hash );
				$rate = $total_sent > 0 ? round( ( $unique_opens / $total_sent ) * 100, 2 ) : 0;
				?>
				<p>
					<strong><?php esc_html_e( 'Envoyés :', 'dame' ); ?></strong> <?php echo esc_html( $total_sent ); ?><br>
					<strong><?php esc_html_e( 'Ouvertures uniques :', 'dame' ); ?></strong> <?php echo esc_html( $unique_opens ); ?><br>
					<strong><?php esc_html_e( 'Taux d\'ouverture :', 'dame' ); ?></strong> <?php echo esc_html( $rate ); ?>%
				</p>
			</div>

			<br>

			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Nom (Adhérent / Responsable)', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Email', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Statut', 'dame' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $recipients ) ) : ?>
						<?php foreach ( $recipients as $email => $name ) : ?>
							<?php
							$hash = md5( mb_strtolower( trim( $email ), 'UTF-8' ) );
							$is_opened = isset( $opens_by_hash[ $hash ] );
							$open_data = $is_opened ? $opens_by_hash[ $hash ] : null;
							?>
							<tr>
								<td><?php echo esc_html( $name ); ?></td>
								<td><?php echo esc_html( $email ); ?></td>
								<td>
									<?php if ( $is_opened ) : ?>
										<span style="color: green; font-weight: bold;">
											<?php printf( esc_html__( 'Ouvert le %s', 'dame' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $open_data->opened_at ) ) ); ?>
										</span>
									<?php else : ?>
										<span style="color: #888;"><?php esc_html_e( 'Non lu', 'dame' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="3"><?php esc_html_e( 'Aucun destinataire trouvé ou liste non disponible pour ce message.', 'dame' ); ?></td>
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
	 * @return array Sorted array of recipients [email => formatted_name].
	 */
	private function get_formatted_recipients( $message_id ) {
		$message_id = absint( $message_id );
		if ( ! $message_id ) {
			return array();
		}

		$recipients = array();

		// Formatting helper: NOM (Upper) Prenom (Title)
		$format_name = function( $last, $first ) {
			return mb_strtoupper( (string) $last, 'UTF-8' ) . ' ' . mb_convert_case( (string) $first, MB_CASE_TITLE, 'UTF-8' );
		};

		// Get all posts (Adherents and Contacts) that received this message
		$posts = get_posts( [
			'post_type'      => [ 'adherent', 'dame_contact' ],
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => '_dame_message_received',
					'value'   => (string) $message_id,
					'compare' => '=',
				],
			],
		] );

		foreach ( $posts as $post ) {
			$pid       = $post->ID;
			$post_type = $post->post_type;

			if ( 'adherent' === $post_type ) {
				// Check all possible email sources for this adherent
				for ( $i = 1; $i <= 2; $i++ ) {
					$rep_email = get_post_meta( $pid, "_dame_legal_rep_{$i}_email", true );
					$refuses   = get_post_meta( $pid, "_dame_legal_rep_{$i}_email_refuses_comms", true );

					if ( ! empty( $rep_email ) && is_email( (string) $rep_email ) && '1' !== $refuses ) {
						$first = get_post_meta( $pid, "_dame_legal_rep_{$i}_first_name", true );
						$last  = get_post_meta( $pid, "_dame_legal_rep_{$i}_last_name", true );
						$recipients[ (string) $rep_email ] = $format_name( $last, $first );
					}
				}

				$member_email = get_post_meta( $pid, '_dame_email', true );
				$refuses      = get_post_meta( $pid, '_dame_email_refuses_comms', true );

				if ( ! empty( $member_email ) && is_email( (string) $member_email ) && '1' !== $refuses ) {
					$first = get_post_meta( $pid, '_dame_first_name', true );
					$last  = get_post_meta( $pid, '_dame_last_name', true );
					$recipients[ (string) $member_email ] = $format_name( $last, $first );
				}
			} elseif ( 'dame_contact' === $post_type ) {
				$email = get_post_meta( $pid, '_dame_contact_email', true );
				if ( ! empty( $email ) && is_email( (string) $email ) ) {
					$last  = get_post_meta( $pid, '_dame_contact_last_name', true );
					$first = get_post_meta( $pid, '_dame_contact_first_name', true );
					$org   = get_post_meta( $pid, '_dame_contact_organization', true );
					
					$name = $format_name( $last, $first );
					if ( ! empty( $org ) ) {
						$name = (string) $org . ( trim( (string) $name ) ? ' (' . $name . ')' : '' );
					}
					$recipients[ (string) $email ] = $name;
				}
			}
		}

		// Sort by Name Alphabetically
		uasort( $recipients, fn( $a, $b ) => strcasecmp( (string) $a, (string) $b ) );

		return $recipients;
	}
}
