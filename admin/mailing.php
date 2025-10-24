<?php
/**
 * File for handling the mailing feature.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles the email sending process.
 */
function dame_handle_send_email() {
    if ( ! isset( $_POST['submit'] ) || ! isset( $_POST['dame_send_email_nonce_field'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( $_POST['dame_send_email_nonce_field'], 'dame_send_email_nonce' ) ) {
        wp_die( 'Security check failed.' );
    }

    if ( ! current_user_can( 'publish_dame_messages' ) ) {
        wp_die( 'You do not have permission to do this.' );
    }

    $message_id = isset( $_POST['dame_message_to_send'] ) ? absint( $_POST['dame_message_to_send'] ) : 0;
    $selection_method = isset( $_POST['dame_selection_method'] ) ? sanitize_key( $_POST['dame_selection_method'] ) : '';
    $recipient_gender = isset( $_POST['dame_recipient_gender'] ) ? sanitize_text_field( $_POST['dame_recipient_gender'] ) : 'all';

    if ( empty( $message_id ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__( "Veuillez sélectionner un message à envoyer.", 'dame' ) . '</p></div>';
        });
        return;
    }

    $adherent_ids = array();

    if ( 'group' === $selection_method ) {
        $saisonnier_groups = isset( $_POST['dame_recipient_groups_saisonnier'] ) ? array_map( 'absint', (array) $_POST['dame_recipient_groups_saisonnier'] ) : array();
        $permanent_groups = isset( $_POST['dame_recipient_groups_permanent'] ) ? array_map( 'absint', (array) $_POST['dame_recipient_groups_permanent'] ) : array();
        $seasons = isset( $_POST['dame_recipient_seasons'] ) ? array_map( 'absint', (array) $_POST['dame_recipient_seasons'] ) : array();

        $saisonnier_adherent_ids = array();
        $permanent_adherent_ids = array();

        // Query for ("Genre" AND "Saison d'adhesion" AND "Saisonnier")
        if ( ! empty( $seasons ) ) {
            $saisonnier_query_args = array(
                'post_type'      => 'adherent',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'tax_query'      => array(
                    'relation' => 'AND',
                    array(
                        'taxonomy' => 'dame_saison_adhesion',
                        'field'    => 'term_id',
                        'terms'    => $seasons,
                    ),
                ),
                'meta_query'     => array(),
            );

            if ( ! empty( $saisonnier_groups ) ) {
                $saisonnier_query_args['tax_query'][] = array(
                    'taxonomy' => 'dame_group',
                    'field'    => 'term_id',
                    'terms'    => $saisonnier_groups,
                );
            }

            if ( 'all' !== $recipient_gender ) {
                $saisonnier_query_args['meta_query'][] = array(
                    'key'   => '_dame_sexe',
                    'value' => $recipient_gender,
                );
            }
            $saisonnier_adherent_ids = get_posts( $saisonnier_query_args );
        }

        // Query for "Permanent"
        if ( ! empty( $permanent_groups ) ) {
            $permanent_query_args = array(
                'post_type'      => 'adherent',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'dame_group',
                        'field'    => 'term_id',
                        'terms'    => $permanent_groups,
                    ),
                ),
            );
            $permanent_adherent_ids = get_posts( $permanent_query_args );
        }

        $adherent_ids = array_unique( array_merge( $saisonnier_adherent_ids, $permanent_adherent_ids ) );

    } elseif ( 'manual' === $selection_method ) {
        $adherent_ids = isset( $_POST['dame_manual_recipients'] ) ? (array) array_map( 'absint', $_POST['dame_manual_recipients'] ) : array();
    }

    if ( empty( $adherent_ids ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__( "Veuillez sélectionner des destinataires.", 'dame' ) . '</p></div>';
        });
        return;
    }

    $all_adherent_ids = array_unique( $adherent_ids );

    if ( empty( $all_adherent_ids ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-warning"><p>' . esc_html__( "Aucun adhérent ne correspond aux critères sélectionnés.", 'dame' ) . '</p></div>';
        });
        return;
    }

    $recipient_emails = array();
    foreach ( $all_adherent_ids as $adherent_id ) {
        $emails = dame_get_emails_for_adherent( $adherent_id );
        $recipient_emails = array_merge( $recipient_emails, $emails );
    }

    $recipient_emails = array_unique( $recipient_emails );
    $recipient_emails = array_filter( $recipient_emails, 'is_email' );

    if ( empty( $recipient_emails ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-warning"><p>' . esc_html__( "Aucune adresse email valide n'a été trouvée pour les destinataires sélectionnés.", 'dame' ) . '</p></div>';
        });
        return;
    }

    $article = get_post( $message_id );
    $subject = $article->post_title;
    $content = apply_filters( 'the_content', $article->post_content );
    $message = '<div style="margin: 1cm;">' . $content . '</div>';

    $options = get_option( 'dame_options' );
    $sender_email = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : '';

    if ( empty( $sender_email ) ) {
        $admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
        if ( ! empty( $admins ) ) {
            $sender_email = $admins[0]->user_email;
        } else {
            $sender_email = get_option( 'admin_email' ); // Fallback to site admin email if no admin user found
        }
    }

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo( 'name' ) . ' <' . $sender_email . '>',
    );

    // Restore robust BCC implementation
    global $dame_bcc_emails;

    $batch_size = isset( $options['smtp_batch_size'] ) ? absint( $options['smtp_batch_size'] ) : 20;

    if ( $batch_size > 0 ) {
        $email_chunks = array_chunk( $recipient_emails, $batch_size );
    } else {
        $email_chunks = array( $recipient_emails );
    }

    foreach ( $email_chunks as $chunk ) {
        $dame_bcc_emails = $chunk;
        wp_mail( $sender_email, $subject, $message, $headers );
        // It's good practice to clean up the global after each mail call.
        $dame_bcc_emails = null;
    }

    // Record the sent date and author
    update_post_meta( $message_id, '_dame_sent_date', current_time( 'mysql' ) );
    update_post_meta( $message_id, '_dame_sending_author', get_current_user_id() );

    // Store the recipient criteria
    update_post_meta( $message_id, '_dame_recipient_method', $selection_method );
    if ( 'group' === $selection_method ) {
        update_post_meta( $message_id, '_dame_recipient_seasons', $seasons );
        update_post_meta( $message_id, '_dame_recipient_groups_saisonnier', $saisonnier_groups );
        update_post_meta( $message_id, '_dame_recipient_groups_permanent', $permanent_groups );
        update_post_meta( $message_id, '_dame_recipient_gender', $recipient_gender );
    } elseif ( 'manual' === $selection_method ) {
        update_post_meta( $message_id, '_dame_manual_recipients', $adherent_ids );
    }

    add_action( 'admin_notices', function() use ( $recipient_emails ) {
        $count = count( $recipient_emails );
        $message = sprintf(
            esc_html( _n( "Email envoyé avec succès à %d destinataire.", "Email envoyé avec succès à %d destinataires.", $count, 'dame' ) ),
            $count
        );
        echo '<div class="updated"><p>' . $message . '</p></div>';
    });
}
add_action( 'admin_init', 'dame_handle_send_email' );


/**
 * Adds Bcc recipients to the PHPMailer object.
 *
 * This function is hooked into `phpmailer_init` and uses a global variable
 * to get the list of recipients from the `dame_handle_send_email` function.
 *
 * @param PHPMailer $phpmailer The PHPMailer object.
 */
function dame_add_bcc_to_mailer( $phpmailer ) {
    $options = get_option( 'dame_options' );

    // Configure SMTP if settings are provided.
    if ( ! empty( $options['smtp_host'] ) && ! empty( $options['smtp_username'] ) && ! empty( $options['smtp_password'] ) ) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = $options['smtp_host'];
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = isset( $options['smtp_port'] ) ? (int) $options['smtp_port'] : 465;
        $phpmailer->Username   = $options['smtp_username'];
        $phpmailer->Password   = $options['smtp_password'];

        if ( isset( $options['smtp_encryption'] ) && 'none' !== $options['smtp_encryption'] ) {
            $phpmailer->SMTPSecure = $options['smtp_encryption'];
        }
    }

    global $dame_bcc_emails;

    if ( ! empty( $dame_bcc_emails ) && is_array( $dame_bcc_emails ) ) {
        foreach ( $dame_bcc_emails as $email ) {
            try {
                // Add each email as a Bcc recipient.
                $phpmailer->addBCC( $email );
            } catch ( Exception $e ) {
                // Silently continue if an email is invalid.
                continue;
            }
        }
        // The main function will clean up the global.
    }
}
add_action( 'phpmailer_init', 'dame_add_bcc_to_mailer' );


/**
 * Get the relevant email addresses for a given adherent.
 *
 * @param int $adherent_id The ID of the adherent post.
 * @return array An array of email addresses.
 */
function dame_get_emails_for_adherent( $adherent_id ) {
    $emails = array();

    // Get the adherent's own email
    $member_email = get_post_meta( $adherent_id, '_dame_email', true );
    $member_refuses_comms = get_post_meta( $adherent_id, '_dame_email_refuses_comms', true );
    if ( ! empty( $member_email ) && is_email( $member_email ) && '1' !== $member_refuses_comms ) {
        $emails[] = $member_email;
    }

    // Get legal representative 1's email
    $rep1_email = get_post_meta( $adherent_id, '_dame_legal_rep_1_email', true );
    $rep1_refuses_comms = get_post_meta( $adherent_id, '_dame_legal_rep_1_email_refuses_comms', true );
    if ( ! empty( $rep1_email ) && is_email( $rep1_email ) && '1' !== $rep1_refuses_comms ) {
        $emails[] = $rep1_email;
    }

    // Get legal representative 2's email
    $rep2_email = get_post_meta( $adherent_id, '_dame_legal_rep_2_email', true );
    $rep2_refuses_comms = get_post_meta( $adherent_id, '_dame_legal_rep_2_email_refuses_comms', true );
    if ( ! empty( $rep2_email ) && is_email( $rep2_email ) && '1' !== $rep2_refuses_comms ) {
        $emails[] = $rep2_email;
    }

    return $emails;
}

/**
 * Renders the mailing page.
 */
function dame_render_mailing_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Envoyer un message', 'dame' ); ?></h1>
        <p><?php esc_html_e( "Cette page vous permet d'envoyer un message à une sélection d'adhérents.", 'dame' ); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field( 'dame_send_email_nonce', 'dame_send_email_nonce_field' ); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="dame_message_to_send"><?php esc_html_e( "Message à envoyer", 'dame' ); ?></label>
                        </th>
                        <td>
                            <?php
                            $messages = get_posts( array( 'post_type' => 'dame_message', 'post_status' => array( 'publish', 'private' ), 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
                            if ( ! empty( $messages ) ) {
                                echo '<select id="dame_message_to_send" name="dame_message_to_send" style="width: 100%; max-width: 400px;">';
                                foreach ( $messages as $p ) {
                                    echo '<option value="' . esc_attr( $p->ID ) . '">' . esc_html( $p->post_title ) . '</option>';
                                }
                                echo '</select>';
                            } else {
                                echo '<p>' . esc_html__( "Aucun message trouvé.", 'dame' ) . '</p>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( "Méthode de sélection", 'dame' ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e( "Méthode de sélection", 'dame' ); ?></span></legend>
                                <label><input type="radio" name="dame_selection_method" value="group" checked> <?php esc_html_e( "Par filtre", 'dame' ); ?></label><br>
                                <label><input type="radio" name="dame_selection_method" value="manual"> <?php esc_html_e( "Manuelle", 'dame' ); ?></label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top" class="dame-group-filters">
                        <th scope="row"><?php esc_html_e( "Filtrer les destinataires", 'dame' ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e( "Filtres de destinataires", 'dame' ); ?></span></legend>

                                <div id="dame-gender-filter" style="margin-bottom: 15px;">
                                    <label for="dame_recipient_gender" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php esc_html_e( 'Sexe', 'dame' ); ?></label>
                                    <select id="dame_recipient_gender" name="dame_recipient_gender" style="width: 100%; max-width: 400px;">
                                        <option value="all" selected><?php esc_html_e( 'Tous', 'dame' ); ?></option>
                                        <option value="Masculin"><?php esc_html_e( 'Masculin', 'dame' ); ?></option>
                                        <option value="Féminin"><?php esc_html_e( 'Féminin', 'dame' ); ?></option>
                                    </select>
                                </div>

                                <hr style="margin: 15px 0;">

                                <div id="dame-season-filter" style="margin-bottom: 15px;">
                                    <label for="dame_recipient_seasons" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php esc_html_e( "Saison d'adhesion", 'dame' ); ?></label>
                                    <?php
                                    $seasons = get_terms( array(
                                        'taxonomy'   => 'dame_saison_adhesion',
                                        'hide_empty' => false,
                                        'orderby'    => 'name',
                                        'order'      => 'DESC',
                                    ) );

                                    if ( ! empty( $seasons ) && ! is_wp_error( $seasons ) ) {
                                        echo '<select id="dame_recipient_seasons" name="dame_recipient_seasons[]" multiple="multiple" style="width: 100%; max-width: 400px; height: 120px;">';
                                        foreach ( $seasons as $season ) {
                                            echo '<option value="' . esc_attr( $season->term_id ) . '">' . esc_html( $season->name ) . '</option>';
                                        }
                                        echo '</select>';
                                        echo '<p class="description" style="margin-top: 5px;">' . esc_html__( 'Maintenez la touche (Ctrl) ou (Cmd) enfoncée pour sélectionner plusieurs saisons.', 'dame' ) . '</p>';
                                    } else {
                                        echo '<p>' . esc_html__( "Aucune saison d'adhésion trouvée.", 'dame' ) . '</p>';
                                    }
                                    ?>
                                </div>

                                <hr style="margin: 15px 0;">

                                <div id="dame-group-saisonnier-filter" style="margin-bottom: 15px;">
                                    <label for="dame_recipient_groups_saisonnier" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php esc_html_e( "Groupes Saisonniers", 'dame' ); ?></label>
                                    <?php
                                    $saisonnier_groups = get_terms( array(
                                        'taxonomy'   => 'dame_group',
                                        'hide_empty' => false,
                                        'orderby'    => 'name',
                                        'order'      => 'ASC',
                                        'meta_query' => array(
                                            'relation' => 'OR',
                                            array(
                                                'key'   => '_dame_group_type',
                                                'value' => 'saisonnier',
                                            ),
                                             array(
                                                'key'     => '_dame_group_type',
                                                'compare' => 'NOT EXISTS',
                                            ),
                                        ),
                                    ) );

                                    if ( ! empty( $saisonnier_groups ) && ! is_wp_error( $saisonnier_groups ) ) {
                                        echo '<select id="dame_recipient_groups_saisonnier" name="dame_recipient_groups_saisonnier[]" multiple="multiple" style="width: 100%; max-width: 400px; height: 120px;">';
                                        foreach ( $saisonnier_groups as $group ) {
                                            echo '<option value="' . esc_attr( $group->term_id ) . '">' . esc_html( $group->name ) . '</option>';
                                        }
                                        echo '</select>';
                                        echo '<p class="description" style="margin-top: 5px;">' . esc_html__( 'Intersection avec la Saison d\'adhésion et le Sexe.', 'dame' ) . '</p>';
                                    } else {
                                        echo '<p>' . esc_html__( "Aucun groupe saisonnier trouvé.", 'dame' ) . '</p>';
                                    }
                                    ?>
                                </div>

                                <hr style="margin: 15px 0;">

                                <div id="dame-group-permanent-filter" style="margin-bottom: 15px;">
                                    <label for="dame_recipient_groups_permanent" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php esc_html_e( "Groupes Permanents", 'dame' ); ?></label>
                                    <?php
                                    $permanent_groups = get_terms( array(
                                        'taxonomy'   => 'dame_group',
                                        'hide_empty' => false,
                                        'orderby'    => 'name',
                                        'order'      => 'ASC',
                                        'meta_query' => array(
                                            array(
                                                'key'   => '_dame_group_type',
                                                'value' => 'permanent',
                                            ),
                                        ),
                                    ) );

                                    if ( ! empty( $permanent_groups ) && ! is_wp_error( $permanent_groups ) ) {
                                        echo '<select id="dame_recipient_groups_permanent" name="dame_recipient_groups_permanent[]" multiple="multiple" style="width: 100%; max-width: 400px; height: 120px;">';
                                        foreach ( $permanent_groups as $group ) {
                                            echo '<option value="' . esc_attr( $group->term_id ) . '">' . esc_html( $group->name ) . '</option>';
                                        }
                                        echo '</select>';
                                        echo '<p class="description" style="margin-top: 5px;">' . esc_html__( 'Union avec le reste de la sélection.', 'dame' ) . '</p>';
                                    } else {
                                        echo '<p>' . esc_html__( "Aucun groupe permanent trouvé.", 'dame' ) . '</p>';
                                    }
                                    ?>
                                </div>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top" class="dame-manual-filters" style="display:none;">
                         <th scope="row"><?php esc_html_e( "Choisir les adhérents", 'dame' ); ?></th>
                         <td>
                            <?php
                            $adherents = get_posts( array( 'post_type' => 'adherent', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
                            if ( ! empty( $adherents ) ) {
                                echo '<select id="dame_manual_recipients" name="dame_manual_recipients[]" multiple="multiple" style="width: 100%; max-width: 400px; height: 250px;">';
                                foreach ( $adherents as $adherent ) {
                                    echo '<option value="' . esc_attr( $adherent->ID ) . '">' . esc_html( $adherent->post_title ) . '</option>';
                                }
                                echo '</select>';
                            } else {
                                echo '<p>' . esc_html__( "Aucun adhérent trouvé.", 'dame' ) . '</p>';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button( __( "Envoyer l'email", 'dame' ) ); ?>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const methodRadios = document.querySelectorAll('input[name="dame_selection_method"]');
        const groupFilters = document.querySelector('.dame-group-filters');
        const manualFilters = document.querySelector('.dame-manual-filters');

        function toggleMethod() {
            if (document.querySelector('input[name="dame_selection_method"]:checked').value === 'group') {
                groupFilters.style.display = 'table-row';
                manualFilters.style.display = 'none';
            } else {
                groupFilters.style.display = 'none';
                manualFilters.style.display = 'table-row';
            }
        }

        methodRadios.forEach(radio => radio.addEventListener('change', toggleMethod));

        // Initial state
        toggleMethod();
    });
    </script>
    <?php
}