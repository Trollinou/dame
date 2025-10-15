<?php
/**
 * Common metabox functions for DAME.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Display admin notices for our CPT.
 */
function dame_display_admin_notices() {
	if ( get_transient( 'dame_success_message' ) ) {
		$message = get_transient( 'dame_success_message' );
		delete_transient( 'dame_success_message' );
		echo '<div class="updated"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	if ( get_transient( 'dame_error_message' ) ) {
		$message = get_transient( 'dame_error_message' );
		delete_transient( 'dame_error_message' );
		echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	if ( isset( $_GET['message'] ) && '101' === $_GET['message'] ) {
		$screen = get_current_screen();
		if ( $screen && 'edit-dame_pre_inscription' === $screen->id ) {
			echo '<div class="updated"><p>' . esc_html__( 'La préinscription a été supprimée.', 'dame' ) . '</p></div>';
		}
	}
}
add_action( 'admin_notices', 'dame_display_admin_notices' );

/**
 * Enqueues admin scripts for the plugin.
 *
 * @param string $hook The current admin page.
 */
function dame_enqueue_admin_scripts( $hook ) {
	global $post;
	if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && isset( $post->post_type ) && in_array( $post->post_type, array( 'adherent', 'dame_pre_inscription', 'dame_agenda' ), true ) ) {
		wp_enqueue_script(
			'dame-main-js',
			plugin_dir_url( __FILE__ ) . '../js/main.js',
			array( 'jquery' ),
			DAME_VERSION,
			true
		);
		wp_enqueue_script(
			'dame-geo-autocomplete-js',
			plugin_dir_url( __FILE__ ) . '../js/geo-autocomplete.js',
			array(),
			DAME_VERSION,
			true
		);
		$options = get_option( 'dame_options' );
		wp_localize_script(
			'dame-main-js',
			'dame_admin_data',
			array(
				'department_region_mapping' => dame_get_department_region_mapping(),
				'assoc_latitude'            => isset( $options['assoc_latitude'] ) ? $options['assoc_latitude'] : '',
				'assoc_longitude'           => isset( $options['assoc_longitude'] ) ? $options['assoc_longitude'] : '',
			)
		);
		wp_enqueue_script(
			'dame-autocomplete-js',
			plugin_dir_url( __FILE__ ) . '../js/ign-autocomplete.js',
			array('dame-main-js'),
			DAME_VERSION,
			true
		);
	} elseif ( 'adherent_page_dame-mailing' === $hook ) {
		// Ensure main.js is enqueued for the mailing page as well
		wp_enqueue_script(
			'dame-main-js',
			plugin_dir_url( __FILE__ ) . '../js/main.js',
			array(),
			DAME_VERSION,
			true
		);

		// Localize data for the mailing page filter
		wp_localize_script(
			'dame-main-js',
			'dame_mailing_data',
			array(
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'dame_filter_articles_nonce' ),
				'no_articles_found' => __( "Aucun article ne correspond aux filtres sélectionnés.", 'dame' ),
				'generic_error'     => __( "Une erreur est survenue lors de la récupération des articles.", 'dame' ),
			)
		);
	} elseif ( 'settings_page_dame-settings' === $hook ) {
		wp_enqueue_script(
			'dame-autocomplete-js',
			plugin_dir_url( __FILE__ ) . '../js/ign-autocomplete.js',
			array(),
			DAME_VERSION,
			true
		);
	}
    if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && isset( $post->post_type ) && in_array( $post->post_type, array( 'dame_pre_inscription' ), true ) ) {
        wp_enqueue_style(
            'dame-admin-styles',
            plugin_dir_url( __FILE__ ) . '../css/admin-styles.css',
            array(),
            DAME_VERSION
        );
    }

}
add_action( 'admin_enqueue_scripts', 'dame_enqueue_admin_scripts' );

/**
 * Adds custom CSS to the admin head for the suggestion box.
 */
function dame_add_admin_styles() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array( 'adherent', 'dame_pre_inscription' ), true ) ) {
		return;
	}
	?>
	<style>
		.dame-autocomplete-wrapper {
			position: relative;
		}
		.dame-address-suggestions {
			border: 1px solid #999;
			border-top: none;
			max-height: 150px; /* Show approx 4 lines */
			overflow-y: auto;
			background-color: #fff;
			position: absolute;
			width: 100%;
			z-index: 9999; /* High z-index to appear above other elements */
			box-shadow: 0 3px 5px rgba(0,0,0,0.2);
		}
		.dame-suggestion-item {
			padding: 8px;
			cursor: pointer;
		}
		.dame-suggestion-item:hover {
			background-color: #f1f1f1;
		}
		#dame_birth_date, #dame_membership_date {
			width: 8em;
		}
		.dame-inline-fields {
			display: flex;
			gap: 1em;
		}
		.dame-inline-fields .postal-code {
			width: 8em;
			flex-shrink: 0;
		}
		.dame-inline-fields .city {
			width: 16em;
			flex-shrink: 0;
		}
	</style>
	<?php
}
add_action( 'admin_head-post.php', 'dame_add_admin_styles' );
add_action( 'admin_head-post-new.php', 'dame_add_admin_styles' );
