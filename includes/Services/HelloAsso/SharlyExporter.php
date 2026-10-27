<?php
/**
 * Sharly Chess CSV Exporter.
 *
 * @package DAME\Services\HelloAsso
 */

declare(strict_types=1);

namespace DAME\Services\HelloAsso;

use DAME\Core\Utils;

/**
 * Class SharlyExporter
 */
class SharlyExporter {

	/**
	 * Generates CSV content formatted for Sharly Chess / Papi.
	 *
	 * Columns:
	 * Nom;Prenom;Sexe;DateNaissance;CodeFIDE;CodeFFE;Elo;Club;Categorie;Tarif;Statut
	 *
	 * @param array<int, array<string, mixed>> $items Array of items from TicketingRepository.
	 * @return string CSV content formatted with UTF-8 BOM.
	 */
	public static function generate_csv( array $items ): string {
		$output = fopen( 'php://temp', 'r+' );
		if ( false === $output ) {
			return '';
		}

		// UTF-8 BOM for Microsoft Excel / French Windows compatibility.
		fwrite( $output, "\xEF\xBB\xBF" );

		// Header.
		fputcsv(
			$output,
			array(
				'Nom',
				'Prenom',
				'Sexe',
				'Date_Naissance',
				'Code_FFE',
				'Code_FIDE',
				'Elo',
				'Club',
				'Categorie',
				'Tarif',
				'Pointe_Present',
			),
			';'
		);

		foreach ( $items as $item ) {
			$last_name   = Utils::format_lastname( (string) ( $item['participant_last_name'] ?? '' ) );
			$first_name  = Utils::format_firstname( (string) ( $item['participant_first_name'] ?? '' ) );
			$birth_date  = (string) ( $item['participant_birth_date'] ?? '' );
			$license_ffe = (string) ( $item['license_number'] ?? '' );
			$code_fide   = (string) ( $item['fide_id'] ?? '' );
			$elo         = isset( $item['elo'] ) && is_numeric( $item['elo'] ) && (int) $item['elo'] > 0 ? (string) $item['elo'] : '';
			$club        = (string) ( $item['club_name'] ?? '' );
			$tier        = (string) ( $item['tier_name'] ?? '' );
			$checked_in  = ! empty( $item['checked_in'] ) ? 'OUI' : 'NON';

			// Gender and Category from matched adherent if available.
			$sexe     = 'M';
			$category = '';
			$adh_id   = (int) ( $item['matched_adherent_id'] ?? 0 );
			if ( $adh_id > 0 ) {
				$saved_sexe = (string) get_post_meta( $adh_id, '_dame_sexe', true );
				if ( 'Féminin' === $saved_sexe || 'F' === $saved_sexe ) {
					$sexe = 'F';
				}
				if ( ! empty( $birth_date ) ) {
					$category = Utils::get_adherent_age_category( $birth_date, $saved_sexe );
				}
			}

			// Format birth date in DD/MM/YYYY if provided as YYYY-MM-DD.
			$formatted_birth = $birth_date;
			if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $birth_date, $m ) ) {
				$formatted_birth = "{$m[3]}/{$m[2]}/{$m[1]}";
			}

			fputcsv(
				$output,
				array(
					$last_name,
					$first_name,
					$sexe,
					$formatted_birth,
					$license_ffe,
					$code_fide,
					$elo,
					$club,
					$category,
					$tier,
					$checked_in,
				),
				';'
			);
		}

		rewind( $output );
		$csv_string = stream_get_contents( $output );
		fclose( $output );

		return is_string( $csv_string ) ? $csv_string : '';
	}
}
