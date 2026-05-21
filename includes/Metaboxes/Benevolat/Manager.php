<?php
/**
 * Metabox Manager for Benevolat CPT
 *
 * @package DAME
 */

namespace DAME\Metaboxes\Benevolat;

/**
 * Class Manager
 */
class Manager {

	/**
	 * Initialize the metabox hooks.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_benevolat', [ $this, 'save' ] );
		add_action( 'admin_post_dame_delete_benevolat_response', [ $this, 'delete_response' ] );
		add_filter( 'post_updated_messages', [ $this, 'admin_notices' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'edit_form_before_permalink', [ $this, 'display_shortcode_helper' ] );
	}

	/**
	 * Enqueue scripts for the benevolat admin.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ): void {
		$screen = get_current_screen();

		if ( ! $screen || 'benevolat' !== $screen->post_type ) {
			return;
		}

		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'dame-admin-benevolat', \DAME_PLUGIN_URL . 'assets/js/admin-benevolat.js', array( 'jquery' ), \DAME_VERSION, true );
	}

	/**
	 * Add meta boxes for benevolat configuration.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'dame_benevolat_config',
			__( 'Configuration de l\'appel à bénévoles', 'dame' ),
			[ $this, 'render_config' ],
			'benevolat',
			'normal',
			'high'
		);
		add_meta_box(
			'dame_benevolat_results',
			__( 'Résultats', 'dame' ),
			[ $this, 'render_results' ],
			'benevolat',
			'normal',
			'high'
		);
	}

	/**
	 * Display a shortcode helper before the permalink.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function display_shortcode_helper( $post ): void {
		if ( 'benevolat' !== $post->post_type ) {
			return;
		}
		?>
		<div class="dame-shortcode-helper-inline" style="margin: 10px 0 5px 0; font-size: 13px;">
			<span class="dashicons dashicons-shortcode" style="font-size: 18px; vertical-align: middle;"></span>
			<strong><?php _e( 'Shortcode :', 'dame' ); ?></strong>
			<code style="user-select: all; cursor: pointer; background: #fff; border: 1px solid #ccd0d4; padding: 3px 8px;" title="<?php esc_attr_e( 'Cliquer pour sélectionner', 'dame' ); ?>">
				[dame_benevolat slug="<?php echo esc_attr( (string) $post->post_name ?: 'votre-slug' ); ?>"]
			</code>
		</div>
		<?php
	}

	/**
	 * Render the meta box for configuration.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render_config( $post ): void {
		wp_nonce_field( 'dame_save_benevolat_metabox_data', 'dame_benevolat_metabox_nonce' );

		$benevolat_data = get_post_meta( $post->ID, '_dame_benevolat_data', true );
		if ( empty( $benevolat_data ) ) {
			$benevolat_data = [];
		}

		$responses_exist = (bool) get_posts( [
			'post_type'      => 'benevolat_reponse',
			'post_parent'    => $post->ID,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );

		$is_locked = $responses_exist;
		?>
		<div id="benevolat-config-container">
			<?php if ( $is_locked ) : ?>
				<p><strong><?php _e( 'Cet appel est verrouillé car il a déjà reçu des réponses. Vous ne pouvez plus modifier les dates ou les plages horaires.', 'dame' ); ?></strong></p>
			<?php else : ?>
				<p><?php _e( 'Ajoutez les dates et les plages horaires pour cet appel à bénévoles.', 'dame' ); ?></p>
			<?php endif; ?>

			<div id="benevolat-dates-wrapper">
				<?php
				if ( ! empty( $benevolat_data ) ) {
					foreach ( $benevolat_data as $date_key => $date_info ) {
						?>
						<div class="benevolat-date-group">
							<hr>
							<h4><?php echo esc_html( sprintf( __( 'Date %d', 'dame' ), $date_key + 1 ) ); ?></h4>
							<p>
								<label for="benevolat_date_<?php echo esc_attr( (string) $date_key ); ?>"><?php _e( 'Date:', 'dame' ); ?></label>
								<input type="date" id="benevolat_date_<?php echo esc_attr( (string) $date_key ); ?>" name="_dame_benevolat_data[<?php echo esc_attr( (string) $date_key ); ?>][date]" value="<?php echo esc_attr( $date_info['date'] ); ?>" class="benevolat-date-input" <?php disabled( $is_locked ); ?>>
								<?php if ( ! $is_locked ) : ?>
									<button type="button" class="button remove-benevolat-date"><?php _e( 'Supprimer cette date', 'dame' ); ?></button>
								<?php endif; ?>
							</p>
							<div class="benevolat-time-slots-wrapper">
								<?php
								if ( ! empty( $date_info['time_slots'] ) ) {
									foreach ( $date_info['time_slots'] as $time_key => $time_slot ) {
										?>
										<div class="benevolat-time-slot-group">
											<label><?php _e( 'Plage horaire:', 'dame' ); ?></label>
											<input type="time" name="_dame_benevolat_data[<?php echo esc_attr( (string) $date_key ); ?>][time_slots][<?php echo esc_attr( (string) $time_key ); ?>][start]" value="<?php echo esc_attr( $time_slot['start'] ); ?>" step="900" <?php disabled( $is_locked ); ?>>
											<span>-</span>
											<input type="time" name="_dame_benevolat_data[<?php echo esc_attr( (string) $date_key ); ?>][time_slots][<?php echo esc_attr( (string) $time_key ); ?>][end]" value="<?php echo esc_attr( $time_slot['end'] ); ?>" step="900" <?php disabled( $is_locked ); ?>>
											<?php if ( ! $is_locked ) : ?>
												<button type="button" class="button remove-benevolat-time-slot"><?php _e( 'Supprimer', 'dame' ); ?></button>
											<?php endif; ?>
										</div>
										<?php
									}
								}
								?>
							</div>
							<?php if ( ! $is_locked ) : ?>
								<button type="button" class="button add-benevolat-time-slot"><?php _e( 'Ajouter une plage horaire', 'dame' ); ?></button>
							<?php endif; ?>
						</div>
						<?php
					}
				}
				?>
			</div>
			<?php if ( ! $is_locked ) : ?>
				<p>
					<button type="button" id="add-benevolat-date" class="button button-primary"><?php _e( 'Ajouter une date', 'dame' ); ?></button>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the meta box for results.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render_results( $post ): void {
		$benevolat_data = get_post_meta( $post->ID, '_dame_benevolat_data', true );
		if ( empty( $benevolat_data ) ) {
			echo '<p>' . __( 'L\'appel n\'a pas encore été configuré.', 'dame' ) . '</p>';
			return;
		}

		$responses = get_posts( [
			'post_type'      => 'benevolat_reponse',
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

		// Prepare data for the results table
		$results = [];

		foreach ( $benevolat_data as $date_index => $date_info ) {
			$date_str = $date_info['date'];
			if ( ! empty( $date_info['time_slots'] ) ) {
				foreach ( $date_info['time_slots'] as $time_index => $time_slot ) {
					$slot_key = $date_index . '_' . $time_index;
					$results[ $slot_key ] = [
						'date'  => $date_str,
						'start' => $time_slot['start'],
						'end'   => $time_slot['end'],
						'names' => [],
					];
				}
			}
		}

		global $wpdb;
		$table_votes = $wpdb->prefix . 'dame_benevolat_votes';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$vote_records = $wpdb->get_results( $wpdb->prepare(
			"SELECT DISTINCT v.recipient_id, v.choice_key 
			 FROM {$table_votes} v
			 INNER JOIN {$wpdb->posts} p ON v.recipient_id = p.ID
			 WHERE v.poll_id = %d AND p.post_status = 'publish'",
			$post->ID
		) );

		$response_titles = [];
		foreach ( $responses as $resp ) {
			$response_titles[ $resp->ID ] = $resp->post_title;
		}

		foreach ( $vote_records as $v ) {
			$slot_key = $v->choice_key;
			if ( isset( $results[ $slot_key ] ) && isset( $response_titles[ $v->recipient_id ] ) ) {
				$results[ $slot_key ]['names'][] = $response_titles[ $v->recipient_id ];
			}
		}

		$grouped_results = [];
		foreach ( $results as $slot_key => $details ) {
			$date_obj = new \DateTime( $details['date'] );
			$formatted_date = date_i18n( 'l j F Y', $date_obj->getTimestamp() );
			$grouped_results[ $formatted_date ][ $details['start'] . ' - ' . $details['end'] ] = $details['names'];
		}
		?>
		<style>
			.benevolat-results-table { width: 100%; border-collapse: collapse; }
			.benevolat-results-table th, .benevolat-results-table td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
			.benevolat-results-table th { background-color: #f2f2f2; }
			.benevolat-results-table ul { margin: 0; padding-left: 15px; }
		</style>
		<div class="benevolat-results-wrapper">
			<h4><?php _e( 'Tableau récapitulatif', 'dame' ); ?></h4>
			<table class="benevolat-results-table">
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
					foreach ( $grouped_results as $date => $slots ) {
						foreach ( $slots as $slot_time => $names ) {
							$all_time_slots[ $slot_time ] = true;
						}
					}
					ksort( $all_time_slots );

					foreach ( array_keys( $all_time_slots ) as $slot_time ) : ?>
						<tr>
							<td><?php echo esc_html( $slot_time ); ?></td>
							<?php foreach ( $grouped_results as $date => $slots ) : ?>
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
								'action'      => 'dame_delete_benevolat_response',
								'response_id' => $response->ID,
								'benevolat_id' => $post->ID,
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
	 * Save the meta box data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( $post_id ): void {
		$responses_exist = (bool) get_posts( [
			'post_type'      => 'benevolat_reponse',
			'post_parent'    => $post_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );

		if ( $responses_exist ) {
			return; 
		}

		if ( ! isset( $_POST['dame_benevolat_metabox_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['dame_benevolat_metabox_nonce'], 'dame_save_benevolat_metabox_data' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['_dame_benevolat_data'] ) ) {
			delete_post_meta( $post_id, '_dame_benevolat_data' );
			return;
		}

		$benevolat_data = [];
		foreach ( $_POST['_dame_benevolat_data'] as $date_group ) {
			if ( ! empty( $date_group['date'] ) ) {
				$new_date_group = [
					'date'       => sanitize_text_field( $date_group['date'] ),
					'time_slots' => [],
				];
				if ( ! empty( $date_group['time_slots'] ) ) {
					foreach ( $date_group['time_slots'] as $time_slot ) {
						if ( ! empty( $time_slot['start'] ) && ! empty( $time_slot['end'] ) ) {
							$new_date_group['time_slots'][] = [
								'start' => sanitize_text_field( $time_slot['start'] ),
								'end'   => sanitize_text_field( $time_slot['end'] ),
							];
						}
					}
				}
				usort( $new_date_group['time_slots'], function( $a, $b ) {
					return strcmp( $a['start'], $b['start'] );
				} );

				$benevolat_data[] = $new_date_group;
			}
		}

		usort( $benevolat_data, function( $a, $b ) {
			return strcmp( $a['date'], $b['date'] );
		} );

		if ( ! empty( $benevolat_data ) ) {
			update_post_meta( $post_id, '_dame_benevolat_data', $benevolat_data );
		} else {
			delete_post_meta( $post_id, '_dame_benevolat_data' );
		}
	}

	/**
	 * Handle the deletion of a response.
	 */
	public function delete_response(): void {
		if ( ! isset( $_GET['action'], $_GET['response_id'], $_GET['_wpnonce'] ) || 'dame_delete_benevolat_response' !== $_GET['action'] ) {
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

		$benevolat_id = isset( $_GET['benevolat_id'] ) ? intval( $_GET['benevolat_id'] ) : 0;

		if ( $benevolat_id ) {
			$redirect_url = get_edit_post_link( $benevolat_id, 'raw' );
			$redirect_url = add_query_arg( 'message', '101', $redirect_url );
		} else {
			$redirect_url = remove_query_arg( [ 'action', 'response_id', '_wpnonce', 'benevolat_id' ], wp_get_referer() );
			$redirect_url = add_query_arg( 'message', '101', $redirect_url );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Display custom admin notice.
	 *
	 * @param array<string, mixed> $messages Post updated messages.
	 * @return array<string, mixed>
	 */
	 public function admin_notices( array $messages ): array {

		if ( isset( $_GET['message'] ) && '101' === $_GET['message'] ) {
			$messages['post'][101] = __( 'Réponse supprimée.', 'dame' );
		}
		return $messages;
	}
}
