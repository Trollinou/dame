<?php
/**
 * Agenda Metabox Manager.
 *
 * @package DAME\Metaboxes\Agenda
 */

namespace DAME\Metaboxes\Agenda;

use WP_Post;
use DateTimeImmutable;
use DAME\Services\Data_Provider;
use DAME\Services\Agenda\Recurrence_Calculator;
use DAME\Services\Agenda\Batch_Creator;
use DAME\Services\Agenda\Series_Manager;

/**
 * Class Manager
 * Manages metaboxes for the Agenda CPT, including critical script enqueues for geolocation.
 */
class Manager {

	/**
	 * Initialize the metaboxes and scripts.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_dame_agenda', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'edit_form_top', array( $this, 'render_back_button' ) );
		add_action( 'admin_post_dame_delete_series_from', array( $this, 'handle_delete_series_from' ) );
		add_action( 'admin_post_dame_delete_entire_series', array( $this, 'handle_delete_entire_series' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/**
	 * Enqueue necessary scripts for autocomplete and geolocation.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ): void {
		$screen = get_current_screen();

		if ( ! $screen || 'dame_agenda' !== $screen->post_type ) {
			return;
		}

		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		// Enqueue the common admin script which handles address autocomplete and geolocation.
		// We explicitly register and localize it here to ensure it's available for the Agenda CPT
		// with the necessary data (latitude/longitude options), as Assets.php might not cover this CPT.

		// Define the URL to the assets directory relative to the plugin root.
		// Since we are in includes/Metaboxes/Agenda/Manager.php, dirname(__DIR__, 3) gets us to the plugin root.
		$plugin_url = plugin_dir_url( dirname( __DIR__, 3 ) . '/index.php' );

		// Register if not already registered (though wp_register_script handles duplicate calls gracefully).
		wp_register_script(
			'dame-admin-common',
			$plugin_url . 'assets/js/admin-common.js',
			array(), // Dependencies if any
			\DAME_VERSION,
			true
		);

		// Localize script with necessary data for distance calculation.
		$options         = get_option( 'dame_options', array() );
		$assoc_latitude  = isset( $options['assoc_latitude'] ) ? $options['assoc_latitude'] : '';
		$assoc_longitude = isset( $options['assoc_longitude'] ) ? $options['assoc_longitude'] : '';

		wp_localize_script(
			'dame-admin-common',
			'dame_admin_data',
			array(
				'assoc_latitude'  => $assoc_latitude,
				'assoc_longitude' => $assoc_longitude,
				// Include dept map if needed by other parts of the script, though lat/long is critical here.
				'dept_region_map' => Data_Provider::get_department_region_mapping(),
			)
		);

		wp_enqueue_script( 'dame-admin-common' );

		// Enqueue CSS for autocomplete styles if needed (using existing file as common CSS).
		wp_enqueue_style(
			'dame-admin-common-css',
			$plugin_url . 'assets/css/admin-common.css',
			array(),
			\DAME_VERSION
		);

		// Specific Agenda Manager Script
		wp_enqueue_script( 'dame-admin-agenda-manager', \DAME_PLUGIN_URL . 'assets/js/admin-agenda-manager.js', array( 'jquery' ), \DAME_VERSION, true );
		wp_localize_script(
			'dame-admin-agenda-manager',
			'dame_agenda_manager_data',
			array(
				'alert_category'         => __( 'Veuillez sélectionner au moins une catégorie.', 'dame' ),
				'alert_competition_type' => __( 'Veuillez sélectionner un type de compétition.', 'dame' ),
			)
		);
	}

	/**
	 * Renders the back link above the form fields.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_back_button( WP_Post $post ): void {
		if ( 'dame_agenda' !== $post->post_type ) {
			return;
		}

		$user_id  = get_current_user_id();
		$list_url = $user_id ? (string) get_user_meta( $user_id, 'dame_last_agenda_list_url', true ) : '';
		if ( empty( $list_url ) ) {
			$list_url = admin_url( 'edit.php?post_type=dame_agenda' );
		} else {
			$list_url = admin_url( ltrim( str_replace( '/wp-admin/', '', $list_url ), '/' ) );
		}
		?>
		<style>
			.dame-back-link {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				text-decoration: none;
				color: #1e293b;
				background-color: #f8fafc;
				border: 1px solid #cbd5e1;
				border-radius: 6px;
				padding: 8px 16px;
				font-weight: 500;
				font-size: 13px;
				transition: all 0.15s ease-in-out;
				box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
			}
			.dame-back-link:hover {
				background-color: #f1f5f9;
				border-color: #94a3b8;
				color: #0f172a;
			}
			.dame-back-link:hover .dashicons {
				transform: translateX(-3px);
				color: #0f172a;
			}
			.dame-back-link .dashicons {
				font-size: 18px;
				width: 18px;
				height: 18px;
				line-height: 18px;
				margin: 0;
				color: #64748b;
				transition: transform 0.15s ease-in-out;
			}
		</style>
		<div style="margin: 15px 0;">
			<a href="<?php echo esc_url( $list_url ); ?>" class="dame-back-link">
				<span class="dashicons dashicons-arrow-left-alt"></span>
				<span style="line-height: 1;"><?php esc_html_e( 'Retour à la liste filtrée', 'dame' ); ?></span>
			</a>
		</div>
		<?php
	}

	/**
	 * Register the metaboxes.
	 */
	public function register_meta_boxes(): void {
		remove_meta_box( 'postcustom', 'dame_agenda', 'normal' );

		add_meta_box(
			'dame_agenda_description_metabox',
			__( 'Description', 'dame' ),
			array( $this, 'render_description' ),
			'dame_agenda',
			'normal',
			'high'
		);
		add_meta_box(
			'dame_agenda_details_metabox',
			__( 'Détails de l\'événement', 'dame' ),
			array( $this, 'render_details' ),
			'dame_agenda',
			'normal',
			'core'
		);
		add_meta_box(
			'dame_agenda_recurrence_metabox',
			__( 'Récurrence & Répétition', 'dame' ),
			array( $this, 'render_recurrence' ),
			'dame_agenda',
			'normal',
			'default'
		);
		add_meta_box(
			'dame_agenda_participants_metabox',
			__( 'Participants', 'dame' ),
			array( $this, 'render_participants' ),
			'dame_agenda',
			'side',
			'high'
		);
	}

	/**
	 * Renders the recurrence meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_recurrence( $post ): void {
		$recurrence_metabox = new Recurrence_Metabox();
		$recurrence_metabox->render( $post );
	}

	/**
	 * Renders the meta box for the agenda's description.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_description( $post ): void {
		// Check for transient data in case of a validation error on save.
		$transient_data = get_transient( 'dame_agenda_post_data_' . $post->ID );

		// Helper function to get value from transient first, then from post meta.
		$get_value = function ( $field_name, $default = '' ) use ( $post, $transient_data ) {
			$meta_key = 'dame_' . $field_name;
			return isset( $transient_data[ $meta_key ] )
				? esc_attr( $transient_data[ $meta_key ] )
				: ( (string) get_post_meta( $post->ID, '_' . $meta_key, true ) ?: $default );
		};

		$competition_type  = $get_value( 'competition_type', '' );
		$competition_level = $get_value( 'competition_level', 'departementale' );
		$description       = $get_value( 'agenda_description' );
		?>
		<style>
			.dame-radio-group { display: flex; gap: 1em; margin-bottom: 0.5em; }
			.dame-radio-group label { display: flex; align-items: center; gap: 0.2em; }
			#dame_competition_level_wrapper { margin-left: 1em; }
		</style>
		<table class="form-table">
			<tr>
				<th><label><?php esc_html_e( 'Type de compétition', 'dame' ); ?> <span class="description" style="color: #d63638;">*</span></label></th>
				<td>
					<div class="dame-radio-group">
						<label><input type="radio" name="dame_competition_type" value="non" <?php checked( $competition_type, 'non' ); ?> required> <?php esc_html_e( 'Non', 'dame' ); ?></label>
						<label><input type="radio" name="dame_competition_type" value="individuelle" <?php checked( $competition_type, 'individuelle' ); ?>> <?php esc_html_e( 'Individuelle', 'dame' ); ?></label>
						<label><input type="radio" name="dame_competition_type" value="equipe" <?php checked( $competition_type, 'equipe' ); ?>> <?php esc_html_e( 'Par équipe', 'dame' ); ?></label>
					</div>
				</td>
			</tr>
			<tr id="dame_competition_level_wrapper">
				<th><label><?php esc_html_e( 'Niveau de compétition', 'dame' ); ?></label></th>
				<td>
					<div class="dame-radio-group">
						<label><input type="radio" name="dame_competition_level" value="departementale" <?php checked( $competition_level, 'departementale' ); ?>> <?php esc_html_e( 'Départementale', 'dame' ); ?></label>
						<label><input type="radio" name="dame_competition_level" value="regionale" <?php checked( $competition_level, 'regionale' ); ?>> <?php esc_html_e( 'Régionale', 'dame' ); ?></label>
						<label><input type="radio" name="dame_competition_level" value="nationale" <?php checked( $competition_level, 'nationale' ); ?>> <?php esc_html_e( 'Nationale', 'dame' ); ?></label>
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
		<?php
	}

	/**
	 * Renders the meta box for agenda details.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_details( $post ): void {
		wp_nonce_field( 'dame_save_agenda_meta', 'dame_agenda_metabox_nonce' );

		// Check for transient data in case of a validation error on save.
		$transient_data = get_transient( 'dame_agenda_post_data_' . $post->ID );
		if ( $transient_data ) {
			// Clean up the transient so it's only used once.
			delete_transient( 'dame_agenda_post_data_' . $post->ID );
		}

		// Helper function to get value from transient first, then from post meta.
		$get_value = function ( $field_name, $default = '' ) use ( $post, $transient_data ) {
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
				<th><label for="dame_all_day"><?php esc_html_e( 'Journée entière', 'dame' ); ?></label></th>
				<td>
					<input type="checkbox" id="dame_all_day" name="dame_all_day" value="1" <?php checked( $all_day, '1' ); ?> />
				</td>
			</tr>
			<tr>
				<th><label for="dame_start_date"><?php esc_html_e( 'Date de début', 'dame' ); ?></label></th>
				<td>
					<input type="date" id="dame_start_date" name="dame_start_date" value="<?php echo esc_attr( $start_date ); ?>" />
					<span class="dame-time-fields 
					<?php
					if ( $all_day ) {
						echo 'hidden';}
					?>
					">
						<label for="dame_start_time" class="screen-reader-text"><?php esc_html_e( 'Heure de début', 'dame' ); ?></label>
						<input type="time" id="dame_start_time" name="dame_start_time" value="<?php echo esc_attr( $start_time ); ?>" step="900" />
					</span>
				</td>
			</tr>
			<tr>
				<th><label for="dame_end_date"><?php esc_html_e( 'Date de fin', 'dame' ); ?></label></th>
				<td>
					<input type="date" id="dame_end_date" name="dame_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
					<span class="dame-time-fields 
					<?php
					if ( $all_day ) {
						echo 'hidden';}
					?>
					">
						<label for="dame_end_time" class="screen-reader-text"><?php esc_html_e( 'Heure de fin', 'dame' ); ?></label>
						<input type="time" id="dame_end_time" name="dame_end_time" value="<?php echo esc_attr( $end_time ); ?>" step="900" />
					</span>
				</td>
			</tr>

			<!-- Location Fields -->
			<tr>
				<th colspan="2"><h4><?php esc_html_e( 'Lieu', 'dame' ); ?></h4></th>
			</tr>
			<tr>
				<th><label for="dame_location_name"><?php esc_html_e( 'Intitulé du lieu', 'dame' ); ?></label></th>
				<td><input type="text" id="dame_location_name" name="dame_location_name" value="<?php echo esc_attr( $location_name ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="dame_address_1"><?php esc_html_e( 'Adresse', 'dame' ); ?></label></th>
				<td>
					<div class="dame-autocomplete-wrapper" style="position: relative;">
						<input type="text" id="dame_address_1" name="dame_address_1" value="<?php echo esc_attr( $address_1 ); ?>" class="regular-text dame-js-address" data-group="event_location" autocomplete="off" />
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="dame_address_2"><?php esc_html_e( 'Complément', 'dame' ); ?></label></th>
				<td><input type="text" id="dame_address_2" name="dame_address_2" value="<?php echo esc_attr( $address_2 ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="dame_postal_code"><?php esc_html_e( 'Code Postal / Ville', 'dame' ); ?></label></th>
				<td>
					<div class="dame-inline-fields">
						<input type="text" id="dame_postal_code" name="dame_postal_code" value="<?php echo esc_attr( $postal_code ); ?>" class="postal-code dame-js-zip" data-group="event_location" placeholder="<?php esc_attr_e( 'Code Postal', 'dame' ); ?>" />
						<input type="text" id="dame_city" name="dame_city" value="<?php echo esc_attr( $city ); ?>" class="city dame-js-city" data-group="event_location" placeholder="<?php esc_attr_e( 'Ville', 'dame' ); ?>" />
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="dame_latitude"><?php esc_html_e( 'Latitude / Longitude', 'dame' ); ?></label></th>
				<td>
					<div class="dame-inline-fields">
						<input type="text" id="dame_latitude" name="dame_latitude" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_latitude', true ) ); ?>" readonly="readonly" class="dame-js-lat" data-group="event_location" placeholder="<?php esc_attr_e( 'Latitude', 'dame' ); ?>" />
						<input type="text" id="dame_longitude" name="dame_longitude" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_longitude', true ) ); ?>" readonly="readonly" class="dame-js-long" data-group="event_location" placeholder="<?php esc_attr_e( 'Longitude', 'dame' ); ?>" />
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="dame_distance"><?php esc_html_e( 'Distance / Temps de trajet', 'dame' ); ?></label></th>
				<td>
					<div class="dame-inline-fields">
						<input type="text" id="dame_distance" name="dame_distance" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_distance', true ) ); ?>" readonly="readonly" class="dame-js-dist" data-group="event_location" placeholder="<?php esc_attr_e( 'Distance (km)', 'dame' ); ?>" />
						<input type="text" id="dame_travel_time" name="dame_travel_time" value="<?php echo esc_attr( get_post_meta( $post->ID, '_dame_travel_time', true ) ); ?>" readonly="readonly" class="dame-js-time" data-group="event_location" placeholder="<?php esc_attr_e( 'Temps de trajet', 'dame' ); ?>" />
						<button type="button" id="dame_calculate_route_button" class="button dame-js-calc" data-group="event_location"><?php esc_html_e( 'Calculer', 'dame' ); ?></button>
					</div>
				</td>
			</tr>
		</table>
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
	public function render_participants( $post ): void {
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

		$adherents             = get_posts( $args );
		$selected_participants = get_post_meta( $post->ID, '_dame_event_participants', true );
		if ( ! is_array( $selected_participants ) ) {
			$selected_participants = array();
		}

		if ( empty( $adherents ) ) {
			echo '<p>' . esc_html__( 'Aucun adhérent actif pour la saison en cours.', 'dame' ) . '</p>';
			return;
		}

		// 3. Sort adherents to show selected ones first.
		$selected_list   = array();
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
					echo '<input type="checkbox" name="dame_event_participants[]" value="' . esc_attr( (string) $adherent->ID ) . '" ' . $checked . '> ';
					echo esc_html( $adherent->post_title );
					echo '</label>';
					echo '</li>';
				}
				?>
			</ul>
		</div>
		<p class="description"><?php esc_html_e( 'Seuls les adhérents avec une adhésion active pour la saison en cours sont listés.', 'dame' ); ?></p>
		<?php
	}

	/**
	 * Save meta box content for Agenda CPT.
	 *
	 * @param int $post_id Post ID
	 */
	public function save( $post_id ): void {
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
		$errors = array();

		// Check for at least one category checked.
		// tax_input for hierarchical taxonomies is an array of term IDs.
		// If empty, or all values are 0/empty, we have an error.
		$has_category = false;
		if ( isset( $_POST['tax_input']['dame_agenda_category'] ) && is_array( $_POST['tax_input']['dame_agenda_category'] ) ) {
			$cats = array_filter( $_POST['tax_input']['dame_agenda_category'] );
			if ( ! empty( $cats ) ) {
				$has_category = true;
			}
		}

		if ( ! $has_category ) {
			$errors[] = __( 'La catégorie est obligatoire.', 'dame' );
		}

		if ( empty( $_POST['dame_start_date'] ) ) {
			$errors[] = __( 'La date de début est obligatoire.', 'dame' );
		}
		if ( empty( $_POST['dame_end_date'] ) ) {
			$errors[] = __( 'La date de fin est obligatoire.', 'dame' );
		}
		if ( empty( $_POST['dame_competition_type'] ) ) {
			$errors[] = __( 'Le type de compétition est obligatoire.', 'dame' );
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
			remove_action( 'save_post_dame_agenda', array( $this, 'save' ) );

			// Update the post to be a draft
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'draft',
				)
			);

			// Re-hook the function add_action( 'save_post_dame_agenda', [ $this, 'save' ] ): void;
			return;
		}

		// If we are here, it means there are no errors, so we can delete any transient data
		delete_transient( 'dame_agenda_post_data_' . $post_id );

		// --- Sanitize and Save Data ---
		$fields = array(
			'dame_start_date'         => 'sanitize_text_field',
			'dame_start_time'         => 'sanitize_text_field',
			'dame_end_date'           => 'sanitize_text_field',
			'dame_end_time'           => 'sanitize_text_field',
			'dame_all_day'            => 'absint',
			'dame_competition_type'   => 'sanitize_key',
			'dame_competition_level'  => 'sanitize_key',
			'dame_location_name'      => 'sanitize_text_field',
			'dame_address_1'          => 'sanitize_text_field',
			'dame_address_2'          => 'sanitize_text_field',
			'dame_postal_code'        => 'sanitize_text_field',
			'dame_city'               => 'sanitize_text_field',
			'dame_latitude'           => 'sanitize_text_field',
			'dame_longitude'          => 'sanitize_text_field',
			'dame_distance'           => 'sanitize_text_field',
			'dame_travel_time'        => 'sanitize_text_field',
			'dame_agenda_description' => 'wp_kses_post',
		);

		foreach ( $fields as $field_name => $sanitize_callback ) {
			if ( isset( $_POST[ $field_name ] ) ) {
				$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) );
				update_post_meta( $post_id, '_' . $field_name, $value );
			} elseif ( 'absint' === $sanitize_callback ) {
					update_post_meta( $post_id, '_' . $field_name, 0 );
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

		// --- Handle Recurrence Batch Creation or Draft Saving ---
		$post_status    = get_post_status( $post_id );
		$existing_group = get_post_meta( $post_id, '_dame_recurrence_group_id', true );

		// 1. If not published (Draft, Auto-Draft, Pending, etc.)
		if ( 'publish' !== $post_status ) {
			if ( empty( $existing_group ) ) {
				if ( isset( $_POST['dame_enable_recurrence'] ) && '1' === $_POST['dame_enable_recurrence'] ) {
					$pending_config = array(
						'frequency'       => isset( $_POST['dame_recurrence_frequency'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_frequency'] ) ) : 'weekly',
						'interval_weeks'  => isset( $_POST['dame_recurrence_interval_weeks'] ) ? max( 1, (int) $_POST['dame_recurrence_interval_weeks'] ) : 1,
						'days_of_week'    => isset( $_POST['dame_recurrence_days_of_week'] ) && is_array( $_POST['dame_recurrence_days_of_week'] ) ? array_map( 'intval', $_POST['dame_recurrence_days_of_week'] ) : array(),
						'monthly_type'    => isset( $_POST['dame_recurrence_monthly_type'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_monthly_type'] ) ) : 'ordinal',
						'ordinal'         => isset( $_POST['dame_recurrence_ordinal'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_ordinal'] ) ) : 'first',
						'day_name'        => isset( $_POST['dame_recurrence_day_name'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_day_name'] ) ) : 'friday',
						'day_of_month'    => isset( $_POST['dame_recurrence_day_of_month'] ) ? (int) $_POST['dame_recurrence_day_of_month'] : 1,
						'end_type'        => isset( $_POST['dame_recurrence_end_type'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_end_type'] ) ) : 'until_date',
						'end_date'        => isset( $_POST['dame_recurrence_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_recurrence_end_date'] ) ) : '',
						'max_count'       => isset( $_POST['dame_recurrence_max_count'] ) ? max( 1, (int) $_POST['dame_recurrence_max_count'] ) : 10,
					);
					update_post_meta( $post_id, '_dame_recurrence_enabled', 1 );
					update_post_meta( $post_id, '_dame_recurrence_pending_config', $pending_config );
				} else {
					delete_post_meta( $post_id, '_dame_recurrence_enabled' );
					delete_post_meta( $post_id, '_dame_recurrence_pending_config' );
				}
			}
			return;
		}

		// 2. If post is published: create the batch series if enabled and not already created
		if ( empty( $existing_group ) ) {
			$is_enabled = ( isset( $_POST['dame_enable_recurrence'] ) && '1' === $_POST['dame_enable_recurrence'] )
				|| ( '1' === (string) get_post_meta( $post_id, '_dame_recurrence_enabled', true ) );

			if ( $is_enabled ) {
				$start_date_val = isset( $_POST['dame_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_start_date'] ) ) : (string) get_post_meta( $post_id, '_dame_start_date', true );
				$start_time_val = isset( $_POST['dame_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_start_time'] ) ) : (string) get_post_meta( $post_id, '_dame_start_time', true );
				if ( empty( $start_time_val ) ) {
					$start_time_val = '00:00';
				}

				if ( ! empty( $start_date_val ) ) {
					$start_datetime_str = sprintf( '%s %s', $start_date_val, $start_time_val );
					try {
						$start_dt = new DateTimeImmutable( $start_datetime_str );

						$pending_config = get_post_meta( $post_id, '_dame_recurrence_pending_config', true );
						if ( ! is_array( $pending_config ) ) {
							$pending_config = array();
						}

						$rules = array(
							'frequency'       => isset( $_POST['dame_recurrence_frequency'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_frequency'] ) ) : (string) ( $pending_config['frequency'] ?? 'weekly' ),
							'interval_weeks'  => isset( $_POST['dame_recurrence_interval_weeks'] ) ? max( 1, (int) $_POST['dame_recurrence_interval_weeks'] ) : (int) ( $pending_config['interval_weeks'] ?? 1 ),
							'days_of_week'    => isset( $_POST['dame_recurrence_days_of_week'] ) && is_array( $_POST['dame_recurrence_days_of_week'] ) ? array_map( 'intval', $_POST['dame_recurrence_days_of_week'] ) : ( is_array( $pending_config['days_of_week'] ?? null ) ? $pending_config['days_of_week'] : array() ),
							'monthly_type'    => isset( $_POST['dame_recurrence_monthly_type'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_monthly_type'] ) ) : (string) ( $pending_config['monthly_type'] ?? 'ordinal' ),
							'ordinal'         => isset( $_POST['dame_recurrence_ordinal'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_ordinal'] ) ) : (string) ( $pending_config['ordinal'] ?? 'first' ),
							'day_name'        => isset( $_POST['dame_recurrence_day_name'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_day_name'] ) ) : (string) ( $pending_config['day_name'] ?? 'friday' ),
							'day_of_month'    => isset( $_POST['dame_recurrence_day_of_month'] ) ? (int) $_POST['dame_recurrence_day_of_month'] : (int) ( $pending_config['day_of_month'] ?? $start_dt->format( 'j' ) ),
							'interval_months' => 1,
						);

						$end_type      = isset( $_POST['dame_recurrence_end_type'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_end_type'] ) ) : (string) ( $pending_config['end_type'] ?? 'until_date' );
						$end_date_str  = isset( $_POST['dame_recurrence_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_recurrence_end_date'] ) ) : (string) ( $pending_config['end_date'] ?? '' );
						$max_count_val = isset( $_POST['dame_recurrence_max_count'] ) ? (int) $_POST['dame_recurrence_max_count'] : ( isset( $pending_config['max_count'] ) ? (int) $pending_config['max_count'] : null );

						$user_end_date = null;
						$max_count     = null;

						if ( 'until_date' === $end_type && ! empty( $end_date_str ) ) {
							$user_end_date = new DateTimeImmutable( $end_date_str );
						} elseif ( 'count' === $end_type && ! empty( $max_count_val ) ) {
							$max_count = max( 1, $max_count_val );
						}

						$occurrences = Recurrence_Calculator::calculate_occurrences(
							$rules,
							$start_dt,
							$user_end_date,
							$max_count
						);

						if ( ! empty( $occurrences ) ) {
							Batch_Creator::create_series( $post_id, $occurrences );
						}

						delete_post_meta( $post_id, '_dame_recurrence_enabled' );
						delete_post_meta( $post_id, '_dame_recurrence_pending_config' );
					} catch ( \Exception $e ) {
						// Date format error handled gracefully.
					}
				}
			}
		}
	}

	/**
	 * Handles deleting an event and subsequent events in a series.
	 */
	public function handle_delete_series_from(): void {
		$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
		if ( ! $post_id || ! current_user_can( 'delete_post', $post_id ) ) {
			wp_die( esc_html__( 'Action non autorisée.', 'dame' ) );
		}

		check_admin_referer( 'dame_delete_series_from_' . $post_id );

		$deleted_count = Series_Manager::delete_series_from( $post_id );

		$redirect_url = add_query_arg(
			array(
				'post_type' => 'dame_agenda',
				'dame_msg'  => 'series_deleted',
				'count'     => $deleted_count,
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handles deleting an entire series.
	 */
	public function handle_delete_entire_series(): void {
		$group_id = isset( $_GET['group_id'] ) ? sanitize_text_field( wp_unslash( $_GET['group_id'] ) ) : '';
		$post_id  = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;

		if ( empty( $group_id ) || ( $post_id && ! current_user_can( 'delete_post', $post_id ) ) ) {
			wp_die( esc_html__( 'Action non autorisée.', 'dame' ) );
		}

		check_admin_referer( 'dame_delete_entire_series_' . $group_id );

		$deleted_count = Series_Manager::delete_entire_series( $group_id );

		$redirect_url = add_query_arg(
			array(
				'post_type' => 'dame_agenda',
				'dame_msg'  => 'entire_series_deleted',
				'count'     => $deleted_count,
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Display admin notices for series deletion.
	 */
	public function display_admin_notices(): void {
		if ( ! isset( $_GET['dame_msg'] ) ) {
			return;
		}

		$count = isset( $_GET['count'] ) ? (int) $_GET['count'] : 0;
		$msg   = sanitize_key( wp_unslash( $_GET['dame_msg'] ) );

		if ( 'series_deleted' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(
				/* translators: %d: nombre d'événements supprimés */
				esc_html__( '%d événement(s) de la série ont été mis à la corbeille avec succès.', 'dame' ),
				$count
			) . '</p></div>';
		} elseif ( 'entire_series_deleted' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(
				/* translators: %d: nombre d'événements supprimés */
				esc_html__( 'La série complète (%d événements) a été mise à la corbeille avec succès.', 'dame' ),
				$count
			) . '</p></div>';
		}
	}
}
