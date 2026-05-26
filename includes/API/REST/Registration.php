<?php
/**
 * REST API Registration Endpoint and Verification Logic.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\API\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;
use WP_User;
use DAME\Services\Adherent_Matcher;

/**
 * Class Registration
 * Handles user registration via REST API and email verification.
 */
class Registration {

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
	protected string $rest_base = 'register';

	/**
	 * Initialize the class and register hooks.
	 */
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'init', [ $this, 'handle_token_verification' ] );
		add_filter( 'wp_authenticate_user', [ $this, 'block_unverified_login' ], 10, 2 );
	}

	/**
	 * Register the REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_registration' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'username' => [
							'required'          => true,
							'validate_callback' => fn( $param ) => is_string( $param ),
						],
						'email'    => [
							'required'          => true,
							'validate_callback' => fn( $param ) => is_email( (string) $param ),
						],
						'password' => [
							'required'          => true,
							'validate_callback' => fn( $param ) => is_string( $param ),
						],
					],
				],
			]
		);
	}

	/**
	 * Handles the registration request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_registration( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$username = (string) $request['username'];
		$email    = (string) $request['email'];
		$password = (string) $request['password'];

		// CONTRAT D'ERREUR 1 : Si l'e-mail existe déjà comme utilisateur WP.
		if ( email_exists( $email ) ) {
			return new WP_Error(
				'user_exists',
				__( 'Cet e-mail est déjà utilisé.', 'dame' ),
				[ 'status' => 400 ]
			);
		}

		// CONTRAT D'ERREUR 2 : Vérifie si l'e-mail correspond à un adhérent ou RL.
		$adherent_id = Adherent_Matcher::check_if_email_is_member( $email );
		if ( ! $adherent_id ) {
			return new WP_Error(
				'not_member',
				__( "Cet e-mail n'est pas reconnu comme membre du club.", 'dame' ),
				[ 'status' => 403 ]
			);
		}

		// CRÉATION DU COMPTE.
		$user_id = wp_insert_user(
			[
				'user_login' => $username,
				'user_email' => $email,
				'user_pass'  => $password,
				'role'       => 'subscriber',
			]
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// SÉCURISATION : Ajoute immédiatement un user_meta '_dame_is_verified' mis à 0.
		update_user_meta( $user_id, '_dame_is_verified', 0 );

		// JETON & EMAIL : Génère un token unique stocké en user_meta (avec expiration).
		$token      = wp_generate_password( 32, false );
		$expiration = time() + ( 24 * HOUR_IN_SECONDS );

		update_user_meta( $user_id, '_dame_verification_token', $token );
		update_user_meta( $user_id, '_dame_verification_token_expiration', $expiration );

		// Envoie un e-mail au format HTML via wp_mail().
		$verification_url = add_query_arg( 'dame_token', $token, home_url( '/' ) );
		$subject          = __( 'Vérification de votre compte - DAME', 'dame' );
		$message          = sprintf(
			'<p>%s</p><p><a href="%s">%s</a></p>',
			__( 'Merci de vous être inscrit. Veuillez cliquer sur le lien ci-dessous pour vérifier votre compte :', 'dame' ),
			esc_url( $verification_url ),
			__( 'Vérifier mon compte', 'dame' )
		);

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		wp_mail( $email, $subject, $message, $headers );

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Inscription réussie. Veuillez vérifier votre e-mail pour activer votre compte.', 'dame' ),
			],
			200
		);
	}

	/**
	 * Handles the token verification when clicking the link in the email.
	 */
	public function handle_token_verification(): void {
		if ( ! isset( $_GET['dame_token'] ) ) {
			return;
		}

		$token = sanitize_text_field( wp_unslash( $_GET['dame_token'] ) );

		$users = get_users(
			[
				'meta_key'   => '_dame_verification_token',
				'meta_value' => $token,
				'number'     => 1,
			]
		);

		if ( empty( $users ) ) {
			wp_die( __( 'Jeton de vérification invalide.', 'dame' ) );
		}

		$user       = $users[0];
		$expiration = (int) get_user_meta( $user->ID, '_dame_verification_token_expiration', true );

		if ( time() > $expiration ) {
			wp_die( __( 'Le jeton de vérification a expiré.', 'dame' ) );
		}

		// Passe le user_meta '_dame_is_verified' à 1, change le rôle à 'membre' et supprime le token.
		update_user_meta( $user->ID, '_dame_is_verified', 1 );
		$user->set_role( 'membre' );
		delete_user_meta( $user->ID, '_dame_verification_token' );
		delete_user_meta( $user->ID, '_dame_verification_token_expiration' );

		// Associe définitivement l'utilisateur WordPress au CPT Adhérent correspondant à son e-mail.
		$adherent_id = Adherent_Matcher::check_if_email_is_member( $user->user_email );
		if ( $adherent_id ) {
			update_post_meta( $adherent_id, '_dame_wp_user_id', $user->ID );
			update_user_meta( $user->ID, '_dame_adherent_id', $adherent_id );
		}

		// Redirige proprement l'utilisateur vers la PWA.
		$pwa_url = add_query_arg( 'verified', 'true', $this->get_pwa_url() );
		wp_redirect( $pwa_url );
		exit;
	}

	/**
	 * Gets the PWA URL.
	 *
	 * @return string
	 */
	private function get_pwa_url(): string {
		return \DAME_PLUGIN_URL . 'pwa/dist/index.html';
	}

	/**
	 * Blocks login if the user is not verified.
	 *
	 * @param WP_User|WP_Error $user     The authenticated user object or WP_Error.
	 * @param string           $password The user password.
	 * @return WP_User|WP_Error
	 */
	public function block_unverified_login( $user, $password ): WP_User|WP_Error {
		if ( $user instanceof WP_User ) {
			// Bypass verification for Members, Staff and Admins.
			$allowed_roles = [ 'membre', 'staff', 'entraineur', 'editor', 'administrator' ];
			$user_roles    = (array) $user->roles;

			if ( array_intersect( $allowed_roles, $user_roles ) ) {
				return $user;
			}

			$is_verified = get_user_meta( $user->ID, '_dame_is_verified', true );
			if ( '1' !== (string) $is_verified ) {
				return new WP_Error(
					'unverified_account',
					__( 'Votre compte n\'a pas encore été vérifié. Veuillez consulter vos e-mails.', 'dame' ),
					[ 'status' => 401 ]
				);
			}
		}

		return $user;
	}
}
