<template>
  <ion-page>
    <!-- Header Global avec Authentification -->
    <ion-header :translucent="true">
      <ion-toolbar>
        <!-- Logo à gauche -->
        <ion-buttons slot="start">
          <div style="display: flex; align-items: center; padding-left: 12px;">
            <img src="/assets/icon/queen.svg" style="height: 20px; margin-right: 8px;" alt="Logo" />
            <span style="font-weight: 800; letter-spacing: 0.5px; color: var(--ion-color-dark);">DAME</span>
          </div>
        </ion-buttons>

        <!-- Zone Identité et Actions à droite -->
        <ion-buttons slot="end">
          <!-- Identité sélectionnée (Cliquable seulement si ce n'est pas un compte virtuel) -->
          <div 
            v-if="authStore.selectedIdentity" 
            @click="authStore.selectedIdentity.id !== 'wp_virtual' ? goToSelectPerson() : null" 
            style="display: flex; align-items: center; padding: 0 8px; max-width: 250px;"
            :style="{ cursor: authStore.selectedIdentity.id !== 'wp_virtual' ? 'pointer' : 'default' }"
          >
            <div style="display: flex; flex-direction: column; align-items: flex-end; margin-right: 8px; overflow: hidden;">
              <span style="font-size: 0.85em; font-weight: bold; line-height: 1.1; color: var(--ion-color-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; text-align: right;">
                {{ authStore.selectedIdentity.name }}
              </span>
              <span style="font-size: 0.7em; opacity: 0.7; line-height: 1.1; white-space: nowrap;">
                {{ authStore.selectedIdentity.id === 'wp_virtual' ? 'Gestion' : (authStore.selectedIdentity.type === 'representative' ? 'Resp. Légal' : 'Adhérent') }}
              </span>
            </div>
            <ion-icon :icon="peopleOutline" style="font-size: 24px; color: var(--ion-color-primary); flex-shrink: 0;"></ion-icon>
          </div>

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

    <ion-content :fullscreen="true" class="ion-padding">
      <!-- Refresher pour le tirage vers le bas -->
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

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
          <!-- <ion-button fill="clear" router-link="/tabs/survey">Sondages</ion-button> -->
        </ion-list-header>

        <div v-if="sondageStore.isLoading" class="ion-text-center ion-padding">
          <ion-spinner name="dots"></ion-spinner>
        </div>

        <ion-item v-for="sondage in latestSondages" :key="sondage.id">
          <ion-icon slot="start" :icon="statsChartOutline" color="secondary"></ion-icon>
          <ion-label>
            <h3 v-html="sondage.title?.rendered || sondage.title?.raw || 'Sondage en cours'" class="ion-text-wrap" style="font-weight: 600;"></h3>
            <p>Donnez votre avis</p>
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
  IonButtons,
  IonList,
  IonListHeader,
  IonLabel,
  IonItem,
  IonThumbnail,
  IonIcon,
  IonButton,
  IonSpinner,
  IonBadge,
  IonRefresher,
  IonRefresherContent,
  onIonViewWillEnter
} from '@ionic/vue';
import { calendarOutline, statsChartOutline, newspaperOutline, personCircleOutline, logOutOutline, peopleOutline } from 'ionicons/icons';
import { ref, computed, watch } from 'vue';
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
 * Charge toutes les données de la page
 */
const loadAllData = async () => {
  // On lance tout en parallèle pour la vitesse
  await Promise.all([
    fetchLatestNews(),
    agendaStore.fetchAgenda(),
    sondageStore.fetchSondagesData(true) // force le rechargement pour les sondages
  ]);
};

/**
 * Gère le rafraîchissement manuel (Pull-to-refresh)
 */
const handleRefresh = async (event: any) => {
  await loadAllData();
  event.target.complete();
};

/**
 * Surveille les changements de connexion pour rafraîchir les données
 * (Important lors de la déconnexion depuis cette page)
 */
watch(() => authStore.isAuthenticated, () => {
  loadAllData();
});

/**
 * Redirige vers le choix de personne
 */
const goToSelectPerson = () => {
  router.push('/tabs/select-person');
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
  return agendaStore.events
    .filter(e => !isPast(e))
    .slice(0, 3);
});

const isPast = (event: AgendaEvent): boolean => {
  const referenceDate = event.meta?._dame_end_date || event.meta?._dame_start_date || '';
  return referenceDate < todayStr;
};

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
  loadAllData();
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
