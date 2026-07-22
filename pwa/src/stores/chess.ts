import { defineStore } from 'pinia';
import { ref } from 'vue';
import { useAuthStore } from './auth';
import { useQueryClient } from '@tanstack/vue-query';

export interface PendingGame {
	member_id: number;
	difficulty_level: number;
	hints_count: number;
	takebacks_count: number;
	pgn: string;
	duration: number;
	game_date: string;
}

export const useChessStore = defineStore(
	'chess',
	() => {
		const queryClient = useQueryClient();
		const currentPgn = ref( '' );
		const orientation = ref< 'white' | 'black' >( 'white' );
		const engineElo = ref( 1320 );
		const helpCount = ref( 0 );
		const oupsCount = ref( 0 );
		const isSyncing = ref( false );

		const saveGame = (
			pgn: string,
			playerOrientation: 'white' | 'black',
			elo: number,
			help: number = 0,
			oups: number = 0
		) => {
			currentPgn.value = pgn;
			orientation.value = playerOrientation;
			engineElo.value = elo;
			helpCount.value = help;
			oupsCount.value = oups;
		};

		const clearGame = () => {
			currentPgn.value = '';
			helpCount.value = 0;
			oupsCount.value = 0;
		};

		const saveCompletedGame = async ( durationSeconds: number ) => {
			const authStore = useAuthStore();
			const identity = authStore.selectedIdentity;

			// Ignorer si pas de membre connecté ou si c'est un parent (representative)
			if (
				! identity ||
				identity.type === 'representative' ||
				! identity.member_id
			) {
				return;
			}

			const gameData: PendingGame = {
				member_id: identity.member_id,
				difficulty_level: engineElo.value,
				hints_count: helpCount.value,
				takebacks_count: oupsCount.value,
				pgn: currentPgn.value,
				duration: durationSeconds,
				game_date: new Date().toISOString(),
			};

			// Si hors ligne, stocker localement
			if ( ! navigator.onLine ) {
				queuePendingGame( gameData );
				return;
			}

			// Sinon tenter l'envoi direct
			try {
				const token = authStore.token;
				const response = await fetch(
					`${ import.meta.env.VITE_API_BASE_URL }/roi/v1/games`,
					{
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							Authorization: `Bearer ${ token }`,
						},
						body: JSON.stringify( gameData ),
					}
				);

				if ( ! response.ok ) {
					throw new Error( 'Erreur de sauvegarde serveur' );
				}

				queryClient.invalidateQueries( { queryKey: [ 'saved-games' ] } );
			} catch ( error ) {
				console.warn(
					"Échec de l'envoi immédiat, mise en file d'attente hors ligne:",
					error
				);
				queuePendingGame( gameData );
			}
		};

		const queuePendingGame = ( game: PendingGame ) => {
			const pending = getPendingGames();
			pending.push( game );
			localStorage.setItem(
				'dame_pending_games',
				JSON.stringify( pending )
			);
		};

		const getPendingGames = (): PendingGame[] => {
			const data = localStorage.getItem( 'dame_pending_games' );
			try {
				return data ? JSON.parse( data ) : [];
			} catch {
				return [];
			}
		};

		const syncPendingGames = async () => {
			if ( isSyncing.value || ! navigator.onLine ) {
				return;
			}
			const pending = getPendingGames();
			if ( pending.length === 0 ) {
				return;
			}

			const authStore = useAuthStore();
			if ( ! authStore.token ) {
				return;
			}

			isSyncing.value = true;
			const remaining: PendingGame[] = [];

			for ( const game of pending ) {
				try {
					const response = await fetch(
						`${ import.meta.env.VITE_API_BASE_URL }/roi/v1/games`,
						{
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								Authorization: `Bearer ${ authStore.token }`,
							},
							body: JSON.stringify( game ),
						}
					);

					if ( ! response.ok ) {
						remaining.push( game );
					}
				} catch {
					remaining.push( game );
				}
			}

			localStorage.setItem(
				'dame_pending_games',
				JSON.stringify( remaining )
			);
			isSyncing.value = false;
		};

		// Écouteur de retour en ligne
		if ( typeof window !== 'undefined' ) {
			window.addEventListener( 'online', () => {
				syncPendingGames();
			} );
		}

		return {
			currentPgn,
			orientation,
			engineElo,
			helpCount,
			oupsCount,
			saveGame,
			clearGame,
			saveCompletedGame,
			syncPendingGames,
		};
	},
	{
		persist: true,
	}
);
