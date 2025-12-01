<?php
/**
 * File for displaying the message opens report.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Registers the report page as a hidden submenu page.
 */
function dame_register_message_opens_report_page() {
	add_submenu_page(
		null, // No parent menu.
		__( 'Rapport d\'ouverture', 'dame' ),
		__( 'Rapport d\'ouverture', 'dame' ),
		'publish_dame_messages',
		'dame-message-opens-report',
		'dame_render_message_opens_report_page'
	);
}
add_action( 'admin_menu', 'dame_register_message_opens_report_page' );

/**
 * Renders the content of the message opens report page.
 */
function dame_render_message_opens_report_page() {
	global $wpdb;

	$message_id = isset( $_GET['message_id'] ) ? absint( $_GET['message_id'] ) : 0;

	if ( ! $message_id || 'dame_message' !== get_post_type( $message_id ) || ! current_user_can( 'edit_post', $message_id ) ) {
		wp_die( esc_html__( 'Le message demandé est invalide ou vous n\'avez pas la permission de le voir.', 'dame' ) );
	}

	$message_title = get_the_title( $message_id );
	$recipients    = dame_get_message_recipients( $message_id );
	$total_sent    = count( $recipients );

	$table_name = $wpdb->prefix . 'dame_message_opens';
	$opens_query = $wpdb->prepare(
		"SELECT email_hash, opened_at FROM {$table_name} WHERE message_id = %d",
		$message_id
	);
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$open_data_raw = $wpdb->get_results( $opens_query, OBJECT_K );
	$open_data     = is_array( $open_data_raw ) ? $open_data_raw : array();
	$total_opens   = count( $open_data );

	?>
	<div class="wrap">
		<h1><?php echo sprintf( esc_html__( 'Rapport d\'ouverture pour : %s', 'dame' ), esc_html( $message_title ) ); ?></h1>

		<p>
			<?php
			echo sprintf(
				// translators: %1$d is the number of opens, %2$d is the total number of recipients.
				esc_html__( 'Total : %1$d ouvertures sur %2$d destinataires.', 'dame' ),
				absint( $total_opens ),
				absint( $total_sent )
			);
			?>
		</p>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Nom du destinataire', 'dame' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Email', 'dame' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Date d\'ouverture', 'dame' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( ! empty( $recipients ) ) {
					// Sort recipients by name for consistent display.
					uasort(
						$recipients,
						function( $a, $b ) {
							return strnatcasecmp( $a, $b );
						}
					);

					foreach ( $recipients as $email => $name ) {
						$email_hash = md5( strtolower( trim( $email ) ) );
						$opened     = isset( $open_data[ $email_hash ] );
						?>
						<tr>
							<td><?php echo esc_html( $name ); ?></td>
							<td><?php echo esc_html( $email ); ?></td>
							<td>
								<?php
								if ( $opened ) {
									$open_date = new DateTime( $open_data[ $email_hash ]->opened_at, new DateTimeZone( 'UTC' ) );
									$open_date->setTimezone( new DateTimeZone( wp_timezone_string() ) );
									echo esc_html( $open_date->format( 'd/m/Y H:i:s' ) );
								} else {
									echo '<span style="color:#999;">' . esc_html__( 'Non ouvert', 'dame' ) . '</span>';
								}
								?>
							</td>
						</tr>
						<?php
					}
				} else {
					?>
					<tr>
						<td colspan="3"><?php esc_html_e( 'Aucun destinataire trouvé pour ce message.', 'dame' ); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>
	<?php
}
