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
	dame_birth_name: string;
	dame_last_name: string;
	dame_first_name: string;
	dame_sexe: GenderType | 'Masculin' | 'Féminin' | 'Non précisé' | string;
	dame_birth_date: string;
	dame_birth_city: string;
	dame_phone_number: string;
	dame_email: string;
	dame_profession: string;
	dame_address_1: string;
	dame_address_2: string;
	dame_postal_code: string;
	dame_city: string;
	dame_taille_vetements: string;
	dame_license_type: string;
	dame_legal_rep_1_first_name: string;
	dame_legal_rep_1_last_name: string;
	dame_legal_rep_1_email: string;
	dame_legal_rep_1_phone: string;
	dame_legal_rep_1_address_1: string;
	dame_legal_rep_1_address_2: string;
	dame_legal_rep_1_postal_code: string;
	dame_legal_rep_1_city: string;
	dame_legal_rep_1_profession: string;
	dame_legal_rep_1_date_naissance: string;
	dame_legal_rep_1_commune_naissance: string;
	dame_legal_rep_2_first_name: string;
	dame_legal_rep_2_last_name: string;
	dame_legal_rep_2_email: string;
	dame_legal_rep_2_phone: string;
	dame_legal_rep_2_address_1: string;
	dame_legal_rep_2_address_2: string;
	dame_legal_rep_2_postal_code: string;
	dame_legal_rep_2_city: string;
	dame_legal_rep_2_profession: string;
	dame_legal_rep_2_date_naissance: string;
	dame_legal_rep_2_commune_naissance: string;
	dame_health_questionnaire: string;
	dame_refuses_comms: boolean;
	dame_legal_rep_1_refuses_comms: boolean;
	dame_legal_rep_2_refuses_comms: boolean;
	signature_image: string;
	health_honor_consent: boolean;
	parental_consent: boolean;
	first_name?: string;
	last_name?: string;
	birth_date?: string;
	gender?: GenderType;
	email?: string;
	phone?: string;
	address?: string;
	postal_code?: string;
	city?: string;
	department?: string;
	region?: string;
	health_status?: HealthDocumentStatusValue;
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
