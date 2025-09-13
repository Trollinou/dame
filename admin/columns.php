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
        'dame_category'   => __( 'Catégorie', 'dame' ),
        'dame_location'   => __( 'Lieu', 'dame' ),
        'dame_start_date' => __( 'Date de début', 'dame' ),
        'dame_end_date'   => __( 'Date de fin', 'dame' ),
        'date'            => $columns['date'],
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
    }
}
add_action( 'manage_dame_agenda_posts_custom_column', 'dame_render_agenda_columns', 10, 2 );
