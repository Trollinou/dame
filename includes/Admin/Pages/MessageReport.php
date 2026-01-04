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
		$opens = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE message_id = %d ORDER BY opened_at DESC", $message_id ) );

		$message = get_post( $message_id );
		?>
		<div class="wrap">
			<h1><?php printf( esc_html__( 'Rapport d\'ouverture : %s', 'dame' ), esc_html( $message->post_title ) ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Statistiques', 'dame' ); ?></h2>
				<?php
				$total_sent = (int) get_post_meta( $message_id, '_dame_message_recipients_count', true );
				$unique_opens = count( array_unique( array_column( $opens, 'email_hash' ) ) );
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
						<th><?php esc_html_e( 'Date d\'ouverture', 'dame' ); ?></th>
						<th><?php esc_html_e( 'IP (Anonymisée)', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Hash Email', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Adhérent / Responsable', 'dame' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $opens ) ) : ?>
						<?php foreach ( $opens as $open ) : ?>
							<tr>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $open->opened_at ) ) ); ?></td>
								<td><?php echo esc_html( $open->user_ip ); ?></td>
								<td><?php echo esc_html( $open->email_hash ); ?></td>
								<td><?php echo esc_html( $this->get_name_by_hash( $open->email_hash ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="3"><?php esc_html_e( 'Aucune ouverture enregistrée pour le moment.', 'dame' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Find the name associated with an email hash.
	 *
	 * @param string $hash The email hash.
	 * @return string The formatted name or 'Inconnu'.
	 */
	private function get_name_by_hash( $hash ) {
		// We need to find an adherent or legal rep with this email hash.
		// Since we only have the hash, and we can't reverse MD5, we technically can't search directly
		// unless we hash all emails in DB or if we stored the email in the opens table (we didn't).
		// Wait, the prompt said "Pour chaque email, cherche l'adhérent correspondant."
		// But the table only has `email_hash`.
		// However, `get_name_by_email( $email )` implies we have the email.
		// Reviewing `Tracker.php`: it stores `email_hash` only.
		// So we CANNOT know the email from the hash.
		// UNLESS we brute force match against all adherents?
		// "Cherche un post adherent qui a cet email dans _dame_email..." implies we search by EMAIL.
		// But we don't have the email in the Report view, only the hash.
		// Did I miss something?
		// Ah, the tracker stores what the pixel request sends.
		// The pixel request `Tracker::handle_tracking_pixel` receives `h` (hash) and `mid`.
		// It does NOT receive the email.
		// So the database `wp_dame_message_opens` ONLY has the hash.

		// If the user wants to see names, we have to find which adherent has that hash.
		// Since we can't decrypt MD5, we must compute MD5 of all adherent emails and compare.
		// That is expensive.
		// BUT, `_dame_email` is meta. We can't do SQL `MD5(meta_value) = hash` efficiently?
		// Actually, standard SQL `MD5()` might work if the DB supports it.
		// `SELECT post_id FROM postmeta WHERE MD5(LOWER(TRIM(meta_value))) = 'hash'`.
		// That is feasible for a few thousand members.

		// Let's implement that.

		global $wpdb;

		// We need to check 3 keys: _dame_email, _dame_legal_rep_1_email, _dame_legal_rep_2_email.
		// We want the Post Title (Adherent Name).
		// We prioritize Adherent's own email.

		// 1. Check Adherent Email
		$sql = $wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta
			WHERE meta_key = '_dame_email'
			AND MD5(LOWER(TRIM(meta_value))) = %s
			LIMIT 1",
			$hash
		);
		$adherent_id = $wpdb->get_var( $sql );

		if ( $adherent_id ) {
			return get_the_title( $adherent_id );
		}

		// 2. Check Rep 1
		$sql = $wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta
			WHERE meta_key = '_dame_legal_rep_1_email'
			AND MD5(LOWER(TRIM(meta_value))) = %s
			LIMIT 1",
			$hash
		);
		$adherent_id = $wpdb->get_var( $sql );

		if ( $adherent_id ) {
			$title = get_the_title( $adherent_id );
			return $title . ' (Resp. 1)';
		}

		// 3. Check Rep 2
		$sql = $wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta
			WHERE meta_key = '_dame_legal_rep_2_email'
			AND MD5(LOWER(TRIM(meta_value))) = %s
			LIMIT 1",
			$hash
		);
		$adherent_id = $wpdb->get_var( $sql );

		if ( $adherent_id ) {
			$title = get_the_title( $adherent_id );
			return $title . ' (Resp. 2)';
		}

		return __( 'Inconnu', 'dame' );
	}
}
