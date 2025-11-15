<?php
/**
 * Agenda Custom Post Type.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the Agenda CPT.
 */
function dame_register_agenda_cpt() {

    $labels = array(
        'name'                  => _x( 'Agenda', 'Post Type General Name', 'dame' ),
        'singular_name'         => _x( 'Événement', 'Post Type Singular Name', 'dame' ),
        'menu_name'             => __( 'Agenda', 'dame' ),
        'name_admin_bar'        => __( 'Événement', 'dame' ),
        'archives'              => __( 'Archives des événements', 'dame' ),
        'attributes'            => __( 'Attributs de l\'événement', 'dame' ),
        'parent_item_colon'     => __( 'Événement parent :', 'dame' ),
        'all_items'             => __( 'Tous les événements', 'dame' ),
        'add_new_item'          => __( 'Ajouter un nouvel événement', 'dame' ),
        'add_new'               => __( 'Ajouter', 'dame' ),
        'new_item'              => __( 'Nouvel événement', 'dame' ),
        'edit_item'             => __( 'Modifier l\'événement', 'dame' ),
        'update_item'           => __( 'Mettre à jour l\'événement', 'dame' ),
        'view_item'             => __( 'Voir l\'événement', 'dame' ),
        'view_items'            => __( 'Voir les événements', 'dame' ),
        'search_items'          => __( 'Rechercher un événement', 'dame' ),
        'not_found'             => __( 'Non trouvé', 'dame' ),
        'not_found_in_trash'    => __( 'Non trouvé dans la corbeille', 'dame' ),
        'featured_image'        => __( 'Image mise en avant', 'dame' ),
        'set_featured_image'    => __( 'Définir l\'image mise en avant', 'dame' ),
        'remove_featured_image' => __( 'Supprimer l\'image mise en avant', 'dame' ),
        'use_featured_image'    => __( 'Utiliser comme image mise en avant', 'dame' ),
        'insert_into_item'      => __( 'Insérer dans l\'événement', 'dame' ),
        'uploaded_to_this_item' => __( 'Téléversé sur cet événement', 'dame' ),
        'items_list'            => __( 'Liste des événements', 'dame' ),
        'items_list_navigation' => __( 'Navigation de la liste des événements', 'dame' ),
        'filter_items_list'     => __( 'Filtrer la liste des événements', 'dame' ),
    );

    $args = array(
        'label'                 => __( 'Événement', 'dame' ),
        'description'           => __( 'Les événements de l\'agenda', 'dame' ),
        'labels'                => $labels,
        'supports'              => array( 'title' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 25,
        'menu_icon'             => 'dashicons-calendar-alt',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => false,
    );

    register_post_type( 'dame_agenda', $args );
}
add_action( 'init', 'dame_register_agenda_cpt', 0 );

/**
 * Displays event details on the single event page.
 *
 * @param string $content The post content.
 * @return string The modified post content with event details.
 */
function dame_display_event_details( $content ) {
    // Check if we are on a single 'dame_agenda' post page.
    if ( is_singular( 'dame_agenda' ) && in_the_loop() && is_main_query() ) {
        $post_id = get_the_ID();

        // Get event meta data.
        $start_date_str = get_post_meta( $post_id, '_dame_start_date', true );
        $end_date_str   = get_post_meta( $post_id, '_dame_end_date', true );
        $start_time     = get_post_meta( $post_id, '_dame_start_time', true );
        $end_time       = get_post_meta( $post_id, '_dame_end_time', true );
        $all_day        = get_post_meta( $post_id, '_dame_all_day', true );
        $location       = get_post_meta( $post_id, '_dame_location_name', true );
        $description    = get_post_meta( $post_id, '_dame_agenda_description', true );

        $details_html = '<div class="dame-event-details-wrapper">';

        // Date and Time.
        if ( ! empty( $start_date_str ) ) {
            $start_date = new DateTime( $start_date_str );
            $end_date   = new DateTime( $end_date_str );

            $date_display = '';
            // Check if it's a single day event.
            if ( $start_date_str === $end_date_str ) {
                $date_display = date_i18n( get_option( 'date_format' ), $start_date->getTimestamp() );
            } else {
                $date_display = sprintf(
                    __( 'From %s to %s', 'dame' ),
                    date_i18n( get_option( 'date_format' ), $start_date->getTimestamp() ),
                    date_i18n( get_option( 'date_format' ), $end_date->getTimestamp() )
                );
            }

            $ics_download_url = add_query_arg(
                array(
                    'dame_ics_download' => '1',
                    'event_id'          => $post_id,
                ),
                home_url()
            );
            $button_html = '<a href="' . esc_url( $ics_download_url ) . '" class="button dame-add-to-calendar-button">📅 ' . __( 'Ajouter à mon agenda', 'dame' ) . '</a>';

            $ics_download_url = add_query_arg(
                array(
                    'dame_ics_download' => '1',
                    'event_id'          => $post_id,
                ),
                home_url()
            );
            $button_html = '<a href="' . esc_url( $ics_download_url ) . '" class="button dame-add-to-calendar-button">📅 ' . __( 'Ajouter à mon agenda', 'dame' ) . '</a>';

            $details_html .= '<div class="dame-event-detail-item dame-event-date">';
            $details_html .= '<h4>' . __( 'Date', 'dame' ) . '</h4>';
            $details_html .= '<p>' . esc_html( $date_display ) . ' ' . $button_html . '</p>';
            $details_html .= '</div>';

            // Time display.
            if ( ! $all_day && ! empty( $start_time ) ) {
                $details_html .= '<div class="dame-event-detail-item dame-event-time">';
                $details_html .= '<h4>' . __( 'Heure', 'dame' ) . '</h4>';
                $details_html .= '<p>' . esc_html( $start_time . ( ! empty( $end_time ) ? ' - ' . $end_time : '' ) ) . '</p>';
                $details_html .= '</div>';
            }
        }

        // Competition info.
        $competition_type = get_post_meta( $post_id, '_dame_competition_type', true );
        if ( $competition_type && 'non' !== $competition_type ) {
            $competition_level = get_post_meta( $post_id, '_dame_competition_level', true );
            $type_label = ( 'individuelle' === $competition_type ) ? __( 'Individuelle', 'dame' ) : __( 'Par équipe', 'dame' );
            $level_label = '';
            if ( 'departementale' === $competition_level ) {
                $level_label = __( 'Départementale', 'dame' );
            } elseif ( 'regionale' === $competition_level ) {
                $level_label = __( 'Régionale', 'dame' );
            } elseif ( 'nationale' === $competition_level ) {
                $level_label = __( 'Nationale', 'dame' );
            }

            $details_html .= '<div class="dame-event-detail-item dame-event-competition">';
            $details_html .= '<h4>' . __( 'Compétition', 'dame' ) . '</h4>';
            $details_html .= '<p>' . esc_html( $type_label . ' - ' . $level_label ) . '</p>';
            $details_html .= '</div>';
        }

        // Description.
        if ( ! empty( $description ) ) {
            $details_html .= '<div class="dame-event-detail-item dame-event-description">';
            $details_html .= '<h4>' . __( 'Description', 'dame' ) . '</h4>';
            $details_html .= '<div>' . wpautop( wp_kses_post( $description ) ) . '</div>';
            $details_html .= '</div>';
        }

        // Location.
        $address_1    = get_post_meta( $post_id, '_dame_address_1', true );
        if ( ! empty( $location ) || ! empty( $address_1 ) ) {
            $address_2    = get_post_meta( $post_id, '_dame_address_2', true );
            $postal_code  = get_post_meta( $post_id, '_dame_postal_code', true );
            $city         = get_post_meta( $post_id, '_dame_city', true );
            $latitude     = get_post_meta( $post_id, '_dame_latitude', true );
            $longitude    = get_post_meta( $post_id, '_dame_longitude', true );
            $distance     = get_post_meta( $post_id, '_dame_distance', true );
            $travel_time  = get_post_meta( $post_id, '_dame_travel_time', true );

            $details_html .= '<div class="dame-event-detail-item dame-event-location">';
            $details_html .= '<h4>' . __( 'Lieu', 'dame' ) . '</h4>';
            $location_title = ! empty( $location ) ? $location : $address_1;
            $details_html .= '<p><strong>' . esc_html( $location_title ) . '</strong></p>';

            $full_address = '';
            if ( ! empty( $address_1 ) ) {
                $full_address .= $address_1 . '<br>';
            }
            if ( ! empty( $address_2 ) ) {
                $full_address .= $address_2 . '<br>';
            }
            if ( ! empty( $postal_code ) && ! empty( $city ) ) {
                $full_address .= $postal_code . ' ' . $city;
            } elseif ( ! empty( $postal_code ) ) {
                $full_address .= $postal_code;
            } elseif ( ! empty( $city ) ) {
                $full_address .= $city;
            }

            if ( ! empty( $full_address ) ) {
                $details_html .= '<p>' . wp_kses_post( $full_address ) . '</p>';
            }

            if ( ! empty( $latitude ) && ! empty( $longitude ) ) {
                $details_html .= '<p class="dame-gps-coords">(' . esc_html( $latitude ) . ', ' . esc_html( $longitude ) . ')</p>';
            }

            if ( ! empty( $distance ) && ! empty( $travel_time ) ) {
                $details_html .= '<p class="dame-travel-info">' . sprintf( __( 'Distance: %s - Temps de trajet: %s', 'dame' ), esc_html( $distance ), esc_html( $travel_time ) ) . '</p>';
            }

            $details_html .= '</div>';

            if ( ! empty( $latitude ) && ! empty( $longitude ) ) {
                $details_html .= '<div class="map-container">';
                // Embed map
                $details_html .= '<iframe src="https://maps.google.com/maps?q=' . esc_attr( $latitude ) . ',' . esc_attr( $longitude ) . '&hl=es;z=14&amp;output=embed" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>';

                // Navigation buttons
                $details_html .= '<div class="nav-buttons">';
                $details_html .= '<a href="https://www.google.com/maps/dir/?api=1&destination=' . esc_attr( $latitude ) . ',' . esc_attr( $longitude ) . '" target="_blank" class="button nav-button">📱 ' . __( 'Calculer l\'itinéraire', 'dame' ) . '</a>';
                $details_html .= '<button id="dame-open-gps" data-lat="' . esc_attr( $latitude ) . '" data-lng="' . esc_attr( $longitude ) . '" class="button nav-button">🧭 ' . __( 'Ouvrir dans le GPS', 'dame' ) . '</button>';
                $details_html .= '</div>';
                $details_html .= '</div>';
            }
        }

        $details_html .= '</div>';

        // Prepend the details to the original content.
        $content = $details_html . $content;
    }
    return $content;
}
add_filter( 'the_content', 'dame_display_event_details' );
