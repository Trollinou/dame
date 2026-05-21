<?php
/**
 * Custom Post Type: Benevolat
 *
 * @package DAME
 */

namespace DAME\CPT;

/**
 * Class Benevolat
 */
class Benevolat {

	/**
	 * Initialize the CPT hooks.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'wp_trash_post', [ $this, 'handle_status_change' ] );
		add_action( 'untrash_post', [ $this, 'handle_status_change' ] );
		add_action( 'deleted_post', [ $this, 'handle_deletion' ] );
		add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_block_editor' ], 10, 2 );
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
	 * Handle post status changes (trash/untrash).
	 */
	public function handle_deletion( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post || 'benevolat_reponse' !== $post->post_type ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_benevolat_votes';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $table_name, [ 'recipient_id' => $post_id ], [ '%d' ] );
	}

	/**
	 * Simplified status change handler.
	 */
	public function handle_status_change( int $post_id ): void {
		// For now, we only care about permanent deletion to avoid ID collisions.
	}

	/**
	 * Register the custom post types.
	 */
	public function register(): void {
		$benevolat_labels = [
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
		];

		$benevolat_args = [
			'labels'             => $benevolat_labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'dame-admin',
			'query_var'          => true,
			'rewrite'            => [ 'slug' => 'benevolat' ],
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-chart-bar',
			'supports'           => [ 'title', 'editor' ],
			'show_in_rest'       => true,
			'rest_base'          => 'benevolats',
		];

		register_post_type( 'benevolat', $benevolat_args );

		$reponse_labels = [
			'name'          => _x( 'Réponses au bénévolat', 'post type general name', 'dame' ),
			'singular_name' => _x( 'Réponse de bénévolat', 'post type singular name', 'dame' ),
		];

		$reponse_args = [
			'labels'              => $reponse_labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'query_var'           => true,
			'rewrite'             => [ 'slug' => 'benevolat_reponse' ],
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => [ 'title', 'author' ],
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => true,
			'rest_base'           => 'benevolat-reponses',
		];

		register_post_type( 'benevolat_reponse', $reponse_args );
	}
}
