<?php
/**
 * Main Plugin Class.
 *
 * @package DAME
 */

namespace DAME\Core;

use DAME\CPT\Adherent;
use DAME\CPT\Contact;
use DAME\CPT\Message;
use DAME\CPT\PreInscription;
use DAME\CPT\Agenda;
use DAME\CPT\ICalFeed;
use DAME\CPT\Benevolat;
use DAME\Core\Roles;
use DAME\Core\Upgrader; 
use DAME\API\Tracker;
use DAME\Services\Mailer;
use DAME\Services\BatchSender;
use DAME\Admin\Pages\Mailing;
use DAME\Admin\Pages\ImportFFE;
use DAME\Admin\Columns\Message as MessageColumns;
use DAME\Admin\Actions\Message as MessageActions;
use DAME\Admin\Actions\Adherent as AdherentActions;
use DAME\Metaboxes\Message\TestSend;
use DAME\Metaboxes\Contact\Details as ContactDetails;
use DAME\Metaboxes\Adherent\Manager as AdherentMetaboxManager;
use DAME\Metaboxes\PreInscription\Actions as PreInscriptionActions;
use DAME\Metaboxes\PreInscription\Details as PreInscriptionDetails;
use DAME\Metaboxes\PreInscription\Reconciliation as PreInscriptionReconciliation;
use DAME\Metaboxes\Agenda\Manager as AgendaMetaboxManager;
use DAME\Metaboxes\ICalFeed\Settings as ICalFeedSettings;
use DAME\Metaboxes\ICalFeed\Info as ICalFeedInfo;
use DAME\Metaboxes\Benevolat\Manager as BenevolatMetaboxManager;
use DAME\Services\PDF_Generator;
use DAME\Services\ICalFeed as ICalFeedService;
use DAME\Services\Backup;
use DAME\Services\Birthday;
use DAME\Shortcodes\RegistrationForm;
use DAME\Shortcodes\Agenda as AgendaShortcode;
use DAME\Shortcodes\Benevolat as BenevolatShortcode;
use DAME\Shortcodes\Contact as ContactShortcode;
use DAME\Admin\Assets;
use DAME\Admin\Pages\ViewAdherent;
use DAME\Admin\Settings\Main as SettingsMain;
use DAME\Admin\Columns\Adherent as AdherentColumns;
use DAME\Admin\ListTables\Agenda as AgendaListTable;
use DAME\Admin\ListTables\Benevolat as BenevolatListTable;
use DAME\Admin\Actions\Agenda as AgendaActions;
use DAME\Taxonomies\Season;
use DAME\Taxonomies\Contact_Type;
use DAME\Taxonomies\Group;
use DAME\Taxonomies\AgendaCategory;
use DAME\Admin\Toolbar;
use DAME\API\REST\Post_Meta;
use DAME\API\REST\Data_Endpoints;
use DAME\API\REST\Registration;
use DAME\API\REST\Identities;
use DAME\API\REST\Benevolat as Benevolat_REST;

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
	public function run(): void {
		// Initialize Roles.
		$roles = new Roles();
		$roles->init();

		// Gestion des montées de version et migrations
		$upgrader = new Upgrader();
		$upgrader->init();
		
		// Initialize CPTs.
		$adherent_cpt = new Adherent();
		$adherent_cpt->init();

		$contact_cpt = new Contact();
		$contact_cpt->init();

		$pre_inscription_cpt = new PreInscription();
		$pre_inscription_cpt->init();

		$message_cpt = new Message();
		$message_cpt->init();

		$agenda_cpt = new Agenda();
		$agenda_cpt->init();

		$ical_feed_cpt = new ICalFeed();
		$ical_feed_cpt->init();

		$benevolat_cpt = new Benevolat();
		$benevolat_cpt->init();

		$birthday_service = new Birthday();
		$birthday_service->init();

		// Initialize REST Meta.
		$rest_meta = new Post_Meta();
		$rest_meta->init();

		// Initialize custom REST endpoints.
		$data_endpoints = new Data_Endpoints( $birthday_service );
		$data_endpoints->init();

		$registration = new Registration();
		$registration->init();

		$identities = new Identities();
		$identities->init();

		$benevolat_rest = new Benevolat_REST();
		$benevolat_rest->init();

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

		$backup_service = new Backup();
		$backup_service->init();

		$cron_manager = new Cron();
		$cron_manager->init();

		// Initialize Shortcodes.
		$registration_form = new RegistrationForm();
		$registration_form->init();

		$agenda_shortcode = new AgendaShortcode();
		$agenda_shortcode->init();

		$benevolat_shortcode = new BenevolatShortcode();
		$benevolat_shortcode->init();

		$contact_shortcode = new ContactShortcode();
		$contact_shortcode->init();

		// Initialize Taxonomies (MUST BE GLOBAL, NOT INSIDE is_admin)
		$season_taxonomy = new Season();
		$season_taxonomy->init();

		$contact_type_taxonomy = new Contact_Type();
		$contact_type_taxonomy->init();

		$group_taxonomy = new Group();
		$group_taxonomy->init();

		$agenda_category_taxonomy = new AgendaCategory();
		$agenda_category_taxonomy->init();

		// Initialisation de la Toolbar
		$toolbar = new Toolbar();
		$toolbar->init();

		// Setup PWA Redirect and Manifest.
		add_action( 'template_redirect', [ $this, 'handle_pwa_redirect' ] );
		add_action( 'wp_head', [ $this, 'inject_pwa_manifest_link' ] );

		// Initialize Frontend Assets.
		$frontend_assets = new \DAME\Frontend\Assets();
		$frontend_assets->init();

		// Initialize Metaboxes & Admin-only logic.
		if ( is_admin() ) {
			$admin_menu = new \DAME\Admin\Menu();
			$admin_menu->init();
			$adherent_metaboxes = new AdherentMetaboxManager();
			$adherent_metaboxes->init();

			$contact_details = new ContactDetails();
			$contact_details->init();

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

			$ffe_import_page = new ImportFFE();
			$ffe_import_page->init();


			$message_columns = new MessageColumns();
			$message_columns->init();

			$message_actions = new MessageActions();
			$message_actions->init();

			$adherent_actions = new AdherentActions();
			$adherent_actions->init();

			$message_test_send = new TestSend();
			$message_test_send->init();

			// Initialize Agenda Metaboxes, Lists and Actions
			$agenda_metaboxes = new AgendaMetaboxManager();
			$agenda_metaboxes->init();

			$ical_feed_settings = new ICalFeedSettings();
			$ical_feed_settings->init();

			$ical_feed_info = new ICalFeedInfo();
			$ical_feed_info->init();

			$benevolat_metaboxes = new BenevolatMetaboxManager();
			$benevolat_metaboxes->init();

			$agenda_list_table = new AgendaListTable();
			$agenda_list_table->init();

			$benevolat_list_table = new BenevolatListTable();
			$benevolat_list_table->init();

			$agenda_actions = new AgendaActions();
			$agenda_actions->init();


			// Initialisation des pages de sauvegardes manuelles

		}
	}

	/**
	 * Handles the redirection to the PWA and serving the dynamic manifest.
	 * 
	 * Redirects /pwa to the actual PWA index file and serves /dame-manifest.json.
	 */
	public function handle_pwa_redirect(): void {
		$request_uri = trim( (string) parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
		
		if ( 'pwa' === $request_uri ) {
			$pwa_url = \DAME_PLUGIN_URL . 'pwa/dist/index.html';
			wp_safe_redirect( $pwa_url, 301 );
			exit;
		}

		if ( 'dame-manifest.json' === $request_uri ) {
			header( 'Content-Type: application/manifest+json; charset=utf-8' );
			echo wp_json_encode( [
				'name'             => get_bloginfo( 'name' ),
				'short_name'       => get_bloginfo( 'name' ),
				'start_url'        => home_url( '/pwa' ),
				'display'          => 'standalone',
				'background_color' => '#ffffff',
				'theme_color'      => '#ffffff',
				'icons'            => [
					[
						'src'     => get_site_icon_url( 192 ) ?: \DAME_PLUGIN_URL . 'pwa/dist/assets/icon/icon-192.png',
						'sizes'   => '192x192',
						'type'    => 'image/png',
						'purpose' => 'any maskable',
					],
					[
						'src'     => get_site_icon_url( 512 ) ?: \DAME_PLUGIN_URL . 'pwa/dist/assets/icon/icon-512.png',
						'sizes'   => '512x512',
						'type'    => 'image/png',
						'purpose' => 'any maskable',
					]
				]
			] );
			exit;
		}
	}

	/**
	 * Injects the PWA manifest link in the head of public pages.
	 */
	public function inject_pwa_manifest_link(): void {
		if ( ! is_admin() ) {
			echo '<link rel="manifest" href="' . esc_url( home_url( '/dame-manifest.json' ) ) . '">' . "\n";
		}
	}
}
