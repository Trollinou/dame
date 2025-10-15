<?php
/**
 * Settings for the Anniversaires tab.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register settings for the Birthday tab.
 */
function dame_register_birthday_settings() {
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
}
add_action( 'admin_init', 'dame_register_birthday_settings' );


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
 * Callback for the birthday section.
 */
function dame_birthday_section_callback() {
    echo '<p>' . esc_html__( "Paramètres pour l'envoi automatique des emails d'anniversaire aux adhérents.", 'dame' ) . '</p>';
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
