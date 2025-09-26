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

/**
 * Converts a HEX color to its RGB components.
 *
 * @param string $hex_color The color in HEX format (e.g., #a1a1a1 or #a1a).
 * @return array|null An associative array with r, g, b components, or null if the format is invalid.
 */
function dame_hex_to_rgb( $hex_color ) {
	$hex_color = ltrim( $hex_color, '#' );
	if ( strlen( $hex_color ) === 3 ) {
		$r = hexdec( $hex_color[0] . $hex_color[0] );
		$g = hexdec( $hex_color[1] . $hex_color[1] );
		$b = hexdec( $hex_color[2] . $hex_color[2] );
	} elseif ( strlen( $hex_color ) === 6 ) {
		$r = hexdec( substr( $hex_color, 0, 2 ) );
		$g = hexdec( substr( $hex_color, 2, 2 ) );
		$b = hexdec( substr( $hex_color, 4, 2 ) );
	} else {
		return null; // Invalid format
	}
	return array(
		'r' => $r,
		'g' => $g,
		'b' => $b,
	);
}

/**
 * Determines the best contrasting text color (black or white) based on the background color's luminance.
 *
 * @param string $hex_color The background color in HEX format.
 * @return string The HEX code for the contrasting text color (#000000 for black, #ffffff for white).
 */
function dame_get_text_color_based_on_bg( $hex_color ) {
	$rgb = dame_hex_to_rgb( $hex_color );

	if ( ! $rgb ) {
		return '#000000'; // Default to black for invalid colors.
	}

	// Calculate luminance
	$luminance = ( 0.2126 * $rgb['r'] + 0.7152 * $rgb['g'] + 0.0722 * $rgb['b'] ) / 255;

	// Use a threshold of 0.5 to decide text color
	return $luminance > 0.5 ? '#000000' : '#ffffff';
}

/**
 * Lightens a HEX color by a given percentage.
 *
 * @param string $hex_color The color to lighten in HEX format.
 * @param float  $percentage The percentage to lighten by (e.g., 0.33 for 33%).
 * @return string The lightened color in HEX format.
 */
function dame_lighten_color( $hex_color, $percentage ) {
	$rgb = dame_hex_to_rgb( $hex_color );

	if ( ! $rgb ) {
		return $hex_color; // Return original color if invalid
	}

	$new_r = round( $rgb['r'] + ( 255 - $rgb['r'] ) * $percentage );
	$new_g = round( $rgb['g'] + ( 255 - $rgb['g'] ) * $percentage );
	$new_b = round( $rgb['b'] + ( 255 - $rgb['b'] ) * $percentage );

	return sprintf( '#%02x%02x%02x', $new_r, $new_g, $new_b );
}
