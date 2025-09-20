<?php
/**
 * The template for displaying a single Adherent.
 *
 * @package DAME
 */

get_header(); ?>

<style>
    .dame-adherent-single-container {
        width: 80%;
        margin: 2em auto;
        font-family: sans-serif;
    }
    .dame-adherent-single-container h1 {
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .dame-adherent-section {
        background: #f9f9f9;
        border: 1px solid #ddd;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 5px;
    }
    .dame-adherent-section h2 {
        margin-top: 0;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
    }
    .dame-adherent-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    .dame-adherent-field {
        padding: 10px;
        background: #fff;
        border: 1px solid #eee;
    }
    .dame-adherent-field strong {
        display: block;
        color: #555;
        margin-bottom: 5px;
    }
</style>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php
        while ( have_posts() ) :
            the_post();
            $post_id = get_the_ID();
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="dame-adherent-single-container">

                    <h1><?php echo esc_html( get_the_title() ); ?></h1>

                    <!-- Section: Informations sur l'adhérent -->
                    <section class="dame-adherent-section">
                        <h2><?php _e( "Informations sur l'adhérent", 'dame' ); ?></h2>
                        <div class="dame-adherent-grid">
                            <?php
                            $fields = array(
                                'Prénom' => '_dame_first_name',
                                'Nom' => '_dame_last_name',
                                'Date de naissance' => '_dame_birth_date',
                                'Lieu de naissance' => '_dame_birth_city',
                                'Sexe' => '_dame_sexe',
                                'Profession' => '_dame_profession',
                                'Email' => '_dame_email',
                                'Numéro de téléphone' => '_dame_phone_number',
                                'Adresse' => '_dame_address_1',
                                'Complément' => '_dame_address_2',
                                'Code Postal' => '_dame_postal_code',
                                'Ville' => '_dame_city',
                                'Pays' => '_dame_country',
                                'Département' => '_dame_department',
                                'Région' => '_dame_region',
                            );
                            foreach ( $fields as $label => $key ) {
                                $value = get_post_meta( $post_id, $key, true );
                                if ( ! empty( $value ) ) {
                                    echo '<div class="dame-adherent-field"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</div>';
                                }
                            }
                            ?>
                        </div>
                    </section>

                    <!-- Section: Informations Scolaires -->
                    <section class="dame-adherent-section">
                        <h2><?php _e( 'Informations Scolaires', 'dame' ); ?></h2>
                        <div class="dame-adherent-grid">
                            <?php
                            $school_fields = array(
                                'Établissement scolaire' => '_dame_school_name',
                                'Académie' => '_dame_school_academy',
                            );
                            foreach ( $school_fields as $label => $key ) {
                                $value = get_post_meta( $post_id, $key, true );
                                if ( ! empty( $value ) ) {
                                    echo '<div class="dame-adherent-field"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</div>';
                                }
                            }
                            ?>
                        </div>
                    </section>

                    <!-- Section: Représentants Légaux -->
                    <section class="dame-adherent-section">
                        <h2><?php _e( 'Représentants Légaux', 'dame' ); ?></h2>

                        <h3><?php _e( 'Représentant Légal 1', 'dame' ); ?></h3>
                        <div class="dame-adherent-grid">
                            <?php
                            $rep1_fields = array(
                                'Prénom' => '_dame_legal_rep_1_first_name',
                                'Nom' => '_dame_legal_rep_1_last_name',
                                'Profession' => '_dame_legal_rep_1_profession',
                                'Email' => '_dame_legal_rep_1_email',
                                'Téléphone' => '_dame_legal_rep_1_phone',
                                'Adresse' => '_dame_legal_rep_1_address_1',
                                'Complément' => '_dame_legal_rep_1_address_2',
                                'Code Postal' => '_dame_legal_rep_1_postal_code',
                                'Ville' => '_dame_legal_rep_1_city',
                            );
                            foreach ( $rep1_fields as $label => $key ) {
                                $value = get_post_meta( $post_id, $key, true );
                                if ( ! empty( $value ) ) {
                                    echo '<div class="dame-adherent-field"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</div>';
                                }
                            }
                            ?>
                        </div>

                        <h3 style="margin-top: 20px;"><?php _e( 'Représentant Légal 2', 'dame' ); ?></h3>
                        <div class="dame-adherent-grid">
                            <?php
                            $rep2_fields = array(
                                'Prénom' => '_dame_legal_rep_2_first_name',
                                'Nom' => '_dame_legal_rep_2_last_name',
                                'Profession' => '_dame_legal_rep_2_profession',
                                'Email' => '_dame_legal_rep_2_email',
                                'Téléphone' => '_dame_legal_rep_2_phone',
                                'Adresse' => '_dame_legal_rep_2_address_1',
                                'Complément' => '_dame_legal_rep_2_address_2',
                                'Code Postal' => '_dame_legal_rep_2_postal_code',
                                'Ville' => '_dame_legal_rep_2_city',
                            );
                            foreach ( $rep2_fields as $label => $key ) {
                                $value = get_post_meta( $post_id, $key, true );
                                if ( ! empty( $value ) ) {
                                    echo '<div class="dame-adherent-field"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</div>';
                                }
                            }
                            ?>
                        </div>
                    </section>

                    <!-- Section: Informations diverses -->
                    <section class="dame-adherent-section">
                        <h2><?php _e( 'Informations diverses', 'dame' ); ?></h2>
                        <div class="dame-adherent-grid">
                            <?php
                            $diverse_fields = array(
                                'Autre téléphone' => '_dame_autre_telephone',
                                'Taille vêtements' => '_dame_taille_vetements',
                                'Allergies connues' => '_dame_allergies',
                                'Régime alimentaire' => '_dame_diet',
                                'Moyen de locomotion' => '_dame_transport',
                            );
                            foreach ( $diverse_fields as $label => $key ) {
                                $value = get_post_meta( $post_id, $key, true );
                                if ( ! empty( $value ) ) {
                                    echo '<div class="dame-adherent-field"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</div>';
                                }
                            }
                            ?>
                        </div>
                    </section>

                    <!-- Section: Classification et Adhésion -->
                    <section class="dame-adherent-section">
                        <h2><?php _e( 'Classification et Adhésion', 'dame' ); ?></h2>
                        <div class="dame-adherent-grid">
                            <?php
                            $classification_fields = array(
                                'Numéro de licence' => '_dame_license_number',
                                'Type de licence' => '_dame_license_type',
                                'Document de santé' => '_dame_health_document',
                                'Niveau d\'arbitre' => '_dame_arbitre_level',
                                'École d\'échecs' => '_dame_is_junior',
                                'Pôle Excellence' => '_dame_is_pole_excellence',
                                'Bénévole' => '_dame_is_benevole',
                                'Elu local' => '_dame_is_elu_local',
                                'Contrôle d\'honorabilité' => '_dame_adherent_honorabilite',
                            );
                            foreach ( $classification_fields as $label => $key ) {
                                $value = get_post_meta( $post_id, $key, true );
                                if ( $value === '1' ) {
                                    $value = 'Oui';
                                }
                                if ( ! empty( $value ) && $value !== '0' ) {
                                    echo '<div class="dame-adherent-field"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</div>';
                                }
                            }
                            ?>
                        </div>
                    </section>

                </div>
            </article>

        <?php endwhile; ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
