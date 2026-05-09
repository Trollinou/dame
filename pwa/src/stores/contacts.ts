import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';

export interface Contact {
  id: number;
  title: {
    rendered: string;
    raw: string;
  };
  'contact-types': number[];
}

export interface ContactType {
  id: number;
  name: string;
}

export const useContactStore = defineStore('contacts', () => {
  const contacts = ref<Contact[]>([]);
  const contactTypes = ref<ContactType[]>([]);
  const isLoading = ref(false);
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
      // Tri alphabétique croissant
      data.sort((a, b) => a.name.localeCompare(b.name, 'fr', { sensitivity: 'base' }));
      contactTypes.value = data;
    } catch (error) {
      console.error("Erreur fetchContactTypes:", error);
    }
  };

  const fetchContacts = async (force = false) => {
    const now = Date.now();
    if (!force && contacts.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    if (contacts.value.length === 0) {
      isLoading.value = true;
    }

    const token = localStorage.getItem('dame_jwt_token');
    if (!token) {
      router.push('/login');
      return;
    }

    try {
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
        const nameA = a.title?.raw || '';
        const nameB = b.title?.raw || '';
        return nameA.localeCompare(nameB, 'fr', { sensitivity: 'base' });
      });

      contacts.value = allContacts;
      lastFetch.value = Date.now();
    } catch (error: any) {
      console.error("Erreur fetchContacts:", error);
      if (error.message === "Session expirée") {
        localStorage.removeItem('dame_jwt_token');
        router.push('/login');
      }
    } finally {
      isLoading.value = false;
    }
  };

  return {
    contacts,
    contactTypes,
    isLoading,
    lastFetch,
    fetchContacts,
    fetchContactTypes
  };
});
