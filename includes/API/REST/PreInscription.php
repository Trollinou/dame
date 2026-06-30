<?php
/**
 * REST API Pre-inscription Endpoint.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\API\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;
use WP_Query;
use DateTime;
use Exception;
use DAME\Services\PDF_Generator;
use DAME\Core\Utils;
use DAME\Services\Data_Provider;

/**
 * Class PreInscription
 * Handles pre-inscription operations for PWA.
 */
class PreInscription {

	/**
	 * Namespace for the API.
	 *
	 * @var string
	 */
	protected string $namespace = 'dame/v1';

	/**
	 * Base path for the resource.
	 *
	 * @var string
	 */
	protected string $rest_base = 'pre-inscription';

	/**
	 * Initialize the class and register hooks.
	 */
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the REST API routes.
	 */
	public function register_routes(): void {
		// Submit pre-inscription
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_submission' ],
					'permission_callback' => '__return_true',
				],
			]
		);

		// Get details of a member for pre-filling
		register_rest_route(
			$this->namespace,
			'/adherent-details',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_adherent_details' ],
					'permission_callback' => [ $this, 'check_user_logged_in' ],
				],
			]
		);

		// Secure PDF download endpoints
		register_rest_route(
			$this->namespace,
			'/pre-inscriptions/(?P<id>\d+)/pdf/health',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'generate_health_pdf' ],
					'permission_callback' => [ $this, 'check_pdf_access' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/pre-inscriptions/(?P<id>\d+)/pdf/parental',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'generate_parental_pdf' ],
					'permission_callback' => [ $this, 'check_pdf_access' ],
				],
			]
		);
	}

	/**
	 * Check if user is logged in.
	 */
	public function check_user_logged_in(): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Vous devez être connecté.', 'dame' ),
				[ 'status' => 401 ]
			);
		}
		return true;
	}

	/**
	 * Permission check for PDF access.
	 * Access is granted if:
	 * 1. The request provides the correct download token matching the post meta.
	 * 2. OR the user is logged in and is the owner/parent or an administrator.
	 */
	public function check_pdf_access( WP_REST_Request $request ): bool|WP_Error {
		$post_id = (int) $request['id'];
		$token   = $request->get_param( 'token' );

		// 1. Token validation (anonymous success download)
		if ( ! empty( $token ) ) {
			$saved_token = get_post_meta( $post_id, '_dame_download_token', true );
			if ( $token === $saved_token ) {
				return true;
			}
		}

		// 2. Logged-in user validation
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$email        = $current_user->user_email;

			// Admins / staff can access
			$allowed_roles = [ 'staff', 'entraineur', 'editor', 'administrator' ];
			if ( array_intersect( $allowed_roles, (array) $current_user->roles ) ) {
				return true;
			}

			// Adherent or legal reps matching the pre-inscription email can access
			$adh_email = get_post_meta( $post_id, '_dame_email', true );
			$rep1_email = get_post_meta( $post_id, '_dame_legal_rep_1_email', true );
			$rep2_email = get_post_meta( $post_id, '_dame_legal_rep_2_email', true );

			if ( ! empty( $email ) && ( $email === $adh_email || $email === $rep1_email || $email === $rep2_email ) ) {
				return true;
			}
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Accès non autorisé à ce document.', 'dame' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Get details of an adherent to prefill registration.
	 */
	public function get_adherent_details( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$adherent_id = (int) $request->get_param( 'adherent_id' );
		if ( ! $adherent_id ) {
			return new WP_Error( 'missing_param', __( 'ID adhérent manquant.', 'dame' ), [ 'status' => 400 ] );
		}

		$current_user = wp_get_current_user();
		$email        = $current_user->user_email;
		$is_admin     = array_intersect( [ 'staff', 'entraineur', 'editor', 'administrator' ], (array) $current_user->roles );

		if ( ! $is_admin ) {
			// Verify access
			$rep_1     = get_post_meta( $adherent_id, '_dame_legal_rep_1_email', true );
			$rep_2     = get_post_meta( $adherent_id, '_dame_legal_rep_2_email', true );
			$adh_email = get_post_meta( $adherent_id, '_dame_email', true );

			if ( empty( $email ) || ( $email !== $rep_1 && $email !== $rep_2 && $email !== $adh_email ) ) {
				return new WP_Error( 'forbidden', __( 'Vous n\'avez pas accès à cet adhérent.', 'dame' ), [ 'status' => 403 ] );
			}
		}

		// Retrieve all metadata
		$meta_keys = [
			'first_name', 'last_name', 'birth_name', 'birth_date', 'birth_city', 'sexe', 'profession',
			'email', 'phone_number', 'address_1', 'address_2', 'postal_code', 'city', 'taille_vetements',
			'license_type',
			'legal_rep_1_first_name', 'legal_rep_1_last_name', 'legal_rep_1_email', 'legal_rep_1_phone',
			'legal_rep_1_address_1', 'legal_rep_1_address_2', 'legal_rep_1_postal_code', 'legal_rep_1_city', 'legal_rep_1_profession',
			'legal_rep_1_date_naissance', 'legal_rep_1_commune_naissance',
			'legal_rep_2_first_name', 'legal_rep_2_last_name', 'legal_rep_2_email', 'legal_rep_2_phone',
			'legal_rep_2_address_1', 'legal_rep_2_address_2', 'legal_rep_2_postal_code', 'legal_rep_2_city', 'legal_rep_2_profession',
			'legal_rep_2_date_naissance', 'legal_rep_2_commune_naissance',
		];

		$details = [];
		foreach ( $meta_keys as $key ) {
			$details[ $key ] = get_post_meta( $adherent_id, '_dame_' . $key, true );
		}

		return rest_ensure_response( $details );
	}

	/**
	 * Handle pre-inscription submission.
	 */
	public function handle_submission( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$params = $request->get_params();

		// If usage name is empty, copy birth name into it.
		if ( empty( $params['dame_last_name'] ) && ! empty( $params['dame_birth_name'] ) ) {
			$params['dame_last_name'] = $params['dame_birth_name'];
		}

		// Validation
		$errors = [];
		$required_fields = [
			'dame_first_name'           => __( 'Le prénom est obligatoire.', 'dame' ),
			'dame_birth_name'           => __( 'Le nom de naissance est obligatoire.', 'dame' ),
			'dame_birth_date'           => __( 'La date de naissance est obligatoire.', 'dame' ),
			'dame_license_type'         => __( 'Le type de licence est obligatoire.', 'dame' ),
			'dame_sexe'                 => __( 'Le sexe est obligatoire.', 'dame' ),
			'dame_email'                => __( 'L\'email est obligatoire.', 'dame' ),
			'dame_phone_number'         => __( 'Le numéro de téléphone est obligatoire.', 'dame' ),
			'dame_address_1'            => __( 'L\'adresse est obligatoire.', 'dame' ),
			'dame_city'                 => __( 'La ville est obligatoire.', 'dame' ),
			'dame_health_questionnaire' => __( 'La réponse au questionnaire de santé est obligatoire.', 'dame' ),
			'dame_consent_checkbox'     => __( 'Vous devez accepter le règlement intérieur.', 'dame' ),
		];

		foreach ( $required_fields as $field_key => $error_message ) {
			if ( empty( $params[ $field_key ] ) ) {
				$errors[] = $error_message;
			}
		}

		// Conditional validation for minors
		if ( ! empty( $params['dame_birth_date'] ) ) {
			$birth_date = DateTime::createFromFormat( 'Y-m-d', $params['dame_birth_date'] );
			if ( $birth_date ) {
				$today = new DateTime();
				$age   = $today->diff( $birth_date )->y;

				if ( $age < 18 ) {
					$rep1_required_fields = [
						'dame_legal_rep_1_first_name' => __( 'Le prénom du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_last_name'  => __( 'Le nom de naissance du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_email'      => __( 'L\'email du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_phone'      => __( 'Le téléphone du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_address_1'  => __( 'L\'adresse du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_city'       => __( 'La ville du représentant légal 1 est obligatoire.', 'dame' ),
					];

					foreach ( $rep1_required_fields as $field_key => $error_message ) {
						if ( empty( $params[ $field_key ] ) ) {
							$errors[] = $error_message;
						}
					}
				} else {
					// For adults, birth city is required for the honorability check.
					if ( empty( $params['dame_birth_city'] ) ) {
						$errors[] = __( 'La commune de naissance est obligatoire pour les personnes majeures.', 'dame' );
					}
				}
			}
		}

		// Email format validation
		if ( ! empty( $params['dame_email'] ) && ! is_email( $params['dame_email'] ) ) {
			$errors[] = __( 'L\'adresse email de l\'adhérent n\'est pas valide.', 'dame' );
		}
		if ( ! empty( $params['dame_legal_rep_1_email'] ) && ! is_email( $params['dame_legal_rep_1_email'] ) ) {
			$errors[] = __( 'L\'adresse email du représentant légal 1 n\'est pas valide.', 'dame' );
		}
		if ( ! empty( $params['dame_legal_rep_2_email'] ) && ! is_email( $params['dame_legal_rep_2_email'] ) ) {
			$errors[] = __( 'L\'adresse email du représentant légal 2 n\'est pas valide.', 'dame' );
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'validation_failed', implode( '<br>', $errors ), [ 'status' => 400 ] );
		}

		// Sanitize Data
		$sanitized_data     = [];
		$fields_to_sanitize = [
			'dame_first_name', 'dame_last_name', 'dame_birth_name', 'dame_birth_date', 'dame_license_type', 'dame_birth_city', 'dame_sexe', 'dame_profession',
			'dame_email', 'dame_phone_number', 'dame_address_1', 'dame_address_2', 'dame_postal_code', 'dame_city', 'dame_taille_vetements',
			'dame_legal_rep_1_first_name', 'dame_legal_rep_1_last_name', 'dame_legal_rep_1_email', 'dame_legal_rep_1_phone',
			'dame_legal_rep_1_address_1', 'dame_legal_rep_1_address_2', 'dame_legal_rep_1_postal_code', 'dame_legal_rep_1_city', 'dame_legal_rep_1_profession',
			'dame_legal_rep_1_date_naissance', 'dame_legal_rep_1_commune_naissance',
			'dame_legal_rep_2_first_name', 'dame_legal_rep_2_last_name', 'dame_legal_rep_2_email', 'dame_legal_rep_2_phone',
			'dame_legal_rep_2_address_1', 'dame_legal_rep_2_address_2', 'dame_legal_rep_2_postal_code', 'dame_legal_rep_2_city', 'dame_legal_rep_2_profession',
			'dame_legal_rep_2_date_naissance', 'dame_legal_rep_2_commune_naissance',
			'dame_health_questionnaire',
		];

		foreach ( $fields_to_sanitize as $field ) {
			if ( isset( $params[ $field ] ) ) {
				if ( strpos( $field, 'email' ) !== false ) {
					$sanitized_data[ $field ] = sanitize_email( wp_unslash( (string) $params[ $field ] ) );
				} else {
					$sanitized_data[ $field ] = sanitize_text_field( wp_unslash( (string) $params[ $field ] ) );
				}
			}
		}

		// Communication Preferences
		$accept_comms = ( isset( $params['dame_accept_comms'] ) && (bool) $params['dame_accept_comms'] ) ? '0' : '1';
		$sanitized_data['dame_email_refuses_comms'] = $accept_comms;
		$sanitized_data['dame_legal_rep_1_email_refuses_comms'] = $accept_comms;
		$sanitized_data['dame_legal_rep_2_email_refuses_comms'] = $accept_comms;

		// Format names
		if ( ! empty( $sanitized_data['dame_first_name'] ) ) {
			$sanitized_data['dame_first_name'] = Utils::format_firstname( $sanitized_data['dame_first_name'] );
		}
		if ( ! empty( $sanitized_data['dame_last_name'] ) ) {
			$sanitized_data['dame_last_name'] = Utils::format_lastname( $sanitized_data['dame_last_name'] );
		}
		if ( ! empty( $sanitized_data['dame_birth_name'] ) ) {
			$sanitized_data['dame_birth_name'] = Utils::format_lastname( $sanitized_data['dame_birth_name'] );
		}
		if ( ! empty( $sanitized_data['dame_legal_rep_1_first_name'] ) ) {
			$sanitized_data['dame_legal_rep_1_first_name'] = Utils::format_firstname( $sanitized_data['dame_legal_rep_1_first_name'] );
		}
		if ( ! empty( $sanitized_data['dame_legal_rep_1_last_name'] ) ) {
			$sanitized_data['dame_legal_rep_1_last_name'] = Utils::format_lastname( $sanitized_data['dame_legal_rep_1_last_name'] );
		}
		if ( ! empty( $sanitized_data['dame_legal_rep_2_first_name'] ) ) {
			$sanitized_data['dame_legal_rep_2_first_name'] = Utils::format_firstname( $sanitized_data['dame_legal_rep_2_first_name'] );
		}
		if ( ! empty( $sanitized_data['dame_legal_rep_2_last_name'] ) ) {
			$sanitized_data['dame_legal_rep_2_last_name'] = Utils::format_lastname( $sanitized_data['dame_legal_rep_2_last_name'] );
		}

		$is_minor = false;
		if ( isset( $sanitized_data['dame_birth_date'] ) ) {
			$birth_date = DateTime::createFromFormat( 'Y-m-d', $sanitized_data['dame_birth_date'] );
			if ( $birth_date ) {
				$today    = new DateTime();
				$age      = $today->diff( $birth_date )->y;
				$is_minor = ( $age < 18 );

				if ( ! $is_minor ) {
					foreach ( $sanitized_data as $key => $value ) {
						if ( strpos( $key, 'dame_legal_rep_' ) === 0 ) {
							unset( $sanitized_data[ $key ] );
						}
					}
				}
			}
		}

		// Create Pre-inscription Post
		$effective_last_name = ! empty( $sanitized_data['dame_last_name'] ) ? $sanitized_data['dame_last_name'] : $sanitized_data['dame_birth_name'];
		$post_title          = Utils::format_lastname( (string) $effective_last_name ) . ' ' . Utils::format_firstname( (string) $sanitized_data['dame_first_name'] );

		$post_data = [
			'post_title'  => $post_title,
			'post_type'   => 'dame_pre_inscription',
			'post_status' => 'pending',
		];
		$post_id   = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'post_creation_failed', __( 'Erreur lors de la création de la préinscription.', 'dame' ), [ 'status' => 500 ] );
		}

		// Generate secure download token
		$download_token = wp_generate_password( 32, false );
		update_post_meta( $post_id, '_dame_download_token', $download_token );

		// Save Meta Data
		global $wpdb;
		$meta_insert_values       = [];
		$meta_insert_placeholders = [];

		foreach ( $sanitized_data as $key => $value ) {
			if ( 'dame_health_questionnaire' === $key ) {
				continue;
			}
			$meta_insert_values[]       = $post_id;
			$meta_insert_values[]       = '_' . $key;
			$meta_insert_values[]       = maybe_serialize( $value );
			$meta_insert_placeholders[] = '(%d, %s, %s)';
		}

		$health_document_status = 'none';
		if ( isset( $sanitized_data['dame_health_questionnaire'] ) ) {
			if ( 'oui' === $sanitized_data['dame_health_questionnaire'] ) {
				$health_document_status = 'certificate';
			} elseif ( 'non' === $sanitized_data['dame_health_questionnaire'] ) {
				$health_document_status = 'attestation';
			}
		}

		$meta_insert_values[]       = $post_id;
		$meta_insert_values[]       = '_dame_health_document';
		$meta_insert_values[]       = $health_document_status;
		$meta_insert_placeholders[] = '(%d, %s, %s)';

		$query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ', ', $meta_insert_placeholders );
		$wpdb->query( $wpdb->prepare( $query, $meta_insert_values ) );

		// Send Email Notification
		$options         = get_option( 'dame_options' );
		$recipient_email = isset( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

		$subject  = 'Nouvelle préinscription de ' . $sanitized_data['dame_first_name'] . ' ' . $sanitized_data['dame_last_name'];
		$body     = "Une nouvelle demande de préinscription a été soumise depuis la PWA.\n\n";
		$body    .= "Voici les détails :\n";
		foreach ( $sanitized_data as $key => $value ) {
			if ( ! empty( $value ) ) {
				$label  = str_replace( [ 'dame_', '_' ], [ '', ' ' ], $key );
				$label  = mb_convert_case( $label, MB_CASE_TITLE, 'UTF-8' );
				$body  .= '- ' . $label . ': ' . $value . "\n";
			}
		}
		$headers = [ 'From: ' . $recipient_email ];
		wp_mail( $recipient_email, $subject, $body, $headers );

		$payment_url  = isset( $options['payment_url'] ) ? $options['payment_url'] : '';
		$sender_email = isset( $options['sender_email'] ) && ! empty( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

		return rest_ensure_response(
			[
				'success'              => true,
				'message'              => sprintf(
					__( 'La préinscription pour %s %s a bien été enregistrée.', 'dame' ),
					$sanitized_data['dame_first_name'],
					$sanitized_data['dame_last_name']
				),
				'post_id'              => $post_id,
				'download_token'       => $download_token,
				'health_questionnaire' => $sanitized_data['dame_health_questionnaire'],
				'is_minor'             => $is_minor,
				'payment_url'          => $payment_url,
				'sender_email'         => $sender_email,
			]
		);
	}

	/**
	 * Generate Health PDF.
	 */
	public function generate_health_pdf( WP_REST_Request $request ): void {
		$post_id = (int) $request['id'];

		// We need to inject variables that PDF_Generator expects in $_GET or rewrite the call
		$_GET['post_id'] = $post_id;

		// Bypass nonce check by setting FPDF directly or mock $_GET['_wpnonce'] if needed
		$this->output_health_form( $post_id );
	}

	/**
	 * Generate Parental PDF.
	 */
	public function generate_parental_pdf( WP_REST_Request $request ): void {
		$post_id = (int) $request['id'];
		$this->output_parental_auth( $post_id );
	}

	/**
	 * Fpdi logic for Health Form.
	 */
	private function output_health_form( int $post_id ): void {
		$first_name     = get_post_meta( $post_id, '_dame_first_name', true );
		$last_name      = get_post_meta( $post_id, '_dame_last_name', true );
		$birth_date_str = get_post_meta( $post_id, '_dame_birth_date', true );
		$city           = get_post_meta( $post_id, '_dame_city', true );

		$legal_rep_1_first_name = get_post_meta( $post_id, '_dame_legal_rep_1_first_name', true );
		$legal_rep_1_last_name  = get_post_meta( $post_id, '_dame_legal_rep_1_last_name', true );
		$legal_rep_1_city       = get_post_meta( $post_id, '_dame_legal_rep_1_city', true );

		if ( empty( $first_name ) || empty( $last_name ) || empty( $birth_date_str ) || empty( $city ) ) {
			wp_die( __( 'Données de préinscription manquantes ou invalides.', 'dame' ), 404 );
		}

		$birth_date = DateTime::createFromFormat( 'Y-m-d', $birth_date_str );
		$today      = new DateTime();
		$age        = $today->diff( $birth_date )->y;

		$full_name_adherent_for_pdf = Utils::generate_adherent_title( $post_id );
		$current_date               = wp_date( 'd/m/Y' );

		$full_name_adherent_for_pdf = mb_convert_encoding( $full_name_adherent_for_pdf, 'ISO-8859-1', 'UTF-8' );
		$city_for_pdf               = mb_convert_encoding( $city, 'ISO-8859-1', 'UTF-8' );

		if ( ! class_exists( 'setasign\Fpdi\Fpdi' ) ) {
			$lib_dir = DAME_PLUGIN_DIR . 'includes/lib/';
			if ( file_exists( $lib_dir . 'fpdf/fpdf.php' ) ) {
				require_once $lib_dir . 'fpdf/fpdf.php';
			}
			if ( file_exists( $lib_dir . 'fpdi/src/autoload.php' ) ) {
				require_once $lib_dir . 'fpdi/src/autoload.php';
			}
		}

		$pdf = new \setasign\Fpdi\Fpdi();
		$pdf->AddPage();

		try {
			$template_path = DAME_PLUGIN_DIR . 'assets/pdf/ffe_attestation_sante.pdf';
			$pdf->setSourceFile( $template_path );
			$tplId = $pdf->importPage( 1 );
			$pdf->useTemplate( $tplId, 0, 0, 210, 297 );
		} catch ( Exception $e ) {
			wp_die( sprintf( __( 'Erreur lors du chargement du template PDF : %s', 'dame' ), $e->getMessage() ), 500 );
		}

		$pdf->SetFont( 'Helvetica' );
		$pdf->SetTextColor( 0, 0, 0 );

		if ( $age >= 18 ) {
			$pdf->SetXY( 54, 128 );
			$pdf->Write( 0, $full_name_adherent_for_pdf );
			$pdf->SetXY( 32, 156 );
			$pdf->Write( 0, $current_date );
			$pdf->SetXY( 62, 156 );
			$pdf->Write( 0, $city_for_pdf );
		} else {
			if ( empty( $legal_rep_1_first_name ) || empty( $legal_rep_1_last_name ) || empty( $legal_rep_1_city ) ) {
				wp_die( __( 'Données du représentant légal manquantes pour un adhérent mineur.', 'dame' ), 400 );
			}
			$full_name_rep1_for_pdf   = Utils::format_lastname( (string) $legal_rep_1_last_name ) . ' ' . Utils::format_firstname( (string) $legal_rep_1_first_name );
			$full_name_rep1_for_pdf   = mb_convert_encoding( $full_name_rep1_for_pdf, 'ISO-8859-1', 'UTF-8' );
			$legal_rep_1_city_for_pdf = mb_convert_encoding( $legal_rep_1_city, 'ISO-8859-1', 'UTF-8' );

			$pdf->SetXY( 54, 181 );
			$pdf->Write( 0, $full_name_rep1_for_pdf );
			$pdf->SetXY( 119, 190 );
			$pdf->Write( 0, $full_name_adherent_for_pdf );
			$pdf->SetXY( 32, 227 );
			$pdf->Write( 0, $current_date );
			$pdf->SetXY( 62, 227 );
			$pdf->Write( 0, $legal_rep_1_city_for_pdf );
		}

		$filename = sanitize_file_name( 'attestation_sante_' . $last_name . '_' . $first_name . '.pdf' );
		$pdf->Output( 'D', $filename );
		exit;
	}

	/**
	 * Fpdi logic for Parental Auth.
	 */
	private function output_parental_auth( int $post_id ): void {
		$first_name     = get_post_meta( $post_id, '_dame_first_name', true );
		$last_name      = get_post_meta( $post_id, '_dame_last_name', true );
		$birth_date_str = get_post_meta( $post_id, '_dame_birth_date', true );
		$city           = get_post_meta( $post_id, '_dame_city', true );

		$rl1_first_name  = get_post_meta( $post_id, '_dame_legal_rep_1_first_name', true );
		$rl1_last_name   = get_post_meta( $post_id, '_dame_legal_rep_1_last_name', true );
		$rl1_birth_date  = get_post_meta( $post_id, '_dame_legal_rep_1_date_naissance', true );
		$rl1_birth_place = get_post_meta( $post_id, '_dame_legal_rep_1_commune_naissance', true );
		$rl1_profession  = get_post_meta( $post_id, '_dame_legal_rep_1_profession', true );

		$rl2_first_name  = get_post_meta( $post_id, '_dame_legal_rep_2_first_name', true );
		$rl2_last_name   = get_post_meta( $post_id, '_dame_legal_rep_2_last_name', true );
		$rl2_birth_date  = get_post_meta( $post_id, '_dame_legal_rep_2_date_naissance', true );
		$rl2_birth_place = get_post_meta( $post_id, '_dame_legal_rep_2_commune_naissance', true );
		$rl2_profession  = get_post_meta( $post_id, '_dame_legal_rep_2_profession', true );

		if ( empty( $first_name ) || empty( $last_name ) || empty( $birth_date_str ) ) {
			wp_die( __( 'Données de préinscription de l\'adhérent manquantes ou invalides.', 'dame' ), 404 );
		}

		$adherent_full_name            = mb_convert_encoding( Utils::generate_adherent_title( $post_id ), 'ISO-8859-1', 'UTF-8' );
		$adherent_birth_date_formatted = mb_convert_encoding( wp_date( 'd/m/Y', strtotime( $birth_date_str ), new \DateTimeZone( 'UTC' ) ), 'ISO-8859-1', 'UTF-8' );
		$adherent_city                 = mb_convert_encoding( (string) $city, 'ISO-8859-1', 'UTF-8' );
		$current_date                  = wp_date( 'd/m/Y' );
		$rl1_full_name                 = '';
		if ( ! empty( $rl1_first_name ) && ! empty( $rl1_last_name ) ) {
			$rl1_full_name = mb_convert_encoding( Utils::format_lastname( (string) $rl1_last_name ) . ' ' . Utils::format_firstname( (string) $rl1_first_name ), 'ISO-8859-1', 'UTF-8' );
		}

		if ( ! class_exists( 'setasign\Fpdi\Fpdi' ) ) {
			$lib_dir = DAME_PLUGIN_DIR . 'includes/lib/';
			if ( file_exists( $lib_dir . 'fpdf/fpdf.php' ) ) {
				require_once $lib_dir . 'fpdf/fpdf.php';
			}
			if ( file_exists( $lib_dir . 'fpdi/src/autoload.php' ) ) {
				require_once $lib_dir . 'fpdi/src/autoload.php';
			}
		}

		$pdf = new \setasign\Fpdi\Fpdi();
		$pdf->AddPage();
		$pdf->SetAutoPageBreak( true, 0 );

		try {
			$template_path = DAME_PLUGIN_DIR . 'assets/pdf/el_autorisation_parentale.pdf';
			$pdf->setSourceFile( $template_path );
			$tplId = $pdf->importPage( 1 );
			$pdf->useTemplate( $tplId, 0, 0, 210, 297 );
		} catch ( Exception $e ) {
			wp_die( sprintf( __( 'Erreur lors du chargement du template PDF : %s', 'dame' ), $e->getMessage() ), 500 );
		}

		$pdf->SetFont( 'Helvetica', '', 12 );
		$pdf->SetTextColor( 0, 0, 0 );

		if ( ! empty( $rl1_full_name ) ) {
			$pdf->SetXY( 50, 72 );
			$pdf->Write( 0, $rl1_full_name );
		}

		$pdf->SetXY( 88, 88 );
		$pdf->Write( 0, $adherent_full_name );
		$pdf->SetXY( 163, 88 );
		$pdf->Write( 0, $adherent_birth_date_formatted );
		$pdf->SetXY( 30, 191 );
		$pdf->Write( 0, $adherent_city );
		$pdf->SetXY( 27, 201 );
		$pdf->Write( 0, $current_date );

		if ( ! empty( $rl1_last_name ) ) {
			$pdf->SetXY( 25, 248 );
			$pdf->Write( 0, mb_convert_encoding( mb_strtoupper( $rl1_last_name, 'UTF-8' ), 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl1_first_name ) ) {
			$pdf->SetXY( 30, 255 );
			$pdf->Write( 0, mb_convert_encoding( $rl1_first_name, 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl1_birth_place ) ) {
			$pdf->SetXY( 48, 264 );
			$pdf->Write( 0, mb_convert_encoding( $rl1_birth_place, 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl1_birth_date ) ) {
			$pdf->SetXY( 54, 270 );
			$pdf->Write( 0, mb_convert_encoding( wp_date( 'd/m/Y', strtotime( $rl1_birth_date ), new \DateTimeZone( 'UTC' ) ), 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl1_profession ) ) {
			$pdf->SetXY( 35, 279 );
			$pdf->Write( 0, mb_convert_encoding( $rl1_profession, 'ISO-8859-1', 'UTF-8' ) );
		}

		if ( ! empty( $rl2_last_name ) ) {
			$pdf->SetXY( 125, 248 );
			$pdf->Write( 0, mb_convert_encoding( mb_strtoupper( $rl2_last_name, 'UTF-8' ), 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl2_first_name ) ) {
			$pdf->SetXY( 130, 255 );
			$pdf->Write( 0, mb_convert_encoding( $rl2_first_name, 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl2_birth_place ) ) {
			$pdf->SetXY( 148, 264 );
			$pdf->Write( 0, mb_convert_encoding( $rl2_birth_place, 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl2_birth_date ) ) {
			$pdf->SetXY( 154, 270 );
			$pdf->Write( 0, mb_convert_encoding( wp_date( 'd/m/Y', strtotime( $rl2_birth_date ), new \DateTimeZone( 'UTC' ) ), 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl2_profession ) ) {
			$pdf->SetXY( 135, 279 );
			$pdf->Write( 0, mb_convert_encoding( $rl2_profession, 'ISO-8859-1', 'UTF-8' ) );
		}

		$filename = sanitize_file_name( 'autorisation_parentale_' . $last_name . '_' . $first_name . '.pdf' );
		$pdf->Output( 'D', $filename );
		exit;
	}
}
