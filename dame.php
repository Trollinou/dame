<?php
/**
 * Plugin Name:       DAME - Dossier et Apprentissage des Membres Échiquéens
 * Plugin URI:
 * Description:       Gère une base de données d'adhérents pour un club.
 * Version:           3.0.5
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

define( 'DAME_VERSION', '3.0.5' );
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


// Include plugin files
require_once plugin_dir_path( __FILE__ ) . 'includes/roles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/assets.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/cpt.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/taxonomies.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/cron.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/data-lists.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/utils.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/access-control.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/lesson-completion.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/pdf-generator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/single-exercice-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/single-course-handler.php';

if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/menu.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/metaboxes.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/columns.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/import-export.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/mailing.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/import-export-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/user-assignment.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/backup-restore.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/backup-restore-page.php';
}


/**
 * Load plugin textdomain.
 */
function dame_load_textdomain() {
    load_plugin_textdomain( 'dame', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'dame_load_textdomain' );

// Register hooks
register_activation_hook( __FILE__, 'dame_add_custom_roles' );
register_deactivation_hook( __FILE__, 'dame_remove_custom_roles' );

// Cron job for daily backups
register_activation_hook( __FILE__, 'dame_schedule_backup_event' );
register_deactivation_hook( __FILE__, 'dame_unschedule_backup_event' );
add_action( 'dame_daily_backup_event', 'dame_do_scheduled_backup' );
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

    if ( $old_time !== $new_time ) {
        dame_schedule_backup_event();
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
