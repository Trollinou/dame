<?php
/**
 * Adherent Actions Controller.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin\Actions;

use WP_Post;
use DAME\Core\Utils;

/**
 * Class Adherent
 */
class Adherent {

	/**
	 * Initialize the actions.
	 */
	public function init(): void {
		add_action( 'admin_post_dame_transform_to_contact', [ $this, 'handle_transformation' ] );
	}

	/**
	 * Handle the transformation of an adherent to a contact.
	 */
	public function handle_transformation(): void {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_die( esc_html__( 'ID d\'adhérent invalide.', 'dame' ) );
		}

		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'dame_transform_contact_' . $post_id ) ) {
			wp_die( esc_html__( 'Vérification de sécurité échouée.', 'dame' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les droits nécessaires pour effectuer cette action.', 'dame' ) );
		}

		$adherent = get_post( $post_id );
		if ( ! $adherent || 'adherent' !== $adherent->post_type ) {
			wp_die( esc_html__( 'L\'article n\'est pas un adhérent valide.', 'dame' ) );
		}

		// 1. Récupération des données de l'adhérent
		$first_name = get_post_meta( $post_id, '_dame_first_name', true );
		$last_name  = get_post_meta( $post_id, '_dame_last_name', true );
		$email      = get_post_meta( $post_id, '_dame_email', true );
		$phone      = get_post_meta( $post_id, '_dame_phone_number', true );
		$address_1  = get_post_meta( $post_id, '_dame_address_1', true );
		$address_2  = get_post_meta( $post_id, '_dame_address_2', true );
		$postcode   = get_post_meta( $post_id, '_dame_postal_code', true );
		$city       = get_post_meta( $post_id, '_dame_city', true );
		$department = get_post_meta( $post_id, '_dame_department', true );
		$region     = get_post_meta( $post_id, '_dame_region', true );

		// 2. Préparation du titre formaté
		$formatted_last_name  = Utils::format_lastname( $last_name );
		$formatted_first_name = Utils::format_firstname( $first_name );
		$new_title            = trim( $formatted_last_name . ' ' . $formatted_first_name );

		if ( empty( $new_title ) ) {
			$new_title = $adherent->post_title;
		}

		// 3. Création du nouveau Contact
		$contact_id = wp_insert_post( [
			'post_title'  => $new_title,
			'post_type'   => 'dame_contact',
			'post_status' => 'publish',
			'post_name'   => sanitize_title( $new_title ),
		] );

		if ( is_wp_error( $contact_id ) ) {
			wp_die( esc_html__( 'Erreur lors de la création de la fiche contact.', 'dame' ) );
		}

		// 4. Migration des métadonnées vers le format Contact
		update_post_meta( $contact_id, '_dame_contact_first_name', $formatted_first_name );
		update_post_meta( $contact_id, '_dame_contact_last_name', $formatted_last_name );
		update_post_meta( $contact_id, '_dame_contact_email', $email );
		update_post_meta( $contact_id, '_dame_contact_phone', $phone );
		update_post_meta( $contact_id, '_dame_contact_address_1', $address_1 );
		update_post_meta( $contact_id, '_dame_contact_address_2', $address_2 );
		update_post_meta( $contact_id, '_dame_contact_postcode', $postcode );
		update_post_meta( $contact_id, '_dame_contact_city', $city );
		update_post_meta( $contact_id, '_dame_contact_department', $department );
		update_post_meta( $contact_id, '_dame_contact_region', $region );

		// 5. Mise à la corbeille de l'adhérent original
		wp_trash_post( $post_id );

		// 6. Redirection vers la fiche contact
		wp_redirect( get_edit_post_link( $contact_id, 'raw' ) );
		exit;
	}
}
