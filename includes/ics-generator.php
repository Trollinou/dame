<?php
/**
 * Functions for generating and handling ICS file downloads for events.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Generates the content for an .ics file for a given event.
 *
 * @param int $post_id The ID of the event post.
 * @return string The formatted .ics content.
 */
function dame_generate_ics_content( $post_id ) {
    // Get post data
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'dame_agenda' ) {
        return '';
    }

    // Get event meta data
    $start_date_str = get_post_meta( $post_id, '_dame_start_date', true );
    $end_date_str   = get_post_meta( $post_id, '_dame_end_date', true );
    $start_time     = get_post_meta( $post_id, '_dame_start_time', true );
    $end_time       = get_post_meta( $post_id, '_dame_end_time', true );
    $all_day        = get_post_meta( $post_id, '_dame_all_day', true );
    $location       = get_post_meta( $post_id, '_dame_location_name', true );
    $description    = get_post_meta( $post_id, '_dame_agenda_description', true );
    $address_1      = get_post_meta( $post_id, '_dame_address_1', true );
    $address_2      = get_post_meta( $post_id, '_dame_address_2', true );
    $postal_code    = get_post_meta( $post_id, '_dame_postal_code', true );
    $city           = get_post_meta( $post_id, '_dame_city', true );

    $full_address = '';
    if ( ! empty( $address_1 ) ) {
        $full_address .= $address_1 . ', ';
    }
    if ( ! empty( $address_2 ) ) {
        $full_address .= $address_2 . ', ';
    }
    if ( ! empty( $postal_code ) ) {
        $full_address .= $postal_code . ' ';
    }
    if ( ! empty( $city ) ) {
        $full_address .= $city;
    }
    $full_address = trim( $full_address, ', ' );

    if ( ! empty( $location ) && ! empty( $full_address ) ) {
        $location_for_ics = $location . ' - ' . $full_address;
    } elseif ( ! empty( $location ) ) {
        $location_for_ics = $location;
    } else {
        $location_for_ics = $full_address;
    }


    // Format dates and times for iCalendar, correctly handling timezones.
    if ($all_day) {
        // For DATE values, the time part is not used.
        $start_date_obj = new DateTime($start_date_str);
        $end_date_obj = new DateTime($end_date_str);
        $end_date_obj->modify('+1 day'); // DTEND is exclusive for all-day events.

        $dtstart = ";VALUE=DATE:" . $start_date_obj->format('Ymd');
        $dtend = ";VALUE=DATE:" . $end_date_obj->format('Ymd');
    } else {
        // For DATETIME, we must specify the timezone to ensure the time is correct globally.
        $timezone_string = get_option('timezone_string');
        if ( empty($timezone_string) ) {
            // A sensible fallback for this plugin, given the user's context.
            $timezone_string = 'Europe/Paris';
        }
        $timezone = new DateTimeZone($timezone_string);

        // Create DateTime objects with the site's timezone to get the correct Unix timestamp.
        $start_datetime = new DateTime($start_date_str . ' ' . $start_time, $timezone);
        $end_datetime = new DateTime($end_date_str . ' ' . $end_time, $timezone);

        // Format the timestamp as UTC Zulu time for the ICS file.
        $dtstart = ":" . gmdate('Ymd\THis\Z', $start_datetime->getTimestamp());
        $dtend = ":" . gmdate('Ymd\THis\Z', $end_datetime->getTimestamp());
    }

    $uid = md5( $post_id ) . '@' . parse_url( home_url(), PHP_URL_HOST );
    $created_date = gmdate( 'Ymd\THis\Z', strtotime( $post->post_date_gmt ) );
    $last_modified = gmdate( 'Ymd\THis\Z', strtotime( $post->post_modified_gmt ) );
    $event_url = get_permalink( $post_id );

    // Build the ICS content
    $ics_content = "BEGIN:VCALENDAR\r\n";
    $ics_content .= "VERSION:2.0\r\n";
    $ics_content .= "PRODID:-//DAME Plugin//NONSGML v1.0//EN\r\n";
    $ics_content .= "BEGIN:VEVENT\r\n";
    $ics_content .= "UID:" . $uid . "\r\n";
    $ics_content .= "DTSTAMP:" . $created_date . "\r\n";
    $ics_content .= "DTSTART" . $dtstart . "\r\n";
    $ics_content .= "DTEND" . $dtend . "\r\n";
    $ics_content .= "LAST-MODIFIED:" . $last_modified . "\r\n";
    $ics_content .= "SUMMARY:" . str_replace( ',', '\,', $post->post_title ) . "\r\n";
    $ics_content .= "DESCRIPTION:" . str_replace( ',', '\,', strip_tags( $description ) ) . "\r\n";
    $ics_content .= "LOCATION:" . str_replace( ',', '\,', $location_for_ics ) . "\r\n";
    if ( ! empty( $event_url ) ) {
        $ics_content .= "URL:" . $event_url . "\r\n";
    }
    $ics_content .= "END:VEVENT\r\n";
    $ics_content .= "END:VCALENDAR\r\n";

    return $ics_content;
}

/**
 * Handles the request to download an .ics file for an event.
 */
function dame_handle_ics_download() {
    if ( isset( $_GET['dame_ics_download'] ) && isset( $_GET['event_id'] ) ) {
        $event_id = intval( $_GET['event_id'] );

        if ( $event_id > 0 && get_post_type( $event_id ) === 'dame_agenda' ) {
            $ics_content = dame_generate_ics_content( $event_id );

            if ( ! empty( $ics_content ) ) {
                $event_title = sanitize_title( get_the_title( $event_id ) );
                $filename = "{$event_title}.ics";

                header( 'Content-Type: text/calendar; charset=utf-8' );
                header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

                echo $ics_content;
                exit;
            }
        }
    }
}
add_action( 'init', 'dame_handle_ics_download' );
