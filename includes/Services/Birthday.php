<?php
/**
 * Birthday service for DAME.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Services;

use WP_Query;
use DateTime;
use DAME\Services\Season_Service;

/**
 * Class Birthday
 */
class Birthday {

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		add_action( 'save_post_adherent', array( self::class, 'clear_birthday_cache' ) );
		add_action( 'trashed_post', array( self::class, 'clear_birthday_cache' ) );
		add_action( 'untrashed_post', array( self::class, 'clear_birthday_cache' ) );
		add_action( 'deleted_post', array( self::class, 'clear_birthday_cache' ) );
		add_action( 'update_option_dame_current_season_tag_id', array( self::class, 'clear_birthday_cache' ) );
		add_action( 'set_object_terms', array( self::class, 'on_set_object_terms' ), 10, 4 );
	}

	/**
	 * Clears birthday cache when season terms are updated on an adherent.
	 *
	 * @param int                   $object_id Object ID.
	 * @param array<int|string>     $terms     An array of object term IDs or slugs.
	 * @param array<int>            $tt_ids    An array of term taxonomy IDs.
	 * @param string                $taxonomy  Taxonomy slug.
	 */
	public static function on_set_object_terms( int $object_id, array $terms, array $tt_ids, string $taxonomy ): void {
		if ( 'dame_saison_adhesion' === $taxonomy ) {
			self::clear_birthday_cache( $object_id );
		}
	}

	/**
	 * Gets the season IDs to filter by.
	 *
	 * @return array<int>
	 */
	private function get_filtered_season_ids(): array {
		return Season_Service::get_valid_membership_season_ids();
	}

	/**
	 * Retrieves members having their birthday today.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_today_birthdays(): array {
		$season_ids = $this->get_filtered_season_ids();
		if ( empty( $season_ids ) ) {
			return array();
		}

		$today_str = wp_date( 'Y-m-d' );
		$cache_key = 'dame_today_birthdays_' . $today_str . '_' . implode( '_', $season_ids );

		$birthdays = get_transient( $cache_key );

		if ( false === $birthdays ) {
			$args = array(
				'post_type'      => 'adherent',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_dame_birth_date',
						'value'   => '-' . wp_date( 'm-d' ) . '$',
						'compare' => 'REGEXP',
					),
				),
				'tax_query'      => array(
					array(
						'taxonomy' => 'dame_saison_adhesion',
						'field'    => 'term_id',
						'terms'    => $season_ids,
						'operator' => 'IN',
					),
				),
			);

			$query     = new WP_Query( $args );
			$birthdays = array();
			$seen_ids  = array();

			$posts = $query->posts ?? array();
			foreach ( $posts as $post ) {
				if ( ! $post instanceof \WP_Post ) {
					continue;
				}
				if ( in_array( $post->ID, $seen_ids, true ) ) {
					continue;
				}
				$seen_ids[] = $post->ID;

				$birth_date = get_post_meta( $post->ID, '_dame_birth_date', true );
				$age        = 0;
				if ( ! empty( $birth_date ) ) {
					try {
						$date = new DateTime( (string) $birth_date );
						$now  = new DateTime();
						$age  = $now->diff( $date )->y;
					} catch ( \Exception $e ) {
						$age = 0;
					}
				}

				$birthdays[] = array(
					'id'   => $post->ID,
					'name' => $post->post_title,
					'age'  => $age,
				);
			}

			set_transient( $cache_key, $birthdays, DAY_IN_SECONDS );
		}

		return is_array( $birthdays ) ? $birthdays : array();
	}

	/**
	 * Retrieves upcoming birthdays.
	 *
	 * @param int $limit Max number of results.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_upcoming_birthdays( int $limit = 10 ): array {
		$season_ids = $this->get_filtered_season_ids();
		if ( empty( $season_ids ) ) {
			return array();
		}

		$today_str = wp_date( 'Y-m-d' );
		$cache_key = 'dame_upcoming_birthdays_' . $today_str . '_' . implode( '_', $season_ids );

		$upcoming = get_transient( $cache_key );

		if ( false === $upcoming ) {
			$args = array(
				'post_type'      => 'adherent',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => 'dame_saison_adhesion',
						'field'    => 'term_id',
						'terms'    => $season_ids,
						'operator' => 'IN',
					),
				),
			);

			$ids = get_posts( $args );
			if ( is_array( $ids ) ) {
				$ids = array_unique( $ids );
				_prime_post_caches( $ids, false, true );
			} else {
				$ids = array();
			}
			$today    = new DateTime( 'today', new \DateTimeZone( 'UTC' ) );
			$upcoming = array();

			// Identifier la saison N/N+1 lors de la période de chevauchement (01/07 au 31/10).
			$in_overlap     = Season_Service::is_in_membership_overlap();
			$next_season_id = 0;
			$season_cutoff  = null;

			if ( $in_overlap ) {
				$year             = (int) wp_date( 'Y' );
				$next_season_term = get_term_by( 'name', Season_Service::format_season_name( $year, $year + 1 ), 'dame_saison_adhesion' );
				if ( $next_season_term ) {
					$next_season_id = (int) $next_season_term->term_id;
				}
				$season_cutoff = Season_Service::get_membership_expiry_date( $year - 1 );
			}

			foreach ( $ids as $id ) {
				$birth_date_str = get_post_meta( $id, '_dame_birth_date', true );
				if ( empty( $birth_date_str ) ) {
					continue;
				}

				try {
					$birth_date         = new DateTime( (string) $birth_date_str, new \DateTimeZone( 'UTC' ) );
					$this_year_birthday = new DateTime( $today->format( 'Y' ) . '-' . $birth_date->format( 'm-d' ), new \DateTimeZone( 'UTC' ) );

					if ( $this_year_birthday < $today ) {
						$next_birthday = clone $this_year_birthday;
						$next_birthday->modify( '+1 year' );
					} else {
						$next_birthday = $this_year_birthday;
					}

					// Si l'anniversaire tombe après le 31 octobre lors du chevauchement estival/automnal,
					// l'adhérent doit obligatoirement être inscrit pour la saison suivante N/N+1.
					if ( $in_overlap && $season_cutoff && $next_birthday > $season_cutoff && $next_season_id > 0 ) {
						if ( ! has_term( $next_season_id, 'dame_saison_adhesion', $id ) ) {
							continue;
						}
					}

					$days_until = $today->diff( $next_birthday )->days;
					$age        = (int) $next_birthday->format( 'Y' ) - (int) $birth_date->format( 'Y' );

					$upcoming[] = array(
						'id'            => $id,
						'name'          => get_the_title( $id ),
						'date'          => $next_birthday->format( 'Y-m-d' ),
						'days_until'    => $days_until,
						'next_age'      => $age,
						'original_date' => $birth_date->format( 'm-d' ),
					);
				} catch ( \Exception $e ) {
					continue;
				}
			}

			usort(
				$upcoming,
				function ( $a, $b ) {
					if ( $a['days_until'] === $b['days_until'] ) {
						return strcmp( $a['original_date'], $b['original_date'] );
					}
					return $a['days_until'] <=> $b['days_until'];
				}
			);

			set_transient( $cache_key, $upcoming, DAY_IN_SECONDS );
		}

		return array_slice( is_array( $upcoming ) ? $upcoming : array(), 0, $limit );
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

	/**
	 * Sends birthday wishes emails to members whose birthday is today.
	 *
	 * @return void
	 */
	public function send_wishes(): void {
		$options = get_option( 'dame_options' );
		if ( empty( $options['birthday_emails_enabled'] ) ) {
			return;
		}

		$article_slug = $options['birthday_article_slug'] ?? '';
		if ( empty( $article_slug ) ) {
			return;
		}

		$posts = get_posts(
			array(
				'name'           => $article_slug,
				'post_type'      => 'post',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => 1,
			)
		);
		if ( ! $posts ) {
			return;
		}
		$article = $posts[0];

		// Season Logic.
		$season_ids = $this->get_filtered_season_ids();
		if ( empty( $season_ids ) ) {
			return;
		}

		// Query Adherents.
		$query = new WP_Query(
			array(
				'post_type'      => 'adherent',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_dame_birth_date',
						'value'   => '-' . wp_date( 'm-d' ) . '$',
						'compare' => 'REGEXP',
					),
				),
				'tax_query'      => array(
					array(
						'taxonomy' => 'dame_saison_adhesion',
						'field'    => 'term_id',
						'terms'    => $season_ids,
						'operator' => 'IN',
					),
				),
			)
		);

		$sent_list   = array();
		$seen_ids    = array();
		$admin_email = ! empty( $options['assoc_email'] ) && is_email( (string) $options['assoc_email'] ) ? (string) $options['assoc_email'] : (string) get_option( 'admin_email' );
		$headers     = array(
			'Content-Type: text/html; charset=UTF-8',
			'X-Queue-Batch: anniversaires-' . current_time( 'Y-m-d' ),
		);

		while ( $query->have_posts() ) {
			$query->the_post();
			$pid = get_the_ID();
			if ( ! $pid || in_array( $pid, $seen_ids, true ) ) {
				continue;
			}
			$seen_ids[] = $pid;
			$nom        = get_post_meta( $pid, '_dame_last_name', true );
			if ( empty( $nom ) ) {
				$nom = get_post_meta( $pid, '_dame_birth_name', true );
			}
			$prenom = get_post_meta( $pid, '_dame_first_name', true );
			$birth  = get_post_meta( $pid, '_dame_birth_date', true );
			$sexe   = get_post_meta( $pid, '_dame_sexe', true );

			if ( empty( $prenom ) || empty( $birth ) ) {
				continue;
			}

			try {
				$age = ( new DateTime( $birth ) )->diff( new DateTime() )->y;
			} catch ( \Exception $e ) {
				continue; }

			$civilite = 'Monsieur';
			if ( 'Féminin' === $sexe ) {
				$civilite = 'Madame';
			}

			$subject     = str_replace( array( '[NOM]', '[PRENOM]', '[AGE]', '[CIVILITE]' ), array( (string) \DAME\Core\Utils::format_lastname( (string) $nom ), (string) \DAME\Core\Utils::format_firstname( (string) $prenom ), (string) $age, (string) $civilite ), (string) $article->post_title );
			$raw_content = apply_filters( 'the_content', $article->post_content );
			$raw_text    = is_string( $raw_content ) ? $raw_content : (string) $article->post_content;
			$content     = str_replace( array( '[NOM]', '[PRENOM]', '[AGE]', '[CIVILITE]' ), array( (string) \DAME\Core\Utils::format_lastname( (string) $nom ), (string) \DAME\Core\Utils::format_firstname( (string) $prenom ), (string) $age, (string) $civilite ), $raw_text );

			$emails = \DAME\Core\Utils::get_emails_for_adherent( $pid );
			if ( $emails ) {
				foreach ( $emails as $email ) {
					wp_mail( $email, $subject, $content, $headers );
				}
				$sent_list[] = "$prenom $nom ($age ans)";
			}
		}
		wp_reset_postdata();

		// Report to Admin.
		if ( ! empty( $sent_list ) ) {
			wp_mail( $admin_email, 'Rapport Anniversaires', "Joyeux anniversaire envoyé à :\n" . implode( "\n", $sent_list ), $headers );
		}
	}
}
