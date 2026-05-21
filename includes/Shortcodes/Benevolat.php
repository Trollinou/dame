<?php
/**
 * Shortcode for Benevolat
 *
 * @package DAME
 */

namespace DAME\Shortcodes;

use DateTime;

/**
 * Class Benevolat
 */
class Benevolat {

	/**
	 * Initialize shortcode hooks.
	 */
	public function init(): void {
		add_shortcode( 'dame_benevolat', [ $this, 'render' ] );
		add_action( 'admin_post_nopriv_dame_submit_benevolat', [ $this, 'handle_submission' ] );
		add_action( 'admin_post_dame_submit_benevolat', [ $this, 'handle_submission' ] );
	}

	/**
	 * Render the benevolat shortcode.
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			[
				'slug' => '',
			],
			is_array( $atts ) ? $atts : [],
			'dame_benevolat'
		);

		if ( empty( $atts['slug'] ) ) {
			return '<p>' . __( 'Erreur : Le slug est manquant.', 'dame' ) . '</p>';
		}

		$benevolat = get_page_by_path( $atts['slug'], OBJECT, 'benevolat' );

		if ( ! $benevolat ) {
			return '<p>' . __( 'Erreur : Appel à bénévoles non trouvé.', 'dame' ) . '</p>';
		}

		$benevolat_data = get_post_meta( $benevolat->ID, '_dame_benevolat_data', true );

		if ( empty( $benevolat_data ) || ! is_array( $benevolat_data ) ) {
			return '<p>' . __( 'Cet appel n\'a pas encore été configuré.', 'dame' ) . '</p>';
		}

		// Get all responses to calculate counts for each time slot via SQL
		global $wpdb;
		$table_votes = $wpdb->prefix . 'dame_benevolat_votes';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$vote_results = $wpdb->get_results( $wpdb->prepare(
			"SELECT v.choice_key, COUNT(DISTINCT v.recipient_id) as count 
			 FROM {$table_votes} v
			 INNER JOIN {$wpdb->posts} p ON v.recipient_id = p.ID
			 WHERE v.poll_id = %d AND p.post_status = 'publish'
			 GROUP BY v.choice_key",
			$benevolat->ID
		) );

		$response_counts = [];
		foreach ( $vote_results as $row ) {
			$parts = explode( '_', $row->choice_key );
			if ( count( $parts ) === 2 ) {
				$response_counts[ (int) $parts[0] ][ (int) $parts[1] ] = (int) $row->count;
			}
		}

		$current_user_id = get_current_user_id();
		$user_has_voted  = false;
		$user_responses  = [];

		if ( $current_user_id ) {
			$existing_response = get_posts( [
				'post_type'      => 'benevolat_reponse',
				'post_status'    => 'publish',
				'author'         => $current_user_id,
				'post_parent'    => $benevolat->ID,
				'posts_per_page' => 1,
			] );
			if ( ! empty( $existing_response ) ) {
				$user_has_voted = true;
				$user_responses = get_post_meta( $existing_response[0]->ID, '_dame_benevolat_responses', true );
			}
		} else {
			$cookie_name = 'dame_benevolat_response_' . $benevolat->ID;
			if ( isset( $_COOKIE[ $cookie_name ] ) ) {
				$guest_response_id = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
				$existing_response = get_posts( [
					'post_type'      => 'benevolat_reponse',
					'post_status'    => 'publish',
					'post_parent'    => $benevolat->ID,
					'posts_per_page' => 1,
					'meta_key'       => '_dame_guest_response_id',
					'meta_value'     => $guest_response_id,
				] );
				if ( ! empty( $existing_response ) ) {
					$user_has_voted = true;
					$user_responses = get_post_meta( $existing_response[0]->ID, '_dame_benevolat_responses', true );
				}
			}
		}

		if ( ! is_array( $user_responses ) ) {
			$user_responses = [];
		}

		$today = wp_date( 'Y-m-d' );

		ob_start();
		?>
		<style>
			.benevolat-timeslot-label.is-past { color: #888; opacity: 0.7; cursor: not-allowed; }
			.benevolat-date-row.is-past { background-color: #f9f9f9; }
		</style>
		<div class="dame-benevolat-wrapper">
			<h3><?php echo esc_html( (string) $benevolat->post_title ); ?></h3>
			<?php if ( ! empty( $benevolat->post_content ) ) : ?>
				<div class="benevolat-description">
					<?php echo wpautop( wp_kses_post( $benevolat->post_content ) ); ?>
				</div>
			<?php endif; ?>

			<form id="dame-benevolat-form-<?php echo esc_attr( (string) $benevolat->ID ); ?>" class="dame-benevolat-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dame_submit_benevolat">
				<input type="hidden" name="benevolat_id" value="<?php echo esc_attr( (string) $benevolat->ID ); ?>">
				<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ); ?>">
				<?php wp_nonce_field( 'dame_submit_benevolat_response_' . $benevolat->ID, 'dame_benevolat_nonce' ); ?>

				<p>
					<label for="benevolat_name"><?php _e( 'Votre nom :', 'dame' ); ?></label>
					<?php
					$current_user = wp_get_current_user();
					if ( is_user_logged_in() ) {
						$user_name = $current_user->display_name;
						echo '<input type="text" id="benevolat_name" name="benevolat_name" value="' . esc_attr( (string) $user_name ) . '" readonly required>';
					} else {
						$guest_name = ! empty( $existing_response ) ? $existing_response[0]->post_title : '';
						echo '<input type="text" id="benevolat_name" name="benevolat_name" value="' . esc_attr( (string) $guest_name ) . '" required>';
					}
					?>
				</p>

				<table class="dame-benevolat-table">
					<thead>
						<tr>
							<th><?php _e( 'Date', 'dame' ); ?></th>
							<th><?php _e( 'Disponibilités', 'dame' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $benevolat_data as $date_index => $date_info ) : ?>
							<?php
								$date_obj       = new DateTime( $date_info['date'] );
								$formatted_date = date_i18n( 'l j F Y', $date_obj->getTimestamp() );
								$is_locked      = $date_info['date'] <= $today;
							?>
							<tr class="benevolat-date-row <?php echo $is_locked ? 'is-past' : ''; ?>">
								<td>
									<?php echo esc_html( $formatted_date ); ?>
									<?php if ( $is_locked ) : ?>
										<br><small style="color: #d63638; font-style: italic;"><?php _e( '(Verrouillé)', 'dame' ); ?></small>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( ! empty( $date_info['time_slots'] ) ) : ?>
										<?php foreach ( $date_info['time_slots'] as $time_index => $time_slot ) : ?>
											<?php
											$checked = '';
											if ( isset( $user_responses[ $date_index ][ $time_index ] ) && '1' == $user_responses[ $date_index ][ $time_index ] ) {
												$checked = 'checked';
											}
											$count = isset( $response_counts[ $date_index ][ $time_index ] ) ? $response_counts[ $date_index ][ $time_index ] : 0;
											?>
											<label class="benevolat-timeslot-label <?php echo $is_locked ? 'is-past' : ''; ?>">
												<input type="checkbox" name="benevolat_responses[<?php echo esc_attr( (string) $date_index ); ?>][<?php echo esc_attr( (string) $time_index ); ?>]" value="1" <?php echo esc_attr( (string) $checked ); ?> <?php disabled( $is_locked ); ?>>
												<?php echo esc_html( $time_slot['start'] . ' - ' . $time_slot['end'] ); ?> (<?php printf( _n( '%d inscrit', '%d inscrits', $count, 'dame' ), $count ); ?>)
											</label>
										<?php endforeach; ?>
									<?php else : ?>
										<?php _e( 'Aucune plage horaire définie pour cette date.', 'dame' ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p>
					<input type="submit" name="submit_benevolat" value="<?php echo esc_attr( $user_has_voted ? __( 'Mettre à jour', 'dame' ) : __( 'S\'inscrire', 'dame' ) ); ?>">
					<?php if ( isset( $_GET['vote'] ) && 'success' === $_GET['vote'] ) : ?>
						<span class="benevolat-success-message-inline" style="margin-left: 10px; color: green;"><?php _e( 'Merci, votre réponse a été enregistrée.', 'dame' ); ?></span>
					<?php endif; ?>
				</p>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Handle benevolat form submission.
	 */
	public function handle_submission(): void {
		if ( ! isset( $_POST['submit_benevolat'], $_POST['dame_benevolat_nonce'] ) ) {
			return;
		}

		$benevolat_id = isset( $_POST['benevolat_id'] ) ? intval( $_POST['benevolat_id'] ) : 0;

		if ( ! $benevolat_id || ! wp_verify_nonce( $_POST['dame_benevolat_nonce'], 'dame_submit_benevolat_response_' . $benevolat_id ) ) {
			wp_die( 'Invalid nonce.' );
		}

		$name      = sanitize_text_field( wp_unslash( $_POST['benevolat_name'] ) );
		$responses = isset( $_POST['benevolat_responses'] ) ? (array) wp_unslash( $_POST['benevolat_responses'] ) : [];

		// 1. Get configuration to identify past dates
		$benevolat_data = get_post_meta( $benevolat_id, '_dame_benevolat_data', true );
		if ( ! is_array( $benevolat_data ) ) {
			$benevolat_data = [];
		}
		$today = wp_date( 'Y-m-d' );
		$past_date_indices = [];
		foreach ( $benevolat_data as $idx => $info ) {
			if ( $info['date'] <= $today ) {
				$past_date_indices[] = (int) $idx;
			}
		}

		// 2. Sanitize and filter NEW responses (ignore any manual injection for past dates)
		$sanitized_responses = [];
		foreach ( $responses as $date_index => $time_slots ) {
			$date_index = (int) $date_index;
			if ( in_array( $date_index, $past_date_indices, true ) ) {
				continue; // Skip any values submitted for past dates
			}
			foreach ( $time_slots as $time_index => $value ) {
				$sanitized_responses[ $date_index ][ (int) $time_index ] = 1;
			}
		}

		$user_id              = get_current_user_id();
		$existing_response_id = 0;
		$previous_meta        = [];

		if ( $user_id ) {
			$existing_responses = get_posts( [
				'post_type'      => 'benevolat_reponse',
				'post_status'    => 'publish',
				'author'         => $user_id,
				'post_parent'    => $benevolat_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			] );
			if ( ! empty( $existing_responses ) ) {
				$existing_response_id = $existing_responses[0];
				$previous_meta = get_post_meta( $existing_response_id, '_dame_benevolat_responses', true );
			}
		} else {
			$cookie_name = 'dame_benevolat_response_' . $benevolat_id;
			if ( isset( $_COOKIE[ $cookie_name ] ) ) {
				$guest_response_id  = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
				$existing_responses = get_posts( [
					'post_type'      => 'benevolat_reponse',
					'post_status'    => 'publish',
					'post_parent'    => $benevolat_id,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_dame_guest_response_id',
					'meta_value'     => $guest_response_id,
				] );
				if ( ! empty( $existing_responses ) ) {
					$existing_response_id = $existing_responses[0];
					$previous_meta = get_post_meta( $existing_response_id, '_dame_benevolat_responses', true );
				}
			}
		}

		// 3. Merge previous choices for PAST dates
		if ( ! empty( $previous_meta ) && is_array( $previous_meta ) ) {
			foreach ( $past_date_indices as $idx ) {
				if ( isset( $previous_meta[ $idx ] ) ) {
					$sanitized_responses[ $idx ] = $previous_meta[ $idx ];
				}
			}
		}

		$post_data = [
			'post_title'  => $name,
			'post_type'   => 'benevolat_reponse',
			'post_status' => 'publish',
			'post_parent' => $benevolat_id,
			'post_author' => $user_id,
		];

		if ( $existing_response_id ) {
			$post_data['ID'] = $existing_response_id;
			wp_update_post( $post_data );
			$response_id = $existing_response_id;
		} else {
			$response_id = wp_insert_post( $post_data );
		}

		if ( $response_id ) {
			update_post_meta( $response_id, '_dame_benevolat_responses', $sanitized_responses );

			// Sync with SQL table for real-time stats and race condition prevention
			global $wpdb;
			$table_votes = $wpdb->prefix . 'dame_benevolat_votes';
			
			// 1. Clear previous votes for this response
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->delete( $table_votes, [ 'poll_id' => $benevolat_id, 'recipient_id' => $response_id ] );

			// 2. Insert new votes
			foreach ( $sanitized_responses as $date_index => $time_slots ) {
				foreach ( $time_slots as $time_index => $value ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->insert( $table_votes, [
						'poll_id'      => $benevolat_id,
						'recipient_id' => $response_id,
						'choice_key'   => "{$date_index}_{$time_index}",
						'voted_at'     => current_time( 'mysql', true )
					] );
				}
			}

			if ( ! $user_id ) {
				$cookie_name  = 'dame_benevolat_response_' . $benevolat_id;
				$cookie_value = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : uniqid( 'benevolat_' . $benevolat_id . '_' );

				update_post_meta( $response_id, '_dame_guest_response_id', $cookie_value );
				// Cookie is valid for 1 year.
				setcookie( $cookie_name, $cookie_value, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
			}
		}

		// Redirect to the same page with a success query arg.
		$referer      = isset( $_POST['_wp_http_referer'] ) ? esc_url_raw( wp_unslash( $_POST['_wp_http_referer'] ) ) : wp_get_referer();
		$redirect_url = add_query_arg( 'vote', 'success', $referer );
		wp_safe_redirect( $redirect_url );
		exit;
	}
}
