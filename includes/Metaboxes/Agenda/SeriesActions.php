<?php
/**
 * Agenda Series Actions Handler.
 *
 * @package DAME\Metaboxes\Agenda
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Agenda;

use DAME\Services\Agenda\Series_Manager;

/**
 * Handles deletion of recurrence series and admin notice rendering.
 */
class SeriesActions {

	/**
	 * Handles deleting an event and subsequent events in a series.
	 */
	public function handle_delete_series_from(): void {
		$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
		if ( ! $post_id || ! current_user_can( 'delete_post', $post_id ) ) {
			wp_die( esc_html__( 'Action non autorisée.', 'dame' ) );
		}

		check_admin_referer( 'dame_delete_series_from_' . $post_id );

		$deleted_count = Series_Manager::delete_series_from( $post_id );

		$redirect_url = add_query_arg(
			array(
				'post_type' => 'dame_agenda',
				'dame_msg'  => 'series_deleted',
				'count'     => $deleted_count,
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handles deleting an entire series.
	 */
	public function handle_delete_entire_series(): void {
		$group_id = isset( $_GET['group_id'] ) ? sanitize_text_field( wp_unslash( $_GET['group_id'] ) ) : '';
		$post_id  = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;

		if ( empty( $group_id ) || ( $post_id && ! current_user_can( 'delete_post', $post_id ) ) ) {
			wp_die( esc_html__( 'Action non autorisée.', 'dame' ) );
		}

		check_admin_referer( 'dame_delete_entire_series_' . $group_id );

		$deleted_count = Series_Manager::delete_entire_series( $group_id );

		$redirect_url = add_query_arg(
			array(
				'post_type' => 'dame_agenda',
				'dame_msg'  => 'entire_series_deleted',
				'count'     => $deleted_count,
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Display admin notices for series deletion.
	 */
	public function display_admin_notices(): void {
		if ( ! isset( $_GET['dame_msg'] ) ) {
			return;
		}

		$count = isset( $_GET['count'] ) ? (int) $_GET['count'] : 0;
		$msg   = sanitize_key( wp_unslash( $_GET['dame_msg'] ) );

		if ( 'series_deleted' === $msg ) {
			/* translators: %d: nombre d'événements supprimés */
			$notice = sprintf( __( '%d événement(s) de la série ont été mis à la corbeille avec succès.', 'dame' ), $count );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notice ) . '</p></div>';
		} elseif ( 'entire_series_deleted' === $msg ) {
			/* translators: %d: nombre d'événements supprimés */
			$notice = sprintf( __( 'La série complète (%d événements) a été mise à la corbeille avec succès.', 'dame' ), $count );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notice ) . '</p></div>';
		}
	}
}
