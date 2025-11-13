<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the sondage shortcode.
 *
 * @param array $atts The shortcode attributes.
 * @return string The shortcode output.
 */
function dame_sondage_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'slug' => '',
        ),
        $atts,
        'dame_sondage'
    );

    if ( empty( $atts['slug'] ) ) {
        return '<p>' . __( 'Erreur : Le slug du sondage est manquant.', 'dame' ) . '</p>';
    }

    $sondage = get_page_by_path( $atts['slug'], OBJECT, 'sondage' );

    if ( ! $sondage ) {
        return '<p>' . __( 'Erreur : Sondage non trouvé.', 'dame' ) . '</p>';
    }

    $sondage_data = get_post_meta( $sondage->ID, '_dame_sondage_data', true );

    if ( empty( $sondage_data ) ) {
        return '<p>' . __( 'Ce sondage n\'a pas encore été configuré.', 'dame' ) . '</p>';
    }

    $current_user_id = get_current_user_id();
    $user_has_voted = false;
    $user_responses = array();

    if ( $current_user_id ) {
        $existing_response = get_posts( array(
            'post_type'   => 'sondage_reponse',
            'post_status' => 'publish',
            'author'      => $current_user_id,
            'post_parent' => $sondage->ID,
            'posts_per_page' => 1,
        ) );
        if ( ! empty( $existing_response ) ) {
            $user_has_voted = true;
            $user_responses = get_post_meta( $existing_response[0]->ID, '_dame_sondage_responses', true );
        }
    } else {
        $cookie_name = 'dame_sondage_response_' . $sondage->ID;
        if ( isset( $_COOKIE[ $cookie_name ] ) ) {
            $guest_response_id = sanitize_text_field( $_COOKIE[ $cookie_name ] );
            $existing_response = get_posts( array(
                'post_type'   => 'sondage_reponse',
                'post_status' => 'publish',
                'post_parent' => $sondage->ID,
                'posts_per_page' => 1,
                'meta_key'    => '_dame_guest_response_id',
                'meta_value'  => $guest_response_id,
            ) );
            if ( ! empty( $existing_response ) ) {
                $user_has_voted = true;
                $user_responses = get_post_meta( $existing_response[0]->ID, '_dame_sondage_responses', true );
            }
        }
    }

    ob_start();
    ?>
    <div class="dame-sondage-wrapper">
        <h3><?php echo esc_html( $sondage->post_title ); ?></h3>
        <?php if ( ! empty( $sondage->post_content ) ) : ?>
            <div class="sondage-description">
                <?php echo wpautop( wp_kses_post( $sondage->post_content ) ); ?>
            </div>
        <?php endif; ?>

        <?php if ( isset( $_GET['vote'] ) && 'success' === $_GET['vote'] && ! ( is_user_logged_in() && $user_has_voted ) ) : ?>
            <div class="sondage-success-message">
                <p><?php _e( 'Merci, votre réponse a été enregistrée.', 'dame' ); ?></p>
            </div>
        <?php endif; ?>

        <form id="dame-sondage-form-<?php echo esc_attr( $sondage->ID ); ?>" class="dame-sondage-form" method="post">
            <input type="hidden" name="sondage_id" value="<?php echo esc_attr( $sondage->ID ); ?>">
            <?php wp_nonce_field( 'dame_submit_sondage_response_' . $sondage->ID, 'dame_sondage_nonce' ); ?>

            <p>
                <label for="sondage_name"><?php _e( 'Votre nom :', 'dame' ); ?></label>
                <?php
                $current_user = wp_get_current_user();
                if ( is_user_logged_in() ) {
                    $user_name = $current_user->display_name;
                    echo '<input type="text" id="sondage_name" name="sondage_name" value="' . esc_attr( $user_name ) . '" readonly required>';
                } else {
                    $guest_name = ! empty( $existing_response ) ? $existing_response[0]->post_title : '';
                    echo '<input type="text" id="sondage_name" name="sondage_name" value="' . esc_attr( $guest_name ) . '" required>';
                }
                ?>
            </p>

            <table class="dame-sondage-table">
                    <thead>
                        <tr>
                            <th><?php _e( 'Date', 'dame' ); ?></th>
                            <th><?php _e( 'Disponibilités', 'dame' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $sondage_data as $date_index => $date_info ) : ?>
                            <?php
                                $date_obj = new DateTime( $date_info['date'] );
                                $formatted_date = date_i18n( 'l j F Y', $date_obj->getTimestamp() );
                            ?>
                            <tr>
                                <td><?php echo esc_html( $formatted_date ); ?></td>
                                <td>
                                    <?php if ( ! empty( $date_info['time_slots'] ) ) : ?>
                                        <?php foreach ( $date_info['time_slots'] as $time_index => $time_slot ) : ?>
                                            <?php
                                            $checked = '';
                                            if ( isset( $user_responses[ $date_index ][ $time_index ] ) && $user_responses[ $date_index ][ $time_index ] == '1' ) {
                                                $checked = 'checked';
                                            }
                                            ?>
                                            <label class="sondage-timeslot-label">
                                                <input type="checkbox" name="sondage_responses[<?php echo esc_attr( $date_index ); ?>][<?php echo esc_attr( $time_index ); ?>]" value="1" <?php echo $checked; ?>>
                                                <?php echo esc_html( $time_slot['start'] . ' - ' . $time_slot['end'] ); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <?php _e( 'Aucune plage horaire définie pour cette date.', 'dame' ); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p>
                    <input type="submit" name="submit_sondage" value="<?php echo $user_has_voted ? __( 'Mettre à jour', 'dame' ) : __( 'Voter', 'dame' ); ?>">
                    <?php if ( is_user_logged_in() && isset( $_GET['vote'] ) && 'success' === $_GET['vote'] && $user_has_voted ) : ?>
                        <span class="sondage-success-message-inline" style="margin-left: 10px; color: green;"><?php _e( 'Merci, votre réponse a été enregistrée.', 'dame' ); ?></span>
                    <?php endif; ?>
                </p>
            </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'dame_sondage', 'dame_sondage_shortcode' );

/**
 * Handle sondage form submission.
 */
function dame_handle_sondage_submission() {
    if ( ! isset( $_POST['submit_sondage'], $_POST['dame_sondage_nonce'] ) ) {
        return;
    }

    $sondage_id = isset( $_POST['sondage_id'] ) ? intval( $_POST['sondage_id'] ) : 0;

    if ( ! $sondage_id || ! wp_verify_nonce( $_POST['dame_sondage_nonce'], 'dame_submit_sondage_response_' . $sondage_id ) ) {
        wp_die( 'Invalid nonce.' );
    }

    $name = sanitize_text_field( $_POST['sondage_name'] );
    $responses = isset( $_POST['sondage_responses'] ) ? (array) $_POST['sondage_responses'] : array();

    // Sanitize responses
    $sanitized_responses = array();
    foreach ($responses as $date_index => $time_slots) {
        foreach ($time_slots as $time_index => $value) {
            $sanitized_responses[intval($date_index)][intval($time_index)] = 1;
        }
    }

    $user_id = get_current_user_id();
    $existing_response_id = 0;

    if ( $user_id ) {
        $existing_responses = get_posts( array(
            'post_type'   => 'sondage_reponse',
            'post_status' => 'publish',
            'author'      => $user_id,
            'post_parent' => $sondage_id,
            'posts_per_page' => 1,
            'fields'      => 'ids',
        ) );
        if ( ! empty( $existing_responses ) ) {
            $existing_response_id = $existing_responses[0];
        }
    } else {
        $cookie_name = 'dame_sondage_response_' . $sondage_id;
        if ( isset( $_COOKIE[ $cookie_name ] ) ) {
            $guest_response_id = sanitize_text_field( $_COOKIE[ $cookie_name ] );
            $existing_responses = get_posts( array(
                'post_type'   => 'sondage_reponse',
                'post_status' => 'publish',
                'post_parent' => $sondage_id,
                'posts_per_page' => 1,
                'fields'      => 'ids',
                'meta_key'    => '_dame_guest_response_id',
                'meta_value'  => $guest_response_id,
            ) );
            if ( ! empty( $existing_responses ) ) {
                $existing_response_id = $existing_responses[0];
            }
        }
    }

    $post_data = array(
        'post_title'   => $name,
        'post_type'    => 'sondage_reponse',
        'post_status'  => 'publish',
        'post_parent'  => $sondage_id,
        'post_author'  => $user_id,
    );

    if ( $existing_response_id ) {
        $post_data['ID'] = $existing_response_id;
        wp_update_post( $post_data );
        $response_id = $existing_response_id;
    } else {
        $response_id = wp_insert_post( $post_data );
    }

    if ( $response_id ) {
        update_post_meta( $response_id, '_dame_sondage_responses', $sanitized_responses );

        if ( ! $user_id ) {
            $cookie_name = 'dame_sondage_response_' . $sondage_id;
            $cookie_value = isset( $_COOKIE[$cookie_name] ) ? $_COOKIE[$cookie_name] : uniqid( 'sondage_' . $sondage_id . '_' );

            update_post_meta( $response_id, '_dame_guest_response_id', $cookie_value );
            // Cookie is valid for 1 year.
            setcookie( $cookie_name, $cookie_value, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
        }
    }

    // Redirect to the same page with a success query arg.
    $redirect_url = add_query_arg( 'vote', 'success', wp_get_referer() );
    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'init', 'dame_handle_sondage_submission' );
