<?php
/**
 * Association Tab.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Admin\Settings\Tabs;

/**
 * Class Association
 */
class Association {

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Association', 'dame' );
	}

	/**
	 * Register settings.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_settings_section(
			'dame_association_section',
			__( "Informations générales & Salle de jeu (Trajets)", 'dame' ),
			array( $this, 'section_callback' ),
			'dame_association_section_group'
		);

		$general_fields = array(
			'assoc_name'        => __( "Nom de l'association", 'dame' ),
			'assoc_ffe_id'      => __( 'Id de référence du club (FFE)', 'dame' ),
			'assoc_rna'         => __( 'Numéro RNA', 'dame' ),
			'assoc_siren'       => __( 'Numéro SIREN', 'dame' ),
			'assoc_email'       => __( 'Courriel officiel', 'dame' ),
			'assoc_website'     => __( 'Site web officiel', 'dame' ),
			'assoc_address_1'   => __( 'Adresse de la salle de jeu', 'dame' ),
			'assoc_address_2'   => __( 'Complément salle de jeu', 'dame' ),
			'assoc_postal_code' => __( 'Code Postal salle de jeu', 'dame' ),
			'assoc_city'        => __( 'Ville salle de jeu', 'dame' ),
			'assoc_latitude'    => __( 'Latitude salle de jeu', 'dame' ),
			'assoc_longitude'   => __( 'Longitude salle de jeu', 'dame' ),
		);

		foreach ( $general_fields as $key => $label ) {
			add_settings_field(
				'dame_' . $key,
				$label,
				array( $this, 'render_field' ),
				'dame_association_section_group',
				'dame_association_section',
				array( 'key' => $key )
			);
		}

		add_settings_section(
			'dame_association_siege_section',
			__( 'Siège social (Documents & Attestations)', 'dame' ),
			array( $this, 'siege_section_callback' ),
			'dame_association_section_group'
		);

		$siege_fields = array(
			'assoc_siege_address_1'   => __( 'Adresse du siège social', 'dame' ),
			'assoc_siege_address_2'   => __( 'Complément d\'adresse', 'dame' ),
			'assoc_siege_postal_code' => __( 'Code Postal du siège', 'dame' ),
			'assoc_siege_city'        => __( 'Ville du siège social', 'dame' ),
		);

		foreach ( $siege_fields as $key => $label ) {
			add_settings_field(
				'dame_' . $key,
				$label,
				array( $this, 'render_field' ),
				'dame_association_section_group',
				'dame_association_siege_section',
				array( 'key' => $key )
			);
		}

		add_settings_section(
			'dame_association_docs_section',
			__( 'Représentant(e) & Cachet officiel', 'dame' ),
			array( $this, 'docs_section_callback' ),
			'dame_association_section_group'
		);

		$docs_fields = array(
			'assoc_rep_name'           => __( 'Représentant(e) légal(e) signataire', 'dame' ),
			'assoc_rep_role'           => __( 'Qualité du signataire', 'dame' ),
			'assoc_logo_id'            => __( "Logo de l'association", 'dame' ),
			'assoc_stamp_signature_id' => __( 'Cachet et signature du club', 'dame' ),
		);

		foreach ( $docs_fields as $key => $label ) {
			add_settings_field(
				'dame_' . $key,
				$label,
				array( $this, 'render_field' ),
				'dame_association_section_group',
				'dame_association_docs_section',
				array( 'key' => $key )
			);
		}
	}

	/**
	 * Enqueue scripts and media for the Association tab.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( string $hook ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'association';
		if ( false === strpos( $hook, 'dame-settings' ) || 'association' !== $tab ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'dame-admin-association',
			\DAME_PLUGIN_URL . 'assets/js/admin-association.js',
			array(),
			\DAME_VERSION,
			true
		);
	}

	/**
	 * Section callback for general info.
	 */
	public function section_callback(): void {
		echo '<p>' . esc_html__( "Saisir ici les informations administratives du club et l'adresse de la salle de jeu (utilisée pour le calcul automatique des distances et temps de trajet des événements).", 'dame' ) . '</p>';
	}

	/**
	 * Section callback for siege social.
	 */
	public function siege_section_callback(): void {
		echo '<p>' . esc_html__( "Saisir ici l'adresse officielle du siège social de l'association. Cette adresse figurera sur les attestations d'adhésion et de paiement ainsi que les documents légaux.", 'dame' ) . '</p>';
	}

	/**
	 * Section callback for official documents.
	 */
	public function docs_section_callback(): void {
		echo '<p>' . esc_html__( 'Ces informations (représentant légal, logo et signature/cachet) sont utilisées pour la génération des attestations de paiement et documents officiels du club.', 'dame' ) . '</p>';
	}

	/**
	 * Render field callback.
	 *
	 * @param array<string, mixed> $args Arguments.
	 */
	public function render_field( $args ): void {
		$key     = $args['key'];
		$options = get_option( 'dame_options' );
		$value   = isset( $options[ $key ] ) ? $options[ $key ] : '';

		if ( in_array( $key, array( 'assoc_logo_id', 'assoc_stamp_signature_id' ), true ) ) {
			$media_id  = ! empty( $value ) ? absint( $value ) : 0;
			$image_url = $media_id > 0 ? (string) wp_get_attachment_image_url( $media_id, 'medium' ) : '';
			$title     = 'assoc_logo_id' === $key
				? __( "Sélectionner le logo de l'association", 'dame' )
				: __( 'Sélectionner le cachet et la signature du club', 'dame' );
			$btn_label = 'assoc_logo_id' === $key
				? __( 'Choisir un logo', 'dame' )
				: __( 'Choisir un cachet / signature', 'dame' );

			echo '<div class="dame-media-uploader-wrapper" data-field="' . esc_attr( $key ) . '">';
			echo '<input type="hidden" id="dame_' . esc_attr( $key ) . '" name="dame_options[' . esc_attr( $key ) . ']" value="' . esc_attr( $media_id > 0 ? (string) $media_id : '' ) . '" class="dame-media-input" />';
			echo '<div class="dame-media-preview" style="margin-bottom: 10px;' . ( empty( $image_url ) ? ' display: none;' : '' ) . '">';
			echo '<img src="' . esc_url( $image_url ) . '" alt="" style="max-height: 120px; max-width: 250px; height: auto; border: 1px solid #ccd0d4; padding: 4px; background: #fff; border-radius: 4px; display: block;" />';
			echo '</div>';
			echo '<button type="button" class="button dame-media-upload-btn" data-title="' . esc_attr( $title ) . '">' . esc_html( $btn_label ) . '</button> ';
			echo '<button type="button" class="button dame-media-remove-btn" style="' . ( empty( $image_url ) ? 'display: none;' : '' ) . ' margin-left: 5px;">' . esc_html__( 'Supprimer', 'dame' ) . '</button>';
			echo '</div>';
			return;
		}

		$wrapper_start = '';
		$wrapper_end   = '';
		$readonly      = '';
		$class         = 'regular-text';
		$type          = 'text';
		$extra_attr    = '';
		$data_group    = 'data-group="settings"';

		if ( 'assoc_ffe_id' === $key ) {
			$type  = 'number';
			$class = 'small-text';
		} elseif ( 'assoc_name' === $key ) {
			$extra_attr = 'placeholder="' . esc_attr( get_bloginfo( 'name' ) ) . '"';
		} elseif ( 'assoc_website' === $key ) {
			$type       = 'url';
			$extra_attr = 'placeholder="' . esc_attr( home_url() ) . '"';
		} elseif ( 'assoc_email' === $key ) {
			$type       = 'email';
			$extra_attr = 'placeholder="contact@exemple.org"';
		} elseif ( 'assoc_rna' === $key ) {
			$class      = 'regular-text';
			$extra_attr = 'placeholder="W392000000"';
		} elseif ( 'assoc_siren' === $key ) {
			$class      = 'regular-text';
			$extra_attr = 'placeholder="123 456 789"';
		} elseif ( 'assoc_rep_name' === $key ) {
			$class      = 'regular-text';
			$extra_attr = 'placeholder="Jean DUPONT"';
		} elseif ( 'assoc_rep_role' === $key ) {
			$class      = 'regular-text';
			$extra_attr = 'placeholder="' . esc_attr__( 'Président(e)', 'dame' ) . '"';
		} elseif ( 'assoc_address_1' === $key ) {
			$wrapper_start = '<div class="dame-autocomplete-wrapper">';
			$wrapper_end   = '</div>';
			$extra_attr    = 'autocomplete="off"';
			$class        .= ' dame-js-address';
		} elseif ( 'assoc_postal_code' === $key ) {
			$class .= ' dame-js-zip';
		} elseif ( 'assoc_city' === $key ) {
			$class .= ' dame-js-city';
		} elseif ( 'assoc_latitude' === $key ) {
			$readonly = 'readonly="readonly"';
			$class   .= ' dame-js-lat';
		} elseif ( 'assoc_longitude' === $key ) {
			$readonly = 'readonly="readonly"';
			$class   .= ' dame-js-long';
		} elseif ( 'assoc_siege_address_1' === $key ) {
			$wrapper_start = '<div class="dame-autocomplete-wrapper">';
			$wrapper_end   = '</div>';
			$extra_attr    = 'autocomplete="off"';
			$class        .= ' dame-js-address';
			$data_group    = 'data-group="siege"';
		} elseif ( 'assoc_siege_postal_code' === $key ) {
			$class     .= ' dame-js-zip';
			$data_group = 'data-group="siege"';
		} elseif ( 'assoc_siege_city' === $key ) {
			$class     .= ' dame-js-city';
			$data_group = 'data-group="siege"';
		} elseif ( 'assoc_siege_address_2' === $key ) {
			$data_group = 'data-group="siege"';
		}

		echo $wrapper_start; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<input type="' . esc_attr( $type ) . '" id="dame_' . esc_attr( $key ) . '" name="dame_options[' . esc_attr( $key ) . ']" value="' . esc_attr( (string) $value ) . '" class="' . esc_attr( $class ) . '" ' . $readonly . ' ' . $extra_attr . ' ' . $data_group . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $wrapper_end; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		do_settings_sections( 'dame_association_section_group' );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array<string, mixed> $input New input.
	 * @param array<string, mixed> $existing_options Existing options.
	 * @return array<string, mixed> Sanitized options.
	 */
	public function sanitize( $input, $existing_options ) {
		$fields = array(
			'assoc_name'               => 'text',
			'assoc_ffe_id'             => 'int',
			'assoc_rna'                => 'text',
			'assoc_siren'              => 'text',
			'assoc_email'              => 'email',
			'assoc_website'            => 'url',
			'assoc_address_1'          => 'text',
			'assoc_address_2'          => 'text',
			'assoc_postal_code'        => 'text',
			'assoc_city'               => 'text',
			'assoc_latitude'           => 'text',
			'assoc_longitude'          => 'text',
			'assoc_siege_address_1'    => 'text',
			'assoc_siege_address_2'    => 'text',
			'assoc_siege_postal_code'  => 'text',
			'assoc_siege_city'         => 'text',
			'assoc_rep_name'           => 'text',
			'assoc_rep_role'           => 'text',
			'assoc_logo_id'            => 'int',
			'assoc_stamp_signature_id' => 'int',
		);

		foreach ( $fields as $field => $type ) {
			if ( isset( $input[ $field ] ) ) {
				if ( 'int' === $type ) {
					$existing_options[ $field ] = absint( $input[ $field ] );
				} elseif ( 'email' === $type ) {
					$existing_options[ $field ] = sanitize_email( (string) $input[ $field ] );
				} elseif ( 'url' === $type ) {
					$existing_options[ $field ] = esc_url_raw( (string) $input[ $field ] );
				} else {
					$existing_options[ $field ] = sanitize_text_field( (string) $input[ $field ] );
				}
			}
		}

		return $existing_options;
	}
}
