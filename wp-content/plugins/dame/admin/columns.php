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
        'cb' => $columns['cb'],
        'title' => __( 'Nom de l\'adhérent', 'dame' ),
        'dame_license_number' => __( 'Licence', 'dame' ),
        'dame_email' => __( 'Email', 'dame' ),
        'dame_phone' => __( 'Téléphone', 'dame' ),
        'dame_classification' => __( 'Classification', 'dame' ),
        'dame_membership_status' => __( 'Statut Adhésion', 'dame' ),
        'dame_membership_date' => __( 'Date d\'adhésion', 'dame' ),
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

        case 'dame_membership_date':
            $date = get_post_meta( $post_id, '_dame_membership_date', true );
            echo esc_html( $date );
            break;

        case 'dame_classification':
            $classifications = [];
            if ( get_post_meta( $post_id, '_dame_is_junior', true ) ) {
            $classifications[] = __( 'École d\'échecs', 'dame' );
            }
            if ( get_post_meta( $post_id, '_dame_is_pole_excellence', true ) ) {
                $classifications[] = __( 'Pôle Excellence', 'dame' );
            }
            echo esc_html( implode( ', ', $classifications ) );
            break;

        case 'dame_membership_status':
            $status_key = get_post_meta( $post_id, '_dame_membership_status', true );
            $status_options = [
                'N' => __( 'Non Adhérent (N)', 'dame' ),
                'A' => __( 'Actif (A)', 'dame' ),
                'E' => __( 'Expiré (E)', 'dame' ),
                'X' => __( 'Ancien (X)', 'dame' ),
            ];
            echo esc_html( $status_options[ $status_key ] ?? '' );
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
        // Group filter
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

        // Status filter
        $current_status = $_GET['dame_status_filter'] ?? '';
        $status_options = [
            'N' => __( 'Non Adhérent (N)', 'dame' ),
            'A' => __( 'Actif (A)', 'dame' ),
            'E' => __( 'Expiré (E)', 'dame' ),
            'X' => __( 'Ancien (X)', 'dame' ),
        ];
        ?>
        <select name="dame_status_filter">
            <option value=""><?php _e( 'Tous les statuts', 'dame' ); ?></option>
            <?php foreach ( $status_options as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $current_status ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
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

        // Group filter
        if ( isset( $_GET['dame_group_filter'] ) && '' !== $_GET['dame_group_filter'] ) {
            $group = sanitize_key( $_GET['dame_group_filter'] );
            $group_key = '';

            switch($group) {
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
                    'key' => $group_key,
                    'value' => '1',
                );
            }
        }

        // Status filter
        if ( isset( $_GET['dame_status_filter'] ) && '' !== $_GET['dame_status_filter'] ) {
            $status = sanitize_key( $_GET['dame_status_filter'] );
            $meta_query[] = array(
                'key' => '_dame_membership_status',
                'value' => $status,
            );
        }

        if ( count( $meta_query ) > 0 ) {
            if( ! isset( $meta_query['relation'] ) ) {
                $meta_query['relation'] = 'AND';
            }
            $query->set( 'meta_query', $meta_query );
        }
    }
}
add_action( 'pre_get_posts', 'dame_filter_adherent_query' );
