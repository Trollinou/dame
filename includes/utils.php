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

/**
 * Calculates the age category of an adherent based on their birth date and the current season.
 *
 * @param string $birth_date_str The birth date in 'Y-m-d' format.
 * @param string $gender The gender ('Féminin' or 'Masculin').
 * @return string The calculated age category.
 */
function dame_get_adherent_age_category( $birth_date_str, $gender = 'Masculin' ) {
    if ( ! $birth_date_str ) {
        return __( 'Date de naissance manquante', 'dame' );
    }

    $birth_date = new DateTime( $birth_date_str );
    if ( ! $birth_date ) {
        return __( 'Date de naissance invalide', 'dame' );
    }

    // Get the current season tag ID from options.
    $current_season_tag_id = get_option( 'dame_current_season_tag_id' );
    if ( ! $current_season_tag_id ) {
        return __( 'Saison non définie', 'dame' );
    }

    $season_term = get_term( $current_season_tag_id, 'dame_saison_adhesion' );
    if ( is_wp_error( $season_term ) || ! $season_term ) {
        return __( 'Saison invalide', 'dame' );
    }

    // Season name is expected to be in "YYYY/YYYY" format, e.g., "2023/2024".
    $season_name = $season_term->name;
    $years = explode( '/', $season_name );
    if ( count( $years ) !== 2 || ! is_numeric( $years[1] ) ) {
        return __( 'Format de saison invalide', 'dame' );
    }
    $season_end_year = (int) $years[1];

    // Calculate age at the beginning of the season's end year.
    $reference_date = new DateTime( $season_end_year . '-01-01' );
    $age_interval = $reference_date->diff( $birth_date );
    $age = $age_interval->y;

    // Determine category based on age.
    $category = '';
    if ( $age < 8 ) {
        $category = 'U8';
    } elseif ( $age <= 9 ) {
        $category = 'U10';
    } elseif ( $age <= 11 ) {
        $category = 'U12';
    } elseif ( $age <= 13 ) {
        $category = 'U14';
    } elseif ( $age <= 15 ) {
        $category = 'U16';
    } elseif ( $age <= 17 ) {
        $category = 'U18';
    } elseif ( $age <= 19 ) {
        $category = 'U20';
    } elseif ( $age <= 49 ) {
        return 'Sénior';
    } elseif ( $age <= 64 ) {
        return 'Sénior+';
    } else {
        return 'Vétéran';
    }

    // Append 'F' for female gender in youth categories.
    if ( 'Féminin' === $gender ) {
        $category .= 'F';
    }

    return $category;
}

/**
 * Returns a key for the age category.
 *
 * @param string $birth_date_str The birth date in 'Y-m-d' format.
 * @param string $gender The gender ('Féminin' or 'Masculin').
 * @return string The calculated age category key.
 */
function dame_get_adherent_age_category_key( $birth_date_str, $gender = 'Masculin' ) {
    $category = dame_get_adherent_age_category( $birth_date_str, $gender );
    $key = str_replace( '+', '-plus', $category );
    return sanitize_key( $key );
}

/**
 * Returns a list of all possible age categories.
 *
 * @return array An array of age categories.
 */
function dame_get_all_age_categories() {
    return array(
        'u8'          => 'U8',
        'u8f'         => 'U8F',
        'u10'         => 'U10',
        'u10f'        => 'U10F',
        'u12'         => 'U12',
        'u12f'        => 'U12F',
        'u14'         => 'U14',
        'u14f'        => 'U14F',
        'u16'         => 'U16',
        'u16f'        => 'U16F',
        'u18'         => 'U18',
        'u18f'        => 'U18F',
        'u20'         => 'U20',
        'u20f'        => 'U20F',
        'senior'      => 'Sénior',
        'senior-plus' => 'Sénior+',
        'veteran'     => 'Vétéran',
    );
}

/**
 * Calculates the birth date range for a given age category.
 *
 * @param string $category_key The key of the age category.
 * @return array|null An array containing the start and end dates of the birth range, or null.
 */
function dame_get_birth_date_range_for_category( $category_key ) {
    // Get the current season tag ID from options.
    $current_season_tag_id = get_option( 'dame_current_season_tag_id' );
    if ( ! $current_season_tag_id ) {
        return null;
    }

    $season_term = get_term( $current_season_tag_id, 'dame_saison_adhesion' );
    if ( is_wp_error( $season_term ) || ! $season_term ) {
        return null;
    }

    // Season name is expected to be in "YYYY/YYYY" format, e.g., "2023/2024".
    $season_name = $season_term->name;
    $years = explode( '/', $season_name );
    if ( count( $years ) !== 2 || ! is_numeric( $years[1] ) ) {
        return null;
    }
    $season_end_year = (int) $years[1];
    $reference_date = new DateTime( $season_end_year . '-01-01' );

    $age_map = array(
        'u8'          => array( 'min' => 0, 'max' => 7 ),
        'u10'         => array( 'min' => 8, 'max' => 9 ),
        'u12'         => array( 'min' => 10, 'max' => 11 ),
        'u14'         => array( 'min' => 12, 'max' => 13 ),
        'u16'         => array( 'min' => 14, 'max' => 15 ),
        'u18'         => array( 'min' => 16, 'max' => 17 ),
        'u20'         => array( 'min' => 18, 'max' => 19 ),
        'senior'      => array( 'min' => 20, 'max' => 49 ),
        'senior-plus' => array( 'min' => 50, 'max' => 64 ),
        'veteran'     => array( 'min' => 65, 'max' => 999 ),
    );

    // Remove 'f' from category key for age map lookup
    $age_key = rtrim( $category_key, 'f' );

    if ( ! isset( $age_map[ $age_key ] ) ) {
        return null;
    }

    $min_age = $age_map[ $age_key ]['min'];
    $max_age = $age_map[ $age_key ]['max'];

    $end_date = clone $reference_date;
    $end_date->modify( "-$min_age years" );

    $start_date = clone $reference_date;
    $start_date->modify( "-" . ( $max_age + 1 ) . " years" );
    $start_date->modify( "+1 day" );

    return array(
        'start' => $start_date->format( 'Y-m-d' ),
        'end'   => $end_date->format( 'Y-m-d' ),
    );
}
