<?php
/**
 * Message Admin Columns.
 *
 * @package DAME
 */

namespace DAME\Admin\Columns;

/**
 * Class Message
 */
class Message {

	/**
	 * Initialize columns.
	 */
	public function init() {
		add_filter( 'manage_dame_message_posts_columns', [ $this, 'manage_columns' ] );
		add_action( 'manage_dame_message_posts_custom_column', [ $this, 'render_columns' ], 10, 2 );
	}

	/**
	 * Manage columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function manage_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( 'date' === $key ) {
				$new_columns['sent_date']      = __( 'Date d\'envoi', 'dame' );
				$new_columns['sending_author'] = __( 'Auteur', 'dame' );
				$new_columns['recipients']     = __( 'Destinataires', 'dame' );
				$new_columns['open_rate']      = __( 'Taux d\'ouverture', 'dame' );
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
	}

	/**
	 * Render columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'sent_date':
				$status = get_post_meta( $post_id, '_dame_message_status', true );
				if ( 'sent' === $status || 'sending' === $status || 'scheduled' === $status ) {
					if ( 'sent' === $status ) {
						$old_sent_date = get_post_meta( $post_id, '_dame_sent_date', true );
						if ( ! empty( $old_sent_date ) ) {
							if ( is_numeric( $old_sent_date ) ) {
								echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', (int) $old_sent_date ), 'd/m/Y H:i' ) );
							} else {
								echo esc_html( get_date_from_gmt( $old_sent_date, 'd/m/Y H:i' ) );
							}
						} else {
							echo esc_html( get_the_modified_date( 'd/m/Y H:i', $post_id ) );
						}
					} else {
						echo esc_html( ucfirst( $status ) );
					}
				} else {
					echo '—';
				}
				break;

			case 'sending_author':
				$author_id = get_post_meta( $post_id, '_dame_sending_author', true );
				if ( empty( $author_id ) ) {
					$author_id = get_post_field( 'post_author', $post_id );
				}
				$user = get_userdata( $author_id );
				echo $user ? esc_html( $user->display_name ) : '—';
				break;

			case 'recipients':
				$count = get_post_meta( $post_id, '_dame_message_recipients_count', true );
				if ( '' === $count ) {
					$count = get_post_meta( $post_id, '_dame_total_recipients', true );
				}
				$count = (int) $count;

				if ( $count > 0 ) {
					echo esc_html( $count );
				} else {
					echo '0';
				}
				break;

			case 'open_rate':
				global $wpdb;
				$table_name = $wpdb->prefix . 'dame_message_opens';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$opens = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT email_hash) FROM $table_name WHERE message_id = %d", $post_id ) );

				$total = get_post_meta( $post_id, '_dame_message_recipients_count', true );
				if ( '' === $total ) {
					$total = get_post_meta( $post_id, '_dame_total_recipients', true );
				}
				$total = (int) $total;

				if ( $total > 0 ) {
					$percentage = round( ( $opens / $total ) * 100 );
					echo sprintf(
						'<a href="%s">%d / %d (%d%%)</a>',
						esc_url( admin_url( 'admin.php?page=dame-message-report&message_id=' . $post_id ) ),
						$opens,
						$total,
						$percentage
					);
				} else {
					echo '—';
				}
				break;
		}
	}
}
