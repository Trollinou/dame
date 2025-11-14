<?php
/**
 * Functions for generating iCalendar feeds.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Updates iCalendar metadata for an event when it is saved.
 *
 * - Ensures a stable, unique UID exists.
 * - Increments the sequence number to notify clients of updates.
 *
 * @param int $post_id The post ID.
 */
function dame_update_ical_meta( $post_id ) {
    // If this is just a revision, don't do anything.
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Check for a UID, create if it doesn't exist.
    if ( ! get_post_meta( $post_id, '_dame_ical_uid', true ) ) {
        // Generate a new UUID.
        $uid = wp_generate_uuid4() . '@' . parse_url( home_url(), PHP_URL_HOST );
        update_post_meta( $post_id, '_dame_ical_uid', $uid );
    }

    // Increment sequence number.
    $sequence = (int) get_post_meta( $post_id, '_dame_ical_sequence', true );
    update_post_meta( $post_id, '_dame_ical_sequence', $sequence + 1 );
}
add_action( 'save_post_agenda', 'dame_update_ical_meta' );

/**
 * Initializes the iCalendar feeds.
 */
function dame_init_ical_feeds() {
    add_feed( 'agenda', 'dame_handle_ical_feed_request' );
    add_rewrite_rule( '^feed/agenda/([^/]+)/?$', 'index.php?feed=agenda&dame_feed_slug=$matches[1]', 'top' );
}
add_action( 'init', 'dame_init_ical_feeds' );

/**
 * Adds the custom query variable for the feed slug.
 *
 * @param array $vars The array of query variables.
 * @return array The modified array of query variables.
 */
function dame_add_ical_query_vars( $vars ) {
    $vars[] = 'dame_feed_slug';
    return $vars;
}
add_filter( 'query_vars', 'dame_add_ical_query_vars' );

/**
 * Handles the request for an iCalendar feed.
 */
function dame_handle_ical_feed_request() {
    $feed_slug = get_query_var( 'dame_feed_slug' );
    if ( ! $feed_slug ) {
        return;
    }

    $args = array(
        'post_type'      => 'dame_agenda',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    );

    $feed_details = array(
        'name' => '',
        'url'  => home_url( '/feed/agenda/' . $feed_slug ),
    );

    if ( 'public' === $feed_slug ) {
        $feed_details['name'] = __( 'Tous les événements publics', 'dame' );
        // Args are already set for public events.
    } elseif ( 'prive' === $feed_slug ) {
        $feed_details['name'] = __( 'Tous les événements privés', 'dame' );
        $args['post_status'] = 'private';
        if ( ! is_user_logged_in() ) {
            // Optional: You might want to return a 403 Forbidden status here.
            // For now, we just return an empty feed.
            dame_generate_ical_feed( array(), $feed_details );
        }
    } else {
        $feed_post = get_page_by_path( $feed_slug, OBJECT, 'dame_ical_feed' );
        if ( $feed_post ) {
            $feed_details['name'] = $feed_post->post_title;
            $categories = get_post_meta( $feed_post->ID, '_dame_ical_feed_categories', true );
            if ( ! empty( $categories ) && is_array( $categories ) ) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'dame_agenda_category',
                        'field'    => 'term_id',
                        'terms'    => $categories,
                    ),
                );
            } else {
                // If a feed has no categories, return no events.
                dame_generate_ical_feed( array(), $feed_details );
            }
        } else {
            // Invalid feed slug, return a 404.
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            return;
        }
    }

    $event_posts = get_posts( $args );
    dame_generate_ical_feed( $event_posts, $feed_details );
}

/**
 * Flushes rewrite rules on plugin activation.
 */
function dame_flush_rewrite_rules_on_activation() {
    dame_init_ical_feeds();
    flush_rewrite_rules();
}
register_activation_hook( DAME_PLUGIN_DIR . 'dame.php', 'dame_flush_rewrite_rules_on_activation' );


/**
 * Generates and outputs a full iCalendar feed based on a set of events.
 *
 * @param array $event_posts Array of WP_Post objects for the events.
 * @param array $feed_details Associative array with feed metadata (name, url).
 */
function dame_generate_ical_feed( $event_posts, $feed_details ) {
    header( 'Content-Type: text/calendar; charset=utf-8' );
    header( 'Content-Disposition: inline; filename="' . sanitize_title( $feed_details['name'] ) . '.ics"' );

    // Helper function for escaping and folding lines.
    $format_for_ics = function( $text ) {
        $text = str_replace( '\\', '\\\\', $text );
        $text = str_replace( ',', '\,', $text );
        $text = str_replace( ';', '\;', $text );
        $text = preg_replace( "/\r\n|\n|\r/", "\n", $text );
        $text = str_replace( "\n", '\n', $text );
        return $text;
    };

    $fold_line = function( $line ) {
        $line = preg_replace('/(?=.)/u', '$0', $line); // Make sure we count multibyte characters correctly
        $line = str_replace("\r\n", "\n", $line);
        $line = wordwrap($line, 75, "\r\n ", true);
        return $line;
    };

    echo "BEGIN:VCALENDAR\r\n";
    echo "VERSION:2.0\r\n";
    echo "PRODID:-//DAME Plugin//NONSGML v1.0//EN\r\n";
    echo "CALSCALE:GREGORIAN\r\n";
    echo $fold_line( "NAME:" . $feed_details['name'] ) . "\r\n";
    echo "SOURCE:" . $feed_details['url'] . "\r\n";
    echo "REFRESH-INTERVAL;VALUE=DURATION:P1D\r\n";
    echo "X-WR-CALNAME:" . $feed_details['name'] . "\r\n";

    // VTIMEZONE for Europe/Paris
    echo "BEGIN:VTIMEZONE\r\n";
    echo "TZID:Europe/Paris\r\n";
    echo "BEGIN:DAYLIGHT\r\n";
    echo "TZOFFSETFROM:+0100\r\n";
    echo "TZOFFSETTO:+0200\r\n";
    echo "TZNAME:CEST\r\n";
    echo "DTSTART:19700329T020000\r\n";
    echo "RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3\r\n";
    echo "END:DAYLIGHT\r\n";
    echo "BEGIN:STANDARD\r\n";
    echo "TZOFFSETFROM:+0200\r\n";
    echo "TZOFFSETTO:+0100\r\n";
    echo "TZNAME:CET\r\n";
    echo "DTSTART:19701025T030000\r\n";
    echo "RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10\r\n";
    echo "END:STANDARD\r\n";
    echo "END:VTIMEZONE\r\n";

    foreach ( $event_posts as $post ) {
        $post_id = $post->ID;

        $uid = get_post_meta( $post_id, '_dame_ical_uid', true );
        $sequence = (int) get_post_meta( $post_id, '_dame_ical_sequence', true );
        $dtstamp = gmdate('Ymd\THis\Z', strtotime($post->post_modified_gmt));

        $start_date_str = get_post_meta( $post_id, '_dame_start_date', true );
        $end_date_str = get_post_meta( $post_id, '_dame_end_date', true );
        $start_time = get_post_meta( $post_id, '_dame_start_time', true );
        $end_time = get_post_meta( $post_id, '_dame_end_time', true );
        $all_day = get_post_meta( $post_id, '_dame_all_day', true );

        if ($all_day) {
            $start_date_obj = new DateTime($start_date_str);
            $end_date_obj = new DateTime($end_date_str);
            $end_date_obj->modify('+1 day');
            $dtstart = ";VALUE=DATE:" . $start_date_obj->format('Ymd');
            $dtend = ";VALUE=DATE:" . $end_date_obj->format('Ymd');
        } else {
            $dtstart = ";TZID=Europe/Paris:" . date('Ymd\THis', strtotime($start_date_str . ' ' . $start_time));
            $dtend = ";TZID=Europe/Paris:" . date('Ymd\THis', strtotime($end_date_str . ' ' . $end_time));
        }

        $description = $format_for_ics(strip_tags( get_post_meta( $post_id, '_dame_agenda_description', true ) ));
        $location = $format_for_ics(get_post_meta($post_id, '_dame_location_name', true));
        $summary = $format_for_ics($post->post_title);

        echo "BEGIN:VEVENT\r\n";
        echo "UID:" . $uid . "\r\n";
        echo "SEQUENCE:" . $sequence . "\r\n";
        echo "DTSTAMP:" . $dtstamp . "\r\n";
        echo "DTSTART" . $dtstart . "\r\n";
        echo "DTEND" . $dtend . "\r\n";
        echo $fold_line("SUMMARY:" . $summary) . "\r\n";
        if (!empty($description)) {
            echo $fold_line("DESCRIPTION:" . $description) . "\r\n";
        }
        if (!empty($location)) {
            echo $fold_line("LOCATION:" . $location) . "\r\n";
        }
        echo "URL:" . get_permalink($post_id) . "\r\n";
        echo "END:VEVENT\r\n";
    }

    echo "END:VCALENDAR\r\n";
    exit;
}
