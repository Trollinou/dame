/**
 * Types et interfaces pour le bénévolat du plugin DAME.
 */

export interface BenevolatTimeSlot {
	start: string;
	end: string;
	needed?: number;
	max_participants?: number;
}

export interface BenevolatDay {
	date: string;
	time_slots?: BenevolatTimeSlot[];
}

export interface LinkedAgendaSummary {
	id: number;
	title: string;
	start_date: string;
	end_date: string;
	location?: string;
	url: string;
}

export interface Benevolat {
	id: number;
	modified: string;
	title: {
		rendered: string;
		raw?: string;
	};
	content?: {
		rendered: string;
	};
	dame_benevolat_data: BenevolatDay[];
	dame_benevolat_counts?: Record<string, number>;
	linked_agenda?: LinkedAgendaSummary | null;
	meta?: {
		_dame_linked_agenda_id?: number | null;
	};
}

export interface BenevolatSlotStat {
	choice_key: string;
	date: string;
	start: string;
	end: string;
	needed: number;
	registered_count: number;
	is_fulfilled: boolean;
}

export interface BenevolatStatsResponse {
	id: number;
	total_distinct_voters: number;
	slots: BenevolatSlotStat[];
}

export interface BenevolatReponse {
	id: number;
	modified: string;
	title: {
		rendered: string;
		raw?: string;
	};
	benevolat_id: number;
	choices?: string[];
	meta?: {
		_dame_member_id?: number;
	};
}

export interface BenevolatMission {
	id: number;
	title: string;
	description?: string;
	date: string;
	start_time?: string;
	end_time?: string;
	location?: string;
	max_volunteers?: number;
	current_volunteers_count?: number;
	status?: 'open' | 'closed' | 'cancelled';
}

export interface BenevolatPresence {
	id: number;
	mission_id: number;
	member_id: number;
	status: 'confirmed' | 'pending' | 'declined';
	comment?: string;
}
