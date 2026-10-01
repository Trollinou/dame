<?php
/**
 * Recipient Resolver for Mailing.
 *
 * @package DAME\Admin\Pages\Mailing
 */

declare(strict_types=1);

namespace DAME\Admin\Pages\Mailing;

use DAME\Core\Utils;

/**
 * Resolves, filters, deduplicates and registers message recipients.
 */
class RecipientResolver {

	/**
	 * Resolves recipient IDs based on form submission.
	 *
	 * @param array<string, mixed> $post_data Submitted form data.
	 * @return array{
	 *     adherent_ids: array<int>,
	 *     contact_ids: array<int>,
	 *     adherent_criteria_selected: bool,
	 *     contact_criteria_selected: bool,
	 *     meta: array<string, mixed>
	 * }
	 */
	public function resolve_target_ids( array $post_data ): array {
		$adherent_method = isset( $post_data['dame_adherent_method'] ) ? sanitize_key( (string) $post_data['dame_adherent_method'] ) : 'group';
		$contact_method  = isset( $post_data['dame_contact_method'] ) ? sanitize_key( (string) $post_data['dame_contact_method'] ) : 'group';

		$adherent_ids               = array();
		$contact_ids                = array();
		$adherent_criteria_selected = false;
		$contact_criteria_selected  = false;

		$meta = array(
			'seasons'           => array(),
			'groups_saisonnier' => array(),
			'groups_permanent'  => array(),
			'gender'            => 'all',
			'contact_types'     => array(),
			'depts'             => array(),
			'regions'           => array(),
			'manual_recipients' => array(),
			'manual_contacts'   => array(),
		);

		// A. Adhérents.
		if ( 'manual' === $adherent_method ) {
			if ( ! empty( $post_data['dame_manual_recipients'] ) && is_array( $post_data['dame_manual_recipients'] ) ) {
				$adherent_ids               = array_map( 'absint', $post_data['dame_manual_recipients'] );
				$meta['manual_recipients']  = $adherent_ids;
				$adherent_criteria_selected = true;
			}
		} else {
			$seasons           = isset( $post_data['dame_recipient_seasons'] ) ? array_map( 'absint', (array) $post_data['dame_recipient_seasons'] ) : array();
			$groups_saisonnier = isset( $post_data['dame_recipient_groups_saisonnier'] ) ? array_map( 'absint', (array) $post_data['dame_recipient_groups_saisonnier'] ) : array();
			$groups_permanent  = isset( $post_data['dame_recipient_groups_permanent'] ) ? array_map( 'absint', (array) $post_data['dame_recipient_groups_permanent'] ) : array();
			$gender            = isset( $post_data['dame_recipient_gender'] ) ? sanitize_text_field( (string) $post_data['dame_recipient_gender'] ) : 'all';

			$meta['seasons']           = $seasons;
			$meta['groups_saisonnier'] = $groups_saisonnier;
			$meta['groups_permanent']  = $groups_permanent;
			$meta['gender']            = $gender;

			if ( ! empty( $seasons ) || ! empty( $groups_saisonnier ) || ! empty( $groups_permanent ) || 'all' !== $gender ) {
				$adherent_criteria_selected = true;
				$args                       = array(
					'post_type'      => 'adherent',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => array( 'relation' => 'OR' ),
				);

				if ( ! empty( $seasons ) ) {
					$args['tax_query'][] = array(
						'taxonomy' => 'dame_saison_adhesion',
						'field'    => 'term_id',
						'terms'    => $seasons,
					);
				}

				$all_groups = array_merge( $groups_saisonnier, $groups_permanent );
				if ( ! empty( $all_groups ) ) {
					$args['tax_query'][] = array(
						'taxonomy' => 'dame_group',
						'field'    => 'term_id',
						'terms'    => $all_groups,
					);
				}

				if ( 'all' !== $gender ) {
					$args['meta_query'] = array(
						array(
							'key'   => '_dame_sexe',
							'value' => $gender,
						),
					);
				}
				$adherent_ids = get_posts( $args );
			}
		}

		// B. Contacts.
		if ( 'manual' === $contact_method ) {
			if ( ! empty( $post_data['dame_manual_contacts'] ) && is_array( $post_data['dame_manual_contacts'] ) ) {
				$contact_ids               = array_map( 'absint', $post_data['dame_manual_contacts'] );
				$meta['manual_contacts']   = $contact_ids;
				$contact_criteria_selected = true;
			}
		} else {
			$contact_types = isset( $post_data['dame_recipient_contact_types'] ) ? array_map( 'absint', (array) $post_data['dame_recipient_contact_types'] ) : array();
			$depts         = isset( $post_data['dame_contact_depts'] ) && is_array( $post_data['dame_contact_depts'] ) ? array_map( 'sanitize_text_field', $post_data['dame_contact_depts'] ) : array();
			$regions       = isset( $post_data['dame_contact_regions'] ) && is_array( $post_data['dame_contact_regions'] ) ? array_map( 'sanitize_text_field', $post_data['dame_contact_regions'] ) : array();

			$meta['contact_types'] = $contact_types;
			$meta['depts']         = $depts;
			$meta['regions']       = $regions;

			$has_types = ! empty( $contact_types );
			$has_depts = ! empty( $depts );

			if ( $has_types || $has_depts ) {
				$contact_criteria_selected = true;
				if ( $has_types && $has_depts ) {
					$contact_ids = get_posts(
						array(
							'post_type'      => 'dame_contact',
							'posts_per_page' => -1,
							'fields'         => 'ids',
							'tax_query'      => array(
								array(
									'taxonomy' => 'dame_contact_type',
									'field'    => 'term_id',
									'terms'    => $contact_types,
								),
							),
							'meta_query'     => array(
								array(
									'key'     => '_dame_contact_department',
									'value'   => $depts,
									'compare' => 'IN',
								),
							),
						)
					);
				} elseif ( $has_types ) {
					$contact_ids = get_posts(
						array(
							'post_type'      => 'dame_contact',
							'posts_per_page' => -1,
							'fields'         => 'ids',
							'tax_query'      => array(
								array(
									'taxonomy' => 'dame_contact_type',
									'field'    => 'term_id',
									'terms'    => $contact_types,
								),
							),
						)
					);
				} elseif ( $has_depts ) {
					$contact_ids = get_posts(
						array(
							'post_type'      => 'dame_contact',
							'posts_per_page' => -1,
							'fields'         => 'ids',
							'meta_query'     => array(
								array(
									'key'     => '_dame_contact_department',
									'value'   => $depts,
									'compare' => 'IN',
								),
							),
						)
					);
				}
			}
		}

		return array(
			'adherent_ids'               => array_map( 'intval', $adherent_ids ),
			'contact_ids'                => array_map( 'intval', $contact_ids ),
			'adherent_criteria_selected' => $adherent_criteria_selected,
			'contact_criteria_selected'  => $contact_criteria_selected,
			'meta'                       => $meta,
		);
	}

	/**
	 * Filters out recipients who already received this message.
	 *
	 * @param array<int> $ids List of post IDs.
	 * @param int        $message_id The message post ID.
	 * @return array<int> Filtered post IDs.
	 */
	public function filter_already_received( array $ids, int $message_id ): array {
		return array_values(
			array_filter(
				$ids,
				function ( int $id ) use ( $message_id ): bool {
					$received_messages = get_post_meta( $id, '_dame_message_received', false );
					$received_ids      = array_map( 'strval', (array) $received_messages );
					return ! in_array( (string) $message_id, $received_ids, true );
				}
			)
		);
	}

	/**
	 * Collects, prioritizes and deduplicates email addresses for target recipients.
	 *
	 * @param array<int> $adherent_ids Adherent post IDs.
	 * @param array<int> $contact_ids Contact post IDs.
	 * @return array<string, array{id: int, names: array<string>, prio: int, raw_email: string}>
	 */
	public function collect_email_data( array $adherent_ids, array $contact_ids ): array {
		$all_ids = array_merge( $adherent_ids, $contact_ids );
		if ( ! empty( $all_ids ) ) {
			update_meta_cache( 'post', $all_ids );
		}

		$email_data = array();

		$format_name = fn( int $id, string $type = 'adherent' ) =>
			'adherent' === $type ? Utils::generate_adherent_title( $id ) : Utils::generate_contact_title( $id );

		// Priorité 1 : Emails directs des Adhérents.
		foreach ( array_unique( $adherent_ids ) as $aid ) {
			$email = get_post_meta( $aid, '_dame_email', true );
			if ( ! empty( $email ) && is_email( (string) $email ) && '1' !== get_post_meta( $aid, '_dame_email_refuses_comms', true ) ) {
				$raw_email = trim( (string) $email );
				$lemail    = strtolower( $raw_email );
				if ( ! isset( $email_data[ $lemail ] ) ) {
					$email_data[ $lemail ] = array(
						'id'        => $aid,
						'names'     => array(),
						'prio'      => 1,
						'raw_email' => $raw_email,
					);
				}
				$email_data[ $lemail ]['names'][] = $format_name( $aid );
			}
		}

		// Priorité 2 : Emails des Représentants Légaux.
		foreach ( array_unique( $adherent_ids ) as $aid ) {
			for ( $i = 1; $i <= 2; $i++ ) {
				$email   = get_post_meta( $aid, "_dame_legal_rep_{$i}_email", true );
				$refuses = get_post_meta( $aid, "_dame_legal_rep_{$i}_email_refuses_comms", true );
				if ( ! empty( $email ) && is_email( (string) $email ) && '1' !== $refuses ) {
					$raw_email = trim( (string) $email );
					$lemail    = strtolower( $raw_email );
					if ( ! isset( $email_data[ $lemail ] ) ) {
						$email_data[ $lemail ] = array(
							'id'        => $aid,
							'names'     => array(),
							'prio'      => 2,
							'raw_email' => $raw_email,
						);
					}
					if ( 2 === $email_data[ $lemail ]['prio'] ) {
						$email_data[ $lemail ]['names'][] = $format_name( $aid ) . ' (RL)';
					}
				}
			}
		}

		// Priorité 3 : Emails des Contacts.
		foreach ( array_unique( $contact_ids ) as $cid ) {
			$email   = get_post_meta( $cid, '_dame_contact_email', true );
			$refuses = get_post_meta( $cid, '_dame_contact_no_emails', true );
			if ( ! empty( $email ) && is_email( (string) $email ) && '1' !== $refuses ) {
				$raw_email = trim( (string) $email );
				$lemail    = strtolower( $raw_email );
				if ( ! isset( $email_data[ $lemail ] ) ) {
					$email_data[ $lemail ] = array(
						'id'        => $cid,
						'names'     => array(),
						'prio'      => 3,
						'raw_email' => $raw_email,
					);
				}
				if ( 3 === $email_data[ $lemail ]['prio'] ) {
					$email_data[ $lemail ]['names'][] = $format_name( $cid, 'contact' );
				}
			}
		}

		return $email_data;
	}

	/**
	 * Pre-registers message opens tracking records.
	 *
	 * @param int                                                                                   $message_id Message post ID.
	 * @param array<string, array{id: int, names: array<string>, prio: int, raw_email: string}> $email_data Email recipient data.
	 */
	public function register_tracking_records( int $message_id, array $email_data ): void {
		global $wpdb;
		$table_tracking = $wpdb->prefix . 'dame_message_opens';
		$values_sql     = array();

		foreach ( $email_data as $info ) {
			$email        = $info['raw_email'];
			$hash         = md5( strtolower( trim( $email ) ) );
			$label        = implode( ', ', array_unique( $info['names'] ) );
			$values_sql[] = $wpdb->prepare(
				'(%d, %d, %s, %s, %s)',
				$message_id,
				$info['id'],
				$label,
				$email,
				$hash
			);
		}

		if ( ! empty( $values_sql ) ) {
			$emails_to_insert = array_column( $email_data, 'raw_email' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table_tracking} WHERE message_id = %d AND recipient_email IN (" . implode( ',', array_fill( 0, count( $emails_to_insert ), '%s' ) ) . ')', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					array_merge( array( $message_id ), $emails_to_insert )
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "INSERT INTO {$table_tracking} (message_id, recipient_id, recipient_name, recipient_email, email_hash) VALUES " . implode( ',', $values_sql ) );
		}
	}
}
