import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';
import { useAuthStore } from './auth';

export interface Sondage {
  id: number;
  title: {
    rendered: string;
    raw?: string;
  };
  content?: {
    rendered: string;
  };
  dame_sondage_data: any[];
}

export interface SondageReponse {
  id: number;
  title: {
    rendered: string;
    raw?: string;
  };
  sondage_id: number;
  choices?: string[];
}

export const useSondageStore = defineStore('sondages', () => {
  const sondages = ref<Sondage[]>([]);
  const reponses = ref<SondageReponse[]>([]);
  const isLoading = ref(false);
  let isFetching = false;
  const lastFetch = ref<number | null>(null);

  const getResponseCount = (sondageId: number): number => {
    return reponses.value.filter(r => r.sondage_id === sondageId).length;
  };

  const fetchSondagesData = async (force = false) => {
    if (isFetching) return;

    const now = Date.now();
    if (!force && sondages.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    isFetching = true;
    if (sondages.value.length === 0) {
      isLoading.value = true;
    }

    try {
      const token = localStorage.getItem('dame_jwt_token');
      
      const context = 'view';
      const baseUrl = `${import.meta.env.VITE_API_BASE_URL}/wp/v2`;
      
      const headers: Record<string, string> = {
        'Content-Type': 'application/json'
      };

      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const fetchOptions = {
        method: 'GET',
        headers
      };

      const [sondagesRes, reponsesRes] = await Promise.all([
        fetch(`${baseUrl}/sondages?context=${context}&per_page=100`, fetchOptions),
        fetch(`${baseUrl}/sondage-reponses?context=${context}&per_page=100`, fetchOptions)
      ]);

      if ((sondagesRes.status === 401 || reponsesRes.status === 401) && token) {
        localStorage.removeItem('dame_jwt_token');
        isFetching = false;
        return fetchSondagesData(true);
      }

      if (!sondagesRes.ok || !reponsesRes.ok) throw new Error("Erreur serveur");

      sondages.value = await sondagesRes.json();
      reponses.value = await reponsesRes.json();
      
      lastFetch.value = Date.now();
    } catch (error: any) {
      console.error("Erreur fetchSondagesData:", error);
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
    sondages.value = [];
    reponses.value = [];
    lastFetch.value = null;
  };

  return {
    sondages,
    reponses,
    isLoading,
    lastFetch,
    getResponseCount,
    fetchSondagesData,
    clearData
  };
});
