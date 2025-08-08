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
                $classifications[] = __( 'Junior', 'dame' );
            }
            if ( get_post_meta( $post_id, '_dame_is_pole_excellence', true ) ) {
                $classifications[] = __( 'Pôle Excellence', 'dame' );
            }
            echo esc_html( implode( ', ', $classifications ) );
            break;
    }
}
add_action( 'manage_adherent_posts_custom_column', 'dame_render_adherent_columns', 10, 2 );
