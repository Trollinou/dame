<?php
/**
 * Adherent Documents Metabox.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Adherent;

use WP_Post;
use DateTime;
use DAME\Services\Document_Storage;

/**
 * Class Documents
 * Manages seasonal health attestations and parental authorizations on member profile.
 */
class Documents {

	/**
	 * Register the metabox.
	 */
	public function register(): void {
		add_meta_box(
			'dame_adherent_documents_metabox',
			__( 'Documents & Signatures (Saison)', 'dame' ),
			array( $this, 'render' ),
			'adherent',
			'side',
			'default'
		);
	}

	/**
	 * Render the metabox content.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'dame_save_adherent_documents_meta', 'dame_adherent_documents_nonce' );

		$current_season_tag_id = (int) get_option( 'dame_current_season_tag_id' );
		$season_suffix         = $current_season_tag_id > 0 ? '_' . $current_season_tag_id : '';

		// Check if adherent is minor
		$birth_date_str = (string) get_post_meta( $post->ID, '_dame_birth_date', true );
		$is_minor       = false;
		if ( ! empty( $birth_date_str ) ) {
			$birth_date = DateTime::createFromFormat( 'Y-m-d', $birth_date_str );
			if ( $birth_date ) {
				$age      = ( new DateTime() )->diff( $birth_date )->y;
				$is_minor = ( $age < 18 );
			}
		}

		// Retrieve season-specific docs first, fallback to unversioned
		$health_doc = (string) get_post_meta( $post->ID, '_dame_doc_health_attestation_path' . $season_suffix, true );
		if ( empty( $health_doc ) ) {
			$health_doc = (string) get_post_meta( $post->ID, '_dame_doc_health_attestation_path', true );
		}

		$parental_doc = (string) get_post_meta( $post->ID, '_dame_doc_parental_auth_path' . $season_suffix, true );
		if ( empty( $parental_doc ) ) {
			$parental_doc = (string) get_post_meta( $post->ID, '_dame_doc_parental_auth_path', true );
		}

		$sig_date = (string) get_post_meta( $post->ID, '_dame_signature_date' . $season_suffix, true );
		if ( empty( $sig_date ) ) {
			$sig_date = (string) get_post_meta( $post->ID, '_dame_signature_date', true );
		}

		$health_url   = ! empty( $health_doc ) ? add_query_arg(
			array(
				'action'   => 'dame_download_doc',
				'type'     => 'health',
				'post_id'  => $post->ID,
				'_wpnonce' => wp_create_nonce( 'dame_download_doc_' . $post->ID ),
			),
			admin_url( 'admin-ajax.php' )
		) : '';
		$parental_url = ! empty( $parental_doc ) ? add_query_arg(
			array(
				'action'   => 'dame_download_doc',
				'type'     => 'parental',
				'post_id'  => $post->ID,
				'_wpnonce' => wp_create_nonce( 'dame_download_doc_' . $post->ID ),
			),
			admin_url( 'admin-ajax.php' )
		) : '';
		?>
		<div class="dame-adherent-docs-container">
			<?php if ( ! empty( $sig_date ) ) : ?>
				<p style="font-size: 0.9em; color: #555; margin-bottom: 12px;">
					ℹ️ <?php esc_html_e( 'Signé le :', 'dame' ); ?> <strong><?php echo esc_html( $sig_date ); ?></strong>
				</p>
			<?php endif; ?>

			<!-- Attestation de santé -->
			<div style="margin-bottom: 15px; padding-bottom: 12px; border-bottom: 1px solid #ddd;">
				<p style="margin: 0 0 6px 0;"><strong><?php esc_html_e( 'Attestation de santé :', 'dame' ); ?></strong></p>
				<?php if ( ! empty( $health_doc ) ) : ?>
					<p style="margin: 0 0 8px 0;">
						<span class="dashicons dashicons-yes-alt" style="color: green;"></span>
						<a href="<?php echo esc_url( $health_url ); ?>" class="button button-small" target="_blank">
							<?php esc_html_e( 'Voir le document', 'dame' ); ?>
						</a>
					</p>
				<?php else : ?>
					<p style="margin: 0 0 8px 0; color: #d63638; font-size: 0.9em;">
						<span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Non renseignée pour cette saison', 'dame' ); ?>
					</p>
				<?php endif; ?>
				<label style="font-size: 0.85em; color: #666; display: block; margin-bottom: 4px;">
					<?php esc_html_e( 'Importer / Remplacer (PDF, JPG, PNG) :', 'dame' ); ?>
				</label>
				<input type="file" name="dame_upload_health_doc" accept=".pdf,image/jpeg,image/png" style="max-width: 100%;" />
			</div>

			<!-- Autorisation parentale (si mineur) -->
			<?php if ( $is_minor ) : ?>
				<div style="margin-bottom: 10px;">
					<p style="margin: 0 0 6px 0;"><strong><?php esc_html_e( 'Autorisation parentale :', 'dame' ); ?></strong></p>
					<?php if ( ! empty( $parental_doc ) ) : ?>
						<p style="margin: 0 0 8px 0;">
							<span class="dashicons dashicons-yes-alt" style="color: green;"></span>
							<a href="<?php echo esc_url( $parental_url ); ?>" class="button button-small" target="_blank">
								<?php esc_html_e( 'Voir le document', 'dame' ); ?>
							</a>
						</p>
					<?php else : ?>
						<p style="margin: 0 0 8px 0; color: #d63638; font-size: 0.9em;">
							<span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Non renseignée pour cette saison', 'dame' ); ?>
						</p>
					<?php endif; ?>
					<label style="font-size: 0.85em; color: #666; display: block; margin-bottom: 4px;">
						<?php esc_html_e( 'Importer / Remplacer (PDF, JPG, PNG) :', 'dame' ); ?>
					</label>
					<input type="file" name="dame_upload_parental_doc" accept=".pdf,image/jpeg,image/png" style="max-width: 100%;" />
				</div>
			<?php endif; ?>
		</div>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var form = document.getElementById('post');
			if (form) {
				form.setAttribute('enctype', 'multipart/form-data');
			}
		});
		</script>
		<?php
	}

	/**
	 * Save manually uploaded documents on adherent profile.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( int $post_id ): void {
		$nonce = isset( $_POST['dame_adherent_documents_nonce'] ) ? sanitize_key( wp_unslash( $_POST['dame_adherent_documents_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dame_save_adherent_documents_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$current_season_tag_id = (int) get_option( 'dame_current_season_tag_id' );
		$season_suffix         = $current_season_tag_id > 0 ? '_' . $current_season_tag_id : '';
		$last_name             = (string) get_post_meta( $post_id, '_dame_last_name', true );
		$first_name            = (string) get_post_meta( $post_id, '_dame_first_name', true );
		$allowed_mimes         = array(
			'pdf'      => 'application/pdf',
			'jpg|jpeg' => 'image/jpeg',
			'png'      => 'image/png',
		);

		// 1. Health Doc Upload
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_FILES['dame_upload_health_doc']['tmp_name'] ) && ! empty( $_FILES['dame_upload_health_doc']['tmp_name'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$file_name = isset( $_FILES['dame_upload_health_doc']['name'] ) ? sanitize_file_name( wp_unslash( (string) $_FILES['dame_upload_health_doc']['name'] ) ) : '';
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$tmp_path = isset( $_FILES['dame_upload_health_doc']['tmp_name'] ) ? sanitize_text_field( wp_unslash( (string) $_FILES['dame_upload_health_doc']['tmp_name'] ) ) : '';
			$check    = wp_check_filetype( $file_name, $allowed_mimes );
			if ( $check['ext'] ) {
				$target_name = 'attestation_sante_' . sanitize_file_name( $last_name . '_' . $first_name . '_manuel.' . $check['ext'] );
				$stored_name = Document_Storage::import_file( $tmp_path, $target_name );
				if ( $stored_name ) {
					// Delete previous if any
					$old = (string) get_post_meta( $post_id, '_dame_doc_health_attestation_path' . $season_suffix, true );
					if ( $old ) {
						Document_Storage::delete_file( $old );
					}
					update_post_meta( $post_id, '_dame_doc_health_attestation_path' . $season_suffix, $stored_name );
					update_post_meta( $post_id, '_dame_doc_health_attestation_path', $stored_name );
					update_post_meta( $post_id, '_dame_health_document', 'attestation' );
				}
			}
		}

		// 2. Parental Doc Upload
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_FILES['dame_upload_parental_doc']['tmp_name'] ) && ! empty( $_FILES['dame_upload_parental_doc']['tmp_name'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$file_name = isset( $_FILES['dame_upload_parental_doc']['name'] ) ? sanitize_file_name( wp_unslash( (string) $_FILES['dame_upload_parental_doc']['name'] ) ) : '';
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$tmp_path = isset( $_FILES['dame_upload_parental_doc']['tmp_name'] ) ? sanitize_text_field( wp_unslash( (string) $_FILES['dame_upload_parental_doc']['tmp_name'] ) ) : '';
			$check    = wp_check_filetype( $file_name, $allowed_mimes );
			if ( $check['ext'] ) {
				$target_name = 'autorisation_parentale_' . sanitize_file_name( $last_name . '_' . $first_name . '_manuel.' . $check['ext'] );
				$stored_name = Document_Storage::import_file( $tmp_path, $target_name );
				if ( $stored_name ) {
					$old = (string) get_post_meta( $post_id, '_dame_doc_parental_auth_path' . $season_suffix, true );
					if ( $old ) {
						Document_Storage::delete_file( $old );
					}
					update_post_meta( $post_id, '_dame_doc_parental_auth_path' . $season_suffix, $stored_name );
					update_post_meta( $post_id, '_dame_doc_parental_auth_path', $stored_name );
				}
			}
		}
	}
}
