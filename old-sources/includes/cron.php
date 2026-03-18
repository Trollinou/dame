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
        require_once DAME_PLUGIN_DIR . 'admin/backup-restore-adherent.php';
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
 * Sends birthday emails to members.
 */
function dame_send_birthday_emails() {
    // Ensure the SMTP configuration is loaded for this cron job.
    add_action( 'phpmailer_init', 'dame_configure_smtp' );

    $options = get_option( 'dame_options' );
    $enabled = isset( $options['birthday_emails_enabled'] ) ? $options['birthday_emails_enabled'] : 0;
    $article_slug = isset( $options['birthday_article_slug'] ) ? $options['birthday_article_slug'] : '';

    if ( ! $enabled || empty( $article_slug ) ) {
        return; // Feature disabled or no article slug configured
    }

    $sender_email = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

    $current_season_tag_id = get_option( 'dame_current_season_tag_id' );

    // If no season is set as active, do nothing.
    if ( ! $current_season_tag_id ) {
        return;
    }

    $season_ids_to_query = array( $current_season_tag_id );

    // If it's September, also include members from the previous season as their licenses are still valid.
    if ( (int) date( 'n' ) === 9 ) {
        $current_season_term = get_term( $current_season_tag_id, 'dame_saison_adhesion' );

        if ( $current_season_term && ! is_wp_error( $current_season_term ) ) {
            // Extract years from the current season name, e.g., "Saison 2024/2025".
            if ( preg_match( '/(\d{4})\/(\d{4})/', $current_season_term->name, $matches ) ) {
                $start_year = (int) $matches[1];

                // Construct the previous season's name, e.g., "Saison 2023/2024".
                $previous_season_name = sprintf( 'Saison %d/%d', $start_year - 1, $start_year );
                $previous_season_term = get_term_by( 'name', $previous_season_name, 'dame_saison_adhesion' );

                if ( $previous_season_term && ! is_wp_error( $previous_season_term ) ) {
                    $season_ids_to_query[] = $previous_season_term->term_id;
                }
            }
        }
    }

    $today_md = date( 'm-d' );
    $adherents_query = new WP_Query( array(
        'post_type'      => 'adherent',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_dame_birth_date',
                'value'   => '-'. $today_md . '$',
                'compare' => 'REGEXP',
            ),
        ),
        'tax_query'      => array(
            array(
                'taxonomy' => 'dame_saison_adhesion',
                'field'    => 'term_id',
                'terms'    => $season_ids_to_query,
                'operator' => 'IN',
            ),
        ),
    ) );

    if ( ! $adherents_query->have_posts() ) {
        return; // No birthdays today
    }

    $posts = get_posts( array(
        'name'           => $article_slug,
        'post_type'      => 'post',
        'post_status'    => array( 'publish', 'private' ),
        'posts_per_page' => 1,
    ) );

    if ( ! $posts ) {
        return; // Article not found
    }
    $article = $posts[0];

    $original_content = apply_filters( 'the_content', $article->post_content );
    $original_subject = $article->post_title;
    $sent_to = array();

    while ( $adherents_query->have_posts() ) {
        $adherents_query->the_post();
        $adherent_id = get_the_ID();
        $nom = get_post_meta( $adherent_id, '_dame_last_name', true );
        $prenom = get_post_meta( $adherent_id, '_dame_first_name', true );
        $birth_date_str = get_post_meta( $adherent_id, '_dame_birth_date', true );

        if ( empty( $prenom ) || empty( $birth_date_str ) ) {
            continue;
        }

        try {
            $birth_date = new DateTime( $birth_date_str );
            $age = $birth_date->diff( new DateTime( 'now' ) )->y;
        } catch ( Exception $e ) {
            continue; // Invalid date format
        }

        $content = str_replace( '[NOM]', mb_strtoupper( $nom, 'UTF-8' ), $original_content );
        $content = str_replace( '[PRENOM]', mb_convert_case( $prenom, MB_CASE_TITLE, 'UTF-8' ), $content );
        $content = str_replace( '[AGE]', $age, $content );
        $message = '<div style="margin: 1cm;">' . $content . '</div>';

        $subject = str_replace( '[NOM]', mb_strtoupper( $nom, 'UTF-8' ), $original_subject );
        $subject = str_replace( '[PRENOM]', mb_convert_case( $prenom, MB_CASE_TITLE, 'UTF-8' ), $subject );
        $subject = str_replace( '[AGE]', $age, $subject );

        // The function is now in utils.php and will be loaded with the plugin.
        $recipient_emails = dame_get_emails_for_adherent( $adherent_id );

        if ( ! empty( $recipient_emails ) ) {
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo( 'name' ) . ' <' . $sender_email . '>',
            );
            foreach ( $recipient_emails as $email ) {
                wp_mail( $email, $subject, $message, $headers );
            }
            $sent_to[] = "<li>" . esc_html( mb_convert_case( $prenom, MB_CASE_TITLE, 'UTF-8' ) . ' ' . mb_strtoupper( $nom, 'UTF-8' ) ) . " (" . $age . " ans)</li>";
        }
    }
    wp_reset_postdata();

    if ( ! empty( $sent_to ) ) {
        $summary_subject = __( "Rapport d'envoi des vœux d'anniversaire", 'dame' );
        $summary_body = "<p>" . __( "Les vœux d'anniversaire ont été envoyés aujourd'hui aux personnes suivantes :", 'dame' ) . "</p>";
        $summary_body .= "<ul>" . implode( '', $sent_to ) . "</ul>";
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $sender_email, $summary_subject, $summary_body, $headers );
    }
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

    // Get WordPress timezone
    $timezone = wp_timezone();
    try {
        $schedule_date = new DateTime( 'today ' . $schedule_time_str, $timezone );
        if ( $schedule_date->getTimestamp() < time() ) {
            $schedule_date->modify( '+1 day' );
        }
        $first_event_timestamp = $schedule_date->getTimestamp();
    } catch ( Exception $e ) {
        // Fallback to the old method in case of an exception
        $first_event_timestamp = strtotime( 'today ' . $schedule_time_str );
        if ( $first_event_timestamp < time() ) {
            $first_event_timestamp = strtotime( 'tomorrow ' . $schedule_time_str );
        }
    }

    wp_schedule_event( $first_event_timestamp, 'daily', 'dame_daily_backup_event' );
}

/**
 * Unschedules the daily backup event.
 */
function dame_unschedule_backup_event() {
    wp_clear_scheduled_hook( 'dame_daily_backup_event' );
}

/**
 * Schedules the daily birthday email event if it's not already scheduled.
 *
 * The event is scheduled to run 2 hours after the daily backup.
 */
function dame_schedule_birthday_event() {
    // Always clear any existing schedule to ensure we can start fresh or disable it.
    if ( wp_next_scheduled( 'dame_birthday_email_event' ) ) {
        wp_clear_scheduled_hook( 'dame_birthday_email_event' );
    }

    $options = get_option( 'dame_options' );
    $enabled = isset( $options['birthday_emails_enabled'] ) ? $options['birthday_emails_enabled'] : 0;

    // If the feature is disabled, we just unscheduled it and we can stop here.
    if ( ! $enabled ) {
        return;
    }

    $backup_time_str = ! empty( $options['backup_time'] ) && preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $options['backup_time'] ) ? $options['backup_time'] : '01:00';

    // Calculate birthday event time (2 hours after backup)
    $birthday_time_str = date( 'H:i', strtotime( $backup_time_str . ' +2 hours' ) );

    // Get WordPress timezone
    $timezone = wp_timezone();
    try {
        $schedule_date = new DateTime( 'today ' . $birthday_time_str, $timezone );
        if ( $schedule_date->getTimestamp() < time() ) {
            $schedule_date->modify( '+1 day' );
        }
        $first_event_timestamp = $schedule_date->getTimestamp();
    } catch ( Exception $e ) {
        // Fallback to the old method in case of an exception
        $first_event_timestamp = strtotime( 'today ' . $birthday_time_str );
        if ( $first_event_timestamp < time() ) {
            $first_event_timestamp = strtotime( 'tomorrow ' . $birthday_time_str );
        }
    }

    wp_schedule_event( $first_event_timestamp, 'daily', 'dame_birthday_email_event' );
}

/**
 * Unschedules the daily birthday email event.
 */
function dame_unschedule_birthday_event() {
    wp_clear_scheduled_hook( 'dame_birthday_email_event' );
}

/**
 * Sends a batch of emails via WP-Cron.
 *
 * @param int   $message_id  The ID of the message post.
 * @param array $emails      An array of email addresses to send to.
 * @param int   $retry_count The number of times this batch has been retried.
 */
function dame_cron_send_batch_callback( $message_id, $emails, $retry_count ) {
    $message_post = get_post( $message_id );
    if ( ! $message_post ) {
        return; // Stop if message post is deleted.
    }

    // Mark as 'sending' on the first batch.
    $status = get_post_meta( $message_id, '_dame_message_status', true );
    if ( 'scheduled' === $status ) {
        update_post_meta( $message_id, '_dame_message_status', 'sending' );
    }

    $subject = $message_post->post_title;
    $content = apply_filters( 'the_content', $message_post->post_content );

    $options      = get_option( 'dame_options' );
    $sender_email = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );
    $headers      = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo( 'name' ) . ' <' . $sender_email . '>',
    );

    $failed_emails = array();

    foreach ( $emails as $email ) {
        $email_hash   = md5( mb_strtolower( trim( $email ), 'UTF-8' ) );
        $tracking_url = site_url( "/wp-json/dame/v1/track?mid={$message_id}&h={$email_hash}" );
        $pixel_img    = '<img src="' . esc_url( $tracking_url ) . '" alt="" width="1" height="1" style="display:none; border:0;" />';

        $message_body = '<div style="margin: 1cm;">' . $content . $pixel_img . '</div>';

        $sent = wp_mail( $email, $subject, $message_body, $headers );
        if ( ! $sent ) {
            $failed_emails[] = $email;
        }
    }

    // Handle failures with a retry mechanism.
    if ( ! empty( $failed_emails ) && $retry_count < 3 ) {
        wp_schedule_single_event(
            time() + 60, // Retry in 1 minute.
            'dame_cron_send_batch',
            array(
                'message_id'  => $message_id,
                'emails'      => $failed_emails,
                'retry_count' => $retry_count + 1,
            )
        );
    }

    // Update progress tracking meta.
    $processed_batches = (int) get_post_meta( $message_id, '_dame_scheduled_batches_processed', true );
    $total_batches     = (int) get_post_meta( $message_id, '_dame_scheduled_batches_total', true );

    $processed_batches++;
    update_post_meta( $message_id, '_dame_scheduled_batches_processed', $processed_batches );

    // If all batches are done, mark as sent.
    if ( $processed_batches >= $total_batches ) {
        update_post_meta( $message_id, '_dame_message_status', 'sent' );
    }
}
add_action( 'dame_cron_send_batch', 'dame_cron_send_batch_callback', 10, 3 );
