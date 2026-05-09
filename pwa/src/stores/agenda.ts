import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';

export interface AgendaEvent {
  id: number;
  title: {
    raw: string;
  };
  meta: {
    _dame_start_date: string;
    _dame_end_date: string;
    _dame_start_time: string;
    _dame_end_time: string;
    _dame_all_day: number;
  };
}

export const useAgendaStore = defineStore('agenda', () => {
  const events = ref<AgendaEvent[]>([]);
  const isLoading = ref(false);
  const lastFetch = ref<number | null>(null);

  const fetchAgenda = async (force = false) => {
    const now = Date.now();
    // Silent refresh: si on a déjà des données et que ça date de moins de 5 min
    if (!force && events.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    // On ne montre le spinner que si la liste est vide
    if (events.value.length === 0) {
      isLoading.value = true;
    }

    const token = localStorage.getItem('dame_jwt_token');
    if (!token) {
      router.push('/login');
      return;
    }

    try {
      const baseUrl = `${import.meta.env.VITE_API_BASE_URL}/agenda?per_page=100&context=edit`;
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
      let allEvents: AgendaEvent[] = await response.json();

      if (totalPages > 1) {
        const pagePromises = [];
        for (let i = 2; i <= totalPages; i++) {
          pagePromises.push(
            fetch(`${baseUrl}&page=${i}`, fetchOptions).then(res => res.json())
          );
        }
        const results = await Promise.all(pagePromises);
        results.forEach((pageData: AgendaEvent[]) => {
          allEvents = allEvents.concat(pageData);
        });
      }

      // Tri chronologique sécurisé
      allEvents.sort((a, b) => {
        const startA = `${a.meta?._dame_start_date || '9999-12-31'} ${a.meta?._dame_start_time || '00:00'}`;
        const startB = `${b.meta?._dame_start_date || '9999-12-31'} ${b.meta?._dame_start_time || '00:00'}`;
        return startA.localeCompare(startB);
      });

      events.value = allEvents;
      lastFetch.value = Date.now();
    } catch (error: any) {
      console.error("Erreur fetchAgenda:", error);
      if (error.message === "Session expirée") {
        localStorage.removeItem('dame_jwt_token');
        router.push('/login');
      }
    } finally {
      isLoading.value = false;
    }
  };

  return {
    events,
    isLoading,
    lastFetch,
    fetchAgenda
  };
});
