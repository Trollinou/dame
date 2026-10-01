<?php
/**
 * Pre-Inscription Data Transfer Object.
 *
 * @package DAME\DTO
 */

declare(strict_types=1);

namespace DAME\DTO;

use DAME\Enums\Gender;
use DAME\Enums\HealthDocumentStatus;
use WP_Post;

/**
 * Readonly DTO representing a Pre-Inscription submission.
 */
readonly class PreInscriptionDTO {

	/**
	 * Constructor with property promotion.
	 *
	 * @param int                  $id               Post ID.
	 * @param string               $first_name       Prénom.
	 * @param string               $last_name        Nom.
	 * @param string|null          $birth_date       Date de naissance.
	 * @param Gender               $gender           Sexe (Enum).
	 * @param string|null          $email            Courriel.
	 * @param string|null          $phone            Téléphone.
	 * @param string|null          $address          Adresse.
	 * @param string|null          $postal_code      Code postal.
	 * @param string|null          $city             Ville.
	 * @param string|null          $department       Département (code 2-3 caractères).
	 * @param string|null          $region           Région (code 3 caractères).
	 * @param HealthDocumentStatus $health_status    Statut document de santé (Enum).
	 * @param string|null          $ffe_id           Code licence FFE existant.
	 * @param string|null          $club_origin      Club d'origine si mutation.
	 */
	public function __construct(
		public int $id,
		public string $first_name,
		public string $last_name,
		public ?string $birth_date = null,
		public Gender $gender = Gender::OTHER,
		public ?string $email = null,
		public ?string $phone = null,
		public ?string $address = null,
		public ?string $postal_code = null,
		public ?string $city = null,
		public ?string $department = null,
		public ?string $region = null,
		public HealthDocumentStatus $health_status = HealthDocumentStatus::NONE,
		public ?string $ffe_id = null,
		public ?string $club_origin = null,
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

		return new self(
			id: $id,
			first_name: (string) get_post_meta( $id, '_dame_first_name', true ),
			last_name: (string) get_post_meta( $id, '_dame_last_name', true ),
			birth_date: get_post_meta( $id, '_dame_birth_date', true ) ?: null,
			gender: Gender::from_raw( (string) get_post_meta( $id, '_dame_sexe', true ) ),
			email: get_post_meta( $id, '_dame_email', true ) ?: null,
			phone: ( get_post_meta( $id, '_dame_phone_number', true ) ?: get_post_meta( $id, '_dame_phone', true ) ) ?: null,
			address: ( get_post_meta( $id, '_dame_address_1', true ) ?: get_post_meta( $id, '_dame_address', true ) ) ?: null,
			postal_code: get_post_meta( $id, '_dame_postal_code', true ) ?: null,
			city: get_post_meta( $id, '_dame_city', true ) ?: null,
			department: get_post_meta( $id, '_dame_department', true ) ?: null,
			region: get_post_meta( $id, '_dame_region', true ) ?: null,
			health_status: HealthDocumentStatus::from_raw( (string) ( get_post_meta( $id, '_dame_health_document', true ) ?: get_post_meta( $id, '_dame_health_document_status', true ) ) ),
			ffe_id: get_post_meta( $id, '_dame_ffe_id', true ) ?: null,
			club_origin: get_post_meta( $id, '_dame_club_origin', true ) ?: null,
		);
	}

	/**
	 * Convert DTO to associative array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'            => $this->id,
			'first_name'    => $this->first_name,
			'last_name'     => $this->last_name,
			'birth_date'    => $this->birth_date,
			'gender'        => $this->gender->value,
			'email'         => $this->email,
			'phone'         => $this->phone,
			'address'       => $this->address,
			'postal_code'   => $this->postal_code,
			'city'          => $this->city,
			'department'    => $this->department,
			'region'        => $this->region,
			'health_status' => $this->health_status->value,
			'ffe_id'        => $this->ffe_id,
			'club_origin'   => $this->club_origin,
		);
	}
}
