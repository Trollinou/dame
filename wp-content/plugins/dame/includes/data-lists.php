<?php
/**
 * Data lists for dropdown menus.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Returns a list of countries.
 * NOTE: This is a sample list. The search tool failed to retrieve a complete list.
 * This can be replaced with a more comprehensive array.
 *
 * @return array
 */
function dame_get_country_list() {
    return array(
        'FR' => 'France',
        'BE' => 'Belgique',
        'CH' => 'Suisse',
        'LU' => 'Luxembourg',
        'DE' => 'Allemagne',
        'ES' => 'Espagne',
        'IT' => 'Italie',
        'GB' => 'Royaume-Uni',
        'US' => 'États-Unis',
        'CA' => 'Canada',
    );
}

/**
 * Returns a list of French regions.
 * NOTE: This is a sample list.
 *
 * @return array
 */
function dame_get_region_list() {
    return array(
        'NA' => 'N/A',
        'ARA' => 'Auvergne-Rhône-Alpes',
        'BFC' => 'Bourgogne-Franche-Comté',
        'BRE' => 'Bretagne',
        'CVL' => 'Centre-Val de Loire',
        'COR' => 'Corse',
        'GES' => 'Grand Est',
        'HDF' => 'Hauts-de-France',
        'IDF' => 'Île-de-France',
        'NOR' => 'Normandie',
        'NAQ' => 'Nouvelle-Aquitaine',
        'OCC' => 'Occitanie',
        'PDL' => 'Pays de la Loire',
        'PACA' => 'Provence-Alpes-Côte d\'Azur',
    );
}

/**
 * Returns a list of French departments.
 * NOTE: This is a sample list.
 *
 * @return array
 */
function dame_get_department_list() {
    return array(
        'NA' => 'N/A',
        '01' => '01 - Ain',
        '02' => '02 - Aisne',
        '03' => '03 - Allier',
        // ... and so on.
        '75' => '75 - Paris',
        '92' => '92 - Hauts-de-Seine',
        '93' => '93 - Seine-Saint-Denis',
        '94' => '94 - Val-de-Marne',
    );
}
