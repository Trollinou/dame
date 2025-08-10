<?php
/**
 * File for handling the user assignment admin page.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handle the user assignment form submission.
 */
function dame_handle_user_assignment() {
    if ( ! isset( $_POST['assign_user'] ) || ! isset( $_POST['dame_user_assignment_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dame_user_assignment_nonce'] ) ), 'dame_user_assignment_action' ) ) {
        dame_add_admin_notice( __( 'La vérification de sécurité a échoué.', 'dame' ), 'error' );
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        dame_add_admin_notice( __( "Vous n'avez pas la permission d'effectuer cette action.", 'dame' ), 'error' );
        return;
    }

    $member_id = absint( $_POST['assign_user'] );
    $user_id   = isset( $_POST['user_id'][ $member_id ] ) ? absint( $_POST['user_id'][ $member_id ] ) : 0;
    $role      = isset( $_POST['role'][ $member_id ] ) ? sanitize_text_field( $_POST['role'][ $member_id ] ) : '';

    if ( ! $member_id || ! get_post( $member_id ) ) {
        dame_add_admin_notice( __( "L'adhérent spécifié est invalide.", 'dame' ), 'error' );
        return;
    }

    if ( ! $user_id ) {
        dame_add_admin_notice( __( 'Veuillez sélectionner un compte WordPress à assigner.', 'dame' ), 'warning' );
        return;
    }

    // Check if user exists
    $user = get_user_by( 'ID', $user_id );
    if ( ! $user ) {
        dame_add_admin_notice( __( "Le compte WordPress sélectionné n'existe pas.", 'dame' ), 'error' );
        return;
    }

    // Check if role is valid
    global $wp_roles;
    if ( ! in_array( $role, array_keys( $wp_roles->get_names() ), true ) ) {
        dame_add_admin_notice( __( 'Le rôle sélectionné est invalide.', 'dame' ), 'error' );
        return;
    }

    // Update member meta and user role
    update_post_meta( $member_id, '_dame_linked_wp_user', $user_id );
    $user->set_role( $role );

    $member_name = get_the_title( $member_id );
    $message = sprintf(
        /* translators: 1: User name, 2: Member name */
        esc_html__( 'Le compte WordPress "%1$s" a été assigné à l\'adhérent "%2$s" avec le rôle "%3$s".', 'dame' ),
        esc_html( $user->display_name ),
        esc_html( $member_name ),
        esc_html( $wp_roles->roles[ $role ]['name'] )
    );
    dame_add_admin_notice( $message, 'success' );

    // Redirect to the same page to prevent form resubmission
    wp_safe_redirect( admin_url( 'edit.php?post_type=adherent&page=dame-user-assignment' ) );
    exit;
}
add_action( 'admin_init', 'dame_handle_user_assignment' );

/**
 * Add the user assignment page to the Adherent CPT menu.
 */
function dame_add_user_assignment_page() {
    add_submenu_page(
        'edit.php?post_type=adherent',
        __( 'Assignation des comptes', 'dame' ),
        __( 'Assignation des comptes', 'dame' ),
        'manage_options',
        'dame-user-assignment',
        'dame_render_user_assignment_page'
    );
}
add_action( 'admin_menu', 'dame_add_user_assignment_page' );

/**
 * Renders the user assignment page.
 */
function dame_render_user_assignment_page() {
    // Get all linked user IDs first
    $linked_user_ids = get_posts( array(
        'post_type'      => 'adherent',
        'posts_per_page' => -1,
        'meta_key'       => '_dame_linked_wp_user',
        'fields'         => 'meta_values',
    ) );
    $linked_user_ids = array_map( 'intval', $linked_user_ids );

    // Get members who are not yet linked
    $unlinked_members_args = array(
        'post_type'      => 'adherent',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => '_dame_linked_wp_user',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_dame_linked_wp_user',
                'value'   => '',
                'compare' => '=',
            ),
        ),
        'orderby'        => 'title',
        'order'          => 'ASC',
    );
    $unlinked_members = new WP_Query( $unlinked_members_args );

    // Get WP Users who are not yet linked
    $unlinked_users_args = array(
        'exclude' => $linked_user_ids,
        'fields'  => array( 'ID', 'display_name' ),
    );
    $unlinked_users = get_users( $unlinked_users_args );

    // Get all editable roles
    global $wp_roles;
    $roles = $wp_roles->get_names();

    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p><?php esc_html_e( "Cette page vous permet d'assigner rapidement un compte WordPress et un rôle à un adhérent.", 'dame' ); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field( 'dame_user_assignment_action', 'dame_user_assignment_nonce' ); ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" id="member" class="manage-column"><?php esc_html_e( 'Adhérent', 'dame' ); ?></th>
                        <th scope="col" id="user" class="manage-column"><?php esc_html_e( 'Compte WordPress', 'dame' ); ?></th>
                        <th scope="col" id="role" class="manage-column"><?php esc_html_e( 'Rôle', 'dame' ); ?></th>
                        <th scope="col" id="action" class="manage-column"><?php esc_html_e( 'Action', 'dame' ); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php if ( $unlinked_members->have_posts() ) : ?>
                        <?php while ( $unlinked_members->have_posts() ) : $unlinked_members->the_post(); ?>
                            <tr>
                                <td><strong><?php the_title(); ?></strong></td>
                                <td>
                                    <?php if ( ! empty( $unlinked_users ) ) : ?>
                                        <select name="user_id[<?php echo esc_attr( get_the_ID() ); ?>]">
                                            <option value=""><?php esc_html_e( 'Sélectionner un utilisateur', 'dame' ); ?></option>
                                            <?php foreach ( $unlinked_users as $user ) : ?>
                                                <option value="<?php echo esc_attr( $user->ID ); ?>">
                                                    <?php echo esc_html( $user->display_name ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else : ?>
                                        <?php esc_html_e( 'Aucun utilisateur non assigné disponible', 'dame' ); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <select name="role[<?php echo esc_attr( get_the_ID() ); ?>]">
                                        <?php foreach ( $roles as $role_value => $role_name ) : ?>
                                            <option value="<?php echo esc_attr( $role_value ); ?>">
                                                <?php echo esc_html( $role_name ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" name="assign_user" value="<?php echo esc_attr( get_the_ID() ); ?>" class="button button-primary">
                                        <?php esc_html_e( 'Assigner', 'dame' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e( 'Tous les adhérents ont déjà un compte WordPress assigné.', 'dame' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
    <?php
}
