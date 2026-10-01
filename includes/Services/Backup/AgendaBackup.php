<?php
/**
 * Agenda Backup & Restore Service.
 *
 * @package DAME\Services\Backup
 */

declare(strict_types=1);

namespace DAME\Services\Backup;

use WP_Query;
use WP_Post;
use DAME\Core\Upgrader;

/**
 * Handles JSON export/import and backup/restore for Agenda events, categories and volunteer calls.
 */
class AgendaBackup {
	use NoticeTrait;

	/**
	 * Generate Agenda export data (Events and Polls).
	 *
	 * @return array<string, mixed>
	 */
	public function generate_export_data(): array {
		$data = array(
			'version'        => DAME_VERSION,
			'posts'          => array(),
			'taxonomy_terms' => array(),
		);

		// Taxonomy Terms.
		$terms = get_terms(
			array(
				'taxonomy'   => 'dame_agenda_category',
				'hide_empty' => false,
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$term_data = array(
					'term_id'          => $t->term_id,
					'term_taxonomy_id' => $t->term_taxonomy_id,
					'name'             => $t->name,
					'slug'             => $t->slug,
					'description'      => $t->description,
					'parent'           => $t->parent,
					'meta_data'        => array(),
				);
				$meta      = get_option( 'taxonomy_' . $t->term_id );
				if ( ! empty( $meta ) ) {
					$term_data['meta_data']['dame_taxonomy_meta'] = $meta;
				}
				$data['taxonomy_terms'][] = $term_data;
			}
		}

		// Events and Benevolat.
		$post_types = array( 'dame_agenda', 'benevolat', 'benevolat_reponse' );
		$query      = new WP_Query(
			array(
				'post_type'      => $post_types,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		// Optimisation : Pré-chargement des métadonnées.
		if ( ! empty( $query->posts ) ) {
			update_meta_cache( 'post', wp_list_pluck( $query->posts, 'ID' ) );
		}

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$meta = array();
			foreach ( get_post_meta( $post->ID ) as $k => $vals ) {
				$meta[ $k ] = array_map( 'maybe_unserialize', $vals );
			}
			$cats = wp_get_post_terms( $post->ID, 'dame_agenda_category', array( 'fields' => 'slugs' ) );

			$data['posts'][] = array(
				'ID'            => $post->ID,
				'post_author'   => $post->post_author,
				'post_date'     => $post->post_date,
				'post_date_gmt' => $post->post_date_gmt,
				'post_content'  => $post->post_content,
				'post_title'    => $post->post_title,
				'post_excerpt'  => $post->post_excerpt,
				'post_status'   => $post->post_status,
				'post_name'     => $post->post_name,
				'post_parent'   => $post->post_parent,
				'menu_order'    => $post->menu_order,
				'post_type'     => $post->post_type,
				'meta_data'     => $meta,
				'categories'    => $cats,
			);
		}

		// Benevolat Votes.
		global $wpdb;
		$table_votes = $wpdb->prefix . 'dame_benevolat_votes';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- System backup.
		$votes_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', $table_votes ), ARRAY_A );
		if ( is_array( $votes_data ) ) {
			$data['benevolat_votes'] = $votes_data;
		}

		return $data;
	}

	/**
	 * Export JSON agenda backup file.
	 */
	public function export_json(): void {
		$data     = $this->generate_export_data();
		$gz       = gzcompress( (string) wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		$filename = 'dame-agenda-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		ob_clean();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( (string) $gz ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary stream output.
		echo $gz;
		exit;
	}

	/**
	 * Import JSON agenda restore file.
	 */
	public function import_json(): void {
		if ( ! isset( $_POST['dame_agenda_restore_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_agenda_restore_nonce'] ) ), 'dame_agenda_restore_nonce_action' ) ) {
			return;
		}
		if ( ! isset( $_FILES['dame_agenda_restore_file'] ) || ! is_array( $_FILES['dame_agenda_restore_file'] ) || empty( $_FILES['dame_agenda_restore_file']['tmp_name'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded temp file path.
		$tmp_file = sanitize_text_field( wp_unslash( $_FILES['dame_agenda_restore_file']['tmp_name'] ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local temp file read.
		$json = gzuncompress( (string) file_get_contents( $tmp_file ) );
		$data = json_decode( (string) $json, true );
		if ( ! $data ) {
			return;
		}

		global $wpdb;
		$post_types   = array( 'dame_agenda', 'benevolat', 'benevolat_reponse' );
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// 1. PURGE.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- System restore purge.
		$posts_to_delete = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM %i WHERE post_type IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( array( $wpdb->posts ), $post_types )
			)
		);
		foreach ( $posts_to_delete as $pid ) {
			wp_delete_post( (int) $pid, true );
		}
		$terms = get_terms(
			array(
				'taxonomy'   => 'dame_agenda_category',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $tid ) {
				delete_option( "taxonomy_$tid" );
				wp_delete_term( (int) $tid, 'dame_agenda_category' );
			}
		}
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}dame_benevolat_votes" );

		// 2. RESTORE TAXONOMIES.
		foreach ( $data['taxonomy_terms'] ?? array() as $t ) {
			$term_id = (int) $t['term_id'];
			$tt_id   = (int) $t['term_taxonomy_id'];

			// Term check.
			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->terms WHERE term_id = %d", $term_id ) ) ) {
				$wpdb->insert(
					$wpdb->terms,
					array(
						'term_id'    => $term_id,
						'name'       => $t['name'],
						'slug'       => $t['slug'],
						'term_group' => 0,
					)
				);
			} else {
				$wpdb->update(
					$wpdb->terms,
					array(
						'name' => $t['name'],
						'slug' => $t['slug'],
					),
					array( 'term_id' => $term_id )
				);
			}

			// Taxonomy check.
			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $tt_id ) ) ) {
				$wpdb->insert(
					$wpdb->term_taxonomy,
					array(
						'term_taxonomy_id' => $tt_id,
						'term_id'          => $term_id,
						'taxonomy'         => 'dame_agenda_category',
						'description'      => $t['description'],
						'parent'           => $t['parent'],
						'count'            => 0,
					)
				);
			} else {
				$wpdb->update(
					$wpdb->term_taxonomy,
					array(
						'term_id'     => $term_id,
						'taxonomy'    => 'dame_agenda_category',
						'description' => $t['description'],
						'parent'      => $t['parent'],
					),
					array( 'term_taxonomy_id' => $tt_id )
				);
			}

			if ( ! empty( $t['meta_data']['dame_taxonomy_meta'] ) ) {
				update_option( 'taxonomy_' . $term_id, $t['meta_data']['dame_taxonomy_meta'] );
			}
		}

		// 3. RESTORE POSTS.
		$max_post_id = 0;
		foreach ( $data['posts'] ?? array() as $p ) {
			$pid         = (int) $p['ID'];
			$max_post_id = max( $max_post_id, $pid );
			$post_data   = array(
				'ID'                    => $pid,
				'post_author'           => $p['post_author'],
				'post_date'             => $p['post_date'],
				'post_date_gmt'         => $p['post_date_gmt'],
				'post_content'          => $p['post_content'],
				'post_title'            => $p['post_title'],
				'post_excerpt'          => $p['post_excerpt'],
				'post_status'           => $p['post_status'],
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'post_name'             => $p['post_name'],
				'post_modified'         => $p['post_date'],
				'post_modified_gmt'     => $p['post_date_gmt'],
				'post_parent'           => $p['post_parent'],
				'menu_order'            => $p['menu_order'],
				'post_type'             => $p['post_type'],
				'post_content_filtered' => '',
				'to_ping'               => '',
				'pinged'                => '',
				'guid'                  => '',
			);

			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID = %d", $pid ) ) ) {
				$wpdb->insert( $wpdb->posts, $post_data );
			} else {
				$wpdb->update( $wpdb->posts, $post_data, array( 'ID' => $pid ) );
				$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $pid ) );
			}
			foreach ( $p['meta_data'] as $k => $vals ) {
				foreach ( $vals as $v ) {
					add_post_meta( $pid, $k, $v, false );
				}
			}
			if ( ! empty( $p['categories'] ) ) {
				wp_set_object_terms( $pid, $p['categories'], 'dame_agenda_category' );
			}
		}

		// 4. RESTORE VOTES.
		$votes = $data['benevolat_votes'] ?? $data['poll_votes'] ?? array();
		foreach ( $votes as $vote ) {
			$wpdb->insert(
				"{$wpdb->prefix}dame_benevolat_votes",
				array(
					'poll_id'      => $vote['poll_id'],
					'recipient_id' => $vote['recipient_id'],
					'choice_key'   => $vote['choice_key'],
					'voted_at'     => $vote['voted_at'],
				)
			);
		}

		// 5. REALIGN.
		if ( $max_post_id > 0 ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = %d", $max_post_id + 1 ) );
		}

		// 6. TRIGGER AUTO-UPGRADE IF BACKUP IS OLD.
		$backup_version = $data['version'] ?? '1.0.0';
		update_option( 'dame_plugin_version', $backup_version );
		( new Upgrader() )->check_for_updates();

		$this->add_admin_notice( "Restauration de l'agenda et des appels à bénévoles terminée avec succès (Données mises à jour)." );
	}
}
