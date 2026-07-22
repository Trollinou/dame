import { defineStore } from 'pinia';
import { computed } from 'vue';
import router from '../router';
import { useAuthStore } from './auth';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { safeFetch } from '@/utils/safeFetch';

export interface Birthday {
	id: number;
	name: string;
	date: string;
	days_until: number;
	next_age: number;
}

export const useDashboardStore = defineStore( 'dashboard', () => {
	const authStore = useAuthStore();
	const queryClient = useQueryClient();

	const { data: rawBirthdays, isLoading } = useQuery< Birthday[] >( {
		queryKey: [ 'dashboard', 'birthdays' ],
		queryFn: async () => {
			const token = localStorage.getItem( 'dame_jwt_token' );
			if ( ! token ) {
				router.push( '/login' );
				throw new Error( 'Non authentifié' );
			}

			const response = await safeFetch(
				`${
					import.meta.env.VITE_API_BASE_URL
				}/dame/v1/birthdays/upcoming?limit=5`,
				{
					method: 'GET',
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
				throw new Error( 'Erreur serveur' );
			}

			return await response.json();
		},
		enabled: computed( () => authStore.isAuthenticated ),
	} );

	const birthdays = computed( () => rawBirthdays.value || [] );

	/**
	 * Récupère les prochains anniversaires (Silent Refresh)
	 * @param force
	 */
	const fetchBirthdays = async ( force = false ) => {
		if ( force ) {
			await queryClient.invalidateQueries( {
				queryKey: [ 'dashboard', 'birthdays' ],
			} );
		}
	};

	/**
	 * Réinitialise les données du store (ex: déconnexion)
	 */
	const clearData = () => {
		// Le cache global est nettoyé par queryClient.clear() au logout
	};

	return {
		birthdays,
		isLoading,
		fetchBirthdays,
		clearData,
	};
} );
