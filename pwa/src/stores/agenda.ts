import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';
import { useAuthStore } from './auth';

export interface AgendaEvent {
  id: number;
  title: {
    rendered: string;
    raw: string;
  };
  _dame_agenda_description_html?: string;
  meta: {
    _dame_start_date: string;
    _dame_end_date: string;
    _dame_start_time: string;
    _dame_end_time: string;
    _dame_all_day: number;
    _dame_competition_type?: string;
    _dame_level?: string;
    _dame_location_name?: string;
    _dame_address?: string;
    _dame_postal_code?: string;
    _dame_city?: string;
    _dame_agenda_description?: string;
  };
}

export const useAgendaStore = defineStore('agenda', () => {
  const events = ref<AgendaEvent[]>([]);
  const isLoading = ref(false);
  let isFetching = false;
  const lastFetch = ref<number | null>(null);

  const fetchAgenda = async (force = false) => {
    // 1. Verrouillage pour éviter les appels multiples
    if (isFetching) return;

    const now = Date.now();
    // 2. Cache de 5 minutes
    if (!force && events.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    isFetching = true;
    
    // 3. Spinner uniquement si on n'a aucune donnée
    if (events.value.length === 0) {
      isLoading.value = true;
    }

    try {
      const token = localStorage.getItem('dame_jwt_token');
      if (!token) {
        router.push('/login');
        return;
      }

      const baseUrl = `${import.meta.env.VITE_API_BASE_URL}/wp/v2/agenda?per_page=100&context=edit`;
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
        useAuthStore().logout();
      }
    } finally {
      // 4. Libération systématique du verrou et du spinner
      isLoading.value = false;
      isFetching = false;
    }
  };

  /**
   * Réinitialise les données du store (ex: déconnexion)
   */
  const clearData = () => {
    events.value = [];
    lastFetch.value = null;
  };

  return {
    events,
    isLoading,
    lastFetch,
    fetchAgenda,
    clearData
  };
});
