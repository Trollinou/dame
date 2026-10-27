<?php
/**
 * HelloAsso Settings Tab.
 *
 * @package DAME\Admin\Settings\Tabs
 */

declare(strict_types=1);

namespace DAME\Admin\Settings\Tabs;

use DAME\Services\HelloAsso\Client as HelloAssoClient;

/**
 * Class HelloAsso
 */
class HelloAsso {

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'HelloAsso', 'dame' );
	}

	/**
	 * Register settings sections and fields.
	 */
	public function register(): void {
		add_settings_section(
			'dame_helloasso_api_section',
			__( 'Connexion API v5 (OAuth2)', 'dame' ),
			array( $this, 'render_api_section_intro' ),
			'dame_helloasso_section_group'
		);

		add_settings_field(
			'helloasso_environment',
			__( 'Environnement', 'dame' ),
			array( $this, 'render_environment_field' ),
			'dame_helloasso_section_group',
			'dame_helloasso_api_section'
		);

		add_settings_field(
			'helloasso_client_id',
			__( 'Client ID', 'dame' ),
			array( $this, 'render_client_id_field' ),
			'dame_helloasso_section_group',
			'dame_helloasso_api_section'
		);

		add_settings_field(
			'helloasso_client_secret',
			__( 'Client Secret', 'dame' ),
			array( $this, 'render_client_secret_field' ),
			'dame_helloasso_section_group',
			'dame_helloasso_api_section'
		);

		add_settings_field(
			'helloasso_org_slug',
			__( 'Slug de l\'organisation', 'dame' ),
			array( $this, 'render_org_slug_field' ),
			'dame_helloasso_section_group',
			'dame_helloasso_api_section'
		);

		add_settings_section(
			'dame_helloasso_webhook_section',
			__( 'Notifications Webhooks (Temps Réel)', 'dame' ),
			array( $this, 'render_webhook_section' ),
			'dame_helloasso_section_group'
		);
	}

	/**
	 * Render section intro.
	 */
	public function render_api_section_intro(): void {
		echo '<p class="description">' . esc_html__( 'Renseignez ici vos identifiants d\'API HelloAsso v5 générés depuis votre espace association (Menu Mon compte > Intégrations et API).', 'dame' ) . '</p>';
	}

	/**
	 * Render Environment field.
	 */
	public function render_environment_field(): void {
		$options = get_option( 'dame_options', array() );
		$val     = (string) ( $options['helloasso_environment'] ?? HelloAssoClient::ENV_PRODUCTION );
		?>
		<select name="dame_options[helloasso_environment]" id="helloasso_environment">
			<option value="<?php echo esc_attr( HelloAssoClient::ENV_PRODUCTION ); ?>" <?php selected( $val, HelloAssoClient::ENV_PRODUCTION ); ?>>
				<?php esc_html_e( 'Production (api.helloasso.com)', 'dame' ); ?>
			</option>
			<option value="<?php echo esc_attr( HelloAssoClient::ENV_SANDBOX ); ?>" <?php selected( $val, HelloAssoClient::ENV_SANDBOX ); ?>>
				<?php esc_html_e( 'Bac à sable / Test (api.helloasso-sandbox.com)', 'dame' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Choisissez l\'environnement correspondant à votre compte HelloAsso (Production ou Sandbox).', 'dame' ); ?>
		</p>
		<?php
	}

	/**
	 * Render Client ID field.
	 */
	public function render_client_id_field(): void {
		$options = get_option( 'dame_options', array() );
		$val     = (string) ( $options['helloasso_client_id'] ?? '' );
		echo '<input type="text" name="dame_options[helloasso_client_id]" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="ex: 12345678-abcd-..." autocomplete="off" autocorrect="off" spellcheck="false" />';
	}

	/**
	 * Render Client Secret field.
	 */
	public function render_client_secret_field(): void {
		$options = get_option( 'dame_options', array() );
		$val     = (string) ( $options['helloasso_client_secret'] ?? '' );
		echo '<input type="password" name="dame_options[helloasso_client_secret]" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="••••••••••••••••" autocomplete="new-password" autocorrect="off" spellcheck="false" />';
	}

	/**
	 * Render Org Slug field.
	 */
	public function render_org_slug_field(): void {
		$options = get_option( 'dame_options', array() );
		$val     = (string) ( $options['helloasso_org_slug'] ?? '' );
		echo '<input type="text" name="dame_options[helloasso_org_slug]" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="ex: mon-club-echecs" autocomplete="off" autocorrect="off" spellcheck="false" />';
		echo '<p class="description">' . esc_html__( 'Le slug correspond au nom de votre association dans l\'URL HelloAsso (ex: helloasso.com/associations/votre-slug).', 'dame' ) . '</p>';
	}

	/**
	 * Render Webhook notification section.
	 */
	public function render_webhook_section(): void {
		$webhook_url = HelloAssoClient::get_webhook_url();
		?>
		<p class="description">
			<?php esc_html_e( 'Pour recevoir automatiquement les inscriptions et paiements en direct, copiez cette URL dans votre espace HelloAsso (Menu Mon compte > Intégrations et API > Notifications / Webhooks) :', 'dame' ); ?>
		</p>
		<p>
			<input type="text" readonly="readonly" value="<?php echo esc_attr( $webhook_url ); ?>" class="large-text code" style="background:#f6f7f7;" onfocus="this.select();" />
		</p>
		<p class="description">
			<?php esc_html_e( 'Cochez les événements : « Commande validée » (Order) et « Paiement validé » (Payment) sur HelloAsso.', 'dame' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		$is_configured = HelloAssoClient::is_configured();

		if ( $is_configured ) {
			$conn = HelloAssoClient::test_connection();
			if ( $conn['success'] ) {
				echo '<div class="notice notice-success inline" style="margin-bottom:15px; padding:10px 15px;"><p><strong>✓ ' . esc_html( $conn['message'] ) . '</strong></p></div>';
			} else {
				echo '<div class="notice notice-error inline" style="margin-bottom:15px; padding:10px 15px;"><p><strong>⚠ ' . esc_html__( 'Erreur de connexion API HelloAsso :', 'dame' ) . '</strong> ' . esc_html( $conn['message'] ) . '</p></div>';
			}
		}

		do_settings_sections( 'dame_helloasso_section_group' );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array<string, mixed> $input New input.
	 * @param array<string, mixed> $existing_options Existing options.
	 * @return array<string, mixed> Sanitized options.
	 */
	public function sanitize( array $input, array $existing_options ): array {
		if ( isset( $input['helloasso_environment'] ) && in_array( $input['helloasso_environment'], array( HelloAssoClient::ENV_PRODUCTION, HelloAssoClient::ENV_SANDBOX ), true ) ) {
			$existing_options['helloasso_environment'] = $input['helloasso_environment'];
		}
		if ( isset( $input['helloasso_client_id'] ) ) {
			$existing_options['helloasso_client_id'] = sanitize_text_field( trim( (string) $input['helloasso_client_id'] ) );
		}
		if ( isset( $input['helloasso_client_secret'] ) ) {
			$existing_options['helloasso_client_secret'] = sanitize_text_field( trim( (string) $input['helloasso_client_secret'] ) );
		}
		if ( isset( $input['helloasso_org_slug'] ) ) {
			$existing_options['helloasso_org_slug'] = sanitize_text_field( trim( (string) $input['helloasso_org_slug'] ) );
		}

		// Ensure webhook token exists.
		if ( empty( $existing_options['helloasso_webhook_token'] ) ) {
			$existing_options['helloasso_webhook_token'] = wp_generate_password( 32, false, false );
		}

		return $existing_options;
	}
}

