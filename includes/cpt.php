<?php
/**
 * File for registering Custom Post Types.
 * This file now acts as a loader for the modularized CPT files.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Include the new modular files.
require_once __DIR__ . '/cpt/adherent.php';
require_once __DIR__ . '/cpt/pre-inscription.php';
require_once __DIR__ . '/cpt/agenda.php';
require_once __DIR__ . '/cpt/message.php';
require_once __DIR__ . '/cpt/sondage.php';
