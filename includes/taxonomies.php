<?php
/**
 * File for registering custom taxonomies.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register Membership Season Taxonomy for Adherents.
 */
function dame_register_membership_season_taxonomy() {
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
        'show_admin_column' => false, // We will handle this column manually.
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'saison-adhesion' ),
        'show_in_rest'      => true,
    );

    register_taxonomy( 'dame_saison_adhesion', 'adherent', $args );
}
add_action( 'init', 'dame_register_membership_season_taxonomy', 0 );

/**
 * Register Group Taxonomy for Adherents.
 */
function dame_register_group_taxonomy() {
    $labels = array(
        'name'                       => _x( 'Groupes', 'taxonomy general name', 'dame' ),
        'singular_name'              => _x( 'Groupe', 'taxonomy singular name', 'dame' ),
        'search_items'               => __( 'Rechercher les groupes', 'dame' ),
        'popular_items'              => __( 'Groupes populaires', 'dame' ),
        'all_items'                  => __( 'Tous les groupes', 'dame' ),
        'parent_item'                => __( 'Groupe parent', 'dame' ),
        'parent_item_colon'          => __( 'Groupe parent :', 'dame' ),
        'edit_item'                  => __( 'Modifier le groupe', 'dame' ),
        'update_item'                => __( 'Mettre à jour le groupe', 'dame' ),
        'add_new_item'               => __( 'Ajouter un nouveau groupe', 'dame' ),
        'new_item_name'              => __( 'Nom du nouveau groupe', 'dame' ),
        'separate_items_with_commas' => __( 'Séparer les groupes avec des virgules', 'dame' ),
        'add_or_remove_items'        => __( 'Ajouter ou supprimer des groupes', 'dame' ),
        'choose_from_most_used'      => __( 'Choisir parmi les groupes les plus utilisés', 'dame' ),
        'not_found'                  => __( 'Aucun groupe trouvé.', 'dame' ),
        'menu_name'                  => __( 'Groupes', 'dame' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'groupe' ),
        'show_in_rest'      => true,
    );

    register_taxonomy( 'dame_group', 'adherent', $args );
}
add_action( 'init', 'dame_register_group_taxonomy', 0 );

/**
 * Register Agenda Category Taxonomy
 */
function dame_register_agenda_category_taxonomy() {
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
add_action( 'init', 'dame_register_agenda_category_taxonomy', 0 );

/**
 * Enqueue the color picker script for the agenda category taxonomy.
 */
function dame_enqueue_color_picker( $hook ) {
    if ( 'term.php' !== $hook && 'edit-tags.php' !== $hook ) {
        return;
    }
    // Ensure we are on the correct taxonomy page
    if ( isset($_GET['taxonomy']) && 'dame_agenda_category' === $_GET['taxonomy'] ) {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'dame-color-picker-js', plugin_dir_url( __FILE__ ) . '../admin/js/main.js', array( 'wp-color-picker' ), false, true );
    }
}
add_action( 'admin_enqueue_scripts', 'dame_enqueue_color_picker' );

/**
 * Add color picker to the "Add New Category" form for agenda categories.
 */
function dame_agenda_category_add_form_fields() {
    ?>
    <div class="form-field">
        <label for="term_meta[color]"><?php _e( 'Couleur de la catégorie', 'dame' ); ?></label>
        <input type="text" name="term_meta[color]" id="term_meta[color]" value="" class="dame-color-picker">
        <p class="description"><?php _e( 'Choisissez une couleur pour cette catégorie.', 'dame' ); ?></p>
    </div>
    <?php
}
add_action( 'dame_agenda_category_add_form_fields', 'dame_agenda_category_add_form_fields', 10, 2 );

/**
 * Add color picker to the "Edit Category" form for agenda categories.
 */
function dame_agenda_category_edit_form_fields( $term ) {
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
add_action( 'dame_agenda_category_edit_form_fields', 'dame_agenda_category_edit_form_fields', 10, 2 );

/**
 * Save custom taxonomy meta fields.
 */
function dame_save_agenda_category_color( $term_id ) {
    if ( isset( $_POST['term_meta'] ) ) {
        $term_meta = get_option( "taxonomy_$term_id" );
        if ( ! is_array( $term_meta ) ) {
            $term_meta = array();
        }
        $cat_keys = array_keys( $_POST['term_meta'] );
        foreach ( $cat_keys as $key ) {
            if ( isset( $_POST['term_meta'][$key] ) ) {
                $term_meta[$key] = sanitize_hex_color( $_POST['term_meta'][$key] );
            }
        }
        update_option( "taxonomy_$term_id", $term_meta );
    }
}
add_action( 'edited_dame_agenda_category', 'dame_save_agenda_category_color', 10, 2 );
add_action( 'create_dame_agenda_category', 'dame_save_agenda_category_color', 10, 2 );

/**
 * Add a "Color" column to the agenda category list table.
 */
function dame_add_agenda_category_color_column( $columns ) {
    $columns['color'] = __( 'Couleur', 'dame' );
    return $columns;
}
add_filter( 'manage_edit-dame_agenda_category_columns', 'dame_add_agenda_category_color_column' );

/**
 * Display the color in the "Color" column.
 */
function dame_add_agenda_category_color_column_content( $content, $column_name, $term_id ) {
    if ( 'color' === $column_name ) {
        $term_meta = get_option( "taxonomy_$term_id" );
        if ( isset( $term_meta['color'] ) && ! empty( $term_meta['color'] ) ) {
            $content .= '<div style="width: 40px; height: 20px; background-color:' . esc_attr( $term_meta['color'] ) . '; border: 1px solid #ccc;"></div>';
        }
    }
    return $content;
}
add_filter( 'manage_dame_agenda_category_custom_column', 'dame_add_agenda_category_color_column_content', 10, 3 );
