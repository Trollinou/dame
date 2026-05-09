import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';

export interface Sondage {
  id: number;
  title: {
    raw: string;
  };
  dame_sondage_data: any[]; // Tableau de données du sondage
}

export interface SondageReponse {
  id: number;
  title: {
    raw: string;
  };
  sondage_id: number; // Mapping direct vers le sondage
}

export const useSondageStore = defineStore('sondages', () => {
  const sondages = ref<Sondage[]>([]);
  const reponses = ref<SondageReponse[]>([]);
  const isLoading = ref(false);
  const lastFetch = ref<number | null>(null);

  /**
   * Getter: Retourne le nombre de réponses pour un sondage spécifique
   */
  const getResponseCount = (sondageId: number): number => {
    return reponses.value.filter(r => r.sondage_id === sondageId).length;
  };

  /**
   * Action: Récupère les sondages et les réponses (Silent Refresh)
   */
  const fetchSondagesData = async (force = false) => {
    const now = Date.now();
    if (!force && sondages.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    if (sondages.value.length === 0) {
      isLoading.value = true;
    }

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

    try {
      const baseUrl = `${import.meta.env.VITE_API_BASE_URL}`;
      
      // On récupère les 100 premiers de chaque (on pourra ajouter la pagination complète si besoin)
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
