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
		add_action( 'admin_menu', [ $this, 'register_page' ] );
		add_action( 'admin_head', [ $this, 'hide_menu_link' ] );
	}

	/**
	 * Register the submenu page.
	 */
	public function register_page() {
		add_submenu_page(
			'dame', // Parent slug (or 'edit.php?post_type=adherent' or null/options.php if hidden)
			// Instructions say: "add_submenu_page( 'dame', ... )". "dame" usually refers to the main menu slug if it exists.
			// If 'dame' main menu doesn't exist, this might fail.
			// However, usually 'dame' is the slug for settings or main plugin page.
			// Let's assume 'dame' works as per instruction.
			__( 'Rapport de message', 'dame' ),
			__( 'Rapport de message', 'dame' ),
			'edit_dame_messages',
			'dame-message-report',
			[ $this, 'render' ]
		);
	}

	/**
	 * Hide the menu link via CSS.
	 */
	public function hide_menu_link() {
		echo '<style>a[href="admin.php?page=dame-message-report"] { display: none; }</style>';
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
	 * @param int $message_id The message ID.
	 * @return array Sorted array of recipients [email => formatted_name].
	 */
	private function get_formatted_recipients( $message_id ) {
		$message_id = absint( $message_id );
		if ( ! $message_id ) {
			return array();
		}

		$selection_method = get_post_meta( $message_id, '_dame_recipient_method', true );
		$adherent_ids     = array();

		if ( 'group' === $selection_method ) {
			$seasons           = get_post_meta( $message_id, '_dame_recipient_seasons', true );
			$saisonnier_groups = get_post_meta( $message_id, '_dame_recipient_groups_saisonnier', true );
			$permanent_groups  = get_post_meta( $message_id, '_dame_recipient_groups_permanent', true );
			$recipient_gender  = get_post_meta( $message_id, '_dame_recipient_gender', true );

			$saisonnier_adherent_ids = array();
			$permanent_adherent_ids  = array();

			// Logic matched from Mailing::process_mailing and utils.php

			// If Seasons is selected, query using OR relation logic for groups if present.
			// Re-building the complex query:
			$query_args = array(
				'post_type'      => 'adherent',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					'relation' => 'OR',
				),
			);

			if ( ! empty( $seasons ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'dame_saison_adhesion',
					'field'    => 'term_id',
					'terms'    => $seasons,
					'operator' => 'IN',
				);
			}

			$all_groups = array_merge(
				is_array( $saisonnier_groups ) ? $saisonnier_groups : [],
				is_array( $permanent_groups ) ? $permanent_groups : []
			);

			if ( ! empty( $all_groups ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'dame_group',
					'field'    => 'term_id',
					'terms'    => $all_groups,
					'operator' => 'IN',
				);
			}

			if ( ! empty( $recipient_gender ) && 'all' !== $recipient_gender ) {
				$query_args['meta_query'] = array(
					array(
						'key'   => '_dame_sexe',
						'value' => $recipient_gender,
					),
				);
			}

			// Only run query if we have criteria
			if ( ! empty( $seasons ) || ! empty( $all_groups ) ) {
				$adherent_ids = get_posts( $query_args );
			}

		} elseif ( 'manual' === $selection_method ) {
			$ids = get_post_meta( $message_id, '_dame_manual_recipients', true );
			$adherent_ids = ! empty( $ids ) && is_array( $ids ) ? $ids : array();
		}

		$recipients = array();
		if ( ! empty( $adherent_ids ) ) {
			foreach ( $adherent_ids as $adherent_id ) {
				$adherent_id = absint( $adherent_id );

				// Helper to format name: NOM (Upper) First (Title)
				$format_name = function( $last, $first ) {
					return mb_strtoupper( $last, 'UTF-8' ) . ' ' . mb_convert_case( $first, MB_CASE_TITLE, 'UTF-8' );
				};

				// Legal Reps
				for ( $i = 1; $i <= 2; $i++ ) {
					$rep_email         = get_post_meta( $adherent_id, "_dame_legal_rep_{$i}_email", true );
					$rep_refuses_comms = get_post_meta( $adherent_id, "_dame_legal_rep_{$i}_email_refuses_comms", true );

					if ( ! empty( $rep_email ) && is_email( $rep_email ) && '1' !== $rep_refuses_comms ) {
						if ( ! array_key_exists( $rep_email, $recipients ) ) {
							$first = get_post_meta( $adherent_id, "_dame_legal_rep_{$i}_first_name", true );
							$last  = get_post_meta( $adherent_id, "_dame_legal_rep_{$i}_last_name", true );
							$recipients[ $rep_email ] = $format_name( $last, $first );
						}
					}
				}

				// Adherent
				$member_email         = get_post_meta( $adherent_id, '_dame_email', true );
				$member_refuses_comms = get_post_meta( $adherent_id, '_dame_email_refuses_comms', true );

				if ( ! empty( $member_email ) && is_email( $member_email ) && '1' !== $member_refuses_comms ) {
					if ( ! array_key_exists( $member_email, $recipients ) ) {
						$first = get_post_meta( $adherent_id, '_dame_first_name', true );
						$last  = get_post_meta( $adherent_id, '_dame_last_name', true );
						$recipients[ $member_email ] = $format_name( $last, $first );
					}
				}
			}
		}

		// Sort by Name (Value) Alphabetically
		uasort( $recipients, function( $a, $b ) {
			return strcasecmp( $a, $b );
		} );

		return $recipients;
	}
}
