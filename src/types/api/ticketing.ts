/**
 * Types TypeScript pour le module Billetterie & Inscriptions HelloAsso du plugin DAME.
 * Conforme au contrat PWA (isole de WordPress et du DOM).
 */

export type CampaignCategory = 'tournament' | 'membership' | 'stage' | 'other';

export interface TicketingCampaignDTO {
	id: number;
	form_slug: string;
	form_type: string;
	title: string;
	campaign_category: CampaignCategory;
	linked_agenda_id?: number | null;
	linked_season_id?: number | null;
	start_date?: string | null;
	end_date?: string | null;
	last_sync_at?: string | null;
	total_items: number;
	checked_in_count: number;
	total_amount_cents: number;
	tiers: Record<string, number>;
}

export interface TicketingItemDTO {
	id: number;
	campaign_id: number;
	ha_item_id?: number | null;
	source: 'helloasso' | 'onsite';
	status: string;
	participant_first_name: string;
	participant_last_name: string;
	participant_email: string;
	participant_birth_date?: string | null;
	payer_first_name: string;
	payer_last_name: string;
	payer_email: string;
	tier_name: string;
	amount_cents: number;
	payment_method: string;
	order_date?: string | null;
	license_number: string;
	fide_id: string;
	elo?: number | null;
	club_name: string;
	phone: string;
	matched_adherent_id: number;
	matched_pre_inscription_id: number;
	matched_contact_id: number;
	checked_in: boolean;
	checked_in_at?: string | null;
}

export interface AddOnSiteParticipantRequest {
	participant_first_name: string;
	participant_last_name: string;
	participant_email?: string;
	license_number?: string;
	club_name?: string;
	elo?: number;
	tier_name?: string;
	amount?: number;
	payment_method?: string;
}

