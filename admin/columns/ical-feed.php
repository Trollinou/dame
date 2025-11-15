<?php
/**
 * Custom columns for the `dame_ical_feed` CPT.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Adds custom columns to the iCal Feed list table.
 *
 * @param array $columns The existing columns.
 * @return array The modified columns.
 */
function dame_add_ical_feed_columns( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $title ) {
        $new_columns[ $key ] = $title;
        if ( 'title' === $key ) {
            $new_columns['subscription_url'] = __( 'URL d\'abonnement', 'dame' );
        }
    }
    return $new_columns;
}
add_filter( 'manage_dame_ical_feed_posts_columns', 'dame_add_ical_feed_columns' );

/**
 * Renders the content for the custom columns.
 *
 * @param string $column_name The name of the column.
 * @param int    $post_id     The ID of the post.
 */
function dame_render_ical_feed_columns( $column_name, $post_id ) {
    if ( 'subscription_url' === $column_name ) {
        $feed_slug = get_post_field( 'post_name', $post_id );
        $feed_url = home_url( '/feed/agenda/' . $feed_slug . '.ics' );
        echo '<input type="text" value="' . esc_attr( $feed_url ) . '" readonly onfocus="this.select();" style="width: 100%;">';
    }
}
add_action( 'manage_dame_ical_feed_posts_custom_column', 'dame_render_ical_feed_columns', 10, 2 );

/**
 * Displays the default, non-modifiable feeds at the top of the list table.
 */
function dame_display_default_ical_feeds() {
    $screen = get_current_screen();
    if ( 'edit-dame_ical_feed' === $screen->id ) {
        $default_feeds = array(
            array(
                'title' => __( 'Flux public global', 'dame' ),
                'url' => home_url( '/feed/agenda/public.ics' ),
                'description' => __( 'Contient tous les événements publics.', 'dame' ),
            ),
            array(
                'title' => __( 'Flux privé global', 'dame' ),
                'url' => home_url( '/feed/agenda/prive.ics' ),
                'description' => __( 'Contient tous les événements privés.', 'dame' ),
            ),
        );

        echo '<div class="notice notice-info inline" style="margin: 1em 0;">';
        echo '<h3>' . __( 'Flux par défaut', 'dame' ) . '</h3>';
        echo '<p>' . __( 'Les flux suivants sont toujours disponibles et ne peuvent pas être modifiés ou supprimés.', 'dame' ) . '</p>';
        echo '<table class="wp-list-table widefat fixed striped" style="margin-bottom: 1em;">';
        echo '<thead><tr><th style="width: 25%;">' . __( 'Nom du flux', 'dame' ) . '</th><th>' . __( 'URL d\'abonnement', 'dame' ) . '</th><th>' . __( 'Description', 'dame' ) . '</th></tr></thead>';
        echo '<tbody>';
        foreach ( $default_feeds as $feed ) {
            echo '<tr>';
            echo '<td><strong>' . esc_html( $feed['title'] ) . '</strong></td>';
            echo '<td><input type="text" value="' . esc_attr( $feed['url'] ) . '" readonly onfocus="this.select();" style="width: 100%;"></td>';
            echo '<td>' . esc_html( $feed['description'] ) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
}
add_action( 'admin_notices', 'dame_display_default_ical_feeds' );
