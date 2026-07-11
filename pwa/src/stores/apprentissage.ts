import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useAuthStore } from './auth';
import { safeFetch } from '@/utils/safeFetch';

export interface ExerciceConfig {
	fen: string;
	solution: string[];
	couleur_joueur: 'white' | 'black';
	id?: number;
}

export interface Exercice {
	id: number;
	titre: string;
	type: number;
	config: ExerciceConfig;
	niveau: number;
	chapitre: string;
	couleur: string;
}

export interface ExerciceResume {
	id: number;
	titre: string;
	type: number;
	niveau: number;
	chapitre: string;
	couleur: string;
}

export const useApprentissageStore = defineStore( 'apprentissage', () => {
	const authStore = useAuthStore();
	const exerciceActuel = ref< Exercice | null >( null );
	const listeExercices = ref< ExerciceResume[] >( [] );
	const isLoadingListe = ref( false );
	const exercicesValides = ref< number[] >( [] );

	const exercicesGroupes = computed( () => {
		return listeExercices.value.reduce<
			Record<
				number,
				Record<
					string,
					{ couleur: string; exercices: ExerciceResume[] }
				>
			>
		>( ( acc, exercice ) => {
			const { niveau, chapitre, couleur } = exercice;
			const numNiveau = niveau !== undefined && niveau !== null ? niveau : 1;
			const nomChapitre = chapitre && chapitre.trim() !== '' ? chapitre.trim() : "Autres exercices";
			const couleurChapitre = couleur || "medium";

			if ( ! acc[ numNiveau ] ) {
				acc[ numNiveau ] = {};
			}
			if ( ! acc[ numNiveau ][ nomChapitre ] ) {
				acc[ numNiveau ][ nomChapitre ] = {
					couleur: couleurChapitre,
					exercices: [],
				};
			}
			acc[ numNiveau ][ nomChapitre ].exercices.push( exercice );
			return acc;
		}, {} );
	} );

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
			if ( data && data.title && ! data.titre ) {
				data.titre = data.title;
			}
			exerciceActuel.value = data;
		} catch ( error ) {
			console.error(
				"Erreur lors de la récupération de l'exercice :",
				error
			);
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
				throw new Error(
					'Impossible de charger la liste des exercices.'
				);
			}

			const data = await response.json();
			listeExercices.value = data;
		} catch ( error ) {
			console.error(
				'Erreur lors de la récupération de la liste des exercices :',
				error
			);
		} finally {
			isLoadingListe.value = false;
		}
	};

	const validerExercice = async ( exerciceId: number ): Promise< void > => {
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
					body: JSON.stringify( { exercice_id: exerciceId } ),
				},
				5000
			);

			if ( response.status === 401 && token ) {
				authStore.logout();
				throw new Error( 'Session expirée' );
			}

			if ( ! response.ok ) {
				throw new Error(
					`Impossible de valider l'exercice ${ exerciceId }.`
				);
			}

			if ( ! exercicesValides.value.includes( exerciceId ) ) {
				exercicesValides.value.push( exerciceId );
			}
		} catch ( error ) {
			console.error(
				"Erreur lors de la validation de l'exercice :",
				error
			);
		}
	};

	const clearData = () => {
		exerciceActuel.value = null;
		listeExercices.value = [];
		isLoadingListe.value = false;
		exercicesValides.value = [];
	};

	return {
		exerciceActuel,
		listeExercices,
		isLoadingListe,
		exercicesValides,
		exercicesGroupes,
		fetchExercice,
		fetchListeExercices,
		validerExercice,
		clearData,
	};
} );
