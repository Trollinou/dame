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
