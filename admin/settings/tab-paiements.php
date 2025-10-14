<?php
/**
 * Settings for the Paiements tab.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register settings for the Payment tab.
 */
function dame_register_payment_settings() {
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
}
add_action( 'admin_init', 'dame_register_payment_settings' );


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
