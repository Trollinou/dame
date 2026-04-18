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

		add_action( 'admin_post_dame_process_mailing', [ $this, 'process_mailing' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}


	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'dame-mailing' ) === false ) {
			return;
		}

		wp_enqueue_script(
			'dame-admin-mailing',
			\DAME_PLUGIN_URL . 'assets/js/admin-mailing.js',
			array( 'jquery' ),
			\DAME_VERSION,
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

		// Récupération de l'état sauvegardé en cas d'erreur précédente.
		$user_id     = get_current_user_id();
		$state_key   = 'dame_mailing_state_' . $user_id;
		$saved_state = get_transient( $state_key );
		
		// On supprime le transient immédiatement après lecture pour qu'il ne serve qu'une fois.
		if ( false !== $saved_state ) {
			delete_transient( $state_key );
		}

		// Initialisation des variables par défaut ou sauvegardées.
		$state_message           = isset( $saved_state['dame_message_to_send'] ) ? absint( $saved_state['dame_message_to_send'] ) : 0;
		$state_method            = isset( $saved_state['dame_selection_method'] ) ? sanitize_key( $saved_state['dame_selection_method'] ) : 'group';
		$state_gender            = isset( $saved_state['dame_recipient_gender'] ) ? sanitize_text_field( $saved_state['dame_recipient_gender'] ) : 'all';
		$state_seasons           = isset( $saved_state['dame_recipient_seasons'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_seasons'] ) : array();
		$state_groups_saisonnier = isset( $saved_state['dame_recipient_groups_saisonnier'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_groups_saisonnier'] ) : array();
		$state_groups_permanent  = isset( $saved_state['dame_recipient_groups_permanent'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_groups_permanent'] ) : array();
		$state_manual_recipients = isset( $saved_state['dame_manual_recipients'] ) ? array_map( 'absint', (array) $saved_state['dame_manual_recipients'] ) : array();
		$state_had_attachment    = ! empty( $saved_state['_had_attachment'] );

		// Gestion des notifications (Admin Notices).
		$success = isset( $_GET['success'] ) ? absint( $_GET['success'] ) : 0;
		$count   = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0;
		$error   = isset( $_GET['error'] ) ? sanitize_key( $_GET['error'] ) : '';

		// Get all seasons.
		$seasons = get_terms( array(
			'taxonomy'   => 'dame_saison_adhesion',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'DESC',
		) );

		// Get all groups and separate them by type (Saisonnier / Permanent).
		$all_groups = get_terms( array(
			'taxonomy'   => 'dame_group',
			'hide_empty' => false,
		) );

		$saisonniers = array();
		$permanents  = array();

		foreach ( $all_groups as $group ) {
			$type = get_term_meta( $group->term_id, '_dame_group_type', true );
			if ( 'permanent' === $type ) {
				$permanents[] = $group;
			} else {
				$saisonniers[] = $group;
			}
		}

		// Get published messages - Triés par date décroissante.
		$messages = get_posts( array(
			'post_type'      => 'dame_message',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		// Get all published adherents for manual selection.
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

			<?php
			// Affichage des notifications de succès.
			if ( 1 === $success && $count > 0 ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( sprintf( __( 'Planification réussie. %d messages sont en cours d\'envoi.', 'dame' ), $count ) ); ?></p>
				</div>
			<?php endif; ?>

			<?php
			// Affichage des notifications d'erreur.
			if ( ! empty( $error ) ) :
				$allowed_types = 'JPG, PNG, PDF, DOC, DOCX, ODT';
				$error_message = match ( $error ) {
					'nonce'           => __( 'Vérification de sécurité échouée.', 'dame' ),
					'permission'      => __( 'Permission refusée.', 'dame' ),
					'invalid_message' => __( 'Message invalide.', 'dame' ),
					'upload_failed'   => sprintf( __( 'Erreur lors du téléchargement de la pièce jointe. Types autorisés : %s.', 'dame' ), $allowed_types ),
					'no_criteria'     => __( 'Veuillez sélectionner au moins un critère (Saison ou Groupe).', 'dame' ),
					'no_recipients'   => __( 'Aucun destinataire trouvé avec ces critères.', 'dame' ),
					default           => __( 'Une erreur inconnue est survenue.', 'dame' ),
				};
				?>
				<div class="notice notice-error is-dismissible">
					<p><strong><?php echo esc_html( $error_message ); ?></strong></p>
					<?php if ( $state_had_attachment || 'upload_failed' === $error ) : ?>
						<p style="color: #d63638;"><?php esc_html_e( '⚠️ IMPORTANT : Votre pièce jointe doit être re-sélectionnée avant de valider à nouveau le formulaire.', 'dame' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="dame_process_mailing">
				<?php wp_nonce_field( 'dame_mailing_action', 'dame_mailing_nonce' ); ?>

				<table class="form-table">
					<!-- Message Selection -->
					<tr>
						<th scope="row"><label for="dame_message_to_send"><?php esc_html_e( 'Message à envoyer', 'dame' ); ?></label></th>
						<td>
							<select name="dame_message_to_send" id="dame_message_to_send" required>
								<option value=""><?php esc_html_e( 'Sélectionner un message...', 'dame' ); ?></option>
								<?php foreach ( $messages as $message ) : ?>
									<?php
									$status = get_post_meta( $message->ID, '_dame_message_status', true );
									?>
									<option value="<?php echo esc_attr( $message->ID ); ?>" <?php selected( $state_message, $message->ID ); ?> data-status="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $message->post_title ); ?> (<?php echo esc_html( $status ? $status : get_post_status( $message->ID ) ); ?>)</option>
								<?php endforeach; ?>
							</select>
							<div id="dame_message_warning" style="color: #d63638; display: none; margin-top: 5px;">
								<?php esc_html_e( 'Ce message a déjà été envoyé. Veuillez le dupliquer pour faire un nouvel envoi.', 'dame' ); ?>
							</div>
						</td>
					</tr>

					<!-- Attachment Selection -->
					<tr>
						<th scope="row"><label for="dame_message_attachment"><?php esc_html_e( 'Pièce jointe (Optionnel)', 'dame' ); ?></label></th>
						<td>
							<input type="file" name="dame_message_attachment" id="dame_message_attachment">
							<p class="description"><?php esc_html_e( 'Le fichier sera envoyé à tous les destinataires.', 'dame' ); ?></p>
							<p class="description" style="color: #646970; font-style: italic;"><?php esc_html_e( 'En cas d\'erreur lors de la soumission, vous devrez re-sélectionner votre pièce jointe.', 'dame' ); ?></p>
						</td>
					</tr>

					<!-- Method Selection -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Méthode de sélection', 'dame' ); ?></th>
						<td>
							<fieldset id="dame_selection_method">
								<label><input type="radio" name="dame_selection_method" value="group" <?php checked( $state_method, 'group' ); ?>> <?php esc_html_e( 'Par critères (Groupes, Saisons)', 'dame' ); ?></label><br>
								<label><input type="radio" name="dame_selection_method" value="manual" <?php checked( $state_method, 'manual' ); ?>> <?php esc_html_e( 'Sélection manuelle', 'dame' ); ?></label>
							</fieldset>
						</td>
					</tr>
				</table>

				<!-- Group Filters Container -->
				<div class="dame-group-filters" <?php echo 'manual' === $state_method ? 'style="display:none;"' : ''; ?>>
					<h3><?php esc_html_e( 'Filtres de destinataires', 'dame' ); ?></h3>
					<table class="form-table">
						<!-- Gender -->
						<tr>
							<th scope="row"><label for="dame_recipient_gender"><?php esc_html_e( 'Genre', 'dame' ); ?></label></th>
							<td>
								<select name="dame_recipient_gender" id="dame_recipient_gender">
									<option value="all" <?php selected( $state_gender, 'all' ); ?>><?php esc_html_e( 'Tous', 'dame' ); ?></option>
									<option value="Masculin" <?php selected( $state_gender, 'Masculin' ); ?>><?php esc_html_e( 'Masculin', 'dame' ); ?></option>
									<option value="Féminin" <?php selected( $state_gender, 'Féminin' ); ?>><?php esc_html_e( 'Féminin', 'dame' ); ?></option>
								</select>
							</td>
						</tr>

						<!-- Seasons -->
						<tr>
							<th scope="row"><label for="dame_recipient_seasons"><?php esc_html_e( 'Saisons', 'dame' ); ?></label></th>
							<td>
								<select name="dame_recipient_seasons[]" id="dame_recipient_seasons" multiple style="height: 150px; width: 300px;">
									<?php foreach ( $seasons as $season ) : ?>
										<option value="<?php echo esc_attr( $season->term_id ); ?>" <?php echo in_array( $season->term_id, $state_seasons, true ) ? 'selected' : ''; ?>><?php echo esc_html( $season->name ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs.', 'dame' ); ?></p>
							</td>
						</tr>

						<!-- Seasonal Groups -->
						<tr>
							<th scope="row"><label for="dame_recipient_groups_saisonnier"><?php esc_html_e( 'Groupes Saisonniers', 'dame' ); ?></label></th>
							<td>
								<select name="dame_recipient_groups_saisonnier[]" id="dame_recipient_groups_saisonnier" multiple style="height: 150px; width: 300px;">
									<?php foreach ( $saisonniers as $group ) : ?>
										<option value="<?php echo esc_attr( $group->term_id ); ?>" <?php echo in_array( $group->term_id, $state_groups_saisonnier, true ) ? 'selected' : ''; ?>><?php echo esc_html( $group->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>

						<!-- Permanent Groups -->
						<tr>
							<th scope="row"><label for="dame_recipient_groups_permanent"><?php esc_html_e( 'Groupes Permanents', 'dame' ); ?></label></th>
							<td>
								<select name="dame_recipient_groups_permanent[]" id="dame_recipient_groups_permanent" multiple style="height: 150px; width: 300px;">
									<?php foreach ( $permanents as $group ) : ?>
										<option value="<?php echo esc_attr( $group->term_id ); ?>" <?php echo in_array( $group->term_id, $state_groups_permanent, true ) ? 'selected' : ''; ?>><?php echo esc_html( $group->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<!-- Manual Filters Container -->
				<div class="dame-manual-filters" <?php echo 'manual' === $state_method ? '' : 'style="display:none;"'; ?>>
					<h3><?php esc_html_e( 'Sélection manuelle des adhérents', 'dame' ); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="dame_manual_recipients"><?php esc_html_e( 'Adhérents', 'dame' ); ?></label></th>
							<td>
								<select name="dame_manual_recipients[]" id="dame_manual_recipients" multiple style="width: 100%; max-width: 400px; height: 250px;">
									<?php foreach ( $adherents as $adherent ) : ?>
										<?php
										$lastname  = get_post_meta( $adherent->ID, '_dame_last_name', true );
										$firstname = get_post_meta( $adherent->ID, '_dame_first_name', true );
										$name      = mb_strtoupper( $lastname ) . ' ' . $firstname;
										if ( empty( trim( $name ) ) ) {
											$name = $adherent->post_title;
										}
										?>
										<option value="<?php echo esc_attr( $adherent->ID ); ?>" <?php echo in_array( $adherent->ID, $state_manual_recipients, true ) ? 'selected' : ''; ?>><?php echo esc_html( $name ); ?></option>
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
		$base_url = admin_url( 'admin.php?page=dame-mailing' );
		$user_id  = get_current_user_id();
		$state_key = 'dame_mailing_state_' . $user_id;

		// Fonction interne pour sauvegarder l'état avant redirection.
		$save_state_and_redirect = function( string $error_code ) use ( $base_url, $state_key ) {
			$data = $_POST;
			if ( ! empty( $_FILES['dame_message_attachment']['name'] ) ) {
				$data['_had_attachment'] = true;
			}
			set_transient( $state_key, $data, 300 ); // Sauvegarde 5 minutes.
			wp_redirect( add_query_arg( 'error', $error_code, $base_url ) );
			exit;
		};

		if ( ! isset( $_POST['dame_mailing_nonce'] ) || ! wp_verify_nonce( $_POST['dame_mailing_nonce'], 'dame_mailing_action' ) ) {
			wp_redirect( add_query_arg( 'error', 'nonce', $base_url ) );
			exit;
		}

		if ( ! current_user_can( 'edit_dame_messages' ) ) {
			wp_redirect( add_query_arg( 'error', 'permission', $base_url ) );
			exit;
		}

		$message_id = isset( $_POST['dame_message_to_send'] ) ? absint( $_POST['dame_message_to_send'] ) : 0;
		if ( ! $message_id ) {
			$save_state_and_redirect( 'invalid_message' );
		}

		$method = isset( $_POST['dame_selection_method'] ) ? sanitize_key( $_POST['dame_selection_method'] ) : 'group';
		$adherent_ids = array();

		// For meta storage
		$meta_seasons = array();
		$meta_groups_saisonnier = array();
		$meta_groups_permanent = array();
		$meta_gender = 'all';

		if ( 'manual' === $method ) {
			if ( ! empty( $_POST['dame_manual_recipients'] ) && is_array( $_POST['dame_manual_recipients'] ) ) {
				$adherent_ids = array_map( 'absint', $_POST['dame_manual_recipients'] );
			}
		} else {
			$seasons = isset( $_POST['dame_recipient_seasons'] ) ? array_map( 'absint', $_POST['dame_recipient_seasons'] ) : array();
			$groups_saisonnier = isset( $_POST['dame_recipient_groups_saisonnier'] ) ? array_map( 'absint', $_POST['dame_recipient_groups_saisonnier'] ) : array();
			$groups_permanent = isset( $_POST['dame_recipient_groups_permanent'] ) ? array_map( 'absint', $_POST['dame_recipient_groups_permanent'] ) : array();
			$gender = isset( $_POST['dame_recipient_gender'] ) ? sanitize_text_field( $_POST['dame_recipient_gender'] ) : 'all';

			$meta_seasons = $seasons;
			$meta_groups_saisonnier = $groups_saisonnier;
			$meta_groups_permanent = $groups_permanent;
			$meta_gender = $gender;

			if ( empty( $seasons ) && empty( $groups_saisonnier ) && empty( $groups_permanent ) ) {
				$save_state_and_redirect( 'no_criteria' );
			}

			$args = array(
				'post_type'      => 'adherent',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					'relation' => 'OR',
				),
			);

			if ( ! empty( $seasons ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'dame_saison_adhesion',
					'field'    => 'term_id',
					'terms'    => $seasons,
					'operator' => 'IN',
				);
			}

			$all_groups = array_merge( $groups_saisonnier, $groups_permanent );
			if ( ! empty( $all_groups ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'dame_group',
					'field'    => 'term_id',
					'terms'    => $all_groups,
					'operator' => 'IN',
				);
			}

			if ( 'all' !== $gender ) {
				$args['meta_query'] = array(
					array(
						'key'   => '_dame_sexe',
						'value' => $gender,
					),
				);
			}

			$adherent_ids = get_posts( $args );
		}

		$recipient_emails = array();
		foreach ( $adherent_ids as $adherent_id ) {
			$emails = Data_Provider::get_emails_for_adherent( $adherent_id );
			$recipient_emails = array_merge( $recipient_emails, $emails );
		}

		$recipient_emails = array_unique( $recipient_emails );

		if ( empty( $recipient_emails ) ) {
			$save_state_and_redirect( 'no_recipients' );
		}

		// LOGIQUE D'UPLOAD : Déplacée ici (à la fin) pour ne s'exécuter qu'en cas de succès de validation.
		if ( ! empty( $_FILES['dame_message_attachment']['name'] ) && 0 === $_FILES['dame_message_attachment']['error'] ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			$overrides = array(
				'test_form' => false,
				'mimes'     => array(
					'jpg|jpeg|jpe' => 'image/jpeg',
					'gif'          => 'image/gif',
					'png'          => 'image/png',
					'pdf'          => 'application/pdf',
					'doc'          => 'application/msword',
					'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
					'odt'          => 'application/vnd.oasis.opendocument.text',
				),
			);

			$upload = wp_handle_upload( $_FILES['dame_message_attachment'], $overrides );

			if ( isset( $upload['error'] ) ) {
				$save_state_and_redirect( 'upload_failed' );
			}

			update_post_meta( $message_id, '_dame_message_attachment', $upload['file'] );
		} else {
			delete_post_meta( $message_id, '_dame_message_attachment' );
		}

		// Update Message status and meta.
		update_post_meta( $message_id, '_dame_message_status', 'scheduled' );
		update_post_meta( $message_id, '_dame_message_recipients_count', count( $recipient_emails ) );
		update_post_meta( $message_id, '_dame_recipient_method', $method );

		if ( 'group' === $method ) {
			update_post_meta( $message_id, '_dame_recipient_seasons', $meta_seasons );
			update_post_meta( $message_id, '_dame_recipient_groups_saisonnier', $meta_groups_saisonnier );
			update_post_meta( $message_id, '_dame_recipient_groups_permanent', $meta_groups_permanent );
			update_post_meta( $message_id, '_dame_recipient_gender', $meta_gender );
		} elseif ( 'manual' === $method ) {
			update_post_meta( $message_id, '_dame_manual_recipients', $adherent_ids );
		}

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
			$delay += 60;
		}

		wp_redirect( add_query_arg( array( 'success' => 1, 'count' => count( $recipient_emails ) ), $base_url ) );
		exit;
	}
}
