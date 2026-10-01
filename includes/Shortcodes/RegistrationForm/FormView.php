<?php
/**
 * Registration Form HTML View.
 *
 * @package DAME\Shortcodes\RegistrationForm
 */

declare(strict_types=1);

namespace DAME\Shortcodes\RegistrationForm;

use DAME\Services\Data_Provider;

/**
 * Handles rendering and assets enqueuing for the registration form shortcode.
 */
class FormView {

	/**
	 * Render the registration form HTML.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string HTML content.
	 */
	public function render( array $atts = array() ): string {
		wp_enqueue_style( 'dame-public-styles', DAME_PLUGIN_URL . 'assets/css/public-styles.css', array(), DAME_VERSION );

		wp_enqueue_script( 'dame-public-geo-autocomplete', DAME_PLUGIN_URL . 'assets/js/public-geo-autocomplete.js', array(), DAME_VERSION, true );
		wp_enqueue_script( 'dame-public-ign-autocomplete', DAME_PLUGIN_URL . 'assets/js/public-ign-autocomplete.js', array(), DAME_VERSION, true );
		wp_enqueue_script( 'dame-public-pre-inscription', DAME_PLUGIN_URL . 'assets/js/public-pre-inscription-form.js', array( 'dame-public-geo-autocomplete', 'dame-public-ign-autocomplete' ), DAME_VERSION, true );

		wp_localize_script(
			'dame-public-pre-inscription',
			'dame_pre_inscription_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);

		ob_start();
		?>
		<div id="dame-pre-inscription-form-wrapper">
			<div id="dame-form-messages" style="display:none; padding: 1em; margin-bottom: 1em;"></div>
			<form id="dame-pre-inscription-form" class="dame-form" novalidate>

				<?php wp_nonce_field( 'dame_pre_inscription_nonce', 'dame_nonce' ); ?>

				<h3><?php esc_html_e( "Informations sur l'adhérent", 'dame' ); ?></h3>

				<p>
					<label for="dame_birth_name"><?php esc_html_e( 'Nom de naissance', 'dame' ); ?> <span class="required">*</span></label>
					<input type="text" id="dame_birth_name" name="dame_birth_name" required>
				</p>
				<p>
					<label for="dame_last_name"><?php esc_html_e( 'Nom d\'usage', 'dame' ); ?></label>
					<input type="text" id="dame_last_name" name="dame_last_name">
				</p>
				<p>
					<label for="dame_first_name"><?php esc_html_e( 'Prénom', 'dame' ); ?> <span class="required">*</span></label>
					<input type="text" id="dame_first_name" name="dame_first_name" required>
				</p>
				<p>
					<label><?php esc_html_e( 'Sexe', 'dame' ); ?> <span class="required">*</span></label>
					<label style="margin-left: 15px; display: inline-block;"><input type="radio" name="dame_sexe" value="Masculin" checked required> <?php esc_html_e( 'Masculin', 'dame' ); ?></label>
					<label style="margin-left: 15px; display: inline-block;"><input type="radio" name="dame_sexe" value="Féminin"> <?php esc_html_e( 'Féminin', 'dame' ); ?></label>
					<label style="margin-left: 15px; display: inline-block;"><input type="radio" name="dame_sexe" value="Non précisé"> <?php esc_html_e( 'Non précisé', 'dame' ); ?></label>
				</p>
				<p>
					<label for="dame_birth_date"><?php esc_html_e( 'Date de naissance', 'dame' ); ?> <span class="required">*</span></label>
					<input type="date" id="dame_birth_date" name="dame_birth_date" required>
				</p>
				<p>
					<label for="dame_birth_city"><?php esc_html_e( 'Lieu de naissance', 'dame' ); ?> <span id="dame_birth_city_required_indicator" class="required" style="display: none;">*</span></label>
					<div class="dame-autocomplete-wrapper">
						<input type="text" id="dame_birth_city" name="dame_birth_city" class="regular-text">
					</div>
				</p>
				<p>
					<label for="dame_phone_number"><?php esc_html_e( 'Numéro de téléphone', 'dame' ); ?> <span class="required">*</span></label>
					<input type="tel" id="dame_phone_number" name="dame_phone_number" required>
				</p>
				<p>
					<label for="dame_email"><?php esc_html_e( 'Email', 'dame' ); ?> <span class="required">*</span></label>
					<input type="email" id="dame_email" name="dame_email" required>
				</p>
				<p style="margin-left: 20px; font-weight: normal; display: flex; align-items: flex-start; gap: 8px;">
					<input type="checkbox" id="dame_refuses_comms" name="dame_refuses_comms" value="1" style="margin-top: 4px; width: auto; display: inline-block;">
					<label for="dame_refuses_comms" style="font-weight: normal; display: inline; cursor: pointer;">
						<?php esc_html_e( "Je m'oppose à la réception des e-mails d'information de l'association.", 'dame' ); ?>
					</label>
				</p>
				<p>
					<label for="dame_profession"><?php esc_html_e( 'Profession', 'dame' ); ?></label>
					<input type="text" id="dame_profession" name="dame_profession">
				</p>
				<p>
					<label for="dame_address_1"><?php esc_html_e( 'Adresse', 'dame' ); ?> <span class="required">*</span></label>
					<div class="dame-autocomplete-wrapper">
						<input type="text" id="dame_address_1" name="dame_address_1" required>
					</div>
				</p>
				<p>
					<label for="dame_address_2"><?php esc_html_e( 'Complément', 'dame' ); ?></label>
					<input type="text" id="dame_address_2" name="dame_address_2">
				</p>
				<p>
					<label for="dame_postal_code"><?php esc_html_e( 'Code Postal', 'dame' ); ?></label>
					<input type="text" id="dame_postal_code" name="dame_postal_code" style="width: 8em;">
				</p>
				<p>
					<label for="dame_city"><?php esc_html_e( 'Ville', 'dame' ); ?> <span class="required">*</span></label>
					<input type="text" id="dame_city" name="dame_city" required>
				</p>
				<p>
					<label for="dame_taille_vetements"><?php esc_html_e( 'Taille de vêtements', 'dame' ); ?></label>
					<select id="dame_taille_vetements" name="dame_taille_vetements">
						<?php
						$taille_vetements_options = Data_Provider::get_clothing_sizes();
						foreach ( $taille_vetements_options as $option ) {
							echo '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
						}
						?>
					</select>
				</p>
				<p>
					<label for="dame_license_type"><?php esc_html_e( 'Type de licence', 'dame' ); ?> <span class="required">*</span></label>
					<select id="dame_license_type" name="dame_license_type" required>
						<option value="A"><?php esc_html_e( 'Licence A (Cours + Compétition)', 'dame' ); ?></option>
						<option value="B"><?php esc_html_e( 'Licence B (Jeu libre)', 'dame' ); ?></option>
					</select>
				</p>

				<div id="dame-dynamic-fields" style="display:none;">
					<div id="dame-adherent-majeur-fields" style="display:none;">
						<h4><?php esc_html_e( 'Informations complémentaires (Majeur)', 'dame' ); ?></h4>
					</div>

					<div id="dame-adherent-mineur-fields" style="display:none;">
						<h4 style="display: flex; align-items: center; flex-wrap: wrap;"><?php esc_html_e( 'Représentant Légal 1', 'dame' ); ?>
							<button type="button" class="dame-copy-button" data-rep-id="1" style="background-color: #3ec0f0; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 5px; white-space: nowrap; font-size: 13px; margin-left: 10px;"><?php esc_html_e( '✂️ Recopier les données de l\'Adhérent ✂️', 'dame' ); ?></button>
						</h4>
						<p><label for="dame_legal_rep_1_last_name"><?php esc_html_e( 'Nom de naissance', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><input type="text" id="dame_legal_rep_1_last_name" name="dame_legal_rep_1_last_name"></p>
						<p><label for="dame_legal_rep_1_first_name"><?php esc_html_e( 'Prénom', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><input type="text" id="dame_legal_rep_1_first_name" name="dame_legal_rep_1_first_name"></p>
						<p><em><?php esc_html_e( 'Dans le cadre de notre politique de prévention des violences sexistes et sexuelles, nous demandons aux parents susceptibles d’accompagner des mineurs de se soumettre à un contrôle d’honorabilité. À cette fin, nous vous remercions de bien vouloir renseigner les deux champs ci-dessous si vous êtes concerné.', 'dame' ); ?></em></p>
						<p><label for="dame_legal_rep_1_date_naissance"><?php esc_html_e( 'Date de naissance', 'dame' ); ?></label><input type="date" id="dame_legal_rep_1_date_naissance" name="dame_legal_rep_1_date_naissance"></p>
						<p><label for="dame_legal_rep_1_commune_naissance"><?php esc_html_e( 'Lieu de naissance', 'dame' ); ?></label><div class="dame-autocomplete-wrapper"><input type="text" id="dame_legal_rep_1_commune_naissance" name="dame_legal_rep_1_commune_naissance"></div></p>
						<p><label for="dame_legal_rep_1_phone"><?php esc_html_e( 'Numéro de téléphone', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><input type="tel" id="dame_legal_rep_1_phone" name="dame_legal_rep_1_phone"></p>
						<p><label for="dame_legal_rep_1_email"><?php esc_html_e( 'Email', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><input type="email" id="dame_legal_rep_1_email" name="dame_legal_rep_1_email"></p>
						<p style="margin-left: 20px; font-weight: normal; display: flex; align-items: flex-start; gap: 8px;">
							<input type="checkbox" id="dame_legal_rep_1_refuses_comms" name="dame_legal_rep_1_refuses_comms" value="1" style="margin-top: 4px; width: auto; display: inline-block;">
							<label for="dame_legal_rep_1_refuses_comms" style="font-weight: normal; display: inline; cursor: pointer;">
								<?php esc_html_e( "Je m'oppose à la réception des e-mails d'information de l'association.", 'dame' ); ?>
							</label>
						</p>
						<p><label for="dame_legal_rep_1_profession"><?php esc_html_e( 'Profession', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_profession" name="dame_legal_rep_1_profession"></p>
						<p><label for="dame_legal_rep_1_address_1"><?php esc_html_e( 'Adresse', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><div class="dame-autocomplete-wrapper"><input type="text" id="dame_legal_rep_1_address_1" name="dame_legal_rep_1_address_1"></div></p>
						<p><label for="dame_legal_rep_1_address_2"><?php esc_html_e( 'Complément', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_address_2" name="dame_legal_rep_1_address_2"></p>
						<p><label for="dame_legal_rep_1_postal_code"><?php esc_html_e( 'Code Postal', 'dame' ); ?></label><input type="text" id="dame_legal_rep_1_postal_code" name="dame_legal_rep_1_postal_code"></p>
						<p><label for="dame_legal_rep_1_city"><?php esc_html_e( 'Ville', 'dame' ); ?> <span class="dame-rep1-required-indicator required" style="display: none;">*</span></label><input type="text" id="dame_legal_rep_1_city" name="dame_legal_rep_1_city"></p>

						<h4 style="display: flex; align-items: center; flex-wrap: wrap;"><?php esc_html_e( 'Représentant Légal 2', 'dame' ); ?>
							<button type="button" class="dame-copy-button" data-rep-id="2" style="background-color: #3ec0f0; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 5px; white-space: nowrap; font-size: 13px; margin-left: 10px;"><?php esc_html_e( '✂️ Recopier les données de l\'Adhérent ✂️', 'dame' ); ?></button>
						</h4>
						<p><label for="dame_legal_rep_2_last_name"><?php esc_html_e( 'Nom de naissance', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_last_name" name="dame_legal_rep_2_last_name"></p>
						<p><label for="dame_legal_rep_2_first_name"><?php esc_html_e( 'Prénom', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_first_name" name="dame_legal_rep_2_first_name"></p>
						<p><em><?php esc_html_e( 'Dans le cadre de notre politique de prévention des violences sexistes et sexuelles, nous demandons aux parents susceptibles d’accompagner des mineurs de se soumettre à un contrôle d’honorabilité. À cette fin, nous vous remercions de bien vouloir renseigner les deux champs ci-dessous si vous êtes concerné.', 'dame' ); ?></em></p>
						<p><label for="dame_legal_rep_2_date_naissance"><?php esc_html_e( 'Date de naissance', 'dame' ); ?></label><input type="date" id="dame_legal_rep_2_date_naissance" name="dame_legal_rep_2_date_naissance"></p>
						<p><label for="dame_legal_rep_2_commune_naissance"><?php esc_html_e( 'Lieu de naissance', 'dame' ); ?></label><div class="dame-autocomplete-wrapper"><input type="text" id="dame_legal_rep_2_commune_naissance" name="dame_legal_rep_2_commune_naissance"></div></p>
						<p><label for="dame_legal_rep_2_phone"><?php esc_html_e( 'Numéro de téléphone', 'dame' ); ?></label><input type="tel" id="dame_legal_rep_2_phone" name="dame_legal_rep_2_phone"></p>
						<p><label for="dame_legal_rep_2_email"><?php esc_html_e( 'Email', 'dame' ); ?></label><input type="email" id="dame_legal_rep_2_email" name="dame_legal_rep_2_email"></p>
						<p style="margin-left: 20px; font-weight: normal; display: flex; align-items: flex-start; gap: 8px;">
							<input type="checkbox" id="dame_legal_rep_2_refuses_comms" name="dame_legal_rep_2_refuses_comms" value="1" style="margin-top: 4px; width: auto; display: inline-block;">
							<label for="dame_legal_rep_2_refuses_comms" style="font-weight: normal; display: inline; cursor: pointer;">
								<?php esc_html_e( "Je m'oppose à la réception des e-mails d'information de l'association.", 'dame' ); ?>
							</label>
						</p>
						<p><label for="dame_legal_rep_2_profession"><?php esc_html_e( 'Profession', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_profession" name="dame_legal_rep_2_profession"></p>
						<p><label for="dame_legal_rep_2_address_1"><?php esc_html_e( 'Adresse', 'dame' ); ?></label><div class="dame-autocomplete-wrapper"><input type="text" id="dame_legal_rep_2_address_1" name="dame_legal_rep_2_address_1"></div></p>
						<p><label for="dame_legal_rep_2_address_2"><?php esc_html_e( 'Complément', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_address_2" name="dame_legal_rep_2_address_2"></p>
						<p><label for="dame_legal_rep_2_postal_code"><?php esc_html_e( 'Code Postal', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_postal_code" name="dame_legal_rep_2_postal_code"></p>
						<p><label for="dame_legal_rep_2_city"><?php esc_html_e( 'Ville', 'dame' ); ?></label><input type="text" id="dame_legal_rep_2_city" name="dame_legal_rep_2_city"></p>
					</div>
				</div>

				<h4>
					<?php esc_html_e( 'Questionnaire de santé', 'dame' ); ?>
					<span id="health-questionnaire-link-container" style="display: none; margin-left: 10px; font-weight: normal;">
						<a href="#" id="health-questionnaire-link" target="_blank" style="font-size: initial; color: blue; text-decoration: underline;"></a>
					</span>
				</h4>
				<p>
					<label><input type="radio" name="dame_health_questionnaire" value="non" required> <?php esc_html_e( 'J’ai répondu NON partout', 'dame' ); ?></label>
					<label><input type="radio" name="dame_health_questionnaire" value="oui"> <?php esc_html_e( 'J’ai au moins une réponse à OUI', 'dame' ); ?></label>
				</p>

				<div id="dame-signature-section" style="display: none; margin-top: 20px; padding: 15px; border: 1px solid #cbd5e1; border-radius: 8px; background-color: #f8fafc;">
					<h4 style="margin-top: 0; margin-bottom: 8px; color: #1e293b;">
						<?php esc_html_e( 'Signature électronique', 'dame' ); ?>
					</h4>
					<p id="dame-signature-hint" style="margin-bottom: 12px; font-size: 0.9em; color: #475569;">
						<?php esc_html_e( 'Veuillez apposer votre signature manuscrite ci-dessous.', 'dame' ); ?>
					</p>

					<p id="dame-health-attestation-consent-p" style="margin-bottom: 10px;">
						<label for="dame_health_attestation_consent" style="font-weight: normal; display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
							<input type="checkbox" id="dame_health_attestation_consent" name="dame_health_attestation_consent" value="1" style="margin-top: 4px; width: auto;">
							<span><?php esc_html_e( 'J’atteste sur l’honneur avoir répondu « NON » à toutes les questions du questionnaire de santé et m’engage à signaler tout changement de mon état de santé.', 'dame' ); ?></span>
						</label>
					</p>

					<p id="dame-parental-auth-consent-p" style="display: none; margin-bottom: 12px;">
						<label for="dame_parental_auth_consent" style="font-weight: normal; display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
							<input type="checkbox" id="dame_parental_auth_consent" name="dame_parental_auth_consent" value="1" style="margin-top: 4px; width: auto;">
							<span><?php esc_html_e( 'En tant que représentant légal, j’autorise le mineur à participer aux activités du club et confirme l’exactitude des informations fournies.', 'dame' ); ?></span>
						</label>
					</p>

					<div class="dame-signature-wrapper" style="position: relative; border: 1px dashed #94a3b8; border-radius: 6px; background-color: #ffffff; margin-bottom: 8px;">
						<canvas id="dame-signature-canvas" style="display: block; width: 100%; height: 160px; touch-action: none; cursor: crosshair;"></canvas>
					</div>
					<div style="display: flex; justify-content: space-between; align-items: center;">
						<span style="font-size: 0.8em; color: #64748b;"><?php esc_html_e( 'Signez avec votre doigt ou la souris dans le cadre ci-dessus', 'dame' ); ?></span>
						<button type="button" id="dame-clear-signature" style="background: none; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 10px; font-size: 0.85em; cursor: pointer; color: #475569;">
							<?php esc_html_e( 'Effacer la signature', 'dame' ); ?>
						</button>
					</div>
					<input type="hidden" id="dame_signature_image" name="signature_image" value="">
				</div>

				<p style="margin-top: 20px;">
					<label for="dame_consent_checkbox">
						<input type="checkbox" id="dame_consent_checkbox" name="dame_consent_checkbox" required>
						<?php esc_html_e( 'En cochant cette case, je reconnais avoir pris connaissance du règlement intérieur de l’Association Échiquier Lédonien et m’engage à le respecter.', 'dame' ); ?>
					</label>
				</p>

				<p>
					<button type="submit" id="dame_submit_button" disabled><?php esc_html_e( 'Valider ma préinscription', 'dame' ); ?></button>
				</p>
				<p style="font-size: 0.85em; color: #666; margin-top: 10px; line-height: 1.4;">
					<?php esc_html_e( "Les données collectées sur ce formulaire sont nécessaires à la gestion de votre adhésion. Pour en savoir plus sur l'utilisation de vos données, de nos outils de communication et pour exercer vos droits, consultez nos Mentions Légales.", 'dame' ); ?>
				</p>

			</form>
		</div>
		<?php
		$output = ob_get_clean();
		return false !== $output ? $output : '';
	}
}
