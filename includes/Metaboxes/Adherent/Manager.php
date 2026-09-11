<?php
/**
 * Adherent Metabox Manager.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\Adherent;

use WP_Post;
use DAME\Metaboxes\Adherent\Identity;
use DAME\Metaboxes\Adherent\Legal;
use DAME\Metaboxes\Adherent\School;
use DAME\Metaboxes\Adherent\Diverse;
use DAME\Metaboxes\Adherent\Classification;
use DAME\Metaboxes\Adherent\Groups;
use DAME\Metaboxes\Adherent\Actions;

/**
 * Class Manager
 */
class Manager {

	/**
	 * Initialize the manager.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'edit_form_top', array( $this, 'render_back_button' ) );
	}

	/**
	 * Register meta boxes.
	 */
	public function add_meta_boxes(): void {
		remove_meta_box( 'postcustom', 'adherent', 'normal' );

		$identity = new Identity();
		$identity->register();

		$school = new School();
		$school->register();

		$legal = new Legal();
		$legal->register();

		$diverse = new Diverse();
		$diverse->register();

		$classification = new Classification();
		$classification->register();

		$groups = new Groups();
		$groups->register();

		$actions = new Actions();
		$actions->register();
	}

	/**
	 * Save meta boxes.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_post( $post_id ): void {
		// Common security checks.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( 'adherent' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Delegate saving to components.
		$identity = new Identity();
		$identity->save( $post_id );

		$school = new School();
		$school->save( $post_id );

		$legal = new Legal();
		$legal->save( $post_id );

		$diverse = new Diverse();
		$diverse->save( $post_id );

		$classification = new Classification();
		$classification->save( $post_id );
	}

	/**
	 * Renders the back link above the form fields.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_back_button( WP_Post $post ): void {
		if ( 'adherent' !== $post->post_type ) {
			return;
		}

		$user_id  = get_current_user_id();
		$list_url = $user_id ? (string) get_user_meta( $user_id, 'dame_last_adherent_list_url', true ) : '';
		if ( empty( $list_url ) ) {
			$list_url = admin_url( 'edit.php?post_type=adherent' );
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
		<div style="margin: 15px 0;">
			<a href="<?php echo esc_url( $list_url ); ?>" class="dame-back-link">
				<span class="dashicons dashicons-arrow-left-alt"></span>
				<span style="line-height: 1;"><?php esc_html_e( 'Retour à la liste filtrée', 'dame' ); ?></span>
			</a>
		</div>
		<?php
	}
}
