<?php
declare(strict_types=1);

namespace DAME\Services;

use WP_Query;
use DateTime;

class Birthday {

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		add_action( 'save_post_adherent', [ self::class, 'clear_birthday_cache' ] );
		add_action( 'trashed_post', [ self::class, 'clear_birthday_cache' ] );
		add_action( 'untrashed_post', [ self::class, 'clear_birthday_cache' ] );
		add_action( 'deleted_post', [ self::class, 'clear_birthday_cache' ] );
		add_action( 'update_option_dame_current_season_tag_id', [ self::class, 'clear_birthday_cache' ] );
	}

	/**
	 * Gets the season IDs to filter by.
	 *
	 * @return array<int>
	 */
	private function get_filtered_season_ids(): array {
		$md = wp_date( 'm-d' );
		if ( $md >= '07-01' && $md <= '10-30' ) {
			$year = (int) wp_date( 'Y' );
			$season_ids = [];

			$season_1_name = sprintf( 'Saison %d/%d', $year - 1, $year );
			$term_1 = get_term_by( 'name', $season_1_name, 'dame_saison_adhesion' );
			if ( $term_1 ) {
				$season_ids[] = (int) $term_1->term_id;
			}

			$season_2_name = sprintf( 'Saison %d/%d', $year, $year + 1 );
			$term_2 = get_term_by( 'name', $season_2_name, 'dame_saison_adhesion' );
			if ( $term_2 ) {
				$season_ids[] = (int) $term_2->term_id;
			}

			if ( ! empty( $season_ids ) ) {
				return $season_ids;
			}
		}

		$season_id = (int) get_option( 'dame_current_season_tag_id' );
		return $season_id ? [ $season_id ] : [];
	}

	/**
	 * Retrieves members having their birthday today.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_today_birthdays(): array {
		$season_ids = $this->get_filtered_season_ids();
		$today_str = wp_date( 'Y-m-d' );
		$cache_key = 'dame_today_birthdays_' . $today_str . '_' . implode( '_', $season_ids );

		$birthdays = get_transient( $cache_key );

		if ( false === $birthdays ) {
			$args = [
				'post_type'      => 'adherent',
				'posts_per_page' => -1,
				'meta_query'     => [
					[
						'key'     => '_dame_birth_date',
						'value'   => '-' . wp_date( 'm-d' ) . '$',
						'compare' => 'REGEXP',
					],
				],
			];

			if ( ! empty( $season_ids ) ) {
				$args['tax_query'] = [
					[
						'taxonomy' => 'dame_saison_adhesion',
						'field'    => 'term_id',
						'terms'    => $season_ids,
						'operator' => 'IN',
					],
				];
			}

			$query = new WP_Query( $args );
			$birthdays = [];
			$seen_ids  = [];

			foreach ( $query->posts as $post ) {
				if ( in_array( $post->ID, $seen_ids, true ) ) {
					continue;
				}
				$seen_ids[] = $post->ID;

				$birth_date = get_post_meta( $post->ID, '_dame_birth_date', true );
				$age = 0;
				if ( ! empty( $birth_date ) ) {
					try {
						$date = new DateTime( (string) $birth_date );
						$now  = new DateTime();
						$age  = $now->diff( $date )->y;
					} catch ( \Exception $e ) {
						$age = 0;
					}
				}

				$birthdays[] = [
					'id'   => $post->ID,
					'name' => $post->post_title,
					'age'  => $age,
				];
			}

			set_transient( $cache_key, $birthdays, DAY_IN_SECONDS );
		}

		return is_array( $birthdays ) ? $birthdays : [];
	}

	/**
	 * Retrieves upcoming birthdays.
	 *
	 * @param int $limit Max number of results.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_upcoming_birthdays( int $limit = 10 ): array {
		$season_ids = $this->get_filtered_season_ids();
		$today_str = wp_date( 'Y-m-d' );
		$cache_key = 'dame_upcoming_birthdays_' . $today_str . '_' . implode( '_', $season_ids );

		$upcoming = get_transient( $cache_key );

		if ( false === $upcoming ) {
			$args = [
				'post_type'      => 'adherent',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			];

			if ( ! empty( $season_ids ) ) {
				$args['tax_query'] = [
					[
						'taxonomy' => 'dame_saison_adhesion',
						'field'    => 'term_id',
						'terms'    => $season_ids,
						'operator' => 'IN',
					],
				];
			}

			$ids       = get_posts( $args );
			if ( is_array( $ids ) ) {
				$ids = array_unique( $ids );
			} else {
				$ids = [];
			}
			$today     = new DateTime( 'today', new \DateTimeZone( 'UTC' ) );
			$upcoming  = [];

			foreach ( $ids as $id ) {
				$birth_date_str = get_post_meta( $id, '_dame_birth_date', true );
				if ( empty( $birth_date_str ) ) {
					continue;
				}

				try {
					$birth_date = new DateTime( (string) $birth_date_str, new \DateTimeZone( 'UTC' ) );
					$this_year_birthday = new DateTime( $today->format( 'Y' ) . '-' . $birth_date->format( 'm-d' ), new \DateTimeZone( 'UTC' ) );

					if ( $this_year_birthday < $today ) {
						$next_birthday = clone $this_year_birthday;
						$next_birthday->modify( '+1 year' );
					} else {
						$next_birthday = $this_year_birthday;
					}

					$days_until = $today->diff( $next_birthday )->days;
					$age        = (int) $next_birthday->format( 'Y' ) - (int) $birth_date->format( 'Y' );

					$upcoming[] = [
						'id'            => $id,
						'name'          => get_the_title( $id ),
						'date'          => $next_birthday->format( 'Y-m-d' ),
						'days_until'    => $days_until,
						'next_age'      => $age,
						'original_date' => $birth_date->format( 'm-d' ),
					];
				} catch ( \Exception $e ) {
					continue;
				}
			}

			usort( $upcoming, function( $a, $b ) {
				if ( $a['days_until'] === $b['days_until'] ) {
					return strcmp( $a['original_date'], $b['original_date'] );
				}
				return $a['days_until'] <=> $b['days_until'];
			} );

			set_transient( $cache_key, $upcoming, DAY_IN_SECONDS );
		}

		return array_slice( is_array( $upcoming ) ? $upcoming : [], 0, $limit );
	}

	/**
	 * Clears the birthday transients.
	 *
	 * @param int $post_id Optional post ID to check type.
	 */
	public static function clear_birthday_cache( $post_id = 0 ): void {
		if ( $post_id && 'adherent' !== get_post_type( $post_id ) ) {
			return;
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dame_upcoming_birthdays_%' OR option_name LIKE '_transient_dame_today_birthdays_%'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dame_upcoming_birthdays_%' OR option_name LIKE '_transient_timeout_dame_today_birthdays_%'" );
	}

	public function send_wishes(): void {
		$options = get_option( 'dame_options' );
		if ( empty( $options['birthday_emails_enabled'] ) ) return;

		$article_slug = $options['birthday_article_slug'] ?? '';
		if ( empty( $article_slug ) ) return;

		$posts = get_posts( [ 'name' => $article_slug, 'post_type' => 'post', 'post_status' => [ 'publish', 'private' ], 'posts_per_page' => 1 ] );
		if ( ! $posts ) return;
		$article = $posts[0];

		// Season Logic
		$season_id = get_option( 'dame_current_season_tag_id' );
		if ( ! $season_id ) return;
		$season_ids = [ $season_id ];

		// If Sept, add previous season
		if ( (int) wp_date( 'n' ) === 9 ) {
			$term = get_term( $season_id );
			if ( $term && preg_match( '/(\d{4})\/(\d{4})/', $term->name, $matches ) ) {
				$prev_name = sprintf( 'Saison %d/%d', $matches[1] - 1, $matches[1] );
				$prev_term = get_term_by( 'name', $prev_name, 'dame_saison_adhesion' );
				if ( $prev_term ) $season_ids[] = $prev_term->term_id;
			}
		}

		// Query Adherents
		$query = new WP_Query( [
			'post_type' => 'adherent', 'posts_per_page' => -1,
			'meta_query' => [ [ 'key' => '_dame_birth_date', 'value' => '-' . wp_date( 'm-d' ) . '$', 'compare' => 'REGEXP' ] ],
			'tax_query' => [ [ 'taxonomy' => 'dame_saison_adhesion', 'field' => 'term_id', 'terms' => $season_ids, 'operator' => 'IN' ] ]
		] );

		$sent_list = [];
		$sender_email = $options['sender_email'] ?? get_option( 'admin_email' );
		$headers = [ 'Content-Type: text/html; charset=UTF-8', 'From: ' . get_bloginfo( 'name' ) . ' <' . $sender_email . '>' ];

		while ( $query->have_posts() ) {
			$query->the_post();
			$pid = get_the_ID();
			$nom = get_post_meta( $pid, '_dame_last_name', true );
			if ( empty( $nom ) ) {
				$nom = get_post_meta( $pid, '_dame_birth_name', true );
			}
			$prenom = get_post_meta( $pid, '_dame_first_name', true );
			$birth = get_post_meta( $pid, '_dame_birth_date', true );
			$sexe  = get_post_meta( $pid, '_dame_sexe', true );

			if ( empty( $prenom ) || empty( $birth ) ) continue;

			try {
				$age = ( new DateTime( $birth ) )->diff( new DateTime() )->y;
			} catch ( \Exception $e ) { continue; }

			$civilite = 'Monsieur';
			if ( 'Féminin' === $sexe ) {
				$civilite = 'Madame';
			}

			$subject = str_replace( [ '[NOM]', '[PRENOM]', '[AGE]', '[CIVILITE]' ], [ (string) \DAME\Core\Utils::format_lastname( (string) $nom ), (string) \DAME\Core\Utils::format_firstname( (string) $prenom ), (string) $age, (string) $civilite ], $article->post_title );
			$content = str_replace( [ '[NOM]', '[PRENOM]', '[AGE]', '[CIVILITE]' ], [ (string) \DAME\Core\Utils::format_lastname( (string) $nom ), (string) \DAME\Core\Utils::format_firstname( (string) $prenom ), (string) $age, (string) $civilite ], apply_filters( 'the_content', $article->post_content ) );

			$emails = \DAME\Core\Utils::get_emails_for_adherent( $pid );
			if ( $emails ) {
				foreach ( $emails as $email ) wp_mail( $email, $subject, $content, $headers );
				$sent_list[] = "$prenom $nom ($age ans)";
			}
		}
		wp_reset_postdata();

		// Report to Admin
		if ( ! empty( $sent_list ) ) {
			wp_mail( $sender_email, "Rapport Anniversaires", "Joyeux anniversaire envoyé à :\n" . implode( "\n", $sent_list ), $headers );
		}
	}
}
