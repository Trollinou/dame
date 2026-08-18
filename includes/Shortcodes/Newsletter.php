<?php
/**
 * Newsletter Shortcode.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Shortcodes;

use DAME\Services\Newsletter as NewsletterService;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Newsletter
 *
 * Handles [dame_newsletter] and [newsletter] shortcodes.
 */
class Newsletter {

	/**
	 * Unique counter for multiple forms on same page.
	 *
	 * @var int
	 */
	private static int $instance_counter = 0;

	/**
	 * Initializes the shortcodes and AJAX handlers.
	 */
	public function init(): void {
		add_shortcode( 'dame_newsletter', array( $this, 'render' ) );
		add_shortcode( 'newsletter', array( $this, 'render' ) );

		add_action( 'wp_ajax_dame_submit_newsletter', array( $this, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_dame_submit_newsletter', array( $this, 'handle_submission' ) );
	}

	/**
	 * Renders the shortcode HTML.
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render( $atts ): string {
		self::$instance_counter++;
		$form_id = 'dame-nl-form-' . self::$instance_counter;
		$modal_id = 'dame-nl-modal-' . self::$instance_counter;

		$attributes = shortcode_atts(
			array(
				'button_text'  => __( 'S\'inscrire à la newsletter', 'dame' ),
				'button_class' => '',
				'class'        => '',
				'show_icon'    => 'true',
				'layout'       => 'button', // 'button' or 'inline'
				'title'        => __( 'Inscription à la newsletter', 'dame' ),
				'subtitle'     => __( 'Recevez régulièrement nos actualités et informations.', 'dame' ),
			),
			is_array( $atts ) ? $atts : array(),
			'dame_newsletter'
		);

		// Enqueue the public newsletter script
		wp_enqueue_script(
			'dame-public-newsletter',
			\DAME_PLUGIN_URL . 'assets/js/public-newsletter.js',
			array(),
			\DAME_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'dame-public-newsletter',
			'dameNewsletterData',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'dame_newsletter_nonce' ),
				'i18n'      => array(
					'submitting'    => __( 'Inscription en cours...', 'dame' ),
					'submitSuccess' => __( 'Inscription réussie !', 'dame' ),
					'genericError'  => __( 'Une erreur est survenue. Veuillez réessayer.', 'dame' ),
				),
			)
		);

		// Enqueue public styles
		wp_enqueue_style(
			'dame-public-styles',
			\DAME_PLUGIN_URL . 'assets/css/public-styles.css',
			array(),
			\DAME_VERSION
		);

		ob_start();

		if ( 'inline' === $attributes['layout'] ) {
			?>
			<div class="dame-nl-container dame-nl-container--inline">
				<?php $this->render_form_content( $form_id, $attributes['title'], $attributes['subtitle'] ); ?>
			</div>
			<?php
		} else {
			$custom_classes = trim( (string) ( $attributes['button_class'] ?: $attributes['class'] ) );
			$btn_classes    = array( 'dame-nl-btn-trigger' );

			if ( ! empty( $custom_classes ) ) {
				$btn_classes[] = 'dame-nl-btn-trigger--custom';
				$btn_classes[] = $custom_classes;
			} else {
				// Standard WP element button helper class
				$btn_classes[] = 'wp-element-button';
			}

			$show_icon = filter_var( $attributes['show_icon'], FILTER_VALIDATE_BOOLEAN );
			?>
			<div class="dame-nl-container dame-nl-container--button">
				<button type="button" class="<?php echo esc_attr( implode( ' ', $btn_classes ) ); ?>" data-dame-modal-target="<?php echo esc_attr( $modal_id ); ?>">
					<?php if ( $show_icon ) : ?>
						<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
					<?php endif; ?>
					<span><?php echo esc_html( $attributes['button_text'] ); ?></span>
				</button>

				<div id="<?php echo esc_attr( $modal_id ); ?>" class="dame-nl-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="<?php echo esc_attr( $modal_id ); ?>-title">
					<div class="dame-nl-modal__backdrop" data-dame-modal-close></div>
					<div class="dame-nl-modal__dialog">
						<button type="button" class="dame-nl-modal__close" data-dame-modal-close aria-label="<?php esc_attr_e( 'Fermer la fenêtre', 'dame' ); ?>">&times;</button>
						<div class="dame-nl-modal__body">
							<?php $this->render_form_content( $form_id, $attributes['title'], $attributes['subtitle'], $modal_id . '-title' ); ?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		return (string) ob_get_clean();
	}

	/**
	 * Helper to render the form inner elements.
	 *
	 * @param string $form_id Unique form ID.
	 * @param string $title Form title.
	 * @param string $subtitle Form subtitle.
	 * @param string $title_id Optional title HTML id.
	 */
	private function render_form_content( string $form_id, string $title, string $subtitle, string $title_id = '' ): void {
		?>
		<form id="<?php echo esc_attr( $form_id ); ?>" class="dame-nl-form" novalidate>
			<input type="hidden" name="action" value="dame_submit_newsletter">
			<?php wp_nonce_field( 'dame_newsletter_nonce', 'dame_newsletter_nonce_field' ); ?>

			<!-- Anti-spam honeypot -->
			<div class="dame-nl-hp" aria-hidden="true" style="display:none !important; visibility:hidden !important; position:absolute; left:-9999px;">
				<label for="<?php echo esc_attr( $form_id ); ?>-hp"><?php esc_html_e( 'Ne pas remplir ce champ', 'dame' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $form_id ); ?>-hp" name="dame_newsletter_hp" tabindex="-1" autocomplete="off">
			</div>

			<?php if ( ! empty( $title ) ) : ?>
				<h3 <?php echo ! empty( $title_id ) ? 'id="' . esc_attr( $title_id ) . '"' : ''; ?> class="dame-nl-form__title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>

			<?php if ( ! empty( $subtitle ) ) : ?>
				<p class="dame-nl-form__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>

			<div class="dame-nl-form__row dame-nl-form__row--two-cols">
				<div class="dame-nl-form__group">
					<label for="<?php echo esc_attr( $form_id ); ?>-last-name" class="dame-nl-form__label">
						<?php esc_html_e( 'Nom', 'dame' ); ?> <span class="dame-nl-form__required" aria-hidden="true">*</span>
					</label>
					<input type="text" id="<?php echo esc_attr( $form_id ); ?>-last-name" name="dame_newsletter_last_name" class="dame-nl-form__input" required autocomplete="family-name">
				</div>

				<div class="dame-nl-form__group">
					<label for="<?php echo esc_attr( $form_id ); ?>-first-name" class="dame-nl-form__label">
						<?php esc_html_e( 'Prénom', 'dame' ); ?> <span class="dame-nl-form__required" aria-hidden="true">*</span>
					</label>
					<input type="text" id="<?php echo esc_attr( $form_id ); ?>-first-name" name="dame_newsletter_first_name" class="dame-nl-form__input" required autocomplete="given-name">
				</div>
			</div>

			<div class="dame-nl-form__group">
				<label for="<?php echo esc_attr( $form_id ); ?>-email" class="dame-nl-form__label">
					<?php esc_html_e( 'Adresse email', 'dame' ); ?> <span class="dame-nl-form__required" aria-hidden="true">*</span>
				</label>
				<input type="email" id="<?php echo esc_attr( $form_id ); ?>-email" name="dame_newsletter_email" class="dame-nl-form__input" required autocomplete="email">
			</div>

			<div class="dame-nl-form__feedback" role="alert" style="display:none;"></div>

			<div class="dame-nl-form__actions">
				<button type="submit" class="dame-nl-form__submit">
					<span class="dame-nl-form__submit-text"><?php esc_html_e( 'Je m\'inscris', 'dame' ); ?></span>
					<span class="dame-nl-form__spinner" aria-hidden="true" style="display:none;"></span>
				</button>
			</div>
		</form>
		<?php
	}

	/**
	 * Handles AJAX subscription submission.
	 */
	public function handle_submission(): void {
		// 1. Security: verify nonce
		$nonce = isset( $_POST['dame_newsletter_nonce_field'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_newsletter_nonce_field'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dame_newsletter_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'La session a expiré. Veuillez rafraîchir la page et réessayer.', 'dame' ) ), 403 );
		}

		// 2. Honeypot check for bots
		if ( ! empty( $_POST['dame_newsletter_hp'] ) ) {
			// Silently pretend success
			wp_send_json_success( array( 'message' => __( 'Votre inscription a bien été prise en compte.', 'dame' ) ) );
		}

		// 3. Field validation
		$last_name  = isset( $_POST['dame_newsletter_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_newsletter_last_name'] ) ) : '';
		$first_name = isset( $_POST['dame_newsletter_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_newsletter_first_name'] ) ) : '';
		$email      = isset( $_POST['dame_newsletter_email'] ) ? sanitize_email( wp_unslash( $_POST['dame_newsletter_email'] ) ) : '';

		$errors = array();
		if ( empty( $last_name ) ) {
			$errors[] = __( 'Le nom est obligatoire.', 'dame' );
		}
		if ( empty( $first_name ) ) {
			$errors[] = __( 'Le prénom est obligatoire.', 'dame' );
		}
		if ( empty( $email ) || ! is_email( $email ) ) {
			$errors[] = __( 'Une adresse email valide est obligatoire.', 'dame' );
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( array( 'message' => implode( ' ', $errors ) ), 400 );
		}

		// 4. Delegate to Newsletter Service
		$service = new NewsletterService();
		$result  = $service->handle_subscription( $first_name, $last_name, $email );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ), 500 );
		}
	}
}
