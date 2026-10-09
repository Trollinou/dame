<?php
/**
 * Adherent Payment & Membership Metabox.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Adherent;

use WP_Post;
use DAME\Services\Data_Provider;

/**
 * Class Payment
 */
class Payment {

	/**
	 * Register the meta box.
	 */
	public function register(): void {
		add_meta_box(
			'dame_adherent_payment_metabox',
			__( 'Cotisation & Règlement (Saison)', 'dame' ),
			array( $this, 'render' ),
			'adherent',
			'side',
			'default'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'dame_save_adherent_payment_meta', 'dame_adherent_payment_nonce' );

		$current_season_tag_id = (int) get_option( 'dame_current_season_tag_id' );
		$season_suffix         = $current_season_tag_id > 0 ? '_' . $current_season_tag_id : '';

		$current_season_term = $current_season_tag_id > 0 ? get_term( $current_season_tag_id, 'dame_saison_adhesion' ) : null;
		$season_name         = ( $current_season_term && ! is_wp_error( $current_season_term ) ) ? $current_season_term->name : __( 'Non définie', 'dame' );

		// Determine renewal status for current season.
		$saved_status = (string) get_post_meta( $post->ID, '_dame_payment_status' . $season_suffix, true );
		if ( empty( $saved_status ) ) {
			$is_renewal   = Data_Provider::is_adherent_renewal( $post->ID, $current_season_tag_id );
			$saved_status = $is_renewal ? 'renewal' : 'first_registration';
		}

		// Determine amount for current season.
		$saved_amount_raw = get_post_meta( $post->ID, '_dame_payment_amount' . $season_suffix, true );
		if ( '' !== $saved_amount_raw && false !== $saved_amount_raw ) {
			$amount = (float) $saved_amount_raw;
		} else {
			$amount = Data_Provider::calculate_adherent_fee( $post->ID, $current_season_tag_id, $saved_status );
		}

		// Determine payment date for current season.
		$payment_date = (string) get_post_meta( $post->ID, '_dame_payment_date' . $season_suffix, true );
		if ( empty( $payment_date ) ) {
			$activation_date = (string) get_post_meta( $post->ID, '_dame_season_activation_date' . $season_suffix, true );
			$default_date    = wp_date( 'Y-m-d' );
			$payment_date    = ! empty( $activation_date ) ? $activation_date : ( is_string( $default_date ) ? $default_date : '' );
		}

		// Determine payment method.
		$payment_method = (string) get_post_meta( $post->ID, '_dame_payment_method' . $season_suffix, true );
		if ( empty( $payment_method ) ) {
			$payment_method = 'HelloAsso';
		}

		$method_options = array(
			'HelloAsso'      => __( 'HelloAsso', 'dame' ),
			'Chèques'        => __( 'Chèques', 'dame' ),
			'Espèces'        => __( 'Espèces', 'dame' ),
			'Carte bancaire' => __( 'Carte bancaire', 'dame' ),
		);
		?>
		<p>
			<strong><?php esc_html_e( 'Saison active :', 'dame' ); ?></strong>
			<span style="color: #0073aa; font-weight: 600;"><?php echo esc_html( $season_name ); ?></span>
		</p>

		<p>
			<label for="dame_payment_status"><strong><?php esc_html_e( 'Statut d\'adhésion :', 'dame' ); ?></strong></label><br>
			<select id="dame_payment_status" name="dame_payment_status" style="width: 100%;">
				<option value="renewal" <?php selected( $saved_status, 'renewal' ); ?>>
					<?php esc_html_e( 'Renouvellement', 'dame' ); ?>
				</option>
				<option value="first_registration" <?php selected( $saved_status, 'first_registration' ); ?>>
					<?php esc_html_e( '1ère adhésion (Polo floqué)', 'dame' ); ?>
				</option>
			</select>
		</p>

		<p>
			<label for="dame_payment_amount"><strong><?php esc_html_e( 'Montant réglé (€) :', 'dame' ); ?></strong></label><br>
			<input type="number" step="0.5" min="0" id="dame_payment_amount" name="dame_payment_amount" value="<?php echo esc_attr( (string) $amount ); ?>" style="width: 100%;" required="required" />
		</p>

		<p>
			<label for="dame_payment_date"><strong><?php esc_html_e( 'Date du règlement :', 'dame' ); ?></strong></label><br>
			<input type="date" id="dame_payment_date" name="dame_payment_date" value="<?php echo esc_attr( $payment_date ); ?>" style="width: 100%;" required="required" />
		</p>

		<p>
			<label for="dame_payment_method"><strong><?php esc_html_e( 'Mode de règlement :', 'dame' ); ?></strong></label><br>
			<select id="dame_payment_method" name="dame_payment_method" style="width: 100%;">
				<?php foreach ( $method_options as $val => $lbl ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $payment_method, $val ); ?>>
						<?php echo esc_html( $lbl ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<hr style="margin: 15px 0;">

		<p style="text-align: center; margin-bottom: 5px;">
			<button type="button" class="button button-secondary dame-open-attestation-btn" data-adherent-id="<?php echo esc_attr( (string) $post->ID ); ?>" style="width: 100%;">
				<span class="dashicons dashicons-printer" style="vertical-align: middle; margin-top: -2px;"></span>
				<?php esc_html_e( 'Attestation de paiement', 'dame' ); ?>
			</button>
		</p>
		<?php
	}

	/**
	 * Save the meta box.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( int $post_id ): void {
		$nonce = isset( $_POST['dame_adherent_payment_nonce'] ) ? sanitize_key( wp_unslash( $_POST['dame_adherent_payment_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dame_save_adherent_payment_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$current_season_tag_id = (int) get_option( 'dame_current_season_tag_id' );
		$season_suffix         = $current_season_tag_id > 0 ? '_' . $current_season_tag_id : '';

		if ( isset( $_POST['dame_payment_status'] ) ) {
			$status = sanitize_key( wp_unslash( $_POST['dame_payment_status'] ) );
			update_post_meta( $post_id, '_dame_payment_status' . $season_suffix, $status );
		}

		if ( isset( $_POST['dame_payment_amount'] ) ) {
			$raw_amount = sanitize_text_field( wp_unslash( $_POST['dame_payment_amount'] ) );
			$amount     = max( 0.0, (float) $raw_amount );
			update_post_meta( $post_id, '_dame_payment_amount' . $season_suffix, $amount );
			update_post_meta( $post_id, '_dame_payment_amount', $amount );
		}

		if ( isset( $_POST['dame_payment_date'] ) ) {
			$date = sanitize_text_field( wp_unslash( $_POST['dame_payment_date'] ) );
			update_post_meta( $post_id, '_dame_payment_date' . $season_suffix, $date );
			update_post_meta( $post_id, '_dame_payment_date', $date );
		}

		if ( isset( $_POST['dame_payment_method'] ) ) {
			$method = sanitize_text_field( wp_unslash( $_POST['dame_payment_method'] ) );
			update_post_meta( $post_id, '_dame_payment_method' . $season_suffix, $method );
			update_post_meta( $post_id, '_dame_payment_method', $method );
		}
	}
}
