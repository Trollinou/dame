<?php
/**
 * Agenda Participants Metabox.
 *
 * @package DAME\Metaboxes\Agenda
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Agenda;

use WP_Post;

/**
 * Renders the Participants selection checklist metabox for Agenda events.
 */
class ParticipantsMetabox {

	/**
	 * Renders the meta box for selecting event participants.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render( WP_Post $post ): void {
		$current_season_tag_id = get_option( 'dame_current_season_tag_id' );

		if ( ! $current_season_tag_id ) {
			echo '<p>' . esc_html__( 'La saison actuelle n\'est pas configurée. Impossible de lister les adhérents.', 'dame' ) . '</p>';
			return;
		}

		$args = array(
			'post_type'      => 'adherent',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'dame_saison_adhesion',
					'field'    => 'term_id',
					'terms'    => $current_season_tag_id,
				),
			),
		);

		$adherents             = get_posts( $args );
		$selected_participants = get_post_meta( $post->ID, '_dame_event_participants', true );
		if ( ! is_array( $selected_participants ) ) {
			$selected_participants = array();
		}

		if ( empty( $adherents ) ) {
			echo '<p>' . esc_html__( 'Aucun adhérent actif pour la saison en cours.', 'dame' ) . '</p>';
			return;
		}

		$selected_list   = array();
		$unselected_list = array();
		foreach ( $adherents as $adherent ) {
			if ( in_array( $adherent->ID, $selected_participants, true ) ) {
				$selected_list[] = $adherent;
			} else {
				$unselected_list[] = $adherent;
			}
		}
		$sorted_adherents = array_merge( $selected_list, $unselected_list );
		?>
		<input type="text" id="dame_participant_filter" placeholder="<?php esc_attr_e( 'Filtrer par nom...', 'dame' ); ?>" style="width: 100%; margin-bottom: 5px;">
		<div class="dame-participants-checklist" style="max-height: 250px; overflow-y: auto;">
			<ul id="dame_participants_list">
				<?php
				foreach ( $sorted_adherents as $adherent ) {
					echo '<li>';
					echo '<label>';
					echo '<input type="checkbox" name="dame_event_participants[]" value="' . esc_attr( (string) $adherent->ID ) . '" ' . checked( in_array( $adherent->ID, $selected_participants, true ), true, false ) . '> ';
					echo esc_html( $adherent->post_title );
					echo '</label>';
					echo '</li>';
				}
				?>
			</ul>
		</div>
		<p class="description"><?php esc_html_e( 'Seuls les adhérents avec une adhésion active pour la saison en cours sont listés.', 'dame' ); ?></p>
		<?php
	}
}
