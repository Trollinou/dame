<?php
/**
 * Agenda Category Taxonomy Class.
 *
 * @package DAME\Taxonomies
 */

namespace DAME\Taxonomies;

/**
 * Class AgendaCategory
 * Handles registration and management of the 'dame_agenda_category' taxonomy, including color picker.
 */
class AgendaCategory {

	/**
	 * Initialize the taxonomy.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_color_picker' ] );
		add_action( 'dame_agenda_category_add_form_fields', [ $this, 'add_form_fields' ], 10, 2 );
		add_action( 'dame_agenda_category_edit_form_fields', [ $this, 'edit_form_fields' ], 10, 2 );
		add_action( 'edited_dame_agenda_category', [ $this, 'save_color' ], 10, 2 );
		add_action( 'create_dame_agenda_category', [ $this, 'save_color' ], 10, 2 );
		add_filter( 'manage_edit-dame_agenda_category_columns', [ $this, 'add_color_column' ] );
		add_filter( 'manage_dame_agenda_category_custom_column', [ $this, 'render_color_column' ], 10, 3 );
	}

	/**
	 * Register Agenda Category Taxonomy
	 */
	public function register() {
		$labels = array(
			'name'              => _x( 'Catégories d\'événements', 'taxonomy general name', 'dame' ),
			'singular_name'     => _x( 'Catégorie d\'événement', 'taxonomy singular name', 'dame' ),
			'search_items'      => __( 'Rechercher les catégories', 'dame' ),
			'all_items'         => __( 'Toutes les catégories', 'dame' ),
			'parent_item'       => __( 'Catégorie parente', 'dame' ),
			'parent_item_colon' => __( 'Catégorie parente :', 'dame' ),
			'edit_item'         => __( 'Modifier la catégorie', 'dame' ),
			'update_item'       => __( 'Mettre à jour la catégorie', 'dame' ),
			'add_new_item'      => __( 'Ajouter une nouvelle catégorie', 'dame' ),
			'new_item_name'     => __( 'Nom de la nouvelle catégorie', 'dame' ),
			'menu_name'         => __( 'Catégories d\'événement', 'dame' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'agenda-category' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'dame_agenda_category', array( 'dame_agenda' ), $args );
	}

	/**
	 * Enqueue the color picker script for the agenda category taxonomy.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_color_picker( $hook ) {
		if ( 'term.php' !== $hook && 'edit-tags.php' !== $hook ) {
			return;
		}
		// Ensure we are on the correct taxonomy page
		if ( isset( $_GET['taxonomy'] ) && 'dame_agenda_category' === $_GET['taxonomy'] ) {
			wp_enqueue_style( 'wp-color-picker' );
			// Note: Assuming 'admin/js/main.js' or similar exists for the JS logic of initialization.
			// The legacy code used `plugin_dir_url( __FILE__ ) . '../admin/js/main.js'`, which resolved to `dame/admin/js/main.js`.
			// Since this file is in `includes/Taxonomies/`, `dirname(__DIR__, 2)` points to root.
			wp_enqueue_script( 'dame-color-picker-js', plugin_dir_url( dirname( __DIR__, 2 ) . '/index.php' ) . 'admin/js/main.js', array( 'wp-color-picker' ), false, true );
		}
	}

	/**
	 * Add color picker to the "Add New Category" form.
	 */
	public function add_form_fields() {
		?>
		<div class="form-field">
			<label for="term_meta[color]"><?php _e( 'Couleur de la catégorie', 'dame' ); ?></label>
			<input type="text" name="term_meta[color]" id="term_meta[color]" value="" class="dame-color-picker">
			<p class="description"><?php _e( 'Choisissez une couleur pour cette catégorie.', 'dame' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add color picker to the "Edit Category" form.
	 *
	 * @param WP_Term $term Current term object.
	 */
	public function edit_form_fields( $term ) {
		$term_id = $term->term_id;
		$term_meta = get_option( "taxonomy_$term_id" );
		$color = isset( $term_meta['color'] ) ? $term_meta['color'] : '';
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="term_meta[color]"><?php _e( 'Couleur de la catégorie', 'dame' ); ?></label></th>
			<td>
				<input type="text" name="term_meta[color]" id="term_meta[color]" value="<?php echo esc_attr( $color ); ?>" class="dame-color-picker">
				<p class="description"><?php _e( 'Choisissez une couleur pour cette catégorie.', 'dame' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save custom taxonomy meta fields.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save_color( $term_id ) {
		if ( isset( $_POST['term_meta'] ) ) {
			$term_meta = get_option( "taxonomy_$term_id" );
			if ( ! is_array( $term_meta ) ) {
				$term_meta = array();
			}
			$cat_keys = array_keys( $_POST['term_meta'] );
			foreach ( $cat_keys as $key ) {
				if ( isset( $_POST['term_meta'][ $key ] ) ) {
					$term_meta[ $key ] = sanitize_hex_color( $_POST['term_meta'][ $key ] );
				}
			}
			update_option( "taxonomy_$term_id", $term_meta );
		}
	}

	/**
	 * Add a "Color" column to the agenda category list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_color_column( $columns ) {
		$columns['color'] = __( 'Couleur', 'dame' );
		return $columns;
	}

	/**
	 * Display the color in the "Color" column.
	 *
	 * @param string $content Column content.
	 * @param string $column_name Column name.
	 * @param int    $term_id Term ID.
	 * @return string Modified content.
	 */
	public function render_color_column( $content, $column_name, $term_id ) {
		if ( 'color' === $column_name ) {
			$term_meta = get_option( "taxonomy_$term_id" );
			if ( isset( $term_meta['color'] ) && ! empty( $term_meta['color'] ) ) {
				$content .= '<div style="width: 40px; height: 20px; background-color:' . esc_attr( $term_meta['color'] ) . '; border: 1px solid #ccc;"></div>';
			}
		}
		return $content;
	}
}
