<?php

namespace DAME\Core;

use DAME\Services\Backup;
use DAME\Services\Birthday;
use DateTime;

class Cron {
	public function init(): void {
		add_action( 'dame_daily_backup_event', function() { ( new Backup() )->run_scheduled_backup(); } );
		add_action( 'dame_birthday_email_event', function() { ( new Birthday() )->send_wishes(); } );
		add_action( 'admin_init', [ $this, 'schedule_events' ] );
	}

	public function schedule_events(): void {
		$options = get_option( 'dame_options', [] );
		$time_str = ! empty( $options['backup_time'] ) ? $options['backup_time'] : '01:00';

		$backup_timestamp = $this->get_timestamp_from_local_time( $time_str );
		
		// Check if scheduled time has changed
		$scheduled_backup = wp_next_scheduled( 'dame_daily_backup_event' );
		if ( $scheduled_backup && (int) $scheduled_backup !== (int) $backup_timestamp ) {
			wp_clear_scheduled_hook( 'dame_daily_backup_event' );
			$scheduled_backup = false;
		}

		if ( ! $scheduled_backup ) {
			wp_schedule_event( $backup_timestamp, 'daily', 'dame_daily_backup_event' );
		}

		// Birthday emails (2 hours after backup)
		if ( ! empty( $options['birthday_emails_enabled'] ) ) {
			$birthday_timestamp = $backup_timestamp + 7200;
			$scheduled_birthday = wp_next_scheduled( 'dame_birthday_email_event' );
			
			if ( $scheduled_birthday && (int) $scheduled_birthday !== (int) $birthday_timestamp ) {
				wp_clear_scheduled_hook( 'dame_birthday_email_event' );
				$scheduled_birthday = false;
			}

			if ( ! $scheduled_birthday ) {
				wp_schedule_event( $birthday_timestamp, 'daily', 'dame_birthday_email_event' );
			}
		} else {
			wp_clear_scheduled_hook( 'dame_birthday_email_event' );
		}
	}

	/**
	 * Converts a local time string (HH:MM) to a UTC timestamp for the next occurrence.
	 *
	 * @param string $time_str Time in HH:MM format.
	 * @return int UTC timestamp.
	 */
	private function get_timestamp_from_local_time( $time_str ) {
		$timezone = wp_timezone();
		$now      = new DateTime( 'now', $timezone );
		$target   = DateTime::createFromFormat( 'Y-m-d H:i', $now->format( 'Y-m-d' ) . ' ' . $time_str, $timezone );

		if ( $target <= $now ) {
			$target->modify( '+1 day' );
		}

		return $target->getTimestamp();
	}
}
