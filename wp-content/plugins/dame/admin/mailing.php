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
        $group = isset( $_POST['dame_recipient_group'] ) ? sanitize_key( $_POST['dame_recipient_group'] ) : '';
        $meta_query = array();

        switch ( $group ) {
            case 'juniors':
                $meta_query[] = array( 'key' => '_dame_is_junior', 'value' => '1' );
                break;
            case 'pole_excellence':
                $meta_query[] = array( 'key' => '_dame_is_pole_excellence', 'value' => '1' );
                break;
            case 'status':
                $statuses = isset( $_POST['dame_membership_status'] ) ? (array) array_map( 'sanitize_key', $_POST['dame_membership_status'] ) : array();
                if ( empty( $statuses ) ) {
                    add_action( 'admin_notices', function() {
                        echo '<div class="error"><p>' . esc_html__( "Veuillez sélectionner au moins un état d'adhésion.", 'dame' ) . '</p></div>';
                    });
                    return;
                }
                $meta_query[] = array( 'key' => '_dame_membership_status', 'value' => $statuses, 'compare' => 'IN' );
                break;
        }

        if ( ! empty( $meta_query ) ) {
            $query_args = array(
                'post_type' => 'adherent',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => $meta_query
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
    $message = apply_filters( 'the_content', $article->post_content );

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

    // Final implementation: loop and send individually.
    $sent_count = 0;
    foreach ( $recipient_emails as $recipient_email ) {
        if ( wp_mail( $recipient_email, $subject, $message, $headers ) ) {
            $sent_count++;
        }
    }

    // Admin notice for the UI
    add_action( 'admin_notices', function() use ( $sent_count, $recipient_emails ) {
        $total_recipients = count( $recipient_emails );
        if ( $sent_count === $total_recipients && $sent_count > 0 ) {
            $message = sprintf(
                esc_html( _n( "Email envoyé avec succès à %d destinataire.", "Email envoyé avec succès à %d destinataires.", $sent_count, 'dame' ) ),
                $sent_count
            );
            echo '<div class="updated"><p>' . $message . '</p></div>';
        } elseif ( $sent_count > 0 ) {
            $message = sprintf(
                esc_html__( "L'envoi a réussi pour %d sur %d destinataires.", 'dame' ),
                $sent_count,
                $total_recipients
            );
            echo '<div class="notice notice-warning"><p>' . $message . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__( "L'envoi des emails a échoué pour tous les destinataires.", 'dame' ) . '</p></div>';
        }
    });

    // Send a confirmation email to the sender for traceability
    if ( $sent_count > 0 ) {
        $confirmation_subject = sprintf( __( '[Confirmation] Envoi de l\'article "%s"', 'dame' ), $subject );
        $confirmation_message = sprintf(
            esc_html__( "Bonjour,\n\nCeci est une confirmation que l'article \"%s\" a bien été envoyé à %d membre(s) de votre sélection.\n\nContenu de l'article envoyé :\n\n%s", 'dame' ),
            $subject,
            $sent_count,
            wp_strip_all_tags( $message ) // Send a plain text version for the confirmation
        );
        wp_mail( $sender_email, $confirmation_subject, $confirmation_message );
    }
}
add_action( 'admin_init', 'dame_handle_send_email' );


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
        $linked_user_id = get_post_meta( $adherent_id, '_dame_linked_wp_user', true );
        if ( ! empty( $linked_user_id ) && $linked_user_id > 0 ) {
            $user_data = get_userdata( $linked_user_id );
            if ( $user_data && ! empty( $user_data->user_email ) ) {
                $emails[] = $user_data->user_email;
            }
        } else {
            $member_email = get_post_meta( $adherent_id, '_dame_email', true );
            if ( ! empty( $member_email ) ) {
                $emails[] = $member_email;
            }
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
                                <label><input type="radio" name="dame_selection_method" value="group" checked> <?php esc_html_e( "Par groupe (sélection exclusive)", 'dame' ); ?></label><br>
                                <label><input type="radio" name="dame_selection_method" value="manual"> <?php esc_html_e( "Manuelle", 'dame' ); ?></label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top" class="dame-group-filters">
                        <th scope="row"><?php esc_html_e( "Choisir un groupe", 'dame' ); ?></th>
                        <td>
                             <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e( "Groupes de destinataires", 'dame' ); ?></span></legend>
                                <label>
                                    <input type="radio" name="dame_recipient_group" value="juniors" checked>
                                    <?php esc_html_e( "Juniors", 'dame' ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="dame_recipient_group" value="pole_excellence">
                                    <?php esc_html_e( "Pôle Excellence", 'dame' ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="dame_recipient_group" value="status">
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

        const groupSubRadios = document.querySelectorAll('input[name="dame_recipient_group"]');
        const statusCheckboxes = document.getElementById('dame-status-checkboxes');

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
            if (document.querySelector('input[name="dame_recipient_group"]:checked').value === 'status') {
                statusCheckboxes.style.display = 'block';
            } else {
                statusCheckboxes.style.display = 'none';
            }
        }

        methodRadios.forEach(radio => radio.addEventListener('change', toggleMethod));
        groupSubRadios.forEach(radio => radio.addEventListener('change', toggleStatusCheckboxes));

        // Initial state
        toggleMethod();
        toggleStatusCheckboxes();
    });
    </script>
    <?php
}
