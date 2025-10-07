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

    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( 'You do not have permission to do this.' );
    }

    $article_id = isset( $_POST['dame_article_to_send'] ) ? absint( $_POST['dame_article_to_send'] ) : 0;
    $selection_method = isset( $_POST['dame_selection_method'] ) ? sanitize_key( $_POST['dame_selection_method'] ) : '';
    $recipient_gender = isset( $_POST['dame_recipient_gender'] ) ? sanitize_text_field( $_POST['dame_recipient_gender'] ) : 'all';

    if ( empty( $article_id ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__( "Veuillez sélectionner un article à envoyer.", 'dame' ) . '</p></div>';
        });
        return;
    }

    $adherent_ids = array();

    if ( 'group' === $selection_method ) {
        $groups = isset( $_POST['dame_recipient_groups'] ) ? (array) array_map( 'absint', $_POST['dame_recipient_groups'] ) : array();
        $seasons = isset( $_POST['dame_recipient_seasons'] ) ? (array) array_map( 'absint', $_POST['dame_recipient_seasons'] ) : array();

        if ( empty( $groups ) && empty( $seasons ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="error"><p>' . esc_html__( "Veuillez sélectionner au moins un filtre (saison ou groupe).", 'dame' ) . '</p></div>';
            });
            return;
        }

        $season_adherent_ids = array();
        $group_adherent_ids = array();

        // Get adherents by season
        if ( ! empty( $seasons ) ) {
            $season_query_args = array(
                'post_type'      => 'adherent',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'dame_saison_adhesion',
                        'field'    => 'term_id',
                        'terms'    => $seasons,
                        'operator' => 'IN',
                    ),
                ),
                'meta_query' => array(),
            );

            if ( 'all' !== $recipient_gender ) {
                $season_query_args['meta_query'][] = array(
                    'key' => '_dame_sexe',
                    'value' => $recipient_gender,
                );
            }
            $season_adherent_ids = get_posts( $season_query_args );
        }

        // Get adherents by group
        if ( ! empty( $groups ) ) {
            $group_query_args = array(
                'post_type'      => 'adherent',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'dame_group',
                        'field'    => 'term_id',
                        'terms'    => $groups,
                        'operator' => 'IN',
                    ),
                ),
                'meta_query' => array(),
            );

            if ( 'all' !== $recipient_gender ) {
                $group_query_args['meta_query'][] = array(
                    'key'   => '_dame_sexe',
                    'value' => $recipient_gender,
                );
            }
            $group_adherent_ids = get_posts( $group_query_args );
        }

        // Merge and get unique IDs
        $adherent_ids = array_unique( array_merge( $season_adherent_ids, $group_adherent_ids ) );

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
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p><?php esc_html_e( "Cette page vous permet d'envoyer un article du site à une sélection d'adhérents.", 'dame' ); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field( 'dame_send_email_nonce', 'dame_send_email_nonce_field' ); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e( "Filtrer par catégorie", 'dame' ); ?></th>
                        <td>
                            <fieldset id="dame-category-filter">
                                <legend class="screen-reader-text"><span><?php esc_html_e( "Catégories", 'dame' ); ?></span></legend>
                                <?php
                                $categories = get_categories( array( 'hide_empty' => false ) );
                                if ( ! empty( $categories ) ) {
                                    foreach ( $categories as $category ) {
                                        echo '<label style="margin-right: 15px; font-weight: normal;">';
                                        echo '<input type="checkbox" name="dame_article_categories[]" value="' . esc_attr( $category->term_id ) . '">';
                                        echo ' ' . esc_html( $category->name );
                                        echo '</label>';
                                    }
                                } else {
                                    echo '<p>' . esc_html__( "Aucune catégorie trouvée.", 'dame' ) . '</p>';
                                }
                                ?>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="dame_article_to_send"><?php esc_html_e( "Article à envoyer", 'dame' ); ?></label>
                        </th>
                        <td>
                            <div id="dame-articles-list-container">
                                <?php
                                $posts = get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'private' ), 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
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
                            </div>
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
                                        $current_season_id = get_option( 'dame_current_season_tag_id' );
                                        echo '<select id="dame_recipient_seasons" name="dame_recipient_seasons[]" multiple="multiple" style="width: 100%; max-width: 400px; height: 120px;">';
                                        foreach ( $seasons as $season ) {
                                            echo '<option value="' . esc_attr( $season->term_id ) . '" ' . selected( $current_season_id, $season->term_id, false ) . '>' . esc_html( $season->name ) . '</option>';
                                        }
                                        echo '</select>';
                                        echo '<p class="description" style="margin-top: 5px;">' . esc_html__( 'Maintenez la touche (Ctrl) ou (Cmd) enfoncée pour sélectionner plusieurs saisons.', 'dame' ) . '</p>';
                                    } else {
                                        echo '<p>' . esc_html__( "Aucune saison d'adhésion trouvée.", 'dame' ) . '</p>';
                                    }
                                    ?>
                                </div>

                                <hr style="margin: 15px 0;">

                                <div id="dame-group-filter" style="margin-bottom: 15px;">
                                    <label for="dame_recipient_groups" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php esc_html_e( "Groupes", 'dame' ); ?></label>
                                    <?php
                                    $groups = get_terms( array(
                                        'taxonomy'   => 'dame_group',
                                        'hide_empty' => false,
                                        'orderby'    => 'name',
                                        'order'      => 'ASC',
                                    ) );

                                    if ( ! empty( $groups ) && ! is_wp_error( $groups ) ) {
                                        echo '<select id="dame_recipient_groups" name="dame_recipient_groups[]" multiple="multiple" style="width: 100%; max-width: 400px; height: 120px;">';
                                        foreach ( $groups as $group ) {
                                            echo '<option value="' . esc_attr( $group->term_id ) . '">' . esc_html( $group->name ) . '</option>';
                                        }
                                        echo '</select>';
                                        echo '<p class="description" style="margin-top: 5px;">' . esc_html__( 'Maintenez la touche (Ctrl) ou (Cmd) enfoncée pour sélectionner plusieurs groupes.', 'dame' ) . '</p>';
                                    } else {
                                        echo '<p>' . esc_html__( "Aucun groupe trouvé.", 'dame' ) . '</p>';
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

/**
 * AJAX handler for fetching filtered articles.
 */
function dame_get_filtered_articles_ajax_handler() {
    // 1. Security checks
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dame_filter_articles_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( "Vérification de sécurité échouée.", 'dame' ) ), 403 );
    }

    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_send_json_error( array( 'message' => __( "Vous n'avez pas la permission d'effectuer cette action.", 'dame' ) ), 403 );
    }

    // 2. Get and sanitize categories
    $category_ids = isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ? array_map( 'absint', $_POST['categories'] ) : array();

    // 3. Build query arguments
    $args = array(
        'post_type'      => 'post',
        'post_status'    => array( 'publish', 'private' ),
        'numberposts'    => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( ! empty( $category_ids ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => $category_ids,
                'operator' => 'IN',
            ),
        );
    }

    // 4. Fetch posts
    $posts = get_posts( $args );
    $results = array();

    if ( ! empty( $posts ) ) {
        foreach ( $posts as $p ) {
            $results[] = array(
                'ID'         => $p->ID,
                'post_title' => esc_html( get_the_title( $p->ID ) ),
            );
        }
    }

    // 5. Send response
    wp_send_json_success( $results );
}
add_action( 'wp_ajax_dame_get_filtered_articles', 'dame_get_filtered_articles_ajax_handler' );
