<?php
/**
 * File for handling the Backup/Restore admin page for Agenda content.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Add the Backup/Restore page to the Agenda CPT menu.
 */
function dame_add_agenda_backup_restore_page() {
    add_submenu_page(
        'edit.php?post_type=dame_agenda',
        __( 'Sauvegarde / Restauration', 'dame' ),
        __( 'Sauvegarde / Restauration', 'dame' ),
        'manage_options',
        'dame-agenda-backup-restore',
        'dame_render_agenda_backup_restore_page'
    );
}
add_action( 'admin_menu', 'dame_add_agenda_backup_restore_page' );

/**
 * Renders the backup/restore page for the agenda.
 */
function dame_render_agenda_backup_restore_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <div class="dame-backup-restore-wrapper">

            <!-- Backup Section -->
            <div class="dame-backup-section" style="margin-bottom: 2em;">
                <h2><?php esc_html_e( 'Sauvegarder les données de l\'agenda', 'dame' ); ?></h2>
                <p><?php esc_html_e( "Cliquez sur le bouton ci-dessous pour télécharger une sauvegarde de tous les événements et de leurs catégories.", 'dame' ); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field( 'dame_agenda_backup_nonce_action', 'dame_agenda_backup_nonce' ); ?>
                    <?php submit_button( __( 'Sauvegarder la base de données de l\'agenda', 'dame' ), 'primary', 'dame_agenda_backup_action', false ); ?>
                </form>
            </div>

            <hr>

            <!-- Restore Section -->
            <div class="dame-restore-section">
                <h2><?php esc_html_e( 'Restaurer les données de l\'agenda', 'dame' ); ?></h2>
                <p><strong><span style="color: red;"><?php esc_html_e( 'Attention :', 'dame' ); ?></span></strong> <?php esc_html_e( "L'importation depuis un fichier de sauvegarde effacera et remplacera TOUS les événements et catégories existants. Assurez-vous d'avoir une sauvegarde si nécessaire.", 'dame' ); ?></p>
                <form method="post" enctype="multipart/form-data" id="dame-agenda-restore-form" action="">
                    <?php wp_nonce_field( 'dame_agenda_restore_nonce_action', 'dame_agenda_restore_nonce' ); ?>
                    <p>
                        <label for="dame_agenda_restore_file"><?php esc_html_e( 'Choisissez un fichier de sauvegarde (.json.gz) à importer :', 'dame' ); ?></label>
                        <input type="file" id="dame_agenda_restore_file" name="dame_agenda_restore_file" accept=".gz" required>
                    </p>
                    <?php submit_button( __( 'Restaurer la base de données de l\'agenda', 'dame' ), 'delete', 'dame_agenda_restore_action' ); ?>
                </form>
            </div>

        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const restoreForm = document.getElementById('dame-agenda-restore-form');
                if (restoreForm) {
                    restoreForm.addEventListener('submit', function(e) {
                        if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Tous les événements et catégories existants seront supprimés et remplacés. Cette action est irréversible.', 'dame' ) ); ?>")) {
                            e.preventDefault();
                        }
                    });
                }
            });
        </script>
    </div>
    <?php
}
