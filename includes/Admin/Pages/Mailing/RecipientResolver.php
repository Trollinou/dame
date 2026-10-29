<?php
/**
 * Recipient Resolver for Mailing.
 *
 * @package DAME\Admin\Pages\Mailing
 */

declare(strict_types=1);

namespace DAME\Admin\Pages\Mailing;

use DateTime;
use DAME\Core\Utils;
use DAME\Services\Data_Provider;

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
	 *     ha_item_ids: array<int>,
	 *     adherent_criteria_selected: bool,
	 *     contact_criteria_selected: bool,
	 *     ha_criteria_selected: bool,
	 *     meta: array<string, mixed>
	 * }
	 */
	public function resolve_target_ids( array $post_data ): array {
		$adherent_method = isset( $post_data['dame_adherent_method'] ) ? sanitize_key( (string) $post_data['dame_adherent_method'] ) : 'group';
		$contact_method  = isset( $post_data['dame_contact_method'] ) ? sanitize_key( (string) $post_data['dame_contact_method'] ) : 'group';
		$ha_method       = isset( $post_data['dame_ha_method'] ) ? sanitize_key( (string) $post_data['dame_ha_method'] ) : 'group';

		$adherent_ids               = array();
		$contact_ids                = array();
		$ha_item_ids                = array();
		$adherent_criteria_selected = false;
		$contact_criteria_selected  = false;
		$ha_criteria_selected       = false;

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
			'ha_campaigns'      => array(),
			'manual_ha_items'   => array(),
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

			$depts = array_values( array_filter( $depts, fn( string $d ): bool => '' !== trim( $d ) ) );

			// Au moins un type de contact doit obligatoirement être sélectionné pour inclure des contacts par critères.
			if ( ! empty( $contact_types ) ) {
				$contact_criteria_selected = true;

				$query_args = array(
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
				);

				// Seuls les départements explicitement cochés sont pris en compte pour le filtrage géographique.
				// Si aucun département n'est spécifié mais que des régions le sont (ex: appel API direct), dériver les départements.
				$target_depts = $depts;
				if ( empty( $target_depts ) && ! empty( $regions ) ) {
					foreach ( $regions as $region_code ) {
						$target_depts = array_merge( $target_depts, Data_Provider::get_departments_by_region( $region_code ) );
					}
					$target_depts = array_values( array_unique( $target_depts ) );
				}

				if ( ! empty( $target_depts ) ) {
					$query_args['meta_query'] = array(
						array(
							'key'     => '_dame_contact_department',
							'value'   => $target_depts,
							'compare' => 'IN',
						),
					);
				}

				$contact_ids = get_posts( $query_args );
			}
		}

		// C. HelloAsso.
		if ( 'manual' === $ha_method ) {
			if ( ! empty( $post_data['dame_manual_ha_items'] ) && is_array( $post_data['dame_manual_ha_items'] ) ) {
				$ha_item_ids             = array_map( 'absint', $post_data['dame_manual_ha_items'] );
				$meta['manual_ha_items'] = $ha_item_ids;
				$ha_criteria_selected    = true;
			}
		} else {
			$ha_campaigns         = isset( $post_data['dame_recipient_ha_campaigns'] ) ? array_map( 'absint', (array) $post_data['dame_recipient_ha_campaigns'] ) : array();
			$meta['ha_campaigns'] = $ha_campaigns;

			if ( ! empty( $ha_campaigns ) ) {
				$ha_criteria_selected = true;
				$ticketing_repo       = new \DAME\Repositories\TicketingRepository();
				$active_items         = $ticketing_repo->get_active_items_by_campaigns( $ha_campaigns );
				$ha_item_ids          = array_column( $active_items, 'id' );
			}
		}

		return array(
			'adherent_ids'               => array_values( array_unique( array_map( 'intval', $adherent_ids ) ) ),
			'contact_ids'                => array_values( array_unique( array_map( 'intval', $contact_ids ) ) ),
			'ha_item_ids'                => array_values( array_unique( array_map( 'intval', $ha_item_ids ) ) ),
			'adherent_criteria_selected' => $adherent_criteria_selected,
			'contact_criteria_selected'  => $contact_criteria_selected,
			'ha_criteria_selected'       => $ha_criteria_selected,
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
		if ( empty( $ids ) || ! $message_id ) {
			return $ids;
		}

		global $wpdb;
		$table_tracking = $wpdb->prefix . 'dame_message_opens';

		// 1. Get all recipient IDs that already received or are scheduled for this message in SQL tracking table.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing_ids     = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT recipient_id FROM {$table_tracking} WHERE message_id = %d AND recipient_id > 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$message_id
			)
		);
		$existing_ids_map = ! empty( $existing_ids ) ? array_fill_keys( array_map( 'intval', $existing_ids ), true ) : array();

		return array_values(
			array_filter(
				$ids,
				function ( int $id ) use ( $message_id, $existing_ids_map ): bool {
					if ( isset( $existing_ids_map[ $id ] ) ) {
						return false;
					}

					// Fallback to legacy postmeta.
					$received_messages = get_post_meta( $id, '_dame_message_received', false );
					$received_ids      = array_map( 'strval', (array) $received_messages );
					return ! in_array( (string) $message_id, $received_ids, true );
				}
			)
		);
	}

	/**
	 * Checks if the message content or subject contains personalization tags.
	 *
	 * @param string $subject Message subject.
	 * @param string $content Message HTML content.
	 * @return bool
	 */
	public function has_personalization_tags( string $subject, string $content ): bool {
		return (bool) preg_match( '/\[(NOM|PRENOM|CIVILITE|AGE)\]/i', $subject . ' ' . $content );
	}

	/**
	 * Filters out email records that were already sent or scheduled for this message.
	 *
	 * @param array<string, array{id: int, names: array<string>, prio: int, raw_email: string, first_name?: string, last_name?: string}> $email_data Email recipient data.
	 * @param int                                                                                                                        $message_id The message post ID.
	 * @return array<string, array{id: int, names: array<string>, prio: int, raw_email: string, first_name?: string, last_name?: string}> Filtered email recipient data.
	 */
	public function filter_already_sent_emails( array $email_data, int $message_id ): array {
		if ( empty( $email_data ) || ! $message_id ) {
			return $email_data;
		}

		global $wpdb;
		$table_tracking = $wpdb->prefix . 'dame_message_opens';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing_records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT LOWER(recipient_email) as email, recipient_name FROM {$table_tracking} WHERE message_id = %d AND recipient_email != ''", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$message_id
			),
			ARRAY_A
		);

		if ( empty( $existing_records ) ) {
			return $email_data;
		}

		$sent_map = array();
		foreach ( $existing_records as $rec ) {
			$e              = strtolower( (string) $rec['email'] );
			$n              = trim( (string) ( $rec['recipient_name'] ?? '' ) );
			$sent_map[ $e ] = true;
			if ( ! empty( $n ) ) {
				$sent_map[ $e . '|' . $n ] = true;
			}
		}

		return array_filter(
			$email_data,
			function ( array $item ) use ( $sent_map ): bool {
				$email_lower = strtolower( (string) ( $item['raw_email'] ?? '' ) );
				$label       = implode( ', ', array_unique( $item['names'] ?? array() ) );
				if ( ! empty( $label ) && isset( $sent_map[ $email_lower . '|' . $label ] ) ) {
					return false;
				}
				return ! isset( $sent_map[ $email_lower ] );
			}
		);
	}

	/**
	 * Collects, prioritizes and deduplicates email addresses for target recipients.
	 *
	 * @param array<int> $adherent_ids    Adherent post IDs.
	 * @param array<int> $contact_ids     Contact post IDs.
	 * @param array<int> $ha_item_ids     HelloAsso item IDs.
	 * @param bool       $is_personalized Whether the message contains personalization tags.
	 * @return array<string, array{id: int, names: array<string>, prio: int, raw_email: string, first_name?: string, last_name?: string}>
	 */
	public function collect_email_data( array $adherent_ids, array $contact_ids, array $ha_item_ids = array(), bool $is_personalized = false ): array {
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
				$raw_email  = trim( (string) $email );
				$lemail     = strtolower( $raw_email );
				$key        = $is_personalized ? $lemail . '_aid_' . $aid : $lemail;
				$first_name = (string) get_post_meta( $aid, '_dame_first_name', true );
				$last_name  = (string) get_post_meta( $aid, '_dame_last_name', true );
				if ( empty( $last_name ) ) {
					$last_name = (string) get_post_meta( $aid, '_dame_birth_name', true );
				}

				if ( ! isset( $email_data[ $key ] ) ) {
					$email_data[ $key ] = array(
						'id'         => $aid,
						'names'      => array(),
						'prio'       => 1,
						'raw_email'  => $raw_email,
						'first_name' => $first_name,
						'last_name'  => $last_name,
					);
				}
				$email_data[ $key ]['names'][] = $format_name( $aid );
			}
		}

		// Priorité 2 : Emails des Représentants Légaux.
		foreach ( array_unique( $adherent_ids ) as $aid ) {
			$first_name = (string) get_post_meta( $aid, '_dame_first_name', true );
			$last_name  = (string) get_post_meta( $aid, '_dame_last_name', true );
			if ( empty( $last_name ) ) {
				$last_name = (string) get_post_meta( $aid, '_dame_birth_name', true );
			}

			for ( $i = 1; $i <= 2; $i++ ) {
				$email   = get_post_meta( $aid, "_dame_legal_rep_{$i}_email", true );
				$refuses = get_post_meta( $aid, "_dame_legal_rep_{$i}_email_refuses_comms", true );
				if ( ! empty( $email ) && is_email( (string) $email ) && '1' !== $refuses ) {
					$raw_email = trim( (string) $email );
					$lemail    = strtolower( $raw_email );
					$key       = $is_personalized ? $lemail . '_aid_' . $aid : $lemail;

					if ( ! isset( $email_data[ $key ] ) ) {
						$email_data[ $key ] = array(
							'id'         => $aid,
							'names'      => array(),
							'prio'       => 2,
							'raw_email'  => $raw_email,
							'first_name' => $first_name,
							'last_name'  => $last_name,
						);
					}
					if ( 2 === $email_data[ $key ]['prio'] ) {
						$email_data[ $key ]['names'][] = $format_name( $aid ) . ' (RL)';
					}
				}
			}
		}

		// Priorité 3 : Emails des Contacts.
		foreach ( array_unique( $contact_ids ) as $cid ) {
			$email   = get_post_meta( $cid, '_dame_contact_email', true );
			$refuses = get_post_meta( $cid, '_dame_contact_no_emails', true );
			if ( ! empty( $email ) && is_email( (string) $email ) && '1' !== $refuses ) {
				$raw_email  = trim( (string) $email );
				$lemail     = strtolower( $raw_email );
				$key        = $is_personalized ? $lemail . '_cid_' . $cid : $lemail;
				$first_name = (string) get_post_meta( $cid, '_dame_contact_first_name', true );
				$last_name  = (string) get_post_meta( $cid, '_dame_contact_last_name', true );

				if ( ! isset( $email_data[ $key ] ) ) {
					$email_data[ $key ] = array(
						'id'         => $cid,
						'names'      => array(),
						'prio'       => 3,
						'raw_email'  => $raw_email,
						'first_name' => $first_name,
						'last_name'  => $last_name,
					);
				}
				if ( 3 === $email_data[ $key ]['prio'] ) {
					$email_data[ $key ]['names'][] = $format_name( $cid, 'contact' );
				}
			}
		}

		// Priorité 4 : HelloAsso (Participants, Payeurs & Représentants légaux si adhérent mineur).
		if ( ! empty( $ha_item_ids ) ) {
			$ticketing_repo = new \DAME\Repositories\TicketingRepository();
			$ha_items       = $ticketing_repo->get_active_items_by_ids( $ha_item_ids );

			foreach ( $ha_items as $item ) {
				$part_fn       = trim( (string) ( $item['participant_first_name'] ?? '' ) );
				$part_ln       = trim( (string) ( $item['participant_last_name'] ?? '' ) );
				$camp_title    = (string) ( $item['campaign_title'] ?? '' );
				$matched_aid   = (int) ( $item['matched_adherent_id'] ?? 0 );
				$target_suffix = $matched_aid > 0 ? 'aid_' . $matched_aid : 'ha_' . (int) $item['id'];
				$target_rid    = $matched_aid > 0 ? $matched_aid : 0;

				if ( $matched_aid > 0 ) {
					if ( empty( $part_fn ) ) {
						$part_fn = (string) get_post_meta( $matched_aid, '_dame_first_name', true );
					}
					if ( empty( $part_ln ) ) {
						$part_ln = (string) get_post_meta( $matched_aid, '_dame_last_name', true );
						if ( empty( $part_ln ) ) {
							$part_ln = (string) get_post_meta( $matched_aid, '_dame_birth_name', true );
						}
					}
					$part_label = Utils::generate_adherent_title( $matched_aid );
				} else {
					$part_label = trim( $part_fn . ' ' . $part_ln );
					if ( empty( $part_label ) ) {
						$part_label = __( 'Participant HelloAsso', 'dame' );
					}
				}

				// A. E-mail du participant HelloAsso.
				$part_email = trim( (string) ( $item['participant_email'] ?? '' ) );
				if ( ! empty( $part_email ) && is_email( $part_email ) ) {
					$raw_email = $part_email;
					$lemail    = strtolower( $raw_email );
					$key       = $is_personalized ? $lemail . '_' . $target_suffix : $lemail;
					$label     = $part_label . ' (HelloAsso : ' . $camp_title . ')';

					if ( ! isset( $email_data[ $key ] ) ) {
						$email_data[ $key ] = array(
							'id'         => $target_rid,
							'names'      => array(),
							'prio'       => 4,
							'raw_email'  => $raw_email,
							'first_name' => $part_fn,
							'last_name'  => $part_ln,
						);
					}
					$email_data[ $key ]['names'][] = $label;
				}

				// B. E-mail du payeur HelloAsso.
				$payer_email = trim( (string) ( $item['payer_email'] ?? '' ) );
				if ( ! empty( $payer_email ) && is_email( $payer_email ) ) {
					$raw_email = $payer_email;
					$lemail    = strtolower( $raw_email );
					$key       = $is_personalized ? $lemail . '_' . $target_suffix : $lemail;
					$label     = $part_label . ' (Payeur : ' . $camp_title . ')';

					if ( ! isset( $email_data[ $key ] ) ) {
						$email_data[ $key ] = array(
							'id'         => $target_rid,
							'names'      => array(),
							'prio'       => 4,
							'raw_email'  => $raw_email,
							'first_name' => $part_fn,
							'last_name'  => $part_ln,
						);
					}
					$email_data[ $key ]['names'][] = $label;
				}

				// C. Représentants légaux si le participant est un adhérent mineur.
				if ( $matched_aid > 0 ) {
					$birth_date = (string) get_post_meta( $matched_aid, '_dame_birth_date', true );
					$is_minor   = false;
					if ( ! empty( $birth_date ) ) {
						try {
							$age      = ( new DateTime( $birth_date ) )->diff( new DateTime() )->y;
							$is_minor = $age < 18;
						} catch ( \Exception $e ) {
							$is_minor = false;
						}
					} else {
						$rep1_check = get_post_meta( $matched_aid, '_dame_legal_rep_1_email', true );
						$is_minor   = ! empty( $rep1_check );
					}

					if ( $is_minor ) {
						for ( $i = 1; $i <= 2; $i++ ) {
							$rep_email   = trim( (string) get_post_meta( $matched_aid, "_dame_legal_rep_{$i}_email", true ) );
							$rep_refuses = get_post_meta( $matched_aid, "_dame_legal_rep_{$i}_email_refuses_comms", true );
							if ( ! empty( $rep_email ) && is_email( $rep_email ) && '1' !== $rep_refuses ) {
								$raw_email = $rep_email;
								$lemail    = strtolower( $raw_email );
								$key       = $is_personalized ? $lemail . '_' . $target_suffix : $lemail;
								$label     = $part_label . ' (RL : ' . $camp_title . ')';

								if ( ! isset( $email_data[ $key ] ) ) {
									$email_data[ $key ] = array(
										'id'         => $matched_aid,
										'names'      => array(),
										'prio'       => 2,
										'raw_email'  => $raw_email,
										'first_name' => $part_fn,
										'last_name'  => $part_ln,
									);
								}
								$email_data[ $key ]['names'][] = $label;
							}
						}
					}
				}
			}
		}

		return $email_data;
	}

	/**
	 * Registers sent message tracking records and recipient postmeta.
	 *
	 * @param int                                                                                                                        $message_id Message post ID.
	 * @param array<string, array{id: int, names: array<string>, prio: int, raw_email: string, first_name?: string, last_name?: string}> $email_data Email recipient data.
	 * @param string                                                                                                                     $sent_at    Sent datetime string (Y-m-d H:i:s).
	 */
	public function register_tracking_records( int $message_id, array $email_data, string $sent_at ): void {
		global $wpdb;
		$table_tracking = $wpdb->prefix . 'dame_message_opens';
		$values_sql     = array();

		foreach ( $email_data as $info ) {
			$email        = $info['raw_email'];
			$hash         = md5( strtolower( trim( $email ) ) );
			$label        = implode( ', ', array_unique( $info['names'] ) );
			$values_sql[] = $wpdb->prepare(
				'(%d, %d, %s, %s, %s, %s)',
				$message_id,
				$info['id'],
				$label,
				$email,
				$hash,
				$sent_at
			);

			// Maintain postmeta receipt markers.
			if ( $info['id'] > 0 ) {
				add_post_meta( $info['id'], '_dame_message_received', (string) $message_id );
				update_post_meta( $info['id'], "_dame_message_{$message_id}_sent_at", $sent_at );
				wp_cache_delete( $info['id'], 'post_meta' );
			}
		}

		if ( ! empty( $values_sql ) ) {
			$emails_to_insert = array_values( array_unique( array_column( $email_data, 'raw_email' ) ) );
			// Delete any previous pending records for these emails.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table_tracking} WHERE message_id = %d AND sent_at IS NULL AND recipient_email IN (" . implode( ',', array_fill( 0, count( $emails_to_insert ), '%s' ) ) . ')', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					array_merge( array( $message_id ), $emails_to_insert )
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "INSERT INTO {$table_tracking} (message_id, recipient_id, recipient_name, recipient_email, email_hash, sent_at) VALUES " . implode( ',', $values_sql ) );
		}
	}

	/**
	 * Personalizes subject and body tags [NOM], [PRENOM], [AGE], [CIVILITE] for a specific recipient.
	 *
	 * @param int                  $rid            Recipient post ID.
	 * @param string               $email          Recipient email.
	 * @param string               $label          Recipient fallback label.
	 * @param string               $subject        Original subject.
	 * @param string               $content        Original HTML content.
	 * @param array<string, mixed> $recipient_data Optional recipient extra metadata.
	 * @return array{subject: string, content: string}
	 */
	public function personalize_message( int $rid, string $email, string $label, string $subject, string $content, array $recipient_data = array() ): array {
		$nom    = '';
		$prenom = '';
		$sexe   = '';
		$age    = '';

		if ( ! empty( $recipient_data['first_name'] ) || ! empty( $recipient_data['last_name'] ) ) {
			$nom    = (string) ( $recipient_data['last_name'] ?? '' );
			$prenom = (string) ( $recipient_data['first_name'] ?? '' );
		}

		$type = $rid > 0 ? get_post_type( $rid ) : null;
		if ( 'adherent' === $type || 'dame_pre_inscription' === $type ) {
			if ( empty( $nom ) ) {
				$nom = (string) get_post_meta( $rid, '_dame_last_name', true );
				if ( empty( $nom ) ) {
					$nom = (string) get_post_meta( $rid, '_dame_birth_name', true );
				}
			}
			if ( empty( $prenom ) ) {
				$prenom = (string) get_post_meta( $rid, '_dame_first_name', true );
			}
			$sexe  = (string) get_post_meta( $rid, '_dame_sexe', true );
			$birth = (string) get_post_meta( $rid, '_dame_birth_date', true );
			if ( ! empty( $birth ) ) {
				try {
					$age = (string) ( new DateTime( (string) $birth ) )->diff( new DateTime() )->y;
				} catch ( \Exception $e ) {
					$age = '';
				}
			}
		} elseif ( 'dame_contact' === $type ) {
			if ( empty( $nom ) ) {
				$nom = (string) get_post_meta( $rid, '_dame_contact_last_name', true );
			}
			if ( empty( $prenom ) ) {
				$prenom = (string) get_post_meta( $rid, '_dame_contact_first_name', true );
			}
			$sexe = (string) get_post_meta( $rid, '_dame_contact_sexe', true );
		}

		$civilite = 'Madame, Monsieur';
		if ( 'Féminin' === $sexe ) {
			$civilite = 'Madame';
		} elseif ( 'Masculin' === $sexe ) {
			$civilite = 'Monsieur';
		}

		if ( empty( $nom ) && empty( $prenom ) ) {
			$search  = array( '[NOM]', '[PRENOM]', '[AGE]', '[CIVILITE]' );
			$replace = array( $label, '', '', $civilite );
		} else {
			$search  = array( '[NOM]', '[PRENOM]', '[AGE]', '[CIVILITE]' );
			$replace = array(
				esc_html( Utils::format_lastname( (string) $nom ) ),
				esc_html( Utils::format_firstname( (string) $prenom ) ),
				esc_html( (string) $age ),
				esc_html( $civilite ),
			);
		}

		return array(
			'subject' => str_replace( $search, $replace, $subject ),
			'content' => str_replace( $search, $replace, $content ),
		);
	}
}
