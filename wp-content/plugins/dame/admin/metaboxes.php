<?php
/**
 * File for handling custom meta boxes and fields for the Adherent CPT.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Adds the meta boxes for the Adherent CPT.
 */
function dame_add_meta_boxes() {
    add_meta_box(
        'dame_adherent_details_metabox',
        __( 'Informations sur l\'adhérent', 'dame' ),
        'dame_render_adherent_details_metabox',
        'adherent',
        'normal',
        'high'
    );
    add_meta_box(
        'dame_legal_rep_metabox',
        __( 'Représentant Légal (si mineur)', 'dame' ),
        'dame_render_legal_rep_metabox',
        'adherent',
        'normal',
        'default'
    );
    add_meta_box(
        'dame_classification_metabox',
        __( 'Classification et Liaison', 'dame' ),
        'dame_render_classification_metabox',
        'adherent',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'dame_add_meta_boxes' );

/**
 * Renders the meta box for adherent's personal details.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_adherent_details_metabox( $post ) {
    // Add a nonce field so we can check for it later.
    wp_nonce_field( 'dame_save_adherent_meta', 'dame_metabox_nonce' );

    // Retrieve existing values from the database.
    $first_name = get_post_meta( $post->ID, '_dame_first_name', true );
    $last_name = get_post_meta( $post->ID, '_dame_last_name', true );
    $birth_date = get_post_meta( $post->ID, '_dame_birth_date', true );
    $email = get_post_meta( $post->ID, '_dame_email', true );
    $address_1 = get_post_meta( $post->ID, '_dame_address_1', true );
    $address_2 = get_post_meta( $post->ID, '_dame_address_2', true );
    $postal_code = get_post_meta( $post->ID, '_dame_postal_code', true );
    $city = get_post_meta( $post->ID, '_dame_city', true );
    $phone = get_post_meta( $post->ID, '_dame_phone_number', true );
    $membership_date = get_post_meta( $post->ID, '_dame_membership_date', true );

    // We'll use a table for layout.
    ?>
    <table class="form-table">
        <tr>
            <th><label for="dame_first_name"><?php _e( 'Prénom', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_first_name" name="dame_first_name" value="<?php echo esc_attr( $first_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_last_name"><?php _e( 'Nom', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_last_name" name="dame_last_name" value="<?php echo esc_attr( $last_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_birth_date"><?php _e( 'Date de naissance', 'dame' ); ?></label></th>
            <td><input type="date" id="dame_birth_date" name="dame_birth_date" value="<?php echo esc_attr( $birth_date ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="dame_email"><?php _e( 'Email', 'dame' ); ?></label></th>
            <td><input type="email" id="dame_email" name="dame_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_address_1"><?php _e( 'Adresse (Ligne 1)', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_address_1" name="dame_address_1" value="<?php echo esc_attr( $address_1 ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_address_2"><?php _e( 'Adresse (Ligne 2)', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_address_2" name="dame_address_2" value="<?php echo esc_attr( $address_2 ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_postal_code"><?php _e( 'Code Postal', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_postal_code" name="dame_postal_code" value="<?php echo esc_attr( $postal_code ); ?>" class="small-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_city"><?php _e( 'Ville', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_city" name="dame_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_phone_number"><?php _e( 'Numéro de téléphone', 'dame' ); ?></label></th>
            <td><input type="tel" id="dame_phone_number" name="dame_phone_number" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_membership_date"><?php _e( 'Date d\'adhésion', 'dame' ); ?></label></th>
            <td><input type="date" id="dame_membership_date" name="dame_membership_date" value="<?php echo esc_attr( $membership_date ); ?>" /></td>
        </tr>
    </table>
    <?php
}

/**
 * Renders the meta box for legal representative details.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_legal_rep_metabox( $post ) {
    $rep_first_name = get_post_meta( $post->ID, '_dame_legal_rep_first_name', true );
    $rep_last_name = get_post_meta( $post->ID, '_dame_legal_rep_last_name', true );
    $rep_email = get_post_meta( $post->ID, '_dame_legal_rep_email', true );
    $rep_address = get_post_meta( $post->ID, '_dame_legal_rep_address', true );
    $rep_postal_code = get_post_meta( $post->ID, '_dame_legal_rep_postal_code', true );
    $rep_city = get_post_meta( $post->ID, '_dame_legal_rep_city', true );
    ?>
    <p><?php _e( 'Remplir ces informations si l\'adhérent est mineur.', 'dame' ); ?></p>
    <table class="form-table">
        <tr>
            <th><label for="dame_legal_rep_first_name"><?php _e( 'Prénom', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_first_name" name="dame_legal_rep_first_name" value="<?php echo esc_attr( $rep_first_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_last_name"><?php _e( 'Nom', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_last_name" name="dame_legal_rep_last_name" value="<?php echo esc_attr( $rep_last_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_email"><?php _e( 'Email', 'dame' ); ?></label></th>
            <td><input type="email" id="dame_legal_rep_email" name="dame_legal_rep_email" value="<?php echo esc_attr( $rep_email ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_address"><?php _e( 'Adresse', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_address" name="dame_legal_rep_address" value="<?php echo esc_attr( $rep_address ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_postal_code"><?php _e( 'Code Postal', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_postal_code" name="dame_legal_rep_postal_code" value="<?php echo esc_attr( $rep_postal_code ); ?>" class="small-text" /></td>
        </tr>
         <tr>
            <th><label for="dame_legal_rep_city"><?php _e( 'Ville', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_city" name="dame_legal_rep_city" value="<?php echo esc_attr( $rep_city ); ?>" class="regular-text" /></td>
        </tr>
    </table>
    <?php
}

/**
 * Renders the meta box for classification and user linking.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_classification_metabox( $post ) {
    $is_junior = get_post_meta( $post->ID, '_dame_is_junior', true );
    $is_pole_excellence = get_post_meta( $post->ID, '_dame_is_pole_excellence', true );
    $linked_user = get_post_meta( $post->ID, '_dame_linked_wp_user', true );
    ?>
    <p>
        <input type="checkbox" id="dame_is_junior" name="dame_is_junior" value="1" <?php checked( $is_junior, '1' ); ?> />
        <label for="dame_is_junior"><?php _e( 'Junior', 'dame' ); ?></label>
    </p>
    <p>
        <input type="checkbox" id="dame_is_pole_excellence" name="dame_is_pole_excellence" value="1" <?php checked( $is_pole_excellence, '1' ); ?> />
        <label for="dame_is_pole_excellence"><?php _e( 'Pôle Excellence', 'dame' ); ?></label>
    </p>
    <hr>
    <p><strong><?php _e( 'Lier à un compte WordPress', 'dame' ); ?></strong></p>
    <?php
    wp_dropdown_users( array(
        'name'             => 'dame_linked_wp_user',
        'id'               => 'dame_linked_wp_user',
        'show_option_none' => __( 'Aucun', 'dame' ),
        'selected'         => $linked_user,
    ) );
}


/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */
function dame_save_adherent_meta( $post_id ) {
    // Check if our nonce is set.
    if ( ! isset( $_POST['dame_metabox_nonce'] ) ) {
        return;
    }
    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['dame_metabox_nonce'], 'dame_save_adherent_meta' ) ) {
        return;
    }
    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'adherent' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    // Sanitize and save the data
    $fields = [
        'dame_first_name' => 'sanitize_text_field',
        'dame_last_name' => 'sanitize_text_field',
        'dame_birth_date' => 'sanitize_text_field',
        'dame_email' => 'sanitize_email',
        'dame_address_1' => 'sanitize_text_field',
        'dame_address_2' => 'sanitize_text_field',
        'dame_postal_code' => 'sanitize_text_field',
        'dame_city' => 'sanitize_text_field',
        'dame_phone_number' => 'sanitize_text_field',
        'dame_membership_date' => 'sanitize_text_field',
        'dame_legal_rep_first_name' => 'sanitize_text_field',
        'dame_legal_rep_last_name' => 'sanitize_text_field',
        'dame_legal_rep_email' => 'sanitize_email',
        'dame_legal_rep_address' => 'sanitize_text_field',
        'dame_legal_rep_postal_code' => 'sanitize_text_field',
        'dame_legal_rep_city' => 'sanitize_text_field',
        'dame_is_junior' => 'absint',
        'dame_is_pole_excellence' => 'absint',
        'dame_linked_wp_user' => 'absint',
    ];

    foreach ( $fields as $field_name => $sanitize_callback ) {
        if ( isset( $_POST[ $field_name ] ) ) {
            $value = call_user_func( $sanitize_callback, $_POST[ $field_name ] );
            update_post_meta( $post_id, '_' . $field_name, $value );
        } else {
            // For checkboxes, if they are not in POST, it means they were unchecked.
            if ( $sanitize_callback === 'absint' && $field_name !== 'dame_linked_wp_user' ) {
                update_post_meta( $post_id, '_' . $field_name, 0 );
            }
        }
    }
}
add_action( 'save_post_adherent', 'dame_save_adherent_meta' );
