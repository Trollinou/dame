<?php
/**
 * Site Content Backup & Restore Service.
 *
 * @package DAME\Services\Backup
 */

declare(strict_types=1);

namespace DAME\Services\Backup;

use DAME\Core\Upgrader;

/**
 * Handles JSON backup, restore and daily scheduled backup for site content (Posts, Pages, Menus).
 */
class SiteBackup {
	use NoticeTrait;

	/**
	 * Generate Site Content export data (Posts, Pages, Menus).
	 *
	 * @return array<string, mixed>
	 */
	public function generate_export_data(): array {
		$data       = array(
			'version'        => DAME_VERSION,
			'posts'          => array(),
			'taxonomy_terms' => array(),
		);
		$post_types = array( 'post', 'page', 'nav_menu_item' );

		// 1. Identify and Export Taxonomies.
		$taxonomies = get_object_taxonomies( $post_types );
		foreach ( $taxonomies as $tax ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $tax,
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

					// Export term meta.
					$term_meta = get_term_meta( $t->term_id );
					if ( ! empty( $term_meta ) ) {
						foreach ( $term_meta as $k => $v ) {
							$term_data['meta_data'][ $k ] = maybe_unserialize( $v[0] );
						}
					}

					$data['taxonomy_terms'][ $tax ][] = $term_data;
				}
			}
		}

		// 2. Export Posts.
		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		// Optimisation : Pré-chargement des métadonnées.
		if ( ! empty( $posts ) ) {
			update_meta_cache( 'post', wp_list_pluck( $posts, 'ID' ) );
		}

		foreach ( $posts as $p ) {
			$meta = array();
			foreach ( get_post_meta( $p->ID ) as $k => $vals ) {
				$meta[ $k ] = array_map( 'maybe_unserialize', $vals );
			}

			$tax_relationships = array();
			foreach ( $taxonomies as $tax ) {
				$terms = wp_get_post_terms( $p->ID, $tax, array( 'fields' => 'slugs' ) );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$tax_relationships[ $tax ] = $terms;
				}
			}

			$data['posts'][] = array(
				'ID'            => $p->ID,
				'post_author'   => $p->post_author,
				'post_title'    => $p->post_title,
				'post_content'  => $p->post_content,
				'post_excerpt'  => $p->post_excerpt,
				'post_type'     => $p->post_type,
				'post_status'   => $p->post_status,
				'post_name'     => $p->post_name,
				'post_parent'   => $p->post_parent,
				'post_date'     => $p->post_date,
				'post_date_gmt' => $p->post_date_gmt,
				'menu_order'    => $p->menu_order,
				'meta_data'     => $meta,
				'taxonomies'    => $tax_relationships,
			);
		}

		return $data;
	}

	/**
	 * Export Site Content to JSON GZ.
	 */
	public function export_json(): void {
		$data     = $this->generate_export_data();
		$gz       = gzcompress( (string) wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		$filename = 'dame-site-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		ob_clean();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( (string) $gz ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary stream output.
		echo $gz;
		exit;
	}

	/**
	 * Import Site Content from JSON GZ.
	 */
	public function import_json(): void {
		if ( ! isset( $_POST['dame_site_restore_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_site_restore_nonce'] ) ), 'dame_site_restore_nonce_action' ) ) {
			return;
		}
		if ( ! isset( $_FILES['dame_site_restore_file'] ) || ! is_array( $_FILES['dame_site_restore_file'] ) || empty( $_FILES['dame_site_restore_file']['tmp_name'] ) || ( isset( $_FILES['dame_site_restore_file']['error'] ) && UPLOAD_ERR_OK !== $_FILES['dame_site_restore_file']['error'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded temp file path.
		$tmp_file = sanitize_text_field( wp_unslash( $_FILES['dame_site_restore_file']['tmp_name'] ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local temp file read.
		$json = gzuncompress( (string) file_get_contents( $tmp_file ) );
		$data = json_decode( (string) $json, true );
		if ( ! $data ) {
			return;
		}

		global $wpdb;
		$post_types   = array( 'post', 'page', 'nav_menu_item' );
		$taxonomies   = get_object_taxonomies( $post_types );
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// 1. PURGE EVERYTHING.
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

		foreach ( $taxonomies as $tax ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $tax,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			if ( ! is_wp_error( $terms ) && '' !== $tax ) {
				foreach ( $terms as $tid ) {
					wp_delete_term( (int) $tid, $tax );
				}
			}
		}

		// 2. RESTORE TAXONOMIES (Forcing IDs).
		foreach ( $data['taxonomy_terms'] ?? array() as $tax => $terms ) {
			foreach ( $terms as $t ) {
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

				// Taxonomy relation check.
				if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $tt_id ) ) ) {
					$wpdb->insert(
						$wpdb->term_taxonomy,
						array(
							'term_taxonomy_id' => $tt_id,
							'term_id'          => $term_id,
							'taxonomy'         => $tax,
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
							'taxonomy'    => $tax,
							'description' => $t['description'],
							'parent'      => $t['parent'],
						),
						array( 'term_taxonomy_id' => $tt_id )
					);
				}

				// Restore Term Meta.
				if ( ! empty( $t['meta_data'] ) ) {
					foreach ( $t['meta_data'] as $k => $v ) {
						update_term_meta( $term_id, $k, $v );
					}
				}
			}
		}

		// 3. RESTORE POSTS (Forcing IDs).
		$max_post_id = 0;
		foreach ( $data['posts'] ?? array() as $p ) {
			$pid         = (int) $p['ID'];
			$max_post_id = max( $max_post_id, $pid );

			$post_data = array(
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

			// Restore Meta.
			foreach ( $p['meta_data'] as $k => $vals ) {
				foreach ( $vals as $v ) {
					add_post_meta( $pid, $k, $v, false );
				}
			}

			// Restore Taxonomies.
			foreach ( $p['taxonomies'] ?? array() as $tax => $slugs ) {
				wp_set_object_terms( $pid, $slugs, $tax );
			}
		}

		// 4. REALIGN AUTO_INCREMENT.
		if ( $max_post_id > 0 ) {
			$wpdb->query( $wpdb->prepare( "ALTER TABLE $wpdb->posts AUTO_INCREMENT = %d", $max_post_id + 1 ) );
		}

		// 5. TRIGGER AUTO-UPGRADE IF BACKUP IS OLD.
		$backup_version = $data['version'] ?? '1.0.0';
		update_option( 'dame_plugin_version', $backup_version );
		( new Upgrader() )->check_for_updates();

		$this->add_admin_notice( 'Contenu du site restauré avec succès (Données mises à jour).' );
	}

	/**
	 * Runs the daily scheduled backup job.
	 */
	public function run_scheduled_backup(): void {
		$upload_dir = wp_upload_dir();
		$backup_dir = trailingslashit( $upload_dir['basedir'] ) . 'dame-backups';
		wp_mkdir_p( $backup_dir );

		// Initialize WP_Filesystem.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		// Generate files.
		$data_adherent = ( new AdherentBackup() )->generate_export_data();
		$file_adherent = trailingslashit( $backup_dir ) . 'dame-adherents-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		if ( $wp_filesystem ) {
			$wp_filesystem->put_contents( $file_adherent, (string) gzcompress( (string) wp_json_encode( $data_adherent ) ) );
		}

		$data_agenda = ( new AgendaBackup() )->generate_export_data();
		$file_agenda = trailingslashit( $backup_dir ) . 'dame-agenda-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		if ( $wp_filesystem ) {
			$wp_filesystem->put_contents( $file_agenda, (string) gzcompress( (string) wp_json_encode( $data_agenda ) ) );
		}

		$data_site = $this->generate_export_data();
		$file_site = trailingslashit( $backup_dir ) . 'dame-site-backup-' . wp_date( 'Y-m-d' ) . '.json.gz';
		if ( $wp_filesystem ) {
			$wp_filesystem->put_contents( $file_site, (string) gzcompress( (string) wp_json_encode( $data_site ) ) );
		}

		// Attachments.
		$attachments = array( $file_adherent, $file_agenda, $file_site );
		/**
		 * Filter the attachments included in the scheduled daily backup email.
		 *
		 * @param array<int, string> $attachments List of file paths to attach.
		 * @param string             $backup_dir  Directory where backups are temporarily saved.
		 */
		$attachments = apply_filters( 'dame_scheduled_backup_attachments', $attachments, $backup_dir );

		// Send Email.
		$options = get_option( 'dame_options' );
		$to      = $options['sender_email'] ?? get_option( 'admin_email' );
		if ( $to ) {
			/* translators: %s: Site title */
			$subject = sprintf( __( 'Sauvegarde journalière pour %s', 'dame' ), get_bloginfo( 'name' ) );
			$body    = '<p>' . __( 'Veuillez trouver ci-joint les sauvegardes journalières.', 'dame' ) . '</p>';
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			wp_mail( $to, $subject, $body, $headers, $attachments );
		}

		// Cleanup.
		if ( is_array( $attachments ) ) {
			foreach ( $attachments as $attachment_file ) {
				if ( is_string( $attachment_file ) && file_exists( $attachment_file ) ) {
					wp_delete_file( $attachment_file );
				}
			}
		}
	}
}
