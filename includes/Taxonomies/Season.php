<?php
/**
 * Season Taxonomy.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Taxonomies;

/**
 * Class Season
 */
class Season {

	/**
	 * Initialize the taxonomy.
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ), 0 );

		// Custom Term Fields.
		add_action( 'dame_saison_adhesion_add_form_fields', array( $this, 'add_form_fields' ), 10, 1 );
		add_action( 'dame_saison_adhesion_edit_form_fields', array( $this, 'edit_form_fields' ), 10, 2 );
		add_action( 'created_dame_saison_adhesion', array( $this, 'save_term_pricing' ), 10, 1 );
		add_action( 'edited_dame_saison_adhesion', array( $this, 'save_term_pricing' ), 10, 1 );

		// Columns.
		add_filter( 'manage_edit-dame_saison_adhesion_columns', array( $this, 'add_pricing_columns' ) );
		add_filter( 'manage_dame_saison_adhesion_custom_column', array( $this, 'render_pricing_column' ), 10, 3 );
	}

	/**
	 * Register the taxonomy.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'                       => _x( 'Saisons d\'adhésion', 'taxonomy general name', 'dame' ),
			'singular_name'              => _x( 'Saison d\'adhésion', 'taxonomy singular name', 'dame' ),
			'search_items'               => __( 'Rechercher les saisons', 'dame' ),
			'popular_items'              => __( 'Saisons populaires', 'dame' ),
			'all_items'                  => __( 'Toutes les saisons', 'dame' ),
			'parent_item'                => '',
			'parent_item_colon'          => '',
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
			'show_in_menu'      => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'saison-adhesion' ),
			'show_in_rest'      => true,
			'rest_base'         => 'seasons',
		);

		register_taxonomy( 'dame_saison_adhesion', array( 'adherent' ), $args );
	}

	/**
	 * Add pricing fields to the "Add New Season" form.
	 *
	 * @param string $taxonomy The taxonomy slug.
	 */
	public function add_form_fields( string $taxonomy ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$default = \DAME\Services\Data_Provider::DEFAULT_PRICING;
		?>
		<div class="form-field">
			<label for="dame_price_licence_a"><?php esc_html_e( 'Tarif de base Licence A (€)', 'dame' ); ?></label>
			<input type="number" step="0.5" min="0" name="dame_price_licence_a" id="dame_price_licence_a" value="<?php echo esc_attr( (string) $default['price_licence_a'] ); ?>" />
			<p class="description"><?php esc_html_e( 'Cours + Compétition (adulte homme en renouvellement).', 'dame' ); ?></p>
		</div>

		<div class="form-field">
			<label for="dame_price_licence_b"><?php esc_html_e( 'Tarif de base Licence B (€)', 'dame' ); ?></label>
			<input type="number" step="0.5" min="0" name="dame_price_licence_b" id="dame_price_licence_b" value="<?php echo esc_attr( (string) $default['price_licence_b'] ); ?>" />
			<p class="description"><?php esc_html_e( 'Jeu libre (tarif fixe).', 'dame' ); ?></p>
		</div>

		<div class="form-field">
			<label for="dame_discount_female_a"><?php esc_html_e( 'Remise féminine sur Licence A (€)', 'dame' ); ?></label>
			<input type="number" step="0.5" min="0" name="dame_discount_female_a" id="dame_discount_female_a" value="<?php echo esc_attr( (string) $default['discount_female_a'] ); ?>" />
			<p class="description"><?php esc_html_e( 'Déduit de la cotisation Licence A pour les féminines.', 'dame' ); ?></p>
		</div>

		<div class="form-field">
			<label for="dame_surcharge_first_reg_a"><?php esc_html_e( 'Surcoût 1ère adhésion sur Licence A (€)', 'dame' ); ?></label>
			<input type="number" step="0.5" min="0" name="dame_surcharge_first_reg_a" id="dame_surcharge_first_reg_a" value="<?php echo esc_attr( (string) $default['surcharge_first_reg_a'] ); ?>" />
			<p class="description"><?php esc_html_e( 'Ajouté à la Licence A pour financer le polo floqué (1ère adhésion).', 'dame' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add pricing fields to the "Edit Season" form.
	 *
	 * @param \WP_Term $term     The term object.
	 * @param string   $taxonomy The taxonomy slug.
	 */
	public function edit_form_fields( \WP_Term $term, string $taxonomy ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$pricing = \DAME\Services\Data_Provider::get_season_pricing( $term->term_id );
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="dame_price_licence_a"><?php esc_html_e( 'Tarif de base Licence A (€)', 'dame' ); ?></label>
			</th>
			<td>
				<input type="number" step="0.5" min="0" name="dame_price_licence_a" id="dame_price_licence_a" value="<?php echo esc_attr( (string) $pricing['price_licence_a'] ); ?>" style="max-width: 150px;" />
				<p class="description"><?php esc_html_e( 'Cours + Compétition (adulte homme en renouvellement).', 'dame' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="dame_price_licence_b"><?php esc_html_e( 'Tarif de base Licence B (€)', 'dame' ); ?></label>
			</th>
			<td>
				<input type="number" step="0.5" min="0" name="dame_price_licence_b" id="dame_price_licence_b" value="<?php echo esc_attr( (string) $pricing['price_licence_b'] ); ?>" style="max-width: 150px;" />
				<p class="description"><?php esc_html_e( 'Jeu libre (tarif fixe).', 'dame' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="dame_discount_female_a"><?php esc_html_e( 'Remise féminine sur Licence A (€)', 'dame' ); ?></label>
			</th>
			<td>
				<input type="number" step="0.5" min="0" name="dame_discount_female_a" id="dame_discount_female_a" value="<?php echo esc_attr( (string) $pricing['discount_female_a'] ); ?>" style="max-width: 150px;" />
				<p class="description"><?php esc_html_e( 'Déduit de la cotisation Licence A pour les féminines.', 'dame' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="dame_surcharge_first_reg_a"><?php esc_html_e( 'Surcoût 1ère adhésion sur Licence A (€)', 'dame' ); ?></label>
			</th>
			<td>
				<input type="number" step="0.5" min="0" name="dame_surcharge_first_reg_a" id="dame_surcharge_first_reg_a" value="<?php echo esc_attr( (string) $pricing['surcharge_first_reg_a'] ); ?>" style="max-width: 150px;" />
				<p class="description"><?php esc_html_e( 'Ajouté à la Licence A pour financer le polo floqué (1ère adhésion).', 'dame' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Saves pricing configuration when a season term is created or edited.
	 *
	 * @param int $term_id The term ID.
	 */
	public function save_term_pricing( int $term_id ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['dame_price_licence_a'] ) || isset( $_POST['dame_price_licence_b'] ) ) {
			$pricing = array(
				'price_licence_a'       => isset( $_POST['dame_price_licence_a'] ) ? (float) $_POST['dame_price_licence_a'] : 140.0,
				'price_licence_b'       => isset( $_POST['dame_price_licence_b'] ) ? (float) $_POST['dame_price_licence_b'] : 70.0,
				'discount_female_a'     => isset( $_POST['dame_discount_female_a'] ) ? (float) $_POST['dame_discount_female_a'] : 10.0,
				'surcharge_first_reg_a' => isset( $_POST['dame_surcharge_first_reg_a'] ) ? (float) $_POST['dame_surcharge_first_reg_a'] : 30.0,
			);
			\DAME\Services\Data_Provider::save_season_pricing( $term_id, $pricing );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Add pricing summary columns to the Seasons list table.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function add_pricing_columns( array $columns ): array {
		$new_columns = array();
		foreach ( $columns as $key => $val ) {
			$new_columns[ $key ] = $val;
			if ( 'name' === $key ) {
				$new_columns['dame_season_pricing_summary'] = __( 'Tarifs Cotisation', 'dame' );
			}
		}
		if ( ! isset( $new_columns['dame_season_pricing_summary'] ) ) {
			$new_columns['dame_season_pricing_summary'] = __( 'Tarifs Cotisation', 'dame' );
		}
		return $new_columns;
	}

	/**
	 * Renders the pricing summary column in the Seasons list table.
	 *
	 * @param string $content     Current column output.
	 * @param string $column_name Column name.
	 * @param int    $term_id     Term ID.
	 * @return string
	 */
	public function render_pricing_column( string $content, string $column_name, int $term_id ): string {
		if ( 'dame_season_pricing_summary' === $column_name ) {
			$pricing = \DAME\Services\Data_Provider::get_season_pricing( $term_id );
			return sprintf(
				'<strong>A :</strong> %s € (remise F : -%s €, polo : +%s €)<br><strong>B :</strong> %s €',
				number_format( $pricing['price_licence_a'], 0, ',', ' ' ),
				number_format( $pricing['discount_female_a'], 0, ',', ' ' ),
				number_format( $pricing['surcharge_first_reg_a'], 0, ',', ' ' ),
				number_format( $pricing['price_licence_b'], 0, ',', ' ' )
			);
		}
		return $content;
	}
}

