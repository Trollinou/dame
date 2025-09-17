<?php
/**
 * File for handling scheduled tasks (WP-Cron).
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Generates a backup file for the "Adherent" data and saves it to a temporary directory.
 *
 * @return string|WP_Error The path to the backup file on success, or a WP_Error object on failure.
 */
function dame_generate_adherent_backup_file() {
    if ( ! function_exists( 'dame_get_adherent_export_data' ) ) {
        require_once DAME_PLUGIN_DIR . 'admin/import-export.php';
    }

    $export_data = dame_get_adherent_export_data();
    $data_to_compress = json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    $compressed_data = gzcompress( $data_to_compress );

    $upload_dir = wp_upload_dir();
    $backup_dir = trailingslashit( $upload_dir['basedir'] ) . 'dame-backups';
    wp_mkdir_p( $backup_dir );

    $filename = 'dame-adherents-backup-' . date( 'Y-m-d' ) . '.json.gz';
    $filepath = trailingslashit( $backup_dir ) . $filename;

    if ( file_put_contents( $filepath, $compressed_data ) === false ) {
        return new WP_Error( 'file_write_error', __( "Impossible d'écrire le fichier de sauvegarde sur le disque.", 'dame' ) );
    }

    return $filepath;
}

/**
 * Generates a backup file for the "Agenda" data and saves it to a temporary directory.
 *
 * @return string|WP_Error The path to the backup file on success, or a WP_Error object on failure.
 */
function dame_generate_agenda_backup_file() {
    if ( ! function_exists( 'dame_get_agenda_export_data' ) ) {
        require_once DAME_PLUGIN_DIR . 'admin/backup-restore-agenda.php';
    }

    $export_data = dame_get_agenda_export_data();
    $data_to_compress = json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    $compressed_data = gzcompress( $data_to_compress );

    $upload_dir = wp_upload_dir();
    $backup_dir = trailingslashit( $upload_dir['basedir'] ) . 'dame-backups';
    wp_mkdir_p( $backup_dir );

    $filename = 'dame-agenda-backup-' . date( 'Y-m-d' ) . '.json.gz';
    $filepath = trailingslashit( $backup_dir ) . $filename;

    if ( file_put_contents( $filepath, $compressed_data ) === false ) {
        return new WP_Error( 'file_write_error', __( "Impossible d'écrire le fichier de sauvegarde sur le disque.", 'dame' ) );
    }

    return $filepath;
}

/**
 * The main cron job function.
 *
 * Generates both backup files, sends them by email, and cleans up the files.
 */
function dame_do_scheduled_backup() {
    $adherent_backup_path = dame_generate_adherent_backup_file();
    $agenda_backup_path = dame_generate_agenda_backup_file();

    $attachments = array();
    if ( ! is_wp_error( $adherent_backup_path ) && file_exists( $adherent_backup_path ) ) {
        $attachments[] = $adherent_backup_path;
    }

    if ( ! is_wp_error( $agenda_backup_path ) && file_exists( $agenda_backup_path ) ) {
        $attachments[] = $agenda_backup_path;
    }

    if ( function_exists( 'roi_generate_apprentissage_backup_file' ) ) {
        $apprentissage_backup_path = roi_generate_apprentissage_backup_file();
        if ( ! is_wp_error( $apprentissage_backup_path ) && file_exists( $apprentissage_backup_path ) ) {
            $attachments[] = $apprentissage_backup_path;
        }
    }

    if ( empty( $attachments ) ) {
        // Optionally, log an error if no backups were created
        return;
    }

    $options = get_option( 'dame_options' );
    $to = isset( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

    if ( empty( $to ) ) {
        // No recipient, clean up and exit
        foreach ( $attachments as $file_path ) {
            wp_delete_file( $file_path );
        }
        return;
    }

    $subject = sprintf(
        // translators: %s is the site name.
        __( 'Sauvegarde journalière DAME pour %s', 'dame' ),
        get_bloginfo( 'name' )
    );
    $body = '<p>' . __( "Veuillez trouver ci-joint les sauvegardes journalières des bases de données 'Adhérents', 'Apprentissage' et 'Agenda' de l'extension DAME.", 'dame' ) . '</p>';
    $body .= '<p>' . sprintf( __( 'Sauvegarde effectuée le %s.', 'dame' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) . '</p>';

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    wp_mail( $to, $subject, $body, $headers, $attachments );

    // Cleanup the backup files
    foreach ( $attachments as $file_path ) {
        wp_delete_file( $file_path );
    }
}

/**
 * Schedules the daily backup event if it's not already scheduled.
 */
function dame_schedule_backup_event() {
    if ( wp_next_scheduled( 'dame_daily_backup_event' ) ) {
        wp_clear_scheduled_hook( 'dame_daily_backup_event' );
    }

    $options = get_option( 'dame_options' );
    $schedule_time_str = ! empty( $options['backup_time'] ) && preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $options['backup_time'] ) ? $options['backup_time'] : '01:00';

    $first_event_timestamp = strtotime( 'today ' . $schedule_time_str );
    if ( $first_event_timestamp < time() ) {
        $first_event_timestamp = strtotime( 'tomorrow ' . $schedule_time_str );
    }

    wp_schedule_event( $first_event_timestamp, 'daily', 'dame_daily_backup_event' );
}

/**
 * Unschedules the daily backup event.
 */
function dame_unschedule_backup_event() {
    wp_clear_scheduled_hook( 'dame_daily_backup_event' );
}
