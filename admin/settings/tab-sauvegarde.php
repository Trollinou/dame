<?php
/**
 * Settings for the Sauvegarde tab.
 *
 * @package DAME
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register settings for the Backup tab.
 */
function dame_register_backup_settings() {
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
}
add_action( 'admin_init', 'dame_register_backup_settings' );


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
