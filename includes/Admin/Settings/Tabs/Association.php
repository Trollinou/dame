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

		// 1. Identité légale & Contact
		add_settings_section(
			'dame_association_identity_section',
			__( 'Identité légale & Contact', 'dame' ),
			array( $this, 'identity_section_callback' ),
			'dame_association_identity_group'
		);

		$identity_fields = array(
			'assoc_name'    => __( "Nom de l'association", 'dame' ),
			'assoc_ffe_id'  => __( 'Id de référence du club (FFE)', 'dame' ),
			'assoc_rna'     => __( 'Numéro RNA', 'dame' ),
			'assoc_siren'   => __( 'Numéro SIREN', 'dame' ),
			'assoc_email'   => __( 'Courriel officiel', 'dame' ),
			'assoc_website' => __( 'Site web officiel', 'dame' ),
		);

		foreach ( $identity_fields as $key => $label ) {
			add_settings_field(
				'dame_' . $key,
				$label,
				array( $this, 'render_field' ),
				'dame_association_identity_group',
				'dame_association_identity_section',
				array( 'key' => $key )
			);
		}

		// 2. Siège social (Documents & Attestations)
		add_settings_section(
			'dame_association_siege_section',
			__( 'Siège social (Documents & Attestations)', 'dame' ),
			array( $this, 'siege_section_callback' ),
			'dame_association_siege_group'
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
				'dame_association_siege_group',
				'dame_association_siege_section',
				array( 'key' => $key )
			);
		}

		// 3. Salle de jeu (Trajets & Itinéraires)
		add_settings_section(
			'dame_association_salle_section',
			__( 'Salle de jeu (Trajets & Itinéraires)', 'dame' ),
			array( $this, 'salle_section_callback' ),
			'dame_association_salle_group'
		);

		$salle_fields = array(
			'assoc_address_1'   => __( 'Adresse de la salle de jeu', 'dame' ),
			'assoc_address_2'   => __( 'Complément de la salle', 'dame' ),
			'assoc_postal_code' => __( 'Code Postal de la salle', 'dame' ),
			'assoc_city'        => __( 'Ville de la salle', 'dame' ),
			'assoc_latitude'    => __( 'Latitude salle de jeu', 'dame' ),
			'assoc_longitude'   => __( 'Longitude salle de jeu', 'dame' ),
		);

		foreach ( $salle_fields as $key => $label ) {
			add_settings_field(
				'dame_' . $key,
				$label,
				array( $this, 'render_field' ),
				'dame_association_salle_group',
				'dame_association_salle_section',
				array( 'key' => $key )
			);
		}

		// 4. Représentant(e) légal(e) & Cachet officiel
		add_settings_section(
			'dame_association_docs_section',
			__( 'Représentant(e) & Cachet officiel', 'dame' ),
			array( $this, 'docs_section_callback' ),
			'dame_association_docs_group'
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
				'dame_association_docs_group',
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
	 * Section callback for identity & contact.
	 */
	public function identity_section_callback(): void {
		echo '<p>' . esc_html__( "Renseignez les données officielles de l'association (nom déclaré, immatriculations RNA et SIRET, coordonnées de contact officiel).", 'dame' ) . '</p>';
	}

	/**
	 * Section callback for siege social.
	 */
	public function siege_section_callback(): void {
		echo '<p>' . esc_html__( "Adresse officielle du siège social déclarée en préfecture. Cette adresse figurera sur les attestations d'adhésion et de paiement délivrées aux adhérents.", 'dame' ) . '</p>';
	}

	/**
	 * Section callback for salle de jeu.
	 */
	public function salle_section_callback(): void {
		echo '<p>' . esc_html__( "Adresse des locaux où se déroulent les entraînements et rencontres. Utilisée pour le calcul automatique des distances et temps de trajet des événements de l'agenda.", 'dame' ) . '</p>';
	}

	/**
	 * Section callback for official documents.
	 */
	public function docs_section_callback(): void {
		echo '<p>' . esc_html__( 'Ces informations (signataire, logo du club et signature/cachet) sont apposées sur les documents officiels et attestations générés par le club.', 'dame' ) . '</p>';
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
		echo '<div class="dame-association-cards">';

		// Card 1 : Identité légale & Contact
		echo '<div class="dame-association-card">';
		echo '<div class="dame-card-header">';
		echo '<h3><span class="dashicons dashicons-id-alt"></span> ' . esc_html__( '1. Identité légale & Contact', 'dame' ) . '</h3>';
		echo '<p>' . esc_html__( "Nom officiel déclaré, identifiants administratifs (RNA, SIREN) et canaux de contact du club.", 'dame' ) . '</p>';
		echo '</div>';
		echo '<div class="dame-card-body">';
		echo '<table class="form-table" role="presentation">';
		do_settings_fields( 'dame_association_identity_group', 'dame_association_identity_section' );
		echo '</table>';
		echo '</div>';
		echo '</div>';

		// Card 2 : Siège social (Documents & Attestations)
		echo '<div class="dame-association-card">';
		echo '<div class="dame-card-header">';
		echo '<h3><span class="dashicons dashicons-building"></span> ' . esc_html__( '2. Siège social (Documents & Attestations)', 'dame' ) . '</h3>';
		echo '<p>' . esc_html__( "Adresse officielle du siège de l'association, reportée sur les attestations d'adhésion et pièces officielles.", 'dame' ) . '</p>';
		echo '</div>';
		echo '<div class="dame-card-body">';
		echo '<table class="form-table" role="presentation">';
		do_settings_fields( 'dame_association_siege_group', 'dame_association_siege_section' );
		echo '</table>';
		echo '</div>';
		echo '</div>';

		// Card 3 : Salle de jeu (Trajets & Itinéraires)
		echo '<div class="dame-association-card">';
		echo '<div class="dame-card-header">';
		echo '<h3><span class="dashicons dashicons-location-alt"></span> ' . esc_html__( '3. Salle de jeu (Trajets & Itinéraires)', 'dame' ) . '</h3>';
		echo '<p>' . esc_html__( "Local de pratique pour les entraînements et matchs. Sert de point de départ pour le calcul des itinéraires de l'agenda.", 'dame' ) . '</p>';
		echo '</div>';
		echo '<div class="dame-card-body">';

		// Same address banner
		echo '<div class="dame-same-address-banner">';
		echo '<label for="dame_assoc_same_address">';
		echo '<input type="checkbox" id="dame_assoc_same_address" name="dame_assoc_same_address" value="1" /> ';
		echo esc_html__( 'La salle de jeu est située à la même adresse que le siège social', 'dame' );
		echo '</label>';
		echo '<p>' . esc_html__( 'Cocher cette case recopie automatiquement l\'adresse du siège social vers les champs de la salle de jeu.', 'dame' ) . '</p>';
		echo '</div>';

		echo '<table class="form-table" role="presentation">';
		do_settings_fields( 'dame_association_salle_group', 'dame_association_salle_section' );
		echo '</table>';
		echo '</div>';
		echo '</div>';

		// Card 4 : Représentant(e) légal(e) & Cachet officiel
		echo '<div class="dame-association-card">';
		echo '<div class="dame-card-header">';
		echo '<h3><span class="dashicons dashicons-awards"></span> ' . esc_html__( '4. Représentant(e) & Cachet officiel', 'dame' ) . '</h3>';
		echo '<p>' . esc_html__( "Signataire habilité(e), logo et cachet/signature apposés sur les attestations d'adhésion et documents PDF.", 'dame' ) . '</p>';
		echo '</div>';
		echo '<div class="dame-card-body">';
		echo '<table class="form-table" role="presentation">';
		do_settings_fields( 'dame_association_docs_group', 'dame_association_docs_section' );
		echo '</table>';
		echo '</div>';
		echo '</div>';

		echo '</div>'; // .dame-association-cards
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
