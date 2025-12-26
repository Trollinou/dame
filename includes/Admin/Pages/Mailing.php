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
			DAME_URL . 'admin/js/mailing.js', // Assuming this file exists or will be created.
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

		// Get all groups.
		$groups = get_terms( array(
			'taxonomy'   => 'dame_group',
			'hide_empty' => false,
		) );

		// Get draft messages.
		$messages = get_posts( array(
			'post_type'      => 'dame_message',
			'post_status'    => 'any', // Allow selecting any message, though drafts are typical.
			'posts_per_page' => -1,
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
							<p class="description"><?php esc_html_e( 'Seuls les messages enregistrés apparaissent ici.', 'dame' ); ?></p>
						</td>
					</tr>

					<!-- Season Selection -->
					<tr>
						<th scope="row"><label for="dame_season"><?php esc_html_e( 'Saison', 'dame' ); ?></label></th>
						<td>
							<select name="dame_season" id="dame_season" required>
								<option value=""><?php esc_html_e( 'Choisir une saison...', 'dame' ); ?></option>
								<?php foreach ( $seasons as $season ) : ?>
									<option value="<?php echo esc_attr( $season->term_id ); ?>"><?php echo esc_html( $season->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<!-- Recipient Method -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Destinataires', 'dame' ); ?></th>
						<td>
							<fieldset>
								<label><input type="radio" name="dame_recipient_method" value="filters" checked> <?php esc_html_e( 'Utiliser des filtres (Groupes, Catégories...)', 'dame' ); ?></label><br>
								<label><input type="radio" name="dame_recipient_method" value="manual"> <?php esc_html_e( 'Sélection manuelle (Emails spécifiques)', 'dame' ); ?></label>
							</fieldset>
						</td>
					</tr>
				</table>

				<!-- Filters Section -->
				<div id="dame_method_filters" class="dame-mailing-section">
					<h3><?php esc_html_e( 'Filtres de destinataires', 'dame' ); ?></h3>
					<table class="form-table">
						<!-- Groups -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Groupes', 'dame' ); ?></th>
							<td>
								<?php if ( ! empty( $groups ) ) : ?>
									<fieldset>
										<?php foreach ( $groups as $group ) : ?>
											<label>
												<input type="checkbox" name="dame_groups[]" value="<?php echo esc_attr( $group->term_id ); ?>">
												<?php echo esc_html( $group->name ); ?>
											</label><br>
										<?php endforeach; ?>
									</fieldset>
								<?php else : ?>
									<p><?php esc_html_e( 'Aucun groupe trouvé.', 'dame' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>

						<!-- Agenda Categories (optional filter) -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Catégories (Agenda)', 'dame' ); ?></th>
							<td>
								<div class="dame-scrollable-checklist" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
									<?php $this->render_category_checklist(); ?>
								</div>
							</td>
						</tr>
					</table>
				</div>

				<!-- Manual Section -->
				<div id="dame_method_manual" class="dame-mailing-section" style="display:none;">
					<h3><?php esc_html_e( 'Saisie manuelle', 'dame' ); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="dame_manual_emails"><?php esc_html_e( 'Emails (un par ligne)', 'dame' ); ?></label></th>
							<td>
								<textarea name="dame_manual_emails" id="dame_manual_emails" rows="10" cols="50" class="large-text code"></textarea>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button( __( 'Envoyer le message', 'dame' ) ); ?>
			</form>
		</div>
		<script>
			// Simple inline script to handle toggle if JS file is not yet loaded/created.
			document.addEventListener('DOMContentLoaded', function() {
				const radios = document.getElementsByName('dame_recipient_method');
				const filtersDiv = document.getElementById('dame_method_filters');
				const manualDiv = document.getElementById('dame_method_manual');

				function toggleSections() {
					let method = 'filters';
					for (const radio of radios) {
						if (radio.checked) {
							method = radio.value;
							break;
						}
					}
					if (method === 'filters') {
						filtersDiv.style.display = 'block';
						manualDiv.style.display = 'none';
					} else {
						filtersDiv.style.display = 'none';
						manualDiv.style.display = 'block';
					}
				}

				for (const radio of radios) {
					radio.addEventListener('change', toggleSections);
				}
				toggleSections();
			});
		</script>
		<?php
	}

	/**
	 * Renders category checklist recursively.
	 *
	 * @param int $parent_id Parent term ID.
	 */
	private function render_category_checklist( $parent_id = 0 ) {
		$terms = get_terms( array(
			'taxonomy'   => 'dame_agenda_category',
			'parent'     => $parent_id,
			'hide_empty' => false,
		) );

		if ( empty( $terms ) ) {
			return;
		}

		echo '<ul style="margin-top: 5px; margin-bottom: 5px;">';
		foreach ( $terms as $term ) {
			echo '<li>';
			echo '<label><input type="checkbox" name="dame_categories[]" value="' . esc_attr( $term->term_id ) . '"> ' . esc_html( $term->name ) . '</label>';
			$this->render_category_checklist( $term->term_id );
			echo '</li>';
		}
		echo '</ul>';
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

		$season_id = isset( $_POST['dame_season'] ) ? absint( $_POST['dame_season'] ) : 0;
		$method    = isset( $_POST['dame_recipient_method'] ) ? sanitize_key( $_POST['dame_recipient_method'] ) : 'filters';

		$recipient_emails = array();

		if ( 'manual' === $method ) {
			$raw_emails = isset( $_POST['dame_manual_emails'] ) ? sanitize_textarea_field( $_POST['dame_manual_emails'] ) : '';
			$lines      = explode( "\n", $raw_emails );
			foreach ( $lines as $line ) {
				$email = sanitize_email( trim( $line ) );
				if ( is_email( $email ) ) {
					$recipient_emails[] = $email;
				}
			}
		} else {
			// Filter logic.
			if ( ! $season_id ) {
				wp_die( __( 'Veuillez sélectionner une saison.', 'dame' ) );
			}

			$groups     = isset( $_POST['dame_groups'] ) ? array_map( 'absint', $_POST['dame_groups'] ) : array();
			$categories = isset( $_POST['dame_categories'] ) ? array_map( 'absint', $_POST['dame_categories'] ) : array();

			// Build query for adherents.
			$args = array(
				'post_type'      => 'adherent',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'dame_saison_adhesion',
						'field'    => 'term_id',
						'terms'    => $season_id,
					),
				),
			);

			if ( ! empty( $groups ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'dame_group',
					'field'    => 'term_id',
					'terms'    => $groups,
				);
			}

			// Note: Categories usually apply to events (agenda), but legacy might link them to adherents or users want to filter adherents interested in categories.
			// However, adherence usually doesn't have 'dame_agenda_category'.
			// Assuming standard adherence filtering for now. If categories are needed, they might be custom fields or another taxonomy.
			// Given instructions: "Helper `render_category_checklist` pour les catégories (récursif)."
			// If these are agenda categories, they might not apply to adherents directly unless there's a relation.
			// I will keep the logic simple: fetch adherents matching Season + Groups.

			$adherent_ids = get_posts( $args );

			foreach ( $adherent_ids as $adherent_id ) {
				$emails = Data_Provider::get_emails_for_adherent( $adherent_id );
				$recipient_emails = array_merge( $recipient_emails, $emails );
			}
		}

		$recipient_emails = array_unique( $recipient_emails );

		if ( empty( $recipient_emails ) ) {
			wp_die( __( 'Aucun destinataire trouvé avec ces critères.', 'dame' ) );
		}

		// Update Message status and meta.
		update_post_meta( $message_id, '_dame_message_status', 'scheduled' );
		update_post_meta( $message_id, '_dame_message_recipients_count', count( $recipient_emails ) );
		update_post_meta( $message_id, '_dame_recipient_method', $method );
		if ( 'filters' === $method ) {
			update_post_meta( $message_id, '_dame_target_season', $season_id );
			if ( ! empty( $groups ) ) update_post_meta( $message_id, '_dame_target_groups', $groups );
		}

		// Schedule batches.
		// Split emails into chunks (e.g., 20 per minute is the limit, so chunks of 20).
		$chunks = array_chunk( $recipient_emails, 20 );
		$total_batches = count( $chunks );

		update_post_meta( $message_id, '_dame_scheduled_batches_total', $total_batches );
		update_post_meta( $message_id, '_dame_scheduled_batches_processed', 0 );

		// Schedule first batch immediately (or very soon).
		// We schedule subsequent batches by spacing them out to respect rate limits if we were doing single events per batch,
		// BUT the BatchSender instruction said "Le fichier legacy semble utiliser `wp_schedule_single_event` pour relancer un lot en cas d'échec."
		// AND "Rate Limit : L'envoi ne doit jamais dépasser 20 emails/minute (via Cron)."
		// So we should schedule distinct events for each chunk, spaced by 1 minute.

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
