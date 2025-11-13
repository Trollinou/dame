<?php
/**
 * File for handling custom meta boxes and fields.
 * This file now acts as a loader for the modularized metabox files.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include the new modular files.
require_once __DIR__ . '/metaboxes/common.php';
require_once __DIR__ . '/metaboxes/adherent.php';
require_once __DIR__ . '/metaboxes/agenda.php';
require_once __DIR__ . '/metaboxes/pre-inscription.php';
require_once __DIR__ . '/metaboxes/sondage.php';
