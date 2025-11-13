<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add meta box for sondage configuration.
 */
function dame_add_sondage_metaboxes() {
    add_meta_box(
        'dame_sondage_config',
        __( 'Configuration du sondage', 'dame' ),
        'dame_render_sondage_config_metabox',
        'sondage',
        'normal',
        'high'
    );
    add_meta_box(
        'dame_sondage_results',
        __( 'Résultats du sondage', 'dame' ),
        'dame_render_sondage_results_metabox',
        'sondage',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'dame_add_sondage_metaboxes' );

/**
 * Render the meta box for sondage configuration.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_sondage_config_metabox( $post ) {
    wp_nonce_field( 'dame_save_sondage_metabox_data', 'dame_sondage_metabox_nonce' );

    $sondage_data = get_post_meta( $post->ID, '_dame_sondage_data', true );
    if ( empty( $sondage_data ) ) {
        $sondage_data = array();
    }
    ?>
    <div id="sondage-config-container">
        <p><?php _e( 'Ajoutez les dates et les plages horaires pour ce sondage.', 'dame' ); ?></p>
        <div id="sondage-dates-wrapper">
            <?php
            if ( ! empty( $sondage_data ) ) {
                foreach ( $sondage_data as $date_key => $date_info ) {
                    ?>
                    <div class="sondage-date-group">
                        <hr>
                        <h4><?php echo esc_html( sprintf( __( 'Date %d', 'dame' ), $date_key + 1 ) ); ?></h4>
                        <p>
                            <label for="sondage_date_<?php echo esc_attr( $date_key ); ?>"><?php _e( 'Date:', 'dame' ); ?></label>
                            <input type="date" id="sondage_date_<?php echo esc_attr( $date_key ); ?>" name="_dame_sondage_data[<?php echo esc_attr( $date_key ); ?>][date]" value="<?php echo esc_attr( $date_info['date'] ); ?>" class="sondage-date-input">
                            <button type="button" class="button remove-sondage-date"><?php _e( 'Supprimer cette date', 'dame' ); ?></button>
                        </p>
                        <div class="sondage-time-slots-wrapper">
                            <?php
                            if ( ! empty( $date_info['time_slots'] ) ) {
                                foreach ( $date_info['time_slots'] as $time_key => $time_slot ) {
                                    ?>
                                    <div class="sondage-time-slot-group">
                                        <label><?php _e( 'Plage horaire:', 'dame' ); ?></label>
                                        <input type="time" name="_dame_sondage_data[<?php echo esc_attr( $date_key ); ?>][time_slots][<?php echo esc_attr( $time_key ); ?>][start]" value="<?php echo esc_attr( $time_slot['start'] ); ?>" step="900">
                                        <span>-</span>
                                        <input type="time" name="_dame_sondage_data[<?php echo esc_attr( $date_key ); ?>][time_slots][<?php echo esc_attr( $time_key ); ?>][end]" value="<?php echo esc_attr( $time_slot['end'] ); ?>" step="900">
                                        <button type="button" class="button remove-sondage-time-slot"><?php _e( 'Supprimer', 'dame' ); ?></button>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <button type="button" class="button add-sondage-time-slot"><?php _e( 'Ajouter une plage horaire', 'dame' ); ?></button>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <p>
            <button type="button" id="add-sondage-date" class="button button-primary"><?php _e( 'Ajouter une date', 'dame' ); ?></button>
        </p>
    </div>
    <?php
}

/**
 * Save the meta box data for sondage.
 *
 * @param int $post_id The ID of the post being saved.
 */
function dame_save_sondage_metabox_data( $post_id ) {
    if ( ! isset( $_POST['dame_sondage_metabox_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['dame_sondage_metabox_nonce'], 'dame_save_sondage_metabox_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( ! isset( $_POST['_dame_sondage_data'] ) ) {
		// If no data is submitted, delete existing meta to handle case where all dates are removed.
		delete_post_meta( $post_id, '_dame_sondage_data' );
        return;
    }

    $sondage_data = array();
    foreach ( $_POST['_dame_sondage_data'] as $date_group ) {
        if ( ! empty( $date_group['date'] ) ) {
            $new_date_group = array(
                'date'       => sanitize_text_field( $date_group['date'] ),
                'time_slots' => array(),
            );
            if ( ! empty( $date_group['time_slots'] ) ) {
                foreach ( $date_group['time_slots'] as $time_slot ) {
                    if ( ! empty( $time_slot['start'] ) && ! empty( $time_slot['end'] ) ) {
                        $new_date_group['time_slots'][] = array(
                            'start' => sanitize_text_field( $time_slot['start'] ),
                            'end'   => sanitize_text_field( $time_slot['end'] ),
                        );
                    }
                }
            }
            $sondage_data[] = $new_date_group;
        }
    }

    if ( ! empty( $sondage_data ) ) {
        update_post_meta( $post_id, '_dame_sondage_data', $sondage_data );
    } else {
		// Also delete if after sanitization, the array is empty.
		delete_post_meta( $post_id, '_dame_sondage_data' );
	}
}
add_action( 'save_post_sondage', 'dame_save_sondage_metabox_data' );

/**
 * Enqueue admin scripts for sondage edit screen.
 *
 * @param string $hook The current admin page.
 */
function dame_enqueue_sondage_admin_scripts( $hook ) {
    global $post;

    if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && 'sondage' === $post->post_type ) {
        wp_enqueue_script(
            'dame-sondage-admin-js',
            plugin_dir_url( __FILE__ ) . '../js/sondage-admin.js',
            array( 'jquery' ),
            DAME_VERSION,
            true
        );
    }
}
add_action( 'admin_enqueue_scripts', 'dame_enqueue_sondage_admin_scripts' );

/**
 * Render the meta box for sondage results.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_sondage_results_metabox( $post ) {
    $sondage_data = get_post_meta( $post->ID, '_dame_sondage_data', true );
    if ( empty( $sondage_data ) ) {
        echo '<p>' . __( 'Le sondage n\'a pas encore été configuré.', 'dame' ) . '</p>';
        return;
    }

    $responses = get_posts( array(
        'post_type'   => 'sondage_reponse',
        'post_status' => 'publish',
        'post_parent' => $post->ID,
        'posts_per_page' => -1,
        'orderby'     => 'post_title',
        'order'       => 'ASC',
    ) );

    if ( empty( $responses ) ) {
        echo '<p>' . __( 'Aucune réponse pour le moment.', 'dame' ) . '</p>';
        return;
    }

    // Prepare data for the results table
    $results = array();
    $time_slots_by_date = array();

    foreach ( $sondage_data as $date_index => $date_info ) {
        $date_str = $date_info['date'];
        if ( ! empty( $date_info['time_slots'] ) ) {
            foreach ( $date_info['time_slots'] as $time_index => $time_slot ) {
                $slot_key = $date_index . '_' . $time_index;
                $results[ $slot_key ] = array(
                    'date'  => $date_str,
                    'start' => $time_slot['start'],
                    'end'   => $time_slot['end'],
                    'names' => array(),
                );
            }
        }
    }

    foreach ( $responses as $response ) {
        $response_data = get_post_meta( $response->ID, '_dame_sondage_responses', true );
        if ( ! empty( $response_data ) ) {
            foreach ( $response_data as $date_index => $time_slots ) {
                foreach ( $time_slots as $time_index => $value ) {
                    if ( $value == '1' ) {
                        $slot_key = $date_index . '_' . $time_index;
                        if ( isset( $results[ $slot_key ] ) ) {
                            $results[ $slot_key ]['names'][] = $response->post_title;
                        }
                    }
                }
            }
        }
    }

    // Group time slots by date for display
    $grouped_results = array();
    foreach($results as $slot_key => $details) {
        $date_obj = new DateTime( $details['date'] );
        $formatted_date = date_i18n( 'l j F Y', $date_obj->getTimestamp() );
        $grouped_results[$formatted_date][$details['start'] . ' - ' . $details['end']] = $details['names'];
    }

    ?>
    <style>
        .sondage-results-table { width: 100%; border-collapse: collapse; }
        .sondage-results-table th, .sondage-results-table td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
        .sondage-results-table th { background-color: #f2f2f2; }
        .sondage-results-table ul { margin: 0; padding-left: 15px; }
    </style>
    <div class="sondage-results-wrapper">
        <h4><?php _e( 'Tableau récapitulatif', 'dame' ); ?></h4>
        <table class="sondage-results-table">
            <thead>
                <tr>
                    <th><?php _e( 'Plage Horaire', 'dame' ); ?></th>
                    <?php foreach ( array_keys($grouped_results) as $date_header ) : ?>
                        <th><?php echo esc_html( $date_header ); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                // Get all unique time slots across all dates
                $all_time_slots = array();
                foreach($grouped_results as $date => $slots) {
                    foreach($slots as $slot_time => $names) {
                        $all_time_slots[$slot_time] = true;
                    }
                }
                ksort($all_time_slots);

                foreach ( array_keys($all_time_slots) as $slot_time ) : ?>
                    <tr>
                        <td><?php echo esc_html( $slot_time ); ?></td>
                        <?php foreach ( $grouped_results as $date => $slots ) : ?>
                            <td>
                                <?php if ( isset( $slots[ $slot_time ] ) && ! empty( $slots[ $slot_time ] ) ) : ?>
                                    <ul>
                                        <?php foreach ( $slots[ $slot_time ] as $name ) : ?>
                                            <li><?php echo esc_html( $name ); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <hr style="margin: 20px 0;">

        <h4><?php _e( 'Liste des participants', 'dame' ); ?></h4>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php _e( 'Nom', 'dame' ); ?></th>
                    <th><?php _e( 'Action', 'dame' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $responses as $response ) : ?>
                    <tr>
                        <td><?php echo esc_html( $response->post_title ); ?></td>
                        <td>
                            <a href="<?php echo get_delete_post_link( $response->ID, '', true ); ?>" class="button button-small button-danger" onclick="return confirm('<?php _e( 'Êtes-vous sûr de vouloir supprimer cette réponse ?', 'dame' ); ?>');">
                                <?php _e( 'Supprimer', 'dame' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}