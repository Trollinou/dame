import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';

export interface Member {
  id: number;
  title: {
    rendered: string;
    raw: string;
  };
  status: string;
  seasons: number[];
  meta?: {
    _dame_email?: string;
    _dame_phone_number?: string;
    _dame_birth_date?: string;
    _dame_sexe?: string;
    _dame_license_type?: string;
    _dame_license_number?: string;
    // Adresse
    _dame_address_1?: string;
    _dame_address_2?: string;
    _dame_postal_code?: string;
    _dame_city?: string;
    // Représentant légal 1
    _dame_legal_rep_1_first_name?: string;
    _dame_legal_rep_1_last_name?: string;
    _dame_legal_rep_1_email?: string;
    _dame_legal_rep_1_phone?: string;
    _dame_legal_rep_1_profession?: string;
    // Représentant légal 2
    _dame_legal_rep_2_first_name?: string;
    _dame_legal_rep_2_last_name?: string;
    _dame_legal_rep_2_email?: string;
    _dame_legal_rep_2_phone?: string;
    _dame_legal_rep_2_profession?: string;
    [key: string]: any;
  };
}

export interface Season {
  id: number;
  name: string;
}

export const useMemberStore = defineStore('members', () => {
  const members = ref<Member[]>([]);
  const seasons = ref<Season[]>([]);
  const isLoading = ref(false);
  let isFetching = false;
  const lastFetch = ref<number | null>(null);

  const fetchSeasons = async () => {
    if (seasons.value.length > 0) return;

    const token = localStorage.getItem('dame_jwt_token');
    if (!token) return;

    try {
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/wp/v2/seasons?per_page=100`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) throw new Error("Erreur saisons");

      const data: Season[] = await response.json();
      data.sort((a, b) => b.name.localeCompare(a.name));
      seasons.value = data;
    } catch (error) {
      console.error("Erreur fetchSeasons:", error);
    }
  };

  const fetchMembers = async (force = false) => {
    if (isFetching) return;

    const now = Date.now();
    if (!force && members.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    isFetching = true;
    if (members.value.length === 0) {
      isLoading.value = true;
    }

    try {
      const token = localStorage.getItem('dame_jwt_token');
      if (!token) {
        router.push('/login');
        return;
      }

      const baseUrl = `${import.meta.env.VITE_API_BASE_URL}/wp/v2/adherents?per_page=100&context=edit`;
      const fetchOptions = {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      };

      const response = await fetch(`${baseUrl}&page=1`, fetchOptions);

      if (response.status === 401) throw new Error("Session expirée");
      if (!response.ok) throw new Error("Erreur lors de l'accès à l'API");

      const totalPages = parseInt(response.headers.get('X-WP-TotalPages') || '1');
      let allMembers: Member[] = await response.json();

      if (totalPages > 1) {
        const pagePromises = [];
        for (let i = 2; i <= totalPages; i++) {
          pagePromises.push(
            fetch(`${baseUrl}&page=${i}`, fetchOptions).then(res => res.json())
          );
        }
        const additionalResults = await Promise.all(pagePromises);
        additionalResults.forEach((pageMembers: Member[]) => {
          allMembers = allMembers.concat(pageMembers);
        });
      }

      // Tri alphabétique (ignore accents et casse)
      allMembers.sort((a, b) => {
        const nameA = a.title?.raw || '';
        const nameB = b.title?.raw || '';
        return nameA.localeCompare(nameB, 'fr', { sensitivity: 'base' });
      });

      members.value = allMembers;
      lastFetch.value = Date.now();
    } catch (error: any) {
      console.error("Erreur fetchMembers:", error);
      if (error.message === "Session expirée") {
        localStorage.removeItem('dame_jwt_token');
        router.push('/login');
      }
    } finally {
      isLoading.value = false;
      isFetching = false;
    }
  };

  return {
    members,
    seasons,
    isLoading,
    lastFetch,
    fetchMembers,
    fetchSeasons
  };
});
