import { defineStore } from 'pinia';
import { ref } from 'vue';
import { useAuthStore } from './auth';
import { safeFetch } from '@/utils/safeFetch';

export interface ExerciceConfig {
	fen: string;
	solution: string[];
	couleur_joueur: 'white' | 'black';
}

export interface Exercice {
	id: number;
	titre: string;
	type: number;
	config: ExerciceConfig;
}

export interface ExerciceResume {
	id: number;
	titre: string;
	type: number;
}

export const useApprentissageStore = defineStore( 'apprentissage', () => {
	const authStore = useAuthStore();
	const exerciceActuel = ref< Exercice | null >( null );
	const listeExercices = ref< ExerciceResume[] >( [] );
	const isLoadingListe = ref( false );

	const fetchExercice = async ( id: number ): Promise< void > => {
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
				`${ apiUrl }/roi/v1/exercice/${ id }`,
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
				throw new Error( `Impossible de charger l'exercice ${ id }.` );
			}

			const data = await response.json();
			exerciceActuel.value = data;
		} catch ( error ) {
			console.error( "Erreur lors de la récupération de l'exercice :", error );
		}
	};

	const fetchListeExercices = async (): Promise< void > => {
		isLoadingListe.value = true;
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
				`${ apiUrl }/roi/v1/exercices`,
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
				throw new Error( 'Impossible de charger la liste des exercices.' );
			}

			const data = await response.json();
			listeExercices.value = data;
		} catch ( error ) {
			console.error( 'Erreur lors de la récupération de la liste des exercices :', error );
		} finally {
			isLoadingListe.value = false;
		}
	};

	const clearData = () => {
		exerciceActuel.value = null;
		listeExercices.value = [];
		isLoadingListe.value = false;
	};

	return {
		exerciceActuel,
		listeExercices,
		isLoadingListe,
		fetchExercice,
		fetchListeExercices,
		clearData,
	};
} );
