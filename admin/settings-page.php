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
            add_action( 'admin_notices', function() {
                echo '<div class="error"><p>' . esc_html__( 'La réinitialisation a déjà été effectuée cette année.', 'dame' ) . '</p></div>';
            });
            return;
        }

        global $wpdb;

        $expired_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_dame_membership_status' AND meta_value = 'E'" );
        $active_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_dame_membership_status' AND meta_value = 'A'" );

        $expired_to_ancient = 0;
        if ( ! empty( $expired_ids ) ) {
            $expired_to_ancient = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = 'X' WHERE meta_key = '_dame_membership_status' AND post_id IN (" . implode( ',', array_map('absint', $expired_ids) ) . ")" ) );
        }

        $active_to_expired = 0;
        if ( ! empty( $active_ids ) ) {
            $active_to_expired = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = 'E' WHERE meta_key = '_dame_membership_status' AND post_id IN (" . implode( ',', array_map('absint', $active_ids) ) . ")" ) );
        }

        $all_affected_ids = array_unique( array_merge( $expired_ids, $active_ids ) );
        if ( ! empty( $all_affected_ids ) ) {
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_dame_membership_date' AND post_id IN (" . implode( ',', array_map('absint', $all_affected_ids) ) . ")" ) );
        }

        update_option( 'dame_last_reset_year', $current_year );

        add_action( 'admin_notices', function() use ( $active_to_expired, $expired_to_ancient ) {
            $message = sprintf(
                esc_html__( 'Réinitialisation annuelle terminée. %d adhésions actives passées à Expiré. %d adhésions expirées passées à Ancien.', 'dame' ),
                $active_to_expired,
                $expired_to_ancient
            );
            echo '<div class="updated"><p>' . $message . '</p></div>';
        });
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
    echo '<p>' . esc_html__( 'Cette action met à jour le statut de tous les adhérents en fin d\'année civile.', 'dame' ) . '</p>';
    echo '<p><strong>' . esc_html__( 'Processus : Les adhésions "Actif" passent à "Expiré", et les "Expiré" passent à "Ancien". La date d\'adhésion est également effacée.', 'dame' ) . '</strong></p>';
    $last_reset_year = get_option( 'dame_last_reset_year', __( 'jamais', 'dame' ) );
    echo '<p>' . sprintf( esc_html__( 'Dernière réinitialisation effectuée pour l\'année : %s', 'dame' ), '<strong>' . esc_html( $last_reset_year ) . '</strong>' ) . '</p>';
}

function dame_reset_button_callback() {
    $current_year = date( 'Y' );
    $last_reset_year = get_option( 'dame_last_reset_year' );
    $disabled = ( $current_year === $last_reset_year ) ? 'disabled' : '';
    ?>
    <form method="post">
        <input type="hidden" name="dame_action" value="annual_reset" />
        <?php wp_nonce_field( 'dame_annual_reset_nonce', 'dame_annual_reset_nonce_field' ); ?>
        <?php submit_button( __( 'Lancer la réinitialisation annuelle', 'dame' ), 'delete', 'dame_annual_reset', false, $disabled ); ?>
        <p class="description">
            <?php
            if ( $disabled ) {
                esc_html_e( 'La réinitialisation a déjà été effectuée pour cette année.', 'dame' );
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
                    if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir lancer la réinitialisation annuelle ? Cette action est irréversible.', 'dame' ) ); ?>")) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
    <?php
}
