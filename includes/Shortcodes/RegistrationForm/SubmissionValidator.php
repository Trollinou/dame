<?php
/**
 * Registration Form Submission Validator.
 *
 * @package DAME\Shortcodes\RegistrationForm
 */

declare(strict_types=1);

namespace DAME\Shortcodes\RegistrationForm;

use DateTime;

/**
 * Validates nonce and input rules for pre-inscription submissions.
 */
class SubmissionValidator {

	/**
	 * Validates submitted pre-inscription data.
	 *
	 * @param array<string, mixed> $data Raw POST data.
	 * @return array<int, string> List of error messages (empty if valid).
	 */
	public function validate( array $data ): array {
		$errors = array();

		$required_fields = array(
			'dame_first_name'           => __( 'Le prénom est obligatoire.', 'dame' ),
			'dame_birth_name'           => __( 'Le nom de naissance est obligatoire.', 'dame' ),
			'dame_birth_date'           => __( 'La date de naissance est obligatoire.', 'dame' ),
			'dame_license_type'         => __( 'Le type de licence est obligatoire.', 'dame' ),
			'dame_sexe'                 => __( 'Le sexe est obligatoire.', 'dame' ),
			'dame_email'                => __( "L'email est obligatoire.", 'dame' ),
			'dame_phone_number'         => __( 'Le numéro de téléphone est obligatoire.', 'dame' ),
			'dame_address_1'            => __( "L'adresse est obligatoire.", 'dame' ),
			'dame_city'                 => __( 'La ville est obligatoire.', 'dame' ),
			'dame_health_questionnaire' => __( 'La réponse au questionnaire de santé est obligatoire.', 'dame' ),
			'dame_consent_checkbox'     => __( 'Vous devez accepter le règlement intérieur.', 'dame' ),
		);

		foreach ( $required_fields as $field_key => $error_message ) {
			if ( empty( $data[ $field_key ] ) ) {
				$errors[] = $error_message;
			}
		}

		// Conditional validation for minors / adults.
		$birth_date_raw = isset( $data['dame_birth_date'] ) ? sanitize_text_field( (string) $data['dame_birth_date'] ) : '';
		if ( ! empty( $birth_date_raw ) ) {
			$birth_date = DateTime::createFromFormat( 'Y-m-d', $birth_date_raw );
			if ( $birth_date ) {
				$today = new DateTime();
				$age   = $today->diff( $birth_date )->y;

				if ( $age < 18 ) {
					$rep1_required_fields = array(
						'dame_legal_rep_1_first_name' => __( 'Le prénom du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_last_name'  => __( 'Le nom de naissance du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_email'      => __( "L'email du représentant légal 1 est obligatoire.", 'dame' ),
						'dame_legal_rep_1_phone'      => __( 'Le téléphone du représentant légal 1 est obligatoire.', 'dame' ),
						'dame_legal_rep_1_address_1'  => __( "L'adresse du représentant légal 1 est obligatoire.", 'dame' ),
						'dame_legal_rep_1_city'       => __( 'La ville du représentant légal 1 est obligatoire.', 'dame' ),
					);

					foreach ( $rep1_required_fields as $field_key => $error_message ) {
						if ( empty( $data[ $field_key ] ) ) {
							$errors[] = $error_message;
						}
					}
				} elseif ( empty( $data['dame_birth_city'] ) ) {
					$errors[] = __( 'La commune de naissance est obligatoire pour les personnes majeures.', 'dame' );
				}
			}
		}

		// Email format validation.
		$email_raw = isset( $data['dame_email'] ) ? sanitize_email( (string) $data['dame_email'] ) : '';
		if ( ! empty( $email_raw ) && ! is_email( $email_raw ) ) {
			$errors[] = __( "L'adresse email de l'adhérent n'est pas valide.", 'dame' );
		}

		$rep1_email_raw = isset( $data['dame_legal_rep_1_email'] ) ? sanitize_email( (string) $data['dame_legal_rep_1_email'] ) : '';
		if ( ! empty( $rep1_email_raw ) && ! is_email( $rep1_email_raw ) ) {
			$errors[] = __( "L'adresse email du représentant légal 1 n'est pas valide.", 'dame' );
		}

		$rep2_email_raw = isset( $data['dame_legal_rep_2_email'] ) ? sanitize_email( (string) $data['dame_legal_rep_2_email'] ) : '';
		if ( ! empty( $rep2_email_raw ) && ! is_email( $rep2_email_raw ) ) {
			$errors[] = __( "L'adresse email du représentant légal 2 n'est pas valide.", 'dame' );
		}

		return $errors;
	}
}
