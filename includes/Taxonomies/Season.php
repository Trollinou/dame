<?php
/**
 * Season Taxonomy.
 *
 * @package DAME
 */

namespace DAME\Taxonomies;

/**
 * Class Season
 */
class Season {

	/**
	 * Initialize the taxonomy.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register' ], 0 );
	}

	/**
	 * Register the taxonomy.
	 */
	public function register() {
		$labels = array(
			'name'                       => _x( 'Saisons d\'adhésion', 'taxonomy general name', 'dame' ),
			'singular_name'              => _x( 'Saison d\'adhésion', 'taxonomy singular name', 'dame' ),
			'search_items'               => __( 'Rechercher les saisons', 'dame' ),
			'popular_items'              => __( 'Saisons populaires', 'dame' ),
			'all_items'                  => __( 'Toutes les saisons', 'dame' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Modifier la saison', 'dame' ),
			'update_item'                => __( 'Mettre à jour la saison', 'dame' ),
			'add_new_item'               => __( 'Ajouter une nouvelle saison', 'dame' ),
			'new_item_name'              => __( 'Nom de la nouvelle saison', 'dame' ),
			'separate_items_with_commas' => __( 'Séparer les saisons avec des virgules', 'dame' ),
			'add_or_remove_items'        => __( 'Ajouter ou supprimer des saisons', 'dame' ),
			'choose_from_most_used'      => __( 'Choisir parmi les saisons les plus utilisées', 'dame' ),
			'not_found'                  => __( 'Aucune saison trouvée.', 'dame' ),
			'menu_name'                  => __( 'Saisons d\'adhésion', 'dame' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'saison-adhesion' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'dame_saison_adhesion', [ 'adherent' ], $args );
	}
}
