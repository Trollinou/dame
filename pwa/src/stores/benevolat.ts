import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useAuthStore } from './auth';
import { safeFetch } from '@/utils/safeFetch';
import { useQuery, useQueryClient } from '@tanstack/vue-query';

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
  const authStore = useAuthStore();
  const queryClient = useQueryClient();
  const userVotedIds = ref<number[]>([]);

  // 1. Liste publique des appels à bénévolat (Clé public)
  const { data: rawBenevolats, isLoading: isBenevolatsLoading } = useQuery<Benevolat[]>({
    queryKey: ['benevolat', 'list', 'public'],
    queryFn: async () => {
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const response = await safeFetch(`${apiUrl}/wp/v2/benevolats?context=view&per_page=100`, {}, 4000);
      if (!response.ok) throw new Error("Erreur chargement bénévolats");
      return response.json();
    }
  });

  const benevolats = computed(() => rawBenevolats.value || []);

  // 2. Réponses utilisateur (Clé privée, isolée par identité pour éviter les fuites)
  const { data: rawReponses, isLoading: isReponsesLoading } = useQuery<BenevolatReponse[]>({
    queryKey: ['benevolat', 'user-vote', authStore.selectedIdentity?.member_id || 'anonymous'],
    queryFn: async () => {
      const token = localStorage.getItem('dame_jwt_token');
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (token) headers['Authorization'] = `Bearer ${token}`;

      const response = await safeFetch(`${apiUrl}/wp/v2/benevolat-reponses?context=view&per_page=100`, { headers }, 4000);
      
      if (response.status === 401 && token) {
        authStore.logout();
        throw new Error("Session expirée");
      }
      if (!response.ok) throw new Error("Erreur chargement réponses");
      return response.json();
    },
    enabled: computed(() => authStore.isAuthenticated)
  });

  const reponses = computed(() => rawReponses.value || []);

  const isLoading = computed(() => isBenevolatsLoading.value || (authStore.isAuthenticated && isReponsesLoading.value));

  const getResponseCount = (benevolatId: number): number => {
    if (!reponses.value) return 0;
    return reponses.value.filter(r => r.benevolat_id === benevolatId).length;
  };

  const hasUserVoted = (benevolatId: number): boolean => {
    if (userVotedIds.value.includes(benevolatId)) return true;

    const identity = authStore.selectedIdentity;
    if (!identity || !reponses.value) return false;

    return reponses.value.some(r => {
      if (r.benevolat_id !== benevolatId) return false;
      
      const rMemberId = Number(r.meta?._dame_member_id);
      const iMemberId = Number(identity.member_id);
      
      if (iMemberId > 0 && rMemberId === iMemberId) {
        return true;
      }
      
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
    if (force) {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['benevolat', 'list'] }),
        queryClient.invalidateQueries({ queryKey: ['benevolat', 'user-vote'] })
      ]);
    }
  };

  const clearData = () => {
    userVotedIds.value = [];
  };

  return {
    benevolats,
    reponses,
    userVotedIds,
    isLoading,
    getResponseCount,
    hasUserVoted,
    markAsVoted,
    fetchBenevolatsData,
    clearData
  };
});
