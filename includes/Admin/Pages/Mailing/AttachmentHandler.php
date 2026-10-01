<?php
/**
 * Attachment Handler for Mailing.
 *
 * @package DAME\Admin\Pages\Mailing
 */

declare(strict_types=1);

namespace DAME\Admin\Pages\Mailing;

/**
 * Handles validation and upload of email attachments.
 */
class AttachmentHandler {

	/**
	 * Allowed MIME types for mailing attachments.
	 *
	 * @var array<string, string>
	 */
	private const ALLOWED_MIMES = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'pdf'          => 'application/pdf',
		'doc'          => 'application/msword',
		'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'odt'          => 'application/vnd.oasis.opendocument.text',
	);

	/**
	 * Process uploaded attachment for a message.
	 *
	 * @param int $message_id Message post ID.
	 * @return bool True if upload succeeded or no file provided, false on upload error.
	 */
	public function handle_upload( int $message_id ): bool {
		if ( ! isset( $_FILES['dame_message_attachment']['error'] ) || empty( $_FILES['dame_message_attachment']['name'] ) || UPLOAD_ERR_NO_FILE === $_FILES['dame_message_attachment']['error'] ) {
			delete_post_meta( $message_id, '_dame_message_attachment' );
			return true;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$upload_overrides = array(
			'test_form' => false,
			'mimes'     => self::ALLOWED_MIMES,
		);

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$file = $_FILES['dame_message_attachment'] ?? array();
		if ( ! is_array( $file ) ) {
			return false;
		}

		$upload = wp_handle_upload( $file, $upload_overrides );

		if ( isset( $upload['file'] ) && ! isset( $upload['error'] ) ) {
			update_post_meta( $message_id, '_dame_message_attachment', sanitize_text_field( (string) $upload['file'] ) );
			return true;
		}

		return false;
	}
}
