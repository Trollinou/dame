<?php
/**
 * Backup Service Facade for DAME.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Services;

use DAME\Services\Backup\NoticeTrait;
use DAME\Services\Backup\ContactBackup;
use DAME\Services\Backup\AdherentBackup;
use DAME\Services\Backup\AgendaBackup;
use DAME\Services\Backup\SiteBackup;
use DAME\Services\Agenda\Export as AgendaExport;

/**
 * Orchestrator facade handling Backups, Imports and Exports for DAME.
 */
class Backup {
	use NoticeTrait;

	/**
	 * Initialize the service.
	 */
	public function init(): void {
		// Handle manual export/import actions (triggered via admin POST).
		add_action( 'admin_init', array( $this, 'handle_manual_actions' ) );
		add_action( 'admin_notices', array( $this, 'display_import_export_notices' ) );
	}

	/**
	 * Displays the admin notice if one is set.
	 */
	public function display_import_export_notices(): void {
		if ( get_transient( 'dame_import_export_notice' ) ) {
			$notice  = get_transient( 'dame_import_export_notice' );
			$message = $notice['message'];
			$type    = $notice['type'];
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
			delete_transient( 'dame_import_export_notice' );
		}
	}

	/**
	 * Dispatch manual actions based on POST requests.
	 */
	public function handle_manual_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// 1. Export CSV Adherents.
		if ( isset( $_POST['dame_export_csv_action'], $_POST['dame_export_csv_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_export_csv_nonce'] ) ), 'dame_export_csv_nonce_action' ) ) {
			( new AdherentBackup() )->export_csv();
		}

		// 2. Import CSV Adherents.
		if ( isset( $_POST['dame_import_csv_action'], $_POST['dame_import_csv_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_import_csv_nonce'] ) ), 'dame_import_csv_nonce_action' ) ) {
			( new AdherentBackup() )->import_csv();
		}

		// 3. Export JSON Adherents (Backup).
		if ( isset( $_POST['dame_export_action'], $_POST['dame_export_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_export_nonce'] ) ), 'dame_export_nonce_action' ) ) {
			( new AdherentBackup() )->export_json();
		}

		// 4. Import JSON Adherents (Restore).
		if ( isset( $_POST['dame_import'], $_POST['dame_import_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_import_nonce'] ) ), 'dame_import_nonce_action' ) ) {
			( new AdherentBackup() )->import_json();
		}

		// 5. Export JSON Agenda.
		if ( isset( $_POST['dame_agenda_backup_action'], $_POST['dame_agenda_backup_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_agenda_backup_nonce'] ) ), 'dame_agenda_backup_nonce_action' ) ) {
			( new AgendaBackup() )->export_json();
		}

		// 5b. Export CSV Agenda.
		if ( isset( $_POST['dame_export_agenda_csv_post_action'], $_POST['dame_export_agenda_csv_post_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_export_agenda_csv_post_nonce'] ) ), 'dame_export_agenda_csv_post_nonce_action' ) ) {
			( new AgendaExport() )->export_csv();
		}

		// 6. Import JSON Agenda.
		if ( isset( $_POST['dame_agenda_restore_action'], $_POST['dame_agenda_restore_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_agenda_restore_nonce'] ) ), 'dame_agenda_restore_nonce_action' ) ) {
			( new AgendaBackup() )->import_json();
		}

		// 7. Export JSON Site Content.
		if ( isset( $_POST['dame_site_backup_action'], $_POST['dame_site_backup_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_site_backup_nonce'] ) ), 'dame_site_backup_nonce_action' ) ) {
			( new SiteBackup() )->export_json();
		}

		// 8. Import JSON Site Content.
		if ( isset( $_POST['dame_site_restore_action'], $_POST['dame_site_restore_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_site_restore_nonce'] ) ), 'dame_site_restore_nonce_action' ) ) {
			( new SiteBackup() )->import_json();
		}

		// 9. Export CSV Contacts.
		if ( isset( $_POST['dame_export_contacts_csv_action'], $_POST['dame_export_contacts_csv_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_export_contacts_csv_nonce'] ) ), 'dame_export_contacts_csv_nonce_action' ) ) {
			( new ContactBackup() )->export_csv();
		}

		// 10. Import CSV Contacts.
		if ( isset( $_POST['dame_import_contacts_csv_action'], $_POST['dame_import_contacts_csv_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_import_contacts_csv_nonce'] ) ), 'dame_import_contacts_csv_nonce_action' ) ) {
			( new ContactBackup() )->import_csv();
		}

		// 11. Import CSV HelloAsso Contacts.
		if ( isset( $_POST['dame_import_helloasso_csv_action'], $_POST['dame_import_helloasso_csv_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_import_helloasso_csv_nonce'] ) ), 'dame_import_helloasso_csv_nonce_action' ) ) {
			( new ContactBackup() )->import_helloasso_csv();
		}

		// 12. Delete Contact Duplicates.
		if ( isset( $_POST['dame_delete_contact_duplicates_action'], $_POST['dame_delete_contact_duplicates_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dame_delete_contact_duplicates_nonce'] ) ), 'dame_delete_contact_duplicates_nonce_action' ) ) {
			( new ContactBackup() )->delete_duplicates();
		}
	}

	/**
	 * Builds an index of all adherents for fast matching.
	 *
	 * @return array{
	 *     emails: array<string, array{id: int, name: string, detail: string}>,
	 *     names: array<string, array{id: int, name: string, detail: string}>,
	 *     licenses: array<string, array{id: int, name: string, detail: string}>
	 * }
	 */
	public static function get_adherents_matching_index(): array {
		return ContactBackup::get_adherents_matching_index();
	}

	/**
	 * Detects contacts that match an adherent (by email or normalized full name).
	 *
	 * @return array<int, array{
	 *     contact_id: int,
	 *     contact_name: string,
	 *     contact_email: string,
	 *     contact_org: string,
	 *     categories: array<int, string>,
	 *     adherent_id: int,
	 *     adherent_name: string,
	 *     match_reason: string
	 * }>
	 */
	public static function get_contact_adherent_duplicates(): array {
		return ContactBackup::get_contact_adherent_duplicates();
	}

	/**
	 * Generate Adherent export data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate_adherent_export_data(): array {
		return ( new AdherentBackup() )->generate_export_data();
	}

	/**
	 * Generate Agenda export data (Events and Polls).
	 *
	 * @return array<string, mixed>
	 */
	public function generate_agenda_export_data(): array {
		return ( new AgendaBackup() )->generate_export_data();
	}

	/**
	 * Generate Site Content export data (Posts, Pages, Menus).
	 *
	 * @return array<string, mixed>
	 */
	public function generate_site_export_data(): array {
		return ( new SiteBackup() )->generate_export_data();
	}

	/**
	 * Runs the daily scheduled backup job.
	 */
	public function run_scheduled_backup(): void {
		( new SiteBackup() )->run_scheduled_backup();
	}
}
