<?php
/**
 * Agenda Event Data Transfer Object.
 *
 * @package DAME\DTO
 */

declare(strict_types=1);

namespace DAME\DTO;

use DAME\Enums\CompetitionType;
use DAME\Enums\CompetitionLevel;
use WP_Post;

/**
 * Readonly DTO representing an Agenda calendar event.
 */
readonly class AgendaEventDTO {

	/**
	 * Constructor with property promotion.
	 *
	 * @param int                   $id                Post ID.
	 * @param string                $title             Titre de l'événement.
	 * @param string                $start_date        Date de début (YYYY-MM-DD).
	 * @param string                $end_date          Date de fin (YYYY-MM-DD).
	 * @param bool                  $all_day           Journée entière.
	 * @param string|null           $start_time        Heure de début (HH:MM).
	 * @param string|null           $end_time          Heure de fin (HH:MM).
	 * @param string|null           $location          Nom du lieu.
	 * @param string|null           $address           Adresse physique.
	 * @param float|null            $latitude          Latitude GPS.
	 * @param float|null            $longitude         Longitude GPS.
	 * @param CompetitionType       $competition_type  Type de compétition (Enum).
	 * @param CompetitionLevel|null $competition_level Niveau de compétition (Enum).
	 * @param string|null           $color             Code couleur hex (#RRGGBB).
	 * @param string|null           $series_id         ID de série récurrente.
	 */
	public function __construct(
		public int $id,
		public string $title,
		public string $start_date,
		public string $end_date,
		public bool $all_day = false,
		public ?string $start_time = null,
		public ?string $end_time = null,
		public ?string $location = null,
		public ?string $address = null,
		public ?float $latitude = null,
		public ?float $longitude = null,
		public CompetitionType $competition_type = CompetitionType::NONE,
		public ?CompetitionLevel $competition_level = null,
		public ?string $color = null,
		public ?string $series_id = null,
	) {}

	/**
	 * Creates a DTO instance from a WP_Post or Post ID.
	 *
	 * @param WP_Post|int $post Post object or ID.
	 * @return self|null
	 */
	public static function from_post( WP_Post|int $post ): ?self {
		$post_obj = is_numeric( $post ) ? get_post( (int) $post ) : $post;
		if ( ! ( $post_obj instanceof WP_Post ) ) {
			return null;
		}

		$id = $post_obj->ID;

		$start_date = (string) get_post_meta( $id, '_dame_start_date', true );
		$end_date   = (string) get_post_meta( $id, '_dame_end_date', true );
		if ( empty( $end_date ) ) {
			$end_date = $start_date;
		}

		$start_time = (string) get_post_meta( $id, '_dame_start_time', true );
		$end_time   = (string) get_post_meta( $id, '_dame_end_time', true );
		$location   = (string) get_post_meta( $id, '_dame_location_name', true );
		if ( empty( $location ) ) {
			$location = (string) get_post_meta( $id, '_dame_location', true );
		}
		$address = (string) get_post_meta( $id, '_dame_address_1', true );
		if ( empty( $address ) ) {
			$address = (string) get_post_meta( $id, '_dame_address', true );
		}
		$color     = (string) get_post_meta( $id, '_dame_color', true );
		$series_id = (string) get_post_meta( $id, '_dame_recurrence_group_id', true );

		return new self(
			id: $id,
			title: $post_obj->post_title,
			start_date: $start_date,
			end_date: $end_date,
			all_day: '1' === (string) get_post_meta( $id, '_dame_all_day', true ),
			start_time: ! empty( $start_time ) ? $start_time : null,
			end_time: ! empty( $end_time ) ? $end_time : null,
			location: ! empty( $location ) ? $location : null,
			address: ! empty( $address ) ? $address : null,
			latitude: is_numeric( get_post_meta( $id, '_dame_latitude', true ) ) ? (float) get_post_meta( $id, '_dame_latitude', true ) : null,
			longitude: is_numeric( get_post_meta( $id, '_dame_longitude', true ) ) ? (float) get_post_meta( $id, '_dame_longitude', true ) : null,
			competition_type: CompetitionType::from_raw( (string) get_post_meta( $id, '_dame_competition_type', true ) ),
			competition_level: CompetitionLevel::from_raw( (string) get_post_meta( $id, '_dame_competition_level', true ) ),
			color: ! empty( $color ) ? $color : null,
			series_id: ! empty( $series_id ) ? $series_id : null,
		);
	}

	/**
	 * Convert DTO to associative array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                => $this->id,
			'title'             => $this->title,
			'start_date'        => $this->start_date,
			'end_date'          => $this->end_date,
			'all_day'           => $this->all_day,
			'start_time'        => $this->start_time,
			'end_time'          => $this->end_time,
			'location'          => $this->location,
			'address'           => $this->address,
			'latitude'          => $this->latitude,
			'longitude'         => $this->longitude,
			'competition_type'  => $this->competition_type->value,
			'competition_level' => $this->competition_level?->value,
			'color'             => $this->color,
			'series_id'         => $this->series_id,
		);
	}
}
