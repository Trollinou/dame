import { defineStore } from 'pinia';
import { computed } from 'vue';
import { useAuthStore } from './auth';
import { useQuery, useQueryClient } from '@tanstack/vue-query';

export interface Member {
	id: number;
	modified: string;
	dame_age_category?: string;
	title: {
		rendered: string;
		raw: string;
	};
	status: string;
	seasons: number[];
	meta?: {
		_dame_email?: string;
		_dame_phone_number?: string;
		_dame_birth_date?: string;
		_dame_sexe?: string;
		_dame_license_type?: string;
		_dame_license_number?: string;
		// Echecs
		_dame_fide_id?: string;
		_dame_elo_standard?: string | number;
		_dame_elo_rapide?: string | number;
		_dame_elo_blitz?: string | number;
		// Adresse
		_dame_address_1?: string;
		_dame_address_2?: string;
		_dame_postal_code?: string;
		_dame_city?: string;
		// Représentant légal 1
		_dame_legal_rep_1_first_name?: string;
		_dame_legal_rep_1_last_name?: string;
		_dame_legal_rep_1_email?: string;
		_dame_legal_rep_1_phone?: string;
		_dame_legal_rep_1_profession?: string;
		// Représentant légal 2
		_dame_legal_rep_2_first_name?: string;
		_dame_legal_rep_2_last_name?: string;
		_dame_legal_rep_2_email?: string;
		_dame_legal_rep_2_phone?: string;
		_dame_legal_rep_2_profession?: string;
		[ key: string ]: any;
	};
}

export interface Season {
	id: number;
	name: string;
}

export const useMemberStore = defineStore( 'members', () => {
	const authStore = useAuthStore();
	const queryClient = useQueryClient();

	// 1. Liste des adhérents (Clé admin privée)
	const {
		data: rawMembers,
		isLoading: isMembersLoading,
		refetch: refetchMembers,
	} = useQuery< Member[] >( {
		queryKey: [ 'admin', 'members', 'list' ],
		queryFn: async () => {
			const token = localStorage.getItem( 'dame_jwt_token' );
			if ( ! token ) {
				throw new Error( 'Non authentifié' );
			}

			const baseUrl = `${
				import.meta.env.VITE_API_BASE_URL
			}/wp/v2/adherents?per_page=100&context=edit`;
			const fetchOptions = {
				method: 'GET',
				headers: {
					Authorization: `Bearer ${ token }`,
					'Content-Type': 'application/json',
				},
			};

			const response = await fetch( `${ baseUrl }&page=1`, fetchOptions );

			if ( response.status === 401 ) {
				authStore.logout();
				throw new Error( 'Session expirée' );
			}
			if ( ! response.ok ) {
				throw new Error( "Erreur lors de l'accès à l'API" );
			}

			const totalPages = parseInt(
				response.headers.get( 'X-WP-TotalPages' ) || '1'
			);
			let allMembers: Member[] = await response.json();

			if ( totalPages > 1 ) {
				const pagePromises = [];
				for ( let i = 2; i <= totalPages; i++ ) {
					pagePromises.push(
						fetch( `${ baseUrl }&page=${ i }`, fetchOptions ).then(
							( res ) => res.json()
						)
					);
				}
				const additionalResults = await Promise.all( pagePromises );
				additionalResults.forEach( ( pageMembers: Member[] ) => {
					allMembers = allMembers.concat( pageMembers );
				} );
			}

			allMembers.sort( ( a, b ) => {
				const nameA = a.title?.raw || a.title?.rendered || '';
				const nameB = b.title?.raw || b.title?.rendered || '';
				return nameA.localeCompare( nameB, 'fr', {
					sensitivity: 'base',
				} );
			} );

			return allMembers;
		},
		enabled: computed( () => authStore.isAdmin ),
	} );

	const members = computed( () => rawMembers.value || [] );

	// 2. Liste des saisons (Clé admin privée)
	const {
		data: rawSeasons,
		isLoading: isSeasonsLoading,
		refetch: refetchSeasons,
	} = useQuery< Season[] >( {
		queryKey: [ 'admin', 'seasons', 'list' ],
		queryFn: async () => {
			const token = localStorage.getItem( 'dame_jwt_token' );
			if ( ! token ) {
				throw new Error( 'Non authentifié' );
			}

			const response = await fetch(
				`${
					import.meta.env.VITE_API_BASE_URL
				}/wp/v2/seasons?per_page=100`,
				{
					headers: {
						Authorization: `Bearer ${ token }`,
						'Content-Type': 'application/json',
					},
				}
			);

			if ( response.status === 401 ) {
				authStore.logout();
				throw new Error( 'Session expirée' );
			}
			if ( ! response.ok ) {
				throw new Error( 'Erreur saisons' );
			}

			const data: Season[] = await response.json();
			data.sort( ( a, b ) => b.name.localeCompare( a.name ) );
			return data;
		},
		enabled: computed( () => authStore.isAdmin ),
	} );

	const seasons = computed( () => rawSeasons.value || [] );

	const isLoading = computed(
		() => isMembersLoading.value || isSeasonsLoading.value
	);

	const fetchMembers = async ( force = false ) => {
		if ( force ) {
			await queryClient.invalidateQueries( {
				queryKey: [ 'admin', 'members' ],
			} );
		} else {
			await refetchMembers();
		}
	};

	const fetchSeasons = async () => {
		await refetchSeasons();
	};

	const clearData = () => {
		// Le cache global est nettoyé par queryClient.clear() au logout
	};

	return {
		members,
		seasons,
		isLoading,
		fetchMembers,
		fetchSeasons,
		clearData,
	};
} );
