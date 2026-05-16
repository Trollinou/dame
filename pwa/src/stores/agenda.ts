import { defineStore } from 'pinia';
import { ref } from 'vue';

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

  /**
   * Récupère un lot d'événements depuis le serveur
   * @param direction 'upcoming' (futur) ou 'past' (passé)
   * @param referenceDate Date charnière (ISO YYYY-MM-DD)
   * @param page Numéro de page
   */
  const fetchBatch = async (direction: 'upcoming' | 'past', referenceDate: string, page: number) => {
    try {
      const token = localStorage.getItem('dame_jwt_token');
      const context = token ? 'edit' : 'view';
      const perPage = 20;
      
      // Paramètres de tri et de filtre
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
        if (response.status === 400) return []; // Fin de pagination
        throw new Error("Erreur serveur");
      }

      const data: AgendaEvent[] = await response.json();
      
      // Mise à jour des drapeaux de fin
      if (direction === 'upcoming' && data.length < perPage) hasMoreUpcoming.value = false;
      if (direction === 'past' && data.length < perPage) hasMorePast.value = false;

      return data;
    } catch (error) {
      console.error(`Erreur fetchBatch ${direction}:`, error);
      return [];
    }
  };

  /**
   * Méthode de compatibilité pour le reste de l'app (ex: HomePage)
   * Charge les 20 prochains événements
   */
  const fetchAgenda = async () => {
    isLoading.value = true;
    try {
      const today = new Date().toISOString().split('T')[0];
      const data = await fetchBatch('upcoming', today, 1);
      events.value = data;
    } finally {
      isLoading.value = false;
    }
  };

  const clearData = () => {
    events.value = [];
    hasMoreUpcoming.value = true;
    hasMorePast.value = true;
  };

  return {
    events,
    isLoading,
    hasMoreUpcoming,
    hasMorePast,
    fetchBatch,
    fetchAgenda,
    clearData
  };
});
