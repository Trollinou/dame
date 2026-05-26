import { defineStore } from 'pinia';
import { ref } from 'vue';
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
  const hasMoreUpcoming = ref(true);
  const hasMorePast = ref(true);
  let isFetching = false;
  
  // Etat de la pagination partagé
  const upcomingPage = ref(1);
  const pastPage = ref(1);

  /**
   * Récupère un lot d'événements depuis le serveur
   */
  const fetchBatch = async (direction: 'upcoming' | 'past', referenceDate: string, page: number) => {
    if (isFetching) return [];
    isFetching = true;

    try {
      const token = localStorage.getItem('dame_jwt_token');
      const context = 'view';
      const perPage = 20;
      
      const order = direction === 'upcoming' ? 'asc' : 'desc';
      const dateParam = direction === 'upcoming' ? `after_date=${referenceDate}` : `before_date=${referenceDate}`;
      
      const baseUrl = `${import.meta.env.VITE_API_BASE_URL}/wp/v2/agenda`;
      const queryParams = [
        `per_page=${perPage}`,
        `page=${page}`,
        `context=${context}`,
        `orderby=meta_value`,
        `meta_key=_dame_start_date`,
        `order=${order}`,
        dateParam
      ].join('&');

      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (token) headers['Authorization'] = `Bearer ${token}`;

      const response = await fetch(`${baseUrl}?${queryParams}`, { method: 'GET', headers });

      if (!response.ok) {
        // Gestion de la session expirée
        if (response.status === 401) {
          useAuthStore().logout();
          return [];
        }

        // WordPress renvoie 400 quand on demande une page qui n'existe plus (fin de liste)
        if (response.status === 400) {
          if (direction === 'upcoming') hasMoreUpcoming.value = false;
          if (direction === 'past') hasMorePast.value = false;
          return [];
        }
        throw new Error("Erreur serveur");
      }

      // Lecture du nombre total de pages renvoyé par WordPress
      const totalPagesStr = response.headers.get('X-WP-TotalPages');
      const totalPages = totalPagesStr ? parseInt(totalPagesStr, 10) : 1;

      // Si on est arrivé à la dernière page, on coupe la pagination
      if (page >= totalPages) {
        if (direction === 'upcoming') hasMoreUpcoming.value = false;
        if (direction === 'past') hasMorePast.value = false;
      }

      const data: AgendaEvent[] = await response.json();
      
      // Fallback de sécurité : si on reçoit moins que perPage
      if (direction === 'upcoming' && data.length < perPage) hasMoreUpcoming.value = false;
      if (direction === 'past' && data.length < perPage) hasMorePast.value = false;

      return data;
    } catch (error) {
      console.error(`Erreur fetchBatch ${direction}:`, error);
      return [];
    } finally {
      isFetching = false;
    }
  };

  /**
   * Récupère la date du jour au format YYYY-MM-DD (Locale)
   */
  const getTodayLocal = () => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
  };

  /**
   * Rafraîchit les données de base (utilisé par Home et Pull-to-refresh)
   */
  const fetchAgenda = async () => {
    isLoading.value = true;
    try {
      const today = getTodayLocal();
      const data = await fetchBatch('upcoming', today, 1);
      
      // --- LOGIQUE DE FUSION INTELLIGENTE ---
      // On garde tous les événements passés déjà chargés en mémoire
      const pastEventsInCache = events.value.filter(e => {
        const refDate = e.meta?._dame_end_date || e.meta?._dame_start_date || '';
        return refDate < today;
      });

      // On remplace le bloc futur par les données fraîches
      // (On réinitialise la pagination future à 1 car on vient de recharger la page 1)
      const mergedEvents = [...pastEventsInCache, ...data];
      
      // Sécurité anti-doublons
      events.value = mergedEvents.filter((v, i, a) => a.findIndex(t => (t.id === v.id)) === i);
      
      upcomingPage.value = 1;

    } finally {
      isLoading.value = false;
    }
  };

  const clearData = () => {
    events.value = [];
    hasMoreUpcoming.value = true;
    hasMorePast.value = true;
    upcomingPage.value = 1;
    pastPage.value = 1;
  };

  return {
    events,
    isLoading,
    hasMoreUpcoming,
    hasMorePast,
    upcomingPage,
    pastPage,
    getTodayLocal,
    fetchBatch,
    fetchAgenda,
    clearData
  };
});
