import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';
import { useAuthStore } from './auth';

export interface Message {
  id: number;
  date: string;
  title: {
    rendered: string;
    raw: string;
  };
  content: {
    rendered: string;
  };
  report?: {
    stats: {
      sent: number;
      opened: number;
      rate: number;
    };
    recipients: Array<{
      name: string;
      email: string;
      sent_at: string | null;
      opened_at: string | null;
    }>;
  };
}

export const useMessageStore = defineStore('messages', () => {
  const messages = ref<Message[]>([]);
  const isLoading = ref(false);
  let isFetching = false;
  const lastFetch = ref<number | null>(null);

  /**
   * Action: Récupère les messages (Silent Refresh)
   */
  const fetchMessages = async (force = false) => {
    if (isFetching) return;

    const now = Date.now();
    if (!force && messages.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    isFetching = true;
    if (messages.value.length === 0) {
      isLoading.value = true;
    }

    try {
      const token = localStorage.getItem('dame_jwt_token');
      if (!token) {
        router.push('/login');
        return;
      }

      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/wp/v2/messages?context=edit&per_page=100`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.status === 401) throw new Error("Session expirée");
      if (!response.ok) throw new Error("Erreur serveur");

      const data: Message[] = await response.json();
      
      // Tri par date décroissante
      data.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());

      messages.value = data;
      lastFetch.value = Date.now();
    } catch (error: any) {
      console.error("Erreur fetchMessages:", error);
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
    messages.value = [];
    lastFetch.value = null;
  };

  return {
    messages,
    isLoading,
    lastFetch,
    fetchMessages,
    clearData
  };
});
