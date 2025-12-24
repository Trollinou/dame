<?php
/**
 * Details Metabox.
 *
 * @package DAME
 */

namespace DAME\Metaboxes\PreInscription;

use DAME\Services\Data_Provider;

/**
 * Class Details
 */
class Details {

	/**
	 * Initialize the metabox.
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_box' ] );
		add_action( 'save_post', [ $this, 'save' ] );
	}

	/**
	 * Add the meta box.
	 */
	public function add_box() {
		add_meta_box(
			'dame_pre_inscription_details_metabox',
			__( 'Détails de la Préinscription', 'dame' ),
			[ $this, 'render' ],
			'dame_pre_inscription',
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render( $post ) {
		wp_nonce_field( 'dame_save_pre_inscription_meta', 'dame_pre_inscription_metabox_nonce' );

		$get_value = function( $field_name ) use ( $post ) {
			return get_post_meta( $post->ID, '_' . $field_name, true );
		};

		$field_groups = array(
			'Informations Adhérent' => array(
				'Nom de naissance'    => array( 'key' => 'dame_birth_name', 'type' => 'text', 'required' => true ),
				'Nom d\'usage'        => array( 'key' => 'dame_last_name', 'type' => 'text', 'required' => false ),
				'Prénom'              => array( 'key' => 'dame_first_name', 'type' => 'text', 'required' => true ),
				'Sexe'                => array( 'key' => 'dame_sexe', 'type' => 'radio', 'options' => array( 'Masculin', 'Féminin', 'Non précisé' ), 'required' => true ),
				'Date de naissance'   => array( 'key' => 'dame_birth_date', 'type' => 'date', 'required' => true ),
				'Lieu de naissance'   => array( 'key' => 'dame_birth_city', 'type' => 'text_autocomplete' ),
				'Numéro de téléphone' => array( 'key' => 'dame_phone_number', 'type' => 'tel' ),
				'Email'               => array( 'key' => 'dame_email', 'type' => 'email' ),
				'Profession'          => array( 'key' => 'dame_profession', 'type' => 'text' ),
				'Adresse'             => array( 'key' => 'dame_address_1', 'type' => 'text_autocomplete' ),
				'Complément'          => array( 'key' => 'dame_address_2', 'type' => 'text' ),
				'Code Postal'         => array( 'key' => 'dame_postal_code', 'type' => 'text' ),
				'Ville'               => array( 'key' => 'dame_city', 'type' => 'text' ),
				'Taille de vêtements' => array( 'key' => 'dame_taille_vetements', 'type' => 'select', 'options' => Data_Provider::get_clothing_sizes() ),
				'Type de licence'     => array(
					'key'     => 'dame_license_type',
					'type'    => 'select',
					'options' => array(
						'A' => __( 'Licence A (Cours + Compétition)', 'dame' ),
						'B' => __( 'Licence B (Jeu libre)', 'dame' ),
					),
				),
				'Document de santé'   => array( 'key' => 'dame_health_document', 'type' => 'select', 'options' => Data_Provider::get_health_document_options() ),
			),
			'Représentant Légal 1' => array(
				'Nom de naissance'         => array( 'key' => 'dame_legal_rep_1_last_name', 'type' => 'text' ),
				'Prénom'                   => array( 'key' => 'dame_legal_rep_1_first_name', 'type' => 'text' ),
				'Date de naissance'        => array( 'key' => 'dame_legal_rep_1_date_naissance', 'type' => 'date' ),
				'Lieu de naissance'        => array( 'key' => 'dame_legal_rep_1_commune_naissance', 'type' => 'text' ),
				'Contrôle d\'honorabilité' => array( 'key' => 'dame_legal_rep_1_honorabilite', 'type' => 'text' ),
				'Numéro de téléphone'      => array( 'key' => 'dame_legal_rep_1_phone', 'type' => 'tel' ),
				'Email'                    => array( 'key' => 'dame_legal_rep_1_email', 'type' => 'email' ),
				'Profession'               => array( 'key' => 'dame_legal_rep_1_profession', 'type' => 'text' ),
				'Adresse'                  => array( 'key' => 'dame_legal_rep_1_address_1', 'type' => 'text_autocomplete' ),
				'Complément'               => array( 'key' => 'dame_legal_rep_1_address_2', 'type' => 'text' ),
				'Code Postal'              => array( 'key' => 'dame_legal_rep_1_postal_code', 'type' => 'text' ),
				'Ville'                    => array( 'key' => 'dame_legal_rep_1_city', 'type' => 'text' ),
			),
			'Représentant Légal 2' => array(
				'Nom de naissance'         => array( 'key' => 'dame_legal_rep_2_last_name', 'type' => 'text' ),
				'Prénom'                   => array( 'key' => 'dame_legal_rep_2_first_name', 'type' => 'text' ),
				'Date de naissance'        => array( 'key' => 'dame_legal_rep_2_date_naissance', 'type' => 'date' ),
				'Lieu de naissance'        => array( 'key' => 'dame_legal_rep_2_commune_naissance', 'type' => 'text' ),
				'Contrôle d\'honorabilité' => array( 'key' => 'dame_legal_rep_2_honorabilite', 'type' => 'text' ),
				'Numéro de téléphone'      => array( 'key' => 'dame_legal_rep_2_phone', 'type' => 'tel' ),
				'Email'                    => array( 'key' => 'dame_legal_rep_2_email', 'type' => 'email' ),
				'Profession'               => array( 'key' => 'dame_legal_rep_2_profession', 'type' => 'text' ),
				'Adresse'                  => array( 'key' => 'dame_legal_rep_2_address_1', 'type' => 'text_autocomplete' ),
				'Complément'               => array( 'key' => 'dame_legal_rep_2_address_2', 'type' => 'text' ),
				'Code Postal'              => array( 'key' => 'dame_legal_rep_2_postal_code', 'type' => 'text' ),
				'Ville'                    => array( 'key' => 'dame_legal_rep_2_city', 'type' => 'text' ),
			),
		);

		foreach ( $field_groups as $group_label => $fields ) {
			echo '<h3>' . esc_html__( $group_label, 'dame' ) . '</h3>';
			echo '<table class="form-table">';
			foreach ( $fields as $label => $config ) {
				$key = $config['key'];
				$value = $get_value( $key );
				$required = isset( $config['required'] ) && $config['required'] ? ' <span class="description">(' . __( 'obligatoire', 'dame' ) . ')</span>' : '';

				echo '<tr>';
				echo '<th><label for="' . esc_attr( $key ) . '">' . esc_html__( $label, 'dame' ) . $required . '</label></th>';
				echo '<td>';

				if ( 'select' === $config['type'] ) {
					echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
					foreach ( $config['options'] as $option_value => $option_label ) {
						// If options is not associative, use label as value
						$val = is_int( $option_value ) ? $option_label : $option_value;
						echo '<option value="' . esc_attr( $val ) . '" ' . selected( $value, $val, false ) . '>' . esc_html( $option_label ) . '</option>';
					}
					echo '</select>';
				} elseif ( 'radio' === $config['type'] ) {
					foreach ( $config['options'] as $option ) {
						echo '<label style="margin-right: 15px;"><input type="radio" name="' . esc_attr( $key ) . '" value="' . esc_attr( $option ) . '" ' . checked( $value, $option, false ) . ' /> ' . esc_html( $option ) . '</label>';
					}
				} else {
					$type = ( 'text_autocomplete' === $config['type'] ) ? 'text' : $config['type'];
					$is_autocomplete = ( 'text_autocomplete' === $config['type'] );

					// Determine classes for autocompletion
					$classes = 'regular-text';
					if ( strpos( $key, 'birth_city' ) !== false || strpos( $key, 'commune_naissance' ) !== false ) {
						$classes .= ' dame-js-birth-city';
					} elseif ( strpos( $key, 'address_1' ) !== false ) {
						$classes .= ' dame-js-address';
					} elseif ( strpos( $key, 'postal_code' ) !== false ) {
						$classes .= ' dame-js-zip';
					} elseif ( strpos( $key, 'city' ) !== false ) {
						$classes .= ' dame-js-city';
					}

					if ( $is_autocomplete ) {
						echo '<div class="dame-autocomplete-wrapper">';
					}
					echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="' . esc_attr( $classes ) . '" />';
					if ( $is_autocomplete ) {
						echo '</div>';
					}
				}

				echo '</td></tr>';
			}
			echo '</table>';
		}
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['dame_pre_inscription_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['dame_pre_inscription_metabox_nonce'], 'dame_save_pre_inscription_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( 'dame_pre_inscription' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Handle Usage Name logic
		if ( empty( $_POST['dame_last_name'] ) && ! empty( $_POST['dame_birth_name'] ) ) {
			$_POST['dame_last_name'] = $_POST['dame_birth_name'];
		}

		// Update Post Title based on names
		$first_name = isset( $_POST['dame_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_first_name'] ) ) : '';
		$last_name  = isset( $_POST['dame_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dame_last_name'] ) ) : '';

		if ( $first_name && $last_name ) {
			$new_title = dame_format_lastname( $last_name ) . ' ' . dame_format_firstname( $first_name );
			if ( get_the_title( $post_id ) !== $new_title ) {
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $new_title,
					)
				);
			}
		}

		// Save all fields
		$all_field_keys = array(
			'dame_first_name', 'dame_last_name', 'dame_birth_name', 'dame_birth_date', 'dame_license_type', 'dame_birth_city', 'dame_sexe', 'dame_profession',
			'dame_email', 'dame_phone_number', 'dame_address_1', 'dame_address_2', 'dame_postal_code', 'dame_city', 'dame_taille_vetements',
			'dame_health_document', 'dame_legal_rep_1_honorabilite', 'dame_legal_rep_2_honorabilite',
			'dame_legal_rep_1_first_name', 'dame_legal_rep_1_last_name', 'dame_legal_rep_1_email', 'dame_legal_rep_1_phone',
			'dame_legal_rep_1_address_1', 'dame_legal_rep_1_address_2', 'dame_legal_rep_1_postal_code', 'dame_legal_rep_1_city', 'dame_legal_rep_1_profession',
			'dame_legal_rep_1_date_naissance', 'dame_legal_rep_1_commune_naissance',
			'dame_legal_rep_2_first_name', 'dame_legal_rep_2_last_name', 'dame_legal_rep_2_email', 'dame_legal_rep_2_phone',
			'dame_legal_rep_2_address_1', 'dame_legal_rep_2_address_2', 'dame_legal_rep_2_postal_code', 'dame_legal_rep_2_city', 'dame_legal_rep_2_profession',
			'dame_legal_rep_2_date_naissance', 'dame_legal_rep_2_commune_naissance',
		);

		foreach ( $all_field_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = strpos( $key, 'email' ) !== false ? sanitize_email( wp_unslash( $_POST[ $key ] ) ) : sanitize_text_field( wp_unslash( $_POST[ $key ] ) );

				// Format names
				if ( 'dame_first_name' === $key || 'dame_legal_rep_1_first_name' === $key || 'dame_legal_rep_2_first_name' === $key ) {
					$value = dame_format_firstname( $value );
				}
				if ( 'dame_last_name' === $key || 'dame_legal_rep_1_last_name' === $key || 'dame_legal_rep_2_last_name' === $key || 'dame_birth_name' === $key ) {
					$value = dame_format_lastname( $value );
				}
				update_post_meta( $post_id, '_' . $key, $value );
			}
		}
	}
}
