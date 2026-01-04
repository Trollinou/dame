<?php
/**
 * Mailing Page.
 *
 * @package DAME
 */

namespace DAME\Admin\Pages;

use DAME\Services\Data_Provider;
use WP_Query;

/**
 * Class Mailing
 */
class Mailing {

	/**
	 * Initialize the page.
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'register_menu_page' ] );
		add_action( 'admin_post_dame_process_mailing', [ $this, 'process_mailing' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Register the submenu page.
	 */
	public function register_menu_page() {
		add_submenu_page(
			'edit.php?post_type=adherent',
			__( 'Envoyer un message', 'dame' ),
			__( 'Envoyer un message', 'dame' ),
			'edit_dame_messages',
			'dame-mailing',
			[ $this, 'render' ]
		);
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'adherent_page_dame-mailing' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'dame-mailing-js',
			DAME_URL . 'admin/js/mailing.js', // Assuming this file exists.
			array( 'jquery' ),
			DAME_VERSION,
			true
		);
	}

	/**
	 * Render the mailing page.
	 */
	public function render() {
		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			return;
		}

		// Get all seasons.
		$seasons = get_terms( array(
			'taxonomy'   => 'dame_saison_adhesion',
			'hide_empty' => false,
		) );

		// Get all groups and separate them by type (Saisonnier / Permanent).
		$all_groups   = get_terms( array(
			'taxonomy'   => 'dame_group',
			'hide_empty' => false,
		) );
		$saisonniers  = array();
		$permanents   = array();
		$other_groups = array();

		foreach ( $all_groups as $group ) {
			$type = get_term_meta( $group->term_id, '_dame_group_type', true );
			if ( 'saisonnier' === $type ) {
				$saisonniers[] = $group;
			} elseif ( 'permanent' === $type ) {
				$permanents[] = $group;
			} else {
				$other_groups[] = $group;
			}
		}

		// Get draft messages.
		$messages = get_posts( array(
			'post_type'      => 'dame_message',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		) );

		// Get all adherents for manual selection (could be heavy, but requested).
		$adherents = get_posts( array(
			'post_type'      => 'adherent',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Envoyer un message', 'dame' ); ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dame_process_mailing">
				<?php wp_nonce_field( 'dame_mailing_action', 'dame_mailing_nonce' ); ?>

				<table class="form-table">
					<!-- Message Selection -->
					<tr>
						<th scope="row"><label for="dame_message_id"><?php esc_html_e( 'Message à envoyer', 'dame' ); ?></label></th>
						<td>
							<select name="dame_message_id" id="dame_message_id" required>
								<option value=""><?php esc_html_e( 'Sélectionner un message...', 'dame' ); ?></option>
								<?php foreach ( $messages as $message ) : ?>
									<option value="<?php echo esc_attr( $message->ID ); ?>"><?php echo esc_html( $message->post_title ); ?> (<?php echo esc_html( get_post_status( $message->ID ) ); ?>)</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<!-- Method Selection -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Méthode de sélection', 'dame' ); ?></th>
						<td>
							<fieldset id="dame_selection_method">
								<label><input type="radio" name="dame_recipient_method" value="group" checked> <?php esc_html_e( 'Par critères (Groupes, Saisons)', 'dame' ); ?></label><br>
								<label><input type="radio" name="dame_recipient_method" value="manual"> <?php esc_html_e( 'Sélection manuelle', 'dame' ); ?></label>
							</fieldset>
						</td>
					</tr>
				</table>

				<!-- Group Filters Container -->
				<div class="dame-group-filters">
					<h3><?php esc_html_e( 'Critères de sélection', 'dame' ); ?></h3>
					<table class="form-table">
						<!-- Seasons -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Saisons', 'dame' ); ?></th>
							<td>
								<fieldset>
									<?php foreach ( $seasons as $season ) : ?>
										<label>
											<input type="checkbox" name="dame_season_filters[]" value="<?php echo esc_attr( $season->term_id ); ?>">
											<?php echo esc_html( $season->name ); ?>
										</label><br>
									<?php endforeach; ?>
								</fieldset>
								<p class="description"><?php esc_html_e( 'Cochez au moins une saison.', 'dame' ); ?></p>
							</td>
						</tr>

						<!-- Groups -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Groupes', 'dame' ); ?></th>
							<td>
								<fieldset>
									<?php if ( ! empty( $permanents ) ) : ?>
										<strong style="display:block; margin-top:5px;"><?php esc_html_e( 'Groupes Permanents', 'dame' ); ?></strong>
										<?php foreach ( $permanents as $group ) : ?>
											<label>
												<input type="checkbox" name="dame_group_filters[]" value="<?php echo esc_attr( $group->term_id ); ?>">
												<?php echo esc_html( $group->name ); ?>
											</label><br>
										<?php endforeach; ?>
									<?php endif; ?>

									<?php if ( ! empty( $saisonniers ) ) : ?>
										<strong style="display:block; margin-top:10px;"><?php esc_html_e( 'Groupes Saisonniers', 'dame' ); ?></strong>
										<?php foreach ( $saisonniers as $group ) : ?>
											<label>
												<input type="checkbox" name="dame_group_filters[]" value="<?php echo esc_attr( $group->term_id ); ?>">
												<?php echo esc_html( $group->name ); ?>
											</label><br>
										<?php endforeach; ?>
									<?php endif; ?>

									<?php if ( ! empty( $other_groups ) ) : ?>
										<strong style="display:block; margin-top:10px;"><?php esc_html_e( 'Autres', 'dame' ); ?></strong>
										<?php foreach ( $other_groups as $group ) : ?>
											<label>
												<input type="checkbox" name="dame_group_filters[]" value="<?php echo esc_attr( $group->term_id ); ?>">
												<?php echo esc_html( $group->name ); ?>
											</label><br>
										<?php endforeach; ?>
									<?php endif; ?>
								</fieldset>
								<p class="description"><?php esc_html_e( 'Laissez vide pour sélectionner tous les groupes.', 'dame' ); ?></p>
							</td>
						</tr>

						<!-- Gender -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Genre', 'dame' ); ?></th>
							<td>
								<fieldset>
									<label><input type="radio" name="dame_recipient_gender" value="all" checked> <?php esc_html_e( 'Tous', 'dame' ); ?></label><br>
									<label><input type="radio" name="dame_recipient_gender" value="M"> <?php esc_html_e( 'Masculin', 'dame' ); ?></label><br>
									<label><input type="radio" name="dame_recipient_gender" value="F"> <?php esc_html_e( 'Féminin', 'dame' ); ?></label>
								</fieldset>
							</td>
						</tr>
					</table>
				</div>

				<!-- Manual Filters Container -->
				<div class="dame-manual-filters" style="display:none;">
					<h3><?php esc_html_e( 'Sélection manuelle des adhérents', 'dame' ); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="dame_manual_recipients"><?php esc_html_e( 'Adhérents', 'dame' ); ?></label></th>
							<td>
								<select name="dame_manual_recipients[]" id="dame_manual_recipients" multiple style="height: 300px; width: 100%;">
									<?php foreach ( $adherents as $adherent ) : ?>
										<?php
										$lastname = get_post_meta( $adherent->ID, '_dame_last_name', true );
										$firstname = get_post_meta( $adherent->ID, '_dame_first_name', true );
										$name = mb_strtoupper( $lastname ) . ' ' . $firstname;
										if ( empty( trim( $name ) ) ) {
											$name = $adherent->post_title;
										}
										?>
										<option value="<?php echo esc_attr( $adherent->ID ); ?>"><?php echo esc_html( $name ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs adhérents.', 'dame' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button( __( 'Envoyer le message', 'dame' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Process the mailing form submission.
	 */
	public function process_mailing() {
		if ( ! isset( $_POST['dame_mailing_nonce'] ) || ! wp_verify_nonce( $_POST['dame_mailing_nonce'], 'dame_mailing_action' ) ) {
			wp_die( __( 'Vérification de sécurité échouée.', 'dame' ) );
		}

		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			wp_die( __( 'Permission refusée.', 'dame' ) );
		}

		$message_id = isset( $_POST['dame_message_id'] ) ? absint( $_POST['dame_message_id'] ) : 0;
		if ( ! $message_id ) {
			wp_die( __( 'Message invalide.', 'dame' ) );
		}

		$method = isset( $_POST['dame_recipient_method'] ) ? sanitize_key( $_POST['dame_recipient_method'] ) : 'group';
		$recipient_emails = array();
		$adherent_ids = array();

		if ( 'manual' === $method ) {
			if ( ! empty( $_POST['dame_manual_recipients'] ) && is_array( $_POST['dame_manual_recipients'] ) ) {
				$adherent_ids = array_map( 'absint', $_POST['dame_manual_recipients'] );
			}
		} else {
			// Group method.
			$seasons = isset( $_POST['dame_season_filters'] ) ? array_map( 'absint', $_POST['dame_season_filters'] ) : array();
			$groups  = isset( $_POST['dame_group_filters'] ) ? array_map( 'absint', $_POST['dame_group_filters'] ) : array();
			$gender  = isset( $_POST['dame_recipient_gender'] ) ? sanitize_text_field( $_POST['dame_recipient_gender'] ) : 'all';

			if ( empty( $seasons ) ) {
				wp_die( __( 'Veuillez sélectionner au moins une saison.', 'dame' ) );
			}

			// Build Query.
			$args = array(
				'post_type'      => 'adherent',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'dame_saison_adhesion',
						'field'    => 'term_id',
						'terms'    => $seasons,
						'operator' => 'IN',
					),
				),
			);

			// Add Groups filter if selected.
			if ( ! empty( $groups ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'dame_group',
					'field'    => 'term_id',
					'terms'    => $groups,
					'operator' => 'IN',
				);
			}

			// Add Gender filter if not 'all'.
			if ( 'all' !== $gender ) {
				$args['meta_query'] = array(
					array(
						'key'   => '_dame_gender',
						'value' => $gender,
					),
				);
			}

			$adherent_ids = get_posts( $args );
		}

		// Retrieve emails for each adherent.
		foreach ( $adherent_ids as $adherent_id ) {
			$emails = Data_Provider::get_emails_for_adherent( $adherent_id );
			$recipient_emails = array_merge( $recipient_emails, $emails );
		}

		$recipient_emails = array_unique( $recipient_emails );

		if ( empty( $recipient_emails ) ) {
			wp_die( __( 'Aucun destinataire trouvé avec ces critères.', 'dame' ) );
		}

		// Update Message status and meta.
		update_post_meta( $message_id, '_dame_message_status', 'scheduled' );
		update_post_meta( $message_id, '_dame_message_recipients_count', count( $recipient_emails ) );
		update_post_meta( $message_id, '_dame_recipient_method', $method );

		if ( 'group' === $method ) {
			if ( isset( $seasons ) ) update_post_meta( $message_id, '_dame_target_seasons', $seasons ); // Note: plural 'seasons' now
			if ( isset( $groups ) ) update_post_meta( $message_id, '_dame_target_groups', $groups );
			if ( isset( $gender ) ) update_post_meta( $message_id, '_dame_target_gender', $gender );
		}

		// Schedule batches (20 per minute).
		$chunks = array_chunk( $recipient_emails, 20 );
		$total_batches = count( $chunks );

		update_post_meta( $message_id, '_dame_scheduled_batches_total', $total_batches );
		update_post_meta( $message_id, '_dame_scheduled_batches_processed', 0 );

		$delay = 0;
		foreach ( $chunks as $chunk_emails ) {
			wp_schedule_single_event(
				time() + $delay,
				'dame_cron_send_batch',
				array( $message_id, $chunk_emails, 0 )
			);
			$delay += 60; // Add 1 minute delay for next batch.
		}

		// Redirect back with success message.
		$redirect_url = add_query_arg(
			array(
				'page'    => 'dame-mailing',
				'success' => 1,
				'count'   => count( $recipient_emails ),
			),
			admin_url( 'edit.php?post_type=adherent' )
		);

		wp_redirect( $redirect_url );
		exit;
	}
}
