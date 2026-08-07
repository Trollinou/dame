import { defineStore } from 'pinia';
import { computed } from 'vue';
import { useAuthStore } from './auth';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { fetchWpCollection } from '@/utils/wpApi';

export interface Message {
	id: number;
	date: string;
	modified: string;
	title: {
		rendered: string;
		raw: string;
	};
	content: {
		rendered: string;
	};
	report?: {
		stats: {
			sent: number;
			opened: number;
			rate: number;
		};
		recipients: Array< {
			name: string;
			email: string;
			sent_at: string | null;
			opened_at: string | null;
		} >;
	};
}

export const useMessageStore = defineStore( 'messages', () => {
	const authStore = useAuthStore();
	const queryClient = useQueryClient();

	const {
		data: rawMessages,
		isLoading,
		refetch: refetchMessages,
	} = useQuery< Message[] >( {
		queryKey: [ 'admin', 'messages', 'list' ],
		queryFn: async () => {
			const data = await fetchWpCollection< Message >(
				'/wp/v2/messages?context=edit&per_page=100'
			);
			data.sort(
				( a, b ) =>
					new Date( b.date ).getTime() - new Date( a.date ).getTime()
			);
			return data;
		},
		enabled: computed( () => authStore.isAdmin ),
	} );

	const messages = computed( () => rawMessages.value || [] );

	const fetchMessages = async ( force = false ) => {
		if ( force ) {
			await queryClient.invalidateQueries( {
				queryKey: [ 'admin', 'messages' ],
			} );
		} else {
			await refetchMessages();
		}
	};

	const clearData = () => {
		// Le cache global est nettoyé par queryClient.clear() au logout
	};

	return {
		messages,
		isLoading,
		fetchMessages,
		clearData,
	};
} );
