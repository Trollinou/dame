<?php
/**
 * Contact Custom Post Type.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\CPT;

use DAME\Services\Data_Provider;

/**
 * Class Contact
 */
class Contact {

	/**
	 * Registers the CPT and hooks.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_post_type' ], 0 );

		// Hooks pour la liste d'administration (Colonnes et Filtres)
		if ( is_admin() ) {
			add_filter( 'manage_dame_contact_posts_columns', [ $this, 'add_custom_columns' ] );
			add_action( 'manage_dame_contact_posts_custom_column', [ $this, 'render_custom_columns' ], 10, 2 );
			add_action( 'restrict_manage_posts', [ $this, 'add_custom_filters' ] );
			add_action( 'pre_get_posts', [ $this, 'filter_posts_by_meta' ] );
		}
	}

	/**
	 * Register the custom post type.
	 */
	public function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Contacts', 'Post Type General Name', 'dame' ),
			'singular_name'         => _x( 'Contact', 'Post Type Singular Name', 'dame' ),
			'menu_name'             => __( 'Contacts', 'dame' ),
			'name_admin_bar'        => __( 'Contact', 'dame' ),
			'archives'              => __( 'Archives des contacts', 'dame' ),
			'attributes'            => __( 'Attributs du contact', 'dame' ),
			'parent_item_colon'     => __( 'Contact parent :', 'dame' ),
			'all_items'             => __( 'Tous les contacts', 'dame' ),
			'add_new_item'          => __( 'Ajouter un nouveau contact', 'dame' ),
			'add_new'               => __( 'Ajouter', 'dame' ),
			'new_item'              => __( 'Nouveau contact', 'dame' ),
			'edit_item'             => __( 'Modifier le contact', 'dame' ),
			'update_item'           => __( 'Mettre à jour le contact', 'dame' ),
			'view_item'             => __( 'Voir le contact', 'dame' ),
			'view_items'            => __( 'Voir les contacts', 'dame' ),
			'search_items'          => __( 'Rechercher un contact', 'dame' ),
			'not_found'             => __( 'Non trouvé', 'dame' ),
			'not_found_in_trash'    => __( 'Non trouvé dans la corbeille', 'dame' ),
			'featured_image'        => __( 'Image mise en avant', 'dame' ),
			'set_featured_image'    => __( 'Définir l\'image mise en avant', 'dame' ),
			'remove_featured_image' => __( 'Supprimer l\'image mise en avant', 'dame' ),
			'use_featured_image'    => __( 'Utiliser comme image mise en avant', 'dame' ),
			'insert_into_item'      => __( 'Insérer dans le contact', 'dame' ),
			'uploaded_to_this_item' => __( 'Téléversé sur ce contact', 'dame' ),
			'items_list'            => __( 'Liste des contacts', 'dame' ),
			'items_list_navigation' => __( 'Navigation de la liste des contacts', 'dame' ),
			'filter_items_list'     => __( 'Filtrer la liste des contacts', 'dame' ),
		];

		$args = [
			'label'                 => __( 'Contact', 'dame' ),
			'description'           => __( 'Contacts externes (Presse, Élus, Clubs voisins)', 'dame' ),
			'labels'                => $labels,
			'supports'              => [ 'title' ],
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => 'dame-admin',
			'menu_position'         => 27,
			'menu_icon'             => 'dashicons-networking',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
			'show_in_rest'          => true,
		];

		register_post_type( 'dame_contact', $args );
	}

	/**
	 * Ajoute des colonnes personnalisées à la liste des contacts.
	 *
	 * @param array<string, string> $columns Les colonnes existantes.
	 * @return array<string, string> Les colonnes modifiées.
	 */
	public function add_custom_columns( array $columns ): array {
		$new_columns = [];
		foreach ( $columns as $key => $label ) {
			if ( 'date' === $key ) {
				$new_columns['dame_contact_email']  = __( 'Email', 'dame' );
				$new_columns['dame_contact_phone']  = __( 'Téléphone', 'dame' );
				$new_columns['dame_contact_dept']   = __( 'Département', 'dame' );
				$new_columns['dame_contact_region'] = __( 'Région', 'dame' );
			}
			$new_columns[ $key ] = $label;
		}
		return $new_columns;
	}

	/**
	 * Affiche le contenu des colonnes personnalisées.
	 *
	 * @param string $column  Le slug de la colonne.
	 * @param int    $post_id L'ID du contact.
	 */
	public function render_custom_columns( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'dame_contact_email':
				$email = (string) get_post_meta( $post_id, '_dame_contact_email', true );
				if ( $email ) {
					printf( '<a href="mailto:%1$s">%1$s</a>', esc_html( $email ) );
				}
				break;

			case 'dame_contact_phone':
				echo esc_html( (string) get_post_meta( $post_id, '_dame_contact_phone', true ) );
				break;

			case 'dame_contact_dept':
				$departments = Data_Provider::get_departments();
				$dept_code   = (string) get_post_meta( $post_id, '_dame_contact_department', true );
				echo esc_html( $departments[ $dept_code ] ?? $dept_code );
				break;

			case 'dame_contact_region':
				$regions     = Data_Provider::get_regions();
				$region_code = (string) get_post_meta( $post_id, '_dame_contact_region', true );
				echo esc_html( $regions[ $region_code ] ?? $region_code );
				break;
		}
	}

	/**
	 * Ajoute des listes déroulantes de filtrage dans l'administration.
	 *
	 * @param string $post_type Le type de post actuel.
	 */
	public function add_custom_filters( string $post_type ): void {
		if ( 'dame_contact' !== $post_type ) {
			return;
		}

		// Filtre par Type de contact (Taxonomie)
		$selected_type = isset( $_GET['dame_contact_type'] ) ? sanitize_key( (string) $_GET['dame_contact_type'] ) : '';
		wp_dropdown_categories( [
			'show_option_all' => __( 'Tous les types', 'dame' ),
			'taxonomy'        => 'dame_contact_type',
			'name'            => 'dame_contact_type',
			'orderby'         => 'name',
			'selected'        => $selected_type,
			'hierarchical'    => true,
			'depth'           => 3,
			'show_count'      => false,
			'hide_empty'      => false,
			'value_field'     => 'slug',
		] );

		// Filtre par Département (Meta)
		$departments   = Data_Provider::get_departments();
		$selected_dept = isset( $_GET['dame_filter_dept'] ) ? sanitize_text_field( (string) $_GET['dame_filter_dept'] ) : '';
		echo '<select name="dame_filter_dept">';
		echo '<option value="">' . esc_html__( 'Tous les départements', 'dame' ) . '</option>';
		foreach ( $departments as $code => $name ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( (string) $code ),
				selected( $selected_dept, $code, false ),
				esc_html( $name )
			);
		}
		echo '</select>';

		// Filtre par Région (Meta)
		$regions         = Data_Provider::get_regions();
		$selected_region = isset( $_GET['dame_filter_region'] ) ? sanitize_text_field( (string) $_GET['dame_filter_region'] ) : '';
		echo '<select name="dame_filter_region">';
		echo '<option value="">' . esc_html__( 'Toutes les régions', 'dame' ) . '</option>';
		foreach ( $regions as $code => $name ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( (string) $code ),
				selected( $selected_region, $code, false ),
				esc_html( $name )
			);
		}
		echo '</select>';
	}

	/**
	 * Modifie la requête principale pour appliquer les filtres meta.
	 *
	 * @param \WP_Query $query L'objet WP_Query.
	 */
	public function filter_posts_by_meta( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || 'dame_contact' !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = (array) $query->get( 'meta_query' );

		// Filtrage Département
		if ( ! empty( $_GET['dame_filter_dept'] ) ) {
			$meta_query[] = [
				'key'     => '_dame_contact_department',
				'value'   => sanitize_text_field( (string) $_GET['dame_filter_dept'] ),
				'compare' => 'LIKE',
			];
		}

		// Filtrage Région
		if ( ! empty( $_GET['dame_filter_region'] ) ) {
			$meta_query[] = [
				'key'     => '_dame_contact_region',
				'value'   => sanitize_text_field( (string) $_GET['dame_filter_region'] ),
				'compare' => '=',
			];
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}
	}
}
