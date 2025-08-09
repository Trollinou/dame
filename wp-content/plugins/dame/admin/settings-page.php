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
            do_settings_sections( 'dame_uninstall_section_group' );
            submit_button( __( 'Enregistrer les modifications', 'dame' ) );
            ?>
        </form>

        <hr>

        <h2><?php esc_html_e( 'Réinitialisation Annuelle des Adhésions', 'dame' ); ?></h2>
        <?php dame_reset_section_callback(); ?>
        <?php dame_reset_button_callback(); ?>

        <hr>

        <h2><?php esc_html_e( 'Import / Export de la base', 'dame' ); ?></h2>
        <div class="dame-import-export-wrapper">

            <!-- Export Section -->
            <div class="dame-export-section">
                <h3><?php esc_html_e( 'Exporter les données', 'dame' ); ?></h3>
                <p><?php esc_html_e( "Cliquez sur le bouton ci-dessous pour télécharger un fichier JSON de sauvegarde de tous les adhérents.", 'dame' ); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field( 'dame_export_nonce_action', 'dame_export_nonce' ); ?>
                    <?php submit_button( __( 'Exporter la base de données', 'dame' ), 'primary', 'dame_export_action' ); ?>
                </form>
            </div>

            <!-- Import Section -->
            <div class="dame-import-section">
                <h3><?php esc_html_e( 'Importer les données', 'dame' ); ?></h3>
                <p><strong><?php esc_html_e( 'Attention : ', 'dame' ); ?></strong><?php esc_html_e( "L'importation effacera et remplacera toutes les données d'adhérents existantes par les données du fichier téléversé. Assurez-vous d'avoir une sauvegarde.", 'dame' ); ?></p>
                <form method="post" enctype="multipart/form-data" id="dame-import-form">
                    <?php wp_nonce_field( 'dame_import_nonce_action', 'dame_import_nonce' ); ?>
                    <p>
                        <label for="dame_import_file"><?php esc_html_e( 'Choisissez un fichier JSON à importer :', 'dame' ); ?></label>
                        <input type="file" id="dame_import_file" name="dame_import_file" accept="application/json" required>
                    </p>
                    <?php submit_button( __( 'Importer la base de données', 'dame' ), 'delete', 'dame_import' ); ?>
                </form>
            </div>

        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const importForm = document.getElementById('dame-import-form');
                if (importForm) {
                    importForm.addEventListener('submit', function(e) {
                        if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir importer ce fichier ? Toutes les données d\'adhérents existantes seront supprimées et remplacées. Cette action est irréversible.', 'dame' ) ); ?>")) {
                            e.preventDefault();
                        }
                    });
                }
            });
        </script>

    </div>
    <?php
}

/**
 * Register settings, sections, and fields.
 */
function dame_register_settings() {
    register_setting( 'dame_options_group', 'dame_options', 'dame_options_sanitize' );

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
    $sanitized_input = array();
    $sanitized_input['delete_on_uninstall'] = isset( $input['delete_on_uninstall'] ) ? 1 : 0;
    return $sanitized_input;
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
