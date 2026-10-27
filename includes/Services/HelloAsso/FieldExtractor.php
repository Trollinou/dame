<?php
/**
 * HelloAsso Custom Fields and Chess Data Extractor.
 *
 * @package DAME\Services\HelloAsso
 */

declare(strict_types=1);

namespace DAME\Services\HelloAsso;

use DAME\Core\Utils;

/**
 * Class FieldExtractor
 */
class FieldExtractor {

	/**
	 * Parses custom fields from an item or order and maps them to chess-specific fields.
	 *
	 * @param array<int, array{name?: string, value?: mixed, answer?: mixed}>|mixed $custom_fields Raw fields from HelloAsso API/Webhook.
	 * @return array{
	 *     license_number: string,
	 *     fide_id: string,
	 *     elo: int|null,
	 *     club_name: string,
	 *     phone: string,
	 *     birth_date: string|null,
	 *     extracted_fields: array<string, string>
	 * }
	 */
	public static function extract_chess_data( mixed $custom_fields ): array {
		$result = array(
			'license_number'   => '',
			'fide_id'          => '',
			'elo'              => null,
			'club_name'        => '',
			'phone'            => '',
			'birth_date'       => null,
			'extracted_fields' => array(),
		);

		if ( ! is_array( $custom_fields ) || empty( $custom_fields ) ) {
			return $result;
		}

		foreach ( $custom_fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$raw_name = (string) ( $field['name'] ?? '' );
			$raw_val  = (string) ( $field['answer'] ?? ( $field['value'] ?? '' ) );
			$val      = trim( $raw_val );

			if ( empty( $raw_name ) || empty( $val ) ) {
				continue;
			}

			$result['extracted_fields'][ $raw_name ] = $val;
			$lower_name                              = mb_strtolower( $raw_name, 'UTF-8' );

			// 1. License FFE / FIDE.
			if ( empty( $result['license_number'] ) && ( str_contains( $lower_name, 'licence' ) || str_contains( $lower_name, 'ffe' ) ) ) {
				$extracted = Utils::extract_license_numbers( $val );
				if ( ! empty( $extracted ) ) {
					$result['license_number'] = $extracted[0];
				} else {
					$result['license_number'] = strtoupper( str_replace( ' ', '', $val ) );
				}
			} elseif ( empty( $result['fide_id'] ) && str_contains( $lower_name, 'fide' ) ) {
				if ( preg_match( '/\b([0-9]{5,9})\b/', $val, $matches ) ) {
					$result['fide_id'] = $matches[1];
				} else {
					$result['fide_id'] = $val;
				}
			}

			// 2. Club.
			if ( empty( $result['club_name'] ) && str_contains( $lower_name, 'club' ) ) {
				$result['club_name'] = $val;
			}

			// 3. Elo.
			if ( null === $result['elo'] && str_contains( $lower_name, 'elo' ) ) {
				if ( preg_match( '/\b([1-3][0-9]{3})\b/', $val, $matches ) ) {
					$result['elo'] = (int) $matches[1];
				}
			}

			// 4. Phone.
			if ( empty( $result['phone'] ) && ( str_contains( $lower_name, 'téléphone' ) || str_contains( $lower_name, 'telephone' ) || str_contains( $lower_name, 'portable' ) || str_contains( $lower_name, 'tel' ) ) ) {
				$result['phone'] = $val;
			}

			// 5. Birth date.
			if ( null === $result['birth_date'] && ( str_contains( $lower_name, 'naissance' ) || str_contains( $lower_name, 'birth' ) ) ) {
				// Match format DD/MM/YYYY or YYYY-MM-DD.
				if ( preg_match( '/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $val, $m ) ) {
					$result['birth_date'] = sprintf( '%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1] );
				} elseif ( preg_match( '/(\d{4})-(\d{1,2})-(\d{1,2})/', $val, $m ) ) {
					$result['birth_date'] = sprintf( '%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3] );
				}
			}
		}

		return $result;
	}
}
