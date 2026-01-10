<?php
/**
 * Main Plugin Class.
 *
 * @package DAME
 */

namespace DAME\Core;

use DAME\CPT\Adherent;
use DAME\CPT\Message;
use DAME\CPT\PreInscription;
use DAME\CPT\Agenda;
use DAME\CPT\ICalFeed;
use DAME\Core\Roles;
use DAME\API\Tracker;
use DAME\Services\Mailer;
use DAME\Services\BatchSender;
use DAME\Admin\Pages\Mailing;
use DAME\Admin\Pages\MessageReport;
use DAME\Admin\Pages\UserAssignment;
use DAME\Admin\Columns\Message as MessageColumns;
use DAME\Admin\Actions\Message as MessageActions;
use DAME\Metaboxes\Message\TestSend;
use DAME\Metaboxes\Adherent\Manager as AdherentMetaboxManager;
use DAME\Metaboxes\PreInscription\Actions as PreInscriptionActions;
use DAME\Metaboxes\PreInscription\Details as PreInscriptionDetails;
use DAME\Metaboxes\PreInscription\Reconciliation as PreInscriptionReconciliation;
use DAME\Metaboxes\Agenda\Manager as AgendaMetaboxManager;
use DAME\Metaboxes\ICalFeed\Settings as ICalFeedSettings;
use DAME\Services\PDF_Generator;
use DAME\Services\ICalFeed as ICalFeedService;
use DAME\Shortcodes\RegistrationForm;
use DAME\Admin\Assets;
use DAME\Admin\Pages\ViewAdherent;
use DAME\Admin\Settings\Main as SettingsMain;
use DAME\Admin\Columns\Adherent as AdherentColumns;
use DAME\Admin\ListTables\Agenda as AgendaListTable;
use DAME\Admin\Actions\Agenda as AgendaActions;
use DAME\Taxonomies\Season;
use DAME\Taxonomies\Group;
use DAME\Taxonomies\AgendaCategory;

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

		$message_cpt = new Message();
		$message_cpt->init();

		$agenda_cpt = new Agenda();
		$agenda_cpt->init();

		$ical_feed_cpt = new ICalFeed();
		$ical_feed_cpt->init();

		// Initialize API.
		$tracker = new Tracker();
		$tracker->init();

		// Initialize Services.
		$mailer = new Mailer();
		$mailer->init();

		$batch_sender = new BatchSender();
		$batch_sender->init();

		$pdf_generator = new PDF_Generator();
		$pdf_generator->init();

		$ical_feed_service = new ICalFeedService();
		$ical_feed_service->init();

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

			// Initialize Pages
			$view_adherent_page = new ViewAdherent();
			$view_adherent_page->init();

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

			$agenda_category_taxonomy = new AgendaCategory();
			$agenda_category_taxonomy->init();

			// Initialize PreInscription Metaboxes
			$pre_inscription_details = new PreInscriptionDetails();
			$pre_inscription_details->init();

			$pre_inscription_reconciliation = new PreInscriptionReconciliation();
			$pre_inscription_reconciliation->init();

			$pre_inscription_actions = new PreInscriptionActions();
			$pre_inscription_actions->init();

			// Initialize Message Pages & Actions
			$mailing_page = new Mailing();
			$mailing_page->init();

			$message_report = new MessageReport();
			$message_report->init();

			$message_columns = new MessageColumns();
			$message_columns->init();

			$message_actions = new MessageActions();
			$message_actions->init();

			$message_test_send = new TestSend();
			$message_test_send->init();

			// Initialize Agenda Metaboxes, Lists and Actions
			$agenda_metaboxes = new AgendaMetaboxManager();
			$agenda_metaboxes->init();

			$ical_feed_settings = new ICalFeedSettings();
			$ical_feed_settings->init();

			$agenda_list_table = new AgendaListTable();
			$agenda_list_table->init();

			$agenda_actions = new AgendaActions();
			$agenda_actions->init();

			// Initialize User Assignment Page
			$user_assignment = new UserAssignment();
			$user_assignment->init();
		}
	}
}
