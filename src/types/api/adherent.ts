/**
 * Types et interfaces pour les profils adhérents du plugin DAME.
 */

import type { GenderType } from '../enums';

export interface MemberProfileDTOData {
	id: number;
	first_name: string;
	last_name: string;
	birth_name?: string | null;
	birth_date?: string | null;
	gender: GenderType;
	email?: string | null;
	phone?: string | null;
	mobile_phone?: string | null;
	address?: string | null;
	postal_code?: string | null;
	city?: string | null;
	ffe_id?: string | null;
	elo?: number | null;
	full_name: string;
}

export interface AssociatedMember {
	firstname: string;
	name?: string;
	member_id: number;
	elo_standard?: number | string;
	elo_rapide?: number | string;
	elo_blitz?: number | string;
	already_registered?: boolean;
	has_pre_inscription?: boolean;
	pre_inscription_id?: number | null;
}

export interface MemberIdentity {
	id: string;
	name: string;
	type: 'member' | 'representative' | 'admin';
	member_id: number;
	firstname?: string;
	elo_standard?: number | string;
	elo_rapide?: number | string;
	elo_blitz?: number | string;
	associated_members?: AssociatedMember[];
	already_registered?: boolean;
	has_pre_inscription?: boolean;
	pre_inscription_id?: number | null;
}
