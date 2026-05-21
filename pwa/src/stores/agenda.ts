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
  
  // Etat de la pagination partagé
  const upcomingPage = ref(1);
  const pastPage = ref(1);

  /**
   * Récupère un lot d'événements depuis le serveur
   */
  const fetchBatch = async (direction: 'upcoming' | 'past', referenceDate: string, page: number) => {
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
        if (response.status === 400) return [];
        throw new Error("Erreur serveur");
      }

      const data: AgendaEvent[] = await response.json();
      
      if (direction === 'upcoming' && data.length < perPage) hasMoreUpcoming.value = false;
      if (direction === 'past' && data.length < perPage) hasMorePast.value = false;

      return data;
    } catch (error) {
      console.error(`Erreur fetchBatch ${direction}:`, error);
      return [];
    }
  };

  /**
   * Rafraîchit les données de base (utilisé par Home et Pull-to-refresh)
   */
  const fetchAgenda = async () => {
    isLoading.value = true;
    try {
      const today = new Date().toISOString().split('T')[0];
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
      hasMoreUpcoming.value = data.length >= 20;

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
    fetchBatch,
    fetchAgenda,
    clearData
  };
});
