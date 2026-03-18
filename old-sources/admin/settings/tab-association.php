<?php
/**
 * Settings for the Association tab.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register settings for the Association tab.
 */
function dame_register_association_settings() {
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
}
add_action( 'admin_init', 'dame_register_association_settings' );

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
