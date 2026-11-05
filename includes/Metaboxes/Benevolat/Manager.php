<?php
/**
 * Metabox Manager for Benevolat CPT
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Benevolat;

/**
 * Class Manager
 */
class Manager {

	/**
	 * Initialize the metabox hooks.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_benevolat', array( $this, 'save' ) );
		add_action( 'admin_post_dame_delete_benevolat_response', array( $this, 'delete_response' ) );
		add_filter( 'post_updated_messages', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'edit_form_before_permalink', array( $this, 'display_shortcode_helper' ) );
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

		wp_enqueue_script(
			'dame-admin-benevolat',
			\DAME_PLUGIN_URL . 'assets/js/admin-benevolat.js',
			array(),
			\DAME_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}

	/**
	 * Add meta boxes for benevolat configuration.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'dame_benevolat_config',
			__( 'Configuration de l\'appel à bénévoles', 'dame' ),
			array( $this, 'render_config' ),
			'benevolat',
			'normal',
			'high'
		);
		add_meta_box(
			'dame_benevolat_results',
			__( 'Résultats', 'dame' ),
			array( $this, 'render_results' ),
			'benevolat',
			'normal',
			'high'
		);
		add_meta_box(
			'dame_benevolat_article_sync',
			__( 'Flux d\'actualités & Article lié', 'dame' ),
			array( $this, 'render_article_sync' ),
			'benevolat',
			'side',
			'default'
		);
		add_meta_box(
			'dame_benevolat_linked_agenda',
			__( 'Événement Agenda associé', 'dame' ),
			array( $this, 'render_linked_agenda' ),
			'benevolat',
			'side',
			'default'
		);
	}

	/**
	 * Render the meta box for article synchronization.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render_article_sync( \WP_Post $post ): void {
		$options      = get_option( 'dame_options', array() );
		$options      = is_array( $options ) ? $options : array();
		$default_auto = ! empty( $options['benevolat_auto_publish'] );

		$meta_sync = get_post_meta( $post->ID, '_dame_benevolat_sync_article', true );
		$is_sync   = '' === $meta_sync ? $default_auto : (bool) $meta_sync;

		$selected_cat = (int) get_post_meta( $post->ID, '_dame_benevolat_category', true );
		if ( $selected_cat <= 0 ) {
			$selected_cat = isset( $options['benevolat_default_category'] ) ? (int) $options['benevolat_default_category'] : (int) get_option( 'default_category' );
		}

		$linked_article_id = (int) get_post_meta( $post->ID, '_dame_linked_article_id', true );
		$linked_article    = $linked_article_id > 0 ? get_post( $linked_article_id ) : null;
		$article_valid     = $linked_article instanceof \WP_Post && 'post' === $linked_article->post_type;
		?>
		<div class="dame-benevolat-article-sync-box">
			<p>
				<label>
					<input type="checkbox" name="_dame_benevolat_sync_article" value="1" <?php checked( $is_sync ); ?> />
					<strong><?php esc_html_e( 'Publier dans le flux d\'articles', 'dame' ); ?></strong>
				</label>
			</p>

			<p>
				<label for="dame_benevolat_category"><strong><?php esc_html_e( 'Catégorie de l\'article :', 'dame' ); ?></strong></label><br>
				<?php
				wp_dropdown_categories(
					array(
						'name'              => '_dame_benevolat_category',
						'id'                => 'dame_benevolat_category',
						'selected'          => $selected_cat,
						'hierarchical'      => 1,
						'show_option_none'  => __( '— Catégorie par défaut —', 'dame' ),
						'option_none_value' => '0',
						'hide_empty'        => 0,
						'class'             => 'widefat',
					)
				);
				?>
			</p>

			<?php if ( $article_valid && $linked_article ) : ?>
				<hr style="margin: 15px 0 10px 0;">
				<p style="margin: 0 0 5px 0;">
					<strong><?php esc_html_e( 'Article lié :', 'dame' ); ?></strong>
					<?php
					$status_obj   = get_post_status_object( (string) $linked_article->post_status );
					$status_label = $status_obj ? $status_obj->label : $linked_article->post_status;
					?>
					<span class="dame-status-badge" style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
						<?php echo esc_html( (string) $status_label ); ?>
					</span>
				</p>
				<p style="margin: 0; font-size: 12px;">
					<?php if ( 'publish' === $linked_article->post_status ) : ?>
						<a href="<?php echo esc_url( (string) get_permalink( $linked_article->ID ) ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Voir l\'article en ligne', 'dame' ); ?> <span class="dashicons dashicons-external" style="font-size: 14px; vertical-align: middle;"></span>
						</a>
						<span style="color: #ccd0d4;"> | </span>
					<?php endif; ?>
					<a href="<?php echo esc_url( (string) get_edit_post_link( $linked_article->ID ) ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Modifier l\'article', 'dame' ); ?>
					</a>
				</p>
			<?php endif; ?>
			<p class="description" style="margin-top: 10px;">
				<?php esc_html_e( 'L\'article est synchronisé automatiquement lors de la publication ou de la mise à jour de cet appel.', 'dame' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the meta box for linking an Agenda event.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render_linked_agenda( \WP_Post $post ): void {
		$linked_agenda_id = (int) get_post_meta( $post->ID, '_dame_linked_agenda_id', true );

		$agenda_events = get_posts(
			array(
				'post_type'      => 'dame_agenda',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'meta_key'       => '_dame_start_date',
				'orderby'        => 'meta_value',
				'order'          => 'DESC',
			)
		);
		?>
		<div class="dame-benevolat-linked-agenda-box">
			<p>
				<label for="_dame_linked_agenda_id"><strong><?php esc_html_e( 'Événement de l\'Agenda :', 'dame' ); ?></strong></label><br>
				<select name="_dame_linked_agenda_id" id="_dame_linked_agenda_id" class="widefat" style="margin-top: 5px;">
					<option value="0"><?php esc_html_e( '— Aucun événement associé —', 'dame' ); ?></option>
					<?php foreach ( $agenda_events as $event ) : ?>
						<?php
						$start_date = (string) get_post_meta( $event->ID, '_dame_start_date', true );
						$date_label = ! empty( $start_date ) ? ' (' . $start_date . ')' : '';
						?>
						<option value="<?php echo esc_attr( (string) $event->ID ); ?>" <?php selected( (int) $event->ID, $linked_agenda_id ); ?>>
							<?php echo esc_html( (string) $event->post_title . $date_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<?php if ( $linked_agenda_id > 0 ) : ?>
				<?php
				$linked_event = get_post( $linked_agenda_id );
				if ( $linked_event ) :
					$start_date = (string) get_post_meta( $linked_agenda_id, '_dame_start_date', true );
					$location   = (string) get_post_meta( $linked_agenda_id, '_dame_location_name', true );
					?>
					<div style="background: #f0f6fc; border-left: 3px solid #2271b1; padding: 8px 10px; margin-top: 10px; font-size: 12px;">
						<strong><?php echo esc_html( (string) $linked_event->post_title ); ?></strong>
						<?php if ( ! empty( $start_date ) ) : ?>
							<br>📅 <?php echo esc_html( $start_date ); ?>
						<?php endif; ?>
						<?php if ( ! empty( $location ) ) : ?>
							<br>📍 <?php echo esc_html( $location ); ?>
						<?php endif; ?>
						<p style="margin: 8px 0 0 0;">
							<a href="<?php echo esc_url( get_edit_post_link( $linked_agenda_id ) ?? '' ); ?>" target="_blank" class="button button-small">
								<?php esc_html_e( 'Modifier l\'événement ↗', 'dame' ); ?>
							</a>
						</p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<p class="description" style="margin-top: 8px;">
				<?php esc_html_e( 'Relier cet appel à un événement permet de l\'afficher automatiquement sur la fiche de l\'agenda.', 'dame' ); ?>
			</p>
		</div>
		<?php
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
			<strong><?php esc_html_e( 'Shortcode :', 'dame' ); ?></strong>
			<code style="user-select: all; cursor: pointer; background: #fff; border: 1px solid #ccd0d4; padding: 3px 8px;" title="<?php esc_attr_e( 'Cliquer pour sélectionner', 'dame' ); ?>">
				[dame_benevolat slug="<?php echo esc_attr( ! empty( $post->post_name ) ? (string) $post->post_name : 'votre-slug' ); ?>"]
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
			$benevolat_data = array();
		}

		$responses_exist = (bool) get_posts(
			array(
				'post_type'      => 'benevolat_reponse',
				'post_parent'    => $post->ID,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		$is_locked = $responses_exist;

		$user_id  = get_current_user_id();
		$list_url = $user_id ? (string) get_user_meta( $user_id, 'dame_last_benevolat_list_url', true ) : '';
		if ( empty( $list_url ) ) {
			$list_url = admin_url( 'edit.php?post_type=benevolat' );
		} else {
			$list_url = admin_url( ltrim( str_replace( '/wp-admin/', '', $list_url ), '/' ) );
		}

		?>
		<div class="dame-back-link-wrapper">
			<a href="<?php echo esc_url( $list_url ); ?>" class="dame-back-link">
				<span class="dashicons dashicons-arrow-left-alt"></span>
				<span><?php esc_html_e( 'Retour à la liste filtrée', 'dame' ); ?></span>
			</a>
		</div>

		<div id="benevolat-config-container">
			<?php if ( $is_locked ) : ?>
				<p><strong><?php esc_html_e( 'Cet appel est verrouillé car il a déjà reçu des réponses. Vous ne pouvez plus modifier les dates ou les plages horaires.', 'dame' ); ?></strong></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Ajoutez les dates et les plages horaires pour cet appel à bénévoles.', 'dame' ); ?></p>
			<?php endif; ?>

			<div id="benevolat-dates-wrapper">
				<?php
				if ( ! empty( $benevolat_data ) ) {
					foreach ( $benevolat_data as $date_key => $date_info ) {
						?>
						<div class="benevolat-date-group">
							<hr>
							<?php /* translators: %d: date index */ ?>
							<h4><?php echo esc_html( sprintf( __( 'Date %d', 'dame' ), $date_key + 1 ) ); ?></h4>
							<p>
								<label for="benevolat_date_<?php echo esc_attr( (string) $date_key ); ?>"><?php esc_html_e( 'Date:', 'dame' ); ?></label>
								<input type="date" id="benevolat_date_<?php echo esc_attr( (string) $date_key ); ?>" name="_dame_benevolat_data[<?php echo esc_attr( (string) $date_key ); ?>][date]" value="<?php echo esc_attr( $date_info['date'] ); ?>" class="benevolat-date-input" <?php disabled( $is_locked ); ?>>
								<?php if ( ! $is_locked ) : ?>
									<button type="button" class="button remove-benevolat-date"><?php esc_html_e( 'Supprimer cette date', 'dame' ); ?></button>
								<?php endif; ?>
							</p>
							<div class="benevolat-time-slots-wrapper">
								<?php
								if ( ! empty( $date_info['time_slots'] ) ) {
									foreach ( $date_info['time_slots'] as $time_key => $time_slot ) {
										?>
										<div class="benevolat-time-slot-group">
											<label><?php esc_html_e( 'Plage horaire:', 'dame' ); ?></label>
											<input type="time" name="_dame_benevolat_data[<?php echo esc_attr( (string) $date_key ); ?>][time_slots][<?php echo esc_attr( (string) $time_key ); ?>][start]" value="<?php echo esc_attr( $time_slot['start'] ); ?>" step="900" <?php disabled( $is_locked ); ?>>
											<span>-</span>
											<input type="time" name="_dame_benevolat_data[<?php echo esc_attr( (string) $date_key ); ?>][time_slots][<?php echo esc_attr( (string) $time_key ); ?>][end]" value="<?php echo esc_attr( $time_slot['end'] ); ?>" step="900" <?php disabled( $is_locked ); ?>>
											<label style="margin-left: 10px;"><?php esc_html_e( 'Bénévoles souhaités :', 'dame' ); ?></label>
											<input type="number" min="0" step="1" name="_dame_benevolat_data[<?php echo esc_attr( (string) $date_key ); ?>][time_slots][<?php echo esc_attr( (string) $time_key ); ?>][needed]" value="<?php echo esc_attr( isset( $time_slot['needed'] ) && '' !== (string) $time_slot['needed'] ? (string) $time_slot['needed'] : '' ); ?>" placeholder="0" class="benevolat-time-needed" style="width: 70px;" <?php disabled( $is_locked ); ?>>
											<?php if ( ! $is_locked ) : ?>
												<button type="button" class="button remove-benevolat-time-slot"><?php esc_html_e( 'Supprimer', 'dame' ); ?></button>
											<?php endif; ?>
										</div>
										<?php
									}
								}
								?>
							</div>
							<?php if ( ! $is_locked ) : ?>
								<button type="button" class="button add-benevolat-time-slot"><?php esc_html_e( 'Ajouter une plage horaire', 'dame' ); ?></button>
							<?php endif; ?>
						</div>
						<?php
					}
				}
				?>
			</div>
			<?php if ( ! $is_locked ) : ?>
				<p>
					<button type="button" id="add-benevolat-date" class="button button-primary"><?php esc_html_e( 'Ajouter une date', 'dame' ); ?></button>
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
			echo '<p>' . esc_html__( 'L\'appel n\'a pas encore été configuré.', 'dame' ) . '</p>';
			return;
		}

		$responses = get_posts(
			array(
				'post_type'      => 'benevolat_reponse',
				'post_status'    => 'publish',
				'post_parent'    => $post->ID,
				'posts_per_page' => -1,
				'orderby'        => 'post_title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $responses ) ) {
			echo '<p>' . esc_html__( 'Aucune réponse pour le moment.', 'dame' ) . '</p>';
			return;
		}

		// Prepare data for the results table.
		$results = array();

		foreach ( $benevolat_data as $date_index => $date_info ) {
			$date_str = $date_info['date'];
			if ( ! empty( $date_info['time_slots'] ) ) {
				foreach ( $date_info['time_slots'] as $time_index => $time_slot ) {
					$slot_key             = $date_index . '_' . $time_index;
					$results[ $slot_key ] = array(
						'date'   => $date_str,
						'start'  => $time_slot['start'],
						'end'    => $time_slot['end'],
						'needed' => isset( $time_slot['needed'] ) ? (int) $time_slot['needed'] : 0,
						'names'  => array(),
					);
				}
			}
		}

		global $wpdb;
		$table_votes = $wpdb->prefix . 'dame_benevolat_votes';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$vote_records = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare( "SELECT DISTINCT v.recipient_id, v.choice_key FROM {$table_votes} v INNER JOIN {$wpdb->posts} p ON v.recipient_id = p.ID WHERE v.poll_id = %d AND p.post_status = 'publish'", $post->ID )
		);

		$response_titles = array();
		foreach ( $responses as $resp ) {
			$response_titles[ $resp->ID ] = $resp->post_title;
		}

		foreach ( $vote_records as $v ) {
			$slot_key = $v->choice_key;
			if ( isset( $results[ $slot_key ] ) && isset( $response_titles[ $v->recipient_id ] ) ) {
				$results[ $slot_key ]['names'][] = $response_titles[ $v->recipient_id ];
			}
		}

		$grouped_results = array();
		foreach ( $results as $slot_key => $details ) {
			$date_obj       = new \DateTime( $details['date'] );
			$formatted_date = date_i18n( 'l j F Y', $date_obj->getTimestamp() );
			$grouped_results[ $formatted_date ][ $details['start'] . ' - ' . $details['end'] ] = array(
				'names'  => $details['names'],
				'needed' => $details['needed'],
			);
		}
		?>
		<div class="benevolat-results-wrapper">
			<h4><?php esc_html_e( 'Tableau récapitulatif', 'dame' ); ?></h4>
			<table class="benevolat-results-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Plage Horaire', 'dame' ); ?></th>
						<?php foreach ( array_keys( $grouped_results ) as $date_header ) : ?>
							<th><?php echo esc_html( $date_header ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$all_time_slots = array();
					foreach ( $grouped_results as $date => $slots ) {
						foreach ( $slots as $slot_time => $names ) {
							$all_time_slots[ $slot_time ] = true;
						}
					}
					ksort( $all_time_slots );

					foreach ( array_keys( $all_time_slots ) as $slot_time ) :
						?>
						<tr>
							<td><?php echo esc_html( $slot_time ); ?></td>
							<?php foreach ( $grouped_results as $date => $slots ) : ?>
								<td>
									<?php if ( isset( $slots[ $slot_time ] ) ) : ?>
										<?php
										$slot_data = $slots[ $slot_time ];
										$count     = count( $slot_data['names'] );
										$needed    = $slot_data['needed'];
										?>
										<div class="benevolat-slot-recap-header" style="font-size: 11px; margin-bottom: 4px; font-weight: 600;">
											<?php if ( $needed > 0 ) : ?>
												<span style="color: <?php echo $count >= $needed ? '#2271b1' : '#d63638'; ?>;">
													<?php
													/* translators: 1: registered count, 2: needed count */
													echo esc_html( sprintf( __( '%1$d / %2$d souhaité(s)', 'dame' ), $count, $needed ) );
													?>
												</span>
											<?php else : ?>
												<span style="color: #646970;">
													<?php
													/* translators: %d: number of registered volunteers. */
													echo esc_html( sprintf( _n( '%d inscrit', '%d inscrits', $count, 'dame' ), $count ) );
													?>
												</span>
											<?php endif; ?>
										</div>
										<?php if ( ! empty( $slot_data['names'] ) ) : ?>
											<ul style="margin: 0; padding-left: 15px;">
												<?php foreach ( $slot_data['names'] as $name ) : ?>
													<li><?php echo esc_html( $name ); ?></li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<hr style="margin: 20px 0;">

			<h4><?php esc_html_e( 'Liste des participants', 'dame' ); ?></h4>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Nom', 'dame' ); ?></th>
						<th><?php esc_html_e( 'Action', 'dame' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $responses as $response ) : ?>
						<?php
							$delete_url = admin_url( 'admin-post.php' );
							$delete_url = add_query_arg(
								array(
									'action'       => 'dame_delete_benevolat_response',
									'response_id'  => $response->ID,
									'benevolat_id' => $post->ID,
									'_wpnonce'     => wp_create_nonce( 'dame_delete_response_' . $response->ID ),
								),
								$delete_url
							);
						?>
						<tr>
							<td><?php echo esc_html( $response->post_title ); ?></td>
							<td>
								<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small button-danger" data-confirm="<?php esc_attr_e( 'Êtes-vous sûr de vouloir supprimer cette réponse ?', 'dame' ); ?>">
									<?php esc_html_e( 'Supprimer', 'dame' ); ?>
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
		if ( ! isset( $_POST['dame_benevolat_metabox_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['dame_benevolat_metabox_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'dame_save_benevolat_metabox_data' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 1. Save Article synchronization settings.
		if ( isset( $_POST['_dame_benevolat_sync_article'] ) ) {
			update_post_meta( $post_id, '_dame_benevolat_sync_article', 1 );
		} else {
			update_post_meta( $post_id, '_dame_benevolat_sync_article', 0 );
		}

		if ( isset( $_POST['_dame_benevolat_category'] ) ) {
			update_post_meta( $post_id, '_dame_benevolat_category', (int) $_POST['_dame_benevolat_category'] );
		}

		// 2. Save Dates and Timeslots only if not locked by existing responses.
		$responses_exist = (bool) get_posts(
			array(
				'post_type'      => 'benevolat_reponse',
				'post_parent'    => $post_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! $responses_exist ) {
			if ( ! isset( $_POST['_dame_benevolat_data'] ) ) {
				delete_post_meta( $post_id, '_dame_benevolat_data' );
			} else {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$raw_benevolat_data = is_array( $_POST['_dame_benevolat_data'] ) ? wp_unslash( $_POST['_dame_benevolat_data'] ) : array();
				$benevolat_data     = array();
				foreach ( (array) $raw_benevolat_data as $date_group ) {
					if ( ! empty( $date_group['date'] ) ) {
						$new_date_group = array(
							'date'       => sanitize_text_field( $date_group['date'] ),
							'time_slots' => array(),
						);
						if ( ! empty( $date_group['time_slots'] ) ) {
							foreach ( $date_group['time_slots'] as $time_slot ) {
								if ( ! empty( $time_slot['start'] ) && ! empty( $time_slot['end'] ) ) {
									$slot_data = array(
										'start' => sanitize_text_field( $time_slot['start'] ),
										'end'   => sanitize_text_field( $time_slot['end'] ),
									);
									if ( isset( $time_slot['needed'] ) && '' !== (string) $time_slot['needed'] ) {
										$slot_data['needed'] = max( 0, (int) $time_slot['needed'] );
									} else {
										$slot_data['needed'] = 0;
									}
									$new_date_group['time_slots'][] = $slot_data;
								}
							}
						}
						usort(
							$new_date_group['time_slots'],
							function ( $a, $b ) {
								return strcmp( $a['start'], $b['start'] );
							}
						);

						$benevolat_data[] = $new_date_group;
					}
				}

				usort(
					$benevolat_data,
					function ( $a, $b ) {
						return strcmp( $a['date'], $b['date'] );
					}
				);

				if ( ! empty( $benevolat_data ) ) {
					update_post_meta( $post_id, '_dame_benevolat_data', $benevolat_data );
				} else {
					delete_post_meta( $post_id, '_dame_benevolat_data' );
				}
			}
		}

		// 3. Save Linked Agenda event.
		if ( isset( $_POST['_dame_linked_agenda_id'] ) ) {
			$agenda_id     = absint( $_POST['_dame_linked_agenda_id'] );
			$old_agenda_id = (int) get_post_meta( $post_id, '_dame_linked_agenda_id', true );

			if ( $agenda_id > 0 ) {
				update_post_meta( $post_id, '_dame_linked_agenda_id', $agenda_id );
				update_post_meta( $agenda_id, '_dame_linked_benevolat_id', $post_id );

				if ( $old_agenda_id > 0 && $old_agenda_id !== $agenda_id ) {
					$prev_linked = (int) get_post_meta( $old_agenda_id, '_dame_linked_benevolat_id', true );
					if ( $prev_linked === $post_id ) {
						delete_post_meta( $old_agenda_id, '_dame_linked_benevolat_id' );
					}
				}
			} else {
				delete_post_meta( $post_id, '_dame_linked_agenda_id' );
				if ( $old_agenda_id > 0 ) {
					$prev_linked = (int) get_post_meta( $old_agenda_id, '_dame_linked_benevolat_id', true );
					if ( $prev_linked === $post_id ) {
						delete_post_meta( $old_agenda_id, '_dame_linked_benevolat_id' );
					}
				}
			}
		}

		// 4. Trigger synchronization with linked article.
		$post = get_post( $post_id );
		if ( $post instanceof \WP_Post ) {
			( new \DAME\Services\BenevolatArticleSync() )->sync( $post_id, $post );
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
		$raw_nonce   = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

		if ( ! wp_verify_nonce( $raw_nonce, 'dame_delete_response_' . $response_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dame' ) );
		}

		if ( ! current_user_can( 'delete_post', $response_id ) ) {
			wp_die( esc_html__( 'You do not have permission to delete this response.', 'dame' ) );
		}

		wp_trash_post( $response_id );

		$benevolat_id = isset( $_GET['benevolat_id'] ) ? intval( $_GET['benevolat_id'] ) : 0;

		if ( $benevolat_id ) {
			$redirect_url = get_edit_post_link( $benevolat_id, 'raw' );
			$redirect_url = add_query_arg( 'message', '101', $redirect_url );
		} else {
			$redirect_url = remove_query_arg( array( 'action', 'response_id', '_wpnonce', 'benevolat_id' ), wp_get_referer() );
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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['message'] ) && '101' === $_GET['message'] ) {
			$messages['post'][101] = __( 'Réponse supprimée.', 'dame' );
		}
		return $messages;
	}
}
