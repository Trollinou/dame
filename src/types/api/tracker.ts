/**
 * Types et interfaces pour le Tracker du plugin DAME.
 */

export interface TrackingRecord {
	id?: number;
	message_id: number;
	email_hash: string;
	user_ip: string;
	opened_at: string;
}

export interface TrackingStats {
	message_id: number;
	total_sent: number;
	unique_opens: number;
	total_opens: number;
	last_opened_at?: string | null;
}
