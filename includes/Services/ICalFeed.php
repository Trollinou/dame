<?php
/**
 * ICalFeed Service Class.
 *
 * @package DAME\Services
 */

namespace DAME\Services;

use DateTime;
use DateTimeZone;
use WP_Query;

/**
 * Class ICalFeed
 * Handles iCalendar feed generation and URL routing.
 */
class ICalFeed {

	/**
	 * Initialize the service.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_feed' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'do_feed_dame-agenda-ical', [ $this, 'handle_feed_request' ] );
		add_action( 'save_post_dame_agenda', [ $this, 'update_event_meta' ] );
		add_action( 'template_redirect', [ $this, 'handle_single_event_download' ] );
		add_action( 'views_edit-dame_ical_feed', [ $this, 'display_global_feeds_notice' ] );
	}

	/**
	 * Registers the iCal feed and rewrite rule.
	 */
	public function register_feed(): void {
		add_feed( 'dame-agenda-ical', [ $this, 'handle_feed_request' ] );
		add_rewrite_rule( '^feed/agenda/([^/]+)\.ics$', 'index.php?feed=dame-agenda-ical&dame_feed_slug=$matches[1]', 'top' );
	}

	/**
	 * Adds custom query variables.
	 *
	 * @param array<string, mixed> $vars The existing query variables.
	 * @return array<string, mixed> The modified query variables.
	 */
	public function add_query_vars( $vars ): array {
		$vars[] = 'dame_feed_slug';
		return $vars;
	}

	/**
	 * Updates iCalendar metadata for an event when it is saved.
	 *
	 * @param int $post_id The post ID.
	 */
	public function update_event_meta( $post_id ): void {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Ensure UID exists.
		if ( ! get_post_meta( $post_id, '_dame_ical_uid', true ) ) {
			$uid = wp_generate_uuid4() . '@' . parse_url( home_url(), PHP_URL_HOST );
			update_post_meta( $post_id, '_dame_ical_uid', $uid );
		}

		// Increment sequence number.
		$sequence = (int) get_post_meta( $post_id, '_dame_ical_sequence', true );
		update_post_meta( $post_id, '_dame_ical_sequence', $sequence + 1 );
	}

	/**
	 * Handles the feed request and generates the iCal output.
	 */
	public function handle_feed_request(): void {
		$feed_slug = get_query_var( 'dame_feed_slug' );
		if ( ! $feed_slug ) {
			return;
		}

		$args = array(
			'post_type'      => 'dame_agenda',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);

		// Remove .ics from the slug for processing
		$feed_slug_base = preg_replace( '/\.ics$/', '', $feed_slug );

		$feed_details = array(
			'name' => '',
			'url'  => home_url( '/feed/agenda/' . $feed_slug ),
		);

		if ( 'public' === $feed_slug_base ) {
			$feed_details['name'] = __( 'Tous les événements publics', 'dame' );
		} elseif ( 'prive' === $feed_slug_base ) {
			$feed_details['name'] = __( 'Tous les événements privés', 'dame' );
			$args['post_status']  = 'private';
			if ( ! is_user_logged_in() ) {
				// Return empty feed for non-logged-in users trying to access private feed.
				$this->generate_ics( array(), $feed_details );
				return;
			}
		} else {
			$feed_post = get_page_by_path( $feed_slug_base, OBJECT, 'dame_ical_feed' );
			if ( $feed_post ) {
				$feed_details['name'] = $feed_post->post_title;
				$categories           = get_post_meta( $feed_post->ID, '_dame_ical_feed_categories', true );
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
					$this->generate_ics( array(), $feed_details );
					return;
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
		$this->generate_ics( $event_posts, $feed_details );
	}

	/**
	 * Handles single event download.
	 */
	public function handle_single_event_download(): void {
		if ( isset( $_GET['dame_ics_download'] ) && '1' === $_GET['dame_ics_download'] && isset( $_GET['event_id'] ) ) {
			$post_id = intval( $_GET['event_id'] );
			$post    = get_post( $post_id );

			if ( $post && 'dame_agenda' === $post->post_type ) {
				// Check visibility permissions if the post is private.
				if ( 'private' === $post->post_status && ! is_user_logged_in() ) {
					wp_die( esc_html__( 'Vous n\'avez pas la permission de télécharger cet événement.', 'dame' ) );
				}

				$details = array(
					'name' => $post->post_title,
					'url'  => get_permalink( $post ),
				);

				$this->generate_ics( array( $post ), $details, true );
				exit;
			}
		}
	}

	/**
	 * Displays the global feeds notice on the iCal feed list table.
	 */
	public function display_global_feeds_notice(): void {
		$default_feeds = array(
			array(
				'title'       => __( 'Flux public global', 'dame' ),
				'url'         => home_url( '/feed/agenda/public.ics' ),
				'description' => __( 'Contient tous les événements publics.', 'dame' ),
			),
			array(
				'title'       => __( 'Flux privé global', 'dame' ),
				'url'         => home_url( '/feed/agenda/prive.ics' ),
				'description' => __( 'Contient tous les événements privés.', 'dame' ),
			),
		);

		echo '<div class="notice notice-info inline" style="margin: 1em 0;">';
		echo '<h3>' . esc_html__( 'Flux par défaut', 'dame' ) . '</h3>';
		echo '<p>' . esc_html__( 'Les flux suivants sont toujours disponibles et ne peuvent pas être modifiés ou supprimés.', 'dame' ) . '</p>';
		echo '<table class="wp-list-table widefat fixed striped" style="margin-bottom: 1em;">';
		echo '<thead><tr><th style="width: 25%;">' . esc_html__( 'Nom du flux', 'dame' ) . '</th><th>' . esc_html__( 'URL d\'abonnement', 'dame' ) . '</th><th>' . esc_html__( 'Description', 'dame' ) . '</th></tr></thead>';
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

	/**
	 * Generates the ICS content.
	 *
	 * @param array<string, mixed> $event_posts    Array of event posts.
	 * @param array<string, mixed> $feed_details   Feed metadata.
	 * @param bool  $force_download Whether to force download as attachment.
	 */
	private function generate_ics( $event_posts, $feed_details, $force_download = false ) {
		header( 'Content-Type: text/calendar; charset=utf-8' );
		if ( $force_download ) {
			header( 'Content-Disposition: attachment; filename="' . sanitize_title( $feed_details['name'] ) . '.ics"' );
		} else {
			header( 'Content-Disposition: inline; filename="' . sanitize_title( $feed_details['name'] ) . '.ics"' );
		}

		echo "BEGIN:VCALENDAR\r\n";
		echo "VERSION:2.0\r\n";
		echo "PRODID:-//DAME Plugin//NONSGML v1.0//EN\r\n";
		echo "CALSCALE:GREGORIAN\r\n";
		echo $this->fold_line( "NAME:" . $feed_details['name'] ) . "\r\n";
		echo "SOURCE:" . $feed_details['url'] . "\r\n";
		echo "REFRESH-INTERVAL;VALUE=DURATION:P1D\r\n";
		echo "X-WR-CALNAME:" . $feed_details['name'] . "\r\n";

		foreach ( $event_posts as $post ) {
			$post_id = $post->ID;

			// Ensure UID and Sequence exist.
			$uid = get_post_meta( $post_id, '_dame_ical_uid', true );
			if ( empty( $uid ) ) {
				$uid = wp_generate_uuid4() . '@' . parse_url( home_url(), PHP_URL_HOST );
				update_post_meta( $post_id, '_dame_ical_uid', $uid );
			}

			$sequence = (int) get_post_meta( $post_id, '_dame_ical_sequence', true );
			if ( $sequence === 0 ) {
				$sequence = 1;
				update_post_meta( $post_id, '_dame_ical_sequence', $sequence );
			}

			$dtstamp = gmdate( 'Ymd\THis\Z', strtotime( $post->post_modified_gmt ) );

			$start_date_str = get_post_meta( $post_id, '_dame_start_date', true );
			$end_date_str   = get_post_meta( $post_id, '_dame_end_date', true );
			$start_time     = get_post_meta( $post_id, '_dame_start_time', true );
			$end_time       = get_post_meta( $post_id, '_dame_end_time', true );
			$all_day        = get_post_meta( $post_id, '_dame_all_day', true );

			// Skip events with no start date.
			if ( empty( $start_date_str ) ) {
				continue;
			}

			if ( $all_day ) {
				$start_date_obj = new DateTime( $start_date_str );
				// If end date is missing for all day event, assume same day.
				$end_date_obj = ! empty( $end_date_str ) ? new DateTime( $end_date_str ) : new DateTime( $start_date_str );
				// For all-day events, DTEND is exclusive, so we add one day.
				$end_date_obj->modify( '+1 day' );
				$dtstart = ';VALUE=DATE:' . $start_date_obj->format( 'Ymd' );
				$dtend   = ';VALUE=DATE:' . $end_date_obj->format( 'Ymd' );
			} else {
				// For timed events, we convert the site's local time to UTC (Zulu time).
				$timezone_string = get_option( 'timezone_string' );
				if ( empty( $timezone_string ) ) {
					$timezone_string = 'Europe/Paris'; // Fallback.
				}
				$timezone = new DateTimeZone( $timezone_string );

				$start_datetime_str = $start_date_str . ( ! empty( $start_time ) ? ' ' . $start_time : ' 00:00:00' );
				$end_datetime_str   = ( ! empty( $end_date_str ) ? $end_date_str : $start_date_str ) . ( ! empty( $end_time ) ? ' ' . $end_time : ' 23:59:59' );

				try {
					$start_datetime = new DateTime( $start_datetime_str, $timezone );
					$end_datetime   = new DateTime( $end_datetime_str, $timezone );

					$dtstart = ':' . gmdate( 'Ymd\THis\Z', $start_datetime->getTimestamp() );
					$dtend   = ':' . gmdate( 'Ymd\THis\Z', $end_datetime->getTimestamp() );
				} catch ( \Exception $e ) {
					continue; // Skip invalid dates.
				}
			}

			$description = $this->format_for_ics( strip_tags( get_post_meta( $post_id, '_dame_agenda_description', true ) ) );

			// Build full location string.
			$location_name = get_post_meta( $post_id, '_dame_location_name', true );
			$address_1     = get_post_meta( $post_id, '_dame_address_1', true );
			$address_2     = get_post_meta( $post_id, '_dame_address_2', true );
			$postal_code   = get_post_meta( $post_id, '_dame_postal_code', true );
			$city          = get_post_meta( $post_id, '_dame_city', true );

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

			$location_for_ics = '';
			if ( ! empty( $location_name ) && ! empty( $full_address ) ) {
				$location_for_ics = $location_name . ' - ' . $full_address;
			} elseif ( ! empty( $location_name ) ) {
				$location_for_ics = $location_name;
			} elseif ( ! empty( $full_address ) ) {
				$location_for_ics = $full_address;
			}
			$location = $this->format_for_ics( $location_for_ics );

			$summary = $this->format_for_ics( $post->post_title );

			echo "BEGIN:VEVENT\r\n";
			echo "UID:" . $uid . "\r\n";
			echo "SEQUENCE:" . $sequence . "\r\n";
			echo "DTSTAMP:" . $dtstamp . "\r\n";
			echo "DTSTART" . $dtstart . "\r\n";
			echo "DTEND" . $dtend . "\r\n";
			echo $this->fold_line( "SUMMARY:" . $summary ) . "\r\n";
			if ( ! empty( $description ) ) {
				echo $this->fold_line( "DESCRIPTION:" . $description ) . "\r\n";
			}
			if ( ! empty( $location ) ) {
				echo $this->fold_line( "LOCATION:" . $location ) . "\r\n";
			}
			echo "URL:" . get_permalink( $post_id ) . "\r\n";
			echo "END:VEVENT\r\n";
		}

		echo "END:VCALENDAR\r\n";
		exit;
	}

	/**
	 * Formats text for ICS.
	 *
	 * @param string $text Text to format.
	 * @return string Formatted text.
	 */
	private function format_for_ics( $text ) {
		$text = str_replace( '\\', '\\\\', $text );
		$text = str_replace( ',', '\,', $text );
		$text = str_replace( ';', '\;', $text );
		$text = preg_replace( "/\r\n|\n|\r/", "\n", $text );
		$text = str_replace( "\n", '\n', $text );
		return $text;
	}

	/**
	 * Folds lines for ICS compliance.
	 *
	 * @param string $line Line to fold.
	 * @return string Folded line.
	 */
	private function fold_line( $line ) {
		$line = preg_replace( '/(?=.)/u', '$0', $line ); // Make sure we count multibyte characters correctly.
		$line = str_replace( "\r\n", "\n", $line );
		$line = wordwrap( $line, 75, "\r\n ", true );
		return $line;
	}
}
