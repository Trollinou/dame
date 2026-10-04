<?php
/**
 * Agenda Metabox Manager.
 *
 * @package DAME\Metaboxes\Agenda
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Agenda;

use WP_Post;
use DAME\Services\Data_Provider;

/**
 * Class Manager
 * Orchestrator facade for Agenda metaboxes, scripts and recurrence actions.
 */
class Manager {

	/**
	 * Initialize the metaboxes and scripts.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_dame_agenda', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'edit_form_top', array( $this, 'render_back_button' ) );
		add_action( 'admin_post_dame_delete_series_from', array( $this, 'handle_delete_series_from' ) );
		add_action( 'admin_post_dame_delete_entire_series', array( $this, 'handle_delete_entire_series' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/**
	 * Enqueue necessary scripts for autocomplete and geolocation.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( string $hook ): void {
		$screen = get_current_screen();

		if ( ! $screen || 'dame_agenda' !== $screen->post_type ) {
			return;
		}

		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$plugin_url = plugin_dir_url( dirname( __DIR__, 3 ) . '/index.php' );

		wp_register_script(
			'dame-admin-common',
			$plugin_url . 'assets/js/admin-common.js',
			array(),
			DAME_VERSION,
			true
		);

		$options         = get_option( 'dame_options', array() );
		$assoc_latitude  = isset( $options['assoc_latitude'] ) ? $options['assoc_latitude'] : '';
		$assoc_longitude = isset( $options['assoc_longitude'] ) ? $options['assoc_longitude'] : '';

		wp_localize_script(
			'dame-admin-common',
			'dame_admin_data',
			array(
				'assoc_latitude'  => $assoc_latitude,
				'assoc_longitude' => $assoc_longitude,
				'dept_region_map' => Data_Provider::get_department_region_mapping(),
			)
		);

		wp_enqueue_script( 'dame-admin-common' );

		wp_enqueue_style(
			'dame-admin-common-css',
			$plugin_url . 'assets/css/admin-common.css',
			array(),
			DAME_VERSION
		);

		wp_enqueue_script( 'dame-admin-agenda-manager', DAME_PLUGIN_URL . 'assets/js/admin-agenda-manager.js', array(), DAME_VERSION, true );
		wp_localize_script(
			'dame-admin-agenda-manager',
			'dame_agenda_manager_data',
			array(
				'alert_category'         => __( 'Veuillez sélectionner au moins une catégorie.', 'dame' ),
				'alert_competition_type' => __( 'Veuillez sélectionner un type de compétition.', 'dame' ),
			)
		);
	}

	/**
	 * Renders the back link above the form fields.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_back_button( WP_Post $post ): void {
		if ( 'dame_agenda' !== $post->post_type ) {
			return;
		}

		$user_id  = get_current_user_id();
		$list_url = $user_id ? (string) get_user_meta( $user_id, 'dame_last_agenda_list_url', true ) : '';
		if ( empty( $list_url ) ) {
			$list_url = admin_url( 'edit.php?post_type=dame_agenda' );
		} else {
			$list_url = admin_url( ltrim( str_replace( '/wp-admin/', '', $list_url ), '/' ) );
		}
		?>
		<div class="dame-back-link-wrapper">
			<a href="<?php echo esc_url( $list_url ); ?>" class="dame-back-link">
				<span class="dashicons dashicons-arrow-left-alt"></span>
				<span><?php esc_html_e( 'Retour à la liste filtrée', 'dame' ); ?></span>
			</a>
		</div>
		<?php
	}

	/**
	 * Register the metaboxes.
	 */
	public function register_meta_boxes(): void {
		remove_meta_box( 'postcustom', 'dame_agenda', 'normal' );

		add_meta_box(
			'dame_agenda_description_metabox',
			__( 'Description', 'dame' ),
			array( $this, 'render_description' ),
			'dame_agenda',
			'normal',
			'high'
		);
		add_meta_box(
			'dame_agenda_details_metabox',
			__( 'Détails de l\'événement', 'dame' ),
			array( $this, 'render_details' ),
			'dame_agenda',
			'normal',
			'core'
		);
		add_meta_box(
			'dame_agenda_recurrence_metabox',
			__( 'Récurrence & Répétition', 'dame' ),
			array( $this, 'render_recurrence' ),
			'dame_agenda',
			'normal',
			'default'
		);
		add_meta_box(
			'dame_agenda_participants_metabox',
			__( 'Participants', 'dame' ),
			array( $this, 'render_participants' ),
			'dame_agenda',
			'side',
			'high'
		);
	}

	/**
	 * Renders the recurrence meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_recurrence( WP_Post $post ): void {
		( new Recurrence_Metabox() )->render( $post );
	}

	/**
	 * Renders the meta box for the agenda's description.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_description( WP_Post $post ): void {
		( new DescriptionMetabox() )->render( $post );
	}

	/**
	 * Renders the meta box for agenda details.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_details( WP_Post $post ): void {
		( new DetailsMetabox() )->render( $post );
	}

	/**
	 * Renders the meta box for selecting event participants.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_participants( WP_Post $post ): void {
		( new ParticipantsMetabox() )->render( $post );
	}

	/**
	 * Save meta box content for Agenda CPT.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( int $post_id ): void {
		( new SaveHandler() )->save( $post_id );
	}

	/**
	 * Handles deleting an event and subsequent events in a series.
	 */
	public function handle_delete_series_from(): void {
		( new SeriesActions() )->handle_delete_series_from();
	}

	/**
	 * Handles deleting an entire series.
	 */
	public function handle_delete_entire_series(): void {
		( new SeriesActions() )->handle_delete_entire_series();
	}

	/**
	 * Display admin notices for series deletion.
	 */
	public function display_admin_notices(): void {
		( new SeriesActions() )->display_admin_notices();
	}
}
