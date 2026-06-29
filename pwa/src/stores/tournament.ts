import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { safeFetch } from '@/utils/safeFetch';
import { useQuery, useQueryClient } from '@tanstack/vue-query';

export interface MenuItem {
	id: number;
	title: string;
	object_id: number; // ID de la page WordPress
	parent: string | number;
	modified: string; // Date de modification du menu/lien
}

export interface CachedPage {
	id: number;
	title: { rendered: string };
	content: { rendered: string };
	modified: string;
}

export const useTournamentStore = defineStore( 'tournament', () => {
	const queryClient = useQueryClient();
	const cachedPages = ref< Record< number, CachedPage > >( {} );

	// Query pour récupérer le menu des tournois
	const {
		data: rawMenuItems,
		isLoading,
		refetch,
	} = useQuery< MenuItem[] >( {
		queryKey: [ 'tournament', 'menu' ],
		queryFn: async () => {
			const apiUrl = import.meta.env.VITE_API_BASE_URL;
			const response = await safeFetch(
				`${ apiUrl }/dame/v1/pwa-menu`,
				{},
				4000
			);

			if ( ! response.ok ) {
				throw new Error( 'Impossible de charger le menu.' );
			}

			const newData: MenuItem[] = await response.json();

			// PRÉ-CHARGEMENT PROACTIF : On charge le contenu de chaque page de tournoi
			// pour qu'ils soient disponibles hors-ligne sans même avoir à cliquer dessus.
			if ( navigator.onLine ) {
				newData.forEach( ( item ) => {
					if ( item.object_id ) {
						fetchPage( item.object_id );
					}
				} );
			}

			return newData;
		},
	} );

	const menuItems = computed( () => rawMenuItems.value || [] );

	const fetchMenu = async ( force = false ) => {
		if ( force ) {
			await queryClient.invalidateQueries( {
				queryKey: [ 'tournament', 'menu' ],
			} );
		} else {
			await refetch();
		}
	};

	/**
	 * Récupère une page de détail (depuis le cache ou le réseau)
	 * @param pageId
	 */
	const fetchPage = async (
		pageId: number
	): Promise< CachedPage | undefined > => {
		const queryKey = [ 'tournament', 'page', pageId ];

		if ( ! navigator.onLine ) {
			return queryClient.getQueryData< CachedPage >( queryKey );
		}

		try {
			return await queryClient.fetchQuery< CachedPage >( {
				queryKey,
				queryFn: async () => {
					const apiUrl = import.meta.env.VITE_API_BASE_URL;
					const response = await safeFetch(
						`${ apiUrl }/wp/v2/pages/${ pageId }`,
						{},
						4000
					);
					if ( ! response.ok ) {
						throw new Error( `Erreur chargement page ${ pageId }` );
					}
					return await response.json();
				},
				staleTime: 1000 * 60 * 60, // 1 heure de validité
			} );
		} catch ( err ) {
			console.error( `Erreur fetchPage ${ pageId }:`, err );
			return queryClient.getQueryData< CachedPage >( queryKey );
		}
	};

	const clearData = () => {
		// Le cache global est nettoyé par queryClient.clear() au logout
	};

	return {
		menuItems,
		cachedPages,
		isLoading,
		fetchMenu,
		fetchPage,
		clearData,
	};
} );
