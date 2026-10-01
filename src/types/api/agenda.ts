/**
 * Types et interfaces pour les événements Agenda du plugin DAME.
 */

import type { CompetitionTypeValue, CompetitionLevelValue } from '../enums';

export interface AgendaEventCategory {
	id: number;
	name: string;
	slug: string;
	color: string;
}

export interface AgendaEventMeta {
	_dame_start_date: string;
	_dame_end_date: string;
	_dame_start_time?: string;
	_dame_end_time?: string;
	_dame_all_day?: number | boolean;
	_dame_location_name?: string;
	_dame_address?: string;
	_dame_address_1?: string;
	_dame_postal_code?: string;
	_dame_city?: string;
	_dame_latitude?: number | string;
	_dame_longitude?: number | string;
	_dame_competition_type?: CompetitionTypeValue;
	_dame_competition_level?: CompetitionLevelValue;
	_dame_level?: CompetitionLevelValue;
	_dame_color?: string;
	_dame_recurrence_group_id?: string;
	_dame_agenda_description?: string;
}

export interface AgendaEventDTOData {
	id: number;
	title: string;
	start_date: string;
	end_date: string;
	all_day: boolean;
	start_time?: string | null;
	end_time?: string | null;
	location?: string | null;
	address?: string | null;
	latitude?: number | null;
	longitude?: number | null;
	competition_type: CompetitionTypeValue;
	competition_level?: CompetitionLevelValue | null;
	color?: string | null;
	series_id?: string | null;
}

export interface AgendaEvent {
	id: number;
	modified?: string;
	title: {
		rendered: string;
		raw?: string;
	};
	content?: {
		rendered: string;
		raw?: string;
	};
	_dame_agenda_description_html?: string;
	categories_data?: AgendaEventCategory[];
	dame_agenda_category?: number[];
	meta: AgendaEventMeta;
}
