<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/news"></ion-back-button>
        </ion-buttons>
        <ion-title v-if="post" v-html="post.title.rendered"></ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div v-if="isLoading" class="ion-text-center ion-padding">
        <ion-spinner name="crescent"></ion-spinner>
      </div>

      <div v-else-if="post" class="ion-padding">
        <img v-if="featuredImage" :src="featuredImage" class="detail-image" />
        <h1 v-html="post.title.rendered"></h1>
        <p class="date">{{ formatDate(post.date) }}</p>
        
        <!-- Contenu de l'article avec boutons injectés et interception des liens -->
        <div 
          class="content" 
          v-html="processedContent.cleanHtml" 
          @click="handleInternalLinks"
        ></div>
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
  IonButton
} from '@ionic/vue';
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useInternalLinks } from '@/composables/useInternalLinks';

const route = useRoute();
const post = ref<any>(null);
const isLoading = ref(true);
const { handleInternalLinks } = useInternalLinks();

const featuredImage = computed(() => {
  return post.value?._embedded?.['wp:featuredmedia']?.[0]?.source_url;
});

/**
 * Analyse le contenu pour remplacer les shortcodes HelloAsso par des boutons
 */
const processedContent = computed(() => {
  const rawHtml = post.value?.content?.rendered || '';
  
  // Regex robuste globale pour détecter [helloasso campaign="URL"]
  const regex = /\[helloasso\s+campaign=(?:&nbsp;|\s)*[»"']*(https?:\/\/[^&"'\s»\]]+)(?:&nbsp;|\s)*[»"']*[^\]]*\]/gi;

  // Remplacement de chaque shortcode par un bouton Ionic injecté
  const cleanHtml = rawHtml.replace(regex, (match: string, url: string) => {
    return `<ion-button expand="block" class="ion-margin-top ion-margin-bottom" href="${url}" target="_blank">S'inscrire à l'événement</ion-button>`;
  });

  return { cleanHtml };
});

const fetchPost = async () => {
  try {
    const apiUrl = import.meta.env.VITE_API_BASE_URL;
    const response = await fetch(`${apiUrl}/wp/v2/posts/${route.params.id}?_embed`);
    if (response.ok) {
      post.value = await response.json();
    }
  } catch (err) {
    console.error(err);
  } finally {
    isLoading.value = false;
  }
};

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
};

onMounted(fetchPost);
</script>

<style scoped>
.detail-image {
  width: 100%;
  height: auto;
  border-radius: 8px;
  margin-bottom: 16px;
}
h1 { font-size: 1.5rem; font-weight: bold; }
.date { color: var(--ion-color-medium); margin-bottom: 16px; }
.content :deep(img) { max-width: 100%; height: auto; }
.content :deep(p) { margin-bottom: 12px; line-height: 1.5; }
</style>
