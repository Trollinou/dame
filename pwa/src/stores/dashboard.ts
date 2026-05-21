import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';
import { useAuthStore } from './auth';

export interface Birthday {
  id: number;
  name: string;
  date: string;
  days_until: number;
  next_age: number;
}

export const useDashboardStore = defineStore('dashboard', () => {
  const birthdays = ref<Birthday[]>([]);
  const isLoading = ref(false);
  let isFetching = false;
  const lastFetch = ref<number | null>(null);

  /**
   * Récupère les prochains anniversaires (Silent Refresh)
   */
  const fetchBirthdays = async (force = false) => {
    if (isFetching) return;

    const now = Date.now();
    if (!force && birthdays.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    isFetching = true;
    if (birthdays.value.length === 0) {
      isLoading.value = true;
    }

    try {
      const token = localStorage.getItem('dame_jwt_token');
      if (!token) {
        router.push('/login');
        return;
      }

      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/dame/v1/birthdays/upcoming?limit=5`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.status === 401) throw new Error("Session expirée");
      if (!response.ok) throw new Error("Erreur serveur");

      birthdays.value = await response.json();
      lastFetch.value = Date.now();
    } catch (error: any) {
      console.error("Erreur fetchBirthdays:", error);
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
    birthdays.value = [];
    lastFetch.value = null;
  };

  return {
    birthdays,
    isLoading,
    lastFetch,
    fetchBirthdays,
    clearData
  };
});
