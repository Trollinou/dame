<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Actualités</ion-title>
      </ion-toolbar>
      
      <!-- Barre de recherche -->
      <ion-toolbar>
        <ion-searchbar 
          v-model="searchQuery" 
          placeholder="Rechercher..." 
          @ionInput="onSearchInput"
          :debounce="500"
        ></ion-searchbar>
      </ion-toolbar>

      <!-- Filtre par catégorie -->
      <ion-toolbar>
        <ion-item lines="none">
          <ion-select 
            v-model="selectedCategory" 
            placeholder="Toutes les catégories" 
            interface="action-sheet"
            label="Catégorie"
            label-placement="start"
            @ionChange="onCategoryChange"
          >
            <ion-select-option :value="null">Toutes les catégories</ion-select-option>
            <ion-select-option 
              v-for="cat in categories" 
              :key="cat.id" 
              :value="cat.id"
            >
              {{ cat.name }}
            </ion-select-option>
          </ion-select>
        </ion-item>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">Actualités</ion-title>
          </ion-toolbar>
        </ion-header>
        <!-- État de chargement initial -->
        <div v-if="isLoading && posts.length === 0" class="ion-text-center ion-padding">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement des actualités...</p>
        </div>

        <!-- Erreur -->
        <div v-else-if="error" class="ion-text-center ion-padding">
          <p color="danger">{{ error }}</p>
          <ion-button fill="clear" @click="fetchPosts(true)">Réessayer</ion-button>
        </div>

        <!-- Liste des articles -->
        <div v-else>
          <ion-card 
            v-for="post in posts" 
            :key="post.id" 
            class="news-card ion-no-margin ion-margin-bottom" 
            button 
            @click="goToDetail(post.id)"
          >
            <!-- Image mise en avant -->
            <img 
              v-if="getFeaturedImage(post)" 
              :src="getFeaturedImage(post) || undefined" 
              :alt="post.title.rendered"
              class="featured-image"
            />
            
            <ion-card-header>
              <ion-card-subtitle>{{ formatDate(post.date) }}</ion-card-subtitle>
              <ion-card-title v-safe-html="post.title.rendered"></ion-card-title>
            </ion-card-header>

            <ion-card-content>
              <div v-safe-html="post.excerpt.rendered"></div>
            </ion-card-content>
          </ion-card>

          <!-- Aucun article -->
          <div v-if="posts.length === 0 && !isLoading" class="ion-text-center ion-padding">
            <p>Aucune actualité trouvée.</p>
          </div>
        </div>

        <!-- Infinite Scroll -->
        <ion-infinite-scroll 
          @ionInfinite="loadMore($event)" 
          :disabled="!hasMorePosts"
        >
          <ion-infinite-scroll-content 
            loading-spinner="dots" 
            loading-text="Chargement de plus d'articles..."
          >
          </ion-infinite-scroll-content>
        </ion-infinite-scroll>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent,
  IonSpinner,
  IonButton,
  IonInfiniteScroll,
  IonInfiniteScrollContent,
  IonSearchbar,
  IonSelect,
  IonSelectOption,
  IonItem,
  InfiniteScrollCustomEvent,
  onIonViewWillEnter
} from '@ionic/vue';
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';

interface WPPost {
  id: number;
  date: string;
  title: { rendered: string };
  excerpt: { rendered: string };
  _embedded?: {
    'wp:featuredmedia'?: Array<{
      source_url: string;
    }>;
  };
}

interface WPCategory {
  id: number;
  name: string;
  count: number;
}

const router = useRouter();
const posts = ref<WPPost[]>([]);
const categories = ref<WPCategory[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);

// Filtres et Pagination
const page = ref(1);
const hasMorePosts = ref(true);
const searchQuery = ref('');
const selectedCategory = ref<number | null>(null);

/**
 * Récupère les catégories WordPress
 */
const fetchCategories = async () => {
  try {
    const apiUrl = import.meta.env.VITE_API_BASE_URL;
    const response = await fetch(`${apiUrl}/wp/v2/categories?per_page=100&hide_empty=true`);
    if (response.ok) {
      categories.value = await response.json();
    }
  } catch (err) {
    console.error("Erreur lors du chargement des catégories:", err);
  }
};

/**
 * Récupère les articles WordPress via l'API REST
 */
const fetchPosts = async (reset = false) => {
  if (reset) {
    page.value = 1;
    posts.value = [];
    hasMorePosts.value = true;
  }

  isLoading.value = true;
  error.value = null;

  try {
    const apiUrl = import.meta.env.VITE_API_BASE_URL;
    let url = `${apiUrl}/wp/v2/posts?_embed&page=${page.value}&per_page=10`;

    if (searchQuery.value.trim()) {
      url += `&search=${encodeURIComponent(searchQuery.value.trim())}`;
    }

    if (selectedCategory.value) {
      url += `&categories=${selectedCategory.value}`;
    }

    const response = await fetch(url);

    if (!response.ok) {
      if (response.status === 400) {
        hasMorePosts.value = false;
        return;
      }
      throw new Error("Impossible de charger les actualités.");
    }

    const newPosts: WPPost[] = await response.json();
    
    if (newPosts.length < 10) {
      hasMorePosts.value = false;
    }

    posts.value = [...posts.value, ...newPosts];
  } catch (err: any) {
    error.value = err.message || "Une erreur est survenue.";
  } finally {
    isLoading.value = false;
  }
};

/**
 * Gère le défilement infini
 */
const loadMore = async (ev: InfiniteScrollCustomEvent) => {
  if (hasMorePosts.value) {
    page.value++;
    await fetchPosts();
  }
  ev.target.complete();
};

/**
 * Gère le changement de recherche (avec debounce intégré à ion-searchbar)
 */
const onSearchInput = () => {
  fetchPosts(true);
};

/**
 * Gère le changement de catégorie
 */
const onCategoryChange = () => {
  fetchPosts(true);
};

/**
 * Navigation vers le détail
 */
const goToDetail = (id: number) => {
  router.push(`/tabs/news/${id}`);
};

/**
 * Extrait l'URL de l'image mise en avant
 */
const getFeaturedImage = (post: WPPost): string | null => {
  const embedded = post._embedded?.['wp:featuredmedia'];
  if (embedded && embedded.length > 0) {
    return embedded[0].source_url;
  }
  return null;
};

/**
 * Formate la date WordPress
 */
const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
};

onMounted(() => {
  fetchCategories();
});

onIonViewWillEnter(() => {
  if (posts.value.length === 0) {
    fetchPosts();
  }
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.news-card {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.featured-image {
  width: 100%;
  height: 200px;
  object-fit: cover;
}

ion-card-title {
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1.2;
}

ion-card-subtitle {
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--ion-color-medium);
}

ion-item {
  --background: transparent;
}
</style>
