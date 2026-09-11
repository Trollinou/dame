<?php
/**
 * Agenda Recurrence Calculator Service.
 *
 * @package DAME\Services\Agenda
 */

declare(strict_types=1);

namespace DAME\Services\Agenda;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Class Recurrence_Calculator
 * Handles calculation of recurring event dates bounded by the sports season (Sept 1st - Aug 31st).
 */
class Recurrence_Calculator {

	/**
	 * Computes the strict season deadline (August 31st 23:59:59) based on the event's start date.
	 *
	 * - If start date is between September 1st and December 31st of year X: Season is X / X+1, deadline is August 31st X+1.
	 * - If start date is between January 1st and August 31st of year X: Season is X-1 / X, deadline is August 31st X.
	 *
	 * @param DateTimeImmutable $start_date The event start date.
	 * @return DateTimeImmutable The season deadline.
	 */
	public static function get_season_deadline( DateTimeImmutable $start_date ): DateTimeImmutable {
		$year  = (int) $start_date->format( 'Y' );
		$month = (int) $start_date->format( 'n' );

		$end_year = ( $month >= 9 ) ? ( $year + 1 ) : $year;

		return $start_date->setDate( $end_year, 8, 31 )->setTime( 23, 59, 59 );
	}

	/**
	 * Calculates subsequent occurrence dates based on frequency rules and bounds.
	 * Returns subsequent dates strictly greater than the initial start date.
	 *
	 * @param array<string, mixed>    $rules          Recurrence rules.
	 * @param DateTimeImmutable       $start_date     The initial event start date and time.
	 * @param DateTimeImmutable|null  $user_end_date  Optional user-defined end date.
	 * @param int|null                $max_count      Optional maximum total occurrences (including initial event).
	 * @return array<DateTimeImmutable> Array of subsequent occurrence dates.
	 */
	public static function calculate_occurrences(
		array $rules,
		DateTimeImmutable $start_date,
		?DateTimeImmutable $user_end_date = null,
		?int $max_count = null
	): array {
		$season_deadline = self::get_season_deadline( $start_date );

		// Effective deadline is the earliest of user_end_date and season_deadline.
		$effective_deadline = $season_deadline;
		if ( null !== $user_end_date ) {
			$user_end_limit = $user_end_date->setTime( 23, 59, 59 );
			if ( $user_end_limit < $effective_deadline ) {
				$effective_deadline = $user_end_limit;
			}
		}

		// If max count is specified, max subsequent occurrences is max_count - 1.
		$max_subsequent = ( null !== $max_count && $max_count > 1 ) ? ( $max_count - 1 ) : null;
		if ( null !== $max_count && $max_count <= 1 ) {
			return array();
		}

		$frequency = (string) ( $rules['frequency'] ?? 'weekly' );
		$occurrences = array();

		if ( 'monthly' === $frequency ) {
			$occurrences = self::calculate_monthly_occurrences(
				$rules,
				$start_date,
				$effective_deadline,
				$max_subsequent
			);
		} else {
			$occurrences = self::calculate_weekly_occurrences(
				$rules,
				$start_date,
				$effective_deadline,
				$max_subsequent
			);
		}

		return $occurrences;
	}

	/**
	 * Calculates weekly occurrences.
	 *
	 * @param array<string, mixed> $rules
	 * @param DateTimeImmutable $start_date
	 * @param DateTimeImmutable $deadline
	 * @param int|null $max_subsequent
	 * @return array<DateTimeImmutable>
	 */
	private static function calculate_weekly_occurrences(
		array $rules,
		DateTimeImmutable $start_date,
		DateTimeImmutable $deadline,
		?int $max_subsequent
	): array {
		$interval = max( 1, (int) ( $rules['interval_weeks'] ?? 1 ) );
		$days     = isset( $rules['days_of_week'] ) && is_array( $rules['days_of_week'] ) && ! empty( $rules['days_of_week'] )
			? array_map( 'intval', $rules['days_of_week'] ) // 1 (Mon) to 7 (Sun)
			: array( (int) $start_date->format( 'N' ) );

		sort( $days );

		$occurrences = array();
		// Start with the week of the start date (Monday).
		$current_week_start = $start_date->modify( 'monday this week' );
		$iteration_limit    = 60; // Safeguard against infinite loops.
		$loop_count         = 0;

		while ( $loop_count < $iteration_limit ) {
			foreach ( $days as $day_of_week ) {
				$candidate = $current_week_start->modify( '+' . ( $day_of_week - 1 ) . ' days' )
					->setTime(
						(int) $start_date->format( 'H' ),
						(int) $start_date->format( 'i' ),
						(int) $start_date->format( 's' )
					);

				// Must be strictly after the start date.
				if ( $candidate->format( 'Y-m-d' ) <= $start_date->format( 'Y-m-d' ) ) {
					continue;
				}

				// Check deadline.
				if ( $candidate > $deadline ) {
					return $occurrences;
				}

				$occurrences[] = $candidate;

				if ( null !== $max_subsequent && count( $occurrences ) >= $max_subsequent ) {
					return $occurrences;
				}
			}

			// Advance by interval weeks.
			$current_week_start = $current_week_start->modify( sprintf( '+%d weeks', $interval ) );
			if ( $current_week_start > $deadline ) {
				break;
			}
			$loop_count++;
		}

		return $occurrences;
	}

	/**
	 * Calculates monthly occurrences (day of month or ordinal day of week).
	 *
	 * @param array<string, mixed> $rules
	 * @param DateTimeImmutable $start_date
	 * @param DateTimeImmutable $deadline
	 * @param int|null $max_subsequent
	 * @return array<DateTimeImmutable>
	 */
	private static function calculate_monthly_occurrences(
		array $rules,
		DateTimeImmutable $start_date,
		DateTimeImmutable $deadline,
		?int $max_subsequent
	): array {
		$monthly_type = (string) ( $rules['monthly_type'] ?? 'ordinal' ); // 'ordinal' or 'day_of_month'
		$interval     = max( 1, (int) ( $rules['interval_months'] ?? 1 ) );
		$occurrences  = array();

		$current_month = $start_date->modify( 'first day of this month' );
		$iteration_limit = 24; // Safeguard (max 24 months).
		$loop_count      = 0;

		while ( $loop_count < $iteration_limit ) {
			$current_month = $current_month->modify( sprintf( '+%d months', $interval ) );

			if ( $current_month > $deadline ) {
				break;
			}

			$candidate = null;

			if ( 'day_of_month' === $monthly_type ) {
				$day_num       = min( 31, max( 1, (int) ( $rules['day_of_month'] ?? (int) $start_date->format( 'j' ) ) ) );
				$days_in_month = (int) $current_month->format( 't' );
				$actual_day    = min( $day_num, $days_in_month );

				$candidate = $current_month->setDate(
					(int) $current_month->format( 'Y' ),
					(int) $current_month->format( 'n' ),
					$actual_day
				)->setTime(
					(int) $start_date->format( 'H' ),
					(int) $start_date->format( 'i' ),
					(int) $start_date->format( 's' )
				);
			} else {
				// Ordinal day of week (e.g. 1st Friday, 2nd Saturday, last Friday).
				$ordinal  = (string) ( $rules['ordinal'] ?? 'first' ); // 'first', 'second', 'third', 'fourth', 'last'
				$day_name = (string) ( $rules['day_name'] ?? strtolower( $start_date->format( 'l' ) ) ); // 'friday', 'saturday', etc.

				$valid_ordinals = array( 'first', 'second', 'third', 'fourth', 'last' );
				$valid_days     = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

				if ( ! in_array( $ordinal, $valid_ordinals, true ) ) {
					$ordinal = 'first';
				}
				if ( ! in_array( $day_name, $valid_days, true ) ) {
					$day_name = strtolower( $start_date->format( 'l' ) );
				}

				$modify_str    = sprintf( '%s %s of %s', $ordinal, $day_name, $current_month->format( 'F Y' ) );
				$computed_date = new DateTimeImmutable( $modify_str, $start_date->getTimezone() );

				$candidate = $computed_date->setTime(
					(int) $start_date->format( 'H' ),
					(int) $start_date->format( 'i' ),
					(int) $start_date->format( 's' )
				);
			}

			if ( $candidate && $candidate > $start_date ) {
				if ( $candidate > $deadline ) {
					break;
				}

				$occurrences[] = $candidate;

				if ( null !== $max_subsequent && count( $occurrences ) >= $max_subsequent ) {
					break;
				}
			}

			$loop_count++;
		}

		return $occurrences;
	}
}
