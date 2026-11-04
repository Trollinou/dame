<?php
/**
 * Custom Post Type: Benevolat
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\CPT;

/**
 * Class Benevolat
 */
class Benevolat {

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'wp_trash_post', array( $this, 'handle_trash' ) );
		add_action( 'untrash_post', array( $this, 'handle_untrash' ) );
		add_action( 'deleted_post', array( $this, 'handle_deletion' ) );
		add_filter( 'the_content', array( $this, 'display_benevolat_form' ) );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_block_editor' ), 10, 2 );
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'save_last_list_url' ) );
		}
	}

	/**
	 * Disables the block editor for this CPT.
	 *
	 * @param bool   $use_block_editor Whether to use the block editor.
	 * @param string $post_type        The post type.
	 * @return bool
	 */
	public function disable_block_editor( bool $use_block_editor, string $post_type ): bool {
		if ( 'benevolat' === $post_type ) {
			return false;
		}
		return $use_block_editor;
	}

	/**
	 * Handle post status change to trash.
	 *
	 * @param int $post_id Post ID.
	 */
	public function handle_trash( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post && 'benevolat' === $post->post_type ) {
			( new \DAME\Services\BenevolatArticleSync() )->trash_linked_article( $post_id );
		}
	}

	/**
	 * Handle post restoration from trash.
	 *
	 * @param int $post_id Post ID.
	 */
	public function handle_untrash( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post && 'benevolat' === $post->post_type ) {
			( new \DAME\Services\BenevolatArticleSync() )->untrash_linked_article( $post_id );
		}
	}

	/**
	 * Handle post deletion.
	 *
	 * @param int $post_id Post ID.
	 */
	public function handle_deletion( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		if ( 'benevolat_reponse' === $post->post_type ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'dame_benevolat_votes';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table cleanup on CPT deletion.
			$wpdb->delete( $table_name, array( 'recipient_id' => $post_id ), array( '%d' ) );
		} elseif ( 'benevolat' === $post->post_type ) {
			( new \DAME\Services\BenevolatArticleSync() )->delete_linked_article( $post_id );
		}
	}

	/**
	 * Automatically display benevolat form on single benevolat post view.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function display_benevolat_form( string $content ): string {
		if ( is_singular( 'benevolat' ) && in_the_loop() && is_main_query() ) {
			$post_id = get_the_ID();
			if ( ! $post_id ) {
				return $content;
			}

			$slug      = (string) get_post_field( 'post_name', $post_id );
			$shortcode = new \DAME\Shortcodes\Benevolat();
			$form_html = $shortcode->render( array( 'slug' => $slug ) );

			return $content . "\n\n" . $form_html;
		}

		return $content;
	}

	/**
	 * Register the custom post types.
	 */
	public function register(): void {
		$benevolat_labels = array(
			'name'               => _x( 'Bénévolat', 'post type general name', 'dame' ),
			'singular_name'      => _x( 'Bénévolat', 'post type singular name', 'dame' ),
			'menu_name'          => _x( 'Bénévolat', 'admin menu', 'dame' ),
			'name_admin_bar'     => _x( 'Bénévolat', 'add new on admin bar', 'dame' ),
			'add_new'            => _x( 'Nouvel appel', 'benevolat', 'dame' ),
			'add_new_item'       => __( 'Nouvel appel à bénévoles', 'dame' ),
			'new_item'           => __( 'Nouveau bénévolat', 'dame' ),
			'edit_item'          => __( 'Modifier l\'appel', 'dame' ),
			'view_item'          => __( 'Voir l\'appel', 'dame' ),
			'all_items'          => __( 'Appels à bénévoles', 'dame' ),
			'search_items'       => __( 'Rechercher des appels', 'dame' ),
			'parent_item_colon'  => __( 'Appels parents:', 'dame' ),
			'not_found'          => __( 'Aucun appel trouvé.', 'dame' ),
			'not_found_in_trash' => __( 'Aucun appel trouvé dans la corbeille.', 'dame' ),
		);

		$benevolat_args = array(
			'labels'             => $benevolat_labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'dame-admin',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'benevolat' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-chart-bar',
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'       => true,
			'rest_base'          => 'benevolats',
		);

		register_post_type( 'benevolat', $benevolat_args );

		$reponse_labels = array(
			'name'          => _x( 'Réponses au bénévolat', 'post type general name', 'dame' ),
			'singular_name' => _x( 'Réponse de bénévolat', 'post type singular name', 'dame' ),
		);

		$reponse_args = array(
			'labels'             => $reponse_labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'benevolat_reponse' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array( 'title', 'author' ),
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => false,
			'show_in_rest'       => true,
			'rest_base'          => 'benevolat-reponses',
		);

		register_post_type( 'benevolat_reponse', $reponse_args );
	}

	/**
	 * Saves the last benevolat list URL with its query parameters.
	 */
	public function save_last_list_url(): void {
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list view page navigation tracking.
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		if ( 'edit.php' === $pagenow && 'benevolat' === $post_type ) {
			$user_id = get_current_user_id();
			if ( $user_id ) {
				$url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				if ( $url ) {
					$parsed     = wp_parse_url( $url );
					$path_query = ( $parsed['path'] ?? '' ) . ( isset( $parsed['query'] ) ? '?' . $parsed['query'] : '' );
					update_user_meta( $user_id, 'dame_last_benevolat_list_url', $path_query );
				}
			}
		}
	}
}
