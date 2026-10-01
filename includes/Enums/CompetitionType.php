<?php
/**
 * Competition Type Backed Enum.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Enums;

/**
 * Competition Type enum.
 */
enum CompetitionType: string {
	case NONE       = 'non';
	case INDIVIDUAL = 'individuel';
	case TEAM       = 'equipe';

	/**
	 * Returns human-readable French label.
	 *
	 * @return string
	 */
	public function label(): string {
		return match ( $this ) {
			self::NONE       => 'Non / Loisir',
			self::INDIVIDUAL => 'Compétition individuelle',
			self::TEAM       => 'Compétition par équipe',
		};
	}

	/**
	 * Resolves an enum instance from raw value with fallback.
	 *
	 * @param string|null $raw Raw value.
	 * @return self
	 */
	public static function from_raw( ?string $raw ): self {
		if ( empty( $raw ) ) {
			return self::NONE;
		}

		return self::tryFrom( trim( $raw ) ) ?? self::NONE;
	}
}
