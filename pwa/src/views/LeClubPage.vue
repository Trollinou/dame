<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>{{ pageTitle }}</ion-title>
      </ion-toolbar>

      <!-- Sous-navigation (Segment) -->
      <ion-toolbar>
        <ion-segment :value="selectedSegment" @ionChange="onSegmentChange($event.detail.value as string, loadTabContent)">
          <ion-segment-button value="actualites">
            <ion-label>Actualités</ion-label>
          </ion-segment-button>
          <ion-segment-button value="agenda">
            <ion-label>Agenda</ion-label>
          </ion-segment-button>
          <ion-segment-button value="tournois">
            <ion-label>Tournois</ion-label>
          </ion-segment-button>
          <ion-segment-button value="benevolat">
            <ion-label>Bénévolat</ion-label>
          </ion-segment-button>
        </ion-segment>
      </ion-toolbar>

      <ion-toolbar>
        <ion-searchbar
          v-model="searchQuery"
          :placeholder="searchPlaceholder"
          animated
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" ref="contentRef" class="ion-padding">
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">{{ pageTitle }}</ion-title>
          </ion-toolbar>
        </ion-header>

        <!-- ONGLET 0 : ACTUALITES -->
        <ActualitesSegmentView
          v-if="selectedSegment === 'actualites'"
          :search-query="searchQuery"
          @go-to-news-detail="goToNewsDetail"
        />

        <!-- ONGLET 1 : AGENDA -->
        <AgendaSegmentView
          v-else-if="selectedSegment === 'agenda'"
          :search-query="searchQuery"
          :events="events"
          :is-loading="isLoading"
          :has-more-past="hasMorePast"
          :has-more-upcoming="hasMoreUpcoming"
          :today-str="todayStr"
          @go-to-detail="goToDetail"
          @load-more-past="loadMorePast"
          @load-more-upcoming="loadMoreUpcoming"
        />

        <!-- ONGLET 2 : TOURNOIS -->
        <TournoisSegmentView
          v-else-if="selectedSegment === 'tournois'"
          :search-query="searchQuery"
          :tournament-error="tournamentError"
          @go-to-tournament-detail="goToTournamentDetail"
          @retry="fetchTournaments"
        />

        <!-- ONGLET 3 : BENEVOLAT -->
        <BenevolatSegmentView
          v-else-if="selectedSegment === 'benevolat'"
          :search-query="searchQuery"
          :today-str="todayStr"
          @view-benevolat="viewBenevolat"
        />
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, nextTick } from 'vue';
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonSearchbar,
  IonSegment,
  IonSegmentButton,
  IonLabel,
  onIonViewWillEnter
} from '@ionic/vue';
import { useRouter, useRoute } from 'vue-router';
import { useAgendaStore, type AgendaEvent } from '@/stores/agenda';
import { useTournamentStore } from '@/stores/tournament';
import { useBenevolatStore, type Benevolat } from '@/stores/benevolat';
import { useAuthStore } from '@/stores/auth';
import { useNewsStore } from '@/stores/news';
import { storeToRefs } from 'pinia';
import { useAgendaSearch } from '@/composables/agenda/useAgendaSearch';
import ActualitesSegmentView from '@/components/agenda/ActualitesSegmentView.vue';
import AgendaSegmentView from '@/components/agenda/AgendaSegmentView.vue';
import TournoisSegmentView from '@/components/agenda/TournoisSegmentView.vue';
import BenevolatSegmentView from '@/components/agenda/BenevolatSegmentView.vue';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const agendaStore = useAgendaStore();
const tournamentStore = useTournamentStore();
const benevolatStore = useBenevolatStore();
const newsStore = useNewsStore();

const { events, isLoading, hasMoreUpcoming, hasMorePast, upcomingPage, pastPage } = storeToRefs(agendaStore);
const { selectedSegment, searchQuery, pageTitle, searchPlaceholder, onSegmentChange } = useAgendaSearch();

const todayStr = agendaStore.getTodayLocal();
const isFirstLoad = ref(true);
const tournamentError = ref<string | null>(null);

const goToDetail = (id: number) => {
  router.push('/agenda/' + id);
};

const goToTournamentDetail = (id: number) => {
  router.push(`/page/${id}`);
};

const goToNewsDetail = (id: number) => {
  router.push('/news/' + id);
};

const viewBenevolat = (benevolat: Benevolat) => {
  if (authStore.adminMode) {
    router.push('/admin/benevolat/' + benevolat.id);
  } else {
    if (authStore.isAuthenticated) {
      router.push('/benevolat/participation/' + benevolat.id);
    } else {
      router.push({
        path: '/login',
        query: { message: 'Identification requise pour proposer votre aide.' },
      });
    }
  }
};

const fetchNews = async () => {
  try {
    await newsStore.fetchPosts();
  } catch (err) {
    console.warn('Erreur chargement posts:', err);
  }
};

const fetchTournaments = async () => {
  tournamentError.value = null;
  try {
    await tournamentStore.fetchMenu();
  } catch {
    if (!navigator.onLine) {
      tournamentError.value = 'Vous êtes hors-ligne. Les informations sur les tournois nécessitent une connexion.';
    } else {
      tournamentError.value = 'Impossible de charger les tournois.';
    }
  }
};

const fetchBenevolats = async () => {
  try {
    await benevolatStore.fetchBenevolatsData();
  } catch (err) {
    console.error('Erreur chargement bénévolat:', err);
  }
};

const loadTabContent = () => {
  if (selectedSegment.value === 'actualites') {
    fetchNews();
  } else if (selectedSegment.value === 'tournois') {
    fetchTournaments();
  } else if (selectedSegment.value === 'benevolat') {
    fetchBenevolats();
  }
};

const loadMoreUpcoming = async (ev: any) => {
  const data = await agendaStore.fetchBatch('upcoming', todayStr, upcomingPage.value);
  if (data && data.length > 0) {
    const newItems = data.filter((newItem) => !events.value.some((existing) => existing.id === newItem.id));
    events.value = [...events.value, ...newItems];
    upcomingPage.value++;
  }
  ev.target.complete();
};

const loadMorePast = async (ev: any) => {
  const data = await agendaStore.fetchBatch('past', todayStr, pastPage.value);
  if (data && data.length > 0) {
    const dataAsc = [...data].reverse();
    const newItems = dataAsc.filter((newItem) => !events.value.some((existing) => existing.id === newItem.id));
    events.value = [...newItems, ...events.value];
    pastPage.value++;
  }
  ev.target.complete();
};

const scrollToCurrentEvent = async () => {
  await nextTick();
  setTimeout(() => {
    const targetEvent = events.value.find((e) => {
      const refDate = e.meta?._dame_end_date || e.meta?._dame_start_date || '';
      return refDate >= todayStr;
    });
    if (targetEvent) {
      const el = document.getElementById('event-' + targetEvent.id);
      if (el) {
        el.scrollIntoView({ behavior: 'auto', block: 'center' });
      }
    }
  }, 150);
};

onIonViewWillEnter(async () => {
  if (route.query.tab) {
    selectedSegment.value = route.query.tab as string;
  }
  loadTabContent();

  const isPastEvent = (e: AgendaEvent): boolean => {
    const refDate = e.meta?._dame_end_date || e.meta?._dame_start_date || '';
    return refDate !== '' && refDate < todayStr;
  };

  const hasPast = events.value.some(isPastEvent);

  if (events.value.length === 0) {
    isLoading.value = true;
  } else {
    scrollToCurrentEvent();
  }

  try {
    const pastPromise = hasPast
      ? Promise.resolve(null)
      : agendaStore.fetchBatch('past', todayStr, 1);

    const [upcomingData, pastData] = await Promise.all([
      agendaStore.fetchBatch('upcoming', todayStr, 1),
      pastPromise,
    ]);

    let merged = [...events.value];

    if (pastData && pastData.length > 0) {
      const pastAsc = [...pastData].reverse();
      const newPast = pastAsc.filter((newItem) => !merged.some((existing) => existing.id === newItem.id));
      merged = [...newPast, ...merged];
      pastPage.value = 2;
    }

    if (upcomingData && upcomingData.length > 0) {
      const pastOnly = merged.filter(isPastEvent);
      merged = [...pastOnly, ...upcomingData];
      upcomingPage.value = 2;
    }

    events.value = merged.filter((v, i, a) => a.findIndex((t) => t.id === v.id) === i);
  } finally {
    isLoading.value = false;
    scrollToCurrentEvent();
  }
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}
</style>
