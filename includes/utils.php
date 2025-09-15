<?php
/**
 * This file contains utility functions for the DAME plugin.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Formats a last name to be in uppercase.
 *
 * @param string $name The last name to format.
 * @return string The formatted last name.
 */
function dame_format_lastname( $name ) {
	return mb_strtoupper( $name, 'UTF-8' );
}

/**
 * Formats a first name to be in Mixed Case.
 * Capitalizes the first letter of each word separated by a space or a hyphen.
 *
 * @param string $name The first name to format.
 * @return string The formatted first name.
 */
function dame_format_firstname( $name ) {
	// Capitalize the first letter of each word separated by a space or a hyphen.
	return mb_convert_case( $name, MB_CASE_TITLE, 'UTF-8' );
}

/**
 * Determines whether to use black or white text based on the background color.
 *
 * @param string $hexcolor The hex color code of the background.
 * @return string '#000000' for black or '#ffffff' for white.
 */
function dame_get_contrast_color( $hexcolor ) {
    $hexcolor = ltrim( $hexcolor, '#' );
    if ( strlen( $hexcolor ) === 3 ) {
        $r = hexdec( substr( $hexcolor, 0, 1 ) . substr( $hexcolor, 0, 1 ) );
        $g = hexdec( substr( $hexcolor, 1, 1 ) . substr( $hexcolor, 1, 1 ) );
        $b = hexdec( substr( $hexcolor, 2, 1 ) . substr( $hexcolor, 2, 1 ) );
    } elseif ( strlen( $hexcolor ) === 6 ) {
        $r = hexdec( substr( $hexcolor, 0, 2 ) );
        $g = hexdec( substr( $hexcolor, 2, 2 ) );
        $b = hexdec( substr( $hexcolor, 4, 2 ) );
    } else {
        return '#000000'; // Return black for invalid hex codes
    }
    $yiq = ( ( $r * 299 ) + ( $g * 587 ) + ( $b * 114 ) ) / 1000;
    return ( $yiq >= 128 ) ? '#000000' : '#ffffff';
}
