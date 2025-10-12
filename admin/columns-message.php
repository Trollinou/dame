<?php
/**
 * File for handling the custom columns for the Message CPT.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Adds custom columns to the message list table.
 *
 * @param array $columns The existing columns.
 * @return array The modified columns.
 */
function dame_add_message_columns( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $title ) {
        $new_columns[$key] = $title;
        if ( 'title' === $key ) {
            $new_columns['sent_date'] = __( 'Date d\'envoi', 'dame' );
            $new_columns['sending_author'] = __( 'Auteur de l\'envoi', 'dame' );
            $new_columns['recipients'] = __( 'Destinataires', 'dame' );
        }
    }
    return $new_columns;
}
add_filter( 'manage_dame_message_posts_columns', 'dame_add_message_columns' );

/**
 * Renders the content for the custom columns.
 *
 * @param string $column  The name of the column.
 * @param int    $post_id The ID of the post.
 */
function dame_render_message_columns( $column, $post_id ) {
    switch ( $column ) {
        case 'sent_date':
            $sent_date = get_post_meta( $post_id, '_dame_sent_date', true );
            if ( ! empty( $sent_date ) ) {
                echo esc_html( get_date_from_gmt( $sent_date, 'j F Y, H:i' ) );
            } else {
                echo '—';
            }
            break;

        case 'sending_author':
            $author_id = get_post_meta( $post_id, '_dame_sending_author', true );
            if ( ! empty( $author_id ) ) {
                $author = get_user_by( 'id', $author_id );
                echo esc_html( $author ? $author->display_name : __( 'Utilisateur inconnu', 'dame' ) );
            } else {
                echo '—';
            }
            break;

        case 'recipients':
            $method = get_post_meta( $post_id, '_dame_recipient_method', true );
            if ( ! $method ) {
                echo '—';
                break;
            }

            if ( 'group' === $method ) {
                $seasons = get_post_meta( $post_id, '_dame_recipient_seasons', true );
                $groups = get_post_meta( $post_id, '_dame_recipient_groups', true );
                $gender = get_post_meta( $post_id, '_dame_recipient_gender', true );

                $parts = array();
                if ( ! empty( $seasons ) ) {
                    $season_names = get_terms( array( 'taxonomy' => 'dame_saison_adhesion', 'include' => $seasons, 'fields' => 'names' ) );
                    $parts[] = '<strong>' . __( 'Saisons', 'dame' ) . ':</strong> ' . implode( ', ', $season_names );
                }
                if ( ! empty( $groups ) ) {
                    $group_names = get_terms( array( 'taxonomy' => 'dame_group', 'include' => $groups, 'fields' => 'names' ) );
                    $parts[] = '<strong>' . __( 'Groupes', 'dame' ) . ':</strong> ' . implode( ', ', $group_names );
                }
                if ( ! empty( $gender ) && 'all' !== $gender ) {
                    $parts[] = '<strong>' . __( 'Sexe', 'dame' ) . ':</strong> ' . esc_html( $gender );
                }
                echo empty( $parts ) ? '—' : implode( '<br>', $parts );

            } elseif ( 'manual' === $method ) {
                $recipient_ids = get_post_meta( $post_id, '_dame_manual_recipients', true );
                if ( empty( $recipient_ids ) ) {
                    echo '—';
                } else {
                    $count = count( $recipient_ids );
                    $recipient_posts = get_posts( array( 'post__in' => $recipient_ids, 'post_type' => 'adherent', 'numberposts' => -1, 'orderby' => 'post_title', 'order' => 'ASC' ) );
                    $recipient_names = wp_list_pluck( $recipient_posts, 'post_title' );

                    if ( $count > 15 ) {
                        $tooltip_content = implode( "\n", $recipient_names );
                        echo '<span title="' . esc_attr( $tooltip_content ) . '">' . sprintf( __( '%d destinataires', 'dame' ), $count ) . '</span>';
                    } else {
                        echo implode( ', ', $recipient_names );
                    }
                }
            }
            break;
    }
}
add_action( 'manage_dame_message_posts_custom_column', 'dame_render_message_columns', 10, 2 );

/**
 * Sets the default sort order for the message list to be by creation date, descending.
 *
 * @param WP_Query $query The WP_Query instance.
 */
function dame_set_message_default_sort( $query ) {
    // Check if we are on the correct admin page and it's the main query.
    if ( is_admin() && $query->is_main_query() && 'dame_message' === $query->get( 'post_type' ) ) {
        // If no orderby is set, default to date descending.
        if ( ! $query->get( 'orderby' ) ) {
            $query->set( 'orderby', 'date' );
            $query->set( 'order', 'DESC' );
        }
    }
}
add_action( 'pre_get_posts', 'dame_set_message_default_sort' );