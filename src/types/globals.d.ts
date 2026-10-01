/**
 * Déclarations des variables globales injectées par WordPress et DAME.
 */

export interface DameSettings {
	ajaxUrl: string;
	restUrl: string;
	nonce: string;
	apiNonce?: string;
	pluginUrl: string;
	version: string;
	[key: string]: unknown;
}

export interface DameAdminData {
	ajax_url: string;
	nonce: string;
	labels?: Record<string, string>;
	assoc_latitude?: number | string;
	assoc_longitude?: number | string;
	department_region_mapping?: Record<string, string>;
	dept_region_map?: Record<string, string>;
	[key: string]: unknown;
}

export interface DameAdherentActionsData {
	confirm_revert: string;
}

export interface DameBackupAdherentData {
	confirm_restore: string;
	confirm_import_csv: string;
}

export interface DameBackupAgendaData {
	confirm_restore: string;
}

export interface DamePreInscriptionActionsData {
	confirm_delete: string;
}

export interface DameSaisonsData {
	confirm_reset: string;
}

export interface DameTestSendData {
	post_id: string | number;
	nonce: string;
	alert_empty: string;
	admin_url: string;
}

export interface DameSettingsAnniversaires {
	sending_message: string;
	nonce: string;
}

export interface DameAgendaManagerData {
	ajax_url: string;
	nonce: string;
	season_end_date?: string;
	alert_category?: string;
	alert_competition_type?: string;
	[key: string]: unknown;
}

export interface DameBenevolatData {
	ajax_url: string;
	nonce: string;
	confirm_delete?: string;
	[key: string]: unknown;
}

export interface DameMailingData {
	ajax_url: string;
	nonce: string;
	regionMapping?: Record<string, string[]>;
	no_articles_found?: string;
	generic_error?: string;
	[key: string]: unknown;
}

export interface DameAgendaAjax {
	ajax_url: string;
	nonce: string;
	start_of_week?: string | number;
	i18n: {
		months: string[];
		weekdays_short: string[];
		all_day: string;
		[key: string]: unknown;
	};
	categories?: Record<string, unknown>;
	[key: string]: unknown;
}

export interface DameContactAjax {
	ajax_url: string;
	nonce: string;
	[key: string]: unknown;
}

export interface DamePreInscriptionAjax {
	ajax_url: string;
	nonce: string;
	signature_pad_clear_label?: string;
	[key: string]: unknown;
}

export interface DameNewsletterData {
	ajaxUrl: string;
	nonce?: string;
	i18n?: {
		submitting?: string;
		[key: string]: unknown;
	};
	[key: string]: unknown;
}

declare global {
	const ajaxurl: string;
	const dameSettings: DameSettings | undefined;
	const dameAdminData: DameAdminData | undefined;
	const dameAgendaSettings: DameSettings | undefined;
	const dame_settings: DameSettings | undefined;
	const dame_vars: Record<string, unknown> | undefined;

	const dame_adherent_actions_data: DameAdherentActionsData;
	const dame_backup_adherent_data: DameBackupAdherentData;
	const dame_backup_agenda_data: DameBackupAgendaData;
	const dame_pre_inscription_actions_data: DamePreInscriptionActionsData;
	const dame_saisons_data: DameSaisonsData;
	const dame_test_send_data: DameTestSendData;
	const dame_settings_anniversaires: DameSettingsAnniversaires;
	const dame_agenda_manager_data: DameAgendaManagerData;
	const dame_benevolat_data: DameBenevolatData;
	const dame_admin_data: DameAdminData;
	const dame_mailing_data: DameMailingData;
	const dame_agenda_ajax: DameAgendaAjax;
	const dame_contact_ajax: DameContactAjax;
	const dame_pre_inscription_ajax: DamePreInscriptionAjax;
	const dameNewsletterData: DameNewsletterData | undefined;

	const jQuery: any;

	interface Window {
		MSStream?: unknown;
		ajaxurl?: string;
		prefillRep1?: () => void;
		initBirthCityAutocomplete?: (id: string) => void;
		dameSettings?: DameSettings;
		dameAdminData?: DameAdminData;
		dameAgendaSettings?: DameSettings;
		dame_settings?: DameSettings;
		dame_vars?: Record<string, unknown>;
		dameNewsletterData?: DameNewsletterData;
		dame_adherent_actions_data?: DameAdherentActionsData;
		dame_backup_adherent_data?: DameBackupAdherentData;
		dame_backup_agenda_data?: DameBackupAgendaData;
		dame_pre_inscription_actions_data?: DamePreInscriptionActionsData;
		dame_saisons_data?: DameSaisonsData;
		dame_test_send_data?: DameTestSendData;
		dame_settings_anniversaires?: DameSettingsAnniversaires;
		dame_agenda_manager_data?: DameAgendaManagerData;
		dame_benevolat_data?: DameBenevolatData;
		dame_admin_data?: DameAdminData;
		dame_mailing_data?: DameMailingData;
		dame_agenda_ajax?: DameAgendaAjax;
		dame_contact_ajax?: DameContactAjax;
		dame_pre_inscription_ajax?: DamePreInscriptionAjax;
		wp?: {
			apiFetch?: (options: { path?: string; url?: string; method?: string; data?: unknown; headers?: Record<string, string> }) => Promise<unknown>;
			i18n?: {
				__: (text: string, domain?: string) => string;
				_x: (text: string, context: string, domain?: string) => string;
				_n: (single: string, plural: string, number: number, domain?: string) => string;
				sprintf: (format: string, ...args: unknown[]) => string;
			};
			interactivity?: unknown;
			[key: string]: unknown;
		};
	}
}

export {};
