import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useAuthStore } from './auth';
import { safeFetch } from '@/utils/safeFetch';
import { useQuery, useQueryClient } from '@tanstack/vue-query';

export interface ExerciceConfig {
	shapes?: any[];
	[key: string]: any;
}

export interface Contenu {
	id: number;
	titre: string;
	post_type: string;
	chapitre_nom: string;
	chapitre_couleur: string;
	niveau: number;
	type?: number;
	config?: ExerciceConfig;
	contenu_html?: string;
}

export interface PlaylistItem {
	type: string;
	id: number;
	titre?: string;
}

export interface Cours {
	id: number;
	titre: string;
	niveau: number;
	chapitre_nom: string;
	chapitre_couleur: string;
	playlist: PlaylistItem[];
}

export const useApprentissageStore = defineStore( 'apprentissage', () => {
	const authStore = useAuthStore();
	const queryClient = useQueryClient();

	const contenuActuelId = ref< number | null >( null );
	const isCustomLoading = ref( false );

	// Headers de sécurité
	const getAuthHeaders = (): Record< string, string > => {
		const token = localStorage.getItem( 'dame_jwt_token' );
		const headers: Record< string, string > = {
			'Content-Type': 'application/json',
		};
		if ( token ) {
			headers.Authorization = `Bearer ${ token }`;
		}
		if ( authStore.selectedIdentity?.id ) {
			headers['X-Selected-Identity'] = authStore.selectedIdentity.id;
		}
		return headers;
	};

	// 1. Query des Parcours
	const {
		data: queryParcours,
		isLoading: isParcoursLoading,
		refetch: refetchParcours,
	} = useQuery< Cours[] >( {
		queryKey: [
			'parcours',
			computed( () => authStore.selectedIdentity?.id || 'default' ),
		],
		queryFn: async () => {
			const apiUrl = import.meta.env.VITE_API_BASE_URL;
			const response = await safeFetch(
				`${ apiUrl }/roi/v1/parcours`,
				{ method: 'GET', headers: getAuthHeaders() },
				5000
			);

			if (
				response.status === 401 &&
				localStorage.getItem( 'dame_jwt_token' )
			) {
				authStore.logout();
				throw new Error( 'Session expirée' );
			}
			if ( ! response.ok ) {
				throw new Error( 'Impossible de charger les parcours.' );
			}
			return response.json();
		},
	} );

	// 2. Query de Progression
	const {
		data: queryProgression,
		refetch: refetchProgression,
	} = useQuery< number[] >( {
		queryKey: [
			'progression',
			computed( () => authStore.selectedIdentity?.id || 'default' ),
		],
		queryFn: async () => {
			const apiUrl = import.meta.env.VITE_API_BASE_URL;
			const response = await safeFetch(
				`${ apiUrl }/roi/v1/progression`,
				{ method: 'GET', headers: getAuthHeaders() },
				5000
			);

			if (
				response.status === 401 &&
				localStorage.getItem( 'dame_jwt_token' )
			) {
				authStore.logout();
				throw new Error( 'Session expirée' );
			}
			if ( ! response.ok ) {
				throw new Error( 'Impossible de charger la progression.' );
			}
			const data = await response.json();
			return Array.isArray( data ) ? data : data.elements_valides || [];
		},
	} );

	// 3. Query du Contenu Actuel
	const {
		data: queryContenu,
		isLoading: isContenuLoading,
	} = useQuery< Contenu | null >( {
		queryKey: [
			'contenu',
			contenuActuelId,
			computed( () => authStore.selectedIdentity?.id || 'default' ),
		],
		enabled: computed( () => contenuActuelId.value !== null ),
		queryFn: async () => {
			if ( ! contenuActuelId.value ) return null;
			const apiUrl = import.meta.env.VITE_API_BASE_URL;
			const response = await safeFetch(
				`${ apiUrl }/roi/v1/contenu/${ contenuActuelId.value }`,
				{ method: 'GET', headers: getAuthHeaders() },
				5000
			);

			if (
				response.status === 401 &&
				localStorage.getItem( 'dame_jwt_token' )
			) {
				authStore.logout();
				throw new Error( 'Session expirée' );
			}
			if ( ! response.ok ) {
				throw new Error(
					`Impossible de charger le contenu ${ contenuActuelId.value }.`
				);
			}
			return response.json();
		},
	} );

	// Computed properties
	const parcours = computed( () => queryParcours.value || [] );
	const elementsValides = computed( () => queryProgression.value || [] );
	const contenuActuel = computed( () => queryContenu.value || null );
	const isLoading = computed(
		() =>
			isParcoursLoading.value ||
			isContenuLoading.value ||
			isCustomLoading.value
	);

	// Getters
	const isCoursUnlocked = computed( () => {
		return ( coursIndex: number ): boolean => {
			if ( coursIndex <= 0 ) {
				return true;
			}
			const coursPrecedent = parcours.value[ coursIndex - 1 ];
			if ( ! coursPrecedent ) {
				return false;
			}
			return coursPrecedent.playlist.every( ( item ) =>
				elementsValides.value.includes( item.id )
			);
		};
	} );

	const isElementUnlocked = computed( () => {
		return ( coursIndex: number, playlistIndex: number ): boolean => {
			if ( ! isCoursUnlocked.value( coursIndex ) ) {
				return false;
			}
			if ( playlistIndex <= 0 ) {
				return true;
			}
			const cours = parcours.value[ coursIndex ];
			if ( ! cours ) {
				return false;
			}
			const elementPrecedent = cours.playlist[ playlistIndex - 1 ];
			if ( ! elementPrecedent ) {
				return false;
			}
			return elementsValides.value.includes( elementPrecedent.id );
		};
	} );

	// Actions
	const fetchParcours = async (): Promise< void > => {
		await refetchParcours();
	};

	const fetchProgression = async (): Promise< void > => {
		await refetchProgression();
	};

	const fetchContenu = async ( id: number ): Promise< void > => {
		contenuActuelId.value = id;
	};

	const validerElement = async ( id: number ): Promise< void > => {
		try {
			const apiUrl = import.meta.env.VITE_API_BASE_URL;
			const response = await safeFetch(
				`${ apiUrl }/roi/v1/progression`,
				{
					method: 'POST',
					headers: getAuthHeaders(),
					body: JSON.stringify( { element_id: id } ),
				},
				5000
			);

			if (
				response.status === 401 &&
				localStorage.getItem( 'dame_jwt_token' )
			) {
				authStore.logout();
				throw new Error( 'Session expirée' );
			}
			if ( ! response.ok ) {
				throw new Error( `Impossible de valider l'élément ${ id }.` );
			}

			// Invalidation du cache de progression pour forcer la mise à jour réactive
			await queryClient.invalidateQueries( {
				queryKey: [ 'progression' ],
			} );
		} catch ( error ) {
			console.error(
				"Erreur lors de la validation de l'élément :",
				error
			);
		}
	};

	const clearData = () => {
		contenuActuelId.value = null;
		queryClient.removeQueries( { queryKey: [ 'parcours' ] } );
		queryClient.removeQueries( { queryKey: [ 'progression' ] } );
		queryClient.removeQueries( { queryKey: [ 'contenu' ] } );
	};

	return {
		parcours,
		contenuActuel,
		elementsValides,
		isLoading,
		isCoursUnlocked,
		isElementUnlocked,
		fetchParcours,
		fetchProgression,
		fetchContenu,
		validerElement,
		clearData,
	};
} );
