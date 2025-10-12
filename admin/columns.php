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
        'dame_age_category'    => __( 'Catégorie d\'âge', 'dame' ),
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
        case 'dame_age_category':
            $birth_date = get_post_meta( $post_id, '_dame_birth_date', true );
            $gender = get_post_meta( $post_id, '_dame_sexe', true );
            echo esc_html( dame_get_adherent_age_category( $birth_date, $gender ) );
            break;

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
            $groups = get_the_terms( $post_id, 'dame_group' );
            if ( ! empty( $groups ) && ! is_wp_error( $groups ) ) {
                $group_names = wp_list_pluck( $groups, 'name' );
                echo esc_html( implode( ', ', $group_names ) );
            } else {
                echo '—';
            }
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
        // Group filter for the 'dame_group' taxonomy.
        $group_terms = get_terms(
            array(
                'taxonomy'   => 'dame_group',
                'hide_empty' => true, // Only show groups that are in use
                'orderby'    => 'name',
                'order'      => 'ASC',
            )
        );
        if ( ! is_wp_error( $group_terms ) && ! empty( $group_terms ) ) {
            $current_group = $_GET['dame_group_filter'] ?? '';
            ?>
            <select name="dame_group_filter">
                <option value=""><?php _e( 'Tous les groupes', 'dame' ); ?></option>
                <?php foreach ( $group_terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $term->term_id, $current_group ); ?>><?php echo esc_html( $term->name ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        }

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

        // Group filter.
        if ( isset( $_GET['dame_group_filter'] ) && ! empty( $_GET['dame_group_filter'] ) ) {
            $group_id = absint( $_GET['dame_group_filter'] );
            if ( $group_id > 0 ) {
                $tax_query[] = array(
                    'taxonomy' => 'dame_group',
                    'field'    => 'term_id',
                    'terms'    => $group_id,
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
 * Adds custom filters to the Agenda CPT admin list for category and date range.
 */
function dame_add_agenda_filters() {
    global $typenow, $wp_locale;

    if ( 'dame_agenda' !== $typenow ) {
        return;
    }

    // --- Category Filter (existing) ---
    $taxonomy   = 'dame_agenda_category';
    $selected_category = $_GET[ $taxonomy ] ?? '';
    $category_terms    = get_terms(
        array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    if ( $category_terms && ! is_wp_error( $category_terms ) ) {
        echo '<select name="' . esc_attr( $taxonomy ) . '" id="' . esc_attr( $taxonomy ) . '" class="postform">';
        echo '<option value="">' . esc_html__( 'Toutes les catégories', 'dame' ) . '</option>';
        foreach ( $category_terms as $term ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $term->slug ),
                selected( $selected_category, $term->slug, false ),
                esc_html( $term->name )
            );
        }
        echo '</select>';
    }

    // --- New Date Range Filter ---
    global $wpdb;

    // Get all distinct years from event start dates
    $years = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT YEAR(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = %s ORDER BY meta_value DESC", '_dame_start_date' ) );

    // Get min and max dates for defaults
    $min_date = $wpdb->get_var( $wpdb->prepare( "SELECT MIN(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = %s", '_dame_start_date' ) );
    $max_date = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = %s", '_dame_start_date' ) );

    $default_start_month = $min_date ? date( 'm', strtotime( $min_date ) ) : date( 'm' );
    $default_start_year  = $min_date ? date( 'Y', strtotime( $min_date ) ) : date( 'Y' );
    $default_end_month   = $max_date ? date( 'm', strtotime( $max_date ) ) : date( 'm' );
    $default_end_year    = $max_date ? date( 'Y', strtotime( $max_date ) ) : date( 'Y' );

    // Get user's saved preference for start date
    $user_id = get_current_user_id();
    $saved_start_date = get_user_meta( $user_id, 'dame_agenda_filter_start_date', true );
    if ( ! empty( $saved_start_date ) && is_array( $saved_start_date ) ) {
        $default_start_month = $saved_start_date['month'];
        $default_start_year  = $saved_start_date['year'];
    }

    // Determine current values from GET or defaults
    $start_month = $_GET['dame_start_month'] ?? $default_start_month;
    $start_year  = $_GET['dame_start_year'] ?? $default_start_year;
    $end_month   = $_GET['dame_end_month'] ?? $default_end_month;
    $end_year    = $_GET['dame_end_year'] ?? $default_end_year;

    $months = array();
    for ( $i = 1; $i <= 12; $i++ ) {
        $months[ sprintf( '%02d', $i ) ] = $wp_locale->get_month( $i );
    }

    // Render the dropdowns.
    ?>
    <label for="dame_start_month" class="screen-reader-text"><?php _e( 'Mois de début', 'dame' ); ?></label>
    <select name="dame_start_month" id="dame_start_month">
        <?php foreach ( $months as $month_val => $month_name ) : ?>
            <option value="<?php echo esc_attr( $month_val ); ?>" <?php selected( $start_month, $month_val ); ?>><?php echo esc_html( $month_name ); ?></option>
        <?php endforeach; ?>
    </select>
    <label for="dame_start_year" class="screen-reader-text"><?php _e( 'Année de début', 'dame' ); ?></label>
    <select name="dame_start_year" id="dame_start_year">
        <?php foreach ( $years as $year ) : ?>
            <option value="<?php echo esc_attr( $year ); ?>" <?php selected( $start_year, $year ); ?>><?php echo esc_html( $year ); ?></option>
        <?php endforeach; ?>
    </select>
    <label for="dame_end_month" class="screen-reader-text"><?php _e( 'Mois de fin', 'dame' ); ?></label>
    <select name="dame_end_month" id="dame_end_month">
        <?php foreach ( $months as $month_val => $month_name ) : ?>
            <option value="<?php echo esc_attr( $month_val ); ?>" <?php selected( $end_month, $month_val ); ?>><?php echo esc_html( $month_name ); ?></option>
        <?php endforeach; ?>
    </select>
    <label for="dame_end_year" class="screen-reader-text"><?php _e( 'Année de fin', 'dame' ); ?></label>
    <select name="dame_end_year" id="dame_end_year">
        <?php foreach ( $years as $year ) : ?>
            <option value="<?php echo esc_attr( $year ); ?>" <?php selected( $end_year, $year ); ?>><?php echo esc_html( $year ); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
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
        if (empty($tax_query)) {
            $tax_query = array('relation' => 'AND');
        }
        $tax_query[] = array(
            'taxonomy' => 'dame_agenda_category',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( wp_unslash( $_GET['dame_agenda_category'] ) ),
        );
        $query->set( 'tax_query', $tax_query );
    }

    // --- Handle New Date Range Filter ---
    $start_month = $_GET['dame_start_month'] ?? '';
    $start_year  = $_GET['dame_start_year'] ?? '';
    $end_month   = $_GET['dame_end_month'] ?? '';
    $end_year    = $_GET['dame_end_year'] ?? '';

    // Only filter if at least one parameter is present (the form is always submitted)
    if ( ! empty( $start_month ) && ! empty( $start_year ) && ! empty( $end_month ) && ! empty( $end_year ) ) {

        // Persist the user's choice for the start date if filter is actively used.
        // We check for 'filter_action' which is the name of the filter button.
        if ( isset( $_GET['filter_action'] ) ) {
            $user_id = get_current_user_id();
            update_user_meta( $user_id, 'dame_agenda_filter_start_date', array( 'month' => $start_month, 'year' => $start_year ) );
        }

        $start_date_str = $start_year . '-' . $start_month . '-01';
        $end_date_str   = $end_year . '-' . $end_month . '-01';

        // Ensure start date is not after end date
        if ( strtotime( $start_date_str ) > strtotime( $end_date_str ) ) {
            return; // Or swap them, for now just ignore invalid range
        }

        $first_day = date( 'Y-m-d', strtotime( $start_date_str ) );
        $last_day  = date( 'Y-m-t', strtotime( $end_date_str ) );

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
    $keys_to_skip = array( '_dame_start_date', '_dame_end_date' );

    if ( ! empty( $all_meta ) ) {
        foreach ( $all_meta as $meta_key => $meta_values ) {
            // Skip protected meta, but allow our own '_dame_' meta.
            if ( is_protected_meta( $meta_key ) && strpos( $meta_key, '_dame_' ) !== 0 ) {
                continue;
            }
            // Skip the date fields as requested by the user for a better workflow.
            if ( in_array( $meta_key, $keys_to_skip, true ) ) {
                continue;
            }

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
 * Suppresses the default months dropdown for the Agenda CPT.
 *
 * @param array $months The default months list.
 * @return array The modified months list (empty for our CPT).
 */
function dame_suppress_months_dropdown( $months, $post_type ) {
    if ( 'dame_agenda' === $post_type ) {
        return array();
    }
    return $months;
}
add_filter( 'months_dropdown_results', 'dame_suppress_months_dropdown', 10, 2 );

/**
 * Adds a 'Consulter' action link to the adherent list table.
 *
 * @param array   $actions The existing row actions.
 * @param WP_Post $post    The post object.
 * @return array The modified row actions.
 */
function dame_add_adherent_row_actions( $actions, $post ) {
    if ( 'adherent' === $post->post_type ) {
        // CPT is not public, so the default 'View' link is not needed/broken.
        unset( $actions['view'] );

        $url = add_query_arg(
            array(
                'page'        => 'dame-view-adherent',
                'adherent_id' => $post->ID,
            ),
            admin_url( 'admin.php' )
        );

        $view_link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), __( 'Consulter', 'dame' ) );

        // Add the 'Consulter' link before the 'Edit' link.
        return array_merge( array( 'dame_view' => $view_link ), $actions );
    }
    return $actions;
}
add_filter( 'post_row_actions', 'dame_add_adherent_row_actions', 10, 2 );
