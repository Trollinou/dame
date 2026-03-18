<?php
/**
 * Settings for the Emails tab.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register settings for the Email tab.
 */
function dame_register_email_settings() {
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
}
add_action( 'admin_init', 'dame_register_email_settings' );


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
