<?php
/**
 * Agenda Save Handler.
 *
 * @package DAME\Metaboxes\Agenda
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Agenda;

use DateTimeImmutable;
use DAME\Services\Agenda\Recurrence_Calculator;
use DAME\Services\Agenda\Batch_Creator;

/**
 * Handles validation, post meta persistence and recurrence batch generation upon saving an Agenda event.
 */
class SaveHandler {

	/**
	 * Save meta box content for Agenda CPT.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( int $post_id ): void {
		// --- Security checks ---.
		if ( ! isset( $_POST['dame_agenda_metabox_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['dame_agenda_metabox_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'dame_save_agenda_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// --- Validation ---.
		$errors = array();

		$has_category = false;
		if ( isset( $_POST['tax_input']['dame_agenda_category'] ) && is_array( $_POST['tax_input']['dame_agenda_category'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_cats = wp_unslash( $_POST['tax_input']['dame_agenda_category'] );
			$cats     = array_filter( array_map( 'absint', (array) $raw_cats ) );
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

			$post_data_to_save = array();
			foreach ( $_POST as $key => $value ) {
				if ( str_starts_with( $key, 'dame_' ) || 'tax_input' === $key ) {
					$post_data_to_save[ $key ] = wp_unslash( $value );
				}
			}
			set_transient( 'dame_agenda_post_data_' . $post_id, $post_data_to_save, 60 );

			remove_action( 'save_post_dame_agenda', array( $this, 'save' ) );

			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'draft',
				)
			);
			return;
		}

		delete_transient( 'dame_agenda_post_data_' . $post_id );

		// --- Sanitize and Save Data ---.
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
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized dynamically via $sanitize_callback.
				$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field_name ] ) );
				update_post_meta( $post_id, '_' . $field_name, $value );
			} elseif ( 'absint' === $sanitize_callback ) {
				update_post_meta( $post_id, '_' . $field_name, 0 );
			}
		}

		// --- Save Participants ---.
		if ( isset( $_POST['dame_event_participants'] ) && is_array( $_POST['dame_event_participants'] ) ) {
			$participant_ids = array_map( 'intval', $_POST['dame_event_participants'] );
			update_post_meta( $post_id, '_dame_event_participants', $participant_ids );
		} else {
			update_post_meta( $post_id, '_dame_event_participants', array() );
		}

		// --- Handle Recurrence ---.
		$this->handle_recurrence( $post_id );
	}

	/**
	 * Handles recurrence configuration or series batch creation.
	 *
	 * @param int $post_id Post ID.
	 */
	private function handle_recurrence( int $post_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified in calling save() method.
		$post_status    = get_post_status( $post_id );
		$existing_group = get_post_meta( $post_id, '_dame_recurrence_group_id', true );

		// 1. Not published yet (Draft, Auto-Draft, Pending).
		if ( 'publish' !== $post_status ) {
			if ( empty( $existing_group ) ) {
				if ( isset( $_POST['dame_enable_recurrence'] ) && '1' === $_POST['dame_enable_recurrence'] ) {
					$pending_config = array(
						'frequency'      => isset( $_POST['dame_recurrence_frequency'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_frequency'] ) ) : 'weekly',
						'interval_weeks' => isset( $_POST['dame_recurrence_interval_weeks'] ) ? max( 1, (int) $_POST['dame_recurrence_interval_weeks'] ) : 1,
						'days_of_week'   => isset( $_POST['dame_recurrence_days_of_week'] ) && is_array( $_POST['dame_recurrence_days_of_week'] ) ? array_map( 'intval', $_POST['dame_recurrence_days_of_week'] ) : array(),
						'monthly_type'   => isset( $_POST['dame_recurrence_monthly_type'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_monthly_type'] ) ) : 'ordinal',
						'ordinal'        => isset( $_POST['dame_recurrence_ordinal'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_ordinal'] ) ) : 'first',
						'day_name'       => isset( $_POST['dame_recurrence_day_name'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_day_name'] ) ) : 'friday',
						'day_of_month'   => isset( $_POST['dame_recurrence_day_of_month'] ) ? (int) $_POST['dame_recurrence_day_of_month'] : 1,
						'end_type'       => isset( $_POST['dame_recurrence_end_type'] ) ? sanitize_key( wp_unslash( $_POST['dame_recurrence_end_type'] ) ) : 'until_date',
						'end_date'       => isset( $_POST['dame_recurrence_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_recurrence_end_date'] ) ) : '',
						'max_count'      => isset( $_POST['dame_recurrence_max_count'] ) ? max( 1, (int) $_POST['dame_recurrence_max_count'] ) : 10,
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

		// 2. Published: create batch series if enabled and not already created.
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
						unset( $e );
					}
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
