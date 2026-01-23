<?php

namespace DAME\Metaboxes\Sondage;

use WP_Post;
use DateTime;

/**
 * Class Manager
 * Handles metaboxes for Sondage CPT.
 */
class Manager {

	/**
	 * Initialize the metaboxes.
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_dame_sondage', [ $this, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_post_dame_delete_sondage_response', [ $this, 'handle_delete_response' ] );
		add_filter( 'post_updated_messages', [ $this, 'admin_notices' ] );
	}

	/**
	 * Register meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'dame_sondage_config',
			__( 'Configuration du sondage', 'dame' ),
			[ $this, 'render_config' ],
			'dame_sondage',
			'normal',
			'high'
		);
		add_meta_box(
			'dame_sondage_results',
			__( 'Résultats du sondage', 'dame' ),
			[ $this, 'render_results' ],
			'dame_sondage',
			'normal',
			'high'
		);
	}

	/**
	 * Render configuration metabox.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_config( $post ) {
		wp_nonce_field( 'dame_save_sondage_metabox_data', 'dame_sondage_metabox_nonce' );

		$sondage_data = get_post_meta( $post->ID, '_dame_sondage_data', true ) ?: [];
		$responses_exist = (bool) get_posts( [
			'post_type'      => 'dame_sondage_reponse',
			'post_parent'    => $post->ID,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );

		$is_locked = $responses_exist;
		?>
		<div id="sondage-config-container">
			<?php if ( $is_locked ) : ?>
				<p><strong><?php _e( 'Ce sondage est verrouillé car il a déjà reçu des réponses. Vous ne pouvez plus modifier les dates ou les plages horaires.', 'dame' ); ?></strong></p>
			<?php else : ?>
				<p><?php _e( 'Ajoutez les dates et les plages horaires pour ce sondage.', 'dame' ); ?></p>
			<?php endif; ?>

			<div id="sondage-dates-wrapper">
				<?php foreach ( $sondage_data as $date_key => $date_info ) : ?>
					<div class="sondage-date-group">
						<hr>
						<h4><?php echo esc_html( sprintf( __( 'Date %d', 'dame' ), $date_key + 1 ) ); ?></h4>
						<p>
							<label for="sondage_date_<?php echo esc_attr( $date_key ); ?>"><?php _e( 'Date:', 'dame' ); ?></label>
							<input type="date" id="sondage_date_<?php echo esc_attr( $date_key ); ?>" name="_dame_sondage_data[<?php echo esc_attr( $date_key ); ?>][date]" value="<?php echo esc_attr( $date_info['date'] ); ?>" class="sondage-date-input" <?php disabled( $is_locked ); ?>>
							<?php if ( ! $is_locked ) : ?>
								<button type="button" class="button remove-sondage-date"><?php _e( 'Supprimer cette date', 'dame' ); ?></button>
							<?php endif; ?>
						</p>
						<div class="sondage-time-slots-wrapper">
							<?php if ( ! empty( $date_info['time_slots'] ) ) : ?>
								<?php foreach ( $date_info['time_slots'] as $time_key => $time_slot ) : ?>
									<div class="sondage-time-slot-group">
										<label><?php _e( 'Plage horaire:', 'dame' ); ?></label>
										<input type="time" name="_dame_sondage_data[<?php echo esc_attr( $date_key ); ?>][time_slots][<?php echo esc_attr( $time_key ); ?>][start]" value="<?php echo esc_attr( $time_slot['start'] ); ?>" step="900" <?php disabled( $is_locked ); ?>>
										<span>-</span>
										<input type="time" name="_dame_sondage_data[<?php echo esc_attr( $date_key ); ?>][time_slots][<?php echo esc_attr( $time_key ); ?>][end]" value="<?php echo esc_attr( $time_slot['end'] ); ?>" step="900" <?php disabled( $is_locked ); ?>>
										<?php if ( ! $is_locked ) : ?>
											<button type="button" class="button remove-sondage-time-slot"><?php _e( 'Supprimer', 'dame' ); ?></button>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<?php if ( ! $is_locked ) : ?>
							<button type="button" class="button add-sondage-time-slot"><?php _e( 'Ajouter une plage horaire', 'dame' ); ?></button>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if ( ! $is_locked ) : ?>
				<p>
					<button type="button" id="add-sondage-date" class="button button-primary"><?php _e( 'Ajouter une date', 'dame' ); ?></button>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render results metabox.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_results( $post ) {
		$sondage_data = get_post_meta( $post->ID, '_dame_sondage_data', true );
		if ( empty( $sondage_data ) ) {
			echo '<p>' . __( 'Le sondage n\'a pas encore été configuré.', 'dame' ) . '</p>';
			return;
		}

		$responses = get_posts( [
			'post_type'      => 'dame_sondage_reponse',
			'post_status'    => 'publish',
			'post_parent'    => $post->ID,
			'posts_per_page' => -1,
			'orderby'        => 'post_title',
			'order'          => 'ASC',
		] );

		if ( empty( $responses ) ) {
			echo '<p>' . __( 'Aucune réponse pour le moment.', 'dame' ) . '</p>';
			return;
		}

		$results = [];
		foreach ( $sondage_data as $date_index => $date_info ) {
			if ( ! empty( $date_info['time_slots'] ) ) {
				foreach ( $date_info['time_slots'] as $time_index => $time_slot ) {
					$slot_key = $date_index . '_' . $time_index;
					$results[ $slot_key ] = [
						'date'  => $date_info['date'],
						'start' => $time_slot['start'],
						'end'   => $time_slot['end'],
						'names' => [],
					];
				}
			}
		}

		foreach ( $responses as $response ) {
			$response_data = get_post_meta( $response->ID, '_dame_sondage_responses', true );
			if ( ! empty( $response_data ) ) {
				foreach ( $response_data as $date_index => $time_slots ) {
					foreach ( $time_slots as $time_index => $value ) {
						if ( $value == '1' ) {
							$slot_key = $date_index . '_' . $time_index;
							if ( isset( $results[ $slot_key ] ) ) {
								$results[ $slot_key ]['names'][] = $response->post_title;
							}
						}
					}
				}
			}
		}

		$grouped_results = [];
		foreach ( $results as $details ) {
			$date_obj = new DateTime( $details['date'] );
			$formatted_date = date_i18n( 'l j F Y', $date_obj->getTimestamp() );
			$grouped_results[ $formatted_date ][ $details['start'] . ' - ' . $details['end'] ] = $details['names'];
		}
		?>
		<style>
			.sondage-results-table { width: 100%; border-collapse: collapse; }
			.sondage-results-table th, .sondage-results-table td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
			.sondage-results-table th { background-color: #f2f2f2; }
			.sondage-results-table ul { margin: 0; padding-left: 15px; }
		</style>
		<div class="sondage-results-wrapper">
			<h4><?php _e( 'Tableau récapitulatif', 'dame' ); ?></h4>
			<table class="sondage-results-table">
				<thead>
					<tr>
						<th><?php _e( 'Plage Horaire', 'dame' ); ?></th>
						<?php foreach ( array_keys( $grouped_results ) as $date_header ) : ?>
							<th><?php echo esc_html( $date_header ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$all_time_slots = [];
					foreach ( $grouped_results as $slots ) {
						foreach ( $slots as $slot_time => $names ) {
							$all_time_slots[ $slot_time ] = true;
						}
					}
					ksort( $all_time_slots );

					foreach ( array_keys( $all_time_slots ) as $slot_time ) : ?>
						<tr>
							<td><?php echo esc_html( $slot_time ); ?></td>
							<?php foreach ( $grouped_results as $slots ) : ?>
								<td>
									<?php if ( isset( $slots[ $slot_time ] ) && ! empty( $slots[ $slot_time ] ) ) : ?>
										<ul>
											<?php foreach ( $slots[ $slot_time ] as $name ) : ?>
												<li><?php echo esc_html( $name ); ?></li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<hr style="margin: 20px 0;">

			<h4><?php _e( 'Liste des participants', 'dame' ); ?></h4>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php _e( 'Nom', 'dame' ); ?></th>
						<th><?php _e( 'Action', 'dame' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $responses as $response ) : ?>
						<?php
							$delete_url = admin_url( 'admin-post.php' );
							$delete_url = add_query_arg( [
								'action'      => 'dame_delete_sondage_response',
								'response_id' => $response->ID,
								'sondage_id'  => $post->ID,
								'_wpnonce'    => wp_create_nonce( 'dame_delete_response_' . $response->ID ),
							], $delete_url );
						?>
						<tr>
							<td><?php echo esc_html( $response->post_title ); ?></td>
							<td>
								<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small button-danger" onclick="return confirm('<?php _e( 'Êtes-vous sûr de vouloir supprimer cette réponse ?', 'dame' ); ?>');">
									<?php _e( 'Supprimer', 'dame' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Save metabox data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ) {
		$responses_exist = (bool) get_posts( [
			'post_type'      => 'dame_sondage_reponse',
			'post_parent'    => $post_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );

		if ( $responses_exist ) return;

		if ( ! isset( $_POST['dame_sondage_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['dame_sondage_metabox_nonce'], 'dame_save_sondage_metabox_data' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		if ( ! isset( $_POST['_dame_sondage_data'] ) ) {
			delete_post_meta( $post_id, '_dame_sondage_data' );
			return;
		}

		$sondage_data = [];
		foreach ( $_POST['_dame_sondage_data'] as $date_group ) {
			if ( ! empty( $date_group['date'] ) ) {
				$new_group = [ 'date' => sanitize_text_field( $date_group['date'] ), 'time_slots' => [] ];
				if ( ! empty( $date_group['time_slots'] ) ) {
					foreach ( $date_group['time_slots'] as $time_slot ) {
						if ( ! empty( $time_slot['start'] ) && ! empty( $time_slot['end'] ) ) {
							$new_group['time_slots'][] = [
								'start' => sanitize_text_field( $time_slot['start'] ),
								'end'   => sanitize_text_field( $time_slot['end'] ),
							];
						}
					}
				}
				$sondage_data[] = $new_group;
			}
		}

		if ( ! empty( $sondage_data ) ) {
			update_post_meta( $post_id, '_dame_sondage_data', $sondage_data );
		} else {
			delete_post_meta( $post_id, '_dame_sondage_data' );
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook Current hook.
	 */
	public function enqueue_scripts( $hook ) {
		global $post;
		if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && 'dame_sondage' === $post->post_type ) {
			// Note: Assuming assets/js/sondage-admin.js exists or will be handled.
			// Legacy code used: plugin_dir_url( __FILE__ ) . '../js/sondage-admin.js'
			// New structure: assets/js/sondage-admin.js should be available.
			wp_enqueue_script(
				'dame-sondage-admin-js',
				DAME_PLUGIN_URL . 'assets/js/sondage-admin.js',
				[ 'jquery' ],
				DAME_VERSION,
				true
			);
		}
	}

	/**
	 * Handle deletion of a response via admin-post.
	 */
	public function handle_delete_response() {
		if ( ! isset( $_GET['action'], $_GET['response_id'], $_GET['_wpnonce'] ) || 'dame_delete_sondage_response' !== $_GET['action'] ) {
			return;
		}

		$response_id = intval( $_GET['response_id'] );
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'dame_delete_response_' . $response_id ) ) {
			wp_die( __( 'Security check failed.', 'dame' ) );
		}

		if ( ! current_user_can( 'delete_post', $response_id ) ) {
			wp_die( __( 'You do not have permission to delete this response.', 'dame' ) );
		}

		wp_trash_post( $response_id );

		$sondage_id = isset( $_GET['sondage_id'] ) ? intval( $_GET['sondage_id'] ) : 0;
		$redirect_url = $sondage_id ? get_edit_post_link( $sondage_id, 'raw' ) : remove_query_arg( [ 'action', 'response_id', '_wpnonce', 'sondage_id' ], wp_get_referer() );
		$redirect_url = add_query_arg( 'message', '101', $redirect_url );

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Admin notices.
	 */
	public function admin_notices( $messages ) {
		if ( isset( $_GET['message'] ) && '101' === $_GET['message'] ) {
			$messages['post'][101] = __( 'Réponse supprimée.', 'dame' );
		}
		return $messages;
	}
}
