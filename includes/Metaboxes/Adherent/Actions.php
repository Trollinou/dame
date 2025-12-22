<?php
/**
 * Adherent Special Actions Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\Adherent;

/**
 * Class Actions
 */
class Actions {

	/**
	 * Register the meta box.
	 */
	public function register() {
		add_meta_box(
			'dame_special_actions_metabox',
			__( 'Actions spéciales', 'dame' ),
			[ $this, 'render' ],
			'adherent',
			'side',
			'low'
		);

		add_filter( 'postbox_classes_adherent_dame_special_actions_metabox', [ $this, 'close_metabox_by_default' ] );
	}

	/**
	 * Close the "Actions spéciales" metabox by default.
	 *
	 * @param array $classes An array of postbox classes.
	 * @return array The modified array of classes.
	 */
	public function close_metabox_by_default( $classes ) {
		if ( function_exists( 'get_current_screen' ) && get_current_screen() && get_current_screen()->id === 'adherent' ) {
			$classes[] = 'closed';
		}
		return $classes;
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render( $post ) {
		// Get the current season tag ID from options.
		$current_season_tag_id = get_option( 'dame_current_season_tag_id' );
		if ( ! $current_season_tag_id ) {
			echo '<p>' . esc_html__( "L'action est indisponible car la saison actuelle n'est pas configurée.", 'dame' ) . '</p>';
			return;
		}

		// Get all 'dame_saison_adhesion' terms for the current post.
		$terms = wp_get_object_terms( $post->ID, 'dame_saison_adhesion' );

		// Check the condition: exactly one term, and it must be the current season's term.
		$is_eligible = false;
		if ( ! is_wp_error( $terms ) && 1 === count( $terms ) ) {
			if ( (int) $terms[0]->term_id === (int) $current_season_tag_id ) {
				$is_eligible = true;
			}
		}

		if ( $is_eligible ) {
			// Security nonce.
			wp_nonce_field( 'dame_revert_to_pre_inscription_action', 'dame_revert_nonce' );
			?>
			<p><?php esc_html_e( "Cette action va supprimer cet adhérent et créer une nouvelle pré-inscription avec ses données. L'adhérent disparaîtra de la liste des adhérents.", 'dame' ); ?></p>
			<button type="submit" name="dame_revert_to_pre_inscription" value="revert" class="button button-secondary">
				<?php esc_html_e( "Annuler et renvoyer en pré-inscription", 'dame' ); ?>
			</button>
			<script>
				// Add a confirmation dialog to prevent accidental clicks.
				document.addEventListener('DOMContentLoaded', function() {
					const revertButton = document.querySelector('button[name="dame_revert_to_pre_inscription"]');
					if (revertButton) {
						revertButton.addEventListener('click', function(e) {
							if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir annuler cette adhésion et renvoyer cette personne en pré-inscription ? Cette action est irréversible.', 'dame' ) ); ?>")) {
								e.preventDefault();
							}
						});
					}
				});
			</script>
			<?php
		} else {
			echo '<p>' . esc_html__( "Cette action n'est disponible que pour les adhérents qui ont uniquement l'adhésion de la saison en cours.", 'dame' ) . '</p>';
		}
	}

	/**
	 * Save the meta box.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ) {
		// Logic to handle the action is currently handled by legacy hooks or will be migrated later.
		// If we wanted to handle it here, we would check $_POST['dame_revert_to_pre_inscription'].
	}
}
