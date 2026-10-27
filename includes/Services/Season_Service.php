<?php
/**
 * Season Service for DAME.
 *
 * @package DAME\Services
 */

declare(strict_types=1);

namespace DAME\Services;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use WP_Term;

/**
 * Class Season_Service
 *
 * Handles logic, calculations and term lookups related to both the sports season
 * (Sept 1st to Aug 31st) and the membership/licence validity cycle (July 1st to Oct 31st N+1).
 */
class Season_Service {

	/**
	 * Gets the current active season term ID.
	 *
	 * @return int The term ID, or 0 if not set.
	 */
	public static function get_current_season_id(): int {
		return (int) get_option( 'dame_current_season_tag_id' );
	}

	/**
	 * Gets the current active season term.
	 *
	 * @return WP_Term|null The term object or null.
	 */
	public static function get_current_season_term(): ?WP_Term {
		$season_id = self::get_current_season_id();
		if ( $season_id <= 0 ) {
			return null;
		}

		$term = get_term( $season_id, 'dame_saison_adhesion' );
		return ( $term instanceof WP_Term ) ? $term : null;
	}

	/**
	 * Gets the name of the current active season.
	 *
	 * @return string The season name (e.g. "Saison 2024/2025") or empty string.
	 */
	public static function get_current_season_name(): string {
		$term = self::get_current_season_term();
		return $term ? (string) $term->name : '';
	}

	/**
	 * Formats a standardized season name.
	 *
	 * @param int $start_year Starting year (e.g. 2024).
	 * @param int $end_year   Ending year (e.g. 2025).
	 * @return string Formatted string "Saison YYYY/YYYY".
	 */
	public static function format_season_name( int $start_year, int $end_year ): string {
		return sprintf( 'Saison %d/%d', $start_year, $end_year );
	}

	/**
	 * Parses start and end years from a season term, ID or raw string.
	 *
	 * @param WP_Term|int|string $season Season term, term ID, or name string.
	 * @return array{start: int, end: int}|null Array with 'start' and 'end' keys or null if invalid.
	 */
	public static function parse_season_years( WP_Term|int|string $season ): ?array {
		$name = '';
		if ( $season instanceof WP_Term ) {
			$name = (string) $season->name;
		} elseif ( is_numeric( $season ) && (int) $season > 0 ) {
			$term = get_term( (int) $season, 'dame_saison_adhesion' );
			if ( $term instanceof WP_Term ) {
				$name = (string) $term->name;
			}
		} elseif ( is_string( $season ) ) {
			$name = $season;
		}

		if ( empty( $name ) ) {
			return null;
		}

		if ( preg_match( '/(\d{4})\s*\/\s*(\d{4})/', $name, $matches ) ) {
			return array(
				'start' => (int) $matches[1],
				'end'   => (int) $matches[2],
			);
		}

		return null;
	}

	/**
	 * Calculates the name of the next season to create.
	 *
	 * @return string Next season name (e.g. "Saison 2025/2026").
	 */
	public static function get_next_season_name(): string {
		$current_term = self::get_current_season_term();
		if ( $current_term ) {
			$years = self::parse_season_years( $current_term );
			if ( $years ) {
				$next_start = $years['end'];
				$next_end   = $next_start + 1;
				return self::format_season_name( $next_start, $next_end );
			}
		}

		$current_month = (int) wp_date( 'n' );
		$current_year  = (int) wp_date( 'Y' );
		$start_year    = ( $current_month >= 9 ) ? $current_year + 1 : $current_year;
		$end_year      = $start_year + 1;

		return self::format_season_name( $start_year, $end_year );
	}

	/**
	 * Computes the strict sports season deadline (August 31st 23:59:59) based on the event's start date.
	 *
	 * - If start date is between September 1st and December 31st of year X: Season is X / X+1, deadline is August 31st X+1.
	 * - If start date is between January 1st and August 31st of year X: Season is X-1 / X, deadline is August 31st X.
	 *
	 * @param DateTimeImmutable $start_date The event start date.
	 * @return DateTimeImmutable The sports season deadline.
	 */
	public static function get_sports_season_deadline( DateTimeImmutable $start_date ): DateTimeImmutable {
		$year  = (int) $start_date->format( 'Y' );
		$month = (int) $start_date->format( 'n' );

		$end_year = ( $month >= 9 ) ? ( $year + 1 ) : $year;

		return $start_date->setDate( $end_year, 8, 31 )->setTime( 23, 59, 59 );
	}

	/**
	 * Determines whether a given date is within the annual membership overlap window (July 1st to Oct 31st).
	 *
	 * During this window, members of the ending season (N-1/N) retain active privileges
	 * while new members for the upcoming season (N/N+1) can register and participate.
	 *
	 * @param DateTimeInterface|null $date The date to check, defaults to current time.
	 * @return bool True if within the July 1st - October 31st window.
	 */
	public static function is_in_membership_overlap( ?DateTimeInterface $date = null ): bool {
		$md = $date ? $date->format( 'm-d' ) : wp_date( 'm-d' );
		return ( $md >= '07-01' && $md <= '10-31' );
	}

	/**
	 * Computes the membership expiry cutoff date (October 31st 23:59:59 of N+1) for a season starting in year N.
	 *
	 * @param int $season_start_year The starting year of the season (e.g. 2024 for season 2024/2025).
	 * @return DateTimeImmutable The expiry cutoff date.
	 */
	public static function get_membership_expiry_date( int $season_start_year ): DateTimeImmutable {
		return new DateTimeImmutable(
			sprintf( '%d-10-31 23:59:59', $season_start_year + 1 ),
			new DateTimeZone( 'UTC' )
		);
	}

	/**
	 * Gets the term IDs of all valid membership seasons for a given date.
	 *
	 * - During the overlap period (July 1st to Oct 31st): returns term IDs for both Saison (Y-1)/Y and Saison Y/(Y+1).
	 * - Outside overlap (Nov 1st to June 30th): returns the currently configured active season term ID.
	 *
	 * @param DateTimeInterface|null $date Optional reference date, defaults to today.
	 * @return array<int> Term IDs of valid seasons.
	 */
	public static function get_valid_membership_season_ids( ?DateTimeInterface $date = null ): array {
		if ( self::is_in_membership_overlap( $date ) ) {
			$year       = $date ? (int) $date->format( 'Y' ) : (int) wp_date( 'Y' );
			$season_ids = array();

			// Saison N-1/N (finishing on Oct 31st of year N).
			$term_1 = get_term_by( 'name', self::format_season_name( $year - 1, $year ), 'dame_saison_adhesion' );
			if ( $term_1 instanceof WP_Term ) {
				$season_ids[] = (int) $term_1->term_id;
			}

			// Saison N/N+1 (starting on Sept 1st of year N, open since July 1st).
			$term_2 = get_term_by( 'name', self::format_season_name( $year, $year + 1 ), 'dame_saison_adhesion' );
			if ( $term_2 instanceof WP_Term ) {
				$season_ids[] = (int) $term_2->term_id;
			}

			if ( ! empty( $season_ids ) ) {
				return array_values( array_unique( $season_ids ) );
			}
		}

		$current_id = self::get_current_season_id();
		return $current_id > 0 ? array( $current_id ) : array();
	}
}

