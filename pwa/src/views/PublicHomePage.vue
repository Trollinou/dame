<template>
  <ion-page>
    <!-- Header Global avec Authentification -->
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>
          <div style="display: flex; align-items: center;">
            <img src="/assets/icon/queen.svg" style="height: 20px; margin-right: 8px;" alt="Logo" />
            <span style="font-weight: 800; letter-spacing: 0.5px;">DAME</span>
          </div>
        </ion-title>

        <ion-buttons slot="end">
          <!-- Bouton Connexion -->
          <ion-button v-if="!authStore.isAuthenticated" router-link="/login" color="primary" fill="clear">
            <ion-icon slot="icon-only" :icon="personCircleOutline"></ion-icon>
          </ion-button>
          
          <ion-button v-else @click="handleLogout" color="medium" fill="clear">
            <ion-icon slot="icon-only" :icon="logOutOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Dame</ion-title>
        </ion-toolbar>
      </ion-header>

      <!-- Section Dernières Nouvelles -->
      <ion-list lines="full">
        <ion-list-header>
          <ion-label color="primary">Dernières Nouvelles</ion-label>
          <ion-button fill="clear" router-link="/tabs/news">Actualités</ion-button>
        </ion-list-header>

        <div v-if="isLoadingNews" class="ion-text-center ion-padding">
          <ion-spinner name="dots"></ion-spinner>
        </div>
        
        <ion-item v-for="post in latestPosts" :key="post.id" button @click="goToNews(post.id)">
          <ion-icon slot="start" :icon="newspaperOutline" color="primary"></ion-icon>
          <ion-thumbnail slot="start" v-if="getFeaturedImage(post)">
            <img :src="getFeaturedImage(post) || undefined" alt="Thumbnail" style="border-radius: 4px;" />
          </ion-thumbnail>
          <ion-label>
            <h3 v-html="post.title.rendered" class="ion-text-wrap" style="font-weight: 600;"></h3>
            <p>{{ formatDate(post.date) }}</p>
          </ion-label>
        </ion-item>

        <ion-item v-if="!isLoadingNews && latestPosts.length === 0" lines="none">
          <ion-label class="ion-text-center">Aucune actualité</ion-label>
        </ion-item>
      </ion-list>

      <!-- Section Prochains Événements -->
      <ion-list lines="full" class="ion-margin-top">
        <ion-list-header>
          <ion-label color="primary">Prochains Événements</ion-label>
          <ion-button fill="clear" router-link="/tabs/agenda">Agenda</ion-button>
        </ion-list-header>

        <div v-if="agendaStore.isLoading" class="ion-text-center ion-padding">
          <ion-spinner name="dots"></ion-spinner>
        </div>

        <ion-item v-for="event in upcomingEvents" :key="event.id" button @click="goToAgenda(event.id)">
          <ion-icon slot="start" :icon="calendarOutline" color="primary"></ion-icon>
          <ion-label>
            <h3 v-html="event.title.rendered" class="ion-text-wrap" style="font-weight: 600;"></h3>
            <p>{{ formatEventDate(event) }}</p>
          </ion-label>
          <ion-badge v-if="isToday(event)" color="warning" slot="end">Actuellement</ion-badge>
        </ion-item>

        <ion-item v-if="!agendaStore.isLoading && upcomingEvents.length === 0" lines="none">
          <ion-label class="ion-text-center">Aucun événement à venir</ion-label>
        </ion-item>
      </ion-list>

      <!-- Section Sondages en cours -->
      <ion-list lines="full" class="ion-margin-top ion-margin-bottom">
        <ion-list-header>
          <ion-label color="primary">Sondages en cours</ion-label>
          <!-- <ion-button fill="clear" router-link="/tabs/admin/survey">Sondages</ion-button> -->
        </ion-list-header>

        <div v-if="sondageStore.isLoading" class="ion-text-center ion-padding">
          <ion-spinner name="dots"></ion-spinner>
        </div>

        <ion-item v-for="sondage in latestSondages" :key="sondage.id">
          <ion-icon slot="start" :icon="statsChartOutline" color="secondary"></ion-icon>
          <ion-label>
            <h3 v-html="sondage.title.raw" class="ion-text-wrap" style="font-weight: 600;"></h3>
            <p>Se termine prochainement</p>
          </ion-label>
        </ion-item>

        <ion-item v-if="!sondageStore.isLoading && latestSondages.length === 0" lines="none">
          <ion-label class="ion-text-center">Aucun sondage actif</ion-label>
        </ion-item>
      </ion-list>

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
  IonList,
  IonListHeader,
  IonLabel,
  IonItem,
  IonThumbnail,
  IonIcon,
  IonButton,
  IonSpinner,
  IonBadge,
  onIonViewWillEnter
} from '@ionic/vue';
import { calendarOutline, statsChartOutline, newspaperOutline, personCircleOutline, logOutOutline } from 'ionicons/icons';
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { useAgendaStore, type AgendaEvent } from '@/stores/agenda';
import { useSondageStore } from '@/stores/sondages';

const authStore = useAuthStore();
const router = useRouter();
const agendaStore = useAgendaStore();
const sondageStore = useSondageStore();

const latestPosts = ref<any[]>([]);
const isLoadingNews = ref(false);

const todayStr = new Date().toISOString().split('T')[0];

/**
 * Gère le changement de mode Admin via le toggle
 */
const onToggleAdminMode = () => {
  if (authStore.adminMode) {
    router.push('/tabs/admin/dashboard');
  } else {
    router.push('/tabs/home');
  }
};

const toggleMode = () => {
  // On inverse le mode
  authStore.adminMode = !authStore.adminMode;
  
  // Redirection automatique
  if (authStore.adminMode) {
    router.push('/tabs/admin/dashboard');
  } else {
    router.push('/tabs/home');
  }
};

/**
 * Gère la déconnexion
 */
const handleLogout = async () => {
  await authStore.logout();
};

/**
 * Récupère les 3 dernières actualités
 */
const fetchLatestNews = async () => {
  isLoadingNews.value = true;
  try {
    const apiUrl = import.meta.env.VITE_API_BASE_URL;
    const response = await fetch(`${apiUrl}/wp/v2/posts?_embed&per_page=3`);
    if (response.ok) {
      latestPosts.value = await response.json();
    }
  } catch (err) {
    console.error("Erreur news dashboard:", err);
  } finally {
    isLoadingNews.value = false;
  }
};

/**
 * Filtre les 3 prochains événements (non passés)
 */
const upcomingEvents = computed(() => {
  const today = new Date().toISOString().split('T')[0];
  return agendaStore.events
    .filter(e => (e.meta?._dame_start_date || '') >= today)
    .slice(0, 3);
});

/**
 * Prend les 2 sondages les plus récents
 */
const latestSondages = computed(() => {
  return sondageStore.sondages.slice(0, 2);
});

const getFeaturedImage = (post: any): string | null => {
  return post._embedded?.['wp:featuredmedia']?.[0]?.source_url || null;
};

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
};

const formatPart = (dateString: string): string => {
  if (!dateString) return '';
  return dateString.split('-').reverse().join('/');
};

const formatEventDate = (event: AgendaEvent): string => {
  const meta = event.meta;
  const startDate = meta?._dame_start_date;
  const endDate = meta?._dame_end_date;

  if (!startDate) return 'Date non définie';

  if (endDate && startDate !== endDate) {
    return `Du ${formatPart(startDate)} au ${formatPart(endDate)}`;
  }

  const startTime = meta?._dame_start_time;
  const endTime = meta?._dame_end_time;
  const isAllDay = meta?._dame_all_day === 1;

  if (startTime && endTime && !isAllDay) {
    return `Le ${formatPart(startDate)} de ${startTime} à ${endTime}`;
  }

  return `Le ${formatPart(startDate)} (Toute la journée)`;
};

const isToday = (event: AgendaEvent): boolean => {
  const startDate = event.meta?._dame_start_date;
  const endDate = event.meta?._dame_end_date;

  if (!startDate) return false;

  // Cas 1 : Événement sur une période (plusieurs jours)
  if (endDate && startDate !== endDate) {
    return todayStr >= startDate && todayStr <= endDate;
  }

  // Cas 2 : Événement sur une seule journée
  return startDate === todayStr;
};

const goToNews = (id: number) => router.push(`/tabs/news/${id}`);
const goToAgenda = (id: number) => router.push(`/tabs/agenda/${id}`);

onIonViewWillEnter(() => {
  fetchLatestNews();
  agendaStore.fetchAgenda();
  sondageStore.fetchSondagesData();
});
</script>

<style scoped>
ion-list-header {
  --background: transparent;
  font-size: 1.1em;
  font-weight: bold;
}

h3 {
  margin-top: 0;
  margin-bottom: 4px;
}

p {
  color: var(--ion-color-medium);
  font-size: 0.85em;
}

ion-thumbnail {
  --size: 56px;
}
</style>
