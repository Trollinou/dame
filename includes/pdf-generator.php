<?php
/**
 * Handles the PDF generation for the DAME plugin.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// As the libraries are not using namespaces with an autoloader, we need to require them manually.
require_once plugin_dir_path( __FILE__ ) . 'lib/fpdf/fpdf.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/fpdi/src/autoload.php';

use setasign\Fpdi\Fpdi;

/**
 * Handles the AJAX request to generate and download the health attestation PDF.
 */
function dame_generate_health_form_handler() {
	// 1. Security check
	if ( ! isset( $_GET['post_id'] ) || ! isset( $_GET['_wpnonce'] ) ) {
		wp_die( __( "Paramètres invalides.", 'dame' ), 400 );
	}

	$post_id = intval( $_GET['post_id'] );

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'dame_generate_health_form_' . $post_id ) ) {
		wp_die( __( "La vérification de sécurité a échoué.", 'dame' ), 403 );
	}

	// 2. Get data from post meta
	$first_name = get_post_meta( $post_id, '_dame_first_name', true );
	$last_name  = get_post_meta( $post_id, '_dame_last_name', true );
	$birth_date_str = get_post_meta( $post_id, '_dame_birth_date', true );
	$city       = get_post_meta( $post_id, '_dame_city', true );

	$legal_rep_1_first_name = get_post_meta( $post_id, '_dame_legal_rep_1_first_name', true );
	$legal_rep_1_last_name  = get_post_meta( $post_id, '_dame_legal_rep_1_last_name', true );
	$legal_rep_1_city       = get_post_meta( $post_id, '_dame_legal_rep_1_city', true );

	// 3. Data validation
	if ( empty( $first_name ) || empty( $last_name ) || empty( $birth_date_str ) || empty( $city ) ) {
		wp_die( __( "Données de préinscription manquantes ou invalides.", 'dame' ), 404 );
	}

	// 4. Calculate Age
	$birth_date = DateTime::createFromFormat( 'Y-m-d', $birth_date_str );
	$today      = new DateTime();
	$age        = $today->diff( $birth_date )->y;

	// 5. Prepare data for PDF
	$full_name_adherent_for_pdf = strtoupper( $last_name ) . ' ' . $first_name;
	$current_date       = date( 'd/m/Y' );

	// Handle UTF-8 to Windows-1252 conversion for FPDF standard fonts
	$full_name_adherent_for_pdf = utf8_decode( $full_name_adherent_for_pdf );
	$city_for_pdf               = utf8_decode( $city );

	// 6. Generate PDF
	$pdf = new Fpdi();
	$pdf->AddPage();

	try {
		// Set the template file
		$template_path = plugin_dir_path( __DIR__ ) . 'public/pdf/ffe_attestation_sante.pdf';
		$pdf->setSourceFile( $template_path );
		$tplId = $pdf->importPage(1);
		$pdf->useTemplate( $tplId, 0, 0, 210, 297 );
	} catch ( Exception $e ) {
		wp_die( sprintf( __( "Erreur lors du chargement du template PDF : %s", 'dame' ), $e->getMessage() ), 500 );
	}

	// Set font for the data
	$pdf->SetFont( 'Helvetica' );
	$pdf->SetTextColor( 0, 0, 0 );

	if ( $age >= 18 ) {
		// Major adherent
		$pdf->SetXY( 54, 128 );
		$pdf->Write( 0, $full_name_adherent_for_pdf );

		$pdf->SetXY( 32, 156 );
		$pdf->Write( 0, $current_date );

		$pdf->SetXY( 62, 156 );
		$pdf->Write( 0, $city_for_pdf );

	} else {
		// Minor adherent
		if ( empty( $legal_rep_1_first_name ) || empty( $legal_rep_1_last_name ) || empty( $legal_rep_1_city ) ) {
			wp_die( __( "Données du représentant légal manquantes pour un adhérent mineur.", 'dame' ), 400 );
		}

		$full_name_rep1_for_pdf = strtoupper( $legal_rep_1_last_name ) . ' ' . $legal_rep_1_first_name;
		$full_name_rep1_for_pdf = utf8_decode( $full_name_rep1_for_pdf );
		$legal_rep_1_city_for_pdf = utf8_decode( $legal_rep_1_city );

		$pdf->SetXY( 54, 181 );
		$pdf->Write( 0, $full_name_rep1_for_pdf );

		$pdf->SetXY( 119, 190 );
		$pdf->Write( 0, $full_name_adherent_for_pdf );

		$pdf->SetXY( 32, 227 );
		$pdf->Write( 0, $current_date );

		$pdf->SetXY( 62, 227 );
		$pdf->Write( 0, $legal_rep_1_city_for_pdf );
	}

	// 7. Output PDF
	$filename = sanitize_file_name( 'attestation_sante_' . $last_name . '_' . $first_name . '.pdf' );
	$pdf->Output( 'D', $filename );
	exit;
}

add_action( 'wp_ajax_dame_generate_health_form', 'dame_generate_health_form_handler' );
add_action( 'wp_ajax_nopriv_dame_generate_health_form', 'dame_generate_health_form_handler' );


/**
 * Handles the AJAX request to generate and download the parental authorization PDF.
 */
function dame_generate_parental_auth_handler() {
	// 1. Security check
	if ( ! isset( $_GET['post_id'] ) || ! isset( $_GET['_wpnonce'] ) ) {
		wp_die( __( "Paramètres invalides.", 'dame' ), 400 );
	}

	$post_id = intval( $_GET['post_id'] );

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'dame_generate_parental_auth_' . $post_id ) ) {
		wp_die( __( "La vérification de sécurité a échoué.", 'dame' ), 403 );
	}

	// 2. Get all required data from post meta
	$first_name     = get_post_meta( $post_id, '_dame_first_name', true );
	$last_name      = get_post_meta( $post_id, '_dame_last_name', true );
	$birth_date_str = get_post_meta( $post_id, '_dame_birth_date', true );
	$city           = get_post_meta( $post_id, '_dame_city', true );

	// Rep 1 data
	$rl1_first_name  = get_post_meta( $post_id, '_dame_legal_rep_1_first_name', true );
	$rl1_last_name   = get_post_meta( $post_id, '_dame_legal_rep_1_last_name', true );
	$rl1_birth_date  = get_post_meta( $post_id, '_dame_legal_rep_1_date_naissance', true );
	$rl1_birth_place = get_post_meta( $post_id, '_dame_legal_rep_1_commune_naissance', true );
	$rl1_profession  = get_post_meta( $post_id, '_dame_legal_rep_1_profession', true );

	// Rep 2 data
	$rl2_first_name  = get_post_meta( $post_id, '_dame_legal_rep_2_first_name', true );
	$rl2_last_name   = get_post_meta( $post_id, '_dame_legal_rep_2_last_name', true );
	$rl2_birth_date  = get_post_meta( $post_id, '_dame_legal_rep_2_date_naissance', true );
	$rl2_birth_place = get_post_meta( $post_id, '_dame_legal_rep_2_commune_naissance', true );
	$rl2_profession  = get_post_meta( $post_id, '_dame_legal_rep_2_profession', true );

	// 3. Data validation
	if ( empty( $first_name ) || empty( $last_name ) || empty( $birth_date_str ) ) {
		wp_die( __( "Données de préinscription de l'adhérent manquantes ou invalides.", 'dame' ), 404 );
	}

	$birth_date_obj = DateTime::createFromFormat( 'Y-m-d', $birth_date_str );
	$today          = new DateTime();
	$age            = $today->diff( $birth_date_obj )->y;

	if ( $age >= 18 ) {
		wp_die( __( "L'autorisation parentale ne peut être générée que pour un adhérent mineur.", 'dame' ), 400 );
	}

	// 4. Prepare data for PDF
	$adherent_full_name            = utf8_decode( strtoupper( $last_name ) . ' ' . $first_name );
	$adherent_birth_date_formatted = utf8_decode( date( 'd/m/Y', strtotime( $birth_date_str ) ) );
	$adherent_city                 = utf8_decode( $city );
	$current_date                  = date( 'd/m/Y' );
	$rl1_full_name                 = '';
	if ( ! empty( $rl1_first_name ) && ! empty( $rl1_last_name ) ) {
		$rl1_full_name = utf8_decode( strtoupper( $rl1_last_name ) . ' ' . $rl1_first_name );
	}

	// 5. Generate PDF
	$pdf = new Fpdi();
	$pdf->AddPage();
	$pdf->SetAutoPageBreak( true, 0 );

	try {
		$template_path = plugin_dir_path( __DIR__ ) . 'public/pdf/el_autorisation_parentale.pdf';
		$pdf->setSourceFile( $template_path );
		$tplId = $pdf->importPage( 1 );
		$pdf->useTemplate( $tplId, 0, 0, 210, 297 );
	} catch ( Exception $e ) {
		wp_die( sprintf( __( "Erreur lors du chargement du template PDF : %s", 'dame' ), $e->getMessage() ), 500 );
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

	// --- Legal Rep 1 Data ---
	if ( ! empty( $rl1_last_name ) ) {
		$pdf->SetXY( 25, 248 );
		$pdf->Write( 0, utf8_decode( strtoupper( $rl1_last_name ) ) );
	}
	if ( ! empty( $rl1_first_name ) ) {
		$pdf->SetXY( 30, 255 );
		$pdf->Write( 0, utf8_decode( $rl1_first_name ) );
	}
	if ( ! empty( $rl1_birth_place ) ) {
		$pdf->SetXY( 48, 264 );
		$pdf->Write( 0, utf8_decode( $rl1_birth_place ) );
	}
	if ( ! empty( $rl1_birth_date ) ) {
		$pdf->SetXY( 54, 270 );
		$pdf->Write( 0, utf8_decode( date( 'd/m/Y', strtotime( $rl1_birth_date ) ) ) );
	}
	if ( ! empty( $rl1_profession ) ) {
		$pdf->SetXY( 35, 279 );
		$pdf->Write( 0, utf8_decode( $rl1_profession ) );
	}

	// --- Legal Rep 2 Data ---
	if ( ! empty( $rl2_last_name ) ) {
		$pdf->SetXY( 125, 248 );
		$pdf->Write( 0, utf8_decode( strtoupper( $rl2_last_name ) ) );
	}
	if ( ! empty( $rl2_first_name ) ) {
		$pdf->SetXY( 130, 255 );
		$pdf->Write( 0, utf8_decode( $rl2_first_name ) );
	}
	if ( ! empty( $rl2_birth_place ) ) {
		$pdf->SetXY( 148, 264 );
		$pdf->Write( 0, utf8_decode( $rl2_birth_place ) );
	}
	if ( ! empty( $rl2_birth_date ) ) {
		$pdf->SetXY( 154, 270 );
		$pdf->Write( 0, utf8_decode( date( 'd/m/Y', strtotime( $rl2_birth_date ) ) ) );
	}
	if ( ! empty( $rl2_profession ) ) {
		$pdf->SetXY( 135, 279 );
		$pdf->Write( 0, utf8_decode( $rl2_profession ) );
	}

	// 6. Output PDF
	$filename = sanitize_file_name( 'attestation_parental_' . $last_name . '_' . $first_name . '.pdf' );
	$pdf->Output( 'D', $filename );
	exit;
}
add_action( 'wp_ajax_dame_generate_parental_auth', 'dame_generate_parental_auth_handler' );
add_action( 'wp_ajax_nopriv_dame_generate_parental_auth', 'dame_generate_parental_auth_handler' );
