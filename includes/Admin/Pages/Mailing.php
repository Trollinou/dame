<?php
/**
 * Mailing Page.
 *
 * @package DAME
 */

declare(strict_types=1);

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

		// Prépare le mapping Région -> Départements pour le JS
		$regions = Data_Provider::get_regions();
		$region_mapping = [];
		foreach ( array_keys( $regions ) as $code ) {
			if ( 'NA' === $code ) continue;
			$region_mapping[ $code ] = Data_Provider::get_departments_by_region( $code );
		}

		wp_localize_script( 'dame-admin-mailing', 'dameMailingData', [
			'regionMapping' => $region_mapping,
		] );

		wp_enqueue_style(
			'dame-admin-styles',
			\DAME_PLUGIN_URL . 'assets/css/admin-styles.css',
			array(),
			\DAME_VERSION
		);
	}

	/**
	 * Rendu de la page de Mailing avec interface à deux colonnes et filtres de recherche.
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

		// Initialisation des variables d'état (existantes et nouvelles)
		$state_message           = isset( $saved_state['dame_message_to_send'] ) ? absint( $saved_state['dame_message_to_send'] ) : 0;
		$state_adherent_method   = isset( $saved_state['dame_adherent_method'] ) ? sanitize_key( $saved_state['dame_adherent_method'] ) : 'group';
		$state_contact_method    = isset( $saved_state['dame_contact_method'] ) ? sanitize_key( $saved_state['dame_contact_method'] ) : 'group';
		$state_gender            = isset( $saved_state['dame_recipient_gender'] ) ? sanitize_text_field( $saved_state['dame_recipient_gender'] ) : 'all';
		$state_seasons           = isset( $saved_state['dame_recipient_seasons'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_seasons'] ) : array();
		$state_groups_saisonnier = isset( $saved_state['dame_recipient_groups_saisonnier'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_groups_saisonnier'] ) : array();
		$state_groups_permanent  = isset( $saved_state['dame_recipient_groups_permanent'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_groups_permanent'] ) : array();
		$state_contact_types     = isset( $saved_state['dame_recipient_contact_types'] ) ? array_map( 'absint', (array) $saved_state['dame_recipient_contact_types'] ) : array();
		
		// Nouveaux états géographiques et manuels
		$state_depts             = isset( $saved_state['dame_contact_depts'] ) ? array_map( 'sanitize_text_field', (array) $saved_state['dame_contact_depts'] ) : array();
		$state_regions           = isset( $saved_state['dame_contact_regions'] ) ? array_map( 'sanitize_text_field', (array) $saved_state['dame_contact_regions'] ) : array();
		$state_manual_recipients = isset( $saved_state['dame_manual_recipients'] ) ? array_map( 'absint', (array) $saved_state['dame_manual_recipients'] ) : array();
		$state_manual_contacts   = isset( $saved_state['dame_manual_contacts'] ) ? array_map( 'absint', (array) $saved_state['dame_manual_contacts'] ) : array();
		$state_had_attachment    = ! empty( $saved_state['_had_attachment'] );

		// Gestion des notifications (Admin Notices).
		$success = isset( $_GET['success'] ) ? absint( $_GET['success'] ) : 0;
		$count   = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0;
		$error   = isset( $_GET['error'] ) ? sanitize_key( $_GET['error'] ) : '';

		// Données pour les listes
		$seasons       = get_terms( array( 'taxonomy' => 'dame_saison_adhesion', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'DESC' ) );
		$all_groups    = get_terms( array( 'taxonomy' => 'dame_group', 'hide_empty' => false ) );
		$contact_types = get_terms( array( 'taxonomy' => 'dame_contact_type', 'hide_empty' => false ) );
		$departments   = Data_Provider::get_departments();
		$regions       = Data_Provider::get_regions();

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

		$messages = get_posts( array( 'post_type' => 'dame_message', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
		$adherents = get_posts( array( 'post_type' => 'adherent', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
		$contacts  = get_posts( array( 'post_type' => 'dame_contact', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );

		/**
		 * Helper pour rendre une liste avec recherche.
		 */
		$render_searchable_list = function( string $placeholder, array $items, string $name_attr, array $checked_items, callable $label_callback, callable $data_callback = null ) {
			?>
			<div class="dame-searchable-list-wrapper">
				<div class="dame-search-header" style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
					<input type="text" class="dame-list-search regular-text" style="flex: 1; margin: 0;" placeholder="<?php echo esc_attr( $placeholder ); ?>">
					<span class="dame-selection-count" title="<?php esc_attr_e( 'Nombre d\'éléments sélectionnés', 'dame' ); ?>" style="background: #2271b1; color: #fff; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; white-space: nowrap;">
						<?php echo count( $checked_items ); ?>
					</span>
				</div>
				<div class="dame-checkbox-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; background: #f9f9f9;">
					<?php foreach ( $items as $key => $value ) : 
						$id = is_object($value) ? ($value->ID ?? $value->term_id) : $key;
						$label = $label_callback($value, $key);
						if ( empty($label) ) continue;
						$is_checked = in_array($id, $checked_items);
						$data_attrs = $data_callback ? $data_callback($value, $key) : '';
					?>
						<label style="display: block;">
							<input type="checkbox" name="<?php echo esc_attr($name_attr); ?>[]" value="<?php echo esc_attr($id); ?>" <?php checked($is_checked); ?> <?php echo $data_attrs; ?>> 
							<?php echo esc_html($label); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
		};

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
					'no_criteria'     => __( 'Veuillez sélectionner au moins un critère (Saison, Groupe ou Zone).', 'dame' ),
					'no_recipients'   => __( 'Aucun destinataire trouvé avec ces critères.', 'dame' ),
					'no_valid_emails' => __( 'Les destinataires trouvés ne possèdent pas d\'adresse e-mail valide ou ont refusé les communications.', 'dame' ),
					'all_already_received' => __( 'Tous les destinataires sélectionnés ont déjà reçu ce message. Aucun nouvel envoi n\'a été programmé.', 'dame' ),
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
						</td>
					</tr>
				</table>

				<!-- DEUX COLONNES PRINCIPALES -->
				<div class="dame-mailing-columns" style="display:flex; gap: 20px;">
					<!-- Colonne Gauche : Adhérents -->
					<div class="dame-mailing-col" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
						<h3><?php esc_html_e( 'Filtres Adhérents', 'dame' ); ?></h3>

						<div style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
							<label><input type="radio" name="dame_adherent_method" value="group" <?php checked( $state_adherent_method, 'group' ); ?>> <?php esc_html_e( 'Par critères', 'dame' ); ?></label>
							<label style="margin-left: 15px;"><input type="radio" name="dame_adherent_method" value="manual" <?php checked( $state_adherent_method, 'manual' ); ?>> <?php esc_html_e( 'Sélection manuelle', 'dame' ); ?></label>
						</div>

						<!-- Adhérents : Critères -->
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
										<option value="<?php echo $s->term_id; ?>" <?php echo in_array($s->term_id, $state_seasons) ? 'selected' : ''; ?>><?php echo esc_html($s->name); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div style="margin-bottom: 15px;">
								<label><strong><?php esc_html_e( 'Groupes Saisonniers', 'dame' ); ?></strong></label><br>
								<select name="dame_recipient_groups_saisonnier[]" multiple size="5" class="widefat">
									<?php foreach ( $saisonniers as $g ) : ?>
										<option value="<?php echo $g->term_id; ?>" <?php echo in_array($g->term_id, $state_groups_saisonnier) ? 'selected' : ''; ?>><?php echo esc_html($g->name); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div style="margin-bottom: 15px;">
								<label><strong><?php esc_html_e( 'Groupes Permanents', 'dame' ); ?></strong></label><br>
								<select name="dame_recipient_groups_permanent[]" multiple size="5" class="widefat">
									<?php foreach ( $permanents as $g ) : ?>
										<option value="<?php echo $g->term_id; ?>" <?php echo in_array($g->term_id, $state_groups_permanent) ? 'selected' : ''; ?>><?php echo esc_html($g->name); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<!-- Adhérents : Manuel -->
						<div class="dame-adherent-manual-wrap <?php echo 'manual' === $state_adherent_method ? '' : 'dame-hidden'; ?>">
							<?php $render_searchable_list(
								__( 'Rechercher un adhérent...', 'dame' ),
								$adherents,
								'dame_manual_recipients',
								$state_manual_recipients,
								fn($a) => $a->post_title
							); ?>
						</div>
					</div>

					<!-- Colonne Droite : Contacts -->
					<div class="dame-mailing-col" style="flex:1; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
						<h3><?php esc_html_e( 'Filtres Contacts', 'dame' ); ?></h3>

						<div style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
							<label><input type="radio" name="dame_contact_method" value="group" <?php checked( $state_contact_method, 'group' ); ?>> <?php esc_html_e( 'Par critères', 'dame' ); ?></label>
							<label style="margin-left: 15px;"><input type="radio" name="dame_contact_method" value="manual" <?php checked( $state_contact_method, 'manual' ); ?>> <?php esc_html_e( 'Sélection manuelle', 'dame' ); ?></label>
						</div>

						<!-- Contacts : Critères -->
						<div class="dame-contact-group-wrap <?php echo 'group' === $state_contact_method ? '' : 'dame-hidden'; ?>">
							<div style="margin-bottom: 15px;">
								<label><strong><?php esc_html_e( 'Types de Contacts', 'dame' ); ?></strong></label><br>
								<select name="dame_recipient_contact_types[]" id="dame_contact_types_select" multiple size="5" class="widefat">
									<?php foreach ( $contact_types as $t ) : ?>
										<option value="<?php echo $t->term_id; ?>" <?php echo in_array($t->term_id, $state_contact_types) ? 'selected' : ''; ?>><?php echo esc_html($t->name); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div style="display: flex; gap: 15px; margin-bottom: 15px;">
								<!-- Régions avec recherche -->
								<div style="flex: 1; min-width: 0;" class="dame-region-criteria-list">
									<label><strong><?php esc_html_e( 'Régions', 'dame' ); ?></strong></label><br>
									<?php $render_searchable_list(
										__( 'Filtrer les régions...', 'dame' ),
										$regions,
										'dame_contact_regions',
										$state_regions,
										fn($name, $code) => ($code === 'NA') ? '' : $name
									); ?>
								</div>

								<!-- Départements avec recherche -->
								<div style="flex: 1; min-width: 0;" class="dame-dept-criteria-list">
									<label><strong><?php esc_html_e( 'Départements', 'dame' ); ?></strong></label><br>
									<?php $render_searchable_list(
										__( 'Filtrer les départements...', 'dame' ),
										$departments,
										'dame_contact_depts',
										$state_depts,
										fn($name) => $name
									); ?>
								</div>
							</div>
						</div>

						<!-- Contacts : Manuel -->
						<div class="dame-contact-manual-wrap <?php echo 'manual' === $state_contact_method ? '' : 'dame-hidden'; ?>">
							<?php $render_searchable_list(
								__( 'Rechercher un contact...', 'dame' ),
								$contacts,
								'dame_manual_contacts',
								$state_manual_contacts,
								fn($c) => $c->post_title,
								function($c) {
									$dept = get_post_meta($c->ID, '_dame_contact_department', true);
									$reg = get_post_meta($c->ID, '_dame_contact_region', true);
									$terms = wp_get_post_terms($c->ID, 'dame_contact_type', ['fields' => 'ids']);
									$types = is_array($terms) ? implode(',', $terms) : '';
									return sprintf('data-dept="%s" data-region="%s" data-types="%s"', esc_attr($dept), esc_attr($reg), esc_attr($types));
								}
							); ?>
						</div>
					</div>
				</div>

				<div style="margin-top: 30px; padding: 20px; background: #f0f0f1; border: 1px solid #ccd0d4;">
					<?php submit_button( __( 'Envoyer le message', 'dame' ), 'primary large' ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Traitement de la soumission du formulaire de mailing.
	 * Gère les critères complexes, les sélections manuelles et la planification des envois.
	 */
	public function process_mailing(): void {
		$base_url = admin_url( 'admin.php?page=dame-mailing' );
		$user_id  = get_current_user_id();
		$state_key = 'dame_mailing_state_' . $user_id;

		// Fonction interne pour sauvegarder l'état (données POST) avant redirection en cas d'erreur.
		$save_state_and_redirect = function( string $error_code ) use ( $base_url, $state_key ) {
			$data = $_POST;
			if ( ! empty( $_FILES['dame_message_attachment']['name'] ) ) {
				$data['_had_attachment'] = true;
			}
			set_transient( $state_key, $data, 300 ); // Sauvegarde temporaire de 5 minutes.
			wp_redirect( add_query_arg( 'error', $error_code, $base_url ) );
			exit;
		};

		// 1. Sécurité et Permissions
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

		$adherent_method = isset( $_POST['dame_adherent_method'] ) ? sanitize_key( $_POST['dame_adherent_method'] ) : 'group';
		$contact_method  = isset( $_POST['dame_contact_method'] ) ? sanitize_key( $_POST['dame_contact_method'] ) : 'group';
		
		$recipient_emails = [];
		$adherent_ids     = [];
		$contact_ids      = [];

		// Initialisation des métadonnées de suivi
		$meta_seasons           = [];
		$meta_groups_saisonnier = [];
		$meta_groups_permanent  = [];
		$meta_contact_types     = [];
		$meta_depts             = [];
		$meta_regions           = [];
		$meta_manual_recipients = [];
		$meta_manual_contacts   = [];
		$meta_gender            = 'all';

		// 2. Identification des IDs des destinataires
		$adherent_criteria_selected = false;
		
		// A. Bloc Adhérents
		if ( 'manual' === $adherent_method ) {
			if ( ! empty( $_POST['dame_manual_recipients'] ) && is_array( $_POST['dame_manual_recipients'] ) ) {
				$adherent_ids = array_map( 'absint', $_POST['dame_manual_recipients'] );
				$meta_manual_recipients = $adherent_ids;
				$adherent_criteria_selected = true;
			}
		} else {
			$seasons           = isset( $_POST['dame_recipient_seasons'] ) ? array_map( 'absint', $_POST['dame_recipient_seasons'] ) : [];
			$groups_saisonnier = isset( $_POST['dame_recipient_groups_saisonnier'] ) ? array_map( 'absint', $_POST['dame_recipient_groups_saisonnier'] ) : [];
			$groups_permanent  = isset( $_POST['dame_recipient_groups_permanent'] ) ? array_map( 'absint', $_POST['dame_recipient_groups_permanent'] ) : [];
			$gender            = isset( $_POST['dame_recipient_gender'] ) ? sanitize_text_field( $_POST['dame_recipient_gender'] ) : 'all';

			$meta_seasons           = $seasons;
			$meta_groups_saisonnier = $groups_saisonnier;
			$meta_groups_permanent  = $groups_permanent;
			$meta_gender            = $gender;

			if ( ! empty( $seasons ) || ! empty( $groups_saisonnier ) || ! empty( $groups_permanent ) || 'all' !== $gender ) {
				$adherent_criteria_selected = true;
				$args = [
					'post_type'      => 'adherent',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => [ 'relation' => 'OR' ],
				];

				if ( ! empty( $seasons ) ) {
					$args['tax_query'][] = [ 'taxonomy' => 'dame_saison_adhesion', 'field' => 'term_id', 'terms' => $seasons ];
				}

				$all_groups = array_merge( $groups_saisonnier, $groups_permanent );
				if ( ! empty( $all_groups ) ) {
					$args['tax_query'][] = [ 'taxonomy' => 'dame_group', 'field' => 'term_id', 'terms' => $all_groups ];
				}

				if ( 'all' !== $gender ) {
					$args['meta_query'] = [ [ 'key' => '_dame_sexe', 'value' => $gender ] ];
				}
				$adherent_ids = get_posts( $args );
			}
		}

		// B. Bloc Contacts
		$contact_criteria_selected = false;
		if ( 'manual' === $contact_method ) {
			if ( ! empty( $_POST['dame_manual_contacts'] ) && is_array( $_POST['dame_manual_contacts'] ) ) {
				$contact_ids = array_map( 'absint', $_POST['dame_manual_contacts'] );
				$meta_manual_contacts = $contact_ids;
				$contact_criteria_selected = true;
			}
		} else {
			$contact_types = isset( $_POST['dame_recipient_contact_types'] ) ? array_map( 'absint', $_POST['dame_recipient_contact_types'] ) : [];
			$depts         = isset( $_POST['dame_contact_depts'] ) ? array_map( 'sanitize_text_field', $_POST['dame_contact_depts'] ) : [];
			$regions       = isset( $_POST['dame_contact_regions'] ) ? array_map( 'sanitize_text_field', $_POST['dame_contact_regions'] ) : [];

			$meta_contact_types = $contact_types;
			$meta_depts         = $depts;
			$meta_regions       = $regions;

			$has_types = ! empty( $contact_types );
			$has_depts = ! empty( $depts );

			if ( $has_types || $has_depts ) {
				$contact_criteria_selected = true;
				if ( $has_types && $has_depts ) {
					// Intersection stricte Type et Département
					$contact_ids = get_posts( [
						'post_type'      => 'dame_contact',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'tax_query'      => [ [ 'taxonomy' => 'dame_contact_type', 'field' => 'term_id', 'terms' => $contact_types ] ],
						'meta_query'     => [ [ 'key' => '_dame_contact_department', 'value' => $depts, 'compare' => 'IN' ] ],
					] );
				} elseif ( $has_types ) {
					$contact_ids = get_posts( [
						'post_type'      => 'dame_contact',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'tax_query'      => [ [ 'taxonomy' => 'dame_contact_type', 'field' => 'term_id', 'terms' => $contact_types ] ],
					] );
				} elseif ( $has_depts ) {
					$contact_ids = get_posts( [
						'post_type'      => 'dame_contact',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'meta_query'     => [ [ 'key' => '_dame_contact_department', 'value' => $depts, 'compare' => 'IN' ] ],
					] );
				}
			}
		}

		// 1. Check si au moins un critère a été saisi (pour éviter d'envoyer à "personne" par oubli)
		if ( ! $adherent_criteria_selected && ! $contact_criteria_selected ) {
			$save_state_and_redirect( 'no_criteria' );
		}

		// 2. Check si des gens correspondent aux critères (avant filtrage incrémental)
		if ( empty( $adherent_ids ) && empty( $contact_ids ) ) {
			$save_state_and_redirect( 'no_recipients' );
		}

		// Filtrage incrémental : On retire ceux qui ont déjà reçu ce message précis, 
		// MAIS seulement pour les modes par critères (le mode manuel permet le renvoi ciblé).
		$filter_already_received = function( $id ) use ( $message_id ) {
			$received_messages = get_post_meta( $id, '_dame_message_received', false );
			$received_ids      = array_map( 'strval', (array) $received_messages );
			return ! in_array( (string) $message_id, $received_ids, true );
		};


		if ( 'group' === $adherent_method ) {
			$adherent_ids = array_filter( $adherent_ids, $filter_already_received );
		}

		if ( 'group' === $contact_method ) {
			$contact_ids = array_filter( $contact_ids, $filter_already_received );
		}

		// 4. Check final après filtrage incrémental
		if ( empty( $adherent_ids ) && empty( $contact_ids ) ) {
			$save_state_and_redirect( 'all_already_received' );
		}

		// 3. Collecte des E-mails
		foreach ( $adherent_ids as $aid ) {
			$recipient_emails = array_merge( $recipient_emails, Data_Provider::get_emails_for_adherent( $aid ) );
		}
		foreach ( $contact_ids as $cid ) {
			$email = get_post_meta( $cid, '_dame_contact_email', true );
			if ( is_email( $email ) ) {
				$recipient_emails[] = $email;
			}
		}
		$recipient_emails = array_unique( $recipient_emails );

		if ( empty( $recipient_emails ) ) {
			$save_state_and_redirect( 'no_valid_emails' );
		}

		// 4. Gestion de la Pièce Jointe (Optionnelle)
		if ( ! empty( $_FILES['dame_message_attachment']['name'] ) && $_FILES['dame_message_attachment']['error'] !== UPLOAD_ERR_NO_FILE ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			// Configuration de l'upload et validation MIME stricte.
			$upload_overrides = [
				'test_form' => false,
				'mimes'     => [
					'pdf'          => 'application/pdf',
					'jpg|jpeg|jpe' => 'image/jpeg',
					'png'          => 'image/png',
					'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
					'doc'          => 'application/msword',
					'odt'          => 'application/vnd.oasis.opendocument.text',
				],
			];

			$upload = wp_handle_upload( $_FILES['dame_message_attachment'], $upload_overrides );

			if ( isset( $upload['error'] ) ) {
				// Utilisation de wp_die comme demandé pour les erreurs d'upload.
				wp_die( sprintf( esc_html__( 'Erreur lors du téléchargement de la pièce jointe : %s', 'dame' ), esc_html( $upload['error'] ) ) );
			}

			if ( isset( $upload['file'] ) ) {
				update_post_meta( $message_id, '_dame_message_attachment', $upload['file'] );
			}
		} else {
			// Si aucun fichier n'est transmis, on s'assure qu'aucune ancienne meta ne persiste.
			delete_post_meta( $message_id, '_dame_message_attachment' );
		}

		// 5. Sauvegarde et Planification
		$old_count = (int) get_post_meta( $message_id, '_dame_message_recipients_count', true );
		$new_total = $old_count + count( $recipient_emails );

		update_post_meta( $message_id, '_dame_message_status', 'scheduled' );
		update_post_meta( $message_id, '_dame_message_recipients_count', $new_total );
		update_post_meta( $message_id, '_dame_adherent_method', $adherent_method );
		update_post_meta( $message_id, '_dame_contact_method', $contact_method );

		if ( 'group' === $adherent_method ) {
			update_post_meta( $message_id, '_dame_recipient_seasons', $meta_seasons );
			update_post_meta( $message_id, '_dame_recipient_groups_saisonnier', $meta_groups_saisonnier );
			update_post_meta( $message_id, '_dame_recipient_groups_permanent', $meta_groups_permanent );
			update_post_meta( $message_id, '_dame_recipient_gender', $meta_gender );
		} else {
			update_post_meta( $message_id, '_dame_manual_recipients', $meta_manual_recipients );
		}

		if ( 'group' === $contact_method ) {
			update_post_meta( $message_id, '_dame_recipient_contact_types', $meta_contact_types );
			update_post_meta( $message_id, '_dame_recipient_depts', $meta_depts );
			update_post_meta( $message_id, '_dame_recipient_regions', $meta_regions );
		} else {
			update_post_meta( $message_id, '_dame_manual_contacts', $meta_manual_contacts );
		}

		// Découpage en lots (Batching) pour éviter les timeouts
		$chunks = array_chunk( $recipient_emails, 20 );
		$total_batches = count( $chunks );
		update_post_meta( $message_id, '_dame_scheduled_batches_total', $total_batches );
		update_post_meta( $message_id, '_dame_scheduled_batches_processed', 0 );

		$delay = 0;
		foreach ( $chunks as $chunk_emails ) {
			wp_schedule_single_event( time() + $delay, 'dame_cron_send_batch', [ $message_id, $chunk_emails, 0 ] );
			$delay += 60; // 1 minute entre chaque lot de 20 mails.
		}

		wp_redirect( add_query_arg( [ 'success' => 1, 'count' => count( $recipient_emails ) ], $base_url ) );
		exit;
	}
}
