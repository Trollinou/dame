<?php
/**
 * Data Provider Service.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Services;

/**
 * Class Data_Provider
 */
class Data_Provider {

	/**
	 * Countries cache.
	 *
	 * @var array<string, string>|null
	 */
	private static $countries = null;

	/**
	 * Regions cache.
	 *
	 * @var array<string, string>|null
	 */
	private static $regions = null;

	/**
	 * Departments cache.
	 *
	 * @var array<int|string, string>|null
	 */
	private static $departments = null;

	/**
	 * Academies cache.
	 *
	 * @var array<string, string>|null
	 */
	private static $academies = null;

	/**
	 * Returns a list of countries.
	 *
	 * @return array<string, string>
	 */
	public static function get_countries(): array {
		if ( null !== self::$countries ) {
			return self::$countries;
		}
		self::$countries = array(
			'FR' => 'France',
			'BE' => 'Belgique',
			'CH' => 'Suisse',
			'LU' => 'Luxembourg',
			'DE' => 'Allemagne',
			'ES' => 'Espagne',
			'IT' => 'Italie',
			'GB' => 'Royaume-Uni',
			'US' => 'États-Unis',
			'CA' => 'Canada',
		);
		return self::$countries;
	}

	/**
	 * Returns a list of French regions.
	 *
	 * @return array<string, string>
	 */
	public static function get_regions(): array {
		if ( null !== self::$regions ) {
			return self::$regions;
		}
		self::$regions = array(
			'NA'   => 'N/A',
			'ARA'  => 'Auvergne-Rhône-Alpes',
			'BFC'  => 'Bourgogne-Franche-Comté',
			'BRE'  => 'Bretagne',
			'CVL'  => 'Centre-Val de Loire',
			'COR'  => 'Corse',
			'GES'  => 'Grand Est',
			'HDF'  => 'Hauts-de-France',
			'IDF'  => 'Île-de-France',
			'NOR'  => 'Normandie',
			'NAQ'  => 'Nouvelle-Aquitaine',
			'OCC'  => 'Occitanie',
			'PDL'  => 'Pays de la Loire',
			'PACA' => 'Provence-Alpes-Côte d\'Azur',
			'GUA'  => 'Guadeloupe',
			'MAR'  => 'Martinique',
			'GUY'  => 'Guyane',
			'REU'  => 'La Réunion',
			'MAY'  => 'Mayotte',
			'NCL'  => 'Nouvelle-Calédonie',
		);
		return self::$regions;
	}

	/**
	 * Returns a list of French departments.
	 *
	 * @return array<int|string, string>
	 */
	public static function get_departments(): array {
		if ( null !== self::$departments ) {
			return self::$departments;
		}
		self::$departments = array(
			'NA'  => 'N/A',
			'01'  => '01 - Ain',
			'02'  => '02 - Aisne',
			'03'  => '03 - Allier',
			'04'  => '04 - Alpes-de-Haute-Provence',
			'05'  => '05 - Hautes-Alpes',
			'06'  => '06 - Alpes-Maritimes',
			'07'  => '07 - Ardèche',
			'08'  => '08 - Ardennes',
			'09'  => '09 - Ariège',
			'10'  => '10 - Aube',
			'11'  => '11 - Aude',
			'12'  => '12 - Aveyron',
			'13'  => '13 - Bouches-du-Rhône',
			'14'  => '14 - Calvados',
			'15'  => '15 - Cantal',
			'16'  => '16 - Charente',
			'17'  => '17 - Charente-Maritime',
			'18'  => '18 - Cher',
			'19'  => '19 - Corrèze',
			'2A'  => '2A - Corse-du-Sud',
			'2B'  => '2B - Haute-Corse',
			'21'  => '21 - Côte-d\'Or',
			'22'  => '22 - Côtes-d\'Armor',
			'23'  => '23 - Creuse',
			'24'  => '24 - Dordogne',
			'25'  => '25 - Doubs',
			'26'  => '26 - Drôme',
			'27'  => '27 - Eure',
			'28'  => '28 - Eure-et-Loir',
			'29'  => '29 - Finistère',
			'30'  => '30 - Gard',
			'31'  => '31 - Haute-Garonne',
			'32'  => '32 - Gers',
			'33'  => '33 - Gironde',
			'34'  => '34 - Hérault',
			'35'  => '35 - Ille-et-Vilaine',
			'36'  => '36 - Indre',
			'37'  => '37 - Indre-et-Loire',
			'38'  => '38 - Isère',
			'39'  => '39 - Jura',
			'40'  => '40 - Landes',
			'41'  => '41 - Loir-et-Cher',
			'42'  => '42 - Loire',
			'43'  => '43 - Haute-Loire',
			'44'  => '44 - Loire-Atlantique',
			'45'  => '45 - Loiret',
			'46'  => '46 - Lot',
			'47'  => '47 - Lot-et-Garonne',
			'48'  => '48 - Lozère',
			'49'  => '49 - Maine-et-Loire',
			'50'  => '50 - Manche',
			'51'  => '51 - Marne',
			'52'  => '52 - Haute-Marne',
			'53'  => '53 - Mayenne',
			'54'  => '54 - Meurthe-et-Moselle',
			'55'  => '55 - Meuse',
			'56'  => '56 - Morbihan',
			'57'  => '57 - Moselle',
			'58'  => '58 - Nièvre',
			'59'  => '59 - Nord',
			'60'  => '60 - Oise',
			'61'  => '61 - Orne',
			'62'  => '62 - Pas-de-Calais',
			'63'  => '63 - Puy-de-Dôme',
			'64'  => '64 - Pyrénées-Atlantiques',
			'65'  => '65 - Hautes-Pyrénées',
			'66'  => '66 - Pyrénées-Orientales',
			'67'  => '67 - Bas-Rhin',
			'68'  => '68 - Haut-Rhin',
			'69'  => '69 - Rhône',
			'70'  => '70 - Haute-Saône',
			'71'  => '71 - Saône-et-Loire',
			'72'  => '72 - Sarthe',
			'73'  => '73 - Savoie',
			'74'  => '74 - Haute-Savoie',
			'75'  => '75 - Paris',
			'76'  => '76 - Seine-Maritime',
			'77'  => '77 - Seine-et-Marne',
			'78'  => '78 - Yvelines',
			'79'  => '79 - Deux-Sèvres',
			'80'  => '80 - Somme',
			'81'  => '81 - Tarn',
			'82'  => '82 - Tarn-et-Garonne',
			'83'  => '83 - Var',
			'84'  => '84 - Vaucluse',
			'85'  => '85 - Vendée',
			'86'  => '86 - Vienne',
			'87'  => '87 - Haute-Vienne',
			'88'  => '88 - Vosges',
			'89'  => '89 - Yonne',
			'90'  => '90 - Territoire de Belfort',
			'91'  => '91 - Essonne',
			'92'  => '92 - Hauts-de-Seine',
			'93'  => '93 - Seine-Saint-Denis',
			'94'  => '94 - Val-de-Marne',
			'95'  => '95 - Val-d\'Oise',
			'971' => '971 - Guadeloupe',
			'972' => '972 - Martinique',
			'973' => '973 - Guyane',
			'974' => '974 - La Réunion',
			'976' => '976 - Mayotte',
			'988' => '988 - Nouvelle-Calédonie',
		);
		return self::$departments;
	}

	/**
	 * Returns a mapping of French departments to their regions.
	 *
	 * @return array<int|string, string>
	 */
	public static function get_department_region_mapping(): array {
		return array(
			'01'  => 'ARA',
			'03'  => 'ARA',
			'07'  => 'ARA',
			'15'  => 'ARA',
			'26'  => 'ARA',
			'38'  => 'ARA',
			'42'  => 'ARA',
			'43'  => 'ARA',
			'63'  => 'ARA',
			'69'  => 'ARA',
			'73'  => 'ARA',
			'74'  => 'ARA',
			'21'  => 'BFC',
			'25'  => 'BFC',
			'39'  => 'BFC',
			'58'  => 'BFC',
			'70'  => 'BFC',
			'71'  => 'BFC',
			'89'  => 'BFC',
			'90'  => 'BFC',
			'22'  => 'BRE',
			'29'  => 'BRE',
			'35'  => 'BRE',
			'56'  => 'BRE',
			'18'  => 'CVL',
			'28'  => 'CVL',
			'36'  => 'CVL',
			'37'  => 'CVL',
			'41'  => 'CVL',
			'45'  => 'CVL',
			'2A'  => 'COR',
			'2B'  => 'COR',
			'08'  => 'GES',
			'10'  => 'GES',
			'51'  => 'GES',
			'52'  => 'GES',
			'54'  => 'GES',
			'55'  => 'GES',
			'57'  => 'GES',
			'67'  => 'GES',
			'68'  => 'GES',
			'88'  => 'GES',
			'02'  => 'HDF',
			'59'  => 'HDF',
			'60'  => 'HDF',
			'62'  => 'HDF',
			'80'  => 'HDF',
			'75'  => 'IDF',
			'77'  => 'IDF',
			'78'  => 'IDF',
			'91'  => 'IDF',
			'92'  => 'IDF',
			'93'  => 'IDF',
			'94'  => 'IDF',
			'95'  => 'IDF',
			'14'  => 'NOR',
			'27'  => 'NOR',
			'50'  => 'NOR',
			'61'  => 'NOR',
			'76'  => 'NOR',
			'16'  => 'NAQ',
			'17'  => 'NAQ',
			'19'  => 'NAQ',
			'23'  => 'NAQ',
			'24'  => 'NAQ',
			'33'  => 'NAQ',
			'40'  => 'NAQ',
			'47'  => 'NAQ',
			'64'  => 'NAQ',
			'79'  => 'NAQ',
			'86'  => 'NAQ',
			'87'  => 'NAQ',
			'09'  => 'OCC',
			'11'  => 'OCC',
			'12'  => 'OCC',
			'30'  => 'OCC',
			'31'  => 'OCC',
			'32'  => 'OCC',
			'34'  => 'OCC',
			'46'  => 'OCC',
			'48'  => 'OCC',
			'65'  => 'OCC',
			'66'  => 'OCC',
			'81'  => 'OCC',
			'82'  => 'OCC',
			'44'  => 'PDL',
			'49'  => 'PDL',
			'53'  => 'PDL',
			'72'  => 'PDL',
			'85'  => 'PDL',
			'04'  => 'PACA',
			'05'  => 'PACA',
			'06'  => 'PACA',
			'13'  => 'PACA',
			'83'  => 'PACA',
			'84'  => 'PACA',
			'971' => 'GUA',
			'972' => 'MAR',
			'973' => 'GUY',
			'974' => 'REU',
			'976' => 'MAY',
			'988' => 'NCL',
		);
	}

	/**
	 * Returns the list of department codes for a given region.
	 *
	 * @param string $region_code The region code.
	 * @return array<int, string> List of department codes.
	 */
	public static function get_departments_by_region( string $region_code ): array {
		$mapping = self::get_department_region_mapping();
		$keys    = array_keys( array_filter( $mapping, fn( $r ) => $r === $region_code ) );
		return array_values( array_map( 'strval', $keys ) );
	}

	/**
	 * Extracts the French department code from a postal code.
	 *
	 * @param string $postal_code The postal code.
	 * @return string|null The 2 or 3 character department code, or null if not resolved.
	 */
	public static function get_department_from_postal_code( string $postal_code ): ?string {
		$postal_code = trim( $postal_code );
		if ( strlen( $postal_code ) < 2 ) {
			return null;
		}

		$dept_code = substr( $postal_code, 0, 2 );
		if ( strlen( $postal_code ) >= 3 ) {
			if ( strpos( $postal_code, '20' ) === 0 ) {
				$dept_code = ( (int) substr( $postal_code, 2, 1 ) <= 1 ) ? '2A' : '2B';
			} elseif ( strpos( $postal_code, '97' ) === 0 || strpos( $postal_code, '988' ) === 0 ) {
				$dept_code = substr( $postal_code, 0, 3 );
			} elseif ( strpos( $postal_code, '980' ) === 0 ) {
				$dept_code = '06';
			}
		}

		$departments = self::get_departments();
		if ( isset( $departments[ $dept_code ] ) ) {
			return (string) $dept_code;
		}

		return null;
	}

	/**
	 * Returns the region code associated with a department code.
	 *
	 * @param string $department_code The department code.
	 * @return string|null The region code, or null if not found.
	 */
	public static function get_region_for_department( string $department_code ): ?string {
		$mapping = self::get_department_region_mapping();
		return $mapping[ $department_code ] ?? null;
	}

	/**
	 * Returns the region code associated with a postal code.
	 *
	 * @param string $postal_code The postal code.
	 * @return string|null The region code, or null if not found.
	 */
	public static function get_region_from_postal_code( string $postal_code ): ?string {
		$dept_code = self::get_department_from_postal_code( $postal_code );
		if ( ! $dept_code ) {
			return null;
		}
		return self::get_region_for_department( $dept_code );
	}

	/**
	 * Returns a list of French school academies.
	 *
	 * @return array<string, string>
	 */
	public static function get_academies(): array {
		if ( null !== self::$academies ) {
			return self::$academies;
		}
		self::$academies = array(
			'NA'               => 'N/A',
			'aix-marseille'    => 'Aix-Marseille',
			'amiens'           => 'Amiens',
			'besancon'         => 'Besançon',
			'bordeaux'         => 'Bordeaux',
			'clermont-ferrand' => 'Clermont-Ferrand',
			'corse'            => 'Corse',
			'creteil'          => 'Créteil',
			'dijon'            => 'Dijon',
			'grenoble'         => 'Grenoble',
			'guadeloupe'       => 'Guadeloupe',
			'guyane'           => 'Guyane',
			'lille'            => 'Lille',
			'limoges'          => 'Limoges',
			'lyon'             => 'Lyon',
			'martinique'       => 'Martinique',
			'mayotte'          => 'Mayotte',
			'montpellier'      => 'Montpellier',
			'nancy-metz'       => 'Nancy-Metz',
			'nantes'           => 'Nantes',
			'nice'             => 'Nice',
			'normandie'        => 'Normandie',
			'orleans-tours'    => 'Orléans-Tours',
			'paris'            => 'Paris',
			'poitiers'         => 'Poitiers',
			'reims'            => 'Reims',
			'rennes'           => 'Rennes',
			'reunion'          => 'La Réunion',
			'strasbourg'       => 'Strasbourg',
			'toulouse'         => 'Toulouse',
			'versailles'       => 'Versailles',
		);
		return self::$academies;
	}

	/**
	 * Returns the options for the health document status.
	 *
	 * @return array<string, string>
	 */
	public static function get_health_document_options(): array {
		return array(
			'none'        => __( 'Non renseigné', 'dame' ),
			'attestation' => __( 'Attestation signée', 'dame' ),
			'certificate' => __( 'Certificat médical', 'dame' ),
		);
	}

	/**
	 * Returns a list of clothing sizes.
	 *
	 * @return array<int, string>
	 */
	public static function get_clothing_sizes(): array {
		return array(
			'Non renseigné',
			'8/10',
			'10/12',
			'12/14',
			'XS',
			'S',
			'M',
			'L',
			'XL',
			'XXL',
			'XXXL',
		);
	}

	/**
	 * Retrieves all valid email addresses for a given adherent.
	 *
	 * This includes the adherent's own email (if communication is accepted)
	 * and the emails of legal representatives.
	 *
	 * @param int $adherent_id The ID of the adherent post.
	 * @return array<int, string> List of unique email addresses.
	 */
	public static function get_emails_for_adherent( $adherent_id ): array {
		$emails = array();

		// Adherent's own email.
		$adherent_email = get_post_meta( $adherent_id, '_dame_email', true );
		// Note: The logic is inverted compared to previous version. '1' means refusal.
		// So we accept communication if NOT refused (empty or '0').
		$refuses_comms = get_post_meta( $adherent_id, '_dame_email_refuses_comms', true );

		if ( ! empty( $adherent_email ) && is_email( $adherent_email ) && '1' !== $refuses_comms ) {
			$emails[] = $adherent_email;
		}

		// Legal Representative 1.
		$rep1_email   = get_post_meta( $adherent_id, '_dame_legal_rep_1_email', true );
		$rep1_refuses = get_post_meta( $adherent_id, '_dame_legal_rep_1_email_refuses_comms', true );
		if ( ! empty( $rep1_email ) && is_email( $rep1_email ) && '1' !== $rep1_refuses ) {
			$emails[] = $rep1_email;
		}

		// Legal Representative 2.
		$rep2_email   = get_post_meta( $adherent_id, '_dame_legal_rep_2_email', true );
		$rep2_refuses = get_post_meta( $adherent_id, '_dame_legal_rep_2_email_refuses_comms', true );
		if ( ! empty( $rep2_email ) && is_email( $rep2_email ) && '1' !== $rep2_refuses ) {
			$emails[] = $rep2_email;
		}

		return array_unique( $emails );
	}

	/**
	 * Retrieves the valid email address for a given contact.
	 *
	 * @param int $contact_id The ID of the contact post.
	 * @return array<int, string> List containing the email if accepted.
	 */
	public static function get_emails_for_contact( $contact_id ): array {
		$emails  = array();
		$email   = get_post_meta( $contact_id, '_dame_contact_email', true );
		$refuses = get_post_meta( $contact_id, '_dame_contact_no_emails', true );

		if ( ! empty( $email ) && is_email( (string) $email ) && '1' !== $refuses ) {
			$emails[] = (string) $email;
		}

		return $emails;
	}

	/**
	 * Default pricing configuration.
	 *
	 * @var array<string, float>
	 */
	public const DEFAULT_PRICING = array(
		'price_licence_a'       => 140.0,
		'price_licence_b'       => 70.0,
		'discount_female_a'     => 10.0,
		'surcharge_first_reg_a' => 30.0,
	);

	/**
	 * Retrieves the pricing configuration for a given season.
	 *
	 * @param int $season_id The term ID of the season.
	 * @return array{price_licence_a: float, price_licence_b: float, discount_female_a: float, surcharge_first_reg_a: float}
	 */
	public static function get_season_pricing( int $season_id ): array {
		if ( $season_id <= 0 ) {
			return self::DEFAULT_PRICING;
		}

		$options = get_option( 'dame_season_pricing_' . $season_id, null );
		if ( ! is_array( $options ) ) {
			return self::DEFAULT_PRICING;
		}

		return array(
			'price_licence_a'       => isset( $options['price_licence_a'] ) ? (float) $options['price_licence_a'] : 140.0,
			'price_licence_b'       => isset( $options['price_licence_b'] ) ? (float) $options['price_licence_b'] : 70.0,
			'discount_female_a'     => isset( $options['discount_female_a'] ) ? (float) $options['discount_female_a'] : 10.0,
			'surcharge_first_reg_a' => isset( $options['surcharge_first_reg_a'] ) ? (float) $options['surcharge_first_reg_a'] : 30.0,
		);
	}

	/**
	 * Saves the pricing configuration for a given season.
	 *
	 * @param int                  $season_id The term ID of the season.
	 * @param array<string, mixed> $pricing   Pricing data array.
	 */
	public static function save_season_pricing( int $season_id, array $pricing ): void {
		if ( $season_id <= 0 ) {
			return;
		}

		$clean = array(
			'price_licence_a'       => isset( $pricing['price_licence_a'] ) ? max( 0.0, (float) $pricing['price_licence_a'] ) : 140.0,
			'price_licence_b'       => isset( $pricing['price_licence_b'] ) ? max( 0.0, (float) $pricing['price_licence_b'] ) : 70.0,
			'discount_female_a'     => isset( $pricing['discount_female_a'] ) ? max( 0.0, (float) $pricing['discount_female_a'] ) : 10.0,
			'surcharge_first_reg_a' => isset( $pricing['surcharge_first_reg_a'] ) ? max( 0.0, (float) $pricing['surcharge_first_reg_a'] ) : 30.0,
		);

		update_option( 'dame_season_pricing_' . $season_id, $clean, false );
	}

	/**
	 * Determines whether an adherent is renewing their membership for the target season.
	 *
	 * An adherent is considered a renewal if they have historical membership terms
	 * strictly prior to the target season.
	 *
	 * @param int $adherent_id      The post ID of the adherent.
	 * @param int $target_season_id The term ID of the target season.
	 * @return bool True if renewal, false if first registration.
	 */
	public static function is_adherent_renewal( int $adherent_id, int $target_season_id ): bool {
		$target_term = get_term( $target_season_id, 'dame_saison_adhesion' );
		$target_year = 0;
		if ( $target_term && ! is_wp_error( $target_term ) ) {
			if ( preg_match( '/(\d{4})/', $target_term->name, $matches ) ) {
				$target_year = (int) $matches[1];
			}
		}

		$terms = wp_get_post_terms( $adherent_id, 'dame_saison_adhesion' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return false;
		}

		foreach ( $terms as $term ) {
			if ( (int) $term->term_id === $target_season_id ) {
				continue;
			}

			if ( $target_year > 0 ) {
				if ( preg_match( '/(\d{4})/', $term->name, $matches ) ) {
					$prev_year = (int) $matches[1];
					if ( $prev_year < $target_year ) {
						return true;
					}
				}
			} else {
				// Fallback if no year pattern detected: any other existing term indicates a prior membership.
				return true;
			}
		}

		return false;
	}

	/**
	 * Calculates the membership fee for an adherent for a given season.
	 *
	 * Formula:
	 * - Licence B: price_licence_b
	 * - Licence A: price_licence_a
	 *   - discount_female_a (if female)
	 *   + surcharge_first_reg_a (if 1st registration / not renewal)
	 *
	 * @param int         $adherent_id   The adherent post ID.
	 * @param int         $season_id     The season term ID.
	 * @param string|null $force_status  Optional manual status override ('renewal' / 'first_registration').
	 * @param string|null $force_license Optional manual license type override ('A' / 'B').
	 * @param string|null $force_gender  Optional manual gender override ('Masculin' / 'Féminin').
	 * @return float Total fee in euros.
	 */
	public static function calculate_adherent_fee(
		int $adherent_id,
		int $season_id,
		?string $force_status = null,
		?string $force_license = null,
		?string $force_gender = null
	): float {
		$pricing = self::get_season_pricing( $season_id );

		$license = $force_license ?? (string) get_post_meta( $adherent_id, '_dame_license_type', true );
		$license = strtoupper( trim( $license ) );
		if ( empty( $license ) || 'NON PRÉCISÉ' === $license ) {
			$license = 'A';
		}

		$gender = $force_gender ?? (string) get_post_meta( $adherent_id, '_dame_sexe', true );

		if ( null !== $force_status && '' !== $force_status ) {
			$status_lower = strtolower( trim( $force_status ) );
			$is_renewal   = in_array( $status_lower, array( 'renewal', 'renouvellement', 'actif' ), true );
		} else {
			$is_renewal = self::is_adherent_renewal( $adherent_id, $season_id );
		}

		if ( 'B' === $license ) {
			return round( $pricing['price_licence_b'], 2 );
		}

		$total = $pricing['price_licence_a'];

		if ( 'Féminin' === $gender || 'Feminin' === $gender ) {
			$total -= $pricing['discount_female_a'];
		}

		if ( ! $is_renewal ) {
			$total += $pricing['surcharge_first_reg_a'];
		}

		return max( 0.0, round( $total, 2 ) );
	}

	/**
	 * Retrieves the attestation email subject and body template with defaults.
	 *
	 * @return array{subject: string, body: string}
	 */
	public static function get_attestation_email_template(): array {
		$options = get_option( 'dame_options', array() );

		$default_subject = __( 'Votre attestation d\'adhésion et de paiement - {saison}', 'dame' );
		$default_body    = "Bonjour {prenom},\n\nNous vous prions de trouver ci-joint votre attestation d'adhésion et de paiement pour la {saison} au sein de l'association {association}.\n\nCe document atteste du règlement d'un montant de {montant} effectué le {date_paiement} par {mode_paiement}.\nIl peut être transmis à votre comité d'entreprise (CSE), mutuelle ou employeur pour faire valoir vos droits de participation ou de remboursement.\n\nBien cordialement,\nL'équipe de l'association {association}\n{site_web}";

		$subject = ! empty( $options['attestation_email_subject'] ) ? (string) $options['attestation_email_subject'] : $default_subject;
		$body    = ! empty( $options['attestation_email_body'] ) ? (string) $options['attestation_email_body'] : $default_body;

		return array(
			'subject' => $subject,
			'body'    => $body,
		);
	}
}
