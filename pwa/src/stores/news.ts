import { defineStore } from 'pinia';
import { ref } from 'vue';
import { safeFetch } from '@/utils/safeFetch';

export interface Post {
  id: number;
  date: string;
  modified: string;
  title: {
    rendered: string;
  };
  content: {
    rendered: string;
  };
  excerpt: {
    rendered: string;
  };
  _embedded?: {
    'wp:featuredmedia'?: Array<{
      source_url: string;
    }>;
  };
}

export const useNewsStore = defineStore('news', () => {
  const posts = ref<Post[]>([]);
  const isLoading = ref(false);
  const lastFetch = ref<number | null>(null);

  const fetchPosts = async (force = false) => {
    const now = Date.now();
    if (!force && posts.value.length > 0 && lastFetch.value && (now - lastFetch.value < 30 * 60 * 1000)) {
      return;
    }

    if (!navigator.onLine && posts.value.length > 0) {
      return;
    }

    isLoading.value = true;
    try {
      const apiUrl = import.meta.env.VITE_API_BASE_URL;
      const response = await safeFetch(`${apiUrl}/wp/v2/posts?_embed&per_page=20`, {}, 4000);
      
      if (!response.ok) throw new Error("Impossible de charger les actualités.");
      
      const newData: Post[] = await response.json();
      
      // On compare avec l'existant pour ne mettre à jour que si nécessaire
      const hasChanged = JSON.stringify(newData.map(p => ({ id: p.id, mod: p.modified }))) !== 
                         JSON.stringify(posts.value.slice(0, 20).map(p => ({ id: p.id, mod: p.modified })));

      if (hasChanged || posts.value.length === 0) {
        posts.value = newData;
      }
      
      lastFetch.value = Date.now();
    } catch (err: any) {
      console.error("Erreur fetchPosts:", err);
      if (posts.value.length === 0) {
        throw err;
      }
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Sauvegarde ou met à jour un article unique dans le store
   */
  const savePost = (post: Post) => {
    const index = posts.value.findIndex(p => p.id === post.id);
    if (index !== -1) {
      posts.value[index] = post;
    } else {
      posts.value.unshift(post);
      // On garde une limite raisonnable pour le cache
      if (posts.value.length > 100) {
        posts.value.pop();
      }
    }
  };

  const getPostById = (id: number): Post | undefined => {
    return posts.value.find(p => p.id === id);
  };

  const clearData = () => {
    posts.value = [];
    lastFetch.value = null;
  };

  return {
    posts,
    isLoading,
    lastFetch,
    fetchPosts,
    savePost,
    getPostById,
    clearData
  };
}, {
  persist: true
});
