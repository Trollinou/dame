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
	 * Enqueue scripts.
	 *
	 * @param string $hook Current hook.
	 */
	public function enqueue_scripts( $hook ) {
		global $post;
		if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && isset( $post->post_type ) && 'dame_sondage' === $post->post_type ) {
			wp_enqueue_script(
				'dame-sondage-admin',
				\DAME_PLUGIN_URL . 'assets/js/sondage-admin.js',
				[ 'jquery' ],
				\DAME_VERSION,
				true
			);
		}
	}

	/**
	 * Register meta boxes.
	 */
	public function add_metaboxes() {
		add_meta_box(
			'dame_sondage_config',
			__( 'Configuration du sondage', 'dame' ),
			[ $this, 'render_config' ],
			'dame_sondage',
			'normal',
			'high'
		);
		add_meta_box(
			'dame_sondage_answers',
			__( 'Réponses possibles', 'dame' ),
			[ $this, 'render_answers' ],
			'dame_sondage',
			'normal',
			'high'
		);
		add_meta_box(
			'dame_sondage_results',
			__( 'Résultats', 'dame' ),
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

		$end_date    = get_post_meta( $post->ID, '_dame_poll_end_date', true );
		$restriction = get_post_meta( $post->ID, '_dame_poll_restriction', true );
		?>
		<p>
			<label for="dame_poll_end_date"><strong><?php _e( 'Date de fin :', 'dame' ); ?></strong></label><br>
			<input type="date" id="dame_poll_end_date" name="_dame_poll_end_date" value="<?php echo esc_attr( $end_date ); ?>">
		</p>
		<p>
			<label for="dame_poll_restriction"><strong><?php _e( 'Restriction :', 'dame' ); ?></strong></label><br>
			<select id="dame_poll_restriction" name="_dame_poll_restriction">
				<option value="all" <?php selected( $restriction, 'all' ); ?>><?php _e( 'Ouvert à tous', 'dame' ); ?></option>
				<option value="members" <?php selected( $restriction, 'members' ); ?>><?php _e( 'Membres connectés uniquement', 'dame' ); ?></option>
			</select>
		</p>
		<?php
	}

	/**
	 * Render answers metabox.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_answers( $post ) {
		$answers = get_post_meta( $post->ID, '_dame_poll_answers', true );
		// If answers are stored as array, convert to string for textarea
		$answers_text = '';
		if ( is_array( $answers ) ) {
			$answers_text = implode( "\n", $answers );
		}
		?>
		<p>
			<label for="dame_poll_answers"><strong><?php _e( 'Choix de réponses :', 'dame' ); ?></strong></label><br>
			<textarea id="dame_poll_answers" name="_dame_poll_answers" rows="5" style="width:100%;" placeholder="<?php _e( 'Oui', 'dame' ); ?>"><?php echo esc_textarea( $answers_text ); ?></textarea>
			<span class="description"><?php _e( 'Entrez une réponse par ligne.', 'dame' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Render results metabox.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_results( $post ) {
		$votes = get_post_meta( $post->ID, '_dame_poll_votes', true ); // Structure: ['Answer Text' => count]

		if ( empty( $votes ) || ! is_array( $votes ) ) {
			echo '<p>' . __( 'Aucun vote pour le moment.', 'dame' ) . '</p>';
			return;
		}

		$total_votes = array_sum( $votes );

		echo '<table class="widefat striped">';
		echo '<thead><tr><th>' . __( 'Réponse', 'dame' ) . '</th><th>' . __( 'Votes', 'dame' ) . '</th><th>' . __( 'Pourcentage', 'dame' ) . '</th></tr></thead>';
		echo '<tbody>';

		foreach ( $votes as $answer => $count ) {
			$percentage = $total_votes > 0 ? round( ( $count / $total_votes ) * 100, 1 ) : 0;
			echo '<tr>';
			echo '<td>' . esc_html( $answer ) . '</td>';
			echo '<td>' . intval( $count ) . '</td>';
			echo '<td>' . $percentage . '% <div style="background:#ddd;height:5px;width:100%;"><div style="background:#0073aa;height:5px;width:' . $percentage . '%;"></div></div></td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '<p><strong>' . sprintf( __( 'Total des votes : %d', 'dame' ), $total_votes ) . '</strong></p>';
	}

	/**
	 * Save metabox data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['dame_sondage_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['dame_sondage_metabox_nonce'], 'dame_save_sondage_metabox_data' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save Config
		if ( isset( $_POST['_dame_poll_end_date'] ) ) {
			update_post_meta( $post_id, '_dame_poll_end_date', sanitize_text_field( $_POST['_dame_poll_end_date'] ) );
		}

		if ( isset( $_POST['_dame_poll_restriction'] ) ) {
			update_post_meta( $post_id, '_dame_poll_restriction', sanitize_key( $_POST['_dame_poll_restriction'] ) );
		}

		// Save Answers
		if ( isset( $_POST['_dame_poll_answers'] ) ) {
			$raw_answers = explode( "\n", $_POST['_dame_poll_answers'] );
			$clean_answers = array_filter( array_map( 'trim', $raw_answers ) ); // Remove empty lines and trim whitespace
			// Re-index array
			$clean_answers = array_values( $clean_answers );
			update_post_meta( $post_id, '_dame_poll_answers', $clean_answers );
		}
	}
}
