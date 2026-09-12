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
			require_once DAME_PLUGIN_DIR . 'vendor/autoload.php';
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
			// Section 1 : Licencié(e)s majeurs
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
			// Section 2 : Licencié(e)s mineurs
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
		$adherent_birth_date_formatted = mb_convert_encoding( wp_date( 'd/m/Y', strtotime( (string) $birth_date_str ), new \DateTimeZone( 'UTC' ) ) ?: '', 'ISO-8859-1', 'UTF-8' );
		$adherent_city                 = mb_convert_encoding( (string) $city, 'ISO-8859-1', 'UTF-8' );
		$current_date                  = wp_date( 'd/m/Y' ) ?: gmdate( 'd/m/Y' );
		$rl1_full_name                 = mb_convert_encoding( Utils::format_lastname( (string) $rl1_last_name ) . ' ' . Utils::format_firstname( (string) $rl1_first_name ), 'ISO-8859-1', 'UTF-8' );

		if ( ! class_exists( '\setasign\Fpdi\Fpdi' ) ) {
			require_once DAME_PLUGIN_DIR . 'vendor/autoload.php';
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

		// Rep 1 data
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
			$pdf->Write( 0, mb_convert_encoding( (string) wp_date( 'd/m/Y', strtotime( (string) $rl1_birth_date ), new \DateTimeZone( 'UTC' ) ), 'ISO-8859-1', 'UTF-8' ) );
		}
		if ( ! empty( $rl1_profession ) ) {
			$pdf->SetXY( 35, 279 );
			$pdf->Write( 0, mb_convert_encoding( (string) $rl1_profession, 'ISO-8859-1', 'UTF-8' ) );
		}

		// Rep 2 data
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
			$pdf->Write( 0, mb_convert_encoding( (string) wp_date( 'd/m/Y', strtotime( (string) $rl2_birth_date ), new \DateTimeZone( 'UTC' ) ), 'ISO-8859-1', 'UTF-8' ) );
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

		// Check if signed document already exists on disk
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

		// Check if signed document already exists on disk
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
}
