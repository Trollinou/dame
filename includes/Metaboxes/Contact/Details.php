<?php
/**
 * Contact Details Metabox.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Metaboxes\Contact;

use WP_Post;
use DAME\Services\Data_Provider;
use DAME\Core\Utils;

/**
 * Class Details
 */
class Details {

	/**
	 * Initialize the metabox.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', [ $this, 'register' ] );
		add_action( 'save_post', [ $this, 'save' ] );
	}

	/**
	 * Register the metabox.
	 */
	public function register(): void {
		add_meta_box(
			'dame_contact_details_metabox',
			__( 'Détails du contact', 'dame' ),
			[ $this, 'render' ],
			'dame_contact',
			'normal',
			'high'
		);
	}

	/**
	 * Render the metabox HTML.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'dame_save_contact_meta', 'dame_contact_meta_nonce' );

		$first_name   = get_post_meta( $post->ID, '_dame_contact_first_name', true );
		$last_name    = get_post_meta( $post->ID, '_dame_contact_last_name', true );
		$organization = get_post_meta( $post->ID, '_dame_contact_organization', true );
		$role         = get_post_meta( $post->ID, '_dame_contact_role', true );
		$email        = get_post_meta( $post->ID, '_dame_contact_email', true );
		$phone        = get_post_meta( $post->ID, '_dame_contact_phone', true );
		$address_1    = get_post_meta( $post->ID, '_dame_contact_address_1', true );
		$address_2    = get_post_meta( $post->ID, '_dame_contact_address_2', true );
		$postcode     = get_post_meta( $post->ID, '_dame_contact_postcode', true );
		$city         = get_post_meta( $post->ID, '_dame_contact_city', true );
		$department   = get_post_meta( $post->ID, '_dame_contact_department', true );
		$region       = get_post_meta( $post->ID, '_dame_contact_region', true );

		$departments = Data_Provider::get_departments();
		$regions     = Data_Provider::get_regions();

		?>
		<table class="form-table">
			<tr>
				<th><label for="dame_contact_first_name"><?php esc_html_e( 'Prénom', 'dame' ); ?></label></th>
				<td>
					<input type="text" name="_dame_contact_first_name" id="dame_contact_first_name" value="<?php echo esc_attr( $first_name ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="dame_contact_last_name"><?php esc_html_e( 'Nom', 'dame' ); ?></label></th>
				<td>
					<input type="text" name="_dame_contact_last_name" id="dame_contact_last_name" value="<?php echo esc_attr( $last_name ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="dame_contact_organization"><?php esc_html_e( 'Organisation', 'dame' ); ?></label></th>
				<td>
					<input type="text" name="_dame_contact_organization" id="dame_contact_organization" value="<?php echo esc_attr( $organization ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="dame_contact_role"><?php esc_html_e( 'Rôle / Fonction', 'dame' ); ?></label></th>
				<td>
					<input type="text" name="_dame_contact_role" id="dame_contact_role" value="<?php echo esc_attr( $role ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="dame_contact_email"><?php esc_html_e( 'Email', 'dame' ); ?></label></th>
				<td>
					<input type="email" name="_dame_contact_email" id="dame_contact_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="dame_contact_phone"><?php esc_html_e( 'Téléphone', 'dame' ); ?></label></th>
				<td>
					<input type="tel" name="_dame_contact_phone" id="dame_contact_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="dame_contact_address_1"><?php esc_html_e( 'Adresse', 'dame' ); ?></label></th>
				<td>
					<div class="dame-autocomplete-wrapper" style="position: relative;">
						<input type="text" name="_dame_contact_address_1" id="dame_contact_address_1" value="<?php echo esc_attr( $address_1 ); ?>" class="regular-text dame-js-address" data-group="contact" autocomplete="off" />
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="dame_contact_address_2"><?php esc_html_e( 'Complément d\'adresse', 'dame' ); ?></label></th>
				<td>
					<input type="text" name="_dame_contact_address_2" id="dame_contact_address_2" value="<?php echo esc_attr( $address_2 ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="dame_contact_postcode"><?php esc_html_e( 'Code Postal / Ville', 'dame' ); ?></label></th>
				<td>
					<div class="dame-inline-fields">
						<input type="text" name="_dame_contact_postcode" id="dame_contact_postcode" value="<?php echo esc_attr( $postcode ); ?>" class="small-text postal-code dame-js-zip" data-group="contact" placeholder="<?php esc_attr_e( 'CP', 'dame' ); ?>" />
						<input type="text" name="_dame_contact_city" id="dame_contact_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text city dame-js-city" data-group="contact" placeholder="<?php esc_attr_e( 'Ville', 'dame' ); ?>" />
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="dame_contact_department"><?php esc_html_e( 'Département', 'dame' ); ?></label></th>
				<td>
					<select name="_dame_contact_department" id="dame_contact_department" class="dame-js-dept" data-group="contact">
						<?php foreach ( $departments as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $department, $code ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="dame_contact_region"><?php esc_html_e( 'Région', 'dame' ); ?></label></th>
				<td>
					<select name="_dame_contact_region" id="dame_contact_region" class="dame-js-region" data-group="contact">
						<?php foreach ( $regions as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $region, $code ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Sauvegarde les données de la metabox.
	 *
	 * @param int $post_id L'ID du contact.
	 */
	public function save( int $post_id ): void {
		// Vérifications de sécurité (Nonce, Autosave, Capacités)
		if ( ! isset( $_POST['dame_contact_meta_nonce'] ) || ! wp_verify_nonce( $_POST['dame_contact_meta_nonce'], 'dame_save_contact_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 1. Traitement spécifique et formatage pour le Nom et le Prénom
		$first_name = isset( $_POST['_dame_contact_first_name'] ) ? sanitize_text_field( $_POST['_dame_contact_first_name'] ) : '';
		$last_name  = isset( $_POST['_dame_contact_last_name'] ) ? sanitize_text_field( $_POST['_dame_contact_last_name'] ) : '';

		if ( isset( $_POST['_dame_contact_first_name'] ) ) {
			$first_name = Utils::format_firstname( $first_name ); // Formatage : "Jean-pierre" -> "Jean-Pierre"
			update_post_meta( $post_id, '_dame_contact_first_name', $first_name );
		}

		if ( isset( $_POST['_dame_contact_last_name'] ) ) {
			$last_name = Utils::format_lastname( $last_name ); // Formatage : "dupont" -> "DUPONT"
			update_post_meta( $post_id, '_dame_contact_last_name', $last_name );
		}

		// 2. Sauvegarde des autres champs standards
		$fields = [
			'_dame_contact_organization',
			'_dame_contact_role',
			'_dame_contact_phone',
			'_dame_contact_address_1',
			'_dame_contact_address_2',
			'_dame_contact_postcode',
			'_dame_contact_city',
			'_dame_contact_department',
			'_dame_contact_region',
		];

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}

		// 3. Sauvegarde spécifique pour l'Email
		if ( isset( $_POST['_dame_contact_email'] ) ) {
			update_post_meta( $post_id, '_dame_contact_email', sanitize_email( $_POST['_dame_contact_email'] ) );
		}

		// 4. Mise à jour automatique du titre natif
		$organization = isset( $_POST['_dame_contact_organization'] ) ? sanitize_text_field( $_POST['_dame_contact_organization'] ) : '';
		$base_name    = trim( $last_name . ' ' . $first_name );

		if ( ! empty( $organization ) ) {
			$new_title = $organization . ( ! empty( $base_name ) ? ' (' . $base_name . ')' : '' );
		} else {
			$new_title = $base_name ?: __( 'Contact sans nom', 'dame' );
		}

		if ( get_the_title( $post_id ) !== $new_title ) {
			// Désactivation temporaire du hook pour éviter la boucle infinie
			remove_action( 'save_post', [ $this, 'save' ] );

			wp_update_post( [
				'ID'         => $post_id,
				'post_title' => $new_title,
				'post_name'  => sanitize_title( $new_title ),
			] );

			// Réactivation du hook
			add_action( 'save_post', [ $this, 'save' ] );
		}
	}
}
