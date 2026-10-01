<?php
/**
 * Health Document Status Backed Enum.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Enums;

/**
 * Health Document Status enum.
 */
enum HealthDocumentStatus: string {
	case NONE        = 'none';
	case ATTESTATION = 'attestation';
	case CERTIFICATE = 'certificate';

	/**
	 * Returns human-readable French label.
	 *
	 * @return string
	 */
	public function label(): string {
		return match ( $this ) {
			self::NONE        => 'Non renseigné',
			self::ATTESTATION => 'Attestation signée',
			self::CERTIFICATE => 'Certificat médical',
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
