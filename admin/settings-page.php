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

/**
 * Determines the name of the next season based on the current active season.
 *
 * @return string The name of the next season (e.g., "Saison 2025/2026").
 */
function dame_get_next_season_name() {
    $current_season_tag_id = get_option( 'dame_current_season_tag_id' );

    if ( $current_season_tag_id ) {
        $current_season_term = get_term( $current_season_tag_id, 'dame_saison_adhesion' );
        if ( $current_season_term && ! is_wp_error( $current_season_term ) ) {
            // Extract the years from the current season name, e.g., "Saison 2024/2025"
            if ( preg_match( '/(\d{4})\/(\d{4})/', $current_season_term->name, $matches ) ) {
                $end_year = (int) $matches[2];
                $next_season_start_year = $end_year;
                $next_season_end_year   = $next_season_start_year + 1;
                return sprintf( 'Saison %d/%d', $next_season_start_year, $next_season_end_year );
            }
        }
    }

    // Fallback for the very first season or if the current season name is in an unexpected format.
    // A new season is considered to start in September.
    $current_month     = (int) date( 'n' );
    $current_year      = (int) date( 'Y' );
    $season_start_year = ( $current_month >= 9 ) ? $current_year + 1 : $current_year;
    $season_end_year   = $season_start_year + 1;

    return sprintf( 'Saison %d/%d', $season_start_year, $season_end_year );
}

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
 * Register settings, sections, and fields.
 */
function dame_register_settings() {
    register_setting( 'dame_options_group', 'dame_options', 'dame_options_sanitize' );

    // Section for Association
    add_settings_section(
        'dame_association_section',
        __( "Informations de l'association", 'dame' ),
        'dame_association_section_callback',
        'dame_association_section_group'
    );

    add_settings_field(
        'dame_assoc_address_1',
        __( 'Adresse', 'dame' ),
        'dame_assoc_address_1_callback',
        'dame_association_section_group',
        'dame_association_section'
    );

    add_settings_field(
        'dame_assoc_address_2',
        __( 'Complément', 'dame' ),
        'dame_assoc_address_2_callback',
        'dame_association_section_group',
        'dame_association_section'
    );

    add_settings_field(
        'dame_assoc_postal_code',
        __( 'Code Postal', 'dame' ),
        'dame_assoc_postal_code_callback',
        'dame_association_section_group',
        'dame_association_section'
    );

    add_settings_field(
        'dame_assoc_city',
        __( 'Ville', 'dame' ),
        'dame_assoc_city_callback',
        'dame_association_section_group',
        'dame_association_section'
    );

    add_settings_field(
        'dame_assoc_latitude',
        __( 'Latitude', 'dame' ),
        'dame_assoc_latitude_callback',
        'dame_association_section_group',
        'dame_association_section'
    );

    add_settings_field(
        'dame_assoc_longitude',
        __( 'Longitude', 'dame' ),
        'dame_assoc_longitude_callback',
        'dame_association_section_group',
        'dame_association_section'
    );

    // Section pour le mailing
    add_settings_section(
        'dame_mailing_section',
        __( 'Paramètres d\'envoi d\'email', 'dame' ),
        'dame_mailing_section_callback',
        'dame_mailing_section_group'
    );

    add_settings_field(
        'dame_sender_email',
        __( "Email de l'expéditeur", 'dame' ),
        'dame_sender_email_callback',
        'dame_mailing_section_group',
        'dame_mailing_section'
    );

    // SMTP settings fields
    add_settings_field(
        'dame_smtp_host',
        __( 'Hôte SMTP', 'dame' ),
        'dame_smtp_host_callback',
        'dame_mailing_section_group',
        'dame_mailing_section'
    );

    add_settings_field(
        'dame_smtp_port',
        __( 'Port SMTP', 'dame' ),
        'dame_smtp_port_callback',
        'dame_mailing_section_group',
        'dame_mailing_section'
    );

    add_settings_field(
        'dame_smtp_encryption',
        __( 'Chiffrement', 'dame' ),
        'dame_smtp_encryption_callback',
        'dame_mailing_section_group',
        'dame_mailing_section'
    );

    add_settings_field(
        'dame_smtp_username',
        __( 'Nom d\'utilisateur SMTP', 'dame' ),
        'dame_smtp_username_callback',
        'dame_mailing_section_group',
        'dame_mailing_section'
    );

    add_settings_field(
        'dame_smtp_password',
        __( 'Mot de passe SMTP', 'dame' ),
        'dame_smtp_password_callback',
        'dame_mailing_section_group',
        'dame_mailing_section'
    );

    add_settings_field(
        'dame_smtp_batch_size',
        __( "Taille des lots d'envoi", 'dame' ),
        'dame_smtp_batch_size_callback',
        'dame_mailing_section_group',
        'dame_mailing_section'
    );

    // Section for Birthday Emails
    add_settings_section(
        'dame_birthday_section',
        __( "Emails d'anniversaire", 'dame' ),
        'dame_birthday_section_callback',
        'dame_birthday_section_group'
    );

    add_settings_field(
        'dame_birthday_emails_enabled',
        __( "Activer les emails d'anniversaire", 'dame' ),
        'dame_birthday_emails_enabled_callback',
        'dame_birthday_section_group',
        'dame_birthday_section'
    );

    add_settings_field(
        'dame_birthday_article_slug',
        __( "Slug de l'article pour l'anniversaire", 'dame' ),
        'dame_birthday_article_slug_callback',
        'dame_birthday_section_group',
        'dame_birthday_section'
    );

    add_settings_field(
        'dame_birthday_test_email',
        __( 'Email de test', 'dame' ),
        'dame_birthday_test_email_callback',
        'dame_birthday_section_group',
        'dame_birthday_section'
    );

    // Section for Backup Settings
    add_settings_section(
        'dame_backup_section',
        __( 'Paramètres de sauvegarde', 'dame' ),
        'dame_backup_section_callback',
        'dame_backup_section_group'
    );

    add_settings_field(
        'dame_backup_time',
        __( 'Heure de la sauvegarde journalière', 'dame' ),
        'dame_backup_time_callback',
        'dame_backup_section_group',
        'dame_backup_section'
    );

    // Section for payment URL
    add_settings_section(
        'dame_payment_section',
        __( 'Paramètres de paiement', 'dame' ),
        'dame_payment_section_callback',
        'dame_payment_section_group'
    );

    add_settings_field(
        'dame_payment_url',
        __( 'URL de paiement (PayAsso)', 'dame' ),
        'dame_payment_url_callback',
        'dame_payment_section_group',
        'dame_payment_section'
    );

    add_settings_section(
        'dame_uninstall_section',
        __( 'Désinstallation', 'dame' ),
        'dame_uninstall_section_callback',
        'dame_uninstall_section_group'
    );

    add_settings_field(
        'dame_delete_on_uninstall',
        __( 'Suppression des données', 'dame' ),
        'dame_delete_on_uninstall_callback',
        'dame_uninstall_section_group',
        'dame_uninstall_section'
    );
}
add_action( 'admin_init', 'dame_register_settings' );

/**
 * Handle actions related to season management (creation and selection).
 */
function dame_handle_season_actions() {
    // Check for nonce presence and validity for all actions in this section
    if ( isset( $_POST['dame_season_management_nonce_field'] ) && wp_verify_nonce( $_POST['dame_season_management_nonce_field'], 'dame_season_management_nonce' ) ) {

        // Handle creation of a new season
        if ( isset( $_POST['dame_action'] ) && 'annual_reset' === $_POST['dame_action'] ) {
            $new_season_name = dame_get_next_season_name();

            if ( term_exists( $new_season_name, 'dame_saison_adhesion' ) ) {
                add_action(
                    'admin_notices',
                    function() use ( $new_season_name ) {
                        $message = sprintf(
                            esc_html__( 'L\'opération ne peut être effectuée car la saison "%s" a déjà été créée.', 'dame' ),
                            esc_html( $new_season_name )
                        );
                        echo '<div class="error"><p>' . $message . '</p></div>';
                    }
                );
                return;
            }

            $new_season_term = wp_insert_term( $new_season_name, 'dame_saison_adhesion' );

            if ( is_wp_error( $new_season_term ) ) {
                add_action(
                    'admin_notices',
                    function() use ( $new_season_term ) {
                        $message = sprintf(
                            esc_html__( 'Erreur lors de la création de la saison : %s', 'dame' ),
                            $new_season_term->get_error_message()
                        );
                        echo '<div class="error"><p>' . $message . '</p></div>';
                    }
                );
                return;
            }

            update_option( 'dame_current_season_tag_id', $new_season_term['term_id'] );

            add_action(
                'admin_notices',
                function() use ( $new_season_name ) {
                    $message = sprintf(
                        esc_html__( 'Nouvelle saison initialisée avec succès. La saison active est maintenant : %s', 'dame' ),
                        '<strong>' . esc_html( $new_season_name ) . '</strong>'
                    );
                    echo '<div class="updated"><p>' . $message . '</p></div>';
                }
            );
        }

        // Handle updating the current season from the dropdown
        if ( isset( $_POST['dame_action'] ) && 'update_current_season' === $_POST['dame_action'] ) {
            if ( isset( $_POST['dame_current_season_selector'] ) ) {
                $selected_season_id = (int) $_POST['dame_current_season_selector'];
                $term = get_term( $selected_season_id, 'dame_saison_adhesion' );

                if ( $term && ! is_wp_error( $term ) ) {
                    update_option( 'dame_current_season_tag_id', $selected_season_id );

                    add_action(
                        'admin_notices',
                        function() use ( $term ) {
                            $message = sprintf(
                                esc_html__( 'La saison active a été mise à jour : %s', 'dame' ),
                                '<strong>' . esc_html( $term->name ) . '</strong>'
                            );
                            echo '<div class="updated"><p>' . $message . '</p></div>';
                        }
                    );
                }
            }
        }
    } elseif ( isset( $_POST['dame_action'] ) && ( 'annual_reset' === $_POST['dame_action'] || 'update_current_season' === $_POST['dame_action'] ) ) {
        // Handle nonce failure
        wp_die( 'Security check failed.' );
    }
}
add_action( 'admin_init', 'dame_handle_season_actions' );

/**
 * Handle sending the test birthday email.
 */
function dame_handle_send_test_birthday_email() {
    if ( isset( $_POST['dame_action'] ) && 'send_test_birthday_email' === $_POST['dame_action'] ) {
        if ( ! isset( $_POST['dame_send_test_birthday_email_nonce_field'] ) || ! wp_verify_nonce( $_POST['dame_send_test_birthday_email_nonce_field'], 'dame_send_test_birthday_email_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to perform this action.' );
        }

        $options = get_option( 'dame_options' );
        $article_slug = isset( $options['birthday_article_slug'] ) ? $options['birthday_article_slug'] : '';

        if ( empty( $article_slug ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="error"><p>' . esc_html__( "Veuillez d'abord enregistrer un slug d'article pour l'anniversaire.", 'dame' ) . '</p></div>';
            } );
            return;
        }

        $posts = get_posts( array(
            'name'           => $article_slug,
            'post_type'      => 'post',
            'post_status'    => array( 'publish', 'private' ),
            'posts_per_page' => 1,
        ) );

        if ( ! $posts ) {
            add_action( 'admin_notices', function() use ( $article_slug ) {
                $message = sprintf(
                    esc_html__( "L'article avec le slug '%s' n'a pas été trouvé.", 'dame' ),
                    esc_html( $article_slug )
                );
                echo '<div class="error"><p>' . $message . '</p></div>';
            } );
            return;
        }
        $article = $posts[0];

        $current_user = wp_get_current_user();
        $recipient_email = $current_user->user_email;

        $original_content = apply_filters( 'the_content', $article->post_content );
        $original_subject = $article->post_title;

        // Replace placeholders with sample data
        $content = str_replace( '[NOM]', 'DUPONT', $original_content );
        $content = str_replace( '[PRENOM]', 'Jean', $content );
        $content = str_replace( '[AGE]', '30', $content );
        $message = '<div style="margin: 1cm;">' . $content . '</div>';

        $subject = str_replace( '[NOM]', 'DUPONT', $original_subject );
        $subject = str_replace( '[PRENOM]', 'Jean', $subject );
        $subject = str_replace( '[AGE]', '30', $subject );

        $sender_email = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo( 'name' ) . ' <' . $sender_email . '>',
        );

        $sent = wp_mail( $recipient_email, $subject, $message, $headers );

        if ( $sent ) {
            add_action( 'admin_notices', function() use ( $recipient_email ) {
                $message = sprintf(
                    esc_html__( "Email de test envoyé avec succès à %s.", 'dame' ),
                    esc_html( $recipient_email )
                );
                echo '<div class="updated"><p>' . $message . '</p></div>';
            } );
        } else {
            add_action( 'admin_notices', function() {
                echo '<div class="error"><p>' . esc_html__( "Échec de l'envoi de l'email de test. Vérifiez vos réglages SMTP.", 'dame' ) . '</p></div>';
            } );
        }
    }
}
add_action( 'admin_init', 'dame_handle_send_test_birthday_email' );

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

/**
 * Callback for the association section.
 */
function dame_association_section_callback() {
    echo '<p>' . esc_html__( "Saisir ici les informations relatives à l'adresse de l'association. L'autocomplétion est activée sur le champ Adresse.", 'dame' ) . '</p>';
}

/**
 * Callbacks for Association settings fields.
 */
function dame_assoc_address_1_callback() {
    $options = get_option( 'dame_options' );
    $value = isset( $options['assoc_address_1'] ) ? $options['assoc_address_1'] : '';
    ?>
    <div class="dame-autocomplete-wrapper" style="position: relative;">
        <input type="text" id="dame_assoc_address_1" name="dame_options[assoc_address_1]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off" />
    </div>
    <?php
}

function dame_assoc_address_2_callback() {
    $options = get_option( 'dame_options' );
    $value = isset( $options['assoc_address_2'] ) ? $options['assoc_address_2'] : '';
    ?>
    <input type="text" id="dame_assoc_address_2" name="dame_options[assoc_address_2]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
    <?php
}

function dame_assoc_postal_code_callback() {
    $options = get_option( 'dame_options' );
    $value = isset( $options['assoc_postal_code'] ) ? $options['assoc_postal_code'] : '';
    ?>
    <input type="text" id="dame_assoc_postal_code" name="dame_options[assoc_postal_code]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
    <?php
}

function dame_assoc_city_callback() {
    $options = get_option( 'dame_options' );
    $value = isset( $options['assoc_city'] ) ? $options['assoc_city'] : '';
    ?>
    <input type="text" id="dame_assoc_city" name="dame_options[assoc_city]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
    <?php
}

function dame_assoc_latitude_callback() {
    $options = get_option( 'dame_options' );
    $value = isset( $options['assoc_latitude'] ) ? $options['assoc_latitude'] : '';
    ?>
    <input type="text" id="dame_assoc_latitude" name="dame_options[assoc_latitude]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" readonly="readonly" />
    <?php
}

function dame_assoc_longitude_callback() {
    $options = get_option( 'dame_options' );
    $value = isset( $options['assoc_longitude'] ) ? $options['assoc_longitude'] : '';
    ?>
    <input type="text" id="dame_assoc_longitude" name="dame_options[assoc_longitude]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" readonly="readonly" />
    <?php
}


/**
 * Callback for the backup section.
 */
function dame_backup_section_callback() {
    echo '<p>' . esc_html__( "Paramètres relatifs à la sauvegarde automatique journalière.", 'dame' ) . '</p>';
}

/**
 * Callback for the backup_time field.
 */
function dame_backup_time_callback() {
    $options = get_option( 'dame_options' );
    $backup_time = isset( $options['backup_time'] ) ? $options['backup_time'] : '';
    ?>
    <input type="text" id="dame_backup_time" name="dame_options[backup_time]" value="<?php echo esc_attr( $backup_time ); ?>" class="regular-text" placeholder="HH:MM" style="width: 100px;" />
    <p class="description">
        <?php esc_html_e( "Saisir l'heure de déclenchement de la sauvegarde journalière (par ex. 01:00). Utilise le fuseau horaire du serveur.", 'dame' ); ?>
    </p>
    <?php
}

/**
 * Callback for the birthday test email button.
 */
function dame_birthday_test_email_callback() {
    ?>
    <form method="post">
        <input type="hidden" name="dame_action" value="send_test_birthday_email" />
        <?php wp_nonce_field( 'dame_send_test_birthday_email_nonce', 'dame_send_test_birthday_email_nonce_field' ); ?>
        <?php submit_button( __( "Envoyer un email de test", 'dame' ), 'secondary', 'dame_send_test_email', false ); ?>
        <p class="description">
            <?php esc_html_e( "Envoie un exemple de l'email d'anniversaire à votre adresse email.", 'dame' ); ?>
        </p>
    </form>
    <?php
}

/**
 * Callback for the payment section.
 */
function dame_payment_section_callback() {
    echo '<p>' . esc_html__( "Paramètres relatifs au paiement des adhésions.", 'dame' ) . '</p>';
}

/**
 * Callback for the payment_url field.
 */
function dame_payment_url_callback() {
    $options = get_option( 'dame_options' );
    $payment_url = isset( $options['payment_url'] ) ? $options['payment_url'] : '';
    ?>
    <input type="url" id="dame_payment_url" name="dame_options[payment_url]" value="<?php echo esc_attr( $payment_url ); ?>" class="regular-text" placeholder="https://www.payasso.fr/example/form" />
    <p class="description">
        <?php esc_html_e( "L'URL complète de la page de paiement. Ce lien sera présenté à l'utilisateur après la soumission de sa préinscription.", 'dame' ); ?>
    </p>
    <?php
}

/**
 * Callback for the birthday section.
 */
function dame_birthday_section_callback() {
    echo '<p>' . esc_html__( "Paramètres pour l'envoi automatique des emails d'anniversaire aux adhérents.", 'dame' ) . '</p>';
}

/**
 * Callback for the mailing section.
 */
function dame_mailing_section_callback() {
    echo '<p>' . esc_html__( "Paramètres relatifs à l'envoi d'emails depuis le plugin. Pour utiliser un service SMTP externe (recommandé), remplissez les champs ci-dessous.", 'dame' ) . '</p>';
}

/**
 * Callback for the sender_email field.
 */
function dame_sender_email_callback() {
    $options = get_option( 'dame_options' );
    $sender_email = isset( $options['sender_email'] ) ? $options['sender_email'] : '';
    ?>
    <input type="email" id="dame_sender_email" name="dame_options[sender_email]" value="<?php echo esc_attr( $sender_email ); ?>" class="regular-text" />
    <p class="description">
        <?php esc_html_e( "L'adresse email qui sera utilisée comme expéditeur. Doit correspondre au nom d'utilisateur SMTP si utilisé.", 'dame' ); ?>
    </p>
    <?php
}

/**
 * Callbacks for SMTP settings fields.
 */
function dame_smtp_host_callback() {
    $options = get_option( 'dame_options' );
    $smtp_host = isset( $options['smtp_host'] ) ? $options['smtp_host'] : '';
    ?>
    <input type="text" id="dame_smtp_host" name="dame_options[smtp_host]" value="<?php echo esc_attr( $smtp_host ); ?>" class="regular-text" />
    <?php
}

function dame_smtp_port_callback() {
    $options = get_option( 'dame_options' );
    $smtp_port = isset( $options['smtp_port'] ) ? $options['smtp_port'] : '';
    ?>
    <input type="number" id="dame_smtp_port" name="dame_options[smtp_port]" value="<?php echo esc_attr( $smtp_port ); ?>" class="small-text" />
    <p class="description">
        <?php esc_html_e( 'Ex: 465 pour SSL, 587 pour TLS.', 'dame' ); ?>
    </p>
    <?php
}

function dame_smtp_encryption_callback() {
    $options = get_option( 'dame_options' );
    $smtp_encryption = isset( $options['smtp_encryption'] ) ? $options['smtp_encryption'] : 'none';
    ?>
    <select id="dame_smtp_encryption" name="dame_options[smtp_encryption]">
        <option value="none" <?php selected( $smtp_encryption, 'none' ); ?>><?php esc_html_e( 'Aucun', 'dame' ); ?></option>
        <option value="ssl" <?php selected( $smtp_encryption, 'ssl' ); ?>>SSL</option>
        <option value="tls" <?php selected( $smtp_encryption, 'tls' ); ?>>TLS</option>
    </select>
    <?php
}

function dame_smtp_username_callback() {
    $options = get_option( 'dame_options' );
    $smtp_username = isset( $options['smtp_username'] ) ? $options['smtp_username'] : '';
    ?>
    <input type="text" id="dame_smtp_username" name="dame_options[smtp_username]" value="<?php echo esc_attr( $smtp_username ); ?>" class="regular-text" />
    <?php
}

function dame_smtp_password_callback() {
    ?>
    <input type="password" id="dame_smtp_password" name="dame_options[smtp_password]" value="" class="regular-text" autocomplete="new-password" />
    <p class="description">
        <?php esc_html_e( 'Laissez vide pour ne pas modifier le mot de passe existant.', 'dame' ); ?>
    </p>
    <?php
}

function dame_smtp_batch_size_callback() {
    $options = get_option( 'dame_options' );
    $smtp_batch_size = isset( $options['smtp_batch_size'] ) ? $options['smtp_batch_size'] : 20;
    ?>
    <input type="number" id="dame_smtp_batch_size" name="dame_options[smtp_batch_size]" value="<?php echo esc_attr( $smtp_batch_size ); ?>" class="small-text" min="0" />
    <p class="description">
        <?php esc_html_e( "Nombre d'emails à envoyer dans chaque lot. Mettre à 0 pour envoyer tous les emails en une seule fois (non recommandé pour un grand nombre de destinataires).", 'dame' ); ?>
    </p>
    <?php
}

/**
 * Callback for the birthday emails enabled checkbox.
 */
function dame_birthday_emails_enabled_callback() {
    $options = get_option( 'dame_options' );
    $checked = isset( $options['birthday_emails_enabled'] ) ? $options['birthday_emails_enabled'] : 0;
    ?>
    <label>
        <input type="checkbox" name="dame_options[birthday_emails_enabled]" value="1" <?php checked( $checked, 1 ); ?> />
        <?php esc_html_e( 'Cochez cette case pour activer l\'envoi automatique des emails d\'anniversaire.', 'dame' ); ?>
    </label>
    <?php
}

/**
 * Callback for the birthday article slug field.
 */
function dame_birthday_article_slug_callback() {
    $options = get_option( 'dame_options' );
    $birthday_article_slug = isset( $options['birthday_article_slug'] ) ? $options['birthday_article_slug'] : '';
    ?>
    <input type="text" id="dame_birthday_article_slug" name="dame_options[birthday_article_slug]" value="<?php echo esc_attr( $birthday_article_slug ); ?>" class="regular-text" />
    <p class="description">
        <?php esc_html_e( "Saisir le slug de l'article qui sera envoyé pour les anniversaires. Le contenu de l'article peut contenir les balises [NOM], [PRENOM] et [AGE].", 'dame' ); ?>
    </p>
    <?php
}


/**
 * Callback for the uninstall section.
 */
function dame_uninstall_section_callback() {
    echo '<p>' . esc_html__( 'Gérer les options relatives à la désinstallation du plugin.', 'dame' ) . '</p>';
}

/**
 * Callback for the delete_on_uninstall field.
 */
function dame_delete_on_uninstall_callback() {
    $options = get_option( 'dame_options' );
    $checked = isset( $options['delete_on_uninstall'] ) ? $options['delete_on_uninstall'] : 0;
    ?>
    <label>
        <input type="checkbox" name="dame_options[delete_on_uninstall]" value="1" <?php checked( $checked, 1 ); ?> />
        <?php esc_html_e( 'Cochez cette case pour supprimer toutes les données du plugin (adhérents, etc.) lors de sa suppression.', 'dame' ); ?>
    </label>
    <p class="description"><?php _e( 'Attention : cette action est irréversible.', 'dame' ); ?></p>
    <?php
}

/**
 * Renders the entire UI for the annual season management section.
 */
function dame_annual_reset_section_ui() {
    // Get all available seasons
    $seasons = get_terms( array(
        'taxonomy'   => 'dame_saison_adhesion',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'DESC',
    ) );

    $current_season_tag_id = get_option( 'dame_current_season_tag_id' );
    ?>
    <div>
        <!-- Top section: Season Selection -->
        <div>
            <h3><?php esc_html_e( "Saison Active", 'dame' ); ?></h3>
            <p><?php esc_html_e( "Sélectionnez la saison d'adhésion à utiliser comme saison active sur l'ensemble du site.", 'dame' ); ?></p>
            <form method="post">
                <input type="hidden" name="dame_action" value="update_current_season">
                <?php wp_nonce_field( 'dame_season_management_nonce', 'dame_season_management_nonce_field' ); ?>

                <label for="dame_current_season_selector" style="font-weight: bold;"><?php esc_html_e( 'Saison active :', 'dame' ); ?></label>
                <select id="dame_current_season_selector" name="dame_current_season_selector" style="margin-right: 10px;">
                    <?php if ( ! empty( $seasons ) && ! is_wp_error( $seasons ) ) : ?>
                        <?php foreach ( $seasons as $season ) : ?>
                            <option value="<?php echo esc_attr( $season->term_id ); ?>" <?php selected( $season->term_id, $current_season_tag_id ); ?>>
                                <?php echo esc_html( $season->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <option value=""><?php esc_html_e( 'Aucune saison trouvée', 'dame' ); ?></option>
                    <?php endif; ?>
                </select>
                <?php submit_button( __( 'Changer la saison active', 'dame' ), 'secondary', 'dame_update_season', false ); ?>
            </form>
        </div>

        <hr style="margin: 20px 0;">

        <!-- Bottom section: Create New Season -->
        <div>
            <h3><?php esc_html_e( "Nouvelle Saison", 'dame' ); ?></h3>
            <p><?php esc_html_e( 'Cette action prépare le système pour la prochaine saison d\'adhésion en créant le nouveau tag.', 'dame' ); ?></p>
            <?php
            $next_season_name = dame_get_next_season_name();
            $disabled = term_exists( $next_season_name, 'dame_saison_adhesion' ) ? 'disabled' : '';
            ?>
            <form method="post">
                <input type="hidden" name="dame_action" value="annual_reset" />
                <?php wp_nonce_field( 'dame_season_management_nonce', 'dame_season_management_nonce_field' ); ?>
                <?php submit_button( __( 'Initialiser la nouvelle saison', 'dame' ), 'primary', 'dame_annual_reset', false, $disabled ); ?>
                <p class="description">
                    <?php
                    if ( $disabled ) {
                        echo esc_html( sprintf( __( 'La saison "%s" a déjà été créée.', 'dame' ), $next_season_name ) );
                    } else {
                        echo esc_html( sprintf( __( 'Cette action créera et activera la saison "%s".', 'dame' ), $next_season_name ) );
                    }
                    ?>
                </p>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resetButton = document.getElementById('dame_annual_reset');
            if (resetButton) {
                resetButton.addEventListener('click', function(e) {
                    if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir initialiser la nouvelle saison ? Cela créera un nouveau tag et le définira comme saison active.', 'dame' ) ); ?>")) {
                        e.preventDefault();
                    } else {
                        setTimeout(function() { resetButton.disabled = true; }, 0);
                    }
                });
            }
        });
    </script>
    <?php
}
