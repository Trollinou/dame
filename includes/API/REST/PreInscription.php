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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the REST API routes.
	 */
	public function register_routes(): void {
		// Submit pre-inscription
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_submission' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		// Get details of a member for pre-filling
		register_rest_route(
			$this->namespace,
			'/adherent-details',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_adherent_details' ),
					'permission_callback' => array( $this, 'check_user_logged_in' ),
				),
			)
		);

		// Secure PDF download endpoints
		register_rest_route(
			$this->namespace,
			'/pre-inscriptions/(?P<id>\d+)/pdf/health',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'generate_health_pdf' ),
					'permission_callback' => array( $this, 'check_pdf_access' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/pre-inscriptions/(?P<id>\d+)/pdf/parental',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'generate_parental_pdf' ),
					'permission_callback' => array( $this, 'check_pdf_access' ),
				),
			)
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
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Permission check for PDF access.
	 * Access is granted if:
	 * 1. The request provides the correct download token matching the post meta.
	 * 2. OR the user is logged in and is the owner/parent or an administrator.
	 *
	 * @param WP_REST_Request $request REST request.
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
			$allowed_roles = array( 'staff', 'entraineur', 'editor', 'administrator' );
			if ( array_intersect( $allowed_roles, (array) $current_user->roles ) ) {
				return true;
			}

			// Adherent or legal reps matching the pre-inscription email can access
			$adh_email  = get_post_meta( $post_id, '_dame_email', true );
			$rep1_email = get_post_meta( $post_id, '_dame_legal_rep_1_email', true );
			$rep2_email = get_post_meta( $post_id, '_dame_legal_rep_2_email', true );

			if ( ! empty( $email ) && ( $email === $adh_email || $email === $rep1_email || $email === $rep2_email ) ) {
				return true;
			}
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Accès non autorisé à ce document.', 'dame' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Finds an existing pending pre-inscription for an adherent or user email.
	 *
	 * @param int|null    $adherent_id Adherent ID if available.
	 * @param string      $user_email  Current user's email.
	 * @param string|null $first_name  First name.
	 * @param string|null $birth_date  Birth date (Y-m-d).
	 * @param string|null $last_name   Birth name or usage name.
	 * @return \WP_Post|null
	 */
	public function find_pending_pre_inscription(
		?int $adherent_id,
		string $user_email,
		?string $first_name = null,
		?string $birth_date = null,
		?string $last_name = null
	): ?\WP_Post {
		if ( empty( $user_email ) ) {
			return null;
		}

		$adh_email     = '';
		$is_authorized = false;

		// 1. Direct check by _dame_adherent_id if adherent_id is provided
		if ( $adherent_id && $adherent_id > 0 ) {
			$adh_email   = (string) get_post_meta( $adherent_id, '_dame_email', true );
			$rep_1_email = (string) get_post_meta( $adherent_id, '_dame_legal_rep_1_email', true );
			$rep_2_email = (string) get_post_meta( $adherent_id, '_dame_legal_rep_2_email', true );

			$user       = get_user_by( 'email', $user_email );
			$user_roles = $user ? (array) $user->roles : array();
			$is_admin   = (bool) array_intersect( array( 'staff', 'entraineur', 'editor', 'administrator' ), $user_roles );

			if ( $is_admin || $user_email === $adh_email || $user_email === $rep_1_email || $user_email === $rep_2_email ) {
				$is_authorized = true;
			}

			if ( $is_authorized ) {
				$query = new WP_Query(
					array(
						'post_type'      => 'dame_pre_inscription',
						'post_status'    => array( 'pending', 'publish', 'draft' ),
						'posts_per_page' => 1,
						'meta_query'     => array(
							array(
								'key'     => '_dame_adherent_id',
								'value'   => $adherent_id,
								'compare' => '=',
							),
						),
						'orderby'        => 'date',
						'order'          => 'DESC',
					)
				);

				if ( ! empty( $query->posts ) ) {
					return $query->posts[0];
				}
			}

			// If not found by _dame_adherent_id (legacy pre-inscriptions),
			// retrieve adherent identity to search by triplet
			if ( empty( $first_name ) ) {
				$first_name = (string) get_post_meta( $adherent_id, '_dame_first_name', true );
			}
			if ( empty( $birth_date ) ) {
				$birth_date = (string) get_post_meta( $adherent_id, '_dame_birth_date', true );
			}
			if ( empty( $last_name ) ) {
				$birth_name = (string) get_post_meta( $adherent_id, '_dame_birth_name', true );
				$usage_name = (string) get_post_meta( $adherent_id, '_dame_last_name', true );
				$last_name  = ! empty( $birth_name ) ? $birth_name : $usage_name;
			}
		}

		// 2. Search by first_name and birth_date under user email / adherent email (retrocompatibility & new submissions)
		if ( ! empty( $first_name ) && ! empty( $birth_date ) ) {
			$email_or = array(
				array(
					'key'     => '_dame_email',
					'value'   => $user_email,
					'compare' => '=',
				),
				array(
					'key'     => '_dame_legal_rep_1_email',
					'value'   => $user_email,
					'compare' => '=',
				),
				array(
					'key'     => '_dame_legal_rep_2_email',
					'value'   => $user_email,
					'compare' => '=',
				),
				array(
					'key'     => '_dame_submitted_by_email',
					'value'   => $user_email,
					'compare' => '=',
				),
			);

			if ( ! empty( $adh_email ) && $is_authorized ) {
				$email_or[] = array(
					'key'     => '_dame_email',
					'value'   => $adh_email,
					'compare' => '=',
				);
			}

			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => '_dame_first_name',
					'value'   => $first_name,
					'compare' => '=',
				),
				array(
					'key'     => '_dame_birth_date',
					'value'   => $birth_date,
					'compare' => '=',
				),
				array_merge(
					array( 'relation' => 'OR' ),
					$email_or
				),
			);

			$query = new WP_Query(
				array(
					'post_type'      => 'dame_pre_inscription',
					'post_status'    => array( 'pending', 'publish', 'draft' ),
					'posts_per_page' => 1,
					'meta_query'     => $meta_query,
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			);

			if ( ! empty( $query->posts ) ) {
				$post = $query->posts[0];
				// Link _dame_adherent_id if known and not yet linked
				if ( $adherent_id && $adherent_id > 0 && ! get_post_meta( $post->ID, '_dame_adherent_id', true ) ) {
					update_post_meta( $post->ID, '_dame_adherent_id', $adherent_id );
				}
				return $post;
			}
		}

		return null;
	}

	/**
	 * Get details of an adherent to prefill registration.
	 * If a pending pre-inscription exists, its data is returned instead.
	 *
	 * @param WP_REST_Request $request REST request.
	 */
	public function get_adherent_details( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$adherent_id        = (int) $request->get_param( 'adherent_id' );
		$pre_inscription_id = (int) $request->get_param( 'pre_inscription_id' );

		if ( ! $adherent_id && ! $pre_inscription_id ) {
			return new WP_Error( 'missing_param', __( 'ID manquant.', 'dame' ), array( 'status' => 400 ) );
		}

		$current_user = wp_get_current_user();
		$email        = $current_user->user_email;
		$is_admin     = array_intersect( array( 'staff', 'entraineur', 'editor', 'administrator' ), (array) $current_user->roles );

		if ( ! $is_admin ) {
			// Verify access
			if ( $adherent_id > 0 ) {
				$rep_1     = get_post_meta( $adherent_id, '_dame_legal_rep_1_email', true );
				$rep_2     = get_post_meta( $adherent_id, '_dame_legal_rep_2_email', true );
				$adh_email = get_post_meta( $adherent_id, '_dame_email', true );

				if ( empty( $email ) || ( $email !== $rep_1 && $email !== $rep_2 && $email !== $adh_email ) ) {
					return new WP_Error( 'forbidden', __( 'Vous n\'avez pas accès à cet adhérent.', 'dame' ), array( 'status' => 403 ) );
				}
			} elseif ( $pre_inscription_id > 0 ) {
				$sub_email = (string) get_post_meta( $pre_inscription_id, '_dame_submitted_by_email', true );
				$rep_1     = (string) get_post_meta( $pre_inscription_id, '_dame_legal_rep_1_email', true );
				$rep_2     = (string) get_post_meta( $pre_inscription_id, '_dame_legal_rep_2_email', true );
				$adh_email = (string) get_post_meta( $pre_inscription_id, '_dame_email', true );

				if ( empty( $email ) || ( $email !== $sub_email && $email !== $rep_1 && $email !== $rep_2 && $email !== $adh_email ) ) {
					return new WP_Error( 'forbidden', __( 'Vous n\'avez pas accès à cette préinscription.', 'dame' ), array( 'status' => 403 ) );
				}
			}
		}

		// Retrieve all metadata
		$meta_keys = array(
			'first_name',
			'last_name',
			'birth_name',
			'birth_date',
			'birth_city',
			'sexe',
			'profession',
			'email',
			'phone_number',
			'address_1',
			'address_2',
			'postal_code',
			'city',
			'taille_vetements',
			'license_type',
			'legal_rep_1_first_name',
			'legal_rep_1_last_name',
			'legal_rep_1_email',
			'legal_rep_1_phone',
			'legal_rep_1_address_1',
			'legal_rep_1_address_2',
			'legal_rep_1_postal_code',
			'legal_rep_1_city',
			'legal_rep_1_profession',
			'legal_rep_1_date_naissance',
			'legal_rep_1_commune_naissance',
			'legal_rep_2_first_name',
			'legal_rep_2_last_name',
			'legal_rep_2_email',
			'legal_rep_2_phone',
			'legal_rep_2_address_1',
			'legal_rep_2_address_2',
			'legal_rep_2_postal_code',
			'legal_rep_2_city',
			'legal_rep_2_profession',
			'legal_rep_2_date_naissance',
			'legal_rep_2_commune_naissance',
		);

		// Check if a pending pre-inscription exists for this adherent or pre_inscription_id
		$pending_pre = null;
		if ( $adherent_id > 0 ) {
			$pending_pre = $this->find_pending_pre_inscription( $adherent_id, $email );
		} elseif ( $pre_inscription_id > 0 ) {
			$cand_post = get_post( $pre_inscription_id );
			if ( $cand_post && 'dame_pre_inscription' === $cand_post->post_type ) {
				$pending_pre = $cand_post;
			}
		}
		$source_id = $pending_pre ? $pending_pre->ID : $adherent_id;

		$details = array();
		foreach ( $meta_keys as $key ) {
			$details[ $key ] = get_post_meta( $source_id, '_dame_' . $key, true );
		}

		// Health document / questionnaire and preferences
		if ( $pending_pre ) {
			$health_doc = (string) get_post_meta( $source_id, '_dame_health_document', true );
			$health_q   = (string) get_post_meta( $source_id, '_dame_health_questionnaire', true );
			if ( empty( $health_q ) ) {
				if ( 'certificate' === $health_doc ) {
					$health_q = 'oui';
				} elseif ( 'attestation' === $health_doc ) {
					$health_q = 'non';
				}
			}
			$details['health_questionnaire']     = $health_q;
			$details['refuses_comms']             = (bool) get_post_meta( $source_id, '_dame_email_refuses_comms', true );
			$details['legal_rep_1_refuses_comms'] = (bool) get_post_meta( $source_id, '_dame_legal_rep_1_email_refuses_comms', true );
			$details['legal_rep_2_refuses_comms'] = (bool) get_post_meta( $source_id, '_dame_legal_rep_2_email_refuses_comms', true );
			$details['is_pre_inscription']        = true;
			$details['pre_inscription_id']        = $pending_pre->ID;
			$details['pre_inscription_date']      = $pending_pre->post_date;
		} else {
			$details['is_pre_inscription']   = false;
			$details['pre_inscription_id']   = null;
			$details['health_questionnaire'] = '';
		}

		return rest_ensure_response( $details );
	}

	/**
	 * Handle pre-inscription submission.
	 *
	 * @param WP_REST_Request $request REST request.
	 */
	public function handle_submission( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$params = $request->get_params();

		// If usage name is empty, copy birth name into it.
		if ( empty( $params['dame_last_name'] ) && ! empty( $params['dame_birth_name'] ) ) {
			$params['dame_last_name'] = $params['dame_birth_name'];
		}

		// Validation
		$errors          = array();
		$required_fields = array(
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
		);

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
					$rep1_required_fields = array(
						'dame_legal_rep_1_first_name' => __( 'Le prénom du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_last_name'  => __( 'Le nom de naissance du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_email'      => __( 'L\'email du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_phone'      => __( 'Le téléphone du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_address_1'  => __( 'L\'adresse du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_city'       => __( 'La ville du représentant légal 1 est obligatoire.', 'dame' ),
					);

					foreach ( $rep1_required_fields as $field_key => $error_message ) {
						if ( empty( $params[ $field_key ] ) ) {
							$errors[] = $error_message;
						}
					}
				} elseif ( empty( $params['dame_birth_city'] ) ) {
					// For adults, birth city is required for the honorability check.
					$errors[] = __( 'La commune de naissance est obligatoire pour les personnes majeures.', 'dame' );
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
			return new WP_Error( 'validation_failed', implode( '<br>', $errors ), array( 'status' => 400 ) );
		}

		// Sanitize Data
		$sanitized_data     = array();
		$fields_to_sanitize = array(
			'dame_first_name',
			'dame_last_name',
			'dame_birth_name',
			'dame_birth_date',
			'dame_license_type',
			'dame_birth_city',
			'dame_sexe',
			'dame_profession',
			'dame_email',
			'dame_phone_number',
			'dame_address_1',
			'dame_address_2',
			'dame_postal_code',
			'dame_city',
			'dame_taille_vetements',
			'dame_legal_rep_1_first_name',
			'dame_legal_rep_1_last_name',
			'dame_legal_rep_1_email',
			'dame_legal_rep_1_phone',
			'dame_legal_rep_1_address_1',
			'dame_legal_rep_1_address_2',
			'dame_legal_rep_1_postal_code',
			'dame_legal_rep_1_city',
			'dame_legal_rep_1_profession',
			'dame_legal_rep_1_date_naissance',
			'dame_legal_rep_1_commune_naissance',
			'dame_legal_rep_2_first_name',
			'dame_legal_rep_2_last_name',
			'dame_legal_rep_2_email',
			'dame_legal_rep_2_phone',
			'dame_legal_rep_2_address_1',
			'dame_legal_rep_2_address_2',
			'dame_legal_rep_2_postal_code',
			'dame_legal_rep_2_city',
			'dame_legal_rep_2_profession',
			'dame_legal_rep_2_date_naissance',
			'dame_legal_rep_2_commune_naissance',
			'dame_health_questionnaire',
		);

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
		$sanitized_data['dame_email_refuses_comms']             = ( isset( $params['dame_refuses_comms'] ) && (bool) $params['dame_refuses_comms'] ) ? '1' : '0';
		$sanitized_data['dame_legal_rep_1_email_refuses_comms'] = ( isset( $params['dame_legal_rep_1_refuses_comms'] ) && (bool) $params['dame_legal_rep_1_refuses_comms'] ) ? '1' : '0';
		$sanitized_data['dame_legal_rep_2_email_refuses_comms'] = ( isset( $params['dame_legal_rep_2_refuses_comms'] ) && (bool) $params['dame_legal_rep_2_refuses_comms'] ) ? '1' : '0';

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

		$adherent_id        = isset( $params['adherent_id'] ) ? (int) $params['adherent_id'] : 0;
		$pre_inscription_id = isset( $params['pre_inscription_id'] ) ? (int) $params['pre_inscription_id'] : 0;

		// Create or Update Pre-inscription Post
		$effective_last_name = ! empty( $sanitized_data['dame_last_name'] ) ? $sanitized_data['dame_last_name'] : $sanitized_data['dame_birth_name'];
		$post_title          = Utils::format_lastname( (string) $effective_last_name ) . ' ' . Utils::format_firstname( (string) $sanitized_data['dame_first_name'] );

		// Check for existing pending pre-inscription to update instead of creating duplicate
		$existing_post = null;
		if ( $pre_inscription_id > 0 ) {
			$cand_post = get_post( $pre_inscription_id );
			if ( $cand_post && 'dame_pre_inscription' === $cand_post->post_type ) {
				$existing_post = $cand_post;
			}
		}

		if ( ! $existing_post && is_user_logged_in() ) {
			$current_user  = wp_get_current_user();
			$existing_post = $this->find_pending_pre_inscription(
				$adherent_id > 0 ? $adherent_id : null,
				$current_user->user_email,
				$sanitized_data['dame_first_name'],
				$sanitized_data['dame_birth_date'],
				$effective_last_name
			);
		}

		$is_update = ( $existing_post instanceof \WP_Post );
		if ( $is_update ) {
			$post_id = $existing_post->ID;
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $post_title,
				)
			);
		} else {
			$post_data = array(
				'post_title'  => $post_title,
				'post_type'   => 'dame_pre_inscription',
				'post_status' => 'pending',
			);
			$post_id   = wp_insert_post( $post_data, true );

			if ( is_wp_error( $post_id ) ) {
				return new WP_Error( 'post_creation_failed', __( 'Erreur lors de la création de la préinscription.', 'dame' ), array( 'status' => 500 ) );
			}
		}

		// Secure download token
		$download_token = get_post_meta( $post_id, '_dame_download_token', true );
		if ( empty( $download_token ) ) {
			$download_token = wp_generate_password( 32, false );
			update_post_meta( $post_id, '_dame_download_token', $download_token );
		}

		// Save Meta Data
		global $wpdb;
		if ( $is_update ) {
			// Delete existing _dame_ meta except download token
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s AND meta_key != %s",
					$post_id,
					'_dame_%',
					'_dame_download_token'
				)
			);
		}

		$meta_insert_values       = array();
		$meta_insert_placeholders = array();

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

		if ( isset( $sanitized_data['dame_health_questionnaire'] ) ) {
			$meta_insert_values[]       = $post_id;
			$meta_insert_values[]       = '_dame_health_questionnaire';
			$meta_insert_values[]       = $sanitized_data['dame_health_questionnaire'];
			$meta_insert_placeholders[] = '(%d, %s, %s)';
		}

		if ( ! $adherent_id && $existing_post ) {
			$existing_adh_id = (int) get_post_meta( $existing_post->ID, '_dame_adherent_id', true );
			if ( $existing_adh_id > 0 ) {
				$adherent_id = $existing_adh_id;
			}
		}

		if ( $adherent_id > 0 ) {
			$meta_insert_values[]       = $post_id;
			$meta_insert_values[]       = '_dame_adherent_id';
			$meta_insert_values[]       = (string) $adherent_id;
			$meta_insert_placeholders[] = '(%d, %s, %s)';
		}

		$submitted_by_email = '';
		$submitted_by_uid   = 0;
		if ( is_user_logged_in() ) {
			$curr_user          = wp_get_current_user();
			$submitted_by_email = (string) $curr_user->user_email;
			$submitted_by_uid   = (int) $curr_user->ID;
		} elseif ( $existing_post ) {
			$submitted_by_email = (string) get_post_meta( $existing_post->ID, '_dame_submitted_by_email', true );
			$submitted_by_uid   = (int) get_post_meta( $existing_post->ID, '_dame_submitted_by_user_id', true );
		}

		if ( ! empty( $submitted_by_email ) ) {
			$meta_insert_values[]       = $post_id;
			$meta_insert_values[]       = '_dame_submitted_by_email';
			$meta_insert_values[]       = $submitted_by_email;
			$meta_insert_placeholders[] = '(%d, %s, %s)';
		}

		if ( $submitted_by_uid > 0 ) {
			$meta_insert_values[]       = $post_id;
			$meta_insert_values[]       = '_dame_submitted_by_user_id';
			$meta_insert_values[]       = (string) $submitted_by_uid;
			$meta_insert_placeholders[] = '(%d, %s, %s)';
		}

		$query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ', ', $meta_insert_placeholders );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( $query, $meta_insert_values ) );

		// Send Email Notification
		$options         = get_option( 'dame_options' );
		$recipient_email = isset( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

		$subject_prefix = $is_update ? 'Mise à jour de la préinscription de ' : 'Nouvelle préinscription de ';
		$subject        = $subject_prefix . $sanitized_data['dame_first_name'] . ' ' . $sanitized_data['dame_last_name'];
		$body           = $is_update ? "Une demande de préinscription a été mise à jour depuis la PWA.\n\n" : "Une nouvelle demande de préinscription a été soumise depuis la PWA.\n\n";
		$body          .= "Voici les détails :\n";
		foreach ( $sanitized_data as $key => $value ) {
			if ( ! empty( $value ) ) {
				$label = str_replace( array( 'dame_', '_' ), array( '', ' ' ), $key );
				$label = mb_convert_case( $label, MB_CASE_TITLE, 'UTF-8' );
				$body .= '- ' . $label . ': ' . $value . "\n";
			}
		}
		$headers = array( 'From: ' . $recipient_email );
		wp_mail( $recipient_email, $subject, $body, $headers );

		$payment_url  = isset( $options['payment_url'] ) ? $options['payment_url'] : '';
		$sender_email = isset( $options['sender_email'] ) && ! empty( $options['sender_email'] ) ? $options['sender_email'] : get_option( 'admin_email' );

		$message = $is_update
			? sprintf(
				/* translators: 1: Prénom de l'adhérent, 2: Nom de famille */
				__( 'La préinscription pour %1$s %2$s a bien été mise à jour.', 'dame' ),
				$sanitized_data['dame_first_name'],
				$sanitized_data['dame_last_name']
			)
			: sprintf(
				/* translators: 1: Prénom de l'adhérent, 2: Nom de famille */
				__( 'La préinscription pour %1$s %2$s a bien été enregistrée.', 'dame' ),
				$sanitized_data['dame_first_name'],
				$sanitized_data['dame_last_name']
			);

		return rest_ensure_response(
			array(
				'success'              => true,
				'updated'              => $is_update,
				'message'              => $message,
				'post_id'              => $post_id,
				'download_token'       => $download_token,
				'health_questionnaire' => $sanitized_data['dame_health_questionnaire'],
				'is_minor'             => $is_minor,
				'payment_url'          => $payment_url,
				'sender_email'         => $sender_email,
			)
		);
	}

	/**
	 * Generate Health PDF.
	 *
	 * @param WP_REST_Request $request REST request.
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
	 *
	 * @param WP_REST_Request $request REST request.
	 */
	public function generate_parental_pdf( WP_REST_Request $request ): void {
		$post_id = (int) $request['id'];
		$this->output_parental_auth( $post_id );
	}

	/**
	 * Fpdi logic for Health Form.
	 *
	 * @param int $post_id Post ID.
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
			wp_die( esc_html__( 'Données de préinscription manquantes ou invalides.', 'dame' ), 404 );
		}

		$birth_date = DateTime::createFromFormat( 'Y-m-d', $birth_date_str );
		$today      = new DateTime();
		$age        = $today->diff( $birth_date )->y;

		$full_name_adherent_for_pdf = Utils::generate_adherent_title( $post_id );
		$current_date               = wp_date( 'd/m/Y' );

		$full_name_adherent_for_pdf = mb_convert_encoding( $full_name_adherent_for_pdf, 'ISO-8859-1', 'UTF-8' );
		$city_for_pdf               = mb_convert_encoding( $city, 'ISO-8859-1', 'UTF-8' );

		$pdf = new \setasign\Fpdi\Fpdi();
		$pdf->AddPage();

		try {
			$template_path = DAME_PLUGIN_DIR . 'assets/pdf/ffe_attestation_sante.pdf';
			$pdf->setSourceFile( $template_path );
			$tpl_id = $pdf->importPage( 1 );
			$pdf->useTemplate( $tpl_id, 0, 0, 210, 297 );
		} catch ( Exception $e ) {
			/* translators: %s: Message d'erreur */
			wp_die( esc_html( sprintf( __( 'Erreur lors du chargement du template PDF : %s', 'dame' ), $e->getMessage() ) ), 500 );
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
				wp_die( esc_html__( 'Données du représentant légal manquantes pour un adhérent mineur.', 'dame' ), 400 );
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
	 *
	 * @param int $post_id Post ID.
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
			wp_die( esc_html__( 'Données de préinscription de l\'adhérent manquantes ou invalides.', 'dame' ), 404 );
		}

		$adherent_full_name            = mb_convert_encoding( Utils::generate_adherent_title( $post_id ), 'ISO-8859-1', 'UTF-8' );
		$adherent_birth_date_formatted = mb_convert_encoding( wp_date( 'd/m/Y', strtotime( $birth_date_str ), new \DateTimeZone( 'UTC' ) ), 'ISO-8859-1', 'UTF-8' );
		$adherent_city                 = mb_convert_encoding( (string) $city, 'ISO-8859-1', 'UTF-8' );
		$current_date                  = wp_date( 'd/m/Y' );
		$rl1_full_name                 = '';
		if ( ! empty( $rl1_first_name ) && ! empty( $rl1_last_name ) ) {
			$rl1_full_name = mb_convert_encoding( Utils::format_lastname( (string) $rl1_last_name ) . ' ' . Utils::format_firstname( (string) $rl1_first_name ), 'ISO-8859-1', 'UTF-8' );
		}

		$pdf = new \setasign\Fpdi\Fpdi();
		$pdf->AddPage();
		$pdf->SetAutoPageBreak( true, 0 );

		try {
			$template_path = DAME_PLUGIN_DIR . 'assets/pdf/el_autorisation_parentale.pdf';
			$pdf->setSourceFile( $template_path );
			$tpl_id = $pdf->importPage( 1 );
			$pdf->useTemplate( $tpl_id, 0, 0, 210, 297 );
		} catch ( Exception $e ) {
			/* translators: %s: Message d'erreur */
			wp_die( esc_html( sprintf( __( 'Erreur lors du chargement du template PDF : %s', 'dame' ), $e->getMessage() ) ), 500 );
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
