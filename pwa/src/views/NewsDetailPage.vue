<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/news"></ion-back-button>
        </ion-buttons>
        <ion-title v-if="post" v-safe-html="post.title.rendered"></ion-title>
        <ion-title v-else>Actualité</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div v-if="isLoading" class="ion-text-center ion-padding">
        <ion-spinner name="crescent"></ion-spinner>
      </div>

      <!-- Wrapper respectant la Dynamic Island sans ajouter de marges excessives -->
      <div v-else-if="post" class="safe-area-wrapper">
        <img v-if="featuredImage" :src="featuredImage" class="detail-image" />
        <h1 v-safe-html="post.title.rendered"></h1>
        <p class="date">{{ formatDate(post.date) }}</p>
        
        <div 
          class="content" 
          v-safe-html="processedContent.cleanHtml" 
          @click="handleInternalLinks"
        ></div>
      </div>

      <div v-else class="ion-text-center ion-padding">
        <p>Actualité introuvable.</p>
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

const processedContent = computed(() => {
  let cleanHtml = post.value?.content?.rendered || '';
  
  const helloAssoRegex = /\[helloasso\s+campaign=(?:&nbsp;|\s)*[»"']*(https?:\/\/[^&"'\s»\]]+)(?:&nbsp;|\s)*[»"']*[^\]]*\]/gi;
  cleanHtml = cleanHtml.replace(helloAssoRegex, (match: string, url: string) => {
    return `<ion-button expand="block" class="ion-margin-top ion-margin-bottom" href="${url}" target="_blank">S'inscrire à l'événement</ion-button>`;
  });

  const surveyRegex = /<form[^>]+id="dame-benevolat-form-(\d+)"[^>]*>[\s\S]*?<\/form>/gi;
  cleanHtml = cleanHtml.replace(surveyRegex, (match: string, surveyId: string) => {
    return `
      <div class="survey-action-container ion-padding ion-margin-vertical">
        <h3 style="margin-top: 0;">Bénévolat disponible</h3>
        <p style="font-size: 0.9em; opacity: 0.8;">Proposez votre aide directement depuis l'application.</p>
        <ion-button expand="block" color="secondary" class="internal-benevolat-btn" data-path="/tabs/benevolat/participation/${surveyId}">
          Proposer mon aide
        </ion-button>
      </div>
    `;
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
/* Ajoute uniquement l'inset de sécurité sans cumuler les marges ion-padding */
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

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

.content :deep(.survey-action-container) {
  background: var(--ion-color-light);
  border-radius: 12px;
  border: 1px solid var(--ion-color-light-shade);
  text-align: center;
}
</style>
