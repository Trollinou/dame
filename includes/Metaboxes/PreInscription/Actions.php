<?php
/**
 * Actions Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\PreInscription;

use DAME\Services\Adherent_Matcher;

/**
 * Class Actions
 */
class Actions {

	/**
	 * Initialize the metabox.
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_box' ] );
		add_action( 'save_post', [ $this, 'save' ], 20 );
	}

	/**
	 * Add the meta box.
	 */
	public function add_box() {
		$matched_id = Adherent_Matcher::find_match( get_the_ID() );
		add_meta_box(
			'dame_pre_inscription_actions',
			__( 'Actions de Validation', 'dame' ),
			[ $this, 'render' ],
			'dame_pre_inscription',
			'side',
			'high',
			array( 'matched_id' => $matched_id )
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post The post object.
	 * @param array    $metabox The metabox arguments.
	 */
	public function render( $post, $metabox ) {
		$matched_id = $metabox['args']['matched_id'];
		wp_nonce_field( 'dame_pre_inscription_process_action', 'dame_pre_inscription_action_nonce' );
		?>
		<div class="dame-actions-wrapper">
			<?php if ( $matched_id ) : ?>
				<input type="hidden" name="dame_matched_adherent_id" value="<?php echo esc_attr( $matched_id ); ?>" />
				<p><strong><span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php _e( 'Adhérent existant trouvé !', 'dame' ); ?></strong></p>
				<p>
					<button type="submit" name="dame_pre_inscription_action" value="validate_update" class="button button-primary button-large"><?php _e( "Mettre à jour l'adhérent", 'dame' ); ?></button>
				</p>
			<?php else : ?>
				<p><strong><?php _e( "Valider cette préinscription ?", 'dame' ); ?></strong></p>
				<p>
					<button type="submit" name="dame_pre_inscription_action" value="validate_new" class="button button-primary button-large"><?php _e( "Valider et Créer Adhérent", 'dame' ); ?></button>
				</p>
			<?php endif; ?>
			<hr>
			<p>
				<button type="submit" name="dame_pre_inscription_action" value="delete" class="button button-secondary button-large dame-delete-button" formnovalidate><?php _e( "Supprimer la Préinscription", 'dame' ); ?></button>
			</p>
		</div>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const deleteButton = document.querySelector('.dame-delete-button');
				if (deleteButton) {
					deleteButton.addEventListener('click', function(e) {
						if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir supprimer définitivement cette préinscription ? Cette action est irréversible.', 'dame' ) ); ?>")) {
							e.preventDefault();
						}
					});
				}
			});
		</script>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ) {
		// Note: Field saving is handled by Details class (priority 10).
		// This runs at priority 20 to handle actions AFTER fields are saved.

		if ( ! isset( $_POST['dame_pre_inscription_action_nonce'] ) || ! wp_verify_nonce( $_POST['dame_pre_inscription_action_nonce'], 'dame_pre_inscription_process_action' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( 'dame_pre_inscription' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['dame_pre_inscription_action'] ) ) {
			$action = sanitize_key( $_POST['dame_pre_inscription_action'] );

			switch ( $action ) {
				case 'delete':
					wp_delete_post( $post_id, true );
					wp_safe_redirect( admin_url( 'edit.php?post_type=dame_pre_inscription&message=101' ) );
					exit;

				case 'validate_new':
				case 'validate_update':
					// Data is already saved by Details class, so we can read it fresh from the DB.
					$pre_inscription_meta = get_post_meta( $post_id );
					$adherent_meta        = array();
					foreach ( $pre_inscription_meta as $key => $value ) {
						if ( strpos( $key, '_dame_' ) === 0 ) {
							$adherent_meta[ $key ] = maybe_unserialize( $value[0] );
						}
					}

					$post_title       = get_the_title( $post_id );
					$adherent_id      = 0;
					$redirect_message = 0;

					if ( 'validate_new' === $action ) {
						$adherent_post_data = array(
							'post_title'  => $post_title,
							'post_type'   => 'adherent',
							'post_status' => 'publish',
							'meta_input'  => $adherent_meta,
						);
						$adherent_id        = wp_insert_post( $adherent_post_data, true );
						$redirect_message   = 6; // Post published.
					} else { // validate_update
						$adherent_id = isset( $_POST['dame_matched_adherent_id'] ) ? absint( $_POST['dame_matched_adherent_id'] ) : 0;
						if ( ! $adherent_id ) {
							// Fallback: treat as new if ID is missing.
							$adherent_id      = wp_insert_post(
								array(
									'post_title'  => $post_title,
									'post_type'   => 'adherent',
									'post_status' => 'publish',
									'meta_input'  => $adherent_meta,
								)
							);
							$redirect_message = 6;
						} else {
							// Update existing adherent
							wp_update_post(
								array(
									'ID'         => $adherent_id,
									'post_title' => $post_title,
								)
							);
							foreach ( $adherent_meta as $key => $value ) {
								update_post_meta( $adherent_id, $key, $value );
							}
							$redirect_message = 1; // Post updated.
						}
					}

					if ( is_wp_error( $adherent_id ) ) {
						return;
					}

					// Set adherent to 'Active' for the current season
					$current_season_tag_id = get_option( 'dame_current_season_tag_id' );
					if ( $current_season_tag_id ) {
						wp_add_object_terms( $adherent_id, (int) $current_season_tag_id, 'dame_saison_adhesion' );
					}

					// Delete the pre-inscription post
					wp_delete_post( $post_id, true );

					// Redirect to the adherent's edit page
					$redirect_url = get_edit_post_link( $adherent_id, 'raw' );
					wp_safe_redirect( add_query_arg( 'message', $redirect_message, $redirect_url ) );
					exit;
			}
		}
	}
}
