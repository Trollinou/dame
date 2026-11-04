<?php
/**
 * Service for synchronizing Benevolat CPT with standard WordPress posts.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Services;

/**
 * Class BenevolatArticleSync
 */
class BenevolatArticleSync {

	/**
	 * Guard against recursive sync calls.
	 *
	 * @var bool
	 */
	private static bool $is_syncing = false;

	/**
	 * Synchronize a benevolat CPT with a linked standard WordPress article.
	 *
	 * @param int      $benevolat_id The ID of the benevolat post.
	 * @param \WP_Post $post         The post object.
	 */
	public function sync( int $benevolat_id, \WP_Post $post ): void {
		if ( self::$is_syncing ) {
			return;
		}

		if ( 'benevolat' !== $post->post_type ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $benevolat_id ) ) {
			return;
		}

		// Check if sync is requested for this benevolat post.
		$options      = get_option( 'dame_options', array() );
		$options      = is_array( $options ) ? $options : array();
		$default_auto = ! empty( $options['benevolat_auto_publish'] );

		$meta_sync   = get_post_meta( $benevolat_id, '_dame_benevolat_sync_article', true );
		$should_sync = '' === $meta_sync ? $default_auto : (bool) $meta_sync;

		$linked_article_id = (int) get_post_meta( $benevolat_id, '_dame_linked_article_id', true );

		if ( ! $should_sync ) {
			// If sync was disabled, we keep any existing linked article as draft so it does not remain public without intent.
			if ( $linked_article_id > 0 && 'publish' === get_post_status( $linked_article_id ) ) {
				self::$is_syncing = true;
				try {
					wp_update_post(
						array(
							'ID'          => $linked_article_id,
							'post_status' => 'draft',
						)
					);
				} finally {
					self::$is_syncing = false;
				}
			}
			return;
		}

		// Don't auto-publish articles if the benevolat itself is trashed or auto-draft.
		if ( in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
			return;
		}

		self::$is_syncing = true;

		try {
			// 1. Determine slug and prepare post content with shortcode only.
			$slug = (string) get_post_field( 'post_name', $benevolat_id );
			if ( empty( $slug ) || (string) $benevolat_id === $slug ) {
				$slug = sanitize_title( $post->post_title );
				if ( ! empty( $slug ) ) {
					wp_update_post(
						array(
							'ID'        => $benevolat_id,
							'post_name' => $slug,
						)
					);
				}
			}

			// The shortcode already displays the benevolat description, do not duplicate it.
			$content = '[dame_benevolat slug="' . esc_attr( $slug ) . '" id="' . (int) $benevolat_id . '"]';

			// 2. Determine target category.
			$category_id = (int) get_post_meta( $benevolat_id, '_dame_benevolat_category', true );
			if ( $category_id <= 0 ) {
				$category_id = isset( $options['benevolat_default_category'] ) ? (int) $options['benevolat_default_category'] : 0;
			}
			if ( $category_id <= 0 ) {
				$category_id = (int) get_option( 'default_category' );
			}

			// 3. Determine target thumbnail ID.
			$thumbnail_id = (int) get_post_thumbnail_id( $benevolat_id );
			if ( $thumbnail_id <= 0 && ! empty( $options['benevolat_default_thumbnail_id'] ) ) {
				$thumbnail_id = (int) $options['benevolat_default_thumbnail_id'];
			}

			// 4. Check if existing linked article is still valid.
			$article_exists = false;
			if ( $linked_article_id > 0 ) {
				$existing_article = get_post( $linked_article_id );
				if ( $existing_article instanceof \WP_Post && 'post' === $existing_article->post_type ) {
					$article_exists = true;
				}
			}

			// Match the post status (publish, draft, pending, future, private).
			$target_status = in_array( $post->post_status, array( 'publish', 'draft', 'pending', 'future', 'private' ), true )
				? $post->post_status
				: 'draft';

			if ( $article_exists ) {
				wp_update_post(
					array(
						'ID'           => $linked_article_id,
						'post_title'   => $post->post_title,
						'post_name'    => $slug,
						'post_content' => $content,
						'post_status'  => $target_status,
					)
				);
			} else {
				$new_article_id = wp_insert_post(
					array(
						'post_type'    => 'post',
						'post_title'   => $post->post_title,
						'post_name'    => $slug,
						'post_content' => $content,
						'post_status'  => $target_status,
						'post_author'  => (int) $post->post_author,
					)
				);

				if ( ! is_wp_error( $new_article_id ) && $new_article_id > 0 ) {
					$linked_article_id = $new_article_id;
					update_post_meta( $benevolat_id, '_dame_linked_article_id', $linked_article_id );
					update_post_meta( $linked_article_id, '_dame_linked_benevolat_id', $benevolat_id );
				}
			}

			if ( $linked_article_id > 0 ) {
				// Assign category.
				if ( $category_id > 0 ) {
					wp_set_post_categories( $linked_article_id, array( $category_id ) );
				}

				// Assign thumbnail.
				if ( $thumbnail_id > 0 ) {
					set_post_thumbnail( $linked_article_id, $thumbnail_id );
				} else {
					delete_post_thumbnail( $linked_article_id );
				}
			}
		} finally {
			self::$is_syncing = false;
		}
	}

	/**
	 * Trash linked article when benevolat is trashed.
	 *
	 * @param int $benevolat_id The ID of the benevolat post.
	 */
	public function trash_linked_article( int $benevolat_id ): void {
		$linked_article_id = (int) get_post_meta( $benevolat_id, '_dame_linked_article_id', true );
		if ( $linked_article_id > 0 ) {
			wp_trash_post( $linked_article_id );
		}
	}

	/**
	 * Untrash linked article when benevolat is restored.
	 *
	 * @param int $benevolat_id The ID of the benevolat post.
	 */
	public function untrash_linked_article( int $benevolat_id ): void {
		$linked_article_id = (int) get_post_meta( $benevolat_id, '_dame_linked_article_id', true );
		if ( $linked_article_id > 0 ) {
			wp_untrash_post( $linked_article_id );
		}
	}

	/**
	 * Permanently delete linked article when benevolat is deleted.
	 *
	 * @param int $benevolat_id The ID of the benevolat post.
	 */
	public function delete_linked_article( int $benevolat_id ): void {
		$linked_article_id = (int) get_post_meta( $benevolat_id, '_dame_linked_article_id', true );
		if ( $linked_article_id > 0 ) {
			wp_delete_post( $linked_article_id, true );
		}
	}
}
