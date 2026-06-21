import { defineStore } from 'pinia';
import { computed } from 'vue';
import { useAuthStore } from './auth';
import { useQuery, useQueryClient } from '@tanstack/vue-query';

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
    [key: string]: any;
  };
}

export interface ContactType {
  id: number;
  name: string;
}

export const useContactStore = defineStore('contacts', () => {
  const authStore = useAuthStore();
  const queryClient = useQueryClient();

  // 1. Liste des contacts (Clé admin privée)
  const { data: contacts, isLoading: isContactsLoading } = useQuery<Contact[]>({
    queryKey: ['admin', 'contacts', 'list'],
    queryFn: async () => {
      const token = localStorage.getItem('dame_jwt_token');
      if (!token) throw new Error("Non authentifié");

      const baseUrl = `${import.meta.env.VITE_API_BASE_URL}/wp/v2/contacts?per_page=100&context=edit`;
      const fetchOptions = {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      };

      const response = await fetch(`${baseUrl}&page=1`, fetchOptions);

      if (response.status === 401) {
        authStore.logout();
        throw new Error("Session expirée");
      }
      if (!response.ok) throw new Error("Erreur serveur");

      const totalPages = parseInt(response.headers.get('X-WP-TotalPages') || '1');
      let allContacts: Contact[] = await response.json();

      if (totalPages > 1) {
        const pagePromises = [];
        for (let i = 2; i <= totalPages; i++) {
          pagePromises.push(
            fetch(`${baseUrl}&page=${i}`, fetchOptions).then(res => {
              if (!res.ok) throw new Error(`Erreur page ${i}`);
              return res.json();
            })
          );
        }
        const results = await Promise.all(pagePromises);
        results.forEach((pageData: Contact[]) => {
          allContacts = allContacts.concat(pageData);
        });
      }

      allContacts.sort((a, b) => {
        const nameA = a.title?.raw || a.title?.rendered || '';
        const nameB = b.title?.raw || b.title?.rendered || '';
        return nameA.localeCompare(nameB, 'fr', { sensitivity: 'base' });
      });

      return allContacts;
    },
    enabled: computed(() => authStore.isAdmin),
    initialData: []
  });

  // 2. Types de contacts (Clé admin privée)
  const { data: contactTypes, isLoading: isTypesLoading } = useQuery<ContactType[]>({
    queryKey: ['admin', 'contactTypes', 'list'],
    queryFn: async () => {
      const token = localStorage.getItem('dame_jwt_token');
      if (!token) throw new Error("Non authentifié");

      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/wp/v2/contact-types?per_page=100`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.status === 401) {
        authStore.logout();
        throw new Error("Session expirée");
      }
      if (!response.ok) throw new Error("Erreur types contacts");

      const data: ContactType[] = await response.json();
      data.sort((a, b) => a.name.localeCompare(b.name, 'fr', { sensitivity: 'base' }));
      return data;
    },
    enabled: computed(() => authStore.isAdmin),
    initialData: []
  });

  const isLoading = computed(() => isContactsLoading.value || isTypesLoading.value);

  const fetchContacts = async (force = false) => {
    if (force) {
      await queryClient.invalidateQueries({ queryKey: ['admin', 'contacts'] });
    }
  };

  const fetchContactTypes = async () => {
    // Géré automatiquement par TanStack Query (enabled: isAdmin)
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
    clearData
  };
});
