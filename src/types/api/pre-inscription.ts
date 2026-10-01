/**
 * Types et interfaces pour les pré-inscriptions du plugin DAME.
 */

import type { GenderType, HealthDocumentStatusValue } from '../enums';

export interface PreInscriptionDTOData {
	id: number;
	first_name: string;
	last_name: string;
	birth_date?: string | null;
	gender: GenderType;
	email?: string | null;
	phone?: string | null;
	address?: string | null;
	postal_code?: string | null;
	city?: string | null;
	department?: string | null;
	region?: string | null;
	health_status: HealthDocumentStatusValue;
	ffe_id?: string | null;
	club_origin?: string | null;
}

export interface PreInscriptionFormData {
	first_name: string;
	last_name: string;
	birth_date: string;
	gender: GenderType;
	email: string;
	phone?: string;
	address: string;
	postal_code: string;
	city: string;
	department?: string;
	region?: string;
	health_status: HealthDocumentStatusValue;
	ffe_id?: string;
	club_origin?: string;
	saison?: string;
	is_minor?: boolean;
	legal_rep_1_name?: string;
	legal_rep_1_email?: string;
	legal_rep_1_phone?: string;
	legal_rep_2_name?: string;
	legal_rep_2_email?: string;
	legal_rep_2_phone?: string;
	notes?: string;
}
