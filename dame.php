<?php
/**
 * Plugin Name:       DAME - Dossier Administratif des Membres Échiquéens
 * Plugin URI:
 * Description:       Gère une base de données d'adhérents pour un club.
 * Version:           3.4.3
 * Requires at least: 6.8
 * Requires PHP:      8.2
 * Author:            Etienne Gagnon
 * Author URI:
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dame
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'DAME_VERSION', '3.4.3' );
define( 'DAME_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Handles plugin updates.
 */
function dame_check_for_updates() {
    $current_version = get_option( 'dame_plugin_version', '1.0.0' );
    if ( version_compare( $current_version, DAME_VERSION, '<' ) ) {
        dame_perform_upgrade( $current_version, DAME_VERSION );
    }
}
add_action( 'init', 'dame_check_for_updates', 99 );

/**
 * Perform the upgrade procedures.
 *
 * @param string $old_version The old version number.
 * @param string $new_version The new version number.
 */
function dame_perform_upgrade( $old_version, $new_version ) {
    // In the future, you can add upgrade logic here based on version.
    if ( version_compare( $old_version, '2.0.0', '<' ) ) {
        // Add new capabilities for the chess content module
        if ( function_exists( 'dame_add_capabilities_to_roles' ) ) {
            dame_add_capabilities_to_roles();
        }
        // Flush rewrite rules for the new CPTs
        flush_rewrite_rules();
    }

    if ( version_compare( $old_version, '2.2.0', '<' ) ) {
        // Create the terms for the new taxonomy.
        $anterior_season_term = wp_insert_term( 'Saison antérieure', 'dame_saison_adhesion' );

        // Determine the current season name (e.g., "Saison 2025/2026").
        $current_month     = (int) date( 'n' );
        $current_year      = (int) date( 'Y' );
        $season_start_year = ( $current_month >= 9 ) ? $current_year : $current_year - 1;
        $season_end_year   = $season_start_year + 1;
        $current_season_name = sprintf( 'Saison %d/%d', $season_start_year, $season_end_year );

        $current_season_term = wp_insert_term( $current_season_name, 'dame_saison_adhesion' );

        // Store the ID of the current season tag as the active one.
        if ( ! is_wp_error( $current_season_term ) ) {
            update_option( 'dame_current_season_tag_id', $current_season_term['term_id'] );
        } elseif ( isset( $current_season_term->error_data['term_exists'] ) ) {
            update_option( 'dame_current_season_tag_id', $current_season_term->error_data['term_exists'] );
        }

        // Get all adherents to migrate them.
        $adherents_query = new WP_Query(
            array(
                'post_type'      => 'adherent',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'fields'         => 'ids', // We only need the IDs.
            )
        );

        if ( $adherents_query->have_posts() ) {
            $anterior_term_id = ! is_wp_error( $anterior_season_term ) ? $anterior_season_term['term_id'] : $anterior_season_term->get_error_data( 'term_exists' );
            $current_term_id  = ! is_wp_error( $current_season_term ) ? $current_season_term['term_id'] : $current_season_term->get_error_data( 'term_exists' );

            foreach ( $adherents_query->posts as $adherent_id ) {
                $status = get_post_meta( $adherent_id, '_dame_membership_status', true );

                // Assign the correct season tag.
                if ( 'A' === $status && $current_term_id ) {
                    wp_set_object_terms( $adherent_id, (int) $current_term_id, 'dame_saison_adhesion' );
                } elseif ( in_array( $status, array( 'E', 'X' ), true ) && $anterior_term_id ) {
                    wp_set_object_terms( $adherent_id, (int) $anterior_term_id, 'dame_saison_adhesion' );
                }

                // Delete old meta data.
                delete_post_meta( $adherent_id, '_dame_membership_status' );
                delete_post_meta( $adherent_id, '_dame_membership_date' );
            }
        }
        // Flush rewrite rules after the migration.
        flush_rewrite_rules();
    }

    if ( version_compare( $old_version, '2.2.1', '<' ) ) {
        dame_v2_2_1_migrate_clothing_sizes();
    }

    if ( version_compare( $old_version, '3.3.0', '<' ) ) {
        dame_v3_3_0_migrate_to_group_taxonomy();
    }

    if ( version_compare( $old_version, '3.3.9', '<' ) ) {
        dame_v3_3_9_migrate_birth_name();
    }

    if ( version_compare( $old_version, '3.4.0', '<' ) ) {
        dame_v3_4_0_create_message_opens_table();
    }

    // Update the version in the database to the new version.
    update_option( 'dame_plugin_version', $new_version );
}

/**
 * Migrates the clothing sizes for version 2.2.1.
 *
 * Converts any existing non-standard clothing sizes to 'Non renseigné'.
 */
function dame_v2_2_1_migrate_clothing_sizes() {
    $adherents_query = new WP_Query(
        array(
            'post_type'      => 'adherent',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        )
    );

    if ( $adherents_query->have_posts() ) {
        $valid_sizes = array( 'Non renseigné', '8/10', '10/12', '12/14', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL' );
        foreach ( $adherents_query->posts as $post_id ) {
            $current_size = get_post_meta( $post_id, '_dame_taille_vetements', true );
            if ( ! in_array( $current_size, $valid_sizes, true ) ) {
                update_post_meta( $post_id, '_dame_taille_vetements', 'Non renseigné' );
            }
        }
    }
}

/**
 * Migrates old classification meta to the new 'dame_group' taxonomy.
 *
 * This function is for version 3.3.0.
 */
function dame_v3_3_0_migrate_to_group_taxonomy() {
    // 1. Define and create the terms for the new 'dame_group' taxonomy.
    $terms_to_create = array(
        'Ecole d\'échecs',
        'Pôle Excellence',
        'Bénévole',
        'Elu local',
        'Presse',
    );

    foreach ( $terms_to_create as $term_name ) {
        if ( ! term_exists( $term_name, 'dame_group' ) ) {
            wp_insert_term( $term_name, 'dame_group' );
        }
    }

    // 2. Define the mapping from old meta keys to new term names.
    $meta_to_term_map = array(
        '_dame_is_junior'          => 'Ecole d\'échecs',
        '_dame_is_pole_excellence' => 'Pôle Excellence',
        '_dame_is_benevole'        => 'Bénévole',
        '_dame_is_elu_local'       => 'Elu local',
    );

    // 3. Get all adherents to migrate their data.
    $adherents_query = new WP_Query(
        array(
            'post_type'      => 'adherent',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids', // We only need the IDs.
        )
    );

    if ( $adherents_query->have_posts() ) {
        foreach ( $adherents_query->posts as $adherent_id ) {
            foreach ( $meta_to_term_map as $meta_key => $term_name ) {
                // Check if the old meta field exists and is set to '1'.
                if ( get_post_meta( $adherent_id, $meta_key, true ) === '1' ) {
                    // Assign the corresponding term from the 'dame_group' taxonomy.
                    wp_add_object_terms( $adherent_id, $term_name, 'dame_group' );

                    // Delete the old meta field.
                    delete_post_meta( $adherent_id, $meta_key );
                }
            }
        }
    }
}

/**
 * Migrates the last name to the new birth name field for version 3.3.9.
 */
function dame_v3_3_9_migrate_birth_name() {
    $post_types_to_migrate = array( 'adherent', 'dame_pre_inscription' );

    foreach ( $post_types_to_migrate as $post_type ) {
        $query = new WP_Query(
            array(
                'post_type'      => $post_type,
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'fields'         => 'ids',
            )
        );

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post_id ) {
                $birth_name = get_post_meta( $post_id, '_dame_birth_name', true );
                if ( empty( $birth_name ) ) {
                    $last_name = get_post_meta( $post_id, '_dame_last_name', true );
                    if ( ! empty( $last_name ) ) {
                        update_post_meta( $post_id, '_dame_birth_name', $last_name );
                    }
                }
            }
        }
    }
}

/**
 * Creates the dame_message_opens table for version 3.4.0.
 */
function dame_v3_4_0_create_message_opens_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'dame_message_opens';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        message_id bigint(20) NOT NULL,
        email_hash varchar(32) NOT NULL,
        opened_at datetime NOT NULL,
        user_ip varchar(45) NOT NULL,
        PRIMARY KEY  (id),
        INDEX message_email_idx (message_id, email_hash)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}


// Include plugin files
require_once plugin_dir_path( __FILE__ ) . 'includes/roles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/assets.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/cpt.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/taxonomies.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/cron.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/data-lists.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/utils.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/access-control.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ics-generator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ical.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/pdf-generator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/toolbar.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/rest-api.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/mailer.php';

if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/menu.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/message-actions.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/metaboxes.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/columns.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/columns-message.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/backup-restore-adherent.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/mailing.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/backup-restore-adherent-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/user-assignment.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/view-adherent-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/backup-restore-agenda.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/backup-restore-agenda-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/report-message-opens.php';
}


/**
 * Load plugin textdomain.
 */
function dame_load_textdomain() {
    load_plugin_textdomain( 'dame', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'dame_load_textdomain' );

/**
 * Main activation hook for the plugin.
 *
 * This function is called when the plugin is activated. It sets up custom roles,
 * schedules cron events, and ensures the database is up to date.
 */
function dame_plugin_activation() {
    // Set up custom roles.
    dame_add_custom_roles();

    // Schedule cron events.
    dame_schedule_backup_event();
    dame_schedule_birthday_event();

    // Ensure database tables are created.
    dame_v3_4_0_create_message_opens_table();
}
register_activation_hook( __FILE__, 'dame_plugin_activation' );

/**
 * Main deactivation hook for the plugin.
 */
function dame_plugin_deactivation() {
    dame_remove_custom_roles();
    dame_unschedule_backup_event();
    dame_unschedule_birthday_event();
}
register_deactivation_hook( __FILE__, 'dame_plugin_deactivation' );


// Register hooks
add_action( 'dame_daily_backup_event', 'dame_do_scheduled_backup' );
add_action( 'dame_birthday_email_event', 'dame_send_birthday_emails' );

add_action( 'update_option_dame_options', 'dame_handle_schedule_update', 10, 2 );

/**
 * Reschedules the backup event if the time has changed in the settings.
 *
 * @param mixed $old_value The old option value.
 * @param mixed $new_value The new option value.
 */
function dame_handle_schedule_update( $old_value, $new_value ) {
    $old_time = isset( $old_value['backup_time'] ) ? $old_value['backup_time'] : '';
    $new_time = isset( $new_value['backup_time'] ) ? $new_value['backup_time'] : '';

    $old_birthday_enabled = isset( $old_value['birthday_emails_enabled'] ) ? $old_value['birthday_emails_enabled'] : 0;
    $new_birthday_enabled = isset( $new_value['birthday_emails_enabled'] ) ? $new_value['birthday_emails_enabled'] : 0;

    if ( $old_time !== $new_time ) {
        dame_schedule_backup_event();
    }

    // Reschedule birthday event if the backup time or the enabled status changes.
    if ( $old_time !== $new_time || $old_birthday_enabled !== $new_birthday_enabled ) {
        dame_schedule_birthday_event();
    }
}

/**
 * Ensures the backup event is always scheduled. Acts as a failsafe.
 */
function dame_ensure_backup_is_scheduled() {
    if ( ! is_admin() ) {
        return;
    }
    if ( ! wp_next_scheduled( 'dame_daily_backup_event' ) ) {
        dame_schedule_backup_event();
    }
}
add_action( 'init', 'dame_ensure_backup_is_scheduled' );

/**
 * Ensures the birthday email event is always scheduled. Acts as a failsafe.
 */
function dame_ensure_birthday_is_scheduled() {
    if ( ! is_admin() ) {
        return;
    }

    $options = get_option( 'dame_options' );
    $enabled = isset( $options['birthday_emails_enabled'] ) ? $options['birthday_emails_enabled'] : 0;

    if ( $enabled && ! wp_next_scheduled( 'dame_birthday_email_event' ) ) {
        dame_schedule_birthday_event();
    }
}
add_action( 'init', 'dame_ensure_birthday_is_scheduled' );
