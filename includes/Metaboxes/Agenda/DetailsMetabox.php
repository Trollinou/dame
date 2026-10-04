<?php
/**
 * Agenda Details Metabox.
 *
 * @package DAME\Metaboxes\Agenda
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Agenda;

use WP_Post;

/**
 * Renders the Event Details, Dates, Times, Location and Geolocation metabox for Agenda events.
 */
class DetailsMetabox {

	/**
	 * Renders the meta box for agenda details.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'dame_save_agenda_meta', 'dame_agenda_metabox_nonce' );

		$transient_data = get_transient( 'dame_agenda_post_data_' . $post->ID );
		if ( $transient_data ) {
			delete_transient( 'dame_agenda_post_data_' . $post->ID );
		}

		$get_value = function ( string $field_name, string $default_value = '' ) use ( $post, $transient_data ): string {
			if ( is_array( $transient_data ) && isset( $transient_data[ $field_name ] ) ) {
				return esc_attr( (string) $transient_data[ $field_name ] );
			}
			$val = (string) get_post_meta( $post->ID, '_' . $field_name, true );
			return '' !== $val ? $val : $default_value;
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
					<span class="dame-time-fields <?php echo $all_day ? 'hidden' : ''; ?>">
						<label for="dame_start_time" class="screen-reader-text"><?php esc_html_e( 'Heure de début', 'dame' ); ?></label>
						<input type="time" id="dame_start_time" name="dame_start_time" value="<?php echo esc_attr( $start_time ); ?>" step="900" />
					</span>
				</td>
			</tr>
			<tr>
				<th><label for="dame_end_date"><?php esc_html_e( 'Date de fin', 'dame' ); ?></label></th>
				<td>
					<input type="date" id="dame_end_date" name="dame_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
					<span class="dame-time-fields <?php echo $all_day ? 'hidden' : ''; ?>">
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
					<div class="dame-autocomplete-wrapper">
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
						<input type="text" id="dame_latitude" name="dame_latitude" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, '_dame_latitude', true ) ); ?>" readonly="readonly" class="dame-js-lat" data-group="event_location" placeholder="<?php esc_attr_e( 'Latitude', 'dame' ); ?>" />
						<input type="text" id="dame_longitude" name="dame_longitude" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, '_dame_longitude', true ) ); ?>" readonly="readonly" class="dame-js-long" data-group="event_location" placeholder="<?php esc_attr_e( 'Longitude', 'dame' ); ?>" />
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="dame_distance"><?php esc_html_e( 'Distance / Temps de trajet', 'dame' ); ?></label></th>
				<td>
					<div class="dame-inline-fields">
						<input type="text" id="dame_distance" name="dame_distance" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, '_dame_distance', true ) ); ?>" readonly="readonly" class="dame-js-dist" data-group="event_location" placeholder="<?php esc_attr_e( 'Distance (km)', 'dame' ); ?>" />
						<input type="text" id="dame_travel_time" name="dame_travel_time" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, '_dame_travel_time', true ) ); ?>" readonly="readonly" class="dame-js-time" data-group="event_location" placeholder="<?php esc_attr_e( 'Temps de trajet', 'dame' ); ?>" />
						<button type="button" id="dame_calculate_route_button" class="button dame-js-calc" data-group="event_location"><?php esc_html_e( 'Calculer', 'dame' ); ?></button>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}
}
