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
 * Add a "Type" field to the "Add New Group" form.
 */
function dame_group_add_form_fields() {
    ?>
    <div class="form-field">
        <label for="term_meta[group_type]"><?php _e( 'Type de groupe', 'dame' ); ?></label>
        <select name="term_meta[group_type]" id="term_meta[group_type]">
            <option value="saisonnier" selected><?php _e( 'Saisonnier', 'dame' ); ?></option>
            <option value="permanent"><?php _e( 'Permanent', 'dame' ); ?></option>
        </select>
        <p class="description"><?php _e( 'Saisonnier : membres actifs. Permanent : contacts extérieurs (bénévoles, élus, presse).', 'dame' ); ?></p>
    </div>
    <?php
}
add_action( 'dame_group_add_form_fields', 'dame_group_add_form_fields', 10, 2 );

/**
 * Add a "Type" field to the "Edit Group" form.
 */
function dame_group_edit_form_fields( $term ) {
    $group_type = get_term_meta( $term->term_id, '_dame_group_type', true );
    if ( empty( $group_type ) ) {
        $group_type = 'saisonnier'; // Default value
    }
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="term_meta[group_type]"><?php _e( 'Type de groupe', 'dame' ); ?></label></th>
        <td>
            <select name="term_meta[group_type]" id="term_meta[group_type]">
                <option value="saisonnier" <?php selected( $group_type, 'saisonnier' ); ?>><?php _e( 'Saisonnier', 'dame' ); ?></option>
                <option value="permanent" <?php selected( $group_type, 'permanent' ); ?>><?php _e( 'Permanent', 'dame' ); ?></option>
            </select>
            <p class="description"><?php _e( 'Saisonnier : membres actifs. Permanent : contacts extérieurs (bénévoles, élus, presse).', 'dame' ); ?></p>
        </td>
    </tr>
    <?php
}
add_action( 'dame_group_edit_form_fields', 'dame_group_edit_form_fields', 10, 2 );

/**
 * Save the "Type" field for the Group taxonomy.
 */
function dame_save_group_type( $term_id ) {
    if ( isset( $_POST['term_meta']['group_type'] ) ) {
        $group_type = sanitize_key( $_POST['term_meta']['group_type'] );
        update_term_meta( $term_id, '_dame_group_type', $group_type );
    }
}
add_action( 'edited_dame_group', 'dame_save_group_type', 10, 2 );
add_action( 'create_dame_group', 'dame_save_group_type', 10, 2 );


/**
 * Add a "Type" column to the group list table.
 */
function dame_add_group_type_column( $columns ) {
    $columns['group_type'] = __( 'Type', 'dame' );
    return $columns;
}
add_filter( 'manage_edit-dame_group_columns', 'dame_add_group_type_column' );

/**
 * Display the content for the "Type" column.
 */
function dame_render_group_type_column( $content, $column_name, $term_id ) {
    if ( 'group_type' === $column_name ) {
        $group_type = get_term_meta( $term_id, '_dame_group_type', true );
        if ( 'permanent' === $group_type ) {
            $content = __( 'Permanent', 'dame' );
        } else {
            $content = __( 'Saisonnier', 'dame' ); // Default
        }
    }
    return $content;
}
add_filter( 'manage_dame_group_custom_column', 'dame_render_group_type_column', 10, 3 );


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


/**
 * Add a "Reset" action to the group taxonomy list table.
 * This action allows removing all adherents from a group.
 *
 * @param array    $actions An array of action links.
 * @param WP_Term  $term    The term object.
 * @return array   The modified array of action links.
 */
function dame_add_group_reset_action( $actions, $term ) {
    // Check if we are on the 'dame_group' taxonomy screen.
    if ( 'dame_group' !== $term->taxonomy ) {
        return $actions;
    }

    // Check if the user has the required capability.
    if ( current_user_can( 'manage_categories' ) ) {
        // Build the URL for the reset action.
        $reset_url = add_query_arg(
            array(
                'action'   => 'dame_reset_group',
                'taxonomy' => 'dame_group',
                'tag_ID'   => $term->term_id,
                '_wpnonce' => wp_create_nonce( 'dame_reset_group_' . $term->term_id ),
            ),
            admin_url( 'admin-post.php' )
        );

        // Add a confirmation dialog.
        $actions['reset'] = sprintf(
            '<a href="%s" onclick="return confirm(\'%s\')">%s</a>',
            esc_url( $reset_url ),
            esc_js( sprintf( __( 'Êtes-vous sûr de vouloir supprimer tous les adhérents du groupe "%s" ? Cette action est irréversible.', 'dame' ), $term->name ) ),
            __( 'Réinitialiser', 'dame' )
        );
    }
    return $actions;
}
add_filter( 'tag_row_actions', 'dame_add_group_reset_action', 10, 2 );

/**
 * Handle the group reset action.
 * This function removes all adherents from a specific group.
 */
function dame_handle_reset_group_action() {
    // Check if the action is correct.
    if ( ! isset( $_GET['action'] ) || 'dame_reset_group' !== $_GET['action'] ) {
        return;
    }

    // Check user capabilities first.
    if ( ! current_user_can( 'manage_categories' ) ) {
        wp_die( __( 'Vous n\'avez pas les permissions suffisantes pour effectuer cette action.', 'dame' ) );
    }

    // Get the term ID and verify the nonce.
    $term_id = isset( $_GET['tag_ID'] ) ? intval( $_GET['tag_ID'] ) : 0;
    if ( ! $term_id || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'dame_reset_group_' . $term_id ) ) {
        wp_die( __( 'Échec de la vérification de sécurité.', 'dame' ) );
    }

    // Get all adherents in the group.
    $adherents = get_posts(
        array(
            'post_type'      => 'adherent',
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'dame_group',
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ),
            ),
            'fields'         => 'ids', // We only need the post IDs.
        )
    );

    // If there are adherents, remove them from the group.
    if ( ! empty( $adherents ) ) {
        foreach ( $adherents as $adherent_id ) {
            wp_remove_object_terms( $adherent_id, $term_id, 'dame_group' );
        }
    }

    // Redirect back to the taxonomy list table with a success message.
    $redirect_url = add_query_arg(
        array(
            'taxonomy' => 'dame_group',
            'message'  => 'group_reset',
        ),
        admin_url( 'edit-tags.php' )
    );
    wp_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_dame_reset_group', 'dame_handle_reset_group_action' );

/**
 * Display an admin notice after a group has been reset.
 */
function dame_show_reset_group_notice() {
    global $pagenow;
    // Check if we are on the correct page and the message is set.
    if (
        'edit-tags.php' === $pagenow &&
        isset( $_GET['taxonomy'] ) && 'dame_group' === $_GET['taxonomy'] &&
        isset( $_GET['message'] ) && 'group_reset' === $_GET['message']
    ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'Le groupe a été réinitialisé avec succès. Tous les adhérents ont été retirés.', 'dame' ); ?></p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'dame_show_reset_group_notice' );
