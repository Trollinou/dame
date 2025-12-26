<?php
/**
 * Test Send Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\Message;

use DAME\API\Tracker;

/**
 * Class TestSend
 */
class TestSend {

	/**
	 * Initialize the metabox.
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
		add_action( 'admin_post_dame_send_test_email', [ $this, 'handle_test_send' ] );
	}

	/**
	 * Add the metabox.
	 */
	public function add_metabox() {
		add_meta_box(
			'dame_message_test_send',
			__( 'Envoyer un test', 'dame' ),
			[ $this, 'render' ],
			'dame_message',
			'side',
			'default'
		);
	}

	/**
	 * Render the metabox.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render( $post ) {
		$current_user_email = wp_get_current_user()->user_email;
		?>
		<p class="description">
			<?php esc_html_e( 'Envoyez un email de test pour vérifier le rendu.', 'dame' ); ?>
		</p>
		<!-- Note: We can't nest forms in HTML. The WP Edit Post screen is already a form.
		     So we usually use AJAX or a separate submit button that changes the action via JS.
		     OR simpler: A link/button that triggers a JS call.

		     HOWEVER, "Source: message-actions.php" suggests legacy might have used a separate form or a clever hack.
		     But since we are refactoring, let's use a small dedicated form if possible? No, nested forms are invalid.

		     Best approach for "simple admin": Use JS to post to admin-post.php.
		-->

		<label for="dame_test_email">Email :</label>
		<input type="email" id="dame_test_email" value="<?php echo esc_attr( $current_user_email ); ?>" style="width:100%; margin-bottom:10px;">

		<button type="button" class="button" id="dame_send_test_btn"><?php esc_html_e( 'Envoyer le test', 'dame' ); ?></button>
		<span id="dame_test_spinner" class="spinner"></span>
		<div id="dame_test_result" style="margin-top:10px;"></div>

		<script>
		jQuery(document).ready(function($) {
			$('#dame_send_test_btn').on('click', function() {
				var email = $('#dame_test_email').val();
				var post_id = <?php echo $post->ID; ?>;
				var nonce = '<?php echo wp_create_nonce( 'dame_test_send_' . $post->ID ); ?>';

				if (!email) {
					alert('<?php echo esc_js( __( 'Veuillez saisir un email.', 'dame' ) ); ?>');
					return;
				}

				// The form content (Title/Editor) might be unsaved.
				// Ideally we should autosave first, or grab current values from DOM.
				// For now, let's assume we test the SAVED version, or user should save first.
				// Grabbing DOM values for TinyMCE is complex. Let's send the Post ID and rely on saved content for simplicity unless requested otherwise.

				$('#dame_test_spinner').addClass('is-active');
				$('#dame_test_result').html('');

				// Using a specialized admin-post action via AJAX just to reuse the PHP handler logic easily?
				// Actually, admin-post is for full page reloads usually. wp_ajax is for AJAX.
				// The plan said "Hook POST : admin_post_dame_process_mailing" for the main mailing page.
				// For this Test Send, let's just make a hidden form targetting a hidden iframe? No, that's old school.
				// I'll stick to a simple form submission to admin-post.php if I can't do AJAX easily.
				// BUT I can't nest forms.
				// So I will implement a JS function that creates a form on the fly and submits it?
				// OR just use wp_ajax.

				// Wait, the prompt implies "admin_post" handles actions.
				// But "Metabox de Test" usually implies staying on the page.
				// Let's implement an AJAX handler in the class but hook it to wp_ajax_... as well?
				// The prompt instructions for "Action Duplication" mentions "admin_action_...".
				// The prompt for "Page d'Envoi" mentions "admin_post_...".
				// For "Metabox de Test", it just says "Source: message-actions.php".

				// I'll implement a simple form POST to admin-post.php via a dynamically created form,
				// then redirect back to edit post. This is the most robust "no-js-framework" way.

				var form = $('<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">' +
					'<input type="hidden" name="action" value="dame_send_test_email">' +
					'<input type="hidden" name="post_ID" value="' + post_id + '">' +
					'<input type="hidden" name="test_email" value="' + email + '">' +
					'<input type="hidden" name="_wpnonce" value="' + nonce + '">' +
					'</form>');
				$('body').append(form);
				form.submit();
			});
		});
		</script>
		<?php
	}

	/**
	 * Handle test email sending.
	 */
	public function handle_test_send() {
		if ( ! isset( $_POST['_wpnonce'], $_POST['post_ID'], $_POST['test_email'] ) ) {
			wp_die( 'Missing data.' );
		}

		$post_id = absint( $_POST['post_ID'] );
		check_admin_referer( 'dame_test_send_' . $post_id );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( 'Permission denied.' );
		}

		$email = sanitize_email( $_POST['test_email'] );
		if ( ! is_email( $email ) ) {
			wp_die( 'Invalid email.' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( 'Post not found.' );
		}

		$subject = $post->post_title;
		$content = apply_filters( 'the_content', $post->post_content );

		// Inject tracking pixel for test? Maybe not needed for test, or yes to verify it works.
		// Let's inject it to be sure.
		$tracking_url = Tracker::get_pixel_url( $post_id, $email );
		$pixel_img    = '<img src="' . esc_url( $tracking_url ) . '" alt="" width="1" height="1" style="display:none; border:0;" />';
		$message_body = '<div style="margin: 1cm;">' . $content . $pixel_img . '</div>';

		$options      = get_option( 'dame_options' );
		$sender_email = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );
		$headers      = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . $sender_email . '>',
		);

		// Force SMTP config via action just in case.
		// (Mailer class handles this globally on phpmailer_init).

		$sent = wp_mail( $email, '[TEST] ' . $subject, $message_body, $headers );

		// Redirect back.
		$redirect_url = get_edit_post_link( $post_id, 'url' );
		$redirect_url = add_query_arg( 'dame_test_sent', $sent ? '1' : '0', $redirect_url );

		wp_redirect( $redirect_url );
		exit;
	}
}
