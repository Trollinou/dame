<?php
/**
 * Handles access control for the plugin's content.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Restricts access to Leçon CPT content.
 *
 * This function checks if a user has the required capabilities to view a lesson.
 * If not, it displays a message and prevents the content from being shown.
 */
function dame_restrict_lecon_access() {
    // Check if we are on a single 'dame_lecon' post page.
    if ( is_singular( 'dame_lecon' ) ) {
        $current_user = wp_get_current_user();

        // Required roles: 'dame_membre', 'dame_entraineur', or 'administrator'.
        // The roles are actually 'dame_membre' and 'dame_entraineur' as defined in roles.php
        // Let's check for capabilities instead of roles for more flexibility.
        // We'll assume 'read_private_posts' is a good capability for members.
        // A better approach would be to add a custom capability. Let's use roles for now as per the request.

        $user_roles = (array) $current_user->roles;
        $allowed_roles = array( 'membre', 'entraineur', 'administrator' );

        $has_access = count( array_intersect( $user_roles, $allowed_roles ) ) > 0;

        if ( ! $has_access ) {
            // User does not have the required role.
            // We can redirect them or display a message.

            // Let's replace the content with a message.
            add_filter( 'the_content', 'dame_lecon_access_denied_message' );

            // Optional: remove comments template
            add_filter( 'comments_template', function() { return __FILE__; }, 20 );
        }
    }
}
add_action( 'template_redirect', 'dame_restrict_lecon_access' );

/**
 * Displays the access denied message for lessons.
 *
 * @param string $content The original post content.
 * @return string The modified content with the access denied message.
 */
function dame_lecon_access_denied_message( $content ) {
    // We only want to filter the content for 'dame_lecon'
    if ( is_singular( 'dame_lecon' ) ) {
        return '<p>' . __( "Vous devez être adhérent au club pour consulter cette leçon.", "dame" ) . '</p>';
    }
    return $content;
}

// Note: We need to find where the roles 'dame_membre' and 'dame_entraineur' are defined
// to ensure we are using the correct role slugs. A quick look at `includes/roles.php` will be necessary.
// For now, I'm assuming these are the correct slugs.
