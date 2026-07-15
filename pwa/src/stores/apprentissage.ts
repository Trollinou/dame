import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useAuthStore } from './auth';
import { safeFetch } from '@/utils/safeFetch';

export interface Contenu {
	id: number;
	titre: string;
	post_type: string;
	chapitre_nom: string;
	chapitre_couleur: string;
	niveau: number;
	type?: number;
	config?: any;
	contenu_html?: string;
}

export interface PlaylistItem {
	type: string;
	id: number;
}

export interface Cours {
	id: number;
	titre: string;
	niveau: number;
	chapitre_nom: string;
	chapitre_couleur: string;
	playlist: PlaylistItem[];
}

export interface ExerciceConfig {
	fen: string;
	solution: string[];
	couleur_joueur: 'white' | 'black';
	id?: number;
}

export const useApprentissageStore = defineStore( 'apprentissage', () => {
	const authStore = useAuthStore();

	// State
	const parcours = ref< Cours[] >( [] );
	const contenuActuel = ref< Contenu | null >( null );
	const elementsValides = ref< number[] >( [] );
	const isLoading = ref( false );

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
			return coursPrecedent.playlist.every( item =>
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
		isLoading.value = true;
		try {
			const token = localStorage.getItem( 'dame_jwt_token' );
			const apiUrl = import.meta.env.VITE_API_BASE_URL;
			const headers: Record< string, string > = {
				'Content-Type': 'application/json',
			};

			if ( token ) {
				headers.Authorization = `Bearer ${ token }`;
			}

			const response = await safeFetch(
				`${ apiUrl }/roi/v1/parcours`,
				{
					method: 'GET',
					headers,
				},
				5000
			);

			if ( response.status === 401 && token ) {
				authStore.logout();
				throw new Error( 'Session expirée' );
			}

			if ( ! response.ok ) {
				throw new Error( 'Impossible de charger les parcours.' );
			}

			const data = await response.json();
			parcours.value = data;
		} catch ( error ) {
			console.error(
				'Erreur lors de la récupération des parcours :',
				error
			);
		} finally {
			isLoading.value = false;
		}
	};

	const fetchProgression = async (): Promise< void > => {
		try {
			const token = localStorage.getItem( 'dame_jwt_token' );
			const apiUrl = import.meta.env.VITE_API_BASE_URL;
			const headers: Record< string, string > = {
				'Content-Type': 'application/json',
			};

			if ( token ) {
				headers.Authorization = `Bearer ${ token }`;
			}

			const response = await safeFetch(
				`${ apiUrl }/roi/v1/progression`,
				{
					method: 'GET',
					headers,
				},
				5000
			);

			if ( response.status === 401 && token ) {
				authStore.logout();
				throw new Error( 'Session expirée' );
			}

			if ( ! response.ok ) {
				throw new Error( 'Impossible de charger la progression.' );
			}

			const data = await response.json();
			elementsValides.value = Array.isArray( data )
				? data
				: ( data.elements_valides || [] );
		} catch ( error ) {
			console.error(
				'Erreur lors de la récupération de la progression :',
				error
			);
		}
	};

	const fetchContenu = async ( id: number ): Promise< void > => {
		isLoading.value = true;
		try {
			const token = localStorage.getItem( 'dame_jwt_token' );
			const apiUrl = import.meta.env.VITE_API_BASE_URL;
			const headers: Record< string, string > = {
				'Content-Type': 'application/json',
			};

			if ( token ) {
				headers.Authorization = `Bearer ${ token }`;
			}

			const response = await safeFetch(
				`${ apiUrl }/roi/v1/contenu/${ id }`,
				{
					method: 'GET',
					headers,
				},
				5000
			);

			if ( response.status === 401 && token ) {
				authStore.logout();
				throw new Error( 'Session expirée' );
			}

			if ( ! response.ok ) {
				throw new Error( `Impossible de charger le contenu ${ id }.` );
			}

			const data = await response.json();
			contenuActuel.value = data;
		} catch ( error ) {
			console.error(
				'Erreur lors de la récupération du contenu :',
				error
			);
		} finally {
			isLoading.value = false;
		}
	};

	const validerElement = async ( id: number ): Promise< void > => {
		try {
			const token = localStorage.getItem( 'dame_jwt_token' );
			const apiUrl = import.meta.env.VITE_API_BASE_URL;
			const headers: Record< string, string > = {
				'Content-Type': 'application/json',
			};

			if ( token ) {
				headers.Authorization = `Bearer ${ token }`;
			}

			const response = await safeFetch(
				`${ apiUrl }/roi/v1/progression`,
				{
					method: 'POST',
					headers,
					body: JSON.stringify( { element_id: id } ),
				},
				5000
			);

			if ( response.status === 401 && token ) {
				authStore.logout();
				throw new Error( 'Session expirée' );
			}

			if ( ! response.ok ) {
				throw new Error(
					`Impossible de valider l'élément ${ id }.`
				);
			}

			if ( ! elementsValides.value.includes( id ) ) {
				elementsValides.value.push( id );
			}
		} catch ( error ) {
			console.error(
				"Erreur lors de la validation de l'élément :",
				error
			);
		}
	};

	const clearData = () => {
		parcours.value = [];
		contenuActuel.value = null;
		elementsValides.value = [];
		isLoading.value = false;
	};
	const validerExercice = validerElement;

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
		validerExercice,
		clearData,
	};
} );
