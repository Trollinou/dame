<?php
/**
 * DAME Upgrader class.
 *
 * @package DAME
 */

namespace DAME\Core;

use WP_Query;
use DateTime;

/**
 * Handles plugin database migrations and updates.
 */
class Upgrader {

	/**
	 * Hook the update check into WordPress.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'check_for_updates' ], 99 );
	}

	/**
	 * Compares stored version with current constant.
	 */
	public function check_for_updates(): void {
		$current_version = get_option( 'dame_plugin_version', '1.0.0' );

		if ( version_compare( $current_version, \DAME_VERSION, '<' ) ) {
			$this->perform_upgrade( $current_version, \DAME_VERSION );
		}
	}

	/**
	 * Core migration logic.
	 */
	private function perform_upgrade( string $old_version, string $new_version ): void {
		
		// Version < 2.0.0 : Capacités et Permaliens
		if ( version_compare( $old_version, '2.0.0', '<' ) ) {
			// Note : Assurez-vous que cette fonction globale existe encore ou déplacez-la ici
			if ( function_exists( 'dame_add_capabilities_to_roles' ) ) {
				dame_add_capabilities_to_roles();
			}
			flush_rewrite_rules();
		}

		// Version < 2.2.0 : Migration Taxonomie Saisons
		if ( version_compare( $old_version, '2.2.0', '<' ) ) {
			$this->migrate_seasons_v220();
		}

		// Version < 2.2.1 : Tailles vêtements
		if ( version_compare( $old_version, '2.2.1', '<' ) ) {
			$this->migrate_clothing_sizes_v221();
		}

		// Version < 3.3.0 : Groupes
		if ( version_compare( $old_version, '3.3.0', '<' ) ) {
			$this->migrate_to_group_taxonomy_v330();
		}

		// Version < 3.3.9 : Nom de naissance
		if ( version_compare( $old_version, '3.3.9', '<' ) ) {
			$this->migrate_birth_name_v339();
		}

		// Version < 3.4.0 : Table SQL des ouvertures d'emails
		if ( version_compare( $old_version, '3.4.0', '<' ) ) {
			$this->create_message_opens_table_v340();
		}

		// Version < 4.2.0 : Refonte du suivi des messages (Enrichissement SQL + Migration postmeta)
		if ( version_compare( $old_version, '4.2.0', '<' ) ) {
			$this->upgrade_message_tracking_v420();
		}

		// Version < 4.2.1 : Correction migration messagerie (Emails partagés)
		if ( version_compare( $old_version, '4.2.1', '<' ) ) {
			$this->upgrade_message_tracking_v421();
		}

		// Version < 4.2.2 : Ajout de recipient_name et agrégation des noms
		if ( version_compare( $old_version, '4.2.2', '<' ) ) {
			$this->upgrade_message_tracking_v422();
		}

		// Version < 4.2.4 : Neutralisation des anciens logs pour éviter les envois accidentels
		if ( version_compare( $old_version, '4.2.4', '<' ) ) {
			$this->neutralize_legacy_logs_v424();
		}

		// Version < 4.3.2 : Table SQL des votes de sondages
		if ( version_compare( $old_version, '4.3.2', '<' ) ) {
			$this->upgrade_poll_votes_v432();
		}

		// Version < 4.3.7 : Nettoyage des doublons de votes de sondages
		if ( version_compare( $old_version, '4.3.7', '<' ) ) {
			$this->cleanup_poll_votes_v437();
		}

		// Finalisation
		update_option( 'dame_plugin_version', $new_version );
	}

	/**
	 * Migration Methods (Ported from functional code)
	 */

	/**
	 * Cleanup duplicate poll votes (v4.3.7).
	 */
	private function cleanup_poll_votes_v437(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_poll_votes';
		
		// This query deletes rows that have the same (poll_id, recipient_id, choice_key) but a higher ID.
		// It keeps only the row with the lowest ID for each unique vote.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "
			DELETE v1 FROM $table_name v1
			INNER JOIN $table_name v2 
			WHERE v1.id > v2.id 
			  AND v1.poll_id = v2.poll_id 
			  AND v1.recipient_id = v2.recipient_id 
			  AND v1.choice_key = v2.choice_key
		" );
	}

	private function migrate_seasons_v220(): void {
		wp_insert_term( 'Saison antérieure', 'dame_saison_adhesion' );

		$current_month      = (int) date( 'n' );
		$current_year       = (int) date( 'Y' );
		$season_start_year  = ( $current_month >= 9 ) ? $current_year : $current_year - 1;
		$season_end_year    = $season_start_year + 1;
		$current_season_name = sprintf( 'Saison %d/%d', $season_start_year, $season_end_year );

		$current_season_term = wp_insert_term( $current_season_name, 'dame_saison_adhesion' );

		if ( ! is_wp_error( $current_season_term ) ) {
			update_option( 'dame_current_season_tag_id', $current_season_term['term_id'] );
		} elseif ( isset( $current_season_term->error_data['term_exists'] ) ) {
			update_option( 'dame_current_season_tag_id', $current_season_term->error_data['term_exists'] );
		}

		$adherents = get_posts( [ 'post_type' => 'adherent', 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'any' ] );
		foreach ( $adherents as $id ) {
			$status = get_post_meta( $id, '_dame_membership_status', true );
			$current_term_id = get_option( 'dame_current_season_tag_id' );

			if ( 'A' === $status && $current_term_id ) {
				wp_set_object_terms( $id, (int) $current_term_id, 'dame_saison_adhesion' );
			}
			delete_post_meta( $id, '_dame_membership_status' );
			delete_post_meta( $id, '_dame_membership_date' );
		}
		flush_rewrite_rules();
	}

	private function migrate_clothing_sizes_v221(): void {
		$ids = get_posts( [ 'post_type' => 'adherent', 'posts_per_page' => -1, 'fields' => 'ids' ] );
		$valid_sizes = [ 'Non renseigné', '8/10', '10/12', '12/14', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL' ];
		foreach ( $ids as $id ) {
			$size = get_post_meta( $id, '_dame_taille_vetements', true );
			if ( ! in_array( $size, $valid_sizes, true ) ) {
				update_post_meta( $id, '_dame_taille_vetements', 'Non renseigné' );
			}
		}
	}

	private function migrate_to_group_taxonomy_v330(): void {
		$terms = [ 'Ecole d\'échecs', 'Pôle Excellence', 'Bénévole', 'Elu local', 'Presse' ];
		foreach ( $terms as $t ) {
			if ( ! term_exists( $t, 'dame_group' ) ) wp_insert_term( $t, 'dame_group' );
		}

		$map = [
			'_dame_is_junior'          => 'Ecole d\'échecs',
			'_dame_is_pole_excellence' => 'Pôle Excellence',
			'_dame_is_benevole'        => 'Bénévole',
			'_dame_is_elu_local'       => 'Elu local',
		];

		$ids = get_posts( [ 'post_type' => 'adherent', 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'any' ] );
		foreach ( $ids as $id ) {
			foreach ( $map as $meta => $term ) {
				if ( get_post_meta( $id, $meta, true ) === '1' ) {
					wp_add_object_terms( $id, $term, 'dame_group' );
					delete_post_meta( $id, $meta );
				}
			}
		}
	}

	private function migrate_birth_name_v339(): void {
		foreach ( [ 'adherent', 'dame_pre_inscription' ] as $pt ) {
			$ids = get_posts( [ 'post_type' => $pt, 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'any' ] );
			foreach ( $ids as $id ) {
				if ( empty( get_post_meta( $id, '_dame_birth_name', true ) ) ) {
					$last = get_post_meta( $id, '_dame_last_name', true );
					if ( ! empty( $last ) ) update_post_meta( $id, '_dame_birth_name', $last );
				}
			}
		}
	}

	private function create_message_opens_table_v340(): void {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'dame_message_opens';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			message_id bigint(20) NOT NULL,
			email_hash varchar(32) NOT NULL,
			opened_at datetime NOT NULL,
			user_ip varchar(45) NOT NULL,
			PRIMARY KEY  (id),
			INDEX message_email_idx (message_id, email_hash)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Upgrade message tracking system to version 4.2.0.
	 * 
	 * Adds columns to the SQL table and migrates history from postmeta.
	 */
	private function upgrade_message_tracking_v420(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_message_opens';

		// 1. Structure update
		$wpdb->query( "ALTER TABLE $table_name 
			ADD COLUMN recipient_id bigint(20) NOT NULL DEFAULT 0 AFTER message_id,
			ADD COLUMN recipient_name varchar(255) NOT NULL DEFAULT '' AFTER recipient_id,
			ADD COLUMN recipient_email varchar(255) NOT NULL DEFAULT '' AFTER recipient_name,
			ADD COLUMN sent_at datetime NULL DEFAULT NULL AFTER email_hash,
			MODIFY COLUMN opened_at datetime NULL DEFAULT NULL,
			MODIFY COLUMN user_ip varchar(45) NULL DEFAULT NULL,
			ADD INDEX recipient_id_idx (recipient_id),
			ADD INDEX message_recipient_idx (message_id, recipient_id)" 
		);

		// 2. Data Migration from postmeta
		$post_types = [ 'adherent', 'dame_contact' ];
		$posts = get_posts( [
			'post_type'      => $post_types,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids'
		] );

		foreach ( $posts as $pid ) {
			$message_ids = get_post_meta( $pid, '_dame_message_received', false );
			if ( empty( $message_ids ) ) continue;

			// Determine primary email
			$type = get_post_type( $pid );
			$email = ( 'adherent' === $type ) ? get_post_meta( $pid, '_dame_email', true ) : get_post_meta( $pid, '_dame_contact_email', true );

			if ( empty( $email ) ) continue;
			$hash = md5( mb_strtolower( trim( (string) $email ), 'UTF-8' ) );

			foreach ( $message_ids as $mid ) {
				$mid = (int) $mid;
				$sent_at = get_post_meta( $pid, "_dame_message_{$mid}_sent_at", true );

				// Look for existing open record
				$existing_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM $table_name WHERE message_id = %d AND email_hash = %s",
					$mid, $hash
				) );

				if ( $existing_id ) {
					$wpdb->update( $table_name, [
						'recipient_id'    => $pid,
						'recipient_email' => $email,
						'sent_at'         => $sent_at ?: current_time( 'mysql', true )
					], [ 'id' => $existing_id ] );
				} else {
					$wpdb->insert( $table_name, [
						'message_id'      => $mid,
						'recipient_id'    => $pid,
						'recipient_email' => $email,
						'email_hash'      => $hash,
						'sent_at'         => $sent_at ?: current_time( 'mysql', true ),
						'opened_at'       => null,
						'user_ip'         => null
					] );
				}
			}
		}
	}

	/**
	 * Corrective migration for shared emails (v4.2.1).
	 * 
	 * Ensures each recipient has their own row in the tracking table.
	 */
	private function upgrade_message_tracking_v421(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_message_opens';

		$post_types = [ 'adherent', 'dame_contact' ];
		$posts = get_posts( [
			'post_type'      => $post_types,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids'
		] );

		foreach ( $posts as $pid ) {
			$message_ids = get_post_meta( $pid, '_dame_message_received', false );
			if ( empty( $message_ids ) ) continue;

			$type = get_post_type( $pid );
			$email = ( 'adherent' === $type ) ? get_post_meta( $pid, '_dame_email', true ) : get_post_meta( $pid, '_dame_contact_email', true );

			if ( empty( $email ) ) continue;
			$hash = md5( mb_strtolower( trim( (string) $email ), 'UTF-8' ) );

			foreach ( $message_ids as $mid ) {
				$mid = (int) $mid;
				$sent_at = get_post_meta( $pid, "_dame_message_{$mid}_sent_at", true );

				// 1. Check if this specific recipient already has a row
				$exists = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM $table_name WHERE message_id = %d AND recipient_id = %d",
					$mid, $pid
				) );

				if ( $exists ) continue;

				// 2. Look for an "old" open record for this email that isn't linked yet
				$ghost_row = $wpdb->get_row( $wpdb->prepare(
					"SELECT id, opened_at, user_ip FROM $table_name WHERE message_id = %d AND email_hash = %s AND recipient_id = 0 LIMIT 1",
					$mid, $hash
				) );

				if ( $ghost_row ) {
					// Claim the ghost row
					$wpdb->update( $table_name, [
						'recipient_id'    => $pid,
						'recipient_email' => $email,
						'sent_at'         => $sent_at ?: current_time( 'mysql', true )
					], [ 'id' => $ghost_row->id ] );
				} else {
					// 3. Check if another recipient with the SAME email was already opened
					$sibling_open = $wpdb->get_row( $wpdb->prepare(
						"SELECT opened_at, user_ip FROM $table_name WHERE message_id = %d AND email_hash = %s AND opened_at IS NOT NULL LIMIT 1",
						$mid, $hash
					) );

					// Create new individual row
					$wpdb->insert( $table_name, [
						'message_id'      => $mid,
						'recipient_id'    => $pid,
						'recipient_email' => $email,
						'email_hash'      => $hash,
						'sent_at'         => $sent_at ?: current_time( 'mysql', true ),
						'opened_at'       => $sibling_open ? $sibling_open->opened_at : null,
						'user_ip'         => $sibling_open ? $sibling_open->user_ip : null
					] );
				}
			}
		}
	}

	/**
	 * Upgrade message tracking system to version 4.2.2.
	 * 
	 * Ensures recipient_name column exists and is populated.
	 */
	private function upgrade_message_tracking_v422(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_message_opens';

		// 1. Add column if missing
		$column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $table_name LIKE 'recipient_name'" );
		if ( empty( $column_exists ) ) {
			$wpdb->query( "ALTER TABLE $table_name ADD COLUMN recipient_name varchar(255) NOT NULL DEFAULT '' AFTER recipient_id" );
		}

		// 2. Populate names for existing entries that have a recipient_id
		$rows = $wpdb->get_results( "SELECT DISTINCT recipient_id FROM $table_name WHERE recipient_id > 0 AND recipient_name = ''" );
		
		$format_name = function( $id ) {
			$type = get_post_type( $id );
			if ( 'adherent' === $type ) {
				$last  = get_post_meta( $id, '_dame_last_name', true );
				$first = get_post_meta( $id, '_dame_first_name', true );
				return mb_strtoupper( (string) $last, 'UTF-8' ) . ' ' . mb_convert_case( (string) $first, MB_CASE_TITLE, 'UTF-8' );
			} elseif ( 'dame_contact' === $type ) {
				$last  = get_post_meta( $id, '_dame_contact_last_name', true );
				$first = get_post_meta( $id, '_dame_contact_first_name', true );
				$org   = get_post_meta( $id, '_dame_contact_organization', true );
				$full_name = mb_strtoupper( (string) $last, 'UTF-8' ) . ' ' . mb_convert_case( (string) $first, MB_CASE_TITLE, 'UTF-8' );
				return ! empty( $org ) ? (string) $org . ' (' . $full_name . ')' : $full_name;
			}
			return '';
		};

		foreach ( $rows as $row ) {
			$name = $format_name( (int) $row->recipient_id );
			if ( ! empty( $name ) ) {
				$wpdb->update( $table_name, [ 'recipient_name' => $name ], [ 'recipient_id' => $row->recipient_id ] );
			}
		}
	}

	/**
	 * Neutralizes legacy logs (v4.2.4).
	 */
	private function neutralize_legacy_logs_v424(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_message_opens';
		$wpdb->query( "UPDATE $table_name SET sent_at = opened_at WHERE sent_at IS NULL AND opened_at IS NOT NULL" );
		$wpdb->query( "UPDATE $table_name SET sent_at = '2000-01-01 00:00:00' WHERE sent_at IS NULL" );
	}

	/**
	 * Upgrade poll system to version 4.3.2.
	 * 
	 * Creates the dame_poll_votes table and migrates serialized history.
	 */
	private function upgrade_poll_votes_v432(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dame_poll_votes';
		$charset_collate = $wpdb->get_charset_collate();

		// 1. Create table
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			poll_id bigint(20) NOT NULL,
			recipient_id bigint(20) NOT NULL,
			choice_key varchar(255) NOT NULL,
			voted_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY poll_vote_unique (poll_id, recipient_id, choice_key),
			INDEX poll_id_idx (poll_id),
			INDEX recipient_idx (recipient_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// 2. Clear table before migration to avoid duplicates if re-run
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "TRUNCATE TABLE $table_name" );

		// 3. Migrate existing votes from sondage_reponse posts
		$responses = get_posts( [
			'post_type'      => 'sondage_reponse',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );

		foreach ( $responses as $r ) {
			$poll_id      = (int) $r->post_parent;
			$recipient_id = (int) $r->ID; // Use the response post ID as unique recipient identifier
			$voted_at     = $r->post_date;

			$meta = get_post_meta( $r->ID, '_dame_sondage_responses', true );
			if ( ! empty( $meta ) && is_array( $meta ) ) {
				foreach ( $meta as $date_index => $time_slots ) {
					if ( ! is_array( $time_slots ) ) continue;
					foreach ( $time_slots as $time_index => $value ) {
						if ( $value == '1' ) {
							$wpdb->insert( $table_name, [
								'poll_id'      => $poll_id,
								'recipient_id' => $recipient_id,
								'choice_key'   => "{$date_index}_{$time_index}",
								'voted_at'     => $voted_at
							] );
						}
					}
				}
			}
		}
	}
}
