<?php
/**
 * File for handling the plugin's settings page.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Include all the tab files.
require_once __DIR__ . '/settings/tab-association.php';
require_once __DIR__ . '/settings/tab-saisons.php';
require_once __DIR__ . '/settings/tab-anniversaires.php';
require_once __DIR__ . '/settings/tab-paiements.php';
require_once __DIR__ . '/settings/tab-sauvegarde.php';
require_once __DIR__ . '/settings/tab-emails.php';
require_once __DIR__ . '/settings/tab-desinstallation.php';


/**
 * Add the options page to the Settings menu.
 */
function dame_add_options_page() {
    add_options_page(
        __( 'Options DAME', 'dame' ),
        __( 'Options DAME', 'dame' ),
        'manage_options',
        'dame-settings',
        'dame_render_options_page'
    );
}
add_action( 'admin_menu', 'dame_add_options_page' );

/**
 * Renders the options page wrapper.
 */
function dame_render_options_page() {
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'association';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="?page=dame-settings&tab=association" class="nav-tab <?php echo $active_tab === 'association' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Association', 'dame' ); ?></a>
            <a href="?page=dame-settings&tab=saisons" class="nav-tab <?php echo $active_tab === 'saisons' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Saisons', 'dame' ); ?></a>
            <a href="?page=dame-settings&tab=anniversaires" class="nav-tab <?php echo $active_tab === 'anniversaires' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Anniversaires', 'dame' ); ?></a>
            <a href="?page=dame-settings&tab=paiements" class="nav-tab <?php echo $active_tab === 'paiements' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Paiements', 'dame' ); ?></a>
            <a href="?page=dame-settings&tab=sauvegarde" class="nav-tab <?php echo $active_tab === 'sauvegarde' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Sauvegarde', 'dame' ); ?></a>
            <a href="?page=dame-settings&tab=emails" class="nav-tab <?php echo $active_tab === 'emails' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Emails', 'dame' ); ?></a>
            <a href="?page=dame-settings&tab=desinstallation" class="nav-tab <?php echo $active_tab === 'desinstallation' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Désinstallation', 'dame' ); ?></a>
        </h2>

        <form action="options.php" method="post">
            <?php
            settings_fields( 'dame_options_group' );

            // Add a hidden field to identify the active tab
            echo '<input type="hidden" name="dame_active_tab" value="' . esc_attr( $active_tab ) . '" />';

            if ( $active_tab === 'association' ) {
                do_settings_sections( 'dame_association_section_group' );
            } elseif ( $active_tab === 'saisons' ) {
                // This is custom UI, not a settings section
            } elseif ( $active_tab === 'anniversaires' ) {
                do_settings_sections( 'dame_birthday_section_group' );
            } elseif ( $active_tab === 'paiements' ) {
                do_settings_sections( 'dame_payment_section_group' );
            } elseif ( $active_tab === 'sauvegarde' ) {
                do_settings_sections( 'dame_backup_section_group' );
            } elseif ( $active_tab === 'emails' ) {
                do_settings_sections( 'dame_mailing_section_group' );
            } elseif ( $active_tab === 'desinstallation' ) {
                do_settings_sections( 'dame_uninstall_section_group' );
            }

            // The submit button should only appear on tabs that have settings fields.
            if ( $active_tab !== 'saisons' ) {
                submit_button( __( 'Enregistrer les modifications', 'dame' ) );
            }
            ?>
        </form>

        <?php
        // The season management UI has its own forms, so it's outside the main form.
        if ( $active_tab === 'saisons' ) {
            dame_annual_reset_section_ui();
        }
        ?>
    </div>
    <?php
}

/**
 * Register the main setting.
 * The sections and fields are now registered in their respective tab files.
 */
function dame_register_main_setting() {
    register_setting( 'dame_options_group', 'dame_options', 'dame_options_sanitize' );
}
add_action( 'admin_init', 'dame_register_main_setting' );

/**
 * Sanitize the options array based on the active tab.
 */
function dame_options_sanitize( $input ) {
    // Get the existing options from the database.
    $options = get_option( 'dame_options', array() );

    // Determine which tab is being saved.
    $active_tab = isset( $_POST['dame_active_tab'] ) ? sanitize_key( $_POST['dame_active_tab'] ) : '';

    $dame_options = isset( $_POST['dame_options'] ) ? $_POST['dame_options'] : array();

    switch ( $active_tab ) {
        case 'association':
            if ( isset( $dame_options['assoc_address_1'] ) ) {
                $options['assoc_address_1'] = sanitize_text_field( $dame_options['assoc_address_1'] );
            }
            if ( isset( $dame_options['assoc_address_2'] ) ) {
                $options['assoc_address_2'] = sanitize_text_field( $dame_options['assoc_address_2'] );
            }
            if ( isset( $dame_options['assoc_postal_code'] ) ) {
                $options['assoc_postal_code'] = sanitize_text_field( $dame_options['assoc_postal_code'] );
            }
            if ( isset( $dame_options['assoc_city'] ) ) {
                $options['assoc_city'] = sanitize_text_field( $dame_options['assoc_city'] );
            }
            if ( isset( $dame_options['assoc_latitude'] ) ) {
                $options['assoc_latitude'] = sanitize_text_field( $dame_options['assoc_latitude'] );
            }
            if ( isset( $dame_options['assoc_longitude'] ) ) {
                $options['assoc_longitude'] = sanitize_text_field( $dame_options['assoc_longitude'] );
            }
            break;

        case 'emails':
            if ( isset( $dame_options['sender_email'] ) ) {
                $options['sender_email'] = sanitize_email( $dame_options['sender_email'] );
            }
            if ( isset( $dame_options['smtp_host'] ) ) {
                $options['smtp_host'] = sanitize_text_field( $dame_options['smtp_host'] );
            }
            if ( isset( $dame_options['smtp_port'] ) ) {
                $options['smtp_port'] = absint( $dame_options['smtp_port'] );
            }
            if ( isset( $dame_options['smtp_encryption'] ) && in_array( $dame_options['smtp_encryption'], array( 'none', 'ssl', 'tls' ) ) ) {
                $options['smtp_encryption'] = $dame_options['smtp_encryption'];
            }
            if ( isset( $dame_options['smtp_username'] ) ) {
                $options['smtp_username'] = sanitize_text_field( $dame_options['smtp_username'] );
            }
            // Only update the password if a new value is provided.
            if ( ! empty( $dame_options['smtp_password'] ) ) {
                $options['smtp_password'] = trim( $dame_options['smtp_password'] );
            }
            if ( isset( $dame_options['smtp_batch_size'] ) ) {
                $options['smtp_batch_size'] = absint( $dame_options['smtp_batch_size'] );
            }
            break;

        case 'anniversaires':
            $options['birthday_emails_enabled'] = isset( $dame_options['birthday_emails_enabled'] ) ? 1 : 0;
            if ( isset( $dame_options['birthday_article_slug'] ) ) {
                $options['birthday_article_slug'] = sanitize_text_field( $dame_options['birthday_article_slug'] );
            }
            break;

        case 'paiements':
            if ( isset( $dame_options['payment_url'] ) ) {
                $options['payment_url'] = esc_url_raw( $dame_options['payment_url'] );
            }
            break;

        case 'sauvegarde':
            if ( isset( $dame_options['backup_time'] ) ) {
                $time = trim( $dame_options['backup_time'] );
                if ( preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time ) ) {
                    $options['backup_time'] = $time;
                } else {
                    $options['backup_time'] = ''; // Invalid format, save as empty.
                }
            }
            break;

        case 'desinstallation':
            $options['delete_on_uninstall'] = isset( $dame_options['delete_on_uninstall'] ) ? 1 : 0;
            break;
    }

    return $options;
}
