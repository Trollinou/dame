<?php
/**
 * Member Profile Data Transfer Object.
 *
 * @package DAME\DTO
 */

declare(strict_types=1);

namespace DAME\DTO;

use DAME\Enums\Gender;
use WP_Post;

/**
 * Readonly DTO representing an Adherent member profile.
 */
readonly class MemberProfileDTO {

	/**
	 * Constructor with property promotion.
	 *
	 * @param int         $id           Post ID.
	 * @param string      $first_name   Prénom.
	 * @param string      $last_name    Nom d'usage.
	 * @param string|null $birth_name   Nom de naissance.
	 * @param string|null $birth_date   Date de naissance (YYYY-MM-DD).
	 * @param Gender      $gender       Sexe (Enum).
	 * @param string|null $email        Adresse courriel.
	 * @param string|null $phone        Téléphone principal.
	 * @param string|null $mobile_phone Téléphone mobile.
	 * @param string|null $address      Adresse postale.
	 * @param string|null $postal_code  Code postal.
	 * @param string|null $city         Ville.
	 * @param string|null $ffe_id       Code licence FFE.
	 * @param int|null    $elo          Classement Elo.
	 */
	public function __construct(
		public int $id,
		public string $first_name,
		public string $last_name,
		public ?string $birth_name = null,
		public ?string $birth_date = null,
		public Gender $gender = Gender::OTHER,
		public ?string $email = null,
		public ?string $phone = null,
		public ?string $mobile_phone = null,
		public ?string $address = null,
		public ?string $postal_code = null,
		public ?string $city = null,
		public ?string $ffe_id = null,
		public ?int $elo = null,
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
			birth_name: get_post_meta( $id, '_dame_birth_name', true ) ?: null,
			birth_date: get_post_meta( $id, '_dame_birth_date', true ) ?: null,
			gender: Gender::from_raw( (string) get_post_meta( $id, '_dame_sexe', true ) ),
			email: get_post_meta( $id, '_dame_email', true ) ?: null,
			phone: get_post_meta( $id, '_dame_phone_number', true ) ?: ( get_post_meta( $id, '_dame_phone', true ) ?: null ),
			mobile_phone: get_post_meta( $id, '_dame_autre_telephone', true ) ?: ( get_post_meta( $id, '_dame_mobile_phone', true ) ?: null ),
			address: get_post_meta( $id, '_dame_address_1', true ) ?: ( get_post_meta( $id, '_dame_address', true ) ?: null ),
			postal_code: get_post_meta( $id, '_dame_postal_code', true ) ?: null,
			city: get_post_meta( $id, '_dame_city', true ) ?: null,
			ffe_id: get_post_meta( $id, '_dame_license_number', true ) ?: ( get_post_meta( $id, '_dame_ffe_id', true ) ?: null ),
			elo: is_numeric( get_post_meta( $id, '_dame_elo', true ) ) ? (int) get_post_meta( $id, '_dame_elo', true ) : null,
		);
	}

	/**
	 * Returns full formatted display name.
	 *
	 * @return string
	 */
	public function get_full_name(): string {
		return trim( "{$this->first_name} {$this->last_name}" );
	}

	/**
	 * Convert DTO to associative array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'           => $this->id,
			'first_name'   => $this->first_name,
			'last_name'    => $this->last_name,
			'birth_name'   => $this->birth_name,
			'birth_date'   => $this->birth_date,
			'gender'       => $this->gender->value,
			'email'        => $this->email,
			'phone'        => $this->phone,
			'mobile_phone' => $this->mobile_phone,
			'address'      => $this->address,
			'postal_code'  => $this->postal_code,
			'city'         => $this->city,
			'ffe_id'       => $this->ffe_id,
			'elo'          => $this->elo,
			'full_name'    => $this->get_full_name(),
		);
	}
}
