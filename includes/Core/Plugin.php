<?php
/**
 * Main Plugin Class.
 *
 * @package DAME
 */

namespace DAME\Core;

use DAME\CPT\Adherent;
use DAME\CPT\PreInscription;
use DAME\Core\Roles;
use DAME\Metaboxes\Adherent\Manager as AdherentMetaboxManager;
use DAME\Metaboxes\PreInscription\Actions as PreInscriptionActions;
use DAME\Metaboxes\PreInscription\Details as PreInscriptionDetails;
use DAME\Metaboxes\PreInscription\Reconciliation as PreInscriptionReconciliation;
use DAME\Services\PDF_Generator;
use DAME\Shortcodes\RegistrationForm;
use DAME\Admin\Assets;
use DAME\Admin\Settings\Main as SettingsMain;
use DAME\Admin\Columns\Adherent as AdherentColumns;
use DAME\Taxonomies\Season;
use DAME\Taxonomies\Group;

/**
 * The core plugin class.
 */
class Plugin {

	/**
	 * The unique instance of the plugin.
	 *
	 * @var Plugin
	 */
	private static $instance;

	/**
	 * Gets the unique instance of the plugin.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton.
	 */
	private function __construct() {
		// Initialization code here.
	}

	/**
	 * Starts the plugin execution.
	 */
	public function run() {
		// Load legacy helpers (temporary dependency).
		if ( defined( 'DAME_PATH' ) ) {
			require_once DAME_PATH . 'includes/data-lists.php';
			require_once DAME_PATH . 'includes/utils.php';
			// require_once DAME_PATH . 'includes/taxonomies.php';
			require_once DAME_PATH . 'includes/assets.php';
		}

		// Initialize Roles.
		$roles = new Roles();
		$roles->init();

		// Initialize CPTs.
		$adherent_cpt = new Adherent();
		$adherent_cpt->init();

		$pre_inscription_cpt = new PreInscription();
		$pre_inscription_cpt->init();

		// Initialize Services.
		$pdf_generator = new PDF_Generator();
		$pdf_generator->init();

		// Initialize Shortcodes.
		$registration_form = new RegistrationForm();
		$registration_form->init();

		// Initialize Metaboxes.
		if ( is_admin() ) {
			$adherent_metaboxes = new AdherentMetaboxManager();
			$adherent_metaboxes->init();

			// Initialize Admin Assets
			$admin_assets = new Assets();
			$admin_assets->init();

			// Initialize Settings
			$settings = new SettingsMain();
			$settings->init();

			// Initialize Adherent Columns (Migrated)
			$adherent_columns = new AdherentColumns();
			$adherent_columns->init();

			// Initialize Taxonomies
			$season_taxonomy = new Season();
			$season_taxonomy->init();

			$group_taxonomy = new Group();
			$group_taxonomy->init();

			// Initialize PreInscription Metaboxes
			$pre_inscription_details = new PreInscriptionDetails();
			$pre_inscription_details->init();

			$pre_inscription_reconciliation = new PreInscriptionReconciliation();
			$pre_inscription_reconciliation->init();

			$pre_inscription_actions = new PreInscriptionActions();
			$pre_inscription_actions->init();
		}
	}
}
