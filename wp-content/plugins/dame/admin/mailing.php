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
    $recipient_groups = isset( $_POST['dame_recipient_groups'] ) ? (array) $_POST['dame_recipient_groups'] : array();
    $manual_recipients = isset( $_POST['dame_manual_recipients'] ) ? (array) array_map( 'absint', $_POST['dame_manual_recipients'] ) : array();

    if ( empty( $article_id ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__( "Veuillez sélectionner un article à envoyer.", 'dame' ) . '</p></div>';
        });
        return;
    }

    if ( empty( $recipient_groups ) && empty( $manual_recipients ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__( "Veuillez sélectionner au moins un groupe ou un adhérent.", 'dame' ) . '</p></div>';
        });
        return;
    }

    $adherent_ids = array();

    if ( ! empty( $recipient_groups ) ) {
        $meta_query = array( 'relation' => 'OR' );
        if ( in_array( 'juniors', $recipient_groups ) ) {
            $meta_query[] = array( 'key' => '_dame_is_junior', 'value' => '1' );
        }
        if ( in_array( 'pole_excellence', $recipient_groups ) ) {
            $meta_query[] = array( 'key' => '_dame_is_pole_excellence', 'value' => '1' );
        }
        if ( in_array( 'active_expired', $recipient_groups ) ) {
            $meta_query[] = array( 'key' => '_dame_membership_status', 'value' => array('A', 'E'), 'compare' => 'IN' );
        }

        $query_args = array(
            'post_type' => 'adherent',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => $meta_query
        );
        $adherent_ids = get_posts( $query_args );
    }

    $all_adherent_ids = array_unique( array_merge( $adherent_ids, $manual_recipients ) );

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
    $sender_email = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : get_option('admin_email');
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo( 'name' ) . ' <' . $sender_email . '>',
    );

    // Use Bcc to protect privacy
    $headers[] = 'Bcc: ' . implode( ',', $recipient_emails );

    wp_mail( $sender_email, $subject, $message, $headers );

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
                            $posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1 ) );
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
                    <tr>
                        <th scope="row"><?php esc_html_e( "Destinataires", 'dame' ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php esc_html_e( "Groupes de destinataires", 'dame' ); ?></span></legend>
                                <label>
                                    <input type="checkbox" name="dame_recipient_groups[]" value="juniors">
                                    <?php esc_html_e( "Juniors", 'dame' ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="dame_recipient_groups[]" value="pole_excellence">
                                    <?php esc_html_e( "Pôle Excellence", 'dame' ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="dame_recipient_groups[]" value="active_expired">
                                    <?php esc_html_e( "Adhérents (Actifs + Échus)", 'dame' ); ?>
                                </label>
                            </fieldset>
                            <hr>
                            <p><strong><?php esc_html_e( "Ou sélectionner manuellement des adhérents :", 'dame' ); ?></strong></p>
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
    <?php
}
