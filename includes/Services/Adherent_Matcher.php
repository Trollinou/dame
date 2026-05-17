<?php
/**
 * Adherent Matcher Service.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Services;

use WP_Query;

/**
 * Class Adherent_Matcher
 */
class Adherent_Matcher {

	/**
	 * Finds an existing adherent that matches the pre-inscription details.
	 *
	 * @param int $pre_inscription_id The pre-inscription post ID.
	 * @return int The matched adherent ID, or 0 if no match found.
	 */
	public static function find_match( $pre_inscription_id ) {
		$first_name = get_post_meta( $pre_inscription_id, '_dame_first_name', true );
		$last_name  = get_post_meta( $pre_inscription_id, '_dame_last_name', true );
		$birth_name = get_post_meta( $pre_inscription_id, '_dame_birth_name', true );
		$birth_date = get_post_meta( $pre_inscription_id, '_dame_birth_date', true );

		$name_to_match     = ! empty( $birth_name ) ? $birth_name : $last_name;
		$name_key_to_match = ! empty( $birth_name ) ? '_dame_birth_name' : '_dame_last_name';

		if ( $first_name && $name_to_match && $birth_date ) {
			$query = new WP_Query(
				array(
					'post_type'      => 'adherent',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'     => '_dame_first_name',
							'value'   => $first_name,
							'compare' => '=',
						),
						array(
							'key'     => $name_key_to_match,
							'value'   => $name_to_match,
							'compare' => '=',
						),
						array(
							'key'     => '_dame_birth_date',
							'value'   => $birth_date,
							'compare' => '=',
						),
					),
					'fields'         => 'ids',
				)
			);

			if ( $query->have_posts() ) {
				return (int) $query->posts[0];
			}
		}

		return 0;
	}

	/**
	 * Checks if an email is associated with an adherent or a legal representative.
	 *
	 * @param string $email The email to check.
	 * @return int The adherent ID if found, 0 otherwise.
	 */
	public static function check_if_email_is_member( string $email ): int {
		if ( ! is_email( $email ) ) {
			return 0;
		}

		$query = new WP_Query(
			[
				'post_type'      => 'adherent',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_query'     => [
					'relation' => 'OR',
					[
						'key'     => '_dame_email',
						'value'   => $email,
						'compare' => '=',
					],
					[
						'key'     => '_dame_legal_rep_1_email',
						'value'   => $email,
						'compare' => '=',
					],
					[
						'key'     => '_dame_legal_rep_2_email',
						'value'   => $email,
						'compare' => '=',
					],
				],
				'fields'         => 'ids',
			]
		);

		if ( $query->have_posts() ) {
			return (int) $query->posts[0];
		}

		return 0;
	}
}
