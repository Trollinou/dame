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
 * Display admin notices for our CPT.
 */
function dame_display_admin_notices() {
    if ( get_transient( 'dame_error_message' ) ) {
        $message = get_transient( 'dame_error_message' );
        delete_transient( 'dame_error_message' );
        echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'dame_display_admin_notices' );

/**
 * Enqueues admin scripts for the plugin.
 *
 * @param string $hook The current admin page.
 */
function dame_enqueue_admin_scripts( $hook ) {
    global $post;
    if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && isset( $post->post_type ) && 'adherent' === $post->post_type ) {
        wp_enqueue_script(
            'dame-autocomplete-js',
            plugin_dir_url( __FILE__ ) . 'js/ign-autocomplete.js',
            array(),
            DAME_VERSION,
            true
        );
    }
}
add_action( 'admin_enqueue_scripts', 'dame_enqueue_admin_scripts' );

/**
 * Adds custom CSS to the admin head for the suggestion box.
 */
function dame_add_admin_styles() {
    $screen = get_current_screen();
    if ( 'adherent' !== $screen->post_type ) {
        return;
    }
    ?>
    <style>
        .dame-autocomplete-wrapper {
            position: relative;
        }
        #dame-address-suggestions {
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            background-color: #fff;
            position: absolute;
            width: 100%;
            z-index: 99;
        }
        .dame-suggestion-item {
            padding: 8px;
            cursor: pointer;
        }
        .dame-suggestion-item:hover {
            background-color: #f1f1f1;
        }
    </style>
    <?php
}
add_action( 'admin_head-post.php', 'dame_add_admin_styles' );
add_action( 'admin_head-post-new.php', 'dame_add_admin_styles' );


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
        'dame_school_info_metabox',
        __( 'Informations Scolaires', 'dame' ),
        'dame_render_school_info_metabox',
        'adherent',
        'normal',
        'default'
    );
    add_meta_box(
        'dame_legal_rep_metabox',
        __( 'Représentants Légaux (si mineur)', 'dame' ),
        'dame_render_legal_rep_metabox',
        'adherent',
        'normal',
        'default'
    );
    add_meta_box(
        'dame_classification_metabox',
        __( 'Classification et Adhésion', 'dame' ),
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
    wp_nonce_field( 'dame_save_adherent_meta', 'dame_metabox_nonce' );

    // Retrieve existing values
    $first_name = get_post_meta( $post->ID, '_dame_first_name', true );
    $last_name = get_post_meta( $post->ID, '_dame_last_name', true );
    $birth_date = get_post_meta( $post->ID, '_dame_birth_date', true );
    $sexe = get_post_meta( $post->ID, '_dame_sexe', true );
    $license_number = get_post_meta( $post->ID, '_dame_license_number', true );
    $phone = get_post_meta( $post->ID, '_dame_phone_number', true );
    $email = get_post_meta( $post->ID, '_dame_email', true );
    $address_1 = get_post_meta( $post->ID, '_dame_address_1', true );
    $address_2 = get_post_meta( $post->ID, '_dame_address_2', true );
    $postal_code = get_post_meta( $post->ID, '_dame_postal_code', true );
    $city = get_post_meta( $post->ID, '_dame_city', true );
    $country = get_post_meta( $post->ID, '_dame_country', true );
    $region = get_post_meta( $post->ID, '_dame_region', true );
    $department = get_post_meta( $post->ID, '_dame_department', true );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="dame_first_name"><?php _e( 'Prénom', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
            <td><input type="text" id="dame_first_name" name="dame_first_name" value="<?php echo esc_attr( $first_name ); ?>" class="regular-text" required="required" /></td>
        </tr>
        <tr>
            <th><label for="dame_last_name"><?php _e( 'Nom', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
            <td><input type="text" id="dame_last_name" name="dame_last_name" value="<?php echo esc_attr( $last_name ); ?>" class="regular-text" required="required" /></td>
        </tr>
        <tr>
            <th><label for="dame_birth_date"><?php _e( 'Date de naissance', 'dame' ); ?> <span class="description">(obligatoire)</span></label></th>
            <td><input type="date" id="dame_birth_date" name="dame_birth_date" value="<?php echo esc_attr( $birth_date ); ?>" required="required" /></td>
        </tr>
        <tr>
            <th><?php _e( 'Sexe', 'dame' ); ?></th>
            <td>
                <label><input type="radio" name="dame_sexe" value="Masculin" <?php checked( $sexe, 'Masculin' ); ?> /> <?php _e( 'Masculin', 'dame' ); ?></label><br>
                <label><input type="radio" name="dame_sexe" value="Féminin" <?php checked( $sexe, 'Féminin' ); ?> /> <?php _e( 'Féminin', 'dame' ); ?></label>
            </td>
        </tr>
        <tr>
            <th><label for="dame_license_number"><?php _e( 'Numéro de licence', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_license_number" name="dame_license_number" value="<?php echo esc_attr( $license_number ); ?>" class="regular-text" placeholder="A12345" pattern="[A-Z][0-9]{5}" /></td>
        </tr>
        <tr>
            <th><label for="dame_phone_number"><?php _e( 'Numéro de téléphone', 'dame' ); ?></label></th>
            <td><input type="tel" id="dame_phone_number" name="dame_phone_number" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_email"><?php _e( 'Email', 'dame' ); ?></label></th>
            <td><input type="email" id="dame_email" name="dame_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_address_1"><?php _e( 'Adresse (Ligne 1)', 'dame' ); ?></label></th>
            <td>
                <div class="dame-autocomplete-wrapper" style="position: relative;">
                    <input type="text" id="dame_address_1" name="dame_address_1" value="<?php echo esc_attr( $address_1 ); ?>" class="regular-text" autocomplete="off" />
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="dame_address_2"><?php _e( 'Adresse (Ligne 2)', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_address_2" name="dame_address_2" value="<?php echo esc_attr( $address_2 ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_postal_code"><?php _e( 'Code Postal', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_postal_code" name="dame_postal_code" value="<?php echo esc_attr( $postal_code ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_city"><?php _e( 'Ville', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_city" name="dame_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_country"><?php _e( 'Pays', 'dame' ); ?></label></th>
            <td>
                <select id="dame_country" name="dame_country">
                    <?php foreach ( dame_get_country_list() as $code => $name ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>><?php echo esc_html( $name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="dame_region"><?php _e( 'Région', 'dame' ); ?></label></th>
            <td>
                <select id="dame_region" name="dame_region">
                    <?php foreach ( dame_get_region_list() as $code => $name ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $region, $code ); ?>><?php echo esc_html( $name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="dame_department"><?php _e( 'Département', 'dame' ); ?></label></th>
            <td>
                <select id="dame_department" name="dame_department">
                    <?php foreach ( dame_get_department_list() as $code => $name ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $department, $code ); ?>><?php echo esc_html( $name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Renders the meta box for school information.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_school_info_metabox( $post ) {
    $school_name = get_post_meta( $post->ID, '_dame_school_name', true );
    $school_academy = get_post_meta( $post->ID, '_dame_school_academy', true );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="dame_school_name"><?php _e( 'Établissement scolaire', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_school_name" name="dame_school_name" value="<?php echo esc_attr( $school_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_school_academy"><?php _e( 'Académie', 'dame' ); ?></label></th>
            <td>
                <select id="dame_school_academy" name="dame_school_academy">
                    <?php foreach ( dame_get_academy_list() as $code => $name ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $school_academy, $code ); ?>><?php echo esc_html( $name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Renders the meta box for legal representative details.
 */
function dame_render_legal_rep_metabox( $post ) {
    // Rep 1
    $rep1_first_name = get_post_meta( $post->ID, '_dame_legal_rep_1_first_name', true );
    $rep1_last_name = get_post_meta( $post->ID, '_dame_legal_rep_1_last_name', true );
    $rep1_email = get_post_meta( $post->ID, '_dame_legal_rep_1_email', true );
    $rep1_phone = get_post_meta( $post->ID, '_dame_legal_rep_1_phone', true );
    $rep1_address_1 = get_post_meta( $post->ID, '_dame_legal_rep_1_address_1', true );
    $rep1_address_2 = get_post_meta( $post->ID, '_dame_legal_rep_1_address_2', true );
    $rep1_postal_code = get_post_meta( $post->ID, '_dame_legal_rep_1_postal_code', true );
    $rep1_city = get_post_meta( $post->ID, '_dame_legal_rep_1_city', true );

    // Rep 2
    $rep2_first_name = get_post_meta( $post->ID, '_dame_legal_rep_2_first_name', true );
    $rep2_last_name = get_post_meta( $post->ID, '_dame_legal_rep_2_last_name', true );
    $rep2_email = get_post_meta( $post->ID, '_dame_legal_rep_2_email', true );
    $rep2_phone = get_post_meta( $post->ID, '_dame_legal_rep_2_phone', true );
    $rep2_address_1 = get_post_meta( $post->ID, '_dame_legal_rep_2_address_1', true );
    $rep2_address_2 = get_post_meta( $post->ID, '_dame_legal_rep_2_address_2', true );
    $rep2_postal_code = get_post_meta( $post->ID, '_dame_legal_rep_2_postal_code', true );
    $rep2_city = get_post_meta( $post->ID, '_dame_legal_rep_2_city', true );
    ?>
    <p><?php _e( 'Remplir ces informations si l\'adhérent est mineur. Au moins un représentant est requis.', 'dame' ); ?></p>

    <h4><?php _e( 'Représentant Légal 1', 'dame' ); ?></h4>
    <table class="form-table">
        <tr>
            <th><label for="dame_legal_rep_1_first_name"><?php _e( 'Prénom', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_1_first_name" name="dame_legal_rep_1_first_name" value="<?php echo esc_attr( $rep1_first_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_1_last_name"><?php _e( 'Nom', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_1_last_name" name="dame_legal_rep_1_last_name" value="<?php echo esc_attr( $rep1_last_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_1_email"><?php _e( 'Email', 'dame' ); ?></label></th>
            <td><input type="email" id="dame_legal_rep_1_email" name="dame_legal_rep_1_email" value="<?php echo esc_attr( $rep1_email ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_1_phone"><?php _e( 'Téléphone', 'dame' ); ?></label></th>
            <td><input type="tel" id="dame_legal_rep_1_phone" name="dame_legal_rep_1_phone" value="<?php echo esc_attr( $rep1_phone ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_1_address_1"><?php _e( 'Adresse (Ligne 1)', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_1_address_1" name="dame_legal_rep_1_address_1" value="<?php echo esc_attr( $rep1_address_1 ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_1_address_2"><?php _e( 'Adresse (Ligne 2)', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_1_address_2" name="dame_legal_rep_1_address_2" value="<?php echo esc_attr( $rep1_address_2 ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_1_postal_code"><?php _e( 'Code Postal', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_1_postal_code" name="dame_legal_rep_1_postal_code" value="<?php echo esc_attr( $rep1_postal_code ); ?>" class="regular-text" /></td>
        </tr>
         <tr>
            <th><label for="dame_legal_rep_1_city"><?php _e( 'Ville', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_1_city" name="dame_legal_rep_1_city" value="<?php echo esc_attr( $rep1_city ); ?>" class="regular-text" /></td>
        </tr>
    </table>

    <hr>

    <h4><?php _e( 'Représentant Légal 2', 'dame' ); ?></h4>
    <table class="form-table">
        <tr>
            <th><label for="dame_legal_rep_2_first_name"><?php _e( 'Prénom', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_2_first_name" name="dame_legal_rep_2_first_name" value="<?php echo esc_attr( $rep2_first_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_2_last_name"><?php _e( 'Nom', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_2_last_name" name="dame_legal_rep_2_last_name" value="<?php echo esc_attr( $rep2_last_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_2_email"><?php _e( 'Email', 'dame' ); ?></label></th>
            <td><input type="email" id="dame_legal_rep_2_email" name="dame_legal_rep_2_email" value="<?php echo esc_attr( $rep2_email ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_2_phone"><?php _e( 'Téléphone', 'dame' ); ?></label></th>
            <td><input type="tel" id="dame_legal_rep_2_phone" name="dame_legal_rep_2_phone" value="<?php echo esc_attr( $rep2_phone ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_2_address_1"><?php _e( 'Adresse (Ligne 1)', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_2_address_1" name="dame_legal_rep_2_address_1" value="<?php echo esc_attr( $rep2_address_1 ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_2_address_2"><?php _e( 'Adresse (Ligne 2)', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_2_address_2" name="dame_legal_rep_2_address_2" value="<?php echo esc_attr( $rep2_address_2 ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="dame_legal_rep_2_postal_code"><?php _e( 'Code Postal', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_2_postal_code" name="dame_legal_rep_2_postal_code" value="<?php echo esc_attr( $rep2_postal_code ); ?>" class="regular-text" /></td>
        </tr>
         <tr>
            <th><label for="dame_legal_rep_2_city"><?php _e( 'Ville', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_legal_rep_2_city" name="dame_legal_rep_2_city" value="<?php echo esc_attr( $rep2_city ); ?>" class="regular-text" /></td>
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
    $membership_date = get_post_meta( $post->ID, '_dame_membership_date', true );
    $is_junior = get_post_meta( $post->ID, '_dame_is_junior', true );
    $is_pole_excellence = get_post_meta( $post->ID, '_dame_is_pole_excellence', true );
    $linked_user = get_post_meta( $post->ID, '_dame_linked_wp_user', true );
    $arbitre_level = get_post_meta( $post->ID, '_dame_arbitre_level', true );
    $arbitre_options = ['Non', 'Jeune', 'Club', 'Open 1', 'Open 2', 'Elite 1', 'Elite 2'];
    $membership_status = get_post_meta( $post->ID, '_dame_membership_status', true );
    $status_options = [
        'N' => __( 'Non Adhérent (N)', 'dame' ),
        'A' => __( 'Actif (A)', 'dame' ),
        'E' => __( 'Expiré (E)', 'dame' ),
        'X' => __( 'Ancien (X)', 'dame' ),
    ];
    ?>
    <p>
        <label for="dame_membership_date"><strong><?php _e( 'Date d\'adhésion', 'dame' ); ?></strong></label><br>
        <input type="date" id="dame_membership_date" name="dame_membership_date" value="<?php echo esc_attr( $membership_date ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="dame_membership_status"><strong><?php _e( 'État de l\'adhésion', 'dame' ); ?></strong></label>
        <select id="dame_membership_status" name="dame_membership_status" style="width:100%;">
            <?php foreach ( $status_options as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $membership_status, $key ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <hr>
    <p>
        <input type="checkbox" id="dame_is_junior" name="dame_is_junior" value="1" <?php checked( $is_junior, '1' ); ?> />
        <label for="dame_is_junior"><?php _e( 'Junior', 'dame' ); ?></label>
    </p>
    <p>
        <input type="checkbox" id="dame_is_pole_excellence" name="dame_is_pole_excellence" value="1" <?php checked( $is_pole_excellence, '1' ); ?> />
        <label for="dame_is_pole_excellence"><?php _e( 'Pôle Excellence', 'dame' ); ?></label>
    </p>
    <hr>
    <p>
        <label for="dame_arbitre_level"><strong><?php _e( 'Niveau d\'arbitre', 'dame' ); ?></strong></label>
        <select id="dame_arbitre_level" name="dame_arbitre_level" style="width:100%;">
            <?php foreach ( $arbitre_options as $option ) : ?>
                <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $arbitre_level, $option ); ?>><?php echo esc_html( $option ); ?></option>
            <?php endforeach; ?>
        </select>
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
    // --- Security checks ---
    if ( ! isset( $_POST['dame_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['dame_metabox_nonce'], 'dame_save_adherent_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // --- Validation ---
    $errors = [];
    if ( empty( $_POST['dame_first_name'] ) ) {
        $errors[] = __( 'Le prénom est obligatoire.', 'dame' );
    }
    if ( empty( $_POST['dame_last_name'] ) ) {
        $errors[] = __( 'Le nom est obligatoire.', 'dame' );
    }
    if ( empty( $_POST['dame_birth_date'] ) ) {
        $errors[] = __( 'La date de naissance est obligatoire.', 'dame' );
    }
    if ( ! empty( $_POST['dame_license_number'] ) && ! preg_match( '/^[A-Z][0-9]{5}$/', $_POST['dame_license_number'] ) ) {
        $errors[] = __( 'Le format du numéro de licence est invalide. Il doit être une lettre majuscule suivie de 5 chiffres (ex: A12345).', 'dame' );
    }

    if ( ! empty( $errors ) ) {
        set_transient( 'dame_error_message', implode( '<br>', $errors ), 10 );
        return;
    }

    // --- Automatic Status Update ---
    if ( ! empty( $_POST['dame_membership_date'] ) ) {
        $_POST['dame_membership_status'] = 'A';
    }

    // --- Title Generation ---
    $first_name = sanitize_text_field( $_POST['dame_first_name'] );
    $last_name = sanitize_text_field( $_POST['dame_last_name'] );
    $new_title = strtoupper( $last_name ) . ' ' . $first_name;

    if ( get_the_title( $post_id ) !== $new_title ) {
        remove_action( 'save_post_adherent', 'dame_save_adherent_meta' );
        wp_update_post( array(
            'ID'         => $post_id,
            'post_title' => $new_title,
            'post_name'  => sanitize_title( $new_title ), // Also update the slug
        ) );
        add_action( 'save_post_adherent', 'dame_save_adherent_meta' );
    }

    // --- Sanitize and Save Data ---
    $fields = [
        'dame_first_name' => 'sanitize_text_field', 'dame_last_name' => 'sanitize_text_field',
        'dame_birth_date' => 'sanitize_text_field', 'dame_license_number' => 'sanitize_text_field',
        'dame_email' => 'sanitize_email', 'dame_address_1' => 'sanitize_text_field',
        'dame_address_2' => 'sanitize_text_field', 'dame_postal_code' => 'sanitize_text_field',
        'dame_city' => 'sanitize_text_field', 'dame_phone_number' => 'sanitize_text_field',
        'dame_membership_date' => 'sanitize_text_field', 'dame_sexe' => 'sanitize_text_field',
        'dame_country' => 'sanitize_text_field', 'dame_region' => 'sanitize_text_field', 'dame_department' => 'sanitize_text_field',
        'dame_school_name' => 'sanitize_text_field', 'dame_school_academy' => 'sanitize_text_field',

        'dame_legal_rep_1_first_name' => 'sanitize_text_field', 'dame_legal_rep_1_last_name' => 'sanitize_text_field',
        'dame_legal_rep_1_email' => 'sanitize_email', 'dame_legal_rep_1_phone' => 'sanitize_text_field',
        'dame_legal_rep_1_address_1' => 'sanitize_text_field', 'dame_legal_rep_1_address_2' => 'sanitize_text_field',
        'dame_legal_rep_1_postal_code' => 'sanitize_text_field', 'dame_legal_rep_1_city' => 'sanitize_text_field',

        'dame_legal_rep_2_first_name' => 'sanitize_text_field', 'dame_legal_rep_2_last_name' => 'sanitize_text_field',
        'dame_legal_rep_2_email' => 'sanitize_email', 'dame_legal_rep_2_phone' => 'sanitize_text_field',
        'dame_legal_rep_2_address_1' => 'sanitize_text_field', 'dame_legal_rep_2_address_2' => 'sanitize_text_field',
        'dame_legal_rep_2_postal_code' => 'sanitize_text_field', 'dame_legal_rep_2_city' => 'sanitize_text_field',

        'dame_is_junior' => 'absint', 'dame_is_pole_excellence' => 'absint',
        'dame_linked_wp_user' => 'absint',
        'dame_arbitre_level' => 'sanitize_text_field',
        'dame_membership_status' => 'sanitize_text_field',
    ];

    foreach ( $fields as $field_name => $sanitize_callback ) {
        if ( isset( $_POST[ $field_name ] ) ) {
            $value = call_user_func( $sanitize_callback, $_POST[ $field_name ] );
            update_post_meta( $post_id, '_' . $field_name, $value );
        } else {
            if ( $sanitize_callback === 'absint' && $field_name !== 'dame_linked_wp_user' ) {
                update_post_meta( $post_id, '_' . $field_name, 0 );
            }
        }
    }
}
add_action( 'save_post_adherent', 'dame_save_adherent_meta' );
