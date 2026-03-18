<?php
/**
 * Handles the public-facing shortcodes for the plugin.
 * This file now acts as a loader for the modularized shortcode files.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Include the new modular files.
require_once __DIR__ . '/shortcodes/fiche-inscription.php';
require_once __DIR__ . '/shortcodes/contact.php';
require_once __DIR__ . '/shortcodes/agenda.php';
require_once __DIR__ . '/shortcodes/sondage.php';
