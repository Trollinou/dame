<?php
/**
 * Benevolat Volunteering Call Metabox for Agenda events.
 *
 * @package DAME\Metaboxes\Agenda
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Agenda;

use WP_Post;
use DAME\Repositories\BenevolatRepository;

/**
 * Class BenevolatMetabox
 */
class BenevolatMetabox {

	/**
	 * Repository.
	 *
	 * @var BenevolatRepository
	 */
	private BenevolatRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param BenevolatRepository|null $repository Optional repository.
	 */
	public function __construct( ?BenevolatRepository $repository = null ) {
		$this->repository = $repository ?? new BenevolatRepository();
	}

	/**
	 * Renders the metabox.
	 *
	 * @param WP_Post $post Current event post.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'dame_agenda_benevolat_nonce_action', 'dame_agenda_benevolat_nonce' );

		$linked_benevolat_id = (int) get_post_meta( $post->ID, '_dame_linked_benevolat_id', true );

		$benevolat_posts = get_posts(
			array(
				'post_type'      => 'benevolat',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		?>
		<div class="dame-agenda-benevolat-wrapper">
			<p>
				<label for="dame_linked_benevolat_id"><strong><?php esc_html_e( 'Appel à bénévoles lié :', 'dame' ); ?></strong></label><br>
				<select name="dame_linked_benevolat_id" id="dame_linked_benevolat_id" style="width: 100%; max-width: 450px; margin-top: 5px;">
					<option value="0"><?php esc_html_e( '-- Aucun appel à bénévoles associé --', 'dame' ); ?></option>
					<?php foreach ( $benevolat_posts as $b_post ) : ?>
						<?php
						$status_label = 'draft' === $b_post->post_status ? ' [' . __( 'Brouillon', 'dame' ) . ']' : '';
						?>
						<option value="<?php echo esc_attr( (string) $b_post->ID ); ?>" <?php selected( (int) $b_post->ID, $linked_benevolat_id ); ?>>
							<?php echo esc_html( (string) $b_post->post_title . $status_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<?php if ( $linked_benevolat_id > 0 ) : ?>
				<?php
				$linked_post = get_post( $linked_benevolat_id );
				$b_data      = get_post_meta( $linked_benevolat_id, '_dame_benevolat_data', true );

				// Get responses count.
				$response_count = (int) count(
					get_posts(
						array(
							'post_type'      => 'benevolat_reponse',
							'post_parent'    => $linked_benevolat_id,
							'post_status'    => 'publish',
							'posts_per_page' => -1,
							'fields'         => 'ids',
						)
					)
				);

				$total_slots = 0;
				if ( is_array( $b_data ) ) {
					foreach ( $b_data as $date_grp ) {
						if ( ! empty( $date_grp['time_slots'] ) && is_array( $date_grp['time_slots'] ) ) {
							$total_slots += count( $date_grp['time_slots'] );
						}
					}
				}
				?>
				<hr style="margin: 15px 0;">

				<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
					<div>
						<strong><?php esc_html_e( 'Statut des inscriptions bénévoles :', 'dame' ); ?></strong>
						<span style="margin-left: 10px; color: #2271b1; font-weight:600;">
							<?php
							printf(
								/* translators: 1: responses count, 2: total time slots */
								esc_html__( '%1$d bénévole(s) inscrit(s) sur %2$d créneau(x)', 'dame' ),
								$response_count,
								$total_slots
							);
							?>
						</span>
					</div>
					<div style="display:flex; gap: 8px;">
						<a href="<?php echo esc_url( get_edit_post_link( $linked_benevolat_id ) ?? '' ); ?>" target="_blank" class="button button-secondary">
							<?php esc_html_e( 'Modifier l\'appel ↗', 'dame' ); ?>
						</a>
						<?php if ( $linked_post && 'publish' === $linked_post->post_status ) : ?>
							<a href="<?php echo esc_url( get_permalink( $linked_benevolat_id ) ); ?>" target="_blank" class="button button-secondary">
								<?php esc_html_e( 'Voir la page publique ↗', 'dame' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<p style="margin: 0; color: #555; font-size: 13px;">
					ℹ️ <?php esc_html_e( 'Les adhérents consultant cet événement verront un encadré les invitant à s\'inscrire sur cet appel à bénévoles.', 'dame' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Saves the linked benevolat ID on agenda event save.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( int $post_id ): void {
		$nonce = isset( $_POST['dame_agenda_benevolat_nonce'] ) ? sanitize_key( wp_unslash( $_POST['dame_agenda_benevolat_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dame_agenda_benevolat_nonce_action' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['dame_linked_benevolat_id'] ) ) {
			$benevolat_id = absint( $_POST['dame_linked_benevolat_id'] );
			$old_id       = (int) get_post_meta( $post_id, '_dame_linked_benevolat_id', true );

			if ( $benevolat_id > 0 ) {
				update_post_meta( $post_id, '_dame_linked_benevolat_id', $benevolat_id );
				// Bidirectional sync on the benevolat post.
				update_post_meta( $benevolat_id, '_dame_linked_agenda_id', $post_id );

				// If previously linked to another benevolat, clear its reference.
				if ( $old_id > 0 && $old_id !== $benevolat_id ) {
					$prev_linked_agenda = (int) get_post_meta( $old_id, '_dame_linked_agenda_id', true );
					if ( $prev_linked_agenda === $post_id ) {
						delete_post_meta( $old_id, '_dame_linked_agenda_id' );
					}
				}
			} else {
				delete_post_meta( $post_id, '_dame_linked_benevolat_id' );
				if ( $old_id > 0 ) {
					$prev_linked_agenda = (int) get_post_meta( $old_id, '_dame_linked_agenda_id', true );
					if ( $prev_linked_agenda === $post_id ) {
						delete_post_meta( $old_id, '_dame_linked_agenda_id' );
					}
				}
			}
		}
	}
}

