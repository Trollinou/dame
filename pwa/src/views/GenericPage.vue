<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/tournoi"></ion-back-button>
        </ion-buttons>
        <ion-title v-if="page" v-safe-html="page.title.rendered"></ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div v-if="isLoading" class="ion-text-center ion-padding">
        <ion-spinner name="crescent"></ion-spinner>
      </div>

      <!-- Wrapper respectant la Dynamic Island sans ajouter de marges excessives -->
      <div v-else-if="page" class="safe-area-wrapper">
        <h1 v-safe-html="page.title.rendered"></h1>

        <!-- Contenu de la page avec boutons injectés et interception des liens -->
        <div 
          class="content" 
          v-safe-html="processedContent.cleanHtml" 
          @click="handleInternalLinks"
        ></div>
      </div>

      <div v-else class="ion-text-center ion-padding offline-container">
        <div v-if="error">
          <ion-icon :icon="cloudOfflineOutline" size="large" color="medium"></ion-icon>
          <p class="ion-margin-top">{{ error }}</p>
          <ion-button fill="solid" class="ion-margin-top" @click="fetchPage">Réessayer</ion-button>
        </div>
        <p v-else>Page introuvable.</p>
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
  IonButtons,
  IonBackButton,
  IonSpinner,
  IonButton,
  IonIcon
} from '@ionic/vue';
import { cloudOfflineOutline } from 'ionicons/icons';
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useInternalLinks } from '@/composables/useInternalLinks';
import { useTournamentStore } from '@/stores/tournament';

const route = useRoute();
const tournamentStore = useTournamentStore();
const page = ref<any>(null);
const isLoading = ref(true);
const error = ref<string | null>(null);
const { handleInternalLinks } = useInternalLinks();

/**
 * Analyse le contenu pour remplacer les shortcodes HelloAsso par des boutons
 */
const processedContent = computed(() => {
  const rawHtml = page.value?.content?.rendered || '';
  
  // Regex robuste globale pour détecter [helloasso campaign="URL"]
  const regex = /\[helloasso\s+campaign=(?:&nbsp;|\s)*[»"']*(https?:\/\/[^&"'\s»\]]+)(?:&nbsp;|\s)*[»"']*[^\]]*\]/gi;

  // Remplacement de chaque shortcode par un bouton Ionic injecté
  const cleanHtml = rawHtml.replace(regex, (match: string, url: string) => {
    return `<ion-button expand="block" class="ion-margin-top ion-margin-bottom" href="${url}" target="_blank">S'inscrire à l'événement</ion-button>`;
  });

  return { cleanHtml };
});

const fetchPage = async () => {
  isLoading.value = true;
  page.value = null;
  error.value = null;
  
  const idOrSlug = route.params.id;
  const isId = /^\d+$/.test(idOrSlug as string);
  
  // Si c'est un ID numérique, on peut tenter d'utiliser le TournamentStore
  if (isId) {
    const pageId = Number(idOrSlug);
    
    // On tente de récupérer via le store (qui gère le cache persistant)
    const result = await tournamentStore.fetchPage(pageId);
    if (result) {
      page.value = result;
      isLoading.value = false;
      // Si on est hors ligne, on s'arrête là car on a affiché le cache
      if (!navigator.onLine) return;
    }
  }

  // Si on n'a rien trouvé dans le store ou si on veut tenter un refresh
  try {
    const apiUrl = import.meta.env.VITE_API_BASE_URL;
    let url = `${apiUrl}/wp/v2/pages/`;
    
    if (isId) {
      url += idOrSlug;
    } else {
      url += `?slug=${idOrSlug}`;
    }

    const response = await fetch(url);
    if (response.ok) {
      const data = await response.json();
      page.value = isId ? data : data[0];
      if (!page.value) {
        error.value = "Page introuvable.";
      }
    } else if (!page.value) {
      error.value = "Impossible de charger la page.";
    }
  } catch (err) {
    console.error(err);
    if (!page.value) {
      if (!navigator.onLine) {
        error.value = "Vous êtes hors-ligne. Cette page nécessite une connexion internet pour être affichée.";
      } else {
        error.value = "Une erreur réseau est survenue.";
      }
    }
  } finally {
    isLoading.value = false;
  }
};

// Recharger si le paramètre ID/Slug change
watch(() => route.params.id, (newId) => {
  if (newId && route.name === 'GenericPage') {
    fetchPage();
  }
});

onMounted(fetchPage);
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

h1 { font-size: 1.5rem; font-weight: bold; margin-bottom: 20px; }
.offline-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-top: 50px;
}
.content :deep(img) { max-width: 100%; height: auto; }
.content :deep(table) { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.content :deep(th), .content :deep(td) { border: 1px solid var(--ion-color-light); padding: 8px; text-align: left; }
.content :deep(p) { margin-bottom: 12px; line-height: 1.5; }
</style>
