<?php
/**
 * HelloAsso Inscription Matcher with Adherents, PreInscriptions and Contacts.
 *
 * @package DAME\Services\HelloAsso
 */

declare(strict_types=1);

namespace DAME\Services\HelloAsso;

use WP_Query;
use DAME\Core\Utils;
use DAME\Services\Backup;

/**
 * Class Matcher
 */
class Matcher {

	/**
	 * Matches an inscription with existing club members, pre-inscriptions and external contacts.
	 *
	 * @param array{
	 *     first_name: string,
	 *     last_name: string,
	 *     email: string,
	 *     payer_email: string,
	 *     license: string,
	 *     birth_date?: string|null
	 * } $participant_data Participant details.
	 * @return array{
	 *     matched_adherent_id: int,
	 *     matched_pre_inscription_id: int,
	 *     matched_contact_id: int,
	 *     enriched_data: array<string, mixed>
	 * }
	 */
	public static function match_and_enrich( array $participant_data ): array {
		$result = array(
			'matched_adherent_id'        => 0,
			'matched_pre_inscription_id' => 0,
			'matched_contact_id'         => 0,
			'enriched_data'              => array(),
		);

		$first_name = trim( $participant_data['first_name'] );
		$last_name  = trim( $participant_data['last_name'] );
		$email      = mb_strtolower( trim( $participant_data['email'] ) );
		$payer_mail = mb_strtolower( trim( $participant_data['payer_email'] ) );
		$license    = strtoupper( str_replace( ' ', '', trim( $participant_data['license'] ) ) );

		// 1. MATCH ADHÉRENT (Prioritaire).
		$adherent_id = self::find_matching_adherent( $first_name, $last_name, $email, $payer_mail, $license );
		if ( $adherent_id > 0 ) {
			$result['matched_adherent_id'] = $adherent_id;
			$result['enriched_data']       = self::get_adherent_enrichment( $adherent_id );
			return $result;
		}

		// 2. MATCH PRÉ-INSCRIPTION.
		$pre_id = self::find_matching_pre_inscription( $first_name, $last_name, $email, $payer_mail );
		if ( $pre_id > 0 ) {
			$result['matched_pre_inscription_id'] = $pre_id;
			return $result;
		}

		// 3. MATCH CONTACT EXISTANT.
		$contact_id = self::find_matching_contact( $email, $payer_mail, $last_name, $first_name );
		if ( $contact_id > 0 ) {
			$result['matched_contact_id'] = $contact_id;
		}

		return $result;
	}

	/**
	 * Finds an adherent by license number, normalized name, or email.
	 *
	 * @param string $first_name First name.
	 * @param string $last_name  Last name.
	 * @param string $email      Participant email.
	 * @param string $payer_mail Payer email.
	 * @param string $license    Clean license code.
	 * @return int Adherent post ID or 0.
	 */
	private static function find_matching_adherent( string $first_name, string $last_name, string $email, string $payer_mail, string $license ): int {
		$index = Backup::get_adherents_matching_index();

		// Check by clean license number.
		if ( ! empty( $license ) && isset( $index['licenses'][ $license ] ) ) {
			return $index['licenses'][ $license ]['id'];
		}

		// Check by normalized full name.
		if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
			$candidate_1 = Utils::normalize_name( $last_name . $first_name );
			$candidate_2 = Utils::normalize_name( $first_name . $last_name );

			if ( isset( $index['names'][ $candidate_1 ] ) ) {
				return $index['names'][ $candidate_1 ]['id'];
			}
			if ( isset( $index['names'][ $candidate_2 ] ) ) {
				return $index['names'][ $candidate_2 ]['id'];
			}
		}

		// Check by participant email.
		if ( ! empty( $email ) && isset( $index['emails'][ $email ] ) ) {
			return $index['emails'][ $email ]['id'];
		}

		// Check by payer email.
		if ( ! empty( $payer_mail ) && isset( $index['emails'][ $payer_mail ] ) ) {
			return $index['emails'][ $payer_mail ]['id'];
		}

		return 0;
	}

	/**
	 * Extracts official club adherent metadata to enrich tournament entry.
	 *
	 * @param int $adherent_id Adherent ID.
	 * @return array<string, mixed>
	 */
	private static function get_adherent_enrichment( int $adherent_id ): array {
		$options   = get_option( 'dame_options', array() );
		$club_name = ! empty( $options['assoc_name'] ) ? (string) $options['assoc_name'] : __( 'Club', 'dame' );

		$license = (string) get_post_meta( $adherent_id, '_dame_license_number', true );
		$fide_id = (string) get_post_meta( $adherent_id, '_dame_fide_id', true );
		$birth   = (string) get_post_meta( $adherent_id, '_dame_birth_date', true );
		$phone   = (string) get_post_meta( $adherent_id, '_dame_phone_number', true );
		$elo_rap = get_post_meta( $adherent_id, '_dame_elo_rapide', true );
		$elo_std = get_post_meta( $adherent_id, '_dame_elo_standard', true );

		$elo = null;
		if ( ! empty( $elo_rap ) && is_numeric( $elo_rap ) && (int) $elo_rap > 0 ) {
			$elo = (int) $elo_rap;
		} elseif ( ! empty( $elo_std ) && is_numeric( $elo_std ) && (int) $elo_std > 0 ) {
			$elo = (int) $elo_std;
		}

		return array(
			'license_number'         => ! empty( $license ) ? $license : null,
			'fide_id'                => ! empty( $fide_id ) ? $fide_id : null,
			'club_name'              => $club_name,
			'elo'                    => $elo,
			'participant_birth_date' => ! empty( $birth ) ? $birth : null,
			'phone'                  => ! empty( $phone ) ? $phone : null,
		);
	}

	/**
	 * Finds a matching pending pre-inscription.
	 *
	 * @param string $first_name First name.
	 * @param string $last_name  Last name.
	 * @param string $email      Participant email.
	 * @param string $payer_mail Payer email.
	 * @return int Pre-inscription ID or 0.
	 */
	private static function find_matching_pre_inscription( string $first_name, string $last_name, string $email, string $payer_mail ): int {
		if ( empty( $first_name ) && empty( $last_name ) && empty( $email ) ) {
			return 0;
		}

		$meta_query = array( 'relation' => 'OR' );

		if ( ! empty( $email ) ) {
			$meta_query[] = array(
				'key'     => '_dame_email',
				'value'   => $email,
				'compare' => '=',
			);
		}
		if ( ! empty( $payer_mail ) && $payer_mail !== $email ) {
			$meta_query[] = array(
				'key'     => '_dame_email',
				'value'   => $payer_mail,
				'compare' => '=',
			);
		}

		if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
			$meta_query[] = array(
				'relation' => 'AND',
				array(
					'key'     => '_dame_first_name',
					'value'   => $first_name,
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_dame_last_name',
					'value'   => $last_name,
					'compare' => 'LIKE',
				),
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'dame_pre_inscription',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => $meta_query,
			)
		);

		if ( ! empty( $query->posts ) ) {
			$first = $query->posts[0];
			return is_numeric( $first ) ? (int) $first : ( $first instanceof \WP_Post ? $first->ID : 0 );
		}

		return 0;
	}

	/**
	 * Finds an existing contact by email or name.
	 *
	 * @param string $email      Email.
	 * @param string $payer_mail Payer email.
	 * @param string $last_name  Last name.
	 * @param string $first_name First name.
	 * @return int Contact post ID or 0.
	 */
	private static function find_matching_contact( string $email, string $payer_mail, string $last_name, string $first_name ): int {
		global $wpdb;

		$check_email = ! empty( $email ) ? $email : $payer_mail;
		if ( ! empty( $check_email ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT p.ID 
					 FROM {$wpdb->posts} p
					 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
					 WHERE p.post_type = 'dame_contact'
					   AND p.post_status != 'trash'
					   AND pm.meta_key = '_dame_contact_email'
					   AND LOWER(pm.meta_value) = %s
					 LIMIT 1",
					$check_email
				)
			);
			if ( $id ) {
				return (int) $id;
			}
		}

		if ( ! empty( $last_name ) && ! empty( $first_name ) ) {
			$title = trim( Utils::format_lastname( $last_name ) . ' ' . Utils::format_firstname( $first_name ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} 
					 WHERE post_type = 'dame_contact' 
					   AND post_status != 'trash' 
					   AND post_title LIKE %s 
					 LIMIT 1",
					'%' . $wpdb->esc_like( $title ) . '%'
				)
			);
			if ( $id ) {
				return (int) $id;
			}
		}

		return 0;
	}
}
