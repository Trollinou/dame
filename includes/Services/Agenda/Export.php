<?php
/**
 * Agenda CSV / Excel Export Service.
 *
 * @package DAME\Services\Agenda
 */

declare(strict_types=1);

namespace DAME\Services\Agenda;

use WP_Post;
use WP_Query;

/**
 * Class Export
 * Handles exporting Agenda events into French Excel-compatible CSV format.
 */
class Export {

	/**
	 * Exports events as a French Excel CSV file.
	 *
	 * @param array<string, mixed> $params Filter parameters (e.g. category, date range).
	 */
	public function export_csv( array $params = array() ): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les permissions requises pour exporter les événements.', 'dame' ) );
		}

		$filename = 'dame-export-agenda-' . wp_date( 'Y-m-d' ) . '.csv';

		if ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		if ( ! is_resource( $output ) ) {
			return;
		}

		// UTF-8 BOM for Microsoft Excel compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		$headers = array(
			__( 'ID', 'dame' ),
			__( 'Titre', 'dame' ),
			__( 'Statut', 'dame' ),
			__( 'Catégories', 'dame' ),
			__( 'Date de début', 'dame' ),
			__( 'Heure de début', 'dame' ),
			__( 'Date de fin', 'dame' ),
			__( 'Heure de fin', 'dame' ),
			__( 'Journée entière', 'dame' ),
			__( 'Type de compétition', 'dame' ),
			__( 'Niveau de compétition', 'dame' ),
			__( 'Lieu - Nom', 'dame' ),
			__( 'Lieu - Adresse', 'dame' ),
			__( 'Lieu - Complément', 'dame' ),
			__( 'Lieu - Code Postal', 'dame' ),
			__( 'Lieu - Ville', 'dame' ),
			__( 'Latitude', 'dame' ),
			__( 'Longitude', 'dame' ),
			__( 'Distance (km)', 'dame' ),
			__( 'Temps de trajet', 'dame' ),
			__( 'Description', 'dame' ),
			__( 'Nombre de participants', 'dame' ),
			__( 'Participants', 'dame' ),
			__( 'Série récurrente', 'dame' ),
			__( 'Auteur', 'dame' ),
		);

		fputcsv( $output, $headers, ';', '"', '\\' );

		$events = $this->query_events( $params );

		foreach ( $events as $event ) {
			if ( ! $event instanceof WP_Post ) {
				continue;
			}
			$row = $this->format_row( $event );
			fputcsv( $output, $row, ';', '"', '\\' );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Direct stream output to client.
		fclose( $output );
		exit;
	}

	/**
	 * Queries agenda events based on filter criteria.
	 *
	 * @param array<string, mixed> $params Filter parameters.
	 * @return array<int, WP_Post>
	 */
	public function query_events( array $params = array() ): array {
		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_tax_query, WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$args = array(
			'post_type'      => 'dame_agenda',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => -1,
			'meta_key'       => '_dame_start_date',
			'orderby'        => 'meta_value',
			'meta_type'      => 'DATE',
			'order'          => 'ASC',
		);

		// Category filter.
		if ( ! empty( $params['dame_agenda_category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'dame_agenda_category',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( (string) $params['dame_agenda_category'] ),
				),
			);
		}

		// Date range filter.
		$start_month = isset( $params['dame_start_month'] ) ? sanitize_text_field( (string) $params['dame_start_month'] ) : '';
		$start_year  = isset( $params['dame_start_year'] ) ? sanitize_text_field( (string) $params['dame_start_year'] ) : '';
		$end_month   = isset( $params['dame_end_month'] ) ? sanitize_text_field( (string) $params['dame_end_month'] ) : '';
		$end_year    = isset( $params['dame_end_year'] ) ? sanitize_text_field( (string) $params['dame_end_year'] ) : '';

		if ( '' !== $start_month && '' !== $start_year && '' !== $end_month && '' !== $end_year ) {
			$start_date_str = sprintf( '%04d-%02d-01', (int) $start_year, (int) $start_month );
			$end_date_str   = sprintf( '%04d-%02d-01', (int) $end_year, (int) $end_month );

			$start_ts = strtotime( $start_date_str );
			$end_ts   = strtotime( $end_date_str );

			if ( false !== $start_ts && false !== $end_ts && $start_ts <= $end_ts ) {
				$raw_first = wp_date( 'Y-m-d', $start_ts );
				$raw_last  = wp_date( 'Y-m-t', $end_ts );
				$first_day = false !== $raw_first ? (string) $raw_first : $start_date_str;
				$last_day  = false !== $raw_last ? (string) $raw_last : $end_date_str;

				$args['meta_query'] = array(
					array(
						'key'     => '_dame_start_date',
						'value'   => array( $first_day, $last_day ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
				);
			}
		}

		$query = new WP_Query( $args );
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_tax_query, WordPress.DB.SlowDBQuery.slow_db_query_meta_query

		if ( ! empty( $query->posts ) ) {
			update_meta_cache( 'post', wp_list_pluck( $query->posts, 'ID' ) );
		}

		/**
		 * List of posts fetched.
		 *
		 * @var array<int, WP_Post> $posts
		 */
		$posts = $query->posts;
		return $posts;
	}

	/**
	 * Formats a single event post into an array of CSV values.
	 *
	 * @param WP_Post $post The event post.
	 * @return array<int, string>
	 */
	private function format_row( WP_Post $post ): array {
		$post_id = $post->ID;

		// Status translation.
		$status_map   = array(
			'publish' => __( 'Publié', 'dame' ),
			'draft'   => __( 'Brouillon', 'dame' ),
			'pending' => __( 'En attente', 'dame' ),
			'future'  => __( 'Planifié', 'dame' ),
			'private' => __( 'Privé', 'dame' ),
			'trash'   => __( 'Corbeille', 'dame' ),
		);
		$status_label = $status_map[ $post->post_status ] ?? $post->post_status;

		// Categories.
		$categories     = wp_get_post_terms( $post_id, 'dame_agenda_category', array( 'fields' => 'names' ) );
		$categories_str = ! is_wp_error( $categories ) && ! empty( $categories ) ? implode( ', ', $categories ) : '';

		// Dates & Times.
		$raw_start_date = (string) get_post_meta( $post_id, '_dame_start_date', true );
		$raw_start_time = (string) get_post_meta( $post_id, '_dame_start_time', true );
		$raw_end_date   = (string) get_post_meta( $post_id, '_dame_end_date', true );
		$raw_end_time   = (string) get_post_meta( $post_id, '_dame_end_time', true );
		$all_day_val    = (string) get_post_meta( $post_id, '_dame_all_day', true );
		$is_all_day     = '1' === $all_day_val;

		$formatted_start_date = $this->format_date( $raw_start_date );
		$formatted_end_date   = $this->format_date( $raw_end_date );

		$formatted_start_time = $is_all_day ? __( 'Journée entière', 'dame' ) : $this->format_time( $raw_start_time );
		$formatted_end_time   = $is_all_day ? __( 'Journée entière', 'dame' ) : $this->format_time( $raw_end_time );

		// Competition Type & Level.
		$raw_comp_type  = (string) get_post_meta( $post_id, '_dame_competition_type', true );
		$raw_comp_level = (string) get_post_meta( $post_id, '_dame_competition_level', true );

		$comp_type_map   = array(
			'non'          => __( 'Non', 'dame' ),
			'individuelle' => __( 'Individuelle', 'dame' ),
			'equipe'       => __( 'Par équipe', 'dame' ),
		);
		$comp_type_label = $comp_type_map[ $raw_comp_type ] ?? ( '' !== $raw_comp_type ? $raw_comp_type : __( 'Non', 'dame' ) );

		$comp_level_map   = array(
			'departementale' => __( 'Départementale', 'dame' ),
			'regionale'      => __( 'Régionale', 'dame' ),
			'nationale'      => __( 'Nationale', 'dame' ),
		);
		$comp_level_label = 'non' === $raw_comp_type ? '—' : ( $comp_level_map[ $raw_comp_level ] ?? ( '' !== $raw_comp_level ? $raw_comp_level : '—' ) );

		// Location fields.
		$location_name = (string) get_post_meta( $post_id, '_dame_location_name', true );
		$address_1     = (string) get_post_meta( $post_id, '_dame_address_1', true );
		$address_2     = (string) get_post_meta( $post_id, '_dame_address_2', true );
		$postal_code   = (string) get_post_meta( $post_id, '_dame_postal_code', true );
		$city          = (string) get_post_meta( $post_id, '_dame_city', true );
		$latitude      = (string) get_post_meta( $post_id, '_dame_latitude', true );
		$longitude     = (string) get_post_meta( $post_id, '_dame_longitude', true );

		// Distance (formatted with comma for French locale) & travel time.
		$raw_distance = (string) get_post_meta( $post_id, '_dame_distance', true );
		$distance_fr  = '' !== $raw_distance ? str_replace( '.', ',', $raw_distance ) : '';
		$travel_time  = (string) get_post_meta( $post_id, '_dame_travel_time', true );

		// Description clean.
		$raw_desc = (string) get_post_meta( $post_id, '_dame_agenda_description', true );
		if ( empty( $raw_desc ) ) {
			$raw_desc = $post->post_content;
		}
		$clean_desc = trim( html_entity_decode( wp_strip_all_tags( $raw_desc ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

		// Participants.
		$participants_meta = get_post_meta( $post_id, '_dame_event_participants', true );
		$participant_names = array();

		if ( is_array( $participants_meta ) && ! empty( $participants_meta ) ) {
			foreach ( $participants_meta as $participant_id ) {
				$p_id = (int) $participant_id;
				if ( $p_id > 0 ) {
					$title = get_the_title( $p_id );
					if ( ! empty( $title ) ) {
						$participant_names[] = $title;
					}
				}
			}
		}

		$participants_count = (string) count( $participant_names );
		$participants_str   = implode( ', ', $participant_names );

		// Recurrence.
		$recurrence_group = (string) get_post_meta( $post_id, '_dame_recurrence_group_id', true );
		$recurrence_label = ! empty( $recurrence_group ) ? __( 'Oui', 'dame' ) : __( 'Non', 'dame' );

		// Author.
		$author_name = get_the_author_meta( 'display_name', (int) $post->post_author );
		if ( empty( $author_name ) ) {
			$author_name = '—';
		}

		$raw_row = array(
			(string) $post_id,
			$post->post_title,
			$status_label,
			$categories_str,
			$formatted_start_date,
			$formatted_start_time,
			$formatted_end_date,
			$formatted_end_time,
			$is_all_day ? __( 'Oui', 'dame' ) : __( 'Non', 'dame' ),
			$comp_type_label,
			$comp_level_label,
			$location_name,
			$address_1,
			$address_2,
			$postal_code,
			$city,
			$latitude,
			$longitude,
			$distance_fr,
			$travel_time,
			$clean_desc,
			$participants_count,
			$participants_str,
			$recurrence_label,
			$author_name,
		);

		return array_map( array( $this, 'sanitize_csv_cell' ), $raw_row );
	}

	/**
	 * Formats a YYYY-MM-DD date into DD/MM/YYYY.
	 *
	 * @param string $date_str The date string.
	 * @return string
	 */
	private function format_date( string $date_str ): string {
		if ( empty( $date_str ) ) {
			return '';
		}
		$parts = explode( '-', $date_str );
		if ( count( $parts ) === 3 ) {
			return sprintf( '%02d/%02d/%04d', (int) $parts[2], (int) $parts[1], (int) $parts[0] );
		}
		return $date_str;
	}

	/**
	 * Formats an HH:MM:SS or HH:MM time string into HH:MM.
	 *
	 * @param string $time_str The time string.
	 * @return string
	 */
	private function format_time( string $time_str ): string {
		if ( empty( $time_str ) ) {
			return '';
		}
		$parts = explode( ':', $time_str );
		if ( count( $parts ) >= 2 ) {
			return sprintf( '%02d:%02d', (int) $parts[0], (int) $parts[1] );
		}
		return $time_str;
	}

	/**
	 * Prevents CSV formula injection by prepending an apostrophe if value begins with dangerous characters.
	 *
	 * @param string $value The cell value.
	 * @return string
	 */
	private function sanitize_csv_cell( string $value ): string {
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}
		return $value;
	}
}
