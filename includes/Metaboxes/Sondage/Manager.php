<?php

namespace DAME\Metaboxes\Sondage;

use WP_Post;

/**
 * Class Manager
 * Handles metaboxes for Sondage CPT.
 */
class Manager {

	/**
	 * Initialize the metaboxes.
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_metaboxes' ] );
		add_action( 'save_post', [ $this, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue styles and scripts.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		global $post;

		if ( ( 'post-new.php' === $hook || 'post.php' === $hook ) && 'dame_sondage' === $post->post_type ) {
			// CORRECTIF : Utilisation de \DAME_PLUGIN_URL et \DAME_VERSION (Global Namespace)
			wp_enqueue_script(
				'dame-sondage-admin',
				\DAME_PLUGIN_URL . 'assets/js/sondage-admin.js',
				[ 'jquery' ],
				defined( '\DAME_VERSION' ) ? \DAME_VERSION : '1.0.0',
				true
			);

			// Localize script if needed (optional)
			wp_localize_script( 'dame-sondage-admin', 'dame_sondage_vars', [
				'nonce' => wp_create_nonce( 'dame_sondage_nonce' )
			]);
		}
	}

	/**
	 * Add metaboxes.
	 */
	public function add_metaboxes() {
		add_meta_box( 'dame_sondage_config', __( 'Configuration', 'dame' ), [ $this, 'render_config' ], 'dame_sondage', 'normal', 'high' );
		add_meta_box( 'dame_sondage_answers', __( 'Réponses possibles', 'dame' ), [ $this, 'render_answers' ], 'dame_sondage', 'normal', 'high' );
		add_meta_box( 'dame_sondage_results', __( 'Résultats', 'dame' ), [ $this, 'render_results' ], 'dame_sondage', 'normal', 'high' );
	}

	public function render_config( $post ) {
		$end_date = get_post_meta( $post->ID, '_dame_poll_end_date', true );
		$restriction = get_post_meta( $post->ID, '_dame_poll_restriction', true );
		wp_nonce_field( 'dame_sondage_save', 'dame_sondage_nonce' );
		?>
		<p>
			<label for="dame_poll_end_date"><strong><?php esc_html_e( 'Date de fin du vote :', 'dame' ); ?></strong></label><br>
			<input type="date" id="dame_poll_end_date" name="dame_poll_end_date" value="<?php echo esc_attr( $end_date ); ?>">
		</p>
		<p>
			<label for="dame_poll_restriction"><strong><?php esc_html_e( 'Qui peut voter ?', 'dame' ); ?></strong></label><br>
			<select id="dame_poll_restriction" name="dame_poll_restriction">
				<option value="all" <?php selected( $restriction, 'all' ); ?>><?php esc_html_e( 'Tout le monde (Public)', 'dame' ); ?></option>
				<option value="members" <?php selected( $restriction, 'members' ); ?>><?php esc_html_e( 'Membres connectés uniquement', 'dame' ); ?></option>
			</select>
		</p>
		<?php
	}

	public function render_answers( $post ) {
		$answers = get_post_meta( $post->ID, '_dame_poll_answers', true );
		// Ensure answers is an array, or if it's a string (legacy), explode it.
		$answers_text = '';
		if ( is_array( $answers ) ) {
			$answers_text = implode( "\n", $answers );
		} elseif ( is_string( $answers ) ) {
			$answers_text = $answers;
		}
		?>
		<p class="description"><?php esc_html_e( 'Inscrivez une réponse par ligne.', 'dame' ); ?></p>
		<textarea name="dame_poll_answers" id="dame_poll_answers" rows="5" style="width:100%;"><?php echo esc_textarea( $answers_text ); ?></textarea>
		<?php
	}

	public function render_results( $post ) {
		$votes = get_post_meta( $post->ID, '_dame_poll_votes', true ); // Stored as ['Answer A' => 10, 'Answer B' => 5]
		if ( empty( $votes ) || ! is_array( $votes ) ) {
			echo '<p>' . esc_html__( 'Aucun vote pour le moment.', 'dame' ) . '</p>';
			return;
		}

		$total_votes = array_sum( $votes );
		echo '<table class="widefat">';
		foreach ( $votes as $answer => $count ) {
			$percent = $total_votes > 0 ? round( ( $count / $total_votes ) * 100 ) : 0;
			echo '<tr>';
			echo '<td style="width: 40%;">' . esc_html( $answer ) . '</td>';
			echo '<td style="width: 60%;">';
			echo '<div style="background: #f1f1f1; border: 1px solid #ccc; height: 20px; width: 100%; position: relative;">';
			echo '<div style="background: #0073aa; height: 100%; width: ' . intval( $percent ) . '%;"></div>';
			echo '<span style="position: absolute; right: 5px; top: 0; line-height: 20px; font-size: 10px;">' . intval( $count ) . ' (' . intval( $percent ) . '%)</span>';
			echo '</div>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
		echo '<p><strong>' . sprintf( __( 'Total des votes : %d', 'dame' ), $total_votes ) . '</strong></p>';
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST['dame_sondage_nonce'] ) || ! wp_verify_nonce( $_POST['dame_sondage_nonce'], 'dame_sondage_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		if ( isset( $_POST['dame_poll_end_date'] ) ) {
			update_post_meta( $post_id, '_dame_poll_end_date', sanitize_text_field( $_POST['dame_poll_end_date'] ) );
		}
		if ( isset( $_POST['dame_poll_restriction'] ) ) {
			update_post_meta( $post_id, '_dame_poll_restriction', sanitize_text_field( $_POST['dame_poll_restriction'] ) );
		}
		if ( isset( $_POST['dame_poll_answers'] ) ) {
			$lines = explode( "\n", $_POST['dame_poll_answers'] );
			$clean_lines = array_filter( array_map( 'trim', $lines ) );
			update_post_meta( $post_id, '_dame_poll_answers', $clean_lines );
		}
	}
}
