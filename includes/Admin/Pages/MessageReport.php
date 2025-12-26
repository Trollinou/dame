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
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $opens ) ) : ?>
						<?php foreach ( $opens as $open ) : ?>
							<tr>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $open->opened_at ) ) ); ?></td>
								<td><?php echo esc_html( $open->user_ip ); ?></td>
								<td><?php echo esc_html( $open->email_hash ); ?></td>
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
}
