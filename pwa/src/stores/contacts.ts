import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';
import { useAuthStore } from './auth';

export interface Contact {
  id: number;
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
  const contacts = ref<Contact[]>([]);
  const contactTypes = ref<ContactType[]>([]);
  const isLoading = ref(false);
  let isFetching = false;
  const lastFetch = ref<number | null>(null);

  const fetchContactTypes = async () => {
    if (contactTypes.value.length > 0) return;

    const token = localStorage.getItem('dame_jwt_token');
    if (!token) return;

    try {
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/wp/v2/contact-types?per_page=100`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) throw new Error("Erreur types contacts");

      const data: ContactType[] = await response.json();
      data.sort((a, b) => a.name.localeCompare(b.name, 'fr', { sensitivity: 'base' }));
      contactTypes.value = data;
    } catch (error) {
      console.error("Erreur fetchContactTypes:", error);
    }
  };

  const fetchContacts = async (force = false) => {
    if (isFetching) return;

    const now = Date.now();
    if (!force && contacts.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    isFetching = true;
    if (contacts.value.length === 0) {
      isLoading.value = true;
    }

    try {
      const token = localStorage.getItem('dame_jwt_token');
      if (!token) {
        router.push('/login');
        return;
      }

      const baseUrl = `${import.meta.env.VITE_API_BASE_URL}/wp/v2/contacts?per_page=100&context=edit`;
      const fetchOptions = {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      };

      const response = await fetch(`${baseUrl}&page=1`, fetchOptions);

      if (response.status === 401) throw new Error("Session expirée");
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

      // Tri alphabétique (ignore accents et casse)
      allContacts.sort((a, b) => {
        const nameA = a.title?.raw || a.title?.rendered || '';
        const nameB = b.title?.raw || b.title?.rendered || '';
        return nameA.localeCompare(nameB, 'fr', { sensitivity: 'base' });
      });

      contacts.value = allContacts;
      lastFetch.value = Date.now();
    } catch (error: any) {
      console.error("Erreur fetchContacts:", error);
      if (error.message === "Session expirée") {
        useAuthStore().logout();
      }
    } finally {
      isLoading.value = false;
      isFetching = false;
    }
  };

  /**
   * Réinitialise les données du store (ex: déconnexion)
   */
  const clearData = () => {
    contacts.value = [];
    contactTypes.value = [];
    lastFetch.value = null;
  };

  return {
    contacts,
    contactTypes,
    isLoading,
    lastFetch,
    fetchContacts,
    fetchContactTypes,
    clearData
  };
});
