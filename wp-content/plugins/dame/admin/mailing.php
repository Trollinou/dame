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
 * Add the mailing page to the Adherent CPT menu.
 */
function dame_add_mailing_page() {
    add_submenu_page(
        'edit.php?post_type=adherent',
        __( 'Envoyer un article', 'dame' ),
        __( 'Envoyer un article', 'dame' ),
        'manage_options', // Capability
        'dame-mailing',
        'dame_render_mailing_page'
    );
}
add_action( 'admin_menu', 'dame_add_mailing_page' );

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

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to do this.' );
    }

    $article_id = isset( $_POST['dame_article_to_send'] ) ? absint( $_POST['dame_article_to_send'] ) : 0;
    $selection_method = isset( $_POST['dame_selection_method'] ) ? sanitize_key( $_POST['dame_selection_method'] ) : '';

    if ( empty( $article_id ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__( "Veuillez sélectionner un article à envoyer.", 'dame' ) . '</p></div>';
        });
        return;
    }

    $adherent_ids = array();

    if ( 'group' === $selection_method ) {
        $groups = isset( $_POST['dame_recipient_group'] ) ? (array) array_map( 'sanitize_key', $_POST['dame_recipient_group'] ) : array();
        $meta_query = array( 'relation' => 'OR' );

        if ( empty( $groups ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="error"><p>' . esc_html__( "Veuillez sélectionner au moins un groupe.", 'dame' ) . '</p></div>';
            });
            return;
        }

        foreach ( $groups as $group ) {
            switch ( $group ) {
                case 'juniors':
                    $meta_query[] = array( 'key' => '_dame_is_junior', 'value' => '1' );
                    break;
                case 'pole_excellence':
                    $meta_query[] = array( 'key' => '_dame_is_pole_excellence', 'value' => '1' );
                    break;
                case 'benevoles':
                    $meta_query[] = array( 'key' => '_dame_is_benevole', 'value' => '1' );
                    break;
                case 'elus_locaux':
                    $meta_query[] = array( 'key' => '_dame_is_elu_local', 'value' => '1' );
                    break;
                case 'status':
                    $statuses = isset( $_POST['dame_membership_status'] ) ? (array) array_map( 'sanitize_key', $_POST['dame_membership_status'] ) : array();
                    if ( empty( $statuses ) ) {
                        add_action( 'admin_notices', function() {
                            echo '<div class="error"><p>' . esc_html__( "Pour le groupe 'Par état d'adhésion', veuillez sélectionner au moins un état.", 'dame' ) . '</p></div>';
                        });
                        return;
                    }
                    $meta_query[] = array( 'key' => '_dame_membership_status', 'value' => $statuses, 'compare' => 'IN' );
                    break;
            }
        }

        // We need at least one condition besides 'relation'
        if ( count( $meta_query ) > 1 ) {
            $query_args = array(
                'post_type' => 'adherent',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => $meta_query,
            );
            $adherent_ids = get_posts( $query_args );
        }

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

    $article = get_post( $article_id );
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
    $dame_bcc_emails = $recipient_emails;

    wp_mail( $sender_email, $subject, $message, $headers );

    // Clean up the global after the mail is sent.
    $dame_bcc_emails = null;

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
        // Clean up the global to prevent it from affecting other wp_mail calls.
        $dame_bcc_emails = null;
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
    $birth_date_str = get_post_meta( $adherent_id, '_dame_birth_date', true );

    $is_minor = false;
    if ( ! empty( $birth_date_str ) ) {
        try {
            $birth_date = new DateTime( $birth_date_str );
            $today = new DateTime();
            $age = $today->diff( $birth_date )->y;
            if ( $age < 18 ) {
                $is_minor = true;
            }
        } catch ( Exception $e ) {
            // Invalid date format, treat as adult for safety.
            $is_minor = false;
        }
    }

    if ( $is_minor ) {
        $rep1_email = get_post_meta( $adherent_id, '_dame_legal_rep_1_email', true );
        $rep2_email = get_post_meta( $adherent_id, '_dame_legal_rep_2_email', true );
        if ( ! empty( $rep1_email ) ) {
            $emails[] = $rep1_email;
        }
        if ( ! empty( $rep2_email ) ) {
            $emails[] = $rep2_email;
        }
    } else {
        // Always use the email from the adherent's profile, not the linked WP user.
        $member_email = get_post_meta( $adherent_id, '_dame_email', true );
        if ( ! empty( $member_email ) ) {
            $emails[] = $member_email;
        }
    }

    return $emails;
}

/**
 * Renders the mailing page.
 */
function dame_render_mailing_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p><?php esc_html_e( "Cette page vous permet d'envoyer un article du site à une sélection d'adhérents.", 'dame' ); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field( 'dame_send_email_nonce', 'dame_send_email_nonce_field' ); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="dame_article_to_send"><?php esc_html_e( "Article à envoyer", 'dame' ); ?></label>
                        </th>
                        <td>
                            <?php
                            $posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 10, 'orderby' => 'date', 'order' => 'DESC' ) );
                            if ( ! empty( $posts ) ) {
                                echo '<select id="dame_article_to_send" name="dame_article_to_send" style="width: 100%; max-width: 400px;">';
                                foreach ( $posts as $p ) {
                                    echo '<option value="' . esc_attr( $p->ID ) . '">' . esc_html( $p->post_title ) . '</option>';
                                }
                                echo '</select>';
                            } else {
                                echo '<p>' . esc_html__( "Aucun article publié trouvé.", 'dame' ) . '</p>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( "Méthode de sélection", 'dame' ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e( "Méthode de sélection", 'dame' ); ?></span></legend>
                                <label><input type="radio" name="dame_selection_method" value="group" checked> <?php esc_html_e( "Par groupe (sélection multiple)", 'dame' ); ?></label><br>
                                <label><input type="radio" name="dame_selection_method" value="manual"> <?php esc_html_e( "Manuelle", 'dame' ); ?></label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top" class="dame-group-filters">
                        <th scope="row"><?php esc_html_e( "Choisir un ou plusieurs groupes", 'dame' ); ?></th>
                        <td>
                             <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e( "Groupes de destinataires", 'dame' ); ?></span></legend>
                                <label>
                                    <input type="checkbox" name="dame_recipient_group[]" value="juniors" checked>
                                    <?php esc_html_e( "École d'échecs", 'dame' ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="dame_recipient_group[]" value="pole_excellence">
                                    <?php esc_html_e( "Pôle Excellence", 'dame' ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="dame_recipient_group[]" value="benevoles">
                                    <?php esc_html_e( "Bénévoles", 'dame' ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="dame_recipient_group[]" value="elus_locaux">
                                    <?php esc_html_e( "Elus locaux", 'dame' ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" id="dame_recipient_group_status" name="dame_recipient_group[]" value="status">
                                    <?php esc_html_e( "Par état d'adhésion", 'dame' ); ?>
                                </label>
                                <div id="dame-status-checkboxes" style="display:none; margin-top: 10px; padding-left: 20px;">
                                    <label><input type="checkbox" name="dame_membership_status[]" value="A"> <?php esc_html_e( "Actif", 'dame' ); ?></label><br>
                                    <label><input type="checkbox" name="dame_membership_status[]" value="E"> <?php esc_html_e( "Expiré", 'dame' ); ?></label><br>
                                    <label><input type="checkbox" name="dame_membership_status[]" value="X"> <?php esc_html_e( "Ancien", 'dame' ); ?></label>
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

        const groupSubRadios = document.querySelectorAll('input[name="dame_recipient_group[]"]');
        const statusCheckboxes = document.getElementById('dame-status-checkboxes');
        const statusCheckbox = document.getElementById('dame_recipient_group_status');

        function toggleMethod() {
            if (document.querySelector('input[name="dame_selection_method"]:checked').value === 'group') {
                groupFilters.style.display = 'table-row';
                manualFilters.style.display = 'none';
            } else {
                groupFilters.style.display = 'none';
                manualFilters.style.display = 'table-row';
            }
        }

        function toggleStatusCheckboxes() {
            if (statusCheckbox.checked) {
                statusCheckboxes.style.display = 'block';
            } else {
                statusCheckboxes.style.display = 'none';
            }
        }

        methodRadios.forEach(radio => radio.addEventListener('change', toggleMethod));
        statusCheckbox.addEventListener('change', toggleStatusCheckboxes);

        // Initial state
        toggleMethod();
        toggleStatusCheckboxes();
    });
    </script>
    <?php
}
