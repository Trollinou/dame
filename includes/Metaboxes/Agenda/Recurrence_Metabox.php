<?php
/**
 * Agenda Recurrence Metabox.
 *
 * @package DAME\Metaboxes\Agenda
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Agenda;

use WP_Post;
use DateTimeImmutable;
use DAME\Services\Agenda\Series_Manager;
use DAME\Services\Agenda\Recurrence_Calculator;

/**
 * Class Recurrence_Metabox
 * Renders recurrence creation options and series management tools.
 */
class Recurrence_Metabox {

	/**
	 * Renders the recurrence metabox content.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render( WP_Post $post ): void {
		$group_id = (string) get_post_meta( $post->ID, '_dame_recurrence_group_id', true );

		if ( ! empty( $group_id ) ) {
			$this->render_existing_series_tools( $post, $group_id );
		} else {
			$this->render_creation_form( $post );
		}
	}

	/**
	 * Renders creation fields for a new recurrence series.
	 *
	 * @param WP_Post $post Current post object.
	 */
	private function render_creation_form( WP_Post $post ): void {
		$start_date_str = (string) get_post_meta( $post->ID, '_dame_start_date', true );
		$season_limit_display = '';

		if ( ! empty( $start_date_str ) ) {
			try {
				$dt = new DateTimeImmutable( $start_date_str );
				$deadline = Recurrence_Calculator::get_season_deadline( $dt );
				$season_limit_display = $deadline->format( 'd/m/Y' );
			} catch ( \Exception $e ) {
				$season_limit_display = '';
			}
		}

		$is_enabled     = ( '1' === (string) get_post_meta( $post->ID, '_dame_recurrence_enabled', true ) );
		$pending_config = get_post_meta( $post->ID, '_dame_recurrence_pending_config', true );
		if ( ! is_array( $pending_config ) ) {
			$pending_config = array();
		}

		$frequency      = (string) ( $pending_config['frequency'] ?? 'weekly' );
		$interval_weeks = (int) ( $pending_config['interval_weeks'] ?? 1 );
		$days_of_week   = is_array( $pending_config['days_of_week'] ?? null ) ? $pending_config['days_of_week'] : array();
		$monthly_type   = (string) ( $pending_config['monthly_type'] ?? 'ordinal' );
		$ordinal        = (string) ( $pending_config['ordinal'] ?? 'first' );
		$day_name       = (string) ( $pending_config['day_name'] ?? 'friday' );
		$day_of_month   = (int) ( $pending_config['day_of_month'] ?? 1 );
		$end_type       = (string) ( $pending_config['end_type'] ?? 'until_date' );
		$end_date       = (string) ( $pending_config['end_date'] ?? '' );
		$max_count      = (int) ( $pending_config['max_count'] ?? 10 );

		?>
		<div class="dame-recurrence-wrapper">
			<p>
				<label style="font-weight: 600; cursor: pointer;">
					<input type="checkbox" id="dame_enable_recurrence" name="dame_enable_recurrence" value="1" <?php checked( $is_enabled, true ); ?> />
					<?php esc_html_e( 'Activer la répétition (Créer une série d\'événements)', 'dame' ); ?>
				</label>
			</p>

			<div id="dame_recurrence_options" style="<?php echo $is_enabled ? '' : 'display: none;'; ?> padding: 15px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 10px;">
				<table class="form-table" style="margin-top: 0;">
					<tr>
						<th style="width: 160px; padding: 8px 0;"><label for="dame_recurrence_frequency"><?php esc_html_e( 'Fréquence', 'dame' ); ?></label></th>
						<td style="padding: 8px 0;">
							<select id="dame_recurrence_frequency" name="dame_recurrence_frequency" style="min-width: 180px;">
								<option value="weekly" <?php selected( $frequency, 'weekly' ); ?>><?php esc_html_e( 'Hebdomadaire (par semaine)', 'dame' ); ?></option>
								<option value="monthly" <?php selected( $frequency, 'monthly' ); ?>><?php esc_html_e( 'Mensuelle (par mois)', 'dame' ); ?></option>
							</select>
						</td>
					</tr>

					<!-- Weekly Settings -->
					<tr id="dame_recurrence_weekly_row" style="<?php echo 'monthly' === $frequency ? 'display: none;' : ''; ?>">
						<th style="padding: 8px 0;"><label><?php esc_html_e( 'Répétition', 'dame' ); ?></label></th>
						<td style="padding: 8px 0;">
							<div style="margin-bottom: 8px;">
								<?php esc_html_e( 'Toutes les', 'dame' ); ?>
								<input type="number" id="dame_recurrence_interval_weeks" name="dame_recurrence_interval_weeks" value="<?php echo esc_attr( (string) $interval_weeks ); ?>" min="1" max="52" style="width: 60px; text-align: center;" />
								<?php esc_html_e( 'semaine(s) le :', 'dame' ); ?>
							</div>
							<div class="dame-days-checklist" style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 6px;">
								<?php
								$days = array(
									1 => __( 'Lun', 'dame' ),
									2 => __( 'Mar', 'dame' ),
									3 => __( 'Mer', 'dame' ),
									4 => __( 'Jeu', 'dame' ),
									5 => __( 'Ven', 'dame' ),
									6 => __( 'Sam', 'dame' ),
									7 => __( 'Dim', 'dame' ),
								);
								foreach ( $days as $num => $label ) {
									$checked = in_array( $num, $days_of_week, true ) ? 'checked="checked"' : '';
									echo '<label style="cursor: pointer;">';
									echo '<input type="checkbox" name="dame_recurrence_days_of_week[]" value="' . esc_attr( (string) $num ) . '" class="dame-recurrence-day-checkbox" ' . $checked . ' /> ';
									echo esc_html( $label );
									echo '</label>';
								}
								?>
							</div>
							<p class="description"><?php esc_html_e( 'Si aucun jour n\'est coché, le jour de la date de début sera utilisé.', 'dame' ); ?></p>
						</td>
					</tr>

					<!-- Monthly Settings -->
					<tr id="dame_recurrence_monthly_row" style="<?php echo 'monthly' === $frequency ? '' : 'display: none;'; ?>">
						<th style="padding: 8px 0;"><label><?php esc_html_e( 'Règle mensuelle', 'dame' ); ?></label></th>
						<td style="padding: 8px 0;">
							<div style="margin-bottom: 8px;">
								<label style="cursor: pointer;">
									<input type="radio" name="dame_recurrence_monthly_type" value="ordinal" <?php checked( $monthly_type, 'ordinal' ); ?> />
									<?php esc_html_e( 'Chaque', 'dame' ); ?>
									<select name="dame_recurrence_ordinal" id="dame_recurrence_ordinal">
										<option value="first" <?php selected( $ordinal, 'first' ); ?>><?php esc_html_e( '1er', 'dame' ); ?></option>
										<option value="second" <?php selected( $ordinal, 'second' ); ?>><?php esc_html_e( '2ème', 'dame' ); ?></option>
										<option value="third" <?php selected( $ordinal, 'third' ); ?>><?php esc_html_e( '3ème', 'dame' ); ?></option>
										<option value="fourth" <?php selected( $ordinal, 'fourth' ); ?>><?php esc_html_e( '4ème', 'dame' ); ?></option>
										<option value="last" <?php selected( $ordinal, 'last' ); ?>><?php esc_html_e( 'Dernier', 'dame' ); ?></option>
									</select>
									<select name="dame_recurrence_day_name" id="dame_recurrence_day_name">
										<option value="monday" <?php selected( $day_name, 'monday' ); ?>><?php esc_html_e( 'Lundi', 'dame' ); ?></option>
										<option value="tuesday" <?php selected( $day_name, 'tuesday' ); ?>><?php esc_html_e( 'Mardi', 'dame' ); ?></option>
										<option value="wednesday" <?php selected( $day_name, 'wednesday' ); ?>><?php esc_html_e( 'Mercredi', 'dame' ); ?></option>
										<option value="thursday" <?php selected( $day_name, 'thursday' ); ?>><?php esc_html_e( 'Jeudi', 'dame' ); ?></option>
										<option value="friday" <?php selected( $day_name, 'friday' ); ?>><?php esc_html_e( 'Vendredi', 'dame' ); ?></option>
										<option value="saturday" <?php selected( $day_name, 'saturday' ); ?>><?php esc_html_e( 'Samedi', 'dame' ); ?></option>
										<option value="sunday" <?php selected( $day_name, 'sunday' ); ?>><?php esc_html_e( 'Dimanche', 'dame' ); ?></option>
									</select>
									<?php esc_html_e( 'du mois', 'dame' ); ?>
								</label>
							</div>
							<div>
								<label style="cursor: pointer;">
									<input type="radio" name="dame_recurrence_monthly_type" value="day_of_month" <?php checked( $monthly_type, 'day_of_month' ); ?> />
									<?php esc_html_e( 'Le', 'dame' ); ?>
									<input type="number" name="dame_recurrence_day_of_month" id="dame_recurrence_day_of_month" min="1" max="31" value="<?php echo esc_attr( (string) $day_of_month ); ?>" style="width: 55px; text-align: center;" />
									<?php esc_html_e( 'de chaque mois', 'dame' ); ?>
								</label>
							</div>
						</td>
					</tr>

					<!-- End condition -->
					<tr>
						<th style="padding: 8px 0;"><label><?php esc_html_e( 'Fin de la série', 'dame' ); ?></label></th>
						<td style="padding: 8px 0;">
							<div style="margin-bottom: 8px;">
								<label style="cursor: pointer;">
									<input type="radio" name="dame_recurrence_end_type" value="until_date" <?php checked( $end_type, 'until_date' ); ?> />
									<?php esc_html_e( 'Jusqu\'au', 'dame' ); ?>
									<input type="date" id="dame_recurrence_end_date" name="dame_recurrence_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
								</label>
							</div>
							<div>
								<label style="cursor: pointer;">
									<input type="radio" name="dame_recurrence_end_type" value="count" <?php checked( $end_type, 'count' ); ?> />
									<?php esc_html_e( 'Après', 'dame' ); ?>
									<input type="number" id="dame_recurrence_max_count" name="dame_recurrence_max_count" min="2" max="60" value="<?php echo esc_attr( (string) $max_count ); ?>" style="width: 60px; text-align: center;" />
									<?php esc_html_e( 'séances au total', 'dame' ); ?>
								</label>
							</div>
							<div id="dame_season_limit_notice" style="margin-top: 8px; color: #0369a1; font-size: 12px;">
								<span class="dashicons dashicons-info" style="font-size: 16px; vertical-align: middle; margin-right: 2px;"></span>
								<strong><?php esc_html_e( 'Limite de saison sportive :', 'dame' ); ?></strong>
								<span id="dame_season_limit_text">
									<?php
									if ( ! empty( $season_limit_display ) ) {
										printf(
											/* translators: %s: date limite de la saison */
											esc_html__( 'Les répétitions ne pourront pas dépasser le %s (fin de saison).', 'dame' ),
											esc_html( $season_limit_display )
										);
									} else {
										esc_html_e( 'Calculée automatiquement au 31 août à partir de la date de début.', 'dame' );
									}
									?>
								</span>
							</div>
						</td>
					</tr>
				</table>

				<div style="margin-top: 12px; padding: 10px; background: #e0f2fe; border-left: 4px solid #0284c7; border-radius: 4px; font-size: 13px; color: #0369a1;">
					<strong><?php esc_html_e( 'Fonctionnement :', 'dame' ); ?></strong>
					<?php esc_html_e( 'En brouillon, seul le modèle est sauvegardé. Lors de la publication finale, un événement indépendant sera généré pour chaque séance dans le calendrier.', 'dame' ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders series management controls for existing recurring posts.
	 *
	 * @param WP_Post $post     Current post.
	 * @param string  $group_id Recurrence group ID.
	 */
	private function render_existing_series_tools( WP_Post $post, string $group_id ): void {
		$from_date       = (string) get_post_meta( $post->ID, '_dame_start_date', true );
		$is_parent       = ( 1 === (int) get_post_meta( $post->ID, '_dame_recurrence_is_parent', true ) );
		$total_events    = Series_Manager::count_series_events( $group_id, null );
		$future_events   = Series_Manager::count_series_events( $group_id, $from_date );

		$delete_from_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=dame_delete_series_from&post_id=' . $post->ID ),
			'dame_delete_series_from_' . $post->ID
		);

		$delete_all_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=dame_delete_entire_series&group_id=' . rawurlencode( $group_id ) . '&post_id=' . $post->ID ),
			'dame_delete_entire_series_' . $group_id
		);

		?>
		<div class="dame-series-info-box" style="padding: 12px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px;">
			<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
				<span class="dashicons dashicons-update" style="color: #2563eb;"></span>
				<strong><?php esc_html_e( 'Événement issu d\'une série récurrente', 'dame' ); ?></strong>
			</div>

			<p style="margin: 0 0 10px 0; color: #475569; font-size: 13px;">
				<?php
				if ( $is_parent ) {
					printf(
						/* translators: %d: nombre total d'événements */
						esc_html__( 'Cet événement est le premier de la série (%d séances au total).', 'dame' ),
						(int) $total_events
					);
				} else {
					printf(
						/* translators: 1: nombre d'événements restants, 2: nombre total d'événements */
						esc_html__( 'Il reste %1$d séance(s) à partir de cette date (sur un total initial de %2$d séances).', 'dame' ),
						(int) $future_events,
						(int) $total_events
					);
				}
				?>
			</p>

			<div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
				<a href="<?php echo esc_url( $delete_from_url ); ?>"
				   class="button button-secondary dame-js-delete-series-from"
				   data-count="<?php echo esc_attr( (string) $future_events ); ?>"
				   data-is-parent="<?php echo $is_parent ? '1' : '0'; ?>"
				   style="color: #b91c1c; border-color: #fca5a5;">
					<span class="dashicons dashicons-trash" style="vertical-align: text-top; font-size: 16px; margin-right: 2px;"></span>
					<?php
					if ( $is_parent ) {
						esc_html_e( 'Supprimer toute la série (tous les événements)', 'dame' );
					} else {
						printf(
							/* translators: %d: nombre de séances */
							esc_html__( 'Supprimer cet événement et les suivants (%d séances)', 'dame' ),
							(int) $future_events
						);
					}
					?>
				</a>

				<?php if ( ! $is_parent && $total_events > $future_events ) : ?>
					<a href="<?php echo esc_url( $delete_all_url ); ?>"
					   class="button-link dame-js-delete-entire-series"
					   data-total="<?php echo esc_attr( (string) $total_events ); ?>"
					   style="color: #991b1b; text-decoration: underline; font-size: 12px; margin-left: 5px;">
						<?php
						printf(
							/* translators: %d: nombre total d'événements */
							esc_html__( 'Supprimer toute la série y compris les passés (%d)', 'dame' ),
							(int) $total_events
						);
						?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
