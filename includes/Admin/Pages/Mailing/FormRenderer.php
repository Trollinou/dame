<?php
/**
 * Form Renderer for Mailing Page.
 *
 * @package DAME\Admin\Pages\Mailing
 */

declare(strict_types=1);

namespace DAME\Admin\Pages\Mailing;

use DAME\Services\Data_Provider;

/**
 * Renders the HTML form, columns and searchable list components for the mailing page.
 */
class FormRenderer {

	/**
	 * Renders the complete mailing admin page.
	 *
	 * @param array<string, mixed> $saved_state Saved form state after errors.
	 * @param int                  $success Success indicator.
	 * @param int                  $count Processed recipient count.
	 * @param string               $error Error code.
	 */
	public function render( array $saved_state, int $success, int $count, string $error ): void {
		$state_message           = isset( $saved_state['dame_message_to_send'] ) ? absint( $saved_state['dame_message_to_send'] ) : 0;
		$state_adherent_method   = isset( $saved_state['dame_adherent_method'] ) ? sanitize_key( (string) $saved_state['dame_adherent_method'] ) : 'group';
		$state_contact_method    = isset( $saved_state['dame_contact_method'] ) ? sanitize_key( (string) $saved_state['dame_contact_method'] ) : 'group';
		$state_gender            = isset( $saved_state['dame_recipient_gender'] ) ? sanitize_text_field( (string) $saved_state['dame_recipient_gender'] ) : 'all';
		$state_seasons           = isset( $saved_state['dame_recipient_seasons'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_seasons'] ) : array();
		$state_groups_saisonnier = isset( $saved_state['dame_recipient_groups_saisonnier'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_groups_saisonnier'] ) : array();
		$state_groups_permanent  = isset( $saved_state['dame_recipient_groups_permanent'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_groups_permanent'] ) : array();
		$state_contact_types     = isset( $saved_state['dame_recipient_contact_types'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_contact_types'] ) : array();
		$state_ha_method         = isset( $saved_state['dame_ha_method'] ) ? sanitize_key( (string) $saved_state['dame_ha_method'] ) : 'group';
		$state_ha_campaigns      = isset( $saved_state['dame_recipient_ha_campaigns'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_ha_campaigns'] ) : array();
		$state_manual_ha_items   = isset( $saved_state['dame_manual_ha_items'] ) ? array_map( 'absint', (array) $saved_state['dame_manual_ha_items'] ) : array();

		$state_depts             = isset( $saved_state['dame_contact_depts'] ) ? array_map( 'sanitize_text_field', (array) $saved_state['dame_contact_depts'] ) : array();
		$state_regions           = isset( $saved_state['dame_contact_regions'] ) ? array_map( 'sanitize_text_field', (array) $saved_state['dame_contact_regions'] ) : array();
		$state_manual_recipients = isset( $saved_state['dame_manual_recipients'] ) ? array_map( 'absint', (array) $saved_state['dame_manual_recipients'] ) : array();
		$state_manual_contacts   = isset( $saved_state['dame_manual_contacts'] ) ? array_map( 'absint', (array) $saved_state['dame_manual_contacts'] ) : array();
		$state_priority          = isset( $saved_state['dame_sending_priority'] ) ? absint( $saved_state['dame_sending_priority'] ) : 0;
		$state_scheduled_at      = isset( $saved_state['dame_scheduled_at'] ) ? sanitize_text_field( (string) $saved_state['dame_scheduled_at'] ) : '';
		$state_had_attachment    = ! empty( $saved_state['_had_attachment'] );
		$min_scheduled_at        = (string) wp_date( 'Y-m-d\TH:i' );

		// Data fetching.
		$raw_seasons = get_terms(
			array(
				'taxonomy'   => 'dame_saison_adhesion',
				'hide_empty' => false,
			)
		);
		$seasons     = is_array( $raw_seasons ) ? $raw_seasons : array();

		$raw_groups = get_terms(
			array(
				'taxonomy'   => 'dame_group',
				'hide_empty' => false,
			)
		);
		$all_groups = is_array( $raw_groups ) ? $raw_groups : array();

		$raw_contacts  = get_terms(
			array(
				'taxonomy'   => 'dame_contact_type',
				'hide_empty' => false,
			)
		);
		$contact_types = is_array( $raw_contacts ) ? $raw_contacts : array();

		$departments = Data_Provider::get_departments();
		$regions     = Data_Provider::get_regions();

		$saisonniers = array();
		$permanents  = array();
		foreach ( $all_groups as $group ) {
			$type = get_term_meta( $group->term_id, '_dame_group_type', true );
			if ( is_array( $type ) ) {
				$type = reset( $type );
			}
			if ( 'permanent' === $type ) {
				$permanents[] = $group;
			} else {
				$saisonniers[] = $group;
			}
		}

		$messages  = get_posts(
			array(
				'post_type'      => 'dame_message',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$adherents = get_posts(
			array(
				'post_type'      => 'adherent',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$contacts  = get_posts(
			array(
				'post_type'      => 'dame_contact',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$ticketing_repo = new \DAME\Repositories\TicketingRepository();
		$ha_campaigns   = $ticketing_repo->get_campaigns_with_active_items();
		$ha_items       = $ticketing_repo->get_all_active_items();
		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Envoyer un message', 'dame' ); ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dame_message' ) ); ?>" class="page-title-action">
					<?php esc_html_e( '← Retour aux messages', 'dame' ); ?>
				</a>
			</h1>

			<?php $this->render_notices( $success, $count, $error, $state_had_attachment ); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="dame-mailing-form-wrap">
				<input type="hidden" name="action" value="dame_process_mailing">
				<?php wp_nonce_field( 'dame_mailing_action', 'dame_mailing_nonce' ); ?>

				<!-- PARAMÈTRES GÉNÉRAUX EN GRILLE 2x2 COMPACTE -->
				<div class="dame-mailing-settings-card">
					<div class="dame-mailing-settings-grid">
						<!-- Ligne 1, Col 1 : Message à envoyer -->
						<div class="dame-mailing-field">
							<label for="dame_message_to_send"><strong><?php esc_html_e( 'Message à envoyer', 'dame' ); ?></strong> <span class="description" style="color: #d63638;">*</span></label>
							<select name="dame_message_to_send" id="dame_message_to_send" required class="widefat">
								<option value=""><?php esc_html_e( 'Sélectionner un message...', 'dame' ); ?></option>
								<?php foreach ( $messages as $message ) : ?>
									<?php $status = get_post_meta( $message->ID, '_dame_message_status', true ); ?>
									<option value="<?php echo esc_attr( (string) $message->ID ); ?>" <?php selected( $state_message, $message->ID ); ?> data-status="<?php echo esc_attr( (string) $status ); ?>"><?php echo esc_html( $message->post_title ); ?> (<?php echo esc_html( $status ? $status : get_post_status( $message->ID ) ); ?>)</option>
								<?php endforeach; ?>
							</select>
							<div id="dame_message_warning" style="color: #d63638; display: none; margin-top: 5px;">
								<?php esc_html_e( 'Ce message a déjà été envoyé. Veuillez le dupliquer pour faire un nouvel envoi.', 'dame' ); ?>
							</div>
						</div>

						<!-- Ligne 1, Col 2 : Pièce jointe -->
						<div class="dame-mailing-field">
							<label for="dame_message_attachment"><strong><?php esc_html_e( 'Pièce jointe', 'dame' ); ?></strong> <span class="description">(<?php esc_html_e( 'Optionnel', 'dame' ); ?>)</span></label>
							<input type="file" name="dame_message_attachment" id="dame_message_attachment" class="widefat">
							<p class="description"><?php esc_html_e( 'Le fichier sera envoyé à tous les destinataires.', 'dame' ); ?></p>
						</div>

						<!-- Ligne 2, Col 1 : Priorité d'envoi -->
						<div class="dame-mailing-field">
							<label for="dame_sending_priority"><strong><?php esc_html_e( 'Priorité d\'envoi', 'dame' ); ?></strong> <span class="description" style="color: #d63638;">*</span></label>
							<select name="dame_sending_priority" id="dame_sending_priority" required class="widefat">
								<option value=""><?php esc_html_e( '— Sélectionner la priorité d\'envoi —', 'dame' ); ?></option>
								<option value="10" <?php selected( $state_priority, 10 ); ?>><?php esc_html_e( 'Normale (Au fil de l\'eau)', 'dame' ); ?></option>
								<option value="20" <?php selected( $state_priority, 20 ); ?>><?php esc_html_e( 'Nocturne / Basse (Plage 22h00 – 06h00)', 'dame' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Obligatoire. La priorité nocturne reporte automatiquement l\'expédition des envois de masse à la fenêtre 22h00 – 06h00.', 'dame' ); ?></p>
						</div>

						<!-- Ligne 2, Col 2 : Date d'envoi programmé -->
						<div class="dame-mailing-field">
							<label for="dame_scheduled_at"><strong><?php esc_html_e( 'Date et heure d\'envoi programmé', 'dame' ); ?></strong> <span class="description">(<?php esc_html_e( 'Optionnel', 'dame' ); ?>)</span></label>
							<input type="datetime-local" name="dame_scheduled_at" id="dame_scheduled_at" value="<?php echo esc_attr( $state_scheduled_at ); ?>" min="<?php echo esc_attr( $min_scheduled_at ); ?>" class="widefat">
							<p class="description"><?php esc_html_e( 'Laissez vide pour un envoi immédiat selon la priorité choisie. En cas de priorité Nocturne (20), la date programmée définit l\'échéance à partir de laquelle l\'envoi nocturne (22h00 – 06h00) débutera.', 'dame' ); ?></p>
						</div>
					</div>
				</div>

				<!-- TROIS COLONNES (25% Adhérents, 50% Contacts, 25% HelloAsso) -->
				<div class="dame-mailing-columns" style="display:flex; gap: 20px;">
					<!-- Colonne 1 : Adhérents (25%) -->
					<div class="dame-mailing-col dame-mailing-col--adherents" style="flex:1; min-width: 250px; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
						<h3><?php esc_html_e( 'Filtres Adhérents', 'dame' ); ?></h3>

						<div style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
							<label><input type="radio" name="dame_adherent_method" value="group" <?php checked( $state_adherent_method, 'group' ); ?>> <?php esc_html_e( 'Par critères', 'dame' ); ?></label>
							<label style="margin-left: 15px;"><input type="radio" name="dame_adherent_method" value="manual" <?php checked( $state_adherent_method, 'manual' ); ?>> <?php esc_html_e( 'Sélection manuelle', 'dame' ); ?></label>
						</div>

						<div class="dame-adherent-group-wrap <?php echo 'group' === $state_adherent_method ? '' : 'dame-hidden'; ?>">
							<div style="margin-bottom: 15px;">
								<label><strong><?php esc_html_e( 'Sexe', 'dame' ); ?></strong></label><br>
								<select name="dame_recipient_gender" class="widefat">
									<option value="all" <?php selected( $state_gender, 'all' ); ?>><?php esc_html_e( 'Tous', 'dame' ); ?></option>
									<option value="Masculin" <?php selected( $state_gender, 'Masculin' ); ?>><?php esc_html_e( 'Masculin', 'dame' ); ?></option>
									<option value="Féminin" <?php selected( $state_gender, 'Féminin' ); ?>><?php esc_html_e( 'Féminin', 'dame' ); ?></option>
								</select>
							</div>

							<div style="margin-bottom: 15px;">
								<label><strong><?php esc_html_e( 'Saisons', 'dame' ); ?></strong></label><br>
								<select name="dame_recipient_seasons[]" multiple size="5" class="widefat">
									<?php foreach ( $seasons as $s ) : ?>
										<option value="<?php echo esc_attr( (string) $s->term_id ); ?>" <?php echo in_array( (int) $s->term_id, $state_seasons, true ) ? 'selected' : ''; ?>><?php echo esc_html( $s->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div style="margin-bottom: 15px;">
								<label><strong><?php esc_html_e( 'Groupes Saisonniers', 'dame' ); ?></strong></label><br>
								<select name="dame_recipient_groups_saisonnier[]" multiple size="5" class="widefat">
									<?php foreach ( $saisonniers as $g ) : ?>
										<option value="<?php echo esc_attr( (string) $g->term_id ); ?>" <?php echo in_array( (int) $g->term_id, $state_groups_saisonnier, true ) ? 'selected' : ''; ?>><?php echo esc_html( $g->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div style="margin-bottom: 15px;">
								<label><strong><?php esc_html_e( 'Groupes Permanents', 'dame' ); ?></strong></label><br>
								<select name="dame_recipient_groups_permanent[]" multiple size="5" class="widefat">
									<?php foreach ( $permanents as $g ) : ?>
										<option value="<?php echo esc_attr( (string) $g->term_id ); ?>" <?php echo in_array( (int) $g->term_id, $state_groups_permanent, true ) ? 'selected' : ''; ?>><?php echo esc_html( $g->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="dame-adherent-manual-wrap <?php echo 'manual' === $state_adherent_method ? '' : 'dame-hidden'; ?>">
							<?php
							$this->render_searchable_list(
								__( 'Rechercher un adhérent...', 'dame' ),
								$adherents,
								'dame_manual_recipients',
								$state_manual_recipients,
								fn( $a ) => $a->post_title
							);
							?>
						</div>
					</div>

					<!-- Colonne 2 : Contacts (50%) -->
					<div class="dame-mailing-col dame-mailing-col--contacts" style="flex:2; min-width: 350px; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
						<h3><?php esc_html_e( 'Filtres Contacts', 'dame' ); ?></h3>

						<div style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
							<label><input type="radio" name="dame_contact_method" value="group" <?php checked( $state_contact_method, 'group' ); ?>> <?php esc_html_e( 'Par critères', 'dame' ); ?></label>
							<label style="margin-left: 15px;"><input type="radio" name="dame_contact_method" value="manual" <?php checked( $state_contact_method, 'manual' ); ?>> <?php esc_html_e( 'Sélection manuelle', 'dame' ); ?></label>
						</div>

						<div class="dame-contact-group-wrap <?php echo 'group' === $state_contact_method ? '' : 'dame-hidden'; ?>">
							<div style="margin-bottom: 15px;">
								<label><strong><?php esc_html_e( 'Types de Contacts', 'dame' ); ?></strong></label><br>
								<select name="dame_recipient_contact_types[]" id="dame_contact_types_select" multiple size="5" class="widefat">
									<?php foreach ( $contact_types as $t ) : ?>
										<option value="<?php echo esc_attr( (string) $t->term_id ); ?>" <?php echo in_array( (int) $t->term_id, $state_contact_types, true ) ? 'selected' : ''; ?>><?php echo esc_html( $t->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div style="display: flex; gap: 15px; margin-bottom: 15px;">
								<div style="flex: 1; min-width: 0;" class="dame-region-criteria-list">
									<label><strong><?php esc_html_e( 'Régions', 'dame' ); ?></strong></label><br>
									<?php
									$this->render_searchable_list(
										__( 'Filtrer les régions...', 'dame' ),
										$regions,
										'dame_contact_regions',
										$state_regions,
										fn( $name, $code ) => ( 'NA' === $code ) ? '' : $name
									);
									?>
								</div>

								<div style="flex: 1; min-width: 0;" class="dame-dept-criteria-list">
									<label><strong><?php esc_html_e( 'Départements', 'dame' ); ?></strong></label><br>
									<?php
									$this->render_searchable_list(
										__( 'Filtrer les départements...', 'dame' ),
										$departments,
										'dame_contact_depts',
										$state_depts,
										fn( $name ) => $name
									);
									?>
								</div>
							</div>
						</div>

						<div class="dame-contact-manual-wrap <?php echo 'manual' === $state_contact_method ? '' : 'dame-hidden'; ?>">
							<?php
							$this->render_searchable_list(
								__( 'Rechercher un contact...', 'dame' ),
								$contacts,
								'dame_manual_contacts',
								$state_manual_contacts,
								fn( $c ) => $c->post_title,
								function ( $c ) {
									$dept  = get_post_meta( $c->ID, '_dame_contact_department', true );
									$reg   = get_post_meta( $c->ID, '_dame_contact_region', true );
									$terms = wp_get_post_terms( $c->ID, 'dame_contact_type', array( 'fields' => 'ids' ) );
									$types = is_array( $terms ) ? implode( ',', $terms ) : '';
									return sprintf( 'data-dept="%s" data-region="%s" data-types="%s"', esc_attr( (string) $dept ), esc_attr( (string) $reg ), esc_attr( $types ) );
								}
							);
							?>
						</div>
					</div>

					<!-- Colonne 3 : HelloAsso (25%) -->
					<div class="dame-mailing-col dame-mailing-col--helloasso" style="flex:1; min-width: 250px; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
						<h3><?php esc_html_e( 'Filtres HelloAsso', 'dame' ); ?></h3>

						<div style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
							<label><input type="radio" name="dame_ha_method" value="group" <?php checked( $state_ha_method, 'group' ); ?>> <?php esc_html_e( 'Par critères', 'dame' ); ?></label>
							<label style="margin-left: 15px;"><input type="radio" name="dame_ha_method" value="manual" <?php checked( $state_ha_method, 'manual' ); ?>> <?php esc_html_e( 'Sélection manuelle', 'dame' ); ?></label>
						</div>

						<div class="dame-ha-group-wrap <?php echo 'group' === $state_ha_method ? '' : 'dame-hidden'; ?>">
							<div style="margin-bottom: 15px;">
								<label><strong><?php esc_html_e( 'Campagnes & Événements HelloAsso', 'dame' ); ?></strong></label><br>
								<?php
								$campaign_items_map = array();
								foreach ( $ha_campaigns as $camp ) {
									$campaign_items_map[ (int) $camp['id'] ] = sprintf(
										'%s (%d)',
										(string) $camp['title'],
										(int) ( $camp['active_items_count'] ?? 0 )
									);
								}
								$this->render_searchable_list(
									__( 'Rechercher une campagne...', 'dame' ),
									$campaign_items_map,
									'dame_recipient_ha_campaigns',
									$state_ha_campaigns,
									fn( $label ) => $label
								);
								?>
							</div>
						</div>

						<div class="dame-ha-manual-wrap <?php echo 'manual' === $state_ha_method ? '' : 'dame-hidden'; ?>">
							<?php
							$participant_items_map = array();
							foreach ( $ha_items as $item ) {
								$part_fn    = trim( (string) ( $item['participant_first_name'] ?? '' ) );
								$part_ln    = trim( (string) ( $item['participant_last_name'] ?? '' ) );
								$part_label = trim( $part_fn . ' ' . $part_ln );
								if ( empty( $part_label ) ) {
									$part_label = __( 'Participant', 'dame' );
								}
								$participant_items_map[ (int) $item['id'] ] = $item;
							}
							$this->render_searchable_list(
								__( 'Rechercher un participant...', 'dame' ),
								$participant_items_map,
								'dame_manual_ha_items',
								$state_manual_ha_items,
								function ( $item ) {
									$part_fn    = trim( (string) ( $item['participant_first_name'] ?? '' ) );
									$part_ln    = trim( (string) ( $item['participant_last_name'] ?? '' ) );
									$part_label = trim( $part_fn . ' ' . $part_ln );
									if ( empty( $part_label ) ) {
										$part_label = __( 'Participant', 'dame' );
									}
									$camp_title = (string) ( $item['campaign_title'] ?? '' );
									return sprintf( '%s — %s', $part_label, $camp_title );
								},
								fn( $item ) => sprintf( 'data-campaign="%d"', (int) ( $item['campaign_id'] ?? 0 ) )
							);
							?>
						</div>
					</div>
				</div>

				<!-- BARRE D'ACTION STICKY (Toujours accessible sans scroll) -->
				<div class="dame-mailing-sticky-bar">
					<div class="dame-mailing-sticky-info">
						<span class="dame-sticky-icon" aria-hidden="true">✉️</span>
						<span id="dame_sticky_message_name" class="dame-sticky-message-name"><?php esc_html_e( 'Aucun message sélectionné', 'dame' ); ?></span>
						<span class="dame-sticky-sep">|</span>
						<span id="dame_sticky_priority_name" class="dame-sticky-priority-name"><?php esc_html_e( 'Priorité non définie', 'dame' ); ?></span>
					</div>
					<div class="dame-mailing-sticky-action">
						<button type="submit" id="dame_submit_mailing_btn" class="button button-primary button-large">
							<?php esc_html_e( 'Envoyer le message', 'dame' ); ?> ➔
						</button>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Helper for searchable checkbox lists.
	 *
	 * @param string        $placeholder Search placeholder text.
	 * @param array<mixed>  $items Items to render.
	 * @param string        $name_attr HTML input name.
	 * @param array<mixed>  $checked_items Checked IDs/keys.
	 * @param callable      $label_callback Callback returning label text.
	 * @param callable|null $data_callback Callback returning data-* HTML attributes.
	 */
	public function render_searchable_list( string $placeholder, array $items, string $name_attr, array $checked_items, callable $label_callback, ?callable $data_callback = null ): void {
		?>
		<div class="dame-searchable-list-wrapper">
			<div class="dame-search-header" style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
				<input type="text" class="dame-list-search regular-text" style="flex: 1; margin: 0;" placeholder="<?php echo esc_attr( $placeholder ); ?>">
				<span class="dame-selection-count" title="<?php esc_attr_e( 'Nombre d\'éléments sélectionnés', 'dame' ); ?>" style="background: #2271b1; color: #fff; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; white-space: nowrap;">
					<?php echo count( $checked_items ); ?>
				</span>
			</div>
			<div class="dame-checkbox-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; background: #f9f9f9;">
				<?php
				foreach ( $items as $key => $value ) :
					$id    = is_object( $value ) ? ( isset( $value->ID ) ? $value->ID : ( isset( $value->term_id ) ? $value->term_id : $key ) ) : $key;
					$label = $label_callback( $value, $key );
					if ( empty( $label ) ) {
						continue;
					}
					$is_checked = in_array( $id, $checked_items, true ) || in_array( (string) $id, array_map( 'strval', $checked_items ), true );
					$data_attrs = $data_callback ? $data_callback( $value, $key ) : '';
					?>
					<label style="display: block;">
						<input type="checkbox" name="<?php echo esc_attr( $name_attr ); ?>[]" value="<?php echo esc_attr( (string) $id ); ?>" <?php checked( $is_checked ); ?> <?php echo $data_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>> 
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render admin notices on the page.
	 *
	 * @param int    $success Success indicator.
	 * @param int    $count Recipient count.
	 * @param string $error Error code.
	 * @param bool   $had_attachment Whether an attachment was previously uploaded.
	 */
	private function render_notices( int $success, int $count, string $error, bool $had_attachment ): void {
		if ( 1 === $success && $count > 0 ) :
			?>
			<div class="notice notice-success is-dismissible">
				<p>
				<?php
				/* translators: %d is the number of scheduled messages. */
				echo esc_html( sprintf( __( 'Envoi transmis avec succès. %d messages ont été pris en charge pour expédition.', 'dame' ), (int) $count ) );
				?>
				</p>
			</div>
		<?php endif; ?>

		<?php
		if ( ! empty( $error ) ) :
			$allowed_types = 'JPG, PNG, PDF, DOC, DOCX, ODT';
			$error_message = match ( $error ) {
				'nonce'                => __( 'Vérification de sécurité échouée.', 'dame' ),
				'permission'           => __( 'Permission refusée.', 'dame' ),
				'invalid_message'        => __( 'Message invalide.', 'dame' ),
				'no_priority'            => __( 'Veuillez obligatoirement sélectionner une priorité d\'envoi (Normale ou Nocturne).', 'dame' ),
				'invalid_scheduled_date' => __( 'La date et l\'heure d\'envoi programmé doivent être valides et situées dans le futur.', 'dame' ),
				/* translators: %s is the allowed file extensions. */
				'upload_failed'        => sprintf( __( 'Erreur lors du téléchargement de la pièce jointe. Types autorisés : %s.', 'dame' ), $allowed_types ),
				'no_criteria'          => __( 'Veuillez sélectionner au moins un critère (Saison, Groupe ou Zone).', 'dame' ),
				'no_recipients'        => __( 'Aucun destinataire trouvé avec ces critères.', 'dame' ),
				'no_valid_emails'      => __( 'Les destinataires trouvés ne possèdent pas d\'adresse e-mail valide ou ont refusé les communications.', 'dame' ),
				'all_already_received' => __( 'Tous les destinataires sélectionnés ont déjà reçu ce message. Aucun nouvel envoi n\'a été programmé.', 'dame' ),
				default                => __( 'Une erreur inconnue est survenue.', 'dame' ),
			};
			?>
			<div class="notice notice-error is-dismissible">
				<p><strong><?php echo esc_html( $error_message ); ?></strong></p>
				<?php if ( $had_attachment || 'upload_failed' === $error ) : ?>
					<p style="color: #d63638;"><?php esc_html_e( '⚠️ IMPORTANT : Votre pièce jointe doit être re-sélectionnée avant de valider à nouveau le formulaire.', 'dame' ); ?></p>
				<?php endif; ?>
			</div>
			<?php
		endif;
	}
}
