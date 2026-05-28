import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';
import { useAuthStore } from './auth';
import { safeFetch } from '@/utils/safeFetch';

export interface Benevolat {
  id: number;
  modified: string;
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
  modified: string;
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

    return reponses.value.some(r => {
      if (r.benevolat_id !== benevolatId) return false;
      
      const rMemberId = Number(r.meta?._dame_member_id);
      const iMemberId = Number(identity.member_id);
      
      // 1. Vérification stricte par ID (les ID doivent être valides > 0)
      if (iMemberId > 0 && rMemberId === iMemberId) {
        return true;
      }
      
      // 2. Fallback par Nom (uniquement si ce n'est pas un profil générique)
      const rName = r.title?.rendered?.toLowerCase().trim();
      const iName = identity.name?.toLowerCase().trim();
      
      if (iName && rName && iName !== 'gestionnaire' && iName !== 'adhérent' && rName === iName) {
        return true;
      }
      
      return false;
    });
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

    // Protection proactive contre les erreurs réseau console
    if (!navigator.onLine && benevolats.value.length > 0) return;

    isFetching = true;
    if (benevolats.value.length === 0) {
      isLoading.value = true;
    }

    try {
      const token = localStorage.getItem('dame_jwt_token');
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      
      // Si on est hors ligne, on ne tente même pas le fetch
      if (!navigator.onLine) throw new Error("Offline");

      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (token) headers['Authorization'] = `Bearer ${token}`;

      // Routes Standard WordPress pour la liste avec interdiction de cache navigateur
      const [benevolatsRes, reponsesRes] = await Promise.all([
        safeFetch(`${apiUrl}/wp/v2/benevolats?context=view&per_page=100`, { headers, cache: 'no-store' }, 4000),
        safeFetch(`${apiUrl}/wp/v2/benevolat-reponses?context=view&per_page=100`, { headers, cache: 'no-store' }, 4000)
      ]);

      if (benevolatsRes.status === 401 && token) {
        useAuthStore().logout();
        return;
      }

      if (!benevolatsRes.ok) {
        if (benevolatsRes.status >= 500) {
          console.warn("Serveur Bénévolat indisponible (500+)");
        }
        throw new Error("Erreur chargement bénévolats");
      }

      benevolats.value = await benevolatsRes.json();
      
      if (reponsesRes.ok) {
        reponses.value = await reponsesRes.json();
      }

      lastFetch.value = Date.now();
    } catch (error: any) {
      if (error.message !== "Offline") {
        console.error("Erreur fetchBenevolatsData:", error);
      }
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
}, {
  persist: true
});
