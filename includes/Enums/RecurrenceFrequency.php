<?php
/**
 * Recurrence Frequency Backed Enum.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Enums;

/**
 * Recurrence Frequency enum.
 */
enum RecurrenceFrequency: string {
	case WEEKLY  = 'weekly';
	case MONTHLY = 'monthly';

	/**
	 * Returns human-readable French label.
	 *
	 * @return string
	 */
	public function label(): string {
		return match ( $this ) {
			self::WEEKLY  => 'Hebdomadaire',
			self::MONTHLY => 'Mensuelle',
		};
	}

	/**
	 * Resolves an enum instance from raw value.
	 *
	 * @param string|null $raw Raw value.
	 * @return self
	 */
	public static function from_raw( ?string $raw ): self {
		if ( empty( $raw ) ) {
			return self::WEEKLY;
		}

		return self::tryFrom( trim( $raw ) ) ?? self::WEEKLY;
	}
}
