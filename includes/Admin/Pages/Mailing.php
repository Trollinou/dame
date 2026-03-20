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
			\DAME_PLUGIN_URL . 'assets/js/admin-mailing.js', // Assuming this file exists.
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
				// Default or explicitly saisonnier.
				$saisonniers[] = $group;
			}
		}

		// Get published messages.
		$messages = get_posts( array(
			'post_type'      => 'dame_message',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
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

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
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
									// Fallback if meta status is empty but WP post status is publish? No, assume meta is source of truth for mailing.
									// Actually, WP status 'draft' vs 'publish' is not sending status.
									// Let's use meta status.
									?>
									<option value="<?php echo esc_attr( $message->ID ); ?>" data-status="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $message->post_title ); ?> (<?php echo esc_html( $status ? $status : get_post_status( $message->ID ) ); ?>)</option>
								<?php endforeach; ?>
							</select>
							<div id="dame_message_warning" style="color: #d63638; display: none; margin-top: 5px;">
								<?php esc_html_e( 'Ce message a déjà été envoyé. Veuillez le dupliquer pour faire un nouvel envoi.', 'dame' ); ?>
							</div>
						</td>
					</tr>

					<!-- Method Selection -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Méthode de sélection', 'dame' ); ?></th>
						<td>
							<fieldset id="dame_selection_method">
								<label><input type="radio" name="dame_selection_method" value="group" checked> <?php esc_html_e( 'Par critères (Groupes, Saisons)', 'dame' ); ?></label><br>
								<label><input type="radio" name="dame_selection_method" value="manual"> <?php esc_html_e( 'Sélection manuelle', 'dame' ); ?></label>
							</fieldset>
						</td>
					</tr>
				</table>

				<!-- Group Filters Container -->
				<div class="dame-group-filters">
					<h3><?php esc_html_e( 'Filtres de destinataires', 'dame' ); ?></h3>
					<table class="form-table">
						<!-- Gender -->
						<tr>
							<th scope="row"><label for="dame_recipient_gender"><?php esc_html_e( 'Genre', 'dame' ); ?></label></th>
							<td>
								<select name="dame_recipient_gender" id="dame_recipient_gender">
									<option value="all"><?php esc_html_e( 'Tous', 'dame' ); ?></option>
									<option value="Masculin"><?php esc_html_e( 'Masculin', 'dame' ); ?></option>
									<option value="Féminin"><?php esc_html_e( 'Féminin', 'dame' ); ?></option>
								</select>
							</td>
						</tr>

						<!-- Seasons -->
						<tr>
							<th scope="row"><label for="dame_recipient_seasons"><?php esc_html_e( 'Saisons', 'dame' ); ?></label></th>
							<td>
								<select name="dame_recipient_seasons[]" id="dame_recipient_seasons" multiple style="height: 150px; width: 300px;">
									<?php foreach ( $seasons as $season ) : ?>
										<option value="<?php echo esc_attr( $season->term_id ); ?>"><?php echo esc_html( $season->name ); ?></option>
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
										<option value="<?php echo esc_attr( $group->term_id ); ?>"><?php echo esc_html( $group->name ); ?></option>
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
										<option value="<?php echo esc_attr( $group->term_id ); ?>"><?php echo esc_html( $group->name ); ?></option>
									<?php endforeach; ?>
								</select>
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
		<script>
			// Simple inline script to handle toggle if JS file is not yet loaded/created.
			// Replicating basic logic of admin-mailing.js if not present or cached.
			document.addEventListener('DOMContentLoaded', function() {
				const radios = document.getElementsByName('dame_selection_method');
				const filtersDiv = document.querySelector('.dame-group-filters');
				const manualDiv = document.querySelector('.dame-manual-filters');

				function toggleSections() {
					let method = 'group';
					for (const radio of radios) {
						if (radio.checked) {
							method = radio.value;
							break;
						}
					}
					if (method === 'group') {
						if (filtersDiv) filtersDiv.style.display = 'block';
						if (manualDiv) manualDiv.style.display = 'none';
					} else {
						if (filtersDiv) filtersDiv.style.display = 'none';
						if (manualDiv) manualDiv.style.display = 'block';
					}
				}

				for (const radio of radios) {
					radio.addEventListener('change', toggleSections);
				}
				toggleSections();

				// Message status check
				const messageSelect = document.getElementById('dame_message_to_send');
				const submitButton = document.getElementById('submit');
				const warningDiv = document.getElementById('dame_message_warning');

				function checkMessageStatus() {
					if (!messageSelect.value) {
						submitButton.disabled = false;
						warningDiv.style.display = 'none';
						return;
					}
					const selectedOption = messageSelect.options[messageSelect.selectedIndex];
					const status = selectedOption.getAttribute('data-status');

					if (status === 'sent' || status === 'sending') {
						submitButton.disabled = true;
						warningDiv.style.display = 'block';
					} else {
						submitButton.disabled = false;
						warningDiv.style.display = 'none';
					}
				}

				if (messageSelect) {
					messageSelect.addEventListener('change', checkMessageStatus);
					checkMessageStatus(); // Check on load
				}
			});
		</script>
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

		$message_id = isset( $_POST['dame_message_to_send'] ) ? absint( $_POST['dame_message_to_send'] ) : 0;
		if ( ! $message_id ) {
			wp_die( __( 'Message invalide.', 'dame' ) );
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
			// Group method.
			$seasons = isset( $_POST['dame_recipient_seasons'] ) ? array_map( 'absint', $_POST['dame_recipient_seasons'] ) : array();
			$groups_saisonnier = isset( $_POST['dame_recipient_groups_saisonnier'] ) ? array_map( 'absint', $_POST['dame_recipient_groups_saisonnier'] ) : array();
			$groups_permanent = isset( $_POST['dame_recipient_groups_permanent'] ) ? array_map( 'absint', $_POST['dame_recipient_groups_permanent'] ) : array();
			$gender = isset( $_POST['dame_recipient_gender'] ) ? sanitize_text_field( $_POST['dame_recipient_gender'] ) : 'all';

			$meta_seasons = $seasons;
			$meta_groups_saisonnier = $groups_saisonnier;
			$meta_groups_permanent = $groups_permanent;
			$meta_gender = $gender;

			if ( empty( $seasons ) && empty( $groups_saisonnier ) && empty( $groups_permanent ) ) {
				wp_die( __( 'Veuillez sélectionner au moins un critère (Saison ou Groupe).', 'dame' ) );
			}

			// Build Query.
			$args = array(
				'post_type'      => 'adherent',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					'relation' => 'OR',
				),
			);

			// Clause 1: Seasons
			if ( ! empty( $seasons ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'dame_saison_adhesion',
					'field'    => 'term_id',
					'terms'    => $seasons,
					'operator' => 'IN',
				);
			}

			// Clause 2: Groups (Merge both types)
			$all_groups = array_merge( $groups_saisonnier, $groups_permanent );
			if ( ! empty( $all_groups ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'dame_group',
					'field'    => 'term_id',
					'terms'    => $all_groups,
					'operator' => 'IN',
				);
			}

			// Meta Query for Gender (Intersection)
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
			wp_die( __( 'Aucun destinataire trouvé avec ces critères.', 'dame' ) );
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

		// Schedule batches (20 per minute to match legacy request of 3s * 20 = 60s).
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
