<?php
/**
 * File for handling the Adherent view page in the admin.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the hidden admin page for viewing an adherent.
 */
function dame_add_view_adherent_page() {
    add_submenu_page(
        'dame', // Attach to the main DAME menu slug.
        __( 'Consulter la fiche Adhérent', 'dame' ),
        __( 'Consulter Adhérent', 'dame' ),
        'edit_posts', // Capability for contributors and up.
        'dame-view-adherent',
        'dame_render_view_adherent_page'
    );
    // Hide the submenu page immediately.
    remove_submenu_page( 'dame', 'dame-view-adherent' );
}
add_action( 'admin_menu', 'dame_add_view_adherent_page' );

/**
 * Helper function to render a single read-only field in a table row.
 *
 * @param string $label The label for the field.
 * @param string $value The value of the field.
 */
function dame_render_view_field( $label, $value ) {
    if ( empty( $value ) && $value !== '0' ) {
        return;
    }
    // Handle boolean values for display
    if ( $value === '1' ) $value = 'Oui';
    if ( $value === '0' ) $value = 'Non';
    ?>
    <tr valign="top">
        <th scope="row" style="width: 200px;">
            <strong><?php echo esc_html( $label ); ?></strong>
        </th>
        <td><?php echo esc_html( $value ); ?></td>
    </tr>
    <?php
}

/**
 * Helper function to render a single read-only field in a simple paragraph.
 *
 * @param string $label The label for the field.
 * @param string $value The value of the field.
 */
function dame_render_view_p_field( $label, $value ) {
    if ( empty( $value ) && $value !== '0' ) {
        return;
    }
    // Handle boolean values for display
    if ( $value === '1' ) $value = 'Oui';
    if ( $value === '0' ) $value = 'Non';

    echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
}


/**
 * Render the content of the adherent view page.
 */
function dame_render_view_adherent_page() {
    // Check permissions
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( esc_html__( 'Vous n\'avez pas la permission de voir cette page.', 'dame' ) );
    }

    // Get adherent ID from URL
    $adherent_id = isset( $_GET['adherent_id'] ) ? intval( $_GET['adherent_id'] ) : 0;
    if ( ! $adherent_id ) {
        wp_die( esc_html__( 'ID de l\'adhérent non valide.', 'dame' ) );
    }

    // Get post object and check if it's a valid adherent
    $adherent = get_post( $adherent_id );
    if ( ! $adherent || 'adherent' !== $adherent->post_type ) {
        wp_die( esc_html__( 'Adhérent non trouvé.', 'dame' ) );
    }

    $post_id = $adherent->ID;
    ?>
    <div class="wrap">
        <h1>
            <?php echo esc_html( __( 'Fiche de l\'adhérent : ', 'dame' ) . get_the_title( $post_id ) ); ?>
            <a href="edit.php?post_type=adherent" class="page-title-action"><?php _e( 'Retour à la liste', 'dame' ); ?></a>
        </h1>

        <style>
            .form-table th, .form-table td {
                padding-top: 5px;
                padding-bottom: 5px;
            }
        </style>

        <?php if ( current_user_can( 'edit_post', $post_id ) ) : ?>
            <a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" class="button button-primary" style="margin-bottom:20px;"><?php _e( 'Modifier cet adhérent', 'dame' ); ?></a>
        <?php endif; ?>

        <div id="poststuff" style="margin-top: 20px;">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e( "Informations sur l'adhérent", 'dame' ); ?></span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <?php
                                $fields = [
                                    'Nom de naissance' => '_dame_birth_name', 'Nom d\'usage' => '_dame_last_name', 'Prénom' => '_dame_first_name',
                                    'Sexe' => '_dame_sexe', 'Date de naissance' => '_dame_birth_date', 'Lieu de naissance' => '_dame_birth_city',
                                    'Numéro de téléphone' => '_dame_phone_number', 'Email' => '_dame_email', 'Profession' => '_dame_profession',
                                    'Adresse' => '_dame_address_1', 'Complément' => '_dame_address_2',
                                    'Code Postal' => '_dame_postal_code', 'Ville' => '_dame_city', 'Pays' => '_dame_country',
                                    'Département' => '_dame_department', 'Région' => '_dame_region',
                                ];
                                foreach ( $fields as $label => $key ) {
                                    dame_render_view_field( $label, get_post_meta( $post_id, $key, true ) );
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e( 'Informations Scolaires', 'dame' ); ?></span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <?php
                                $school_fields = ['Établissement scolaire' => '_dame_school_name', 'Académie' => '_dame_school_academy'];
                                foreach ( $school_fields as $label => $key ) {
                                    dame_render_view_field( $label, get_post_meta( $post_id, $key, true ) );
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e( 'Représentants Légaux (si mineur)', 'dame' ); ?></span></h2>
                        <div class="inside">
                            <h3><?php _e( 'Représentant Légal 1', 'dame' ); ?></h3>
                            <table class="form-table">
                                <?php
                                $rep1_fields = [
                                    'Nom de naissance' => '_dame_legal_rep_1_last_name', 'Prénom' => '_dame_legal_rep_1_first_name',
                                    'Date de naissance' => '_dame_legal_rep_1_date_naissance', 'Lieu de naissance' => '_dame_legal_rep_1_commune_naissance',
                                    'Contrôle d\'honorabilité' => '_dame_legal_rep_1_honorabilite', 'Numéro de téléphone' => '_dame_legal_rep_1_phone',
                                    'Email' => '_dame_legal_rep_1_email', 'Profession' => '_dame_legal_rep_1_profession',
                                    'Adresse' => '_dame_legal_rep_1_address_1', 'Complément' => '_dame_legal_rep_1_address_2',
                                    'Code Postal' => '_dame_legal_rep_1_postal_code', 'Ville' => '_dame_legal_rep_1_city',
                                ];
                                foreach ( $rep1_fields as $label => $key ) {
                                    dame_render_view_field( $label, get_post_meta( $post_id, $key, true ) );
                                }
                                ?>
                            </table>
                            <hr>
                            <h3><?php _e( 'Représentant Légal 2', 'dame' ); ?></h3>
                            <table class="form-table">
                                <?php
                                $rep2_fields = [
                                    'Nom de naissance' => '_dame_legal_rep_2_last_name', 'Prénom' => '_dame_legal_rep_2_first_name',
                                    'Date de naissance' => '_dame_legal_rep_2_date_naissance', 'Lieu de naissance' => '_dame_legal_rep_2_commune_naissance',
                                    'Contrôle d\'honorabilité' => '_dame_legal_rep_2_honorabilite', 'Numéro de téléphone' => '_dame_legal_rep_2_phone',
                                    'Email' => '_dame_legal_rep_2_email', 'Profession' => '_dame_legal_rep_2_profession',
                                    'Adresse' => '_dame_legal_rep_2_address_1', 'Complément' => '_dame_legal_rep_2_address_2',
                                    'Code Postal' => '_dame_legal_rep_2_postal_code', 'Ville' => '_dame_legal_rep_2_city',
                                ];
                                foreach ( $rep2_fields as $label => $key ) {
                                    dame_render_view_field( $label, get_post_meta( $post_id, $key, true ) );
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                     <div class="postbox">
                        <h2 class="hndle"><span><?php _e( 'Informations diverses', 'dame' ); ?></span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <?php
                                $diverse_fields = [
                                    'Autre téléphone' => '_dame_autre_telephone', 'Taille vêtements' => '_dame_taille_vetements',
                                    'Allergies connues' => '_dame_allergies', 'Régime alimentaire' => '_dame_diet',
                                    'Moyen de locomotion' => '_dame_transport',
                                ];
                                foreach ( $diverse_fields as $label => $key ) {
                                    dame_render_view_field( $label, get_post_meta( $post_id, $key, true ) );
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e( 'Classification et Adhésion', 'dame' ); ?></span></h2>
                        <div class="inside">
                            <?php
                            $classification_fields = [
                                'Numéro de licence' => '_dame_license_number', 'Type de licence' => '_dame_license_type',
                                'Document de santé' => '_dame_health_document', 'Niveau d\'arbitre' => '_dame_arbitre_level',
                                'Contrôle d\'honorabilité' => '_dame_adherent_honorabilite',
                            ];
                            foreach ($classification_fields as $label => $key) {
                                dame_render_view_p_field( $label, get_post_meta( $post_id, $key, true ) );
                            }
                            ?>
                        </div>
                    </div>
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e( 'Groupes', 'dame' ); ?></span></h2>
                        <div class="inside">
                            <?php
                            $group_terms = wp_get_post_terms( $post_id, 'dame_group', array( 'fields' => 'names' ) );
                            if ( ! empty( $group_terms ) && ! is_wp_error( $group_terms ) ) {
                                echo '<p>' . esc_html( implode( ', ', $group_terms ) ) . '</p>';
                            } else {
                                echo '<p>' . esc_html__( 'Aucun groupe assigné.', 'dame' ) . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
