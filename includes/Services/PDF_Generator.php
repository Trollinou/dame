<?php
/**
 * PDF Generator Service.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Services;

use setasign\Fpdi\Fpdi;
use DateTime;
use Exception;
use DAME\Core\Utils;
use DAME\Services\Document_Storage;
use DAME\Services\Data_Provider;

/**
 * Class PDF_Generator
 */
class PDF_Generator {

	/**
	 * Initialize the service.
	 */
	public function init(): void {
		add_action( 'wp_ajax_dame_generate_health_form', array( $this, 'generate_health_form' ) );
		add_action( 'wp_ajax_nopriv_dame_generate_health_form', array( $this, 'generate_health_form' ) );

		add_action( 'wp_ajax_dame_generate_parental_auth', array( $this, 'generate_parental_auth' ) );
		add_action( 'wp_ajax_nopriv_dame_generate_parental_auth', array( $this, 'generate_parental_auth' ) );

		add_action( 'wp_ajax_dame_download_doc', array( $this, 'download_stored_document' ) );

		add_action( 'wp_ajax_dame_download_attestation_pdf', array( $this, 'ajax_download_attestation_pdf' ) );
		add_action( 'wp_ajax_dame_send_attestation_email', array( $this, 'ajax_send_attestation_email' ) );
		add_action( 'wp_ajax_dame_get_attestation_data', array( $this, 'ajax_get_attestation_data' ) );
	}

	/**
	 * Secure download endpoint for stored documents from admin.
	 */
	public function download_stored_document(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Accès non autorisé.', 'dame' ), 403 );
		}

		$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
		$type    = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! $post_id || ! wp_verify_nonce( $nonce, 'dame_download_doc_' . $post_id ) ) {
			wp_die( esc_html__( 'Vérification de sécurité échouée.', 'dame' ), 403 );
		}

		$current_season_tag_id = (int) get_option( 'dame_current_season_tag_id' );
		$season_suffix         = $current_season_tag_id > 0 ? '_' . $current_season_tag_id : '';

		$meta_key = ( 'parental' === $type ) ? '_dame_doc_parental_auth_path' : '_dame_doc_health_attestation_path';

		$filename = (string) get_post_meta( $post_id, $meta_key . $season_suffix, true );
		if ( empty( $filename ) ) {
			$filename = (string) get_post_meta( $post_id, $meta_key, true );
		}

		if ( empty( $filename ) ) {
			wp_die( esc_html__( 'Document non trouvé.', 'dame' ), 404 );
		}

		$path = Document_Storage::get_absolute_path( $filename );
		if ( ! $path || ! file_exists( $path ) ) {
			wp_die( esc_html__( 'Fichier introuvable sur le serveur.', 'dame' ), 404 );
		}

		$this->stream_pdf( $path, basename( $path ) );
		exit;
	}

	/**
	 * Build Health Form FPDI instance.
	 *
	 * @param int                  $post_id             Post ID.
	 * @param string|null          $signature_file_path Optional absolute path to PNG signature.
	 * @param array<string, mixed> $audit_data Optional audit data (timestamp, ip).
	 * @return Fpdi
	 * @throws Exception When validation or file reading fails.
	 */
	public function build_health_pdf( int $post_id, ?string $signature_file_path = null, array $audit_data = array() ): Fpdi {
		$first_name     = get_post_meta( $post_id, '_dame_first_name', true );
		$last_name      = get_post_meta( $post_id, '_dame_last_name', true );
		$birth_date_str = get_post_meta( $post_id, '_dame_birth_date', true );
		$city           = get_post_meta( $post_id, '_dame_city', true );

		$legal_rep_1_first_name = get_post_meta( $post_id, '_dame_legal_rep_1_first_name', true );
		$legal_rep_1_last_name  = get_post_meta( $post_id, '_dame_legal_rep_1_last_name', true );
		$legal_rep_1_city       = get_post_meta( $post_id, '_dame_legal_rep_1_city', true );

		if ( empty( $first_name ) || empty( $last_name ) || empty( $birth_date_str ) || empty( $city ) ) {
			throw new Exception( esc_html__( 'Données de préinscription manquantes ou invalides.', 'dame' ) );
		}

		try {
			$birth_date = new DateTime( (string) $birth_date_str );
			$now        = new DateTime();
			$age        = $now->diff( $birth_date )->y;
		} catch ( Exception $e ) {
			$age = 0;
		}

		$full_name_adherent_for_pdf = Utils::format_lastname( (string) $last_name ) . ' ' . Utils::format_firstname( (string) $first_name );
		$full_name_adherent_for_pdf = mb_convert_encoding( $full_name_adherent_for_pdf, 'ISO-8859-1', 'UTF-8' );
		$city_for_pdf               = mb_convert_encoding( (string) $city, 'ISO-8859-1', 'UTF-8' );
		$current_date               = gmdate( 'd/m/Y' );

		if ( ! class_exists( '\setasign\Fpdi\Fpdi' ) ) {
			if ( file_exists( DAME_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
				require_once DAME_PLUGIN_DIR . 'vendor/autoload.php';
			} else {
				throw new Exception( esc_html__( 'Les dépendances PDF (FPDI/FPDF) sont introuvables. Veuillez exécuter "composer install".', 'dame' ) );
			}
		}

		$template_path = DAME_PLUGIN_DIR . 'assets/pdf/ffe_attestation_sante.pdf';
		if ( ! file_exists( $template_path ) ) {
			throw new Exception( esc_html__( 'Le modèle PDF ffe_attestation_sante.pdf est introuvable.', 'dame' ) );
		}

		$pdf = new Fpdi();
		$pdf->AddPage();
		$pdf->setSourceFile( $template_path );
		$tpl_id = $pdf->importPage( 1 );
		$pdf->useTemplate( $tpl_id, 0, 0, 210, 297 );

		$pdf->SetFont( 'Helvetica' );
		$pdf->SetTextColor( 0, 0, 0 );

		if ( $age >= 18 ) {
			// Section 1 : Licencié(e)s majeurs.
			$pdf->SetFontSize( 10 );
			$pdf->SetXY( 57, 128 );
			$pdf->Write( 0, $full_name_adherent_for_pdf );

			$pdf->SetXY( 34, 156 );
			$pdf->Write( 0, $current_date );

			$pdf->SetXY( 61, 156 );
			$pdf->Write( 0, $city_for_pdf );

			if ( $signature_file_path && file_exists( $signature_file_path ) ) {
				$pdf->Image( $signature_file_path, 146, 150, 45, 16, 'PNG' );

				if ( ! empty( $audit_data['timestamp'] ) ) {
					$pdf->SetFontSize( 7 );
					$pdf->SetTextColor( 100, 100, 100 );
					$pdf->SetXY( 146, 168.5 );
					$audit_str = 'Signé le ' . gmdate( 'd/m/Y H:i', (int) $audit_data['timestamp'] );
					if ( ! empty( $audit_data['ip'] ) ) {
						$audit_str .= ' (IP: ' . (string) $audit_data['ip'] . ')';
					}
					$pdf->Write( 0, mb_convert_encoding( $audit_str, 'ISO-8859-1', 'UTF-8' ) );
				}
			}
		} else {
			// Section 2 : Licencié(e)s mineurs.
			if ( empty( $legal_rep_1_first_name ) || empty( $legal_rep_1_last_name ) ) {
				throw new Exception( esc_html__( 'Données du représentant légal manquantes pour un adhérent mineur.', 'dame' ) );
			}

			$full_name_rep1_for_pdf   = Utils::format_lastname( (string) $legal_rep_1_last_name ) . ' ' . Utils::format_firstname( (string) $legal_rep_1_first_name );
			$full_name_rep1_for_pdf   = mb_convert_encoding( $full_name_rep1_for_pdf, 'ISO-8859-1', 'UTF-8' );
			$legal_rep_1_city_for_pdf = ! empty( $legal_rep_1_city ) ? mb_convert_encoding( (string) $legal_rep_1_city, 'ISO-8859-1', 'UTF-8' ) : $city_for_pdf;

			$pdf->SetFontSize( 10 );
			$pdf->SetXY( 51, 181 );
			$pdf->Write( 0, $full_name_rep1_for_pdf );

			$pdf->SetXY( 117, 190 );
			$pdf->Write( 0, $full_name_adherent_for_pdf );

			$pdf->SetXY( 30, 227 );
			$pdf->Write( 0, $current_date );

			$pdf->SetXY( 60, 227 );
			$pdf->Write( 0, $legal_rep_1_city_for_pdf );

			if ( $signature_file_path && file_exists( $signature_file_path ) ) {
				$pdf->Image( $signature_file_path, 130, 232, 45, 16, 'PNG' );

				if ( ! empty( $audit_data['timestamp'] ) ) {
					$pdf->SetFontSize( 7 );
					$pdf->SetTextColor( 100, 100, 100 );
					$pdf->SetXY( 130, 250.5 );
					$audit_str = 'Signé le ' . gmdate( 'd/m/Y H:i', (int) $audit_data['timestamp'] );
					if ( ! empty( $audit_data['ip'] ) ) {
						$audit_str .= ' (IP: ' . (string) $audit_data['ip'] . ')';
					}
					$pdf->Write( 0, mb_convert_encoding( $audit_str, 'ISO-8859-1', 'UTF-8' ) );
				}
			}
		}

		return $pdf;
	}

	/**
	 * Build Parental Authorization FPDI instance.
	 *
	 * @param int                  $post_id             Post ID.
	 * @param string|null          $signature_file_path Optional absolute path to PNG signature.
	 * @param array<string, mixed> $audit_data Optional audit data (timestamp, ip).
	 * @return Fpdi
	 * @throws Exception When validation or file reading fails.
	 */
	public function build_parental_pdf( int $post_id, ?string $signature_file_path = null, array $audit_data = array() ): Fpdi {
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

		if ( empty( $first_name ) || empty( $last_name ) || empty( $birth_date_str ) || empty( $city ) ) {
			throw new Exception( esc_html__( 'Données de préinscription manquantes ou invalides.', 'dame' ) );
		}

		if ( empty( $rl1_first_name ) || empty( $rl1_last_name ) ) {
			throw new Exception( esc_html__( 'Données du représentant légal 1 manquantes.', 'dame' ) );
		}

		$birth_date_obj = DateTime::createFromFormat( 'Y-m-d', (string) $birth_date_str );
		$today          = new DateTime();
		$age            = $birth_date_obj ? $today->diff( $birth_date_obj )->y : 0;

		if ( $age >= 18 ) {
			throw new Exception( esc_html__( "L'autorisation parentale ne peut être générée que pour un adhérent mineur.", 'dame' ) );
		}

		$adherent_full_name            = mb_convert_encoding( Utils::generate_adherent_title( $post_id ), 'ISO-8859-1', 'UTF-8' );
		$birth_ts                      = strtotime( (string) $birth_date_str );
		$raw_birth_date                = false !== $birth_ts ? wp_date( 'd/m/Y', $birth_ts, new \DateTimeZone( 'UTC' ) ) : '';
		$adherent_birth_date_formatted = mb_convert_encoding( false !== $raw_birth_date ? $raw_birth_date : '', 'ISO-8859-1', 'UTF-8' );
		$adherent_city                 = mb_convert_encoding( (string) $city, 'ISO-8859-1', 'UTF-8' );
		$raw_curr_date                 = wp_date( 'd/m/Y' );
		$current_date                  = false !== $raw_curr_date ? $raw_curr_date : gmdate( 'd/m/Y' );
		$rl1_full_name                 = mb_convert_encoding( Utils::format_lastname( (string) $rl1_last_name ) . ' ' . Utils::format_firstname( (string) $rl1_first_name ), 'ISO-8859-1', 'UTF-8' );

		if ( ! class_exists( '\setasign\Fpdi\Fpdi' ) ) {
			if ( file_exists( DAME_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
				require_once DAME_PLUGIN_DIR . 'vendor/autoload.php';
			} else {
				throw new Exception( esc_html__( 'Les dépendances PDF (FPDI/FPDF) sont introuvables. Veuillez exécuter "composer install".', 'dame' ) );
			}
		}

		$template_path = DAME_PLUGIN_DIR . 'assets/pdf/el_autorisation_parentale.pdf';
		if ( ! file_exists( $template_path ) ) {
			throw new Exception( esc_html__( 'Modèle PDF el_autorisation_parentale.pdf introuvable.', 'dame' ) );
		}

		$pdf = new Fpdi();
		$pdf->AddPage();
		$pdf->SetAutoPageBreak( true, 0 );
		$pdf->setSourceFile( $template_path );
		$tpl_id = $pdf->importPage( 1 );
		$pdf->useTemplate( $tpl_id, 0, 0, 210, 297 );

		$pdf->SetFont( 'Helvetica', '', 12 );
		$pdf->SetTextColor( 0, 0, 0 );

		$pdf->SetXY( 50, 72 );
		$pdf->Write( 0, $rl1_full_name );

		$pdf->SetXY( 88, 88 );
		$pdf->Write( 0, $adherent_full_name );

		$pdf->SetXY( 163, 88 );
		$pdf->Write( 0, $adherent_birth_date_formatted );

		$pdf->SetXY( 30, 191 );
		$pdf->Write( 0, $adherent_city );

		$pdf->SetXY( 27, 201 );
		$pdf->Write( 0, $current_date );

		if ( $signature_file_path && file_exists( $signature_file_path ) ) {
			$pdf->Image( $signature_file_path, 140, 190, 48, 18, 'PNG' );

			if ( ! empty( $audit_data['timestamp'] ) ) {
				$pdf->SetFontSize( 7 );
				$pdf->SetTextColor( 100, 100, 100 );
				$pdf->SetXY( 135, 210 );
				$audit_str = 'Signé le ' . gmdate( 'd/m/Y H:i', (int) $audit_data['timestamp'] );
				if ( ! empty( $audit_data['ip'] ) ) {
					$audit_str .= ' (IP: ' . (string) $audit_data['ip'] . ')';
				}
				$pdf->Write( 0, mb_convert_encoding( $audit_str, 'ISO-8859-1', 'UTF-8' ) );
				$pdf->SetFontSize( 12 );
				$pdf->SetTextColor( 0, 0, 0 );
			}
		}

		// Rep 1 data.
		$pdf->SetXY( 25, 248 );
		$pdf->Write( 0, mb_convert_encoding( mb_strtoupper( (string) $rl1_last_name, 'UTF-8' ), 'ISO-8859-1', 'UTF-8' ) );

		$pdf->SetXY( 30, 255 );
		$pdf->Write( 0, mb_convert_encoding( (string) $rl1_first_name, 'ISO-8859-1', 'UTF-8' ) );
		if ( ! empty( $rl1_birth_place ) ) {
			$pdf->SetXY( 48, 264 );
			$pdf->Write( 0, mb_convert_encoding( (string) $rl1_birth_place, 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl1_birth_date ) ) {
			$pdf->SetXY( 54, 270 );
			$rl1_ts             = strtotime( (string) $rl1_birth_date );
			$rl1_date_formatted = false !== $rl1_ts ? (string) wp_date( 'd/m/Y', $rl1_ts, new \DateTimeZone( 'UTC' ) ) : '';
			$pdf->Write( 0, mb_convert_encoding( $rl1_date_formatted, 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl1_profession ) ) {
			$pdf->SetXY( 35, 279 );
			$pdf->Write( 0, mb_convert_encoding( (string) $rl1_profession, 'ISO-8859-1', 'UTF-8' ) );
		}

		// Rep 2 data.
		if ( ! empty( $rl2_last_name ) ) {
			$pdf->SetXY( 125, 248 );
			$pdf->Write( 0, mb_convert_encoding( mb_strtoupper( (string) $rl2_last_name, 'UTF-8' ), 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl2_first_name ) ) {
			$pdf->SetXY( 130, 255 );
			$pdf->Write( 0, mb_convert_encoding( (string) $rl2_first_name, 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl2_birth_place ) ) {
			$pdf->SetXY( 148, 264 );
			$pdf->Write( 0, mb_convert_encoding( (string) $rl2_birth_place, 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl2_birth_date ) ) {
			$pdf->SetXY( 154, 270 );
			$rl2_ts             = strtotime( (string) $rl2_birth_date );
			$rl2_date_formatted = false !== $rl2_ts ? (string) wp_date( 'd/m/Y', $rl2_ts, new \DateTimeZone( 'UTC' ) ) : '';
			$pdf->Write( 0, mb_convert_encoding( $rl2_date_formatted, 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl2_profession ) ) {
			$pdf->SetXY( 135, 279 );
			$pdf->Write( 0, mb_convert_encoding( (string) $rl2_profession, 'ISO-8859-1', 'UTF-8' ) );
		}

		return $pdf;
	}

	/**
	 * Generate and persist signed health document into Document_Storage.
	 *
	 * @param int                  $post_id             Post ID.
	 * @param string|null          $signature_file_path Absolute path to signature image.
	 * @param array<string, mixed> $audit_data Audit information.
	 * @return string|null Saved relative filename.
	 */
	public function save_signed_health_doc( int $post_id, ?string $signature_file_path = null, array $audit_data = array() ): ?string {
		try {
			$pdf     = $this->build_health_pdf( $post_id, $signature_file_path, $audit_data );
			$content = (string) $pdf->Output( 'S' );

			$last_name  = (string) get_post_meta( $post_id, '_dame_last_name', true );
			$first_name = (string) get_post_meta( $post_id, '_dame_first_name', true );
			$filename   = sanitize_file_name( 'attestation_sante_' . $last_name . '_' . $first_name . '.pdf' );

			return Document_Storage::save_file( $content, $filename );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Generate and persist signed parental authorization document into Document_Storage.
	 *
	 * @param int                  $post_id             Post ID.
	 * @param string|null          $signature_file_path Absolute path to signature image.
	 * @param array<string, mixed> $audit_data Audit information.
	 * @return string|null Saved relative filename.
	 */
	public function save_signed_parental_doc( int $post_id, ?string $signature_file_path = null, array $audit_data = array() ): ?string {
		try {
			$pdf     = $this->build_parental_pdf( $post_id, $signature_file_path, $audit_data );
			$content = (string) $pdf->Output( 'S' );

			$last_name  = (string) get_post_meta( $post_id, '_dame_last_name', true );
			$first_name = (string) get_post_meta( $post_id, '_dame_first_name', true );
			$filename   = sanitize_file_name( 'attestation_parentale_' . $last_name . '_' . $first_name . '.pdf' );

			return Document_Storage::save_file( $content, $filename );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Generate Health Form PDF (Download HTTP action).
	 */
	public function generate_health_form(): void {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! isset( $_GET['post_id'] ) || empty( $nonce ) ) {
			wp_die( esc_html__( 'Paramètres invalides.', 'dame' ), 400 );
		}

		$post_id = intval( $_GET['post_id'] );

		if ( ! wp_verify_nonce( $nonce, 'dame_generate_health_form_' . $post_id ) ) {
			wp_die( esc_html__( 'La vérification de sécurité a échoué.', 'dame' ), 403 );
		}

		// Check if signed document already exists on disk.
		$stored_doc = (string) get_post_meta( $post_id, '_dame_doc_health_attestation_path', true );
		if ( ! empty( $stored_doc ) ) {
			$abs_path = Document_Storage::get_absolute_path( $stored_doc );
			if ( $abs_path && file_exists( $abs_path ) ) {
				$this->stream_pdf( $abs_path, basename( $abs_path ) );
				exit;
			}
		}

		try {
			$pdf        = $this->build_health_pdf( $post_id );
			$last_name  = (string) get_post_meta( $post_id, '_dame_last_name', true );
			$first_name = (string) get_post_meta( $post_id, '_dame_first_name', true );
			$filename   = sanitize_file_name( 'attestation_sante_' . $last_name . '_' . $first_name . '.pdf' );
			$pdf->Output( 'D', $filename );
			exit;
		} catch ( Exception $e ) {
			wp_die(
				/* translators: %s: Message d'erreur PDF */
				esc_html( sprintf( __( 'Erreur lors de la génération du PDF : %s', 'dame' ), $e->getMessage() ) ),
				500
			);
		}
	}

	/**
	 * Generate Parental Authorization PDF (Download HTTP action).
	 */
	public function generate_parental_auth(): void {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! isset( $_GET['post_id'] ) || empty( $nonce ) ) {
			wp_die( esc_html__( 'Paramètres invalides.', 'dame' ), 400 );
		}

		$post_id = intval( $_GET['post_id'] );

		if ( ! wp_verify_nonce( $nonce, 'dame_generate_parental_auth_' . $post_id ) ) {
			wp_die( esc_html__( 'La vérification de sécurité a échoué.', 'dame' ), 403 );
		}

		// Check if signed document already exists on disk.
		$stored_doc = (string) get_post_meta( $post_id, '_dame_doc_parental_auth_path', true );
		if ( ! empty( $stored_doc ) ) {
			$abs_path = Document_Storage::get_absolute_path( $stored_doc );
			if ( $abs_path && file_exists( $abs_path ) ) {
				$this->stream_pdf( $abs_path, basename( $abs_path ) );
				exit;
			}
		}

		try {
			$pdf        = $this->build_parental_pdf( $post_id );
			$last_name  = (string) get_post_meta( $post_id, '_dame_last_name', true );
			$first_name = (string) get_post_meta( $post_id, '_dame_first_name', true );
			$filename   = sanitize_file_name( 'attestation_parentale_' . $last_name . '_' . $first_name . '.pdf' );
			$pdf->Output( 'D', $filename );
			exit;
		} catch ( Exception $e ) {
			wp_die(
				/* translators: %s: Message d'erreur PDF */
				esc_html( sprintf( __( 'Erreur lors de la génération du PDF : %s', 'dame' ), $e->getMessage() ) ),
				500
			);
		}
	}

	/**
	 * Output PDF file directly to browser.
	 *
	 * @param string $path Absolute file path.
	 * @param string $filename Download filename.
	 */
	public function stream_pdf( string $path, string $filename ): void {
		if ( ! file_exists( $path ) ) {
			return;
		}

		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . esc_attr( $filename ) . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: public' );

		readfile( $path );
		exit;
	}

	/**
	 * Build single Attestation de paiement FPDI / FPDF page.
	 *
	 * @param int                  $adherent_id Post ID of the adherent.
	 * @param array<string, mixed> $custom_data Optional data overrides (amount, payment_date, payment_method, status).
	 * @param Fpdi|null            $pdf         Optional existing Fpdi instance for bulk generation.
	 * @return Fpdi
	 * @throws Exception When validation fails.
	 */
	public function build_attestation_pdf( int $adherent_id, array $custom_data = array(), ?Fpdi $pdf = null ): Fpdi {
		if ( ! class_exists( '\setasign\Fpdi\Fpdi' ) ) {
			if ( file_exists( DAME_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
				require_once DAME_PLUGIN_DIR . 'vendor/autoload.php';
			} else {
				throw new Exception( esc_html__( 'Les dépendances PDF (FPDI/FPDF) sont introuvables. Veuillez exécuter "composer install".', 'dame' ) );
			}
		}

		$options               = get_option( 'dame_options', array() );
		$current_season_tag_id = (int) get_option( 'dame_current_season_tag_id' );
		$season_suffix         = $current_season_tag_id > 0 ? '_' . $current_season_tag_id : '';

		$current_season_term = $current_season_tag_id > 0 ? get_term( $current_season_tag_id, 'dame_saison_adhesion' ) : null;
		$season_name         = ( $current_season_term && ! is_wp_error( $current_season_term ) ) ? (string) $current_season_term->name : '2026/2027';

		// Association details.
		$assoc_name     = ! empty( $options['assoc_name'] ) ? (string) $options['assoc_name'] : get_bloginfo( 'name' );
		$assoc_rna      = ! empty( $options['assoc_rna'] ) ? (string) $options['assoc_rna'] : '';
		$assoc_siren    = ! empty( $options['assoc_siren'] ) ? (string) $options['assoc_siren'] : '';
		$assoc_email    = ! empty( $options['assoc_email'] ) ? (string) $options['assoc_email'] : get_bloginfo( 'admin_email' );
		$assoc_website  = ! empty( $options['assoc_website'] ) ? (string) $options['assoc_website'] : home_url();
		$assoc_city     = ! empty( $options['assoc_siege_city'] )
			? (string) $options['assoc_siege_city']
			: ( ! empty( $options['assoc_city'] ) ? (string) $options['assoc_city'] : '' );
		$assoc_rep_name = ! empty( $options['assoc_rep_name'] ) ? (string) $options['assoc_rep_name'] : '';
		$assoc_rep_role = ! empty( $options['assoc_rep_role'] ) ? (string) $options['assoc_rep_role'] : __( 'Président(e)', 'dame' );

		// Build address string (Prioritize registered office / siège social, fallback to playing room / club address).
		$addr_parts = array();
		if ( ! empty( $options['assoc_siege_address_1'] ) ) {
			$addr_parts[] = (string) $options['assoc_siege_address_1'];
			if ( ! empty( $options['assoc_siege_address_2'] ) ) {
				$addr_parts[] = (string) $options['assoc_siege_address_2'];
			}
			$siege_city_line = trim( ( ! empty( $options['assoc_siege_postal_code'] ) ? (string) $options['assoc_siege_postal_code'] . ' ' : '' ) . (string) ( $options['assoc_siege_city'] ?? '' ) );
			if ( ! empty( $siege_city_line ) ) {
				$addr_parts[] = $siege_city_line;
			}
		} else {
			if ( ! empty( $options['assoc_address_1'] ) ) {
				$addr_parts[] = (string) $options['assoc_address_1'];
			}
			if ( ! empty( $options['assoc_address_2'] ) ) {
				$addr_parts[] = (string) $options['assoc_address_2'];
			}
			$city_line = trim( ( ! empty( $options['assoc_postal_code'] ) ? (string) $options['assoc_postal_code'] . ' ' : '' ) . ( ! empty( $options['assoc_city'] ) ? (string) $options['assoc_city'] : '' ) );
			if ( ! empty( $city_line ) ) {
				$addr_parts[] = $city_line;
			}
		}
		$assoc_address = implode( ', ', $addr_parts );

		// Member details.
		$first_name     = (string) get_post_meta( $adherent_id, '_dame_first_name', true );
		$last_name      = (string) get_post_meta( $adherent_id, '_dame_last_name', true );
		$birth_date_raw = (string) get_post_meta( $adherent_id, '_dame_birth_date', true );
		$ffe_licence    = (string) get_post_meta( $adherent_id, '_dame_license_number', true );
		if ( empty( $ffe_licence ) ) {
			$ffe_licence = (string) get_post_meta( $adherent_id, '_dame_ffe_licence', true );
		}

		if ( empty( $first_name ) || empty( $last_name ) ) {
			throw new Exception( esc_html__( 'Nom ou prénom de l\'adhérent manquant.', 'dame' ) );
		}

		$birth_date_formatted = '';
		if ( ! empty( $birth_date_raw ) ) {
			$ts = strtotime( $birth_date_raw );
			if ( false !== $ts ) {
				$birth_date_formatted = (string) wp_date( 'd/m/Y', $ts, new \DateTimeZone( 'UTC' ) );
			}
		}

		// Payment status, amount, date and method.
		$status = isset( $custom_data['status'] ) && '' !== $custom_data['status']
			? (string) $custom_data['status']
			: (string) get_post_meta( $adherent_id, '_dame_payment_status' . $season_suffix, true );
		if ( empty( $status ) ) {
			$status = Data_Provider::is_adherent_renewal( $adherent_id, $current_season_tag_id ) ? 'renewal' : 'first_registration';
		}

		if ( isset( $custom_data['amount'] ) && '' !== $custom_data['amount'] ) {
			$amount = max( 0.0, (float) $custom_data['amount'] );
		} else {
			$saved_amount = get_post_meta( $adherent_id, '_dame_payment_amount' . $season_suffix, true );
			if ( '' !== $saved_amount && false !== $saved_amount ) {
				$amount = (float) $saved_amount;
			} else {
				$amount = Data_Provider::calculate_adherent_fee( $adherent_id, $current_season_tag_id, $status );
			}
		}

		if ( isset( $custom_data['payment_date'] ) && '' !== $custom_data['payment_date'] ) {
			$payment_date_str = (string) $custom_data['payment_date'];
		} else {
			$payment_date_str = (string) get_post_meta( $adherent_id, '_dame_payment_date' . $season_suffix, true );
			if ( empty( $payment_date_str ) ) {
				$activation_date  = (string) get_post_meta( $adherent_id, '_dame_season_activation_date' . $season_suffix, true );
				$payment_date_str = ! empty( $activation_date ) ? $activation_date : (string) wp_date( 'Y-m-d' );
			}
		}

		$payment_date_formatted = '';
		if ( ! empty( $payment_date_str ) ) {
			$p_ts = strtotime( $payment_date_str );
			if ( false !== $p_ts ) {
				$payment_date_formatted = (string) wp_date( 'd/m/Y', $p_ts, new \DateTimeZone( 'UTC' ) );
			}
		}

		$payment_method = isset( $custom_data['payment_method'] ) && '' !== $custom_data['payment_method']
			? (string) $custom_data['payment_method']
			: (string) get_post_meta( $adherent_id, '_dame_payment_method' . $season_suffix, true );
		if ( empty( $payment_method ) ) {
			$payment_method = 'HelloAsso';
		}

		// Init PDF if not passed.
		if ( null === $pdf ) {
			$pdf = new Fpdi();
		}

		$pdf->AddPage( 'P', 'A4' );
		$pdf->SetMargins( 15, 15, 15 );
		$pdf->SetAutoPageBreak( false );

		$enc = static function ( string $text ): string {
			// Replace typographical characters with clean ASCII equivalents.
			$replacements = array(
				'—' => '-',
				'–' => '-',
				'’' => "'",
				'‘' => "'",
				'“' => '"',
				'”' => '"',
				'…' => '...',
				'•' => '*',
			);

			$clean_text = strtr( $text, $replacements );

			// Convert to Windows-1252 (CP1252) which maps the Euro sign '€' to byte 0x80 natively supported by FPDF core fonts.
			return (string) mb_convert_encoding( $clean_text, 'windows-1252', 'UTF-8' );
		};

		// 1. Header (Logo on left, association info next to it or on right).
		$header_y = 15.0;
		$logo_id  = ! empty( $options['assoc_logo_id'] ) ? (int) $options['assoc_logo_id'] : 0;
		$logo_x   = 15.0;
		$text_x   = 15.0;

		if ( $logo_id > 0 ) {
			$logo_path = get_attached_file( $logo_id );
			if ( $logo_path && file_exists( $logo_path ) ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$img_info = @getimagesize( $logo_path );
				if ( false !== $img_info && in_array( $img_info[2], array( IMAGETYPE_JPEG, IMAGETYPE_PNG ), true ) ) {
					$pdf->Image( $logo_path, $logo_x, $header_y, 35 );
					$text_x = 55.0;
				}
			}
		}

		$pdf->SetXY( $text_x, $header_y );
		$pdf->SetFont( 'Helvetica', 'B', 12 );
		$pdf->SetTextColor( 30, 41, 59 );
		$pdf->Cell( 0, 5.5, $enc( $assoc_name ), 0, 1, 'L' );

		$pdf->SetX( $text_x );
		$pdf->SetFont( 'Helvetica', 'I', 8.5 );
		$pdf->SetTextColor( 100, 116, 139 );
		$pdf->Cell( 0, 4.5, $enc( 'Association régie par la loi du 1er juillet 1901' ), 0, 1, 'L' );

		$legal_line = '';
		if ( ! empty( $assoc_rna ) ) {
			$legal_line .= 'RNA : ' . $assoc_rna;
		}
		if ( ! empty( $assoc_siren ) ) {
			$legal_line .= ( ! empty( $legal_line ) ? '   |   ' : '' ) . 'SIREN : ' . $assoc_siren;
		}
		if ( ! empty( $legal_line ) ) {
			$pdf->SetX( $text_x );
			$pdf->SetFont( 'Helvetica', '', 8.5 );
			$pdf->Cell( 0, 4.5, $enc( $legal_line ), 0, 1, 'L' );
		}

		if ( ! empty( $assoc_address ) ) {
			$pdf->SetX( $text_x );
			$pdf->SetFont( 'Helvetica', '', 8.5 );
			$pdf->Cell( 0, 4.5, $enc( 'Siège social : ' . $assoc_address ), 0, 1, 'L' );
		}

		$contact_line = '';
		if ( ! empty( $assoc_email ) ) {
			$contact_line .= 'Courriel : ' . $assoc_email;
		}
		if ( ! empty( $assoc_website ) ) {
			$contact_line .= ( ! empty( $contact_line ) ? '   |   ' : '' ) . 'Site web : ' . $assoc_website;
		}
		if ( ! empty( $contact_line ) ) {
			$pdf->SetX( $text_x );
			$pdf->SetFont( 'Helvetica', '', 8.5 );
			$pdf->Cell( 0, 4.5, $enc( $contact_line ), 0, 1, 'L' );
		}

		// Divider under header.
		$pdf->SetY( 48 );
		$pdf->SetDrawColor( 226, 232, 240 );
		$pdf->SetLineWidth( 0.4 );
		$pdf->Line( 15, 48, 195, 48 );

		// 2. Title Box.
		$pdf->SetY( 54 );
		$pdf->SetFont( 'Helvetica', 'B', 15 );
		$pdf->SetTextColor( 15, 23, 42 );
		$pdf->Cell( 0, 7.5, $enc( "ATTESTATION D'ADHÉSION ET DE PAIEMENT" ), 0, 1, 'C' );

		$pdf->SetFont( 'Helvetica', 'B', 11 );
		$pdf->SetTextColor( 37, 99, 235 );
		$pdf->Cell( 0, 6, $enc( 'Saison sportive ' . $season_name ), 0, 1, 'C' );

		// 3. Declaration of representative.
		$pdf->SetY( 73 );
		$pdf->SetFont( 'Helvetica', '', 10.5 );
		$pdf->SetTextColor( 51, 65, 85 );

		$rep_designation = ! empty( $assoc_rep_name ) ? $assoc_rep_name : 'le (la) représentant(e) légal(e)';
		$rep_text        = sprintf(
			'Je soussigné(e), %s, agissant en qualité de %s de l\'association %s, certifie par la présente que :',
			$rep_designation,
			$assoc_rep_role,
			$assoc_name
		);
		$pdf->MultiCell( 0, 5.5, $enc( $rep_text ), 0, 'L' );

		// Helper to draw section box.
		$draw_section_header = static function ( string $title, float $y ) use ( $pdf, $enc ): void {
			$pdf->SetY( $y );
			$pdf->SetFillColor( 241, 245, 249 );
			$pdf->SetDrawColor( 203, 213, 225 );
			$pdf->SetLineWidth( 0.2 );
			$pdf->Rect( 15, $y, 180, 7.5, 'DF' );
			$pdf->SetFont( 'Helvetica', 'B', 9.5 );
			$pdf->SetTextColor( 30, 41, 59 );
			$pdf->SetXY( 18, $y + 1 );
			$pdf->Cell( 174, 5.5, $enc( mb_strtoupper( $title, 'UTF-8' ) ), 0, 0, 'L' );
		};

		// 4. Section Bénéficiaire.
		$benef_y = $pdf->GetY() + 5.0;
		$draw_section_header( 'Bénéficiaire', $benef_y );

		$pdf->SetY( $benef_y + 9.5 );
		$adherent_full_name = Utils::format_lastname( $last_name ) . ' ' . Utils::format_firstname( $first_name );

		$pdf->SetFont( 'Helvetica', 'B', 9.5 );
		$pdf->SetTextColor( 71, 85, 105 );
		$pdf->Cell( 45, 6, $enc( 'Nom et prénom :' ), 0, 0, 'L' );
		$pdf->SetFont( 'Helvetica', 'B', 10 );
		$pdf->SetTextColor( 15, 23, 42 );
		$pdf->Cell( 0, 6, $enc( $adherent_full_name ), 0, 1, 'L' );

		$pdf->SetFont( 'Helvetica', 'B', 9.5 );
		$pdf->SetTextColor( 71, 85, 105 );
		$pdf->Cell( 45, 6, $enc( 'Date de naissance :' ), 0, 0, 'L' );
		$pdf->SetFont( 'Helvetica', '', 9.5 );
		$pdf->SetTextColor( 15, 23, 42 );
		$pdf->Cell( 0, 6, $enc( ! empty( $birth_date_formatted ) ? $birth_date_formatted : 'Non renseignée' ), 0, 1, 'L' );

		$pdf->SetFont( 'Helvetica', 'B', 9.5 );
		$pdf->SetTextColor( 71, 85, 105 );
		$pdf->Cell( 45, 6, $enc( 'Numéro de licence FFE :' ), 0, 0, 'L' );
		$pdf->SetFont( 'Helvetica', '', 9.5 );
		$pdf->SetTextColor( 15, 23, 42 );
		$pdf->Cell( 0, 6, $enc( ! empty( $ffe_licence ) ? $ffe_licence : 'En cours d\'attribution' ), 0, 1, 'L' );

		// 5. Section Règlement & Objet.
		$reg_y = $pdf->GetY() + 4.0;
		$draw_section_header( 'Objet et Modalités du règlement', $reg_y );

		$pdf->SetY( $reg_y + 9.5 );
		$amount_words  = Utils::number_to_french_words( $amount );
		$amount_digits = number_format( $amount, 2, ',', ' ' ) . ' €';

		$objet_text = sprintf(
			'La cotisation annuelle, comprenant l\'adhésion au club et la licence FFE pour la saison %s, a été intégralement acquittée pour un montant de %s (%s).',
			$season_name,
			$amount_digits,
			$amount_words
		);
		$pdf->SetFont( 'Helvetica', '', 9.5 );
		$pdf->SetTextColor( 51, 65, 85 );
		$pdf->MultiCell( 0, 5.2, $enc( $objet_text ), 0, 'L' );

		$pdf->Ln( 2 );

		$pdf->SetFont( 'Helvetica', 'B', 9.5 );
		$pdf->SetTextColor( 71, 85, 105 );
		$pdf->Cell( 45, 6, $enc( 'Date du règlement :' ), 0, 0, 'L' );
		$pdf->SetFont( 'Helvetica', 'B', 9.5 );
		$pdf->SetTextColor( 15, 23, 42 );
		$pdf->Cell( 0, 6, $enc( ! empty( $payment_date_formatted ) ? $payment_date_formatted : 'Non renseignée' ), 0, 1, 'L' );

		$pdf->SetFont( 'Helvetica', 'B', 9.5 );
		$pdf->SetTextColor( 71, 85, 105 );
		$pdf->Cell( 45, 6, $enc( 'Mode de règlement :' ), 0, 0, 'L' );
		$pdf->SetFont( 'Helvetica', '', 9.5 );
		$pdf->SetTextColor( 15, 23, 42 );
		$pdf->Cell( 0, 6, $enc( $payment_method ), 0, 1, 'L' );

		// 6. Mention légale.
		$pdf->SetY( $pdf->GetY() + 5.0 );
		$pdf->SetFont( 'Helvetica', 'I', 8.5 );
		$pdf->SetTextColor( 100, 116, 139 );
		$mention_text = 'Cette attestation est délivrée à l\'intéressé(e) pour servir et valoir ce que de droit (comité d\'entreprise, CSE, mutuelle santé, participation employeur, Pass\'Sport).';
		$pdf->MultiCell( 0, 4.5, $enc( $mention_text ), 0, 'L' );

		// 7. Footer / Signature block (Fixed position near bottom).
		$footer_y     = 210.0;
		$raw_today    = wp_date( 'd/m/Y' );
		$today_date   = is_string( $raw_today ) ? $raw_today : gmdate( 'd/m/Y' );
		$location_str = sprintf( 'Fait à %s, le %s', ! empty( $assoc_city ) ? $assoc_city : 'la ville du siège', $today_date );

		$pdf->SetXY( 110, $footer_y );
		$pdf->SetFont( 'Helvetica', '', 9.5 );
		$pdf->SetTextColor( 51, 65, 85 );
		$pdf->Cell( 85, 5.5, $enc( $location_str ), 0, 1, 'L' );

		$pdf->SetX( 110 );
		$pdf->SetFont( 'Helvetica', 'B', 9.5 );
		$pdf->Cell( 85, 5.5, $enc( 'Pour l\'association,' ), 0, 1, 'L' );

		$pdf->SetX( 110 );
		$pdf->SetFont( 'Helvetica', 'B', 10 );
		$pdf->SetTextColor( 15, 23, 42 );
		$pdf->Cell( 85, 5.5, $enc( $assoc_rep_name ), 0, 1, 'L' );

		$pdf->SetX( 110 );
		$pdf->SetFont( 'Helvetica', 'I', 9 );
		$pdf->SetTextColor( 100, 116, 139 );
		$pdf->Cell( 85, 4.5, $enc( $assoc_rep_role ), 0, 1, 'L' );

		// Incrustation cachet & signature.
		$stamp_id = ! empty( $options['assoc_stamp_signature_id'] ) ? (int) $options['assoc_stamp_signature_id'] : 0;
		if ( $stamp_id > 0 ) {
			$stamp_path = get_attached_file( $stamp_id );
			if ( $stamp_path && file_exists( $stamp_path ) ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$img_info = @getimagesize( $stamp_path );
				if ( false !== $img_info && in_array( $img_info[2], array( IMAGETYPE_JPEG, IMAGETYPE_PNG ), true ) ) {
					$pdf->Image( $stamp_path, 115, $footer_y + 20, 50 );
				}
			}
		}

		return $pdf;
	}

	/**
	 * Generates a multi-page PDF combining attestations for multiple adherents.
	 *
	 * @param array<int> $adherent_ids Array of adherent post IDs.
	 * @return Fpdi
	 * @throws Exception When generation fails or list is empty.
	 */
	public function generate_bulk_attestation_pdf( array $adherent_ids ): Fpdi {
		if ( empty( $adherent_ids ) ) {
			throw new Exception( esc_html__( 'Aucun adhérent sélectionné.', 'dame' ) );
		}

		if ( ! class_exists( '\setasign\Fpdi\Fpdi' ) ) {
			if ( file_exists( DAME_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
				require_once DAME_PLUGIN_DIR . 'vendor/autoload.php';
			} else {
				throw new Exception( esc_html__( 'Les dépendances PDF (FPDI/FPDF) sont introuvables. Veuillez exécuter "composer install".', 'dame' ) );
			}
		}

		$pdf = new Fpdi();
		foreach ( $adherent_ids as $id ) {
			$post_id = absint( $id );
			if ( $post_id <= 0 || 'adherent' !== get_post_type( $post_id ) ) {
				continue;
			}
			$this->build_attestation_pdf( $post_id, array(), $pdf );
		}

		return $pdf;
	}

	/**
	 * Sends the attestation PDF by email to the adherent and their legal representatives.
	 *
	 * @param int                  $adherent_id  Adherent post ID.
	 * @param array<string, mixed> $custom_data Optional payment overrides.
	 * @return array{success: bool, message: string, recipients: array<int, string>}
	 */
	public function send_attestation_email( int $adherent_id, array $custom_data = array() ): array {
		$recipients = Data_Provider::get_emails_for_adherent( $adherent_id );
		if ( empty( $recipients ) ) {
			return array(
				'success'    => false,
				'message'    => __( 'Aucune adresse e-mail valide ou autorisée trouvée pour cet adhérent.', 'dame' ),
				'recipients' => array(),
			);
		}

		try {
			$pdf     = $this->build_attestation_pdf( $adherent_id, $custom_data );
			$content = (string) $pdf->Output( 'S' );
		} catch ( Exception $e ) {
			return array(
				'success'    => false,
				/* translators: %s: Message d'erreur de génération PDF */
				'message'    => sprintf( __( 'Erreur lors de la génération du PDF : %s', 'dame' ), $e->getMessage() ),
				'recipients' => array(),
			);
		}

		$first_name = (string) get_post_meta( $adherent_id, '_dame_first_name', true );
		$last_name  = (string) get_post_meta( $adherent_id, '_dame_last_name', true );
		$filename   = sanitize_file_name( 'attestation_paiement_' . $last_name . '_' . $first_name . '.pdf' );

		// Create temp file in uploads scratch.
		$upload_dir = wp_upload_dir();
		$temp_dir   = trailingslashit( $upload_dir['basedir'] ) . 'dame-temp';
		if ( ! file_exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}
		$temp_file = trailingslashit( $temp_dir ) . wp_generate_password( 12, false ) . '_' . $filename;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $temp_file, $content );

		// Prepare email placeholders.
		$template_data         = Data_Provider::get_attestation_email_template();
		$options               = get_option( 'dame_options', array() );
		$current_season_tag_id = (int) get_option( 'dame_current_season_tag_id' );
		$season_suffix         = $current_season_tag_id > 0 ? '_' . $current_season_tag_id : '';
		$current_season_term   = $current_season_tag_id > 0 ? get_term( $current_season_tag_id, 'dame_saison_adhesion' ) : null;
		$season_name           = ( $current_season_term && ! is_wp_error( $current_season_term ) ) ? (string) $current_season_term->name : '2026/2027';

		$assoc_name    = ! empty( $options['assoc_name'] ) ? (string) $options['assoc_name'] : get_bloginfo( 'name' );
		$assoc_website = ! empty( $options['assoc_website'] ) ? (string) $options['assoc_website'] : home_url();

		$amount = isset( $custom_data['amount'] )
			? (float) $custom_data['amount']
			: (float) get_post_meta( $adherent_id, '_dame_payment_amount' . $season_suffix, true );
		if ( $amount <= 0.0 ) {
			$amount = Data_Provider::calculate_adherent_fee( $adherent_id, $current_season_tag_id );
		}

		$payment_date = isset( $custom_data['payment_date'] )
			? (string) $custom_data['payment_date']
			: (string) get_post_meta( $adherent_id, '_dame_payment_date' . $season_suffix, true );
		$payment_ts   = strtotime( $payment_date );
		$p_date_fr    = false !== $payment_ts ? (string) wp_date( 'd/m/Y', $payment_ts, new \DateTimeZone( 'UTC' ) ) : $payment_date;

		$payment_method = isset( $custom_data['payment_method'] )
			? (string) $custom_data['payment_method']
			: (string) get_post_meta( $adherent_id, '_dame_payment_method' . $season_suffix, true );
		if ( empty( $payment_method ) ) {
			$payment_method = 'HelloAsso';
		}

		$placeholders = array(
			'{prenom}'        => Utils::format_firstname( $first_name ),
			'{nom}'           => Utils::format_lastname( $last_name ),
			'{saison}'        => $season_name,
			'{montant}'       => number_format( $amount, 2, ',', ' ' ) . ' €',
			'{date_paiement}' => ! empty( $p_date_fr ) ? $p_date_fr : (string) wp_date( 'd/m/Y' ),
			'{mode_paiement}' => $payment_method,
			'{association}'   => $assoc_name,
			'{site_web}'      => $assoc_website,
		);

		$subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template_data['subject'] );
		$body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template_data['body'] );

		$headers     = array( 'Content-Type: text/plain; charset=UTF-8' );
		$attachments = array( $temp_file );

		$sent = wp_mail( $recipients, $subject, $body, $headers, $attachments );

		// Clean temp file.
		if ( file_exists( $temp_file ) ) {
			wp_delete_file( $temp_file );
		}

		if ( ! $sent ) {
			return array(
				'success'    => false,
				'message'    => __( 'Échec de l\'envoi de l\'e-mail par le serveur de messagerie.', 'dame' ),
				'recipients' => $recipients,
			);
		}

		return array(
			'success'    => true,
			'message'    => sprintf(
				/* translators: %s: liste des emails destinataires */
				__( 'Attestation envoyée avec succès à : %s', 'dame' ),
				implode( ', ', $recipients )
			),
			'recipients' => $recipients,
		);
	}

	/**
	 * AJAX endpoint for downloading single or bulk attestation PDF.
	 */
	public function ajax_download_attestation_pdf(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Accès non autorisé.', 'dame' ), 403 );
		}

		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'dame_attestation_action' ) ) {
			wp_die( esc_html__( 'Vérification de sécurité échouée.', 'dame' ), 403 );
		}

		$adherent_ids = array();
		if ( isset( $_REQUEST['adherent_ids'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_ids = wp_unslash( $_REQUEST['adherent_ids'] );
			if ( is_array( $raw_ids ) ) {
				$adherent_ids = array_map( 'absint', $raw_ids );
			} elseif ( is_string( $raw_ids ) && '' !== $raw_ids ) {
				$adherent_ids = array_map( 'absint', explode( ',', sanitize_text_field( $raw_ids ) ) );
			}
		} elseif ( isset( $_REQUEST['adherent_id'] ) ) {
			$adherent_ids = array( absint( $_REQUEST['adherent_id'] ) );
		}

		$adherent_ids = array_values( array_filter( $adherent_ids, static fn( $id ) => $id > 0 ) );
		if ( empty( $adherent_ids ) ) {
			wp_die( esc_html__( 'Aucun adhérent spécifié.', 'dame' ), 400 );
		}

		// Optional custom parameters when single download from modal.
		$custom_data = array();
		if ( 1 === count( $adherent_ids ) ) {
			if ( isset( $_REQUEST['payment_status'] ) ) {
				$custom_data['status'] = sanitize_key( wp_unslash( $_REQUEST['payment_status'] ) );
			}
			if ( isset( $_REQUEST['payment_amount'] ) ) {
				$custom_data['amount'] = max( 0.0, (float) sanitize_text_field( wp_unslash( $_REQUEST['payment_amount'] ) ) );
			}
			if ( isset( $_REQUEST['payment_date'] ) ) {
				$custom_data['payment_date'] = sanitize_text_field( wp_unslash( $_REQUEST['payment_date'] ) );
			}
			if ( isset( $_REQUEST['payment_method'] ) ) {
				$custom_data['payment_method'] = sanitize_text_field( wp_unslash( $_REQUEST['payment_method'] ) );
			}
		}

		try {
			if ( 1 === count( $adherent_ids ) ) {
				$adherent_id = $adherent_ids[0];
				$pdf         = $this->build_attestation_pdf( $adherent_id, $custom_data );
				$last_name   = (string) get_post_meta( $adherent_id, '_dame_last_name', true );
				$first_name  = (string) get_post_meta( $adherent_id, '_dame_first_name', true );
				$filename    = sanitize_file_name( 'attestation_paiement_' . $last_name . '_' . $first_name . '.pdf' );
			} else {
				$pdf      = $this->generate_bulk_attestation_pdf( $adherent_ids );
				$filename = sanitize_file_name( 'attestations_paiement_groupees_' . gmdate( 'Ymd_His' ) . '.pdf' );
			}

			$pdf->Output( 'D', $filename );
			exit;
		} catch ( Exception $e ) {
			wp_die(
				/* translators: %s: Message d'erreur PDF */
				esc_html( sprintf( __( 'Erreur lors de la génération de l\'attestation : %s', 'dame' ), $e->getMessage() ) ),
				500
			);
		}
	}

	/**
	 * AJAX endpoint for sending attestation email.
	 */
	public function ajax_send_attestation_email(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accès non autorisé.', 'dame' ) ), 403 );
		}

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'dame_attestation_action' ) ) {
			wp_send_json_error( array( 'message' => __( 'Vérification de sécurité échouée.', 'dame' ) ), 403 );
		}

		$adherent_id = isset( $_POST['adherent_id'] ) ? absint( $_POST['adherent_id'] ) : 0;
		if ( $adherent_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Identifiant adhérent invalide.', 'dame' ) ), 400 );
		}

		$custom_data = array();
		if ( isset( $_POST['payment_status'] ) ) {
			$custom_data['status'] = sanitize_key( wp_unslash( $_POST['payment_status'] ) );
		}
		if ( isset( $_POST['payment_amount'] ) ) {
			$custom_data['amount'] = max( 0.0, (float) sanitize_text_field( wp_unslash( $_POST['payment_amount'] ) ) );
		}
		if ( isset( $_POST['payment_date'] ) ) {
			$custom_data['payment_date'] = sanitize_text_field( wp_unslash( $_POST['payment_date'] ) );
		}
		if ( isset( $_POST['payment_method'] ) ) {
			$custom_data['payment_method'] = sanitize_text_field( wp_unslash( $_POST['payment_method'] ) );
		}

		$result = $this->send_attestation_email( $adherent_id, $custom_data );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX endpoint to retrieve pre-filled attestation data for the modal.
	 */
	public function ajax_get_attestation_data(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accès non autorisé.', 'dame' ) ), 403 );
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'dame_attestation_action' ) ) {
			wp_send_json_error( array( 'message' => __( 'Vérification de sécurité échouée.', 'dame' ) ), 403 );
		}

		$adherent_id = isset( $_GET['adherent_id'] ) ? absint( $_GET['adherent_id'] ) : 0;
		if ( $adherent_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Identifiant adhérent invalide.', 'dame' ) ), 400 );
		}

		$current_season_tag_id = (int) get_option( 'dame_current_season_tag_id' );
		$season_suffix         = $current_season_tag_id > 0 ? '_' . $current_season_tag_id : '';
		$current_season_term   = $current_season_tag_id > 0 ? get_term( $current_season_tag_id, 'dame_saison_adhesion' ) : null;
		$season_name           = ( $current_season_term && ! is_wp_error( $current_season_term ) ) ? (string) $current_season_term->name : '2026/2027';

		$first_name = (string) get_post_meta( $adherent_id, '_dame_first_name', true );
		$last_name  = (string) get_post_meta( $adherent_id, '_dame_last_name', true );

		$status = (string) get_post_meta( $adherent_id, '_dame_payment_status' . $season_suffix, true );
		if ( empty( $status ) ) {
			$status = Data_Provider::is_adherent_renewal( $adherent_id, $current_season_tag_id ) ? 'renewal' : 'first_registration';
		}

		$saved_amount = get_post_meta( $adherent_id, '_dame_payment_amount' . $season_suffix, true );
		if ( '' !== $saved_amount && false !== $saved_amount ) {
			$amount = (float) $saved_amount;
		} else {
			$amount = Data_Provider::calculate_adherent_fee( $adherent_id, $current_season_tag_id, $status );
		}

		$payment_date = (string) get_post_meta( $adherent_id, '_dame_payment_date' . $season_suffix, true );
		if ( empty( $payment_date ) ) {
			$activation_date = (string) get_post_meta( $adherent_id, '_dame_season_activation_date' . $season_suffix, true );
			$default_date    = wp_date( 'Y-m-d' );
			$payment_date    = ! empty( $activation_date ) ? $activation_date : ( is_string( $default_date ) ? $default_date : '' );
		}

		$payment_method = (string) get_post_meta( $adherent_id, '_dame_payment_method' . $season_suffix, true );
		if ( empty( $payment_method ) ) {
			$payment_method = 'HelloAsso';
		}

		$emails = Data_Provider::get_emails_for_adherent( $adherent_id );

		wp_send_json_success(
			array(
				'adherent_id'    => $adherent_id,
				'adherent_name'  => Utils::format_lastname( $last_name ) . ' ' . Utils::format_firstname( $first_name ),
				'season_name'    => $season_name,
				'payment_status' => $status,
				'payment_amount' => $amount,
				'payment_date'   => $payment_date,
				'payment_method' => $payment_method,
				'emails'         => $emails,
			)
		);
	}
}
