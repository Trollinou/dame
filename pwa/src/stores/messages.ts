import { defineStore } from 'pinia';
import { ref } from 'vue';
import router from '../router';

export interface Message {
  id: number;
  date: string;
  title: {
    rendered: string;
  };
  content: {
    rendered: string;
  };
}

export const useMessageStore = defineStore('messages', () => {
  const messages = ref<Message[]>([]);
  const isLoading = ref(false);
  const lastFetch = ref<number | null>(null);

  /**
   * Action: Récupère les messages (Silent Refresh)
   */
  const fetchMessages = async (force = false) => {
    const now = Date.now();
    if (!force && messages.value.length > 0 && lastFetch.value && (now - lastFetch.value < 5 * 60 * 1000)) {
      return;
    }

    if (messages.value.length === 0) {
      isLoading.value = true;
    }

    const token = localStorage.getItem('dame_jwt_token');
    if (!token) {
      router.push('/login');
      return;
    }

    try {
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/messages?context=edit&per_page=100`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.status === 401) throw new Error("Session expirée");
      if (!response.ok) throw new Error("Erreur serveur");

      const data: Message[] = await response.json();
      
      // Tri par date décroissante (plus récents en premier)
      data.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());

      messages.value = data;
      lastFetch.value = Date.now();
    } catch (error: any) {
      console.error("Erreur fetchMessages:", error);
      if (error.message === "Session expirée") {
        localStorage.removeItem('dame_jwt_token');
        router.push('/login');
      }
    } finally {
      isLoading.value = false;
    }
  };

  return {
    messages,
    isLoading,
    fetchMessages
  };
});
