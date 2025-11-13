<?php
/**
 * File for customizing admin columns.
 * This file now acts as a loader for the modularized column files.
 *
 * @package DAME
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Include the new modular files.
require_once __DIR__ . '/columns/adherent.php';
require_once __DIR__ . '/columns/agenda.php';
require_once __DIR__ . '/columns/sondage.php';
require_once __DIR__ . '/columns/user.php';
require_once __DIR__ . '/columns/cpt-common.php';
