<?php
/**
 * Competition Level Backed Enum.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Enums;

/**
 * Competition Level enum.
 */
enum CompetitionLevel: string {
	case DEPARTEMENTAL = 'departemental';
	case REGIONAL      = 'regional';
	case NATIONAL      = 'national';
	case INTERNATIONAL = 'international';

	/**
	 * Returns human-readable French label.
	 *
	 * @return string
	 */
	public function label(): string {
		return match ( $this ) {
			self::DEPARTEMENTAL => 'Départemental',
			self::REGIONAL      => 'Régional',
			self::NATIONAL      => 'National',
			self::INTERNATIONAL => 'International',
		};
	}

	/**
	 * Resolves an enum instance from raw value.
	 *
	 * @param string|null $raw Raw value.
	 * @return self|null
	 */
	public static function from_raw( ?string $raw ): ?self {
		if ( empty( $raw ) ) {
			return null;
		}

		return self::tryFrom( trim( $raw ) );
	}
}
