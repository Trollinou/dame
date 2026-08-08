import { defineStore } from 'pinia';
import { computed } from 'vue';
import { useAuthStore } from './auth';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { fetchWpCollection } from '@/utils/wpApi';

export interface Contact {
	id: number;
	modified: string;
	title: {
		rendered: string;
		raw: string;
	};
	'contact-types': number[];
	meta?: {
		_dame_contact_first_name?: string;
		_dame_contact_last_name?: string;
		_dame_contact_role?: string;
		_dame_contact_organization?: string;
		_dame_contact_phone?: string;
		_dame_contact_email?: string;
		_dame_contact_address_1?: string;
		_dame_contact_address_2?: string;
		_dame_contact_postcode?: string;
		_dame_contact_city?: string;
		[ key: string ]: any;
	};
}

export interface ContactType {
	id: number;
	name: string;
}

export const useContactStore = defineStore( 'contacts', () => {
	const authStore = useAuthStore();
	const queryClient = useQueryClient();

	// 1. Liste des contacts (Clé admin privée)
	const {
		data: rawContacts,
		isLoading: isContactsLoading,
		refetch: refetchContacts,
	} = useQuery< Contact[] >( {
		queryKey: [ 'admin', 'contacts', 'list' ],
			const allContacts = await fetchWpCollection< Contact >(
				'/wp/v2/contacts?per_page=100&context=edit'
			);

			allContacts.sort( ( a, b ) => {
				const nameA = a.title?.raw || a.title?.rendered || '';
				const nameB = b.title?.raw || b.title?.rendered || '';
				return nameA.localeCompare( nameB, 'fr', {
					sensitivity: 'base',
				} );
			} );

			return allContacts;
		},
		enabled: computed( () => authStore.isAdmin ),
	} );

	const contacts = computed( () => rawContacts.value || [] );

	// 2. Types de contacts (Clé admin privée)
	const {
		data: rawContactTypes,
		isLoading: isTypesLoading,
		refetch: refetchContactTypes,
	} = useQuery< ContactType[] >( {
		queryKey: [ 'admin', 'contactTypes', 'list' ],
		queryFn: async () => {
			const data = await fetchWpCollection< ContactType >(
				'/wp/v2/contact-types?per_page=100'
			);
			data.sort( ( a, b ) =>
				a.name.localeCompare( b.name, 'fr', { sensitivity: 'base' } )
			);
			return data;
		},
		enabled: computed( () => authStore.isAdmin ),
	} );

	const contactTypes = computed( () => rawContactTypes.value || [] );

	const isLoading = computed(
		() => isContactsLoading.value || isTypesLoading.value
	);

	const fetchContacts = async ( force = false ) => {
		if ( force ) {
			await queryClient.invalidateQueries( {
				queryKey: [ 'admin', 'contacts' ],
			} );
		} else {
			await refetchContacts();
		}
	};

	const fetchContactTypes = async () => {
		await refetchContactTypes();
	};

	const clearData = () => {
		// Le cache global est nettoyé par queryClient.clear() au logout
	};

	return {
		contacts,
		contactTypes,
		isLoading,
		fetchContacts,
		fetchContactTypes,
		clearData,
	};
} );
