<?php
/**
 * REST API Post Meta Registration.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\API\REST;

/**
 * Class Post_Meta
 * Handles the registration of custom fields to be exposed in the WordPress REST API.
 */
class Post_Meta {

	/**
	 * Initialize the meta registration.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_all_meta' ] );
		add_action( 'rest_api_init', [ $this, 'register_custom_rest_fields' ] );
		add_action( 'rest_after_insert_adherent', [ $this, 'update_titles_after_rest' ] );
		add_action( 'rest_after_insert_dame_contact', [ $this, 'update_titles_after_rest' ] );
		add_action( 'rest_after_insert_dame_pre_inscription', [ $this, 'update_titles_after_rest' ] );
	}

	/**
	 * Registers custom REST fields that are not automatically handled by register_meta.
	 */
	public function register_custom_rest_fields(): void {
		// Sondage Data
		register_rest_field(
			'sondage',
			'dame_sondage_data',
			[
				'get_callback' => function( $post_arr ) {
					return get_post_meta( $post_arr['id'], '_dame_sondage_data', true );
				},
				'update_callback' => function( $value, $post_obj ) {
					return update_post_meta( $post_obj->ID, '_dame_sondage_data', $value );
				},
				'schema' => [
					'description' => __( 'Structured sondage data (dates and time slots).', 'dame' ),
					'type'        => 'array',
				],
			]
		);

		// Sondage Response Parent ID
		register_rest_field(
			'sondage_reponse',
			'sondage_id',
			[
				'get_callback' => function( $post_arr ) {
					return wp_get_post_parent_id( $post_arr['id'] );
				},
				'schema' => [
					'description' => __( 'ID of the parent sondage.', 'dame' ),
					'type'        => 'integer',
				],
			]
		);
	}

	/**
	 * Updates the post title after a REST API request if meta fields changed.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function update_titles_after_rest( $post ): void {
		$post_id   = $post->ID;
		$post_type = $post->post_type;
		$new_title = '';

		if ( 'adherent' === $post_type || 'dame_pre_inscription' === $post_type ) {
			$new_title = \DAME\Core\Utils::generate_adherent_title( $post_id );
		} elseif ( 'dame_contact' === $post_type ) {
			$new_title = \DAME\Core\Utils::generate_contact_title( $post_id );
		}

		if ( $new_title && get_the_title( $post_id ) !== $new_title ) {
			// Avoid infinite loop if somehow triggered again.
			remove_action( 'rest_after_insert_adherent', [ $this, 'update_titles_after_rest' ] );
			remove_action( 'rest_after_insert_dame_contact', [ $this, 'update_titles_after_rest' ] );
			remove_action( 'rest_after_insert_dame_pre_inscription', [ $this, 'update_titles_after_rest' ] );

			wp_update_post(
				[
					'ID'         => $post_id,
					'post_title' => $new_title,
					'post_name'  => sanitize_title( $new_title ),
				]
			);
		}
	}

	/**
	 * Register all meta keys for the REST API.
	 */
	public function register_all_meta(): void {
		$this->register_adherent_meta();
		$this->register_agenda_meta();
		$this->register_contact_meta();
		$this->register_sondage_meta();
		$this->register_ical_feed_meta();
		$this->register_pre_inscription_meta();
	}

	/**
	 * Helper to register a list of meta keys for a specific post type.
	 *
	 * @param string $post_type The custom post type.
	 * @param array<string, string> $fields Array of field_name => type.
	 */
	private function register_fields( string $post_type, array $fields ): void {
		foreach ( $fields as $field_name => $type ) {
			register_meta(
				'post',
				$field_name,
				[
					'object_subtype'    => $post_type,
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => $type,
					'auth_callback'     => [ $this, 'auth_callback' ],
				]
			);
		}
	}

	/**
	 * Authentication callback for meta fields.
	 *
	 * @return bool
	 */
	public function auth_callback(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Register meta for Adherent CPT.
	 */
	private function register_adherent_meta(): void {
		$fields = [
			'_dame_birth_name'                     => 'string',
			'_dame_last_name'                      => 'string',
			'_dame_first_name'                     => 'string',
			'_dame_sexe'                           => 'string',
			'_dame_birth_date'                     => 'string',
			'_dame_birth_city'                     => 'string',
			'_dame_phone_number'                   => 'string',
			'_dame_email'                          => 'string',
			'_dame_email_refuses_comms'            => 'integer',
			'_dame_profession'                     => 'string',
			'_dame_address_1'                      => 'string',
			'_dame_address_2'                      => 'string',
			'_dame_postal_code'                    => 'string',
			'_dame_city'                           => 'string',
			'_dame_country'                        => 'string',
			'_dame_region'                         => 'string',
			'_dame_department'                     => 'string',
			'_dame_latitude'                       => 'string',
			'_dame_longitude'                      => 'string',
			'_dame_distance'                       => 'string',
			'_dame_travel_time'                    => 'string',
			'_dame_license_type'                   => 'string',
			'_dame_license_number'                 => 'string',
			'_dame_linked_wp_user'                 => 'integer',
			'_dame_arbitre_level'                  => 'string',
			'_dame_health_document'                => 'string',
			'_dame_adherent_honorabilite'          => 'string',
			'_dame_autre_telephone'                => 'string',
			'_dame_taille_vetements'               => 'string',
			'_dame_allergies'                      => 'string',
			'_dame_diet'                           => 'string',
			'_dame_transport'                      => 'string',
			'_dame_legal_rep_1_first_name'         => 'string',
			'_dame_legal_rep_1_last_name'          => 'string',
			'_dame_legal_rep_1_email'              => 'string',
			'_dame_legal_rep_1_email_refuses_comms' => 'integer',
			'_dame_legal_rep_1_phone'              => 'string',
			'_dame_legal_rep_1_profession'         => 'string',
			'_dame_legal_rep_1_address_1'          => 'string',
			'_dame_legal_rep_1_address_2'          => 'string',
			'_dame_legal_rep_1_postal_code'        => 'string',
			'_dame_legal_rep_1_city'               => 'string',
			'_dame_legal_rep_1_date_naissance'     => 'string',
			'_dame_legal_rep_1_commune_naissance'  => 'string',
			'_dame_legal_rep_1_honorabilite'       => 'string',
			'_dame_legal_rep_2_first_name'         => 'string',
			'_dame_legal_rep_2_last_name'          => 'string',
			'_dame_legal_rep_2_email'              => 'string',
			'_dame_legal_rep_2_email_refuses_comms' => 'integer',
			'_dame_legal_rep_2_phone'              => 'string',
			'_dame_legal_rep_2_profession'         => 'string',
			'_dame_legal_rep_2_address_1'          => 'string',
			'_dame_legal_rep_2_address_2'          => 'string',
			'_dame_legal_rep_2_postal_code'        => 'string',
			'_dame_legal_rep_2_city'               => 'string',
			'_dame_legal_rep_2_date_naissance'     => 'string',
			'_dame_legal_rep_2_commune_naissance'  => 'string',
			'_dame_legal_rep_2_honorabilite'       => 'string',
			'_dame_school_name'                    => 'string',
			'_dame_school_academy'                 => 'string',
		];

		$this->register_fields( 'adherent', $fields );
	}

	/**
	 * Register meta for Agenda CPT.
	 */
	private function register_agenda_meta(): void {
		$fields = [
			'_dame_start_date'        => 'string',
			'_dame_start_time'        => 'string',
			'_dame_end_date'          => 'string',
			'_dame_end_time'          => 'string',
			'_dame_all_day'           => 'integer',
			'_dame_competition_type'  => 'string',
			'_dame_competition_level' => 'string',
			'_dame_location_name'     => 'string',
			'_dame_address_1'         => 'string',
			'_dame_address_2'         => 'string',
			'_dame_postal_code'       => 'string',
			'_dame_city'              => 'string',
			'_dame_latitude'          => 'string',
			'_dame_longitude'         => 'string',
			'_dame_distance'          => 'string',
			'_dame_travel_time'       => 'string',
			'_dame_agenda_description' => 'string',
		];

		$this->register_fields( 'dame_agenda', $fields );

		// Participants is an array, needs special handling or 'string' with serialization (not ideal)
		// Better to use 'array' type if supported or custom register_rest_field.
		// WordPress register_meta supports 'array' type since 5.3.
		register_meta(
			'post',
			'_dame_event_participants',
			[
				'object_subtype'    => 'dame_agenda',
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [ 'type' => 'integer' ],
					],
				],
				'single'            => true,
				'type'              => 'array',
				'auth_callback'     => [ $this, 'auth_callback' ],
			]
		);
	}

	/**
	 * Register meta for Contact CPT.
	 */
	private function register_contact_meta(): void {
		$fields = [
			'_dame_contact_organization' => 'string',
			'_dame_contact_last_name'    => 'string',
			'_dame_contact_first_name'   => 'string',
			'_dame_contact_role'         => 'string',
			'_dame_contact_email'        => 'string',
			'_dame_contact_no_emails'    => 'string',
			'_dame_contact_phone'        => 'string',
			'_dame_contact_address_1'    => 'string',
			'_dame_contact_address_2'    => 'string',
			'_dame_contact_postcode'     => 'string',
			'_dame_contact_city'         => 'string',
			'_dame_contact_department'   => 'string',
			'_dame_contact_region'       => 'string',
		];

		$this->register_fields( 'dame_contact', $fields );
	}

	/**
	 * Register meta for Sondage CPT.
	 */
	private function register_sondage_meta(): void {
		// Sondage data is a complex array.
		register_meta(
			'post',
			'_dame_sondage_data',
			[
				'object_subtype'    => 'sondage',
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'date'       => [ 'type' => 'string' ],
								'time_slots' => [
									'type'  => 'array',
									'items' => [
										'type'       => 'object',
										'properties' => [
											'start' => [ 'type' => 'string' ],
											'end'   => [ 'type' => 'string' ],
										],
									],
								],
							],
						],
					],
				],
				'single'            => true,
				'type'              => 'array',
				'auth_callback'     => [ $this, 'auth_callback' ],
			]
		);
	}

	/**
	 * Register meta for iCal Feed CPT.
	 */
	private function register_ical_feed_meta(): void {
		register_meta(
			'post',
			'_dame_ical_feed_categories',
			[
				'object_subtype'    => 'dame_ical_feed',
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [ 'type' => 'integer' ],
					],
				],
				'single'            => true,
				'type'              => 'array',
				'auth_callback'     => [ $this, 'auth_callback' ],
			]
		);
	}

	/**
	 * Register meta for Pre-Inscription CPT.
	 */
	private function register_pre_inscription_meta(): void {
		$fields = [
			'_dame_first_name'                     => 'string',
			'_dame_last_name'                      => 'string',
			'_dame_birth_name'                     => 'string',
			'_dame_birth_date'                     => 'string',
			'_dame_license_type'                   => 'string',
			'_dame_birth_city'                     => 'string',
			'_dame_sexe'                           => 'string',
			'_dame_profession'                     => 'string',
			'_dame_email'                          => 'string',
			'_dame_phone_number'                   => 'string',
			'_dame_address_1'                      => 'string',
			'_dame_address_2'                      => 'string',
			'_dame_postal_code'                    => 'string',
			'_dame_city'                           => 'string',
			'_dame_taille_vetements'               => 'string',
			'_dame_health_document'                => 'string',
			'_dame_legal_rep_1_honorabilite'       => 'string',
			'_dame_legal_rep_2_honorabilite'       => 'string',
			'_dame_legal_rep_1_first_name'         => 'string',
			'_dame_legal_rep_1_last_name'          => 'string',
			'_dame_legal_rep_1_email'              => 'string',
			'_dame_legal_rep_1_phone'              => 'string',
			'_dame_legal_rep_1_address_1'          => 'string',
			'_dame_legal_rep_1_address_2'          => 'string',
			'_dame_legal_rep_1_postal_code'        => 'string',
			'_dame_legal_rep_1_city'               => 'string',
			'_dame_legal_rep_1_profession'         => 'string',
			'_dame_legal_rep_1_date_naissance'     => 'string',
			'_dame_legal_rep_1_commune_naissance'  => 'string',
			'_dame_legal_rep_2_first_name'         => 'string',
			'_dame_legal_rep_2_last_name'          => 'string',
			'_dame_legal_rep_2_email'              => 'string',
			'_dame_legal_rep_2_phone'              => 'string',
			'_dame_legal_rep_2_address_1'          => 'string',
			'_dame_legal_rep_2_address_2'          => 'string',
			'_dame_legal_rep_2_postal_code'        => 'string',
			'_dame_legal_rep_2_city'               => 'string',
			'_dame_legal_rep_2_profession'         => 'string',
			'_dame_legal_rep_2_date_naissance'     => 'string',
			'_dame_legal_rep_2_commune_naissance'  => 'string',
		];

		$this->register_fields( 'dame_pre_inscription', $fields );
	}
}
