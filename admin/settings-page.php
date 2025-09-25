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
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form action="options.php" method="post">
            <?php
            settings_fields( 'dame_options_group' );
            do_settings_sections( 'dame_mailing_section_group' ); // Ajout du groupe de la section mailing
            do_settings_sections( 'dame_birthday_section_group' );
            do_settings_sections( 'dame_backup_section_group' );
            do_settings_sections( 'dame_payment_section_group' );
            do_settings_sections( 'dame_uninstall_section_group' );
            submit_button( __( 'Enregistrer les modifications', 'dame' ) );
            ?>
        </form>

        <hr>

        <h2><?php esc_html_e( 'Réinitialisation Annuelle des Adhésions', 'dame' ); ?></h2>
        <?php dame_reset_section_callback(); ?>
        <?php dame_reset_button_callback(); ?>

    </div>
    <?php
}

/**
 * Register settings, sections, and fields.
 */
function dame_register_settings() {
    register_setting( 'dame_options_group', 'dame_options', 'dame_options_sanitize' );

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
 * Handle the annual reset action.
 */
function dame_handle_annual_reset() {
    if ( isset( $_POST['dame_action'] ) && 'annual_reset' === $_POST['dame_action'] ) {
        if ( ! isset( $_POST['dame_annual_reset_nonce_field'] ) || ! wp_verify_nonce( $_POST['dame_annual_reset_nonce_field'], 'dame_annual_reset_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        $new_season_name = dame_get_next_season_name();

        // New, more robust check: see if the next season's tag already exists.
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
            // If the term already exists, we can still set it as active.
            if ( isset( $new_season_term->error_data['term_exists'] ) ) {
                $new_season_id = $new_season_term->error_data['term_exists'];
            } else {
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
        } else {
            $new_season_id = $new_season_term['term_id'];
        }

        update_option( 'dame_current_season_tag_id', $new_season_id );

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
}
add_action( 'admin_init', 'dame_handle_annual_reset' );


/**
 * Sanitize the options array.
 */
function dame_options_sanitize( $input ) {
    $options = get_option( 'dame_options' );
    $sanitized_input = array();

    if ( isset( $input['sender_email'] ) ) {
        $sanitized_input['sender_email'] = sanitize_email( $input['sender_email'] );
    }

    if ( isset( $input['smtp_host'] ) ) {
        $sanitized_input['smtp_host'] = sanitize_text_field( $input['smtp_host'] );
    }

    if ( isset( $input['smtp_port'] ) ) {
        $sanitized_input['smtp_port'] = absint( $input['smtp_port'] );
    }

    if ( isset( $input['smtp_encryption'] ) && in_array( $input['smtp_encryption'], array( 'none', 'ssl', 'tls' ) ) ) {
        $sanitized_input['smtp_encryption'] = $input['smtp_encryption'];
    }

    if ( isset( $input['smtp_username'] ) ) {
        $sanitized_input['smtp_username'] = sanitize_text_field( $input['smtp_username'] );
    }

    // Only update the password if a new value is provided.
    if ( ! empty( $input['smtp_password'] ) ) {
        $sanitized_input['smtp_password'] = trim( $input['smtp_password'] );
    } else {
        // Keep the old password if the field is empty.
        if ( isset( $options['smtp_password'] ) ) {
            $sanitized_input['smtp_password'] = $options['smtp_password'];
        }
    }

    if ( isset( $input['smtp_batch_size'] ) ) {
        $sanitized_input['smtp_batch_size'] = absint( $input['smtp_batch_size'] );
    }

    if ( isset( $input['birthday_article_slug'] ) ) {
        $sanitized_input['birthday_article_slug'] = sanitize_text_field( $input['birthday_article_slug'] );
    }

    $sanitized_input['birthday_emails_enabled'] = isset( $input['birthday_emails_enabled'] ) ? 1 : 0;

    if ( isset( $input['payment_url'] ) ) {
        $sanitized_input['payment_url'] = esc_url_raw( $input['payment_url'] );
    }

    if ( isset( $input['backup_time'] ) ) {
        $time = trim( $input['backup_time'] );
        // Validate HH:MM format and valid time.
        if ( preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time ) ) {
            $sanitized_input['backup_time'] = $time;
        } else {
            $sanitized_input['backup_time'] = ''; // Invalid format, save as empty.
        }
    }

    $sanitized_input['delete_on_uninstall'] = isset( $input['delete_on_uninstall'] ) ? 1 : 0;
    return $sanitized_input;
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
 * Callbacks for Annual Reset Section
 */
function dame_reset_section_callback() {
    echo '<p>' . esc_html__( 'Cette action prépare le système pour la prochaine saison d\'adhésion.', 'dame' ) . '</p>';

    $next_season_name = dame_get_next_season_name();

    echo '<p><strong>' . sprintf( esc_html__( 'Processus : En cliquant sur le bouton, vous allez créer le tag pour la saison "%s" et le définir comme saison "active" pour les nouvelles inscriptions.', 'dame' ), esc_html( $next_season_name ) ) . '</strong></p>';

    $current_season_tag_id = get_option( 'dame_current_season_tag_id' );
    if ( $current_season_tag_id ) {
        $current_season_term = get_term( $current_season_tag_id, 'dame_saison_adhesion' );
        if ( $current_season_term && ! is_wp_error( $current_season_term ) ) {
            echo '<p>' . sprintf( esc_html__( 'Saison active actuelle : %s', 'dame' ), '<strong>' . esc_html( $current_season_term->name ) . '</strong>' ) . '</p>';
        }
    }
}

function dame_reset_button_callback() {
    $next_season_name = dame_get_next_season_name();

    $disabled = term_exists( $next_season_name, 'dame_saison_adhesion' ) ? 'disabled' : '';
    ?>
    <form method="post">
        <input type="hidden" name="dame_action" value="annual_reset" />
        <?php wp_nonce_field( 'dame_annual_reset_nonce', 'dame_annual_reset_nonce_field' ); ?>
        <?php submit_button( __( 'Initialiser la nouvelle saison', 'dame' ), 'primary', 'dame_annual_reset', false, $disabled ); ?>
        <p class="description">
            <?php
            if ( $disabled ) {
                echo esc_html( sprintf( __( 'La saison "%s" a déjà été créée.', 'dame' ), $next_season_name ) );
            } else {
                echo esc_html( sprintf( __( 'Cette action créera la saison "%s".', 'dame' ), $next_season_name ) );
            }
            ?>
        </p>
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resetButton = document.getElementById('dame_annual_reset');
            if (resetButton) {
                resetButton.addEventListener('click', function(e) {
                    if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir initialiser la nouvelle saison ? Cela définira un nouveau tag comme saison active.', 'dame' ) ); ?>")) {
                        e.preventDefault();
                    } else {
                        // On confirmation, disable the button to prevent double-clicks.
                        // Use a timeout to ensure the form submission is not interrupted.
                        setTimeout(function() {
                            resetButton.disabled = true;
                        }, 0);
                    }
                });
            }
        });
    </script>
    <?php
}
