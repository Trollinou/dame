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

        $current_year = date( 'Y' );
        $last_reset_year = get_option( 'dame_last_reset_year' );
        if ( $current_year === $last_reset_year ) {
            add_action(
                'admin_notices',
                function() {
                    echo '<div class="error"><p>' . esc_html__( 'La réinitialisation a déjà été effectuée cette année.', 'dame' ) . '</p></div>';
                }
            );
            return;
        }

        // Determine the upcoming season name. A new season starts in September.
        $current_month     = (int) date( 'n' );
        $current_year      = (int) date( 'Y' );
        $season_start_year = ( $current_month >= 9 ) ? $current_year + 1 : $current_year;
        $season_end_year   = $season_start_year + 1;
        $new_season_name   = sprintf( 'Saison %d/%d', $season_start_year, $season_end_year );

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
        update_option( 'dame_last_reset_year', $current_year );

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

    $sanitized_input['delete_on_uninstall'] = isset( $input['delete_on_uninstall'] ) ? 1 : 0;
    return $sanitized_input;
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

    // Determine the upcoming season name to inform the user.
    $current_month     = (int) date( 'n' );
    $current_year      = (int) date( 'Y' );
    $season_start_year = ( $current_month >= 9 ) ? $current_year + 1 : $current_year;
    $season_end_year   = $season_start_year + 1;
    $next_season_name  = sprintf( 'Saison %d/%d', $season_start_year, $season_end_year );

    echo '<p><strong>' . sprintf( esc_html__( 'Processus : En cliquant sur le bouton, vous allez créer le tag pour la saison "%s" et le définir comme saison "active" pour les nouvelles inscriptions.', 'dame' ), esc_html( $next_season_name ) ) . '</strong></p>';

    $current_season_tag_id = get_option( 'dame_current_season_tag_id' );
    if ( $current_season_tag_id ) {
        $current_season_term = get_term( $current_season_tag_id, 'dame_saison_adhesion' );
        if ( $current_season_term && ! is_wp_error( $current_season_term ) ) {
            echo '<p>' . sprintf( esc_html__( 'Saison active actuelle : %s', 'dame' ), '<strong>' . esc_html( $current_season_term->name ) . '</strong>' ) . '</p>';
        }
    }

    $last_reset_year = get_option( 'dame_last_reset_year', __( 'jamais', 'dame' ) );
    echo '<p>' . sprintf( esc_html__( 'Dernière initialisation de saison effectuée pour l\'année civile : %s', 'dame' ), '<strong>' . esc_html( $last_reset_year ) . '</strong>' ) . '</p>';
}

function dame_reset_button_callback() {
    $current_year    = date( 'Y' );
    $last_reset_year = get_option( 'dame_last_reset_year' );
    $disabled        = ( $current_year === $last_reset_year ) ? 'disabled' : '';
    ?>
    <form method="post">
        <input type="hidden" name="dame_action" value="annual_reset" />
        <?php wp_nonce_field( 'dame_annual_reset_nonce', 'dame_annual_reset_nonce_field' ); ?>
        <?php submit_button( __( 'Initialiser la nouvelle saison', 'dame' ), 'primary', 'dame_annual_reset', false, $disabled ); ?>
        <p class="description">
            <?php
            if ( $disabled ) {
                esc_html_e( 'L\'initialisation de la saison a déjà été effectuée pour cette année civile.', 'dame' );
            } else {
                esc_html_e( 'Cette action ne peut être effectuée qu\'une fois par année civile.', 'dame' );
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
                    }
                });
            }
        });
    </script>
    <?php
}
