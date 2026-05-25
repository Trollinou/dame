<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Agenda</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          v-model="searchQuery"
          placeholder="Rechercher un événement..."
          animated
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" ref="contentRef" class="ion-padding">
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">Agenda</ion-title>
          </ion-toolbar>
        </ion-header>

        <!-- Infinite Scroll TOP (Historique) -->
        <ion-infinite-scroll 
          v-if="!searchQuery"
          position="top" 
          @ionInfinite="loadMorePast($event)" 
          :disabled="!hasMorePast || isLoading"
        >
          <ion-infinite-scroll-content 
            loading-spinner="dots" 
            loading-text="Chargement de l'historique..."
          >
          </ion-infinite-scroll-content>
        </ion-infinite-scroll>

        <!-- État de chargement initial -->
        <div v-if="isLoading && events.length === 0" class="ion-text-center ion-padding">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement de l'agenda...</p>
        </div>

        <!-- Liste des événements -->
        <ion-list v-else-if="filteredEvents.length > 0">
          <ion-item
            v-for="event in filteredEvents"
            :key="event.id"
            :id="'event-' + event.id"
            button
            @click="goToDetail(event.id)"
            :class="{ 'past-event': isPast(event) }"
          >
            <ion-label>
              <h2 :class="{ 'upcoming-title': !isPast(event) }" v-html="event.title.rendered"></h2>
              <p>{{ formatEventDate(event) }}</p>
            </ion-label>
            <ion-badge v-if="isToday(event)" color="warning" slot="end">Actuellement</ion-badge>
          </ion-item>
        </ion-list>

        <!-- Aucun résultat -->
        <div v-else class="ion-text-center ion-padding">
          <p v-if="searchQuery">Aucun événement ne correspond à "{{ searchQuery }}".</p>
          <p v-else>Aucun événement trouvé.</p>
        </div>

        <!-- Infinite Scroll BOTTOM (Futur) -->
        <ion-infinite-scroll 
          v-if="!searchQuery"
          @ionInfinite="loadMoreUpcoming($event)" 
          :disabled="!hasMoreUpcoming || isLoading"
        >
          <ion-infinite-scroll-content 
            loading-spinner="dots" 
            loading-text="Chargement des événements futurs..."
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
  IonSearchbar,
  IonList,
  IonItem,
  IonLabel,
  IonSpinner,
  IonBadge,
  IonInfiniteScroll,
  IonInfiniteScrollContent,
  onIonViewWillEnter
} from '@ionic/vue';
import { ref, computed, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useAgendaStore, type AgendaEvent } from '../stores/agenda';
import { storeToRefs } from 'pinia';

const router = useRouter();
const agendaStore = useAgendaStore();
const { events, isLoading, hasMoreUpcoming, hasMorePast, upcomingPage, pastPage } = storeToRefs(agendaStore);

const searchQuery = ref('');
const todayStr = new Date().toISOString().split('T')[0];
const isFirstLoad = ref(true);

const removeAccents = (str: string): string => {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

const goToDetail = (id: number) => {
  router.push('/tabs/agenda/' + id);
};

const formatPart = (dateString: string): string => {
  if (!dateString) return '';
  return dateString.split('-').reverse().join('/');
};

const isPast = (event: AgendaEvent): boolean => {
  const today = new Date().toISOString().split('T')[0];
  const referenceDate = event.meta?._dame_end_date || event.meta?._dame_start_date || '';
  return referenceDate < today;
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

/**
 * Chargement des événements futurs (Scrolling vers le bas)
 */
const loadMoreUpcoming = async (ev: any) => {
  upcomingPage.value++;
  const data = await agendaStore.fetchBatch('upcoming', todayStr, upcomingPage.value);
  if (data.length > 0) {
    // Filtrage des doublons (au cas où l'API renvoie un événement déjà chargé)
    const newItems = data.filter(newItem => !events.value.some(existing => existing.id === newItem.id));
    events.value = [...events.value, ...newItems];
  }
  ev.target.complete();
};

/**
 * Chargement des événements passés (Scrolling vers le haut)
 */
const loadMorePast = async (ev: any) => {
  const data = await agendaStore.fetchBatch('past', todayStr, pastPage.value);
  if (data.length > 0) {
    // Inversion car le serveur renvoie DESC (plus récent d'abord), on veut ASC pour la liste
    const dataAsc = data.reverse();
    // Filtrage des doublons (crucial pour les événements en cours qui chevauchent les deux requêtes)
    const newItems = dataAsc.filter(newItem => !events.value.some(existing => existing.id === newItem.id));
    
    events.value = [...newItems, ...events.value];
    pastPage.value++;
  }
  ev.target.complete();
};

/**
 * Filtrage local pour la recherche
 */
const filteredEvents = computed(() => {
  if (!searchQuery.value.trim()) return events.value;
  const query = removeAccents(searchQuery.value.toLowerCase());
  return events.value.filter(event => 
    removeAccents((event.title?.raw || "").toLowerCase()).includes(query)
  );
});

onIonViewWillEnter(async () => {
  if (events.value.length === 0) {
    isLoading.value = true;
    const data = await agendaStore.fetchBatch('upcoming', todayStr, 1);
    events.value = data;
    isLoading.value = false;
  }
  
  // On ne repositionne la vue que lors du tout premier chargement
  // Si l'utilisateur revient d'un détail (Back), isFirstLoad sera déjà false
  if (isFirstLoad.value) {
    await nextTick();
    setTimeout(() => {
      const targetEvent = events.value.find(e => (e.meta?._dame_start_date || '') >= todayStr);
      if (targetEvent) {
        const el = document.getElementById('event-' + targetEvent.id);
        if (el) {
          el.scrollIntoView({ behavior: 'auto', block: 'center' });
        }
      }
      isFirstLoad.value = false;
    }, 200);
  }
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

ion-list { margin-top: 8px; }
h2 { font-weight: bold; }
p { color: var(--ion-color-medium); }
.past-event { opacity: 0.6; }
.past-event h2 { font-weight: normal; }
.upcoming-title { color: var(--ion-color-primary); }
ion-badge { margin-left: 8px; }
</style>
