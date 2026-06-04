<template>
  <ion-page>
    <!-- Header Global avec Authentification -->
    <ion-header :translucent="true">
      <ion-toolbar>
        <!-- Logo à gauche -->
        <ion-buttons slot="start">
          <div style="display: flex; align-items: center; padding-left: 12px;">
            <img src="/assets/icon/logo.png" style="height: 20px; margin-right: 8px;" alt="Logo" />
            <span style="font-weight: 800; letter-spacing: 0.5px; color: var(--ion-color-dark);">Echiquier Lédonien</span>
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
      <!-- Wrapper respectant la Dynamic Island -->
      <div class="safe-area-wrapper">
        <!-- Refresher pour le tirage vers le bas -->
        <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
          <ion-refresher-content></ion-refresher-content>
        </ion-refresher>

        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">Echiquier Lédonien</ion-title>
          </ion-toolbar>
        </ion-header>

        <!-- Espace de Jeu (Uniquement si connecté et ROI actif) -->
        <div v-if="authStore.isAuthenticated && authStore.isRoiActive">
          <ion-card style="--background: var(--ion-color-step-50, #f4f5f8); margin-top: 8px; margin-bottom: 0;">
              <ion-button expand="block" color="primary" style="margin: 0;" @click="goToPlay">
               ♟️ Jouer une partie ♟️
              </ion-button>
          </ion-card>
        </div>

        <!-- Section Dernières Nouvelles -->
        <ion-list lines="full" style="margin: 0;">
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
              <h3 v-safe-html="post.title.rendered" class="ion-text-wrap" style="font-weight: 600;"></h3>
              <p>{{ formatDate(post.date) }}</p>
            </ion-label>
          </ion-item>

          <ion-item v-if="!isLoadingNews && latestPosts.length === 0" lines="none">
            <ion-label class="ion-text-center">Aucune actualité</ion-label>
          </ion-item>
        </ion-list>

        <!-- Section Prochains Événements -->
        <ion-list lines="full" style="margin: 0;">
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
              <h3 v-safe-html="event.title.rendered" class="ion-text-wrap" style="font-weight: 600;"></h3>
              <p>{{ formatEventDate(event) }}</p>
            </ion-label>
            <ion-badge v-if="isToday(event)" color="warning" slot="end">Actuellement</ion-badge>
          </ion-item>

          <ion-item v-if="!agendaStore.isLoading && upcomingEvents.length === 0" lines="none">
            <ion-label class="ion-text-center">Aucun événement à venir</ion-label>
          </ion-item>
        </ion-list>

        <!-- Section Appel à bénévoles -->
        <ion-list lines="full" style="margin: 0;">
          <ion-list-header>
            <ion-label color="primary">Appel à bénévoles</ion-label>
            <ion-button fill="clear" router-link="/tabs/benevolat">Bénévolat</ion-button>
          </ion-list-header>

          <div v-if="benevolatStore.isLoading" class="ion-text-center ion-padding">
            <ion-spinner name="dots"></ion-spinner>
          </div>

          <ion-item v-for="benevolat in latestBenevolats" :key="benevolat.id" button @click="goToBenevolat(benevolat.id)">
            <ion-icon slot="start" :icon="handRightOutline" color="secondary"></ion-icon>
            <ion-label>
              <h3 v-safe-html="benevolat.title?.rendered || benevolat.title?.raw || 'Appel en cours'" class="ion-text-wrap" style="font-weight: 600;"></h3>
              <p>{{ formatBenevolatDates(benevolat) }}</p>
            </ion-label>
            <div slot="end" style="display: flex; align-items: center; gap: 8px;">
              <ion-badge v-if="benevolatStore.hasUserVoted(benevolat.id) && !authStore.adminMode" color="success">Inscrit</ion-badge>
              <ion-badge v-if="isBenevolatExpired(benevolat)" color="danger">Terminé</ion-badge>
            </div>
          </ion-item>

          <ion-item v-if="!benevolatStore.isLoading && latestBenevolats.length === 0" lines="none">
            <ion-label class="ion-text-center">Aucun appel actif</ion-label>
          </ion-item>
        </ion-list>
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
  IonCard,
  IonCardContent,
  IonCardHeader,
  IonCardTitle,
  onIonViewWillEnter
} from '@ionic/vue';
import { calendarOutline, handRightOutline, newspaperOutline, personCircleOutline, logOutOutline, peopleOutline } from 'ionicons/icons';
import { ref, computed, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { useAgendaStore, type AgendaEvent } from '@/stores/agenda';
import { useBenevolatStore } from '@/stores/benevolat';
import { useNewsStore } from '@/stores/news';

const authStore = useAuthStore();
const router = useRouter();
const agendaStore = useAgendaStore();
const benevolatStore = useBenevolatStore();
const newsStore = useNewsStore();

const latestPosts = computed(() => newsStore.posts.slice(0, 3));
const isLoadingNews = computed(() => newsStore.isLoading);

/**
 * Calcul de la date locale au format YYYY-MM-DD
 */
const getTodayStr = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const todayStr = getTodayStr();

const loadAllData = async () => {
  const tasks = [
    fetchLatestNews(),
    agendaStore.fetchAgenda(),
    authStore.fetchPwaConfig()
  ];

  if (authStore.isAuthenticated) {
    tasks.push(benevolatStore.fetchBenevolatsData(true));
  } else {
    // CRITIQUE : Purge des données de session pour éviter la persistance
    benevolatStore.clearData();
  }

  await Promise.all(tasks);
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
 * Redirige vers l'espace de jeu
 */
const goToPlay = () => {
  router.push('/tabs/play');
};

/**
 * Gère la déconnexion
 */
const handleLogout = async () => {
  await authStore.logout();
};

/**
 * Récupère les 3 dernières actualités (via le store)
 */
const fetchLatestNews = async () => {
  try {
    await newsStore.fetchPosts();
  } catch (err) {
    // L'erreur est déjà logguée par le store, on ignore ici pour garder le cache à l'écran
    console.warn("Échec refresh news home (serveur coupé ?), utilisation du cache.");
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
 * Prend les appels à bénévolat actifs, triés par date la plus proche
 */
const latestBenevolats = computed(() => {
  return benevolatStore.benevolats
    .filter(b => !isBenevolatExpired(b)) // Uniquement les non-terminés
    .sort((a, b) => {
      const dateA = a.dame_benevolat_data?.[0]?.date || '';
      const dateB = b.dame_benevolat_data?.[0]?.date || '';
      return dateA.localeCompare(dateB); // Ordre chronologique
    })
    .slice(0, 2);
});

/**
 * Formate la plage de dates
 */
const formatBenevolatDates = (benevolat: any): string => {
  const data = benevolat.dame_benevolat_data;
  
  if (Array.isArray(data) && data.length > 0) {
    const firstDate = data[0].date;
    const lastDate = data[data.length - 1].date;

    const format = (d: string) => d ? d.split('-').reverse().join('/') : '?';
    
    return `Du ${format(firstDate)} au ${format(lastDate)}`;
  }

  return 'Dates non définies';
};

/**
 * Vérifie si un appel est expiré
 */
const isBenevolatExpired = (benevolat: any): boolean => {
  const data = benevolat.dame_benevolat_data;
  if (Array.isArray(data) && data.length > 0) {
    const dates = data.map((d: any) => d.date).filter(Boolean);
    if (dates.length === 0) return false;
    const maxDate = dates.reduce((max: string, d: string) => d > max ? d : max, dates[0]);
    return maxDate < todayStr;
  }
  return false;
};

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
const goToBenevolat = (id: number) => {
  if (authStore.adminMode) {
    router.push(`/tabs/admin/benevolat/${id}`);
  } else {
    if (authStore.isAuthenticated) {
      router.push(`/tabs/benevolat/participation/${id}`);
    } else {
      router.push({ 
        path: '/tabs/login', 
        query: { message: 'Identification requise pour proposer votre aide.' } 
      });
    }
  }
};

onIonViewWillEnter(() => {
  loadAllData();
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

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
