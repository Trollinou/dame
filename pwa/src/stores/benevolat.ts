import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';
import { useAuthStore } from './auth';

export interface Benevolat {
  id: number;
  title: {
    rendered: string;
    raw?: string;
  };
  content?: {
    rendered: string;
  };
  dame_benevolat_data: any[];
}

export interface BenevolatReponse {
  id: number;
  title: {
    rendered: string;
    raw?: string;
  };
  benevolat_id: number;
  choices?: string[];
  meta?: {
    _dame_member_id?: number;
  };
}

export const useBenevolatStore = defineStore('benevolat', () => {
  const benevolats = ref<Benevolat[]>([]);
  const reponses = ref<BenevolatReponse[]>([]);
  const userVotedIds = ref<number[]>([]);
  const isLoading = ref(false);
  let isFetching = false;
  const lastFetch = ref<number | null>(null);

  const getResponseCount = (benevolatId: number): number => {
    return reponses.value.filter(r => r.benevolat_id === benevolatId).length;
  };

  /**
   * Vérifie si l'identité actuelle a déjà voté
   */
  const hasUserVoted = (benevolatId: number): boolean => {
    if (userVotedIds.value.includes(benevolatId)) return true;

    const authStore = useAuthStore();
    const identity = authStore.selectedIdentity;
    if (!identity) return false;

    return reponses.value.some(r => 
      r.benevolat_id === benevolatId && 
      (r.meta?._dame_member_id === identity.member_id || r.title.rendered.toLowerCase().trim() === identity.name.toLowerCase().trim())
    );
  };

  const markAsVoted = (benevolatId: number) => {
    const id = Number(benevolatId);
    if (!userVotedIds.value.includes(id)) {
      userVotedIds.value.push(id);
    }
  };

  const fetchBenevolatsData = async (force = false) => {
    if (isFetching) return;

    const now = Date.now();
    if (!force && benevolats.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    isFetching = true;
    if (benevolats.value.length === 0) {
      isLoading.value = true;
    }

    try {
      const token = localStorage.getItem('dame_jwt_token');
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (token) headers['Authorization'] = `Bearer ${token}`;

      // Routes Standard WordPress pour la liste
      const [benevolatsRes, reponsesRes] = await Promise.all([
        fetch(`${apiUrl}/wp/v2/benevolats?context=view&per_page=100`, { headers }),
        fetch(`${apiUrl}/wp/v2/benevolat-reponses?context=view&per_page=100`, { headers })
      ]);

      if (benevolatsRes.status === 401 && token) {
        useAuthStore().logout();
        return;
      }

      if (!benevolatsRes.ok) throw new Error("Erreur chargement bénévolats");

      benevolats.value = await benevolatsRes.json();
      
      if (reponsesRes.ok) {
        reponses.value = await reponsesRes.json();
      }

      lastFetch.value = Date.now();
    } catch (error: any) {
      console.error("Erreur fetchBenevolatsData:", error);
    } finally {
      isLoading.value = false;
      isFetching = false;
    }
  };

  const clearData = () => {
    benevolats.value = [];
    reponses.value = [];
    userVotedIds.value = [];
    lastFetch.value = null;
  };

  return {
    benevolats,
    reponses,
    userVotedIds,
    isLoading,
    lastFetch,
    getResponseCount,
    hasUserVoted,
    markAsVoted,
    fetchBenevolatsData,
    clearData
  };
});
