/**
 * Énumérations et types constants du plugin DAME.
 */

export type GenderType = 'M' | 'F' | 'Autre';

export const Gender = {
	MALE: 'M' as GenderType,
	FEMALE: 'F' as GenderType,
	OTHER: 'Autre' as GenderType,
} as const;

export type CompetitionTypeValue = 'non' | 'individuel' | 'equipe';

export const CompetitionType = {
	NONE: 'non' as CompetitionTypeValue,
	INDIVIDUAL: 'individuel' as CompetitionTypeValue,
	TEAM: 'equipe' as CompetitionTypeValue,
} as const;

export type CompetitionLevelValue = 'departemental' | 'regional' | 'national' | 'international';

export const CompetitionLevel = {
	DEPARTEMENTAL: 'departemental' as CompetitionLevelValue,
	REGIONAL: 'regional' as CompetitionLevelValue,
	NATIONAL: 'national' as CompetitionLevelValue,
	INTERNATIONAL: 'international' as CompetitionLevelValue,
} as const;

export type HealthDocumentStatusValue = 'none' | 'attestation' | 'certificate';

export const HealthDocumentStatus = {
	NONE: 'none' as HealthDocumentStatusValue,
	ATTESTATION: 'attestation' as HealthDocumentStatusValue,
	CERTIFICATE: 'certificate' as HealthDocumentStatusValue,
} as const;

export type RecurrenceFrequencyValue = 'weekly' | 'monthly';

export const RecurrenceFrequency = {
	WEEKLY: 'weekly' as RecurrenceFrequencyValue,
	MONTHLY: 'monthly' as RecurrenceFrequencyValue,
} as const;
