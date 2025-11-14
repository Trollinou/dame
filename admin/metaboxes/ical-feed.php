<?php
/**
 * Metaboxes for the iCal Feed CPT.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Adds the metabox for iCal Feed settings.
 */
function dame_add_ical_feed_metabox() {
    add_meta_box(
        'dame_ical_feed_settings',
        __( 'Configuration du flux', 'dame' ),
        'dame_render_ical_feed_metabox',
        'dame_ical_feed',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'dame_add_ical_feed_metabox' );

/**
 * Renders the metabox content for iCal Feed settings.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_ical_feed_metabox( $post ) {
    wp_nonce_field( 'dame_save_ical_feed_meta', 'dame_ical_feed_nonce' );

    $selected_categories = get_post_meta( $post->ID, '_dame_ical_feed_categories', true );
    if ( ! is_array( $selected_categories ) ) {
        $selected_categories = array();
    }

    $categories = get_terms( array(
        'taxonomy'   => 'categorie_agenda',
        'hide_empty' => false,
    ) );

    echo '<p>' . __( 'Sélectionnez les catégories d\'événements à inclure dans ce flux. Seuls les événements publics seront inclus.', 'dame' ) . '</p>';

    if ( empty( $categories ) || is_wp_error( $categories ) ) {
        echo '<p>' . __( 'Aucune catégorie d\'événement n\'a été trouvée.', 'dame' ) . '</p>';
        return;
    }

    echo '<div class="category-checklist-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 6px;">';
    foreach ( $categories as $category ) {
        $checked = in_array( $category->term_id, $selected_categories ) ? 'checked' : '';
        echo '<label style="display: block;">';
        echo '<input type="checkbox" name="dame_ical_feed_categories[]" value="' . esc_attr( $category->term_id ) . '" ' . $checked . '> ';
        echo esc_html( $category->name );
        echo '</label>';
    }
    echo '</div>';
}

/**
 * Saves the metadata for the iCal Feed.
 *
 * @param int $post_id The post ID.
 */
function dame_save_ical_feed_meta( $post_id ) {
    if ( ! isset( $_POST['dame_ical_feed_nonce'] ) || ! wp_verify_nonce( $_POST['dame_ical_feed_nonce'], 'dame_save_ical_feed_meta' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['dame_ical_feed_categories'] ) ) {
        $categories = array_map( 'intval', $_POST['dame_ical_feed_categories'] );
        update_post_meta( $post_id, '_dame_ical_feed_categories', $categories );
    } else {
        delete_post_meta( $post_id, '_dame_ical_feed_categories' );
    }
}
add_action( 'save_post_dame_ical_feed', 'dame_save_ical_feed_meta' );
