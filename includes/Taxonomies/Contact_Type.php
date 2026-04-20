<?php
/**
 * Contact Type Taxonomy.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Taxonomies;

/**
 * Class Contact_Type
 */
class Contact_Type {

	/**
	 * Initialize the taxonomy.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register' ], 0 );
	}

	/**
	 * Register the taxonomy.
	 */
	public function register(): void {
		$labels = [
			'name'                       => _x( 'Types de Contact', 'taxonomy general name', 'dame' ),
			'singular_name'              => _x( 'Type de Contact', 'taxonomy singular name', 'dame' ),
			'search_items'               => __( 'Rechercher les types', 'dame' ),
			'popular_items'              => __( 'Types populaires', 'dame' ),
			'all_items'                  => __( 'Tous les types', 'dame' ),
			'parent_item'                => __( 'Type parent', 'dame' ),
			'parent_item_colon'          => __( 'Type parent :', 'dame' ),
			'edit_item'                  => __( 'Modifier le type', 'dame' ),
			'update_item'                => __( 'Mettre à jour le type', 'dame' ),
			'add_new_item'               => __( 'Ajouter un type', 'dame' ),
			'new_item_name'              => __( 'Nom du nouveau type', 'dame' ),
			'separate_items_with_commas' => __( 'Séparer les types avec des virgules', 'dame' ),
			'add_or_remove_items'        => __( 'Ajouter ou supprimer des types', 'dame' ),
			'choose_from_most_used'      => __( 'Choisir parmi les types les plus utilisés', 'dame' ),
			'not_found'                  => __( 'Aucun type trouvé.', 'dame' ),
			'menu_name'                  => __( 'Types de Contact', 'dame' ),
		];

		$args = [
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'type-contact' ],
			'show_in_rest'      => true,
			'public'            => true,
		];

		register_taxonomy( 'dame_contact_type', [ 'dame_contact' ], $args );
	}
}
