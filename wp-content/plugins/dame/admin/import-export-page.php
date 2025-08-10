<?php
/**
 * File for handling the Import/Export admin page.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Add the Import/Export page to the Adherent CPT menu.
 */
function dame_add_import_export_page() {
    add_submenu_page(
        'edit.php?post_type=adherent',
        __( 'Importer / Exporter', 'dame' ),
        __( 'Import / Export', 'dame' ),
        'manage_options',
        'dame-import-export',
        'dame_render_import_export_page'
    );
}
add_action( 'admin_menu', 'dame_add_import_export_page' );

/**
 * Renders the import/export page.
 */
function dame_render_import_export_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <div class="dame-import-export-wrapper">

            <!-- Export Section -->
            <div class="dame-export-section" style="margin-bottom: 2em;">
                <h2><?php esc_html_e( 'Exporter les données', 'dame' ); ?></h2>
                <p><?php esc_html_e( "Cliquez sur l'un des boutons ci-dessous pour télécharger une sauvegarde de tous les adhérents.", 'dame' ); ?></p>
                <form method="post" action="" style="display: inline-block; margin-right: 10px;">
                    <?php wp_nonce_field( 'dame_export_csv_nonce_action', 'dame_export_csv_nonce' ); ?>
                    <?php submit_button( __( 'Exporter la liste (CSV)', 'dame' ), 'primary', 'dame_export_csv_action', false ); ?>
                </form>
                <form method="post" action="" style="display: inline-block;">
                    <?php wp_nonce_field( 'dame_export_nonce_action', 'dame_export_nonce' ); ?>
                    <?php submit_button( __( 'Exporter la base de données (JSON)', 'dame' ), 'secondary', 'dame_export_action', false ); ?>
                </form>
            </div>

            <hr>

            <!-- Import Section -->
            <div class="dame-import-section">
                <h2><?php esc_html_e( 'Importer les données', 'dame' ); ?></h2>

                <h4><?php esc_html_e( 'Import CSV', 'dame' ); ?></h4>
				<p><?php esc_html_e( 'Importer une liste d\'adhérents depuis un fichier CSV (séparateur point-virgule, encodage UTF-8). Cet import ajoute les adhérents à la base de données existante.', 'dame' ); ?></p>

                <p><?php esc_html_e( 'Le fichier CSV doit contenir une ligne d\'en-tête avec les noms de colonnes suivants. L\'ordre des colonnes n\'est pas important, mais les noms doivent correspondre. Les colonnes supplémentaires dans le fichier seront ignorées.', 'dame' ); ?></p>
                <div class="dame-expected-columns" style="background: #f7f7f7; border: 1px solid #ccc; padding: 10px 20px; margin-bottom: 1em;">
                    <ol style="margin: 0; padding-left: 20px;">
                        <li>Nom</li>
                        <li>Prénom</li>
                        <li>Date de naissance (format DD/MM/YYYY)</li>
                        <li>Sexe</li>
                        <li>Adresse email</li>
                        <li>Numéro de téléphone</li>
                        <li>Adresse 1</li>
                        <li>Adresse 2</li>
                        <li>Code Postal</li>
                        <li>Ville</li>
                        <li>Numéro de licence</li>
                        <li>Etat de l'adhésion</li>
                        <li>Autre téléphone</li>
                        <li>Taille vêtements</li>
                        <li>Etablissement scolaire</li>
                        <li>Académie</li>
                        <li>Allergies connu</li>
                    </ol>
                </div>

                <form method="post" enctype="multipart/form-data" id="dame-import-csv-form" action="">
					<?php wp_nonce_field( 'dame_import_csv_nonce_action', 'dame_import_csv_nonce' ); ?>
                    <p>
                        <label for="dame_import_csv_file"><?php esc_html_e( 'Choisissez un fichier CSV à importer :', 'dame' ); ?></label>
                        <input type="file" id="dame_import_csv_file" name="dame_import_csv_file" accept=".csv, text/csv" required>
                    </p>
					<?php submit_button( __( 'Importer depuis un CSV', 'dame' ), 'secondary', 'dame_import_csv_action' ); ?>
                </form>

                <hr style="margin: 20px 0;">

                <h4><?php esc_html_e( 'Import JSON (Sauvegarde)', 'dame' ); ?></h4>
                <p><strong><span style="color: red;"><?php esc_html_e( 'Attention :', 'dame' ); ?></span></strong> <?php esc_html_e( "L'importation depuis un fichier JSON effacera et remplacera TOUTES les données d'adhérents existantes. Assurez-vous d'avoir une sauvegarde.", 'dame' ); ?></p>
                <form method="post" enctype="multipart/form-data" id="dame-import-form" action="">
                    <?php wp_nonce_field( 'dame_import_nonce_action', 'dame_import_nonce' ); ?>
                    <p>
                        <label for="dame_import_file"><?php esc_html_e( 'Choisissez un fichier JSON à importer :', 'dame' ); ?></label>
                        <input type="file" id="dame_import_file" name="dame_import_file" accept="application/json" required>
                    </p>
                    <?php submit_button( __( 'Importer la base de données (JSON)', 'dame' ), 'delete', 'dame_import' ); ?>
                </form>
            </div>

        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const importForm = document.getElementById('dame-import-form');
                if (importForm) {
                    importForm.addEventListener('submit', function(e) {
                        if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir importer ce fichier JSON ? Toutes les données d\'adhérents existantes seront supprimées et remplacées. Cette action est irréversible.', 'dame' ) ); ?>")) {
                            e.preventDefault();
                        }
                    });
                }

                const importCsvForm = document.getElementById('dame-import-csv-form');
                if (importCsvForm) {
                    importCsvForm.addEventListener('submit', function(e) {
                        if (!confirm("<?php echo esc_js( __( 'Êtes-vous sûr de vouloir importer ce fichier CSV ? Cela ajoutera les adhérents du fichier à la base de données. Pour éviter les doublons, il est recommandé de vider la base de données au préalable si nécessaire.', 'dame' ) ); ?>")) {
                            e.preventDefault();
                        }
                    });
                }
            });
        </script>
    </div>
    <?php
}
