<?php
/**
 * Agenda Actions Class.
 *
 * @package DAME\Admin\Actions
 */

namespace DAME\Admin\Actions;

/**
 * Class Agenda
 * Manages custom actions for the Agenda CPT.
 */
class Agenda {

	/**
	 * Initialize the actions.
	 */
	public function init(): void {
		add_action( 'admin_action_dame_duplicate_event', [ $this, 'duplicate_event' ] );
	}

	/**
	 * Handles the event duplication action.
	 */
	public function duplicate_event() {
		if ( ! isset( $_GET['post'] ) || ! isset( $_GET['dame_duplicate_nonce'] ) ) {
			wp_die( esc_html__( 'Argument manquant.', 'dame' ) );
		}

		$post_id = absint( $_GET['post'] );
		$nonce   = sanitize_key( $_GET['dame_duplicate_nonce'] );

		if ( ! wp_verify_nonce( $nonce, 'dame_duplicate_event_nonce_' . $post_id ) ) {
			wp_die( esc_html__( 'La vérification de sécurité a échoué.', 'dame' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas la permission de dupliquer cet événement.', 'dame' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( esc_html__( 'Événement non trouvé.', 'dame' ) );
		}

		$new_post_author = wp_get_current_user();

		$new_post_args = array(
			'post_author'    => $new_post_author->ID,
			'post_content'   => $post->post_content,
			'post_status'    => 'draft', // Set new post to draft
			'post_title'     => $post->post_title . ' (Copie)',
			'post_type'      => $post->post_type,
		);

		// Temporarily remove the save hook to prevent it from firing with empty $_POST data.
		// NOTE: In the new architecture, we need to remove the method from the class instance.
		// However, since we don't have easy access to the exact instance of Manager here,
		// we rely on the fact that the action hook string is 'save_post_dame_agenda'.
		// But Wait! 'remove_action' with an object method requires the exact SAME object instance.
		// Since 'Agenda Metbox Manager' is instantiated in Plugin::run(), we can't easily access it here to remove the hook.
		//
		// Strategy: The save method in Manager checks for 'dame_agenda_metabox_nonce'.
		// Since we are not submitting a form with that nonce here (we are doing a GET request for duplication),
		// the nonce check in Manager::save() will fail and it will return early.
		// So we don't actually need to remove the hook! The 'save' logic is safe.

		$new_post_id = wp_insert_post( $new_post_args );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( $new_post_id->get_error_message() );
		}

		// Duplicate post meta (Bulk Insert for N+1 optimization).
		$all_meta = get_post_meta( $post_id );
		$keys_to_skip = array( '_dame_start_date', '_dame_end_date' );

		if ( ! empty( $all_meta ) ) {
			global $wpdb;
			$meta_insert_values = [];
			$meta_insert_placeholders = [];

			foreach ( $all_meta as $meta_key => $meta_values ) {
				// Skip protected meta, but allow our own '_dame_' meta.
				if ( is_protected_meta( $meta_key ) && strpos( $meta_key, '_dame_' ) !== 0 ) {
					continue;
				}
				// Skip the date fields as requested by the user for a better workflow.
				if ( in_array( $meta_key, $keys_to_skip, true ) ) {
					continue;
				}

				foreach ( $meta_values as $meta_value ) {
					// get_post_meta returns an array of string values (even for serialized arrays).
					// To correctly bulk insert, we insert them exactly as they are without re-serializing.
					$meta_insert_values[] = $new_post_id;
					$meta_insert_values[] = $meta_key;
					$meta_insert_values[] = maybe_unserialize($meta_value) !== $meta_value ? $meta_value : maybe_serialize($meta_value);
					$meta_insert_placeholders[] = '(%d, %s, %s)';
				}
			}

			if ( ! empty( $meta_insert_placeholders ) ) {
				$query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ', ', $meta_insert_placeholders );
				$wpdb->query( $wpdb->prepare( $query, $meta_insert_values ) );
			}
		}

		// Duplicate taxonomies.
		$taxonomies = get_object_taxonomies( $post->post_type );
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					wp_set_object_terms( $new_post_id, $terms, $taxonomy, false );
				}
			}
		}

		// Redirect to the edit screen for the new draft.
		$redirect_url = get_edit_post_link( $new_post_id, 'raw' );
		wp_redirect( $redirect_url );
		exit;
	}
}
