import { defineStore } from 'pinia';
import { ref } from 'vue';
import { safeFetch } from '@/utils/safeFetch';

export interface MenuItem {
  id: number;
  title: string;
  object_id: number; // ID de la page WordPress
  parent: string | number;
  modified: string; // Date de modification du menu/lien
}

export interface CachedPage {
  id: number;
  title: { rendered: string };
  content: { rendered: string };
  modified: string;
}

export const useTournamentStore = defineStore('tournament', () => {
  const menuItems = ref<MenuItem[]>([]);
  const cachedPages = ref<Record<number, CachedPage>>({});
  const isLoading = ref(false);
  const lastFetch = ref<number | null>(null);

  const fetchMenu = async (force = false) => {
    const now = Date.now();
    if (!force && menuItems.value.length > 0 && lastFetch.value && (now - lastFetch.value < 60 * 60 * 1000)) {
      return;
    }

    if (!navigator.onLine && menuItems.value.length > 0) {
      return;
    }

    isLoading.value = true;
    try {
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const response = await safeFetch(`${apiUrl}/dame/v1/pwa-menu`, {}, 4000);
      
      if (!response.ok) throw new Error("Impossible de charger le menu.");
      
      const newData: MenuItem[] = await response.json();
      
      // Comparaison pour mise à jour intelligente
      const hasChanged = JSON.stringify(newData.map(i => ({ id: i.id, mod: i.modified }))) !== 
                         JSON.stringify(menuItems.value.map(i => ({ id: i.id, mod: i.modified })));

      if (hasChanged || menuItems.value.length === 0) {
        menuItems.value = newData;
        
        // PRÉ-CHARGEMENT PROACTIF : On charge le contenu de chaque page de tournoi
        // pour qu'ils soient disponibles hors-ligne sans même avoir à cliquer dessus.
        if (navigator.onLine) {
          newData.forEach(item => {
            if (item.object_id) {
              fetchPage(item.object_id);
            }
          });
        }
      }

      lastFetch.value = Date.now();
    } catch (err: any) {
      console.error("Erreur fetchMenu:", err);
      if (menuItems.value.length === 0) throw err;
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Récupère une page de détail (depuis le cache ou le réseau)
   */
  const fetchPage = async (pageId: number) => {
    const cached = cachedPages.value[pageId];
    
    // Si on est hors ligne, on renvoie le cache (même s'il est vide, la vue gérera)
    if (!navigator.onLine) return cached;

    // Si on a un cache, on peut le renvoyer tout de suite pour l'affichage rapide
    // mais on lancera quand même un fetch si on est en ligne pour vérifier les mises à jour.
    
    try {
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const response = await safeFetch(`${apiUrl}/wp/v2/pages/${pageId}`, {}, 4000);
      if (response.ok) {
        const pageData: CachedPage = await response.json();
        
        // On ne met à jour le cache que si la date de modification a changé
        if (!cached || cached.modified !== pageData.modified) {
          cachedPages.value[pageId] = pageData;
        }
        return pageData;
      }
    } catch (err) {
      console.error(`Erreur fetchPage ${pageId}:`, err);
    }

    return cached;
  };

  const clearData = () => {
    menuItems.value = [];
    cachedPages.value = {};
    lastFetch.value = null;
  };

  return {
    menuItems,
    cachedPages,
    isLoading,
    lastFetch,
    fetchMenu,
    fetchPage,
    clearData
  };
}, {
  persist: true
});
