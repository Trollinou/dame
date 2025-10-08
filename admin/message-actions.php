<?php
/**
 * File for handling message actions like duplicate and test send.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles the duplication of a message.
 *
 * Triggers on the 'admin_action_dame_duplicate' hook.
 */
function dame_duplicate_message_action() {
    // Check for required GET parameters.
    if ( ! isset( $_GET['action'], $_GET['post'], $_GET['_wpnonce'] ) ) {
        return;
    }

    // Verify the action and post type.
    if ( 'dame_duplicate' !== $_GET['action'] || 'dame_message' !== get_post_type( $_GET['post'] ) ) {
        return;
    }

    // Verify the nonce for security.
    if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'dame_duplicate_message_' . $_GET['post'] ) ) {
        wp_die( 'Security check failed.' );
    }

    // Check user permissions.
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( 'You do not have permission to duplicate this message.' );
    }

    $post_id = absint( $_GET['post'] );
    $post = get_post( $post_id );

    if ( ! $post ) {
        wp_safe_redirect( admin_url( 'edit.php?post_type=dame_message' ) );
        exit;
    }

    $new_post_author = wp_get_current_user();

    // Create the new post array as a copy.
    $new_post = array(
        'post_title'   => $post->post_title . ' (Copie)',
        'post_content' => $post->post_content,
        'post_status'  => 'draft',
        'post_type'    => $post->post_type,
        'post_author'  => $new_post_author->ID,
    );

    // Insert the new post.
    wp_insert_post( $new_post );

    // Redirect back to the message list.
    wp_safe_redirect( admin_url( 'edit.php?post_type=dame_message' ) );
    exit;
}
add_action( 'admin_action_dame_duplicate', 'dame_duplicate_message_action' );

/**
 * Handles sending a test email for a message.
 *
 * Triggers on the 'admin_action_dame_send_test' hook.
 */
function dame_send_test_message_action() {
    // Check for required GET parameters.
    if ( ! isset( $_GET['action'], $_GET['post'], $_GET['_wpnonce'] ) ) {
        return;
    }

    $post_id = absint( $_GET['post'] );

    // Verify the action and post type.
    if ( 'dame_send_test' !== $_GET['action'] || 'dame_message' !== get_post_type( $post_id ) ) {
        return;
    }

    // Verify the nonce for security.
    if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'dame_send_test_message_' . $post_id ) ) {
        wp_die( 'Security check failed.' );
    }

    // Check user permissions.
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( 'You do not have permission to send this test message.' );
    }

    $post = get_post( $post_id );
    $user = wp_get_current_user();

    // Ensure we have a post and a user email.
    if ( ! $post || ! is_a( $user, 'WP_User' ) || empty( $user->user_email ) ) {
        wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=dame_message' ) );
        exit;
    }

    // Prepare and send the email.
    $subject = '[TEST] ' . $post->post_title;
    $content = apply_filters( 'the_content', $post->post_content );
    $message = '<div style="margin: 1cm;">' . $content . '</div>';

    $options = get_option( 'dame_options' );
    $sender_email = isset( $options['sender_email'] ) && is_email( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo( 'name' ) . ' <' . $sender_email . '>',
    );

    wp_mail( $user->user_email, $subject, $message, $headers );

    // Redirect back with a success notice.
    $referer = wp_get_referer();
    $redirect_url = add_query_arg( 'dame_test_sent', '1', $referer ? $referer : admin_url( 'edit.php?post_type=dame_message' ) );
    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'admin_action_dame_send_test', 'dame_send_test_message_action' );

/**
 * Displays an admin notice after a test email has been sent.
 */
function dame_show_test_sent_notice() {
    if ( isset( $_GET['dame_test_sent'] ) && '1' === $_GET['dame_test_sent'] ) {
        $user = wp_get_current_user();
        if ( ! is_a( $user, 'WP_User' ) ) return;

        $notice = sprintf(
            __( 'Test email sent to %s.', 'dame' ),
            '<strong>' . esc_html( $user->user_email ) . '</strong>'
        );
        echo '<div class="notice notice-success is-dismissible"><p>' . $notice . '</p></div>';
    }
}
add_action( 'admin_notices', 'dame_show_test_sent_notice' );


/**
 * Adds "Duplicate" and "Send Test" links to the message list row actions.
 *
 * @param array   $actions The existing row actions.
 * @param WP_Post $post    The post object.
 * @return array The modified row actions.
 */
function dame_add_message_row_actions( $actions, $post ) {
    if ( 'dame_message' === $post->post_type && current_user_can( 'edit_post', $post->ID ) ) {
        // Duplicate link.
        $duplicate_nonce = wp_create_nonce( 'dame_duplicate_message_' . $post->ID );
        $duplicate_url = admin_url( 'admin.php?action=dame_duplicate&post=' . $post->ID . '&_wpnonce=' . $duplicate_nonce );
        $actions['duplicate'] = '<a href="' . esc_url( $duplicate_url ) . '" aria-label="' . esc_attr__( 'Duplicate this message', 'dame' ) . '">' . __( 'Dupliquer', 'dame' ) . '</a>';

        // Send Test link.
        $send_test_nonce = wp_create_nonce( 'dame_send_test_message_' . $post->ID );
        $send_test_url = admin_url( 'admin.php?action=dame_send_test&post=' . $post->ID . '&_wpnonce=' . $send_test_nonce );
        $actions['send_test'] = '<a href="' . esc_url( $send_test_url ) . '" aria-label="' . esc_attr__( 'Send a test email to yourself', 'dame' ) . '">' . __( 'Envoyer un test', 'dame' ) . '</a>';
    }
    return $actions;
}
add_filter( 'post_row_actions', 'dame_add_message_row_actions', 10, 2 );

/**
 * Adds a metabox to the message edit screen for sending a test email.
 */
function dame_add_test_send_metabox() {
    add_meta_box(
        'dame_test_send',
        __( 'Test d\'envoi', 'dame' ),
        'dame_render_test_send_metabox',
        'dame_message',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'dame_add_test_send_metabox' );

/**
 * Renders the content of the test send metabox.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_test_send_metabox( $post ) {
    $send_test_nonce = wp_create_nonce( 'dame_send_test_message_' . $post->ID );
    // This action URL should point to admin.php to be caught by 'admin_action_*'
    $send_test_url = admin_url( 'admin.php?action=dame_send_test&post=' . $post->ID . '&_wpnonce=' . $send_test_nonce );

    $user = wp_get_current_user();
    $email_text = sprintf(
        esc_html__( 'Click the button below to send this message to your email address (%s) for testing purposes.', 'dame' ),
        '<strong>' . esc_html( $user->user_email ) . '</strong>'
    );
    ?>
    <p><?php echo $email_text; ?></p>
    <a href="<?php echo esc_url( $send_test_url ); ?>" class="button button-secondary">
        <?php esc_html_e( 'S\'envoyer un email de test', 'dame' ); ?>
    </a>
    <?php
}