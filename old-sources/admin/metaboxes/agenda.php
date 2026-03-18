<?php
/**
 * Agenda CPT metaboxes.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Adds the meta boxes for the Agenda CPT.
 */
function dame_add_agenda_meta_boxes() {
    add_meta_box(
        'dame_agenda_description_metabox',
        __( 'Description', 'dame' ),
        'dame_render_agenda_description_metabox',
        'dame_agenda',
        'normal',
        'high'
    );
    add_meta_box(
        'dame_agenda_details_metabox',
        __( 'Détails de l\'événement', 'dame' ),
        'dame_render_agenda_details_metabox',
        'dame_agenda',
        'normal',
        'core'
    );
	add_meta_box(
		'dame_agenda_participants_metabox',
		__( 'Participants', 'dame' ),
		'dame_render_agenda_participants_metabox',
		'dame_agenda',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'dame_add_agenda_meta_boxes' );

/**
 * Renders the meta box for the agenda's description.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_agenda_description_metabox( $post ) {
	// Check for transient data in case of a validation error on save.
	$transient_data = get_transient( 'dame_agenda_post_data_' . $post->ID );

	// Helper function to get value from transient first, then from post meta.
	$get_value = function( $field_name, $default = '' ) use ( $post, $transient_data ) {
		$meta_key = 'dame_' . $field_name;
		return isset( $transient_data[ $meta_key ] )
			? esc_attr( $transient_data[ $meta_key ] )
			: get_post_meta( $post->ID, '_' . $meta_key, true );
	};

	$competition_type    = $get_value( 'competition_type', 'non' );
	$competition_level   = $get_value( 'competition_level', 'departementale' );
	$description         = $get_value( 'agenda_description' );

	?>
	<style>
		.dame-radio-group { display: flex; gap: 1em; margin-bottom: 0.5em; }
		.dame-radio-group label { display: flex; align-items: center; gap: 0.2em; }
		#dame_competition_level_wrapper { margin-left: 1em; }
	</style>
	<table class="form-table">
		<tr>
			<th><label><?php _e( 'Type de compétition', 'dame' ); ?></label></th>
			<td>
				<div class="dame-radio-group">
					<label><input type="radio" name="dame_competition_type" value="non" <?php checked( $competition_type, 'non' ); ?>> <?php _e( 'Non', 'dame' ); ?></label>
					<label><input type="radio" name="dame_competition_type" value="individuelle" <?php checked( $competition_type, 'individuelle' ); ?>> <?php _e( 'Individuelle', 'dame' ); ?></label>
					<label><input type="radio" name="dame_competition_type" value="equipe" <?php checked( $competition_type, 'equipe' ); ?>> <?php _e( 'Par équipe', 'dame' ); ?></label>
				</div>
			</td>
		</tr>
		<tr id="dame_competition_level_wrapper">
			<th><label><?php _e( 'Niveau de compétition', 'dame' ); ?></label></th>
			<td>
				<div class="dame-radio-group">
					<label><input type="radio" name="dame_competition_level" value="departementale" <?php checked( $competition_level, 'departementale' ); ?>> <?php _e( 'Départementale', 'dame' ); ?></label>
					<label><input type="radio" name="dame_competition_level" value="regionale" <?php checked( $competition_level, 'regionale' ); ?>> <?php _e( 'Régionale', 'dame' ); ?></label>
					<label><input type="radio" name="dame_competition_level" value="nationale" <?php checked( $competition_level, 'nationale' ); ?>> <?php _e( 'Nationale', 'dame' ); ?></label>
				</div>
			</td>
		</tr>
	</table>
	<?php

	wp_editor(
		$description,
		'dame_agenda_description',
		array(
			'textarea_name' => 'dame_agenda_description',
			'teeny'         => false,
			'media_buttons' => false,
			'textarea_rows' => 5,
			'quicktags'     => false,
			'tinymce'       => array(
				'toolbar1' => 'undo redo | cut copy pastetext | bold italic underline strikethrough | bullist numlist | alignleft aligncenter alignright | forecolor formatselect | removeformat',
				'toolbar2' => '',
				'toolbar3' => '',
			),
		)
	);
	?>
	<script>
	jQuery(document).ready(function($) {
		function toggleCompetitionLevel() {
			var competitionType = $('input[name="dame_competition_type"]:checked').val();
			if (competitionType === 'non') {
				$('#dame_competition_level_wrapper').hide();
			} else {
				$('#dame_competition_level_wrapper').show();
			}
		}
		// Run on page load
		toggleCompetitionLevel();
		// Run on change
		$('input[name="dame_competition_type"]').on('change', function() {
			toggleCompetitionLevel();
		});
	});
	</script>
	<?php
}

/**
 * Renders the meta box for agenda details.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_agenda_details_metabox( $post ) {
    wp_nonce_field( 'dame_save_agenda_meta', 'dame_agenda_metabox_nonce' );

	// Check for transient data in case of a validation error on save.
	$transient_data = get_transient( 'dame_agenda_post_data_' . $post->ID );
	if ( $transient_data ) {
		// Clean up the transient so it's only used once.
		delete_transient( 'dame_agenda_post_data_' . $post->ID );
	}

	// Helper function to get value from transient first, then from post meta.
	$get_value = function( $field_name, $default = '' ) use ( $post, $transient_data ) {
		// For fields like 'dame_start_date', the key in $_POST is 'dame_start_date'.
		// In post meta, it's '_dame_start_date'. The transient stores it without the underscore.
		return isset( $transient_data[ $field_name ] )
			? esc_attr( $transient_data[ $field_name ] )
			: get_post_meta( $post->ID, '_' . $field_name, true );
	};

	$start_date    = $get_value( 'dame_start_date' );
	$start_time    = $get_value( 'dame_start_time' );
	$end_date      = $get_value( 'dame_end_date' );
	$end_time      = $get_value( 'dame_end_time' );
	$all_day       = $get_value( 'dame_all_day' );
	$location_name = $get_value( 'dame_location_name' );
	$address_1     = $get_value( 'dame_address_1' );
	$address_2     = $get_value( 'dame_address_2' );
	$postal_code   = $get_value( 'dame_postal_code' );
	$city          = $get_value( 'dame_city' );
    ?>
    <table class="form-table">
        <!-- Date and Time Fields -->
        <tr>
            <th><label for="dame_all_day"><?php _e( 'Journée entière', 'dame' ); ?></label></th>
            <td>
                <input type="checkbox" id="dame_all_day" name="dame_all_day" value="1" <?php checked( $all_day, '1' ); ?> />
            </td>
        </tr>
        <tr>
            <th><label for="dame_start_date"><?php _e( 'Date de début', 'dame' ); ?></label></th>
            <td>
                <input type="date" id="dame_start_date" name="dame_start_date" value="<?php echo esc_attr( $start_date ); ?>" />
                <span class="dame-time-fields <?php if ( $all_day ) echo 'hidden'; ?>">
                    <label for="dame_start_time" class="screen-reader-text"><?php _e( 'Heure de début', 'dame' ); ?></label>
                    <select id="dame_start_time" name="dame_start_time">
                        <?php
                        for ( $h = 0; $h < 24; $h++ ) {
                            for ( $m = 0; $m < 60; $m += 15 ) {
                                $time_val = sprintf( '%02d:%02d', $h, $m );
                                echo '<option value="' . esc_attr( $time_val ) . '" ' . selected( $start_time, $time_val, false ) . '>' . esc_html( $time_val ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </span>
            </td>
        </tr>
        <tr>
            <th><label for="dame_end_date"><?php _e( 'Date de fin', 'dame' ); ?></label></th>
            <td>
                <input type="date" id="dame_end_date" name="dame_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
                 <span class="dame-time-fields <?php if ( $all_day ) echo 'hidden'; ?>">
                    <label for="dame_end_time" class="screen-reader-text"><?php _e( 'Heure de fin', 'dame' ); ?></label>
                    <select id="dame_end_time" name="dame_end_time">
                        <?php
                        for ( $h = 0; $h < 24; $h++ ) {
                            for ( $m = 0; $m < 60; $m += 15 ) {
                                $time_val = sprintf( '%02d:%02d', $h, $m );
                                echo '<option value="' . esc_attr( $time_val ) . '" ' . selected( $end_time, $time_val, false ) . '>' . esc_html( $time_val ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </span>
            </td>
        </tr>

        <!-- Location Fields -->
        <tr>
            <th colspan="2"><h4><?php _e( 'Lieu', 'dame' ); ?></h4></th>
        </tr>
        <tr>
            <th><label for="dame_location_name"><?php _e( 'Intitulé du lieu', 'dame' ); ?></label></th>
            <td><input type="text" id="dame_location_name" name="dame_location_name" value="<?php echo esc_attr( $location_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
			<th><label for="dame_address_1"><?php _e( 'Adresse', 'dame' ); ?></label></th>
			<td>
				<div class="dame-autocomplete-wrapper" style="position: relative;">
					<input type="text" id="dame_address_1" name="dame_address_1" value="<?php echo esc_attr( $address_1 ); ?>" class="regular-text" autocomplete="off" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_address_2"><?php _e( 'Complément', 'dame' ); ?></label></th>
			<td><input type="text" id="dame_address_2" name="dame_address_2" value="<?php echo esc_attr( $address_2 ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="dame_postal_code"><?php _e( 'Code Postal / Ville', 'dame' ); ?></label></th>
			<td>
				<div class="dame-inline-fields">
					<input type="text" id="dame_postal_code" name="dame_postal_code" value="<?php echo esc_attr( $postal_code ); ?>" class="postal-code" placeholder="<?php _e( 'Code Postal', 'dame' ); ?>" />
					<input type="text" id="dame_city" name="dame_city" value="<?php echo esc_attr( $city ); ?>" class="city" placeholder="<?php _e( 'Ville', 'dame' ); ?>" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_latitude"><?php _e( 'Latitude / Longitude', 'dame' ); ?></label></th>
			<td>
				<div class="dame-inline-fields">
					<input type="text" id="dame_latitude" name="dame_latitude" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_latitude', true ) ); ?>" readonly="readonly" placeholder="<?php _e( 'Latitude', 'dame' ); ?>" />
					<input type="text" id="dame_longitude" name="dame_longitude" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_longitude', true ) ); ?>" readonly="readonly" placeholder="<?php _e( 'Longitude', 'dame' ); ?>" />
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="dame_distance"><?php _e( 'Distance / Temps de trajet', 'dame' ); ?></label></th>
			<td>
				<div class="dame-inline-fields">
					<input type="text" id="dame_distance" name="dame_distance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_distance', true ) ); ?>" readonly="readonly" placeholder="<?php _e( 'Distance (km)', 'dame' ); ?>" />
					<input type="text" id="dame_travel_time" name="dame_travel_time" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_travel_time', true ) ); ?>" readonly="readonly" placeholder="<?php _e( 'Temps de trajet', 'dame' ); ?>" />
					<button type="button" id="dame_calculate_route_button" class="button"><?php _e( 'Calculer', 'dame' ); ?></button>
				</div>
			</td>
		</tr>
	</table>
    <script>
    jQuery(document).ready(function($) {
        function toggleTimeFields() {
            if ($('#dame_all_day').is(':checked')) {
                $('.dame-time-fields').hide();
            } else {
                $('.dame-time-fields').show();
            }
        }
        toggleTimeFields(); // Initial check
        $('#dame_all_day').on('change', toggleTimeFields);
    });
    </script>
    <style>
        .dame-time-fields.hidden { display: none; }
    </style>
    <?php
}


/**
 * Renders the meta box for selecting event participants.
 *
 * @param WP_Post $post The post object.
 */
function dame_render_agenda_participants_metabox( $post ) {
	// 1. Get the ID of the current season's term.
	$current_season_tag_id = get_option( 'dame_current_season_tag_id' );

	if ( ! $current_season_tag_id ) {
		echo '<p>' . esc_html__( 'La saison actuelle n\'est pas configurée. Impossible de lister les adhérents.', 'dame' ) . '</p>';
		return;
	}

	// 2. Query for adherents who have the current season's term.
	$args = array(
		'post_type'      => 'adherent',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'tax_query'      => array(
			array(
				'taxonomy' => 'dame_saison_adhesion',
				'field'    => 'term_id',
				'terms'    => $current_season_tag_id,
			),
		),
	);

	$adherents = get_posts( $args );
	$selected_participants = get_post_meta( $post->ID, '_dame_event_participants', true );
	if ( ! is_array( $selected_participants ) ) {
		$selected_participants = array();
	}

	if ( empty( $adherents ) ) {
		echo '<p>' . esc_html__( 'Aucun adhérent actif pour la saison en cours.', 'dame' ) . '</p>';
		return;
	}

	// 3. Sort adherents to show selected ones first.
	$selected_list = array();
	$unselected_list = array();
	foreach ( $adherents as $adherent ) {
		if ( in_array( $adherent->ID, $selected_participants, true ) ) {
			$selected_list[] = $adherent;
		} else {
			$unselected_list[] = $adherent;
		}
	}
	$sorted_adherents = array_merge( $selected_list, $unselected_list );

	// 4. Display a checklist with a filter field.
	?>
	<input type="text" id="dame_participant_filter" placeholder="<?php esc_attr_e( 'Filtrer par nom...', 'dame' ); ?>" style="width: 100%; margin-bottom: 5px;">
	<div class="dame-participants-checklist" style="max-height: 250px; overflow-y: auto;">
		<ul id="dame_participants_list">
			<?php
			foreach ( $sorted_adherents as $adherent ) {
				$checked = in_array( $adherent->ID, $selected_participants, true ) ? 'checked="checked"' : '';
				echo '<li>';
				echo '<label>';
				echo '<input type="checkbox" name="dame_event_participants[]" value="' . esc_attr( $adherent->ID ) . '" ' . $checked . '> ';
				echo esc_html( $adherent->post_title );
				echo '</label>';
				echo '</li>';
			}
			?>
		</ul>
	</div>
	<p class="description"><?php esc_html_e( 'Seuls les adhérents avec une adhésion active pour la saison en cours sont listés.', 'dame' ); ?></p>
	<script>
	jQuery(document).ready(function($) {
		$('#dame_participant_filter').on('keyup', function() {
			var value = $(this).val().toLowerCase();
			$('#dame_participants_list li').filter(function() {
				$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
			});
		});
	});
	</script>
	<?php
}

/**
 * Save meta box content for Agenda CPT.
 *
 * @param int $post_id Post ID
 */
function dame_save_agenda_meta( $post_id ) {
    // --- Security checks ---
    if ( ! isset( $_POST['dame_agenda_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['dame_agenda_metabox_nonce'], 'dame_save_agenda_meta' ) ) {
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
    if ( empty( $_POST['dame_start_date'] ) ) {
        $errors[] = __( 'La date de début est obligatoire.', 'dame' );
    }
    if ( empty( $_POST['dame_end_date'] ) ) {
        $errors[] = __( 'La date de fin est obligatoire.', 'dame' );
    }
    if ( empty( $_POST['tax_input']['dame_agenda_category'] ) || ( is_array( $_POST['tax_input']['dame_agenda_category'] ) && count( array_filter( $_POST['tax_input']['dame_agenda_category'] ) ) === 0 ) ) {
        $errors[] = __( 'La catégorie est obligatoire.', 'dame' );
    }

    if ( ! empty( $errors ) ) {
        set_transient( 'dame_error_message', implode( '<br>', $errors ), 10 );

        // Store submitted data in a transient to repopulate the form
        $post_data_to_save = array();
        foreach ( $_POST as $key => $value ) {
            if ( strpos( $key, 'dame_' ) === 0 || $key === 'tax_input' ) {
                $post_data_to_save[ $key ] = wp_unslash( $value );
            }
        }
        set_transient( 'dame_agenda_post_data_' . $post_id, $post_data_to_save, 60 );


        // Unhook this function to prevent infinite loops
        remove_action( 'save_post_dame_agenda', 'dame_save_agenda_meta' );

        // Update the post to be a draft
        wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );

        // Re-hook the function
        add_action( 'save_post_dame_agenda', 'dame_save_agenda_meta' );
        return;
    }

    // If we are here, it means there are no errors, so we can delete any transient data
    delete_transient( 'dame_agenda_post_data_' . $post_id );

    // --- Sanitize and Save Data ---
    $fields = [
        'dame_start_date'    => 'sanitize_text_field',
        'dame_start_time'    => 'sanitize_text_field',
        'dame_end_date'      => 'sanitize_text_field',
        'dame_end_time'      => 'sanitize_text_field',
        'dame_all_day'       => 'absint',
        'dame_competition_type' => 'sanitize_key',
        'dame_competition_level' => 'sanitize_key',
        'dame_location_name' => 'sanitize_text_field',
        'dame_address_1'     => 'sanitize_text_field',
        'dame_address_2'     => 'sanitize_text_field',
        'dame_postal_code'   => 'sanitize_text_field',
        'dame_city'          => 'sanitize_text_field',
        'dame_latitude'      => 'sanitize_text_field',
        'dame_longitude'     => 'sanitize_text_field',
        'dame_distance'      => 'sanitize_text_field',
        'dame_travel_time'   => 'sanitize_text_field',
        'dame_agenda_description' => 'wp_kses_post',
    ];

    foreach ( $fields as $field_name => $sanitize_callback ) {
        if ( isset( $_POST[ $field_name ] ) ) {
            $value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) );
            update_post_meta( $post_id, '_' . $field_name, $value );
        } else {
            if ( 'absint' === $sanitize_callback ) {
                update_post_meta( $post_id, '_' . $field_name, 0 );
            }
        }
    }

	// --- Save Participants ---
	if ( isset( $_POST['dame_event_participants'] ) ) {
		$participant_ids = array_map( 'intval', $_POST['dame_event_participants'] );
		update_post_meta( $post_id, '_dame_event_participants', $participant_ids );
	} else {
		// If no participants are selected, save an empty array.
		update_post_meta( $post_id, '_dame_event_participants', array() );
	}
}
add_action( 'save_post_dame_agenda', 'dame_save_agenda_meta' );
