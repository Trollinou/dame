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
	public function init(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
		add_action( 'admin_post_dame_send_test_email', [ $this, 'handle_test_send' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue scripts for the metabox.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ): void {
		$screen = get_current_screen();

		if ( ! $screen || 'dame_message' !== $screen->post_type ) {
			return;
		}

		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		global $post;
		if ( ! $post ) {
			return;
		}

		wp_enqueue_script( 'dame-admin-test-send', \DAME_PLUGIN_URL . 'assets/js/admin-test-send.js', array( 'jquery' ), \DAME_VERSION, true );
		wp_localize_script( 'dame-admin-test-send', 'dame_test_send_data', array(
			'post_id' => $post->ID,
			'nonce' => wp_create_nonce( 'dame_test_send_' . $post->ID ),
			'alert_empty' => __( 'Veuillez saisir un email.', 'dame' ),
			'admin_url' => admin_url( 'admin-post.php' )
		) );
	}

	/**
	 * Add the metabox.
	 */
	public function add_metabox(): void {
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
	public function render( $post ): void {
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

		<?php
		$user_id  = get_current_user_id();
		$list_url = $user_id ? (string) get_user_meta( $user_id, 'dame_last_message_list_url', true ) : '';
		if ( empty( $list_url ) ) {
			$list_url = admin_url( 'edit.php?post_type=dame_message' );
		} else {
			$list_url = admin_url( ltrim( str_replace( '/wp-admin/', '', $list_url ), '/' ) );
		}
		?>
		<style>
			.dame-back-link {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				text-decoration: none;
				color: #1e293b;
				background-color: #f8fafc;
				border: 1px solid #cbd5e1;
				border-radius: 6px;
				padding: 8px 16px;
				font-weight: 500;
				font-size: 13px;
				transition: all 0.15s ease-in-out;
				box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
			}
			.dame-back-link:hover {
				background-color: #f1f5f9;
				border-color: #94a3b8;
				color: #0f172a;
			}
			.dame-back-link:hover .dashicons {
				transform: translateX(-3px);
				color: #0f172a;
			}
			.dame-back-link .dashicons {
				font-size: 18px;
				width: 18px;
				height: 18px;
				line-height: 18px;
				margin: 0;
				color: #64748b;
				transition: transform 0.15s ease-in-out;
			}
		</style>
		<div style="margin-top: 15px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
			<a href="<?php echo esc_url( $list_url ); ?>" class="dame-back-link">
				<span class="dashicons dashicons-arrow-left-alt"></span>
				<span style="line-height: 1;"><?php esc_html_e( 'Retour à la liste filtrée', 'dame' ); ?></span>
			</a>
		</div>
		<?php
	}

	/**
	 * Handle test email sending.
	 */
	public function handle_test_send(): void {
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
