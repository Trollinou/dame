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
}

export interface Season {
  id: number;
  name: string;
}

export const useMemberStore = defineStore('members', () => {
  const members = ref<Member[]>([]);
  const seasons = ref<Season[]>([]);
  const isLoading = ref(false);
  const lastFetch = ref<number | null>(null);

  const fetchSeasons = async () => {
    if (seasons.value.length > 0) return;

    const token = localStorage.getItem('dame_jwt_token');
    if (!token) return;

    try {
      const response = await fetch('http://echecs.local/wp-json/wp/v2/seasons?per_page=100', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) throw new Error("Erreur saisons");

      const data: Season[] = await response.json();
      // Tri alphabétique décroissant (les plus récentes en premier)
      data.sort((a, b) => b.name.localeCompare(a.name));
      seasons.value = data;
    } catch (error) {
      console.error("Erreur fetchSeasons:", error);
    }
  };

  const fetchMembers = async (force = false) => {
    const now = Date.now();
    if (!force && members.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    if (members.value.length === 0) {
      isLoading.value = true;
    }

    const token = localStorage.getItem('dame_jwt_token');
    if (!token) {
      router.push('/login');
      return;
    }

    try {
      const baseUrl = 'http://echecs.local/wp-json/wp/v2/adherents?per_page=100&context=edit';
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
