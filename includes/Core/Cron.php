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

	public function schedule_events() {
		$options = get_option( 'dame_options' );
		$time_str = $options['backup_time'] ?? '01:00';

		if ( ! wp_next_scheduled( 'dame_daily_backup_event' ) ) {
			wp_schedule_event( strtotime( 'tomorrow ' . $time_str ), 'daily', 'dame_daily_backup_event' );
		}

		if ( ! empty( $options['birthday_emails_enabled'] ) && ! wp_next_scheduled( 'dame_birthday_email_event' ) ) {
			wp_schedule_event( strtotime( 'tomorrow ' . $time_str ) + 7200, 'daily', 'dame_birthday_email_event' );
		}
	}
}
