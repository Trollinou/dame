<?php
/**
 * File for customizing the admin columns for the Adherent CPT.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Sets the custom columns for the Adherent CPT.
 *
 * @param array $columns The existing columns.
 * @return array The modified columns.
 */
function dame_set_adherent_columns( $columns ) {
    $new_columns = array(
        'cb'                   => $columns['cb'],
        'title'                => __( 'Nom de l\'adhérent', 'dame' ),
        'dame_membership_status' => __( 'Statut Adhésion', 'dame' ),
        'dame_saisons'         => __( 'Saisons d\'adhésion', 'dame' ),
        'dame_license_number'  => __( 'Licence', 'dame' ),
        'dame_email'           => __( 'Email', 'dame' ),
        'dame_phone'           => __( 'Téléphone', 'dame' ),
        'dame_classification'  => __( 'Classification', 'dame' ),
    );
    return $new_columns;
}
add_filter( 'manage_edit-adherent_columns', 'dame_set_adherent_columns' );

/**
 * Renders the content for the custom columns.
 *
 * @param string $column The name of the column to render.
 * @param int    $post_id The ID of the post.
 */
function dame_render_adherent_columns( $column, $post_id ) {
    switch ( $column ) {
        case 'dame_license_number':
            $license = get_post_meta( $post_id, '_dame_license_number', true );
            echo esc_html( $license );
            break;

        case 'dame_email':
            $email = get_post_meta( $post_id, '_dame_email', true );
            echo esc_html( $email );
            break;

        case 'dame_phone':
            $phone = get_post_meta( $post_id, '_dame_phone_number', true );
            echo esc_html( $phone );
            break;

        case 'dame_classification':
            $classifications = array();
            if ( get_post_meta( $post_id, '_dame_is_junior', true ) ) {
                $classifications[] = __( 'École d\'échecs', 'dame' );
            }
            if ( get_post_meta( $post_id, '_dame_is_pole_excellence', true ) ) {
                $classifications[] = __( 'Pôle Excellence', 'dame' );
            }
            echo esc_html( implode( ', ', $classifications ) );
            break;

        case 'dame_membership_status':
            $current_season_tag_id = get_option( 'dame_current_season_tag_id' );
            if ( $current_season_tag_id && has_term( (int) $current_season_tag_id, 'dame_saison_adhesion', $post_id ) ) {
                echo '<span style="color: green; font-weight: bold;">' . esc_html__( 'Actif', 'dame' ) . '</span>';
            } else {
                echo esc_html__( 'Non adhérent', 'dame' );
            }
            break;

        case 'dame_saisons':
            $saisons = get_the_terms( $post_id, 'dame_saison_adhesion' );
            if ( ! empty( $saisons ) && ! is_wp_error( $saisons ) ) {
                $saison_names = array();
                foreach ( $saisons as $saison ) {
                    $saison_names[] = '<span style="display: inline-block; background-color: #e0e0e0; color: #333; padding: 2px 8px; margin: 2px; border-radius: 4px; font-size: 0.9em;">' . esc_html( $saison->name ) . '</span>';
                }
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo implode( ' ', $saison_names );
            } else {
                echo '—';
            }
            break;
    }
}
add_action( 'manage_adherent_posts_custom_column', 'dame_render_adherent_columns', 10, 2 );


/**
 * Adds custom filters to the Adherent CPT admin list.
 */
function dame_add_adherent_filters() {
    global $typenow;

    if ( 'adherent' === $typenow ) {
        // Group filter (unchanged).
        $current_group = $_GET['dame_group_filter'] ?? '';
        ?>
        <select name="dame_group_filter">
            <option value=""><?php _e( 'Tous les groupes', 'dame' ); ?></option>
            <option value="juniors" <?php selected( 'juniors', $current_group ); ?>><?php _e( 'École d\'échecs', 'dame' ); ?></option>
            <option value="pole_excellence" <?php selected( 'pole_excellence', $current_group ); ?>><?php _e( 'Pôle Excellence', 'dame' ); ?></option>
            <option value="benevole" <?php selected( 'benevole', $current_group ); ?>><?php _e( 'Bénévole', 'dame' ); ?></option>
            <option value="elu_local" <?php selected( 'elu_local', $current_group ); ?>><?php _e( 'Elu local', 'dame' ); ?></option>
        </select>
        <?php

        // New Membership Status filter.
        $current_status = $_GET['dame_membership_filter'] ?? '';
        ?>
        <select name="dame_membership_filter">
            <option value=""><?php _e( 'Tous les statuts d\'adhésion', 'dame' ); ?></option>
            <option value="active" <?php selected( 'active', $current_status ); ?>><?php _e( 'Adhésion active', 'dame' ); ?></option>
            <option value="inactive" <?php selected( 'inactive', $current_status ); ?>><?php _e( 'Adhésion inactive', 'dame' ); ?></option>
        </select>
        <?php

        // New Season filter.
        $saisons = get_terms(
            array(
                'taxonomy'   => 'dame_saison_adhesion',
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'DESC',
            )
        );
        if ( ! is_wp_error( $saisons ) && ! empty( $saisons ) ) {
            $current_saison = $_GET['dame_saison_filter'] ?? '';
            ?>
            <select name="dame_saison_filter">
                <option value=""><?php _e( 'Toutes les saisons', 'dame' ); ?></option>
                <?php foreach ( $saisons as $saison ) : ?>
                    <option value="<?php echo esc_attr( $saison->term_id ); ?>" <?php selected( $saison->term_id, $current_saison ); ?>><?php echo esc_html( $saison->name ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        }
    }
}
add_action( 'restrict_manage_posts', 'dame_add_adherent_filters' );


/**
 * Filters the Adherent CPT query based on custom filters.
 *
 * @param WP_Query $query The WP_Query instance.
 */
function dame_filter_adherent_query( $query ) {
    global $pagenow;
    $post_type = $_GET['post_type'] ?? '';

    if ( is_admin() && 'edit.php' === $pagenow && 'adherent' === $post_type && $query->is_main_query() ) {
        $meta_query = $query->get( 'meta_query' ) ?: array();
        $tax_query  = $query->get( 'tax_query' ) ?: array();

        // Group filter (unchanged).
        if ( isset( $_GET['dame_group_filter'] ) && '' !== $_GET['dame_group_filter'] ) {
            $group     = sanitize_key( $_GET['dame_group_filter'] );
            $group_key = '';

            switch ( $group ) {
                case 'juniors':
                    $group_key = '_dame_is_junior';
                    break;
                case 'pole_excellence':
                    $group_key = '_dame_is_pole_excellence';
                    break;
                case 'benevole':
                    $group_key = '_dame_is_benevole';
                    break;
                case 'elu_local':
                    $group_key = '_dame_is_elu_local';
                    break;
            }

            if ( ! empty( $group_key ) ) {
                $meta_query[] = array(
                    'key'   => $group_key,
                    'value' => '1',
                );
            }
        }

        // New Membership Status filter.
        if ( isset( $_GET['dame_membership_filter'] ) && ! empty( $_GET['dame_membership_filter'] ) ) {
            $status_filter         = sanitize_key( $_GET['dame_membership_filter'] );
            $current_season_tag_id = get_option( 'dame_current_season_tag_id' );

            if ( $current_season_tag_id ) {
                if ( 'active' === $status_filter ) {
                    $tax_query[] = array(
                        'taxonomy' => 'dame_saison_adhesion',
                        'field'    => 'term_id',
                        'terms'    => (int) $current_season_tag_id,
                    );
                } elseif ( 'inactive' === $status_filter ) {
                    $tax_query[] = array(
                        'taxonomy' => 'dame_saison_adhesion',
                        'field'    => 'term_id',
                        'terms'    => (int) $current_season_tag_id,
                        'operator' => 'NOT IN',
                    );
                }
            }
        }

        // New Season filter.
        if ( isset( $_GET['dame_saison_filter'] ) && ! empty( $_GET['dame_saison_filter'] ) ) {
            $saison_id = absint( $_GET['dame_saison_filter'] );
            if ( $saison_id > 0 ) {
                $tax_query[] = array(
                    'taxonomy' => 'dame_saison_adhesion',
                    'field'    => 'term_id',
                    'terms'    => $saison_id,
                );
            }
        }

        if ( count( $meta_query ) > 0 ) {
            if ( ! isset( $meta_query['relation'] ) ) {
                $meta_query['relation'] = 'AND';
            }
            $query->set( 'meta_query', $meta_query );
        }

        if ( count( $tax_query ) > 0 ) {
            if ( ! isset( $tax_query['relation'] ) ) {
                $tax_query['relation'] = 'AND';
            }
            $query->set( 'tax_query', $tax_query );
        }
    }
}
add_action( 'pre_get_posts', 'dame_filter_adherent_query' );

/**
 * Adds a custom column to the user list table.
 *
 * @param array $columns The existing user list columns.
 * @return array The modified user list columns.
 */
function dame_add_user_lesson_completion_column( $columns ) {
    if ( current_user_can( 'entraineur' ) || current_user_can( 'administrator' ) ) {
        $columns['dame_completed_lessons'] = __( 'Leçons Terminées', 'dame' );
    }
    return $columns;
}
add_filter( 'manage_users_columns', 'dame_add_user_lesson_completion_column' );

/**
 * Renders the content for the custom user list column.
 *
 * @param string $value       The column's current value.
 * @param string $column_name The name of the column.
 * @param int    $user_id     The ID of the user.
 * @return string The column's new value.
 */
function dame_render_user_lesson_completion_column( $value, $column_name, $user_id ) {
    if ( 'dame_completed_lessons' === $column_name && ( current_user_can( 'entraineur' ) || current_user_can( 'administrator' ) ) ) {
        $completed_lessons = get_user_meta( $user_id, 'dame_completed_lessons', true );
        if ( is_array( $completed_lessons ) ) {
            return count( $completed_lessons );
        }
        return 0;
    }
    return $value;
}
add_filter( 'manage_users_custom_column', 'dame_render_user_lesson_completion_column', 10, 3 );


/**
 * Gets a color based on difficulty level.
 *
 * @param int $level The difficulty level (1-6).
 * @return string The hex color code.
 */
function dame_get_difficulty_color( $level ) {
    $colors = [
        1 => '#4CAF50', // Green
        2 => '#8BC34A', // Light Green
        3 => '#FFC107', // Amber
        4 => '#FF9800', // Orange
        5 => '#F44336', // Red
        6 => '#B71C1C', // Dark Red
    ];
    return $colors[ (int) $level ] ?? '#9E9E9E'; // Grey for default
}

/**
 * Adds a 'Difficulté' column to CPT admin lists.
 *
 * @param array $columns The existing columns.
 * @return array The modified columns.
 */
function dame_add_difficulty_column( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $title ) {
        $new_columns[ $key ] = $title;
        if ( 'title' === $key ) {
            $new_columns['dame_difficulty'] = __( 'Difficulté', 'dame' );
        }
    }
    return $new_columns;
}

/**
 * Renders the content for the 'Difficulté' column.
 *
 * @param string $column  The name of the column to render.
 * @param int    $post_id The ID of the post.
 */
function dame_render_difficulty_column( $column, $post_id ) {
    if ( 'dame_difficulty' === $column ) {
        $difficulty = get_post_meta( $post_id, '_dame_difficulty', true );
        if ( $difficulty ) {
            $color = dame_get_difficulty_color( $difficulty );
            echo '<span class="dashicons dashicons-star-filled" style="color:' . esc_attr( $color ) . ';"></span> ' . esc_html( $difficulty );
        } else {
            echo '—';
        }
    }
}

// Apply to all relevant CPTs
$cpts = ['dame_lecon', 'dame_exercice', 'dame_cours'];
foreach ( $cpts as $cpt ) {
    add_filter( "manage_edit-{$cpt}_columns", 'dame_add_difficulty_column' );
    add_action( "manage_{$cpt}_posts_custom_column", 'dame_render_difficulty_column', 10, 2 );
}

/**
 * Sets the custom columns for the Agenda CPT.
 *
 * @param array $columns The existing columns.
 * @return array The modified columns.
 */
function dame_set_agenda_columns( $columns ) {
    $new_columns = array(
        'cb'              => $columns['cb'],
        'title'           => __( 'Titre', 'dame' ),
		'dame_start_date' => __( 'Date de début', 'dame' ),
		'dame_end_date'   => __( 'Date de fin', 'dame' ),
		'dame_location'   => __( 'Lieu', 'dame' ),
		'dame_category'   => __( 'Catégorie', 'dame' ),
		'dame_actions'    => __( 'Actions', 'dame' ),
    );
    return $new_columns;
}
add_filter( 'manage_edit-dame_agenda_columns', 'dame_set_agenda_columns' );

/**
 * Renders the content for the custom Agenda columns.
 *
 * @param string $column The name of the column to render.
 * @param int    $post_id The ID of the post.
 */
function dame_render_agenda_columns( $column, $post_id ) {
    switch ( $column ) {
        case 'dame_start_date':
            $start_date = get_post_meta( $post_id, '_dame_start_date', true );
            $start_time = get_post_meta( $post_id, '_dame_start_time', true );
            $all_day    = get_post_meta( $post_id, '_dame_all_day', true );

            if ( $start_date ) {
                $timestamp = strtotime( $start_date );
                echo esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) );
                if ( ! $all_day && $start_time ) {
                    echo '<br>' . esc_html( $start_time );
                }
            } else {
                echo '—';
            }
            break;

        case 'dame_end_date':
            $end_date = get_post_meta( $post_id, '_dame_end_date', true );
            $end_time = get_post_meta( $post_id, '_dame_end_time', true );
            $all_day  = get_post_meta( $post_id, '_dame_all_day', true );

            if ( $end_date ) {
                $timestamp = strtotime( $end_date );
                echo esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) );
                if ( ! $all_day && $end_time ) {
                    echo '<br>' . esc_html( $end_time );
                }
            } else {
                echo '—';
            }
            break;

        case 'dame_location':
            $location = get_post_meta( $post_id, '_dame_location_name', true );
            echo esc_html( $location ? $location : '—' );
            break;

        case 'dame_category':
            $categories = get_the_terms( $post_id, 'dame_agenda_category' );
            if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
                $category_links = array();
                foreach ( $categories as $category ) {
                    $term_meta = get_option( "taxonomy_{$category->term_id}" );
                    $color     = isset( $term_meta['color'] ) && ! empty( $term_meta['color'] ) ? $term_meta['color'] : '#e0e0e0';

                    $category_links[] = sprintf(
                        '<span style="display: inline-block; background-color: %s; color: %s; padding: 2px 8px; margin: 2px; border-radius: 4px; font-size: 0.9em;">%s</span>',
                        esc_attr( $color ),
                        esc_attr( dame_get_contrast_color( $color ) ),
                        esc_html( $category->name )
                    );
                }
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo implode( ' ', $category_links );
            } else {
                echo '—';
            }
            break;
		case 'dame_actions':
			$duplicate_url = wp_nonce_url(
				admin_url( 'admin.php?action=dame_duplicate_event&post=' . $post_id ),
				'dame_duplicate_event_nonce_' . $post_id,
				'dame_duplicate_nonce'
			);
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<a href="' . esc_url( $duplicate_url ) . '">' . esc_html__( 'Dupliquer', 'dame' ) . '</a>';
			break;
    }
}
add_action( 'manage_dame_agenda_posts_custom_column', 'dame_render_agenda_columns', 10, 2 );

/**
 * Makes the custom date columns sortable for the Agenda CPT.
 *
 * @param array $columns The existing sortable columns.
 * @return array The modified sortable columns.
 */
function dame_set_agenda_sortable_columns( $columns ) {
    $columns['dame_start_date'] = '_dame_start_date';
    $columns['dame_end_date']   = '_dame_end_date';
    return $columns;
}
add_filter( 'manage_edit-dame_agenda_sortable_columns', 'dame_set_agenda_sortable_columns' );

/**
 * Adds custom filters to the Agenda CPT admin list.
 */
function dame_add_agenda_filters() {
    global $typenow;

    if ( 'dame_agenda' === $typenow ) {
        $taxonomy = 'dame_agenda_category';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $selected = $_GET[ $taxonomy ] ?? '';
        $terms    = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            )
        );

        if ( $terms && ! is_wp_error( $terms ) ) {
            echo '<select name="' . esc_attr( $taxonomy ) . '" id="' . esc_attr( $taxonomy ) . '" class="postform">';
            echo '<option value="">' . esc_html__( 'Toutes les catégories', 'dame' ) . '</option>';
            foreach ( $terms as $term ) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr( $term->slug ),
                    selected( $selected, $term->slug, false ),
                    esc_html( $term->name )
                );
            }
            echo '</select>';
        }
    }
}
add_action( 'restrict_manage_posts', 'dame_add_agenda_filters' );

/**
 * Handles sorting and filtering for the Agenda CPT list.
 *
 * @param WP_Query $query The main WP_Query instance.
 */
function dame_filter_and_sort_agenda_query( $query ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! is_admin() || ! $query->is_main_query() || ! isset( $_GET['post_type'] ) || 'dame_agenda' !== $_GET['post_type'] ) {
        return;
    }

    // Handle sorting.
    $orderby = $query->get( 'orderby' );
    if ( '_dame_start_date' === $orderby ) {
        $query->set( 'meta_key', '_dame_start_date' );
        $query->set( 'orderby', 'meta_value' );
        $query->set( 'meta_type', 'DATE' );
    } elseif ( '_dame_end_date' === $orderby ) {
        $query->set( 'meta_key', '_dame_end_date' );
        $query->set( 'orderby', 'meta_value' );
        $query->set( 'meta_type', 'DATE' );
    }

    // Handle category filter.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( isset( $_GET['dame_agenda_category'] ) && '' !== $_GET['dame_agenda_category'] ) {
        $tax_query = $query->get( 'tax_query' ) ?: array();
        $tax_query[] = array(
            'taxonomy' => 'dame_agenda_category',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( wp_unslash( $_GET['dame_agenda_category'] ) ),
        );
        $query->set( 'tax_query', $tax_query );
    }

    // Handle date filter to use start date instead of publication date.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( isset( $_GET['m'] ) && '' !== $_GET['m'] ) {
        $year  = substr( $_GET['m'], 0, 4 );
        $month = substr( $_GET['m'], 4, 2 );

        if ( $year && $month ) {
			$first_day = $year . '-' . $month . '-01';
			$last_day  = date( 'Y-m-t', strtotime( $first_day ) );

            $meta_query = $query->get( 'meta_query' ) ?: array();
            if ( empty( $meta_query ) ) {
                $meta_query = array( 'relation' => 'AND' );
            } elseif ( ! isset( $meta_query['relation'] ) ) {
                $meta_query['relation'] = 'AND';
            }

            $meta_query[] = array(
                'key'     => '_dame_start_date',
                'value'   => array( $first_day, $last_day ),
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            );
            $query->set( 'meta_query', $meta_query );

            // Unset the default date query vars.
            $query->set( 'year', '' );
            $query->set( 'monthnum', '' );
            $query->set( 'day', '' );
            $query->set( 'm', '' );
        }
    }
}
add_action( 'pre_get_posts', 'dame_filter_and_sort_agenda_query' );

/**
 * Handles the event duplication action.
 */
function dame_duplicate_event_action() {
    if ( ! isset( $_GET['post'] ) || ! isset( $_GET['dame_duplicate_nonce'] ) ) {
        wp_die( esc_html__( 'Argument manquant.', 'dame' ) );
    }

    $post_id = absint( $_GET['post'] );
    $nonce   = sanitize_key( $_GET['dame_duplicate_nonce'] );

    if ( ! wp_verify_nonce( $nonce, 'dame_duplicate_event_nonce_' . $post_id ) ) {
        wp_die( esc_html__( 'La vérification de sécurité a échoué.', 'dame' ) );
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( esc_html__( 'Vous n\'avez pas la permission de dupliquer cet événement.', 'dame' ) );
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        wp_die( esc_html__( 'Événement non trouvé.', 'dame' ) );
    }

    $new_post_author = wp_get_current_user();

    $new_post_args = array(
        'post_author'    => $new_post_author->ID,
        'post_content'   => $post->post_content,
        'post_status'    => 'draft', // Set new post to draft
        'post_title'     => $post->post_title . ' (Copie)',
        'post_type'      => $post->post_type,
    );

    // Temporarily remove the save hook to prevent it from firing with empty $_POST data.
    remove_action( 'save_post_dame_agenda', 'dame_save_agenda_meta', 10 );

    $new_post_id = wp_insert_post( $new_post_args );

    // Re-add the hook so it works on subsequent saves from the edit screen.
    add_action( 'save_post_dame_agenda', 'dame_save_agenda_meta', 10, 1 );

    if ( is_wp_error( $new_post_id ) ) {
        wp_die( $new_post_id->get_error_message() );
    }

    // Duplicate post meta.
    $all_meta = get_post_meta( $post_id );
    if ( ! empty( $all_meta ) ) {
        foreach ( $all_meta as $meta_key => $meta_values ) {
            // Diagnostic: Temporarily removing the is_protected_meta check to debug.
            // if ( is_protected_meta( $meta_key ) ) {
            //  continue;
            // }
            foreach ( $meta_values as $meta_value ) {
                add_post_meta( $new_post_id, $meta_key, $meta_value );
            }
        }
    }

    // Duplicate taxonomies.
    $taxonomies = get_object_taxonomies( $post->post_type );
    if ( ! empty( $taxonomies ) ) {
        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                wp_set_object_terms( $new_post_id, $terms, $taxonomy, false );
            }
        }
    }

    // Redirect to the edit screen for the new draft.
    $redirect_url = get_edit_post_link( $new_post_id, 'raw' );
    wp_redirect( $redirect_url );
    exit;
}
add_action( 'admin_action_dame_duplicate_event', 'dame_duplicate_event_action' );

/**
 * Populates the months dropdown filter for the Agenda CPT based on event start dates.
 *
 * @global wpdb $wpdb
 * @param object $months The default months list.
 * @return object The modified months list.
 */
function dame_agenda_months_dropdown_results( $months ) {
    global $wpdb, $typenow;

    if ( 'dame_agenda' === $typenow ) {
        $months = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT DISTINCT YEAR( pm.meta_value ) AS year, MONTH( pm.meta_value ) AS month
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_key = %s
                AND p.post_type = %s
                AND pm.meta_value IS NOT NULL
                AND pm.meta_value != ''
                ORDER BY pm.meta_value DESC
                ",
                '_dame_start_date',
                'dame_agenda'
            )
        );
    }

    return $months;
}
add_filter( 'months_dropdown_results', 'dame_agenda_months_dropdown_results', 10, 1 );
