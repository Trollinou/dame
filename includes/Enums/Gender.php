<?php
/**
 * Gender Backed Enum.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Enums;

/**
 * Gender / Sexe enum.
 */
enum Gender: string {
	case MALE   = 'M';
	case FEMALE = 'F';
	case OTHER  = 'Autre';

	/**
	 * Returns human-readable French label.
	 *
	 * @return string
	 */
	public function label(): string {
		return match ( $this ) {
			self::MALE   => 'Masculin',
			self::FEMALE => 'Féminin',
			self::OTHER  => 'Autre / Non précisé',
		};
	}

	/**
	 * Resolves an enum instance from raw user or database input.
	 *
	 * @param string|null $raw Raw input value.
	 * @return self
	 */
	public static function from_raw( ?string $raw ): self {
		if ( empty( $raw ) ) {
			return self::OTHER;
		}

		$normalized = strtoupper( trim( $raw ) );
		if ( 'M' === $normalized || 'MASCULIN' === $normalized || 'HOMME' === $normalized ) {
			return self::MALE;
		}
		if ( 'F' === $normalized || 'FEMININ' === $normalized || 'FEMME' === $normalized ) {
			return self::FEMALE;
		}

		return self::tryFrom( $raw ) ?? self::OTHER;
	}
}
