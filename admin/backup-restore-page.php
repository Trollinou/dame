<?php
/**
 * File for handling the Backup/Restore admin page for learning content.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Renders the backup/restore page.
 */
function dame_render_backup_restore_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <div class="dame-backup-restore-wrapper">

            <!-- Backup Section -->
            <div class="dame-backup-section" style="margin-bottom: 2em;">
                <h2><?php esc_html_e( 'Sauvegarder les données d\'apprentissage', 'dame' ); ?></h2>
                <p><?php esc_html_e( "Cliquez sur le bouton ci-dessous pour télécharger une sauvegarde de toutes les leçons, exercices et cours, ainsi que leurs catégories.", 'dame' ); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field( 'dame_backup_nonce_action', 'dame_backup_nonce' ); ?>
                    <?php submit_button( __( 'Sauvegarder la base de données', 'dame' ), 'primary', 'dame_backup_action', false ); ?>
                </form>
            </div>

            <hr>

            <!-- Restore Section -->
            <div class="dame-restore-section">
                <h2><?php esc_html_e( 'Restaurer les données d\'apprentissage', 'dame' ); ?></h2>
                <p><strong><span style="color: red;"><?php esc_html_e( 'Attention :', 'dame' ); ?></span></strong> <?php esc_html_e( "L'importation depuis un fichier de sauvegarde effacera et remplacera TOUTES les données d'apprentissage existantes (leçons, exercices, cours et catégories). Assurez-vous d'avoir une sauvegarde si nécessaire.", 'dame' ); ?></p>
                <form method="post" enctype="multipart/form-data" id="dame-restore-form" action="">
                    <?php wp_nonce_field( 'dame_restore_nonce_action', 'dame_restore_nonce' ); ?>
                    <p>
                        <label for="dame_restore_file"><?php esc_html_e( 'Choisissez un fichier de sauvegarde (.json.gz) à importer :', 'dame' ); ?></label>
                        <input type="file" id="dame_restore_file" name="dame_restore_file" accept=".gz" required>
                    </p>
                    <?php submit_button( __( 'Restaurer la base de données', 'dame' ), 'delete', 'dame_restore_action' ); ?>
                </form>
            </div>

        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const restoreForm = document.getElementById('dame-restore-form');
                if (restoreForm) {
                    restoreForm.addEventListener('submit', function(e) {
                        if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Toutes les leçons, exercices, cours et catégories existants seront supprimés et remplacés. Cette action est irréversible.', 'dame' ) ); ?>")) {
                            e.preventDefault();
                        }
                    });
                }
            });
        </script>
    </div>
    <?php
}
