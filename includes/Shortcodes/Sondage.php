<?php

namespace DAME\Shortcodes;

/**
 * Class Sondage
 * Handles the [dame_sondage] shortcode for Simple Polls.
 */
class Sondage {

	/**
	 * Initialize the shortcode.
	 */
	public function init() {
		add_shortcode( 'dame_sondage', [ $this, 'render' ] );
		add_action( 'init', [ $this, 'handle_submission' ] );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string Output.
	 */
	public function render( $atts ) {
		$atts = shortcode_atts( [ 'slug' => '' ], $atts, 'dame_sondage' );

		if ( empty( $atts['slug'] ) ) {
			return '<p>' . __( 'Erreur : Le slug du sondage est manquant.', 'dame' ) . '</p>';
		}

		$sondage = get_page_by_path( $atts['slug'], OBJECT, 'dame_sondage' );
		if ( ! $sondage ) {
			return '<p>' . __( 'Erreur : Sondage non trouvé.', 'dame' ) . '</p>';
		}

		$answers = get_post_meta( $sondage->ID, '_dame_poll_answers', true );
		if ( empty( $answers ) || ! is_array( $answers ) ) {
			return '<p>' . __( 'Ce sondage n\'a pas encore été configuré.', 'dame' ) . '</p>';
		}

		$end_date = get_post_meta( $sondage->ID, '_dame_poll_end_date', true );
		if ( $end_date && date( 'Y-m-d' ) > $end_date ) {
			return $this->render_results( $sondage );
		}

		$restriction = get_post_meta( $sondage->ID, '_dame_poll_restriction', true );
		if ( 'members' === $restriction && ! is_user_logged_in() ) {
			return '<p>' . __( 'Vous devez être connecté pour voter.', 'dame' ) . '</p>';
		}

		// Check if user has voted
		if ( $this->has_user_voted( $sondage->ID ) ) {
			return $this->render_results( $sondage );
		}

		ob_start();
		?>
		<div class="dame-sondage-wrapper">
			<h3><?php echo esc_html( $sondage->post_title ); ?></h3>
			<?php if ( ! empty( $sondage->post_content ) ) : ?>
				<div class="sondage-description">
					<?php echo wpautop( wp_kses_post( $sondage->post_content ) ); ?>
				</div>
			<?php endif; ?>

			<form class="dame-sondage-form" method="post">
				<input type="hidden" name="sondage_id" value="<?php echo esc_attr( $sondage->ID ); ?>">
				<?php wp_nonce_field( 'dame_submit_poll_' . $sondage->ID, 'dame_sondage_nonce' ); ?>

				<ul class="dame-sondage-options">
					<?php foreach ( $answers as $key => $answer ) : ?>
						<li>
							<label>
								<input type="radio" name="dame_poll_vote" value="<?php echo esc_attr( $answer ); ?>" required>
								<?php echo esc_html( $answer ); ?>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>

				<p>
					<input type="submit" name="submit_poll" value="<?php echo esc_attr__( 'Voter', 'dame' ); ?>">
				</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render results.
	 *
	 * @param \WP_Post $sondage The poll post.
	 * @return string HTML output.
	 */
	private function render_results( $sondage ) {
		$votes = get_post_meta( $sondage->ID, '_dame_poll_votes', true ); // ['Answer' => count]
		$answers = get_post_meta( $sondage->ID, '_dame_poll_answers', true );
		$total_votes = is_array( $votes ) ? array_sum( $votes ) : 0;

		ob_start();
		?>
		<div class="dame-sondage-results">
			<h3><?php echo esc_html( $sondage->post_title ); ?> - <?php _e( 'Résultats', 'dame' ); ?></h3>
			<?php if ( empty( $answers ) ) : ?>
				<p><?php _e( 'Aucune donnée.', 'dame' ); ?></p>
			<?php else : ?>
				<ul class="dame-sondage-stats">
					<?php foreach ( $answers as $answer ) : ?>
						<?php
						$count = isset( $votes[ $answer ] ) ? intval( $votes[ $answer ] ) : 0;
						$percent = $total_votes > 0 ? round( ( $count / $total_votes ) * 100, 1 ) : 0;
						?>
						<li>
							<strong><?php echo esc_html( $answer ); ?></strong>: <?php echo $count; ?> votes (<?php echo $percent; ?>%)
							<div style="background:#ddd;height:10px;width:100%;margin-bottom:10px;">
								<div style="background:#0073aa;height:10px;width:<?php echo $percent; ?>%;"></div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
				<p><strong><?php printf( __( 'Total des votes : %d', 'dame' ), $total_votes ); ?></strong></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if user has voted.
	 *
	 * @param int $sondage_id Poll ID.
	 * @return bool
	 */
	private function has_user_voted( $sondage_id ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$user_votes = get_user_meta( $user_id, '_dame_voted_polls', true );
			return is_array( $user_votes ) && in_array( $sondage_id, $user_votes );
		} else {
			$cookie_name = 'dame_poll_' . $sondage_id;
			return isset( $_COOKIE[ $cookie_name ] );
		}
	}

	/**
	 * Handle submission.
	 */
	public function handle_submission() {
		if ( ! isset( $_POST['submit_poll'], $_POST['dame_sondage_nonce'], $_POST['sondage_id'], $_POST['dame_poll_vote'] ) ) {
			return;
		}

		$sondage_id = intval( $_POST['sondage_id'] );
		if ( ! wp_verify_nonce( $_POST['dame_sondage_nonce'], 'dame_submit_poll_' . $sondage_id ) ) {
			wp_die( 'Security check failed.' );
		}

		// Re-check restrictions
		$end_date = get_post_meta( $sondage_id, '_dame_poll_end_date', true );
		if ( $end_date && date( 'Y-m-d' ) > $end_date ) return;

		$restriction = get_post_meta( $sondage_id, '_dame_poll_restriction', true );
		if ( 'members' === $restriction && ! is_user_logged_in() ) return;

		if ( $this->has_user_voted( $sondage_id ) ) return;

		$vote_value = sanitize_text_field( $_POST['dame_poll_vote'] );
		$answers = get_post_meta( $sondage_id, '_dame_poll_answers', true );

		// Validate answer matches one of the options
		if ( ! is_array( $answers ) || ! in_array( $vote_value, $answers ) ) return;

		// Record Vote
		$current_votes = get_post_meta( $sondage_id, '_dame_poll_votes', true );
		if ( ! is_array( $current_votes ) ) $current_votes = [];

		if ( ! isset( $current_votes[ $vote_value ] ) ) {
			$current_votes[ $vote_value ] = 0;
		}
		$current_votes[ $vote_value ]++;

		update_post_meta( $sondage_id, '_dame_poll_votes', $current_votes );

		// Mark user as voted
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$user_votes = get_user_meta( $user_id, '_dame_voted_polls', true );
			if ( ! is_array( $user_votes ) ) $user_votes = [];
			$user_votes[] = $sondage_id;
			update_user_meta( $user_id, '_dame_voted_polls', $user_votes );
		} else {
			setcookie( 'dame_poll_' . $sondage_id, '1', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
		}

		wp_safe_redirect( add_query_arg( 'vote', 'success', wp_get_referer() ) );
		exit;
	}
}
