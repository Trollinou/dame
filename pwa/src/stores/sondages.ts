import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';

export interface Sondage {
  id: number;
  title: {
    raw: string;
  };
  content?: {
    rendered: string;
  };
  dame_sondage_data: any[];
}

export interface SondageReponse {
  id: number;
  title: {
    raw: string;
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
      if (!token) {
        router.push('/login');
        return;
      }

      const fetchOptions = {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      };

      const baseUrl = `${import.meta.env.VITE_API_BASE_URL}/wp/v2`;
      
      const [sondagesRes, reponsesRes] = await Promise.all([
        fetch(`${baseUrl}/sondages?context=edit&per_page=100`, fetchOptions),
        fetch(`${baseUrl}/sondage-reponses?context=edit&per_page=100`, fetchOptions)
      ]);

      if (sondagesRes.status === 401 || reponsesRes.status === 401) throw new Error("Session expirée");
      if (!sondagesRes.ok || !reponsesRes.ok) throw new Error("Erreur serveur");

      sondages.value = await sondagesRes.json();
      reponses.value = await reponsesRes.json();
      
      lastFetch.value = Date.now();
    } catch (error: any) {
      console.error("Erreur fetchSondagesData:", error);
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
    sondages,
    reponses,
    isLoading,
    getResponseCount,
    fetchSondagesData
  };
});
