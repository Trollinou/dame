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

    <ion-content :fullscreen="true" ref="contentRef">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Agenda</ion-title>
        </ion-toolbar>
      </ion-header>
      <!-- État de chargement (Spinner bloquant uniquement si vide) -->
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
  IonIcon,
  IonBadge,
  onIonViewWillEnter,
  onIonViewDidEnter
} from '@ionic/vue';
import { ref, computed, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useAgendaStore, type AgendaEvent } from '../stores/agenda';
import { storeToRefs } from 'pinia';

const router = useRouter();
const agendaStore = useAgendaStore();
const { events, isLoading } = storeToRefs(agendaStore);

const contentRef = ref();
const searchQuery = ref('');
const lastViewedEventId = ref<number | null>(null);
const todayStr = new Date().toISOString().split('T')[0];

const removeAccents = (str: string): string => {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

const goToDetail = (id: number) => {
  lastViewedEventId.value = id;
  router.push('/tabs/agenda/' + id);
};

const formatPart = (dateString: string): string => {
  if (!dateString) return '';
  return dateString.split('-').reverse().join('/');
};

const isPast = (event: AgendaEvent): boolean => {
  return (event.meta?._dame_start_date || '') < todayStr;
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

const scrollToTarget = () => {
  let targetId = lastViewedEventId.value;

  if (!targetId) {
    const upcomingEvent = events.value.find(event => 
      (event.meta?._dame_start_date || '') >= todayStr
    );
    if (upcomingEvent) targetId = upcomingEvent.id;
  }

  if (targetId) {
    const el = document.getElementById('event-' + targetId);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }
};

const filteredEvents = computed(() => {
  if (!searchQuery.value.trim()) {
    return events.value;
  }
  const query = removeAccents(searchQuery.value.toLowerCase());
  return events.value.filter(event => {
    const name = removeAccents((event.title?.raw || "").toLowerCase());
    return name.includes(query);
  });
});

onIonViewWillEnter(async () => {
  await agendaStore.fetchAgenda();
  await nextTick();
  setTimeout(scrollToTarget, 200);
});

onIonViewDidEnter(() => {
  if (!isLoading.value && events.value.length > 0) {
    setTimeout(scrollToTarget, 100);
  }
});
</script>

<style scoped>
ion-list { margin-top: 8px; }
h2 { font-weight: bold; }
p { color: var(--ion-color-medium); }
.past-event { opacity: 0.6; }
.past-event h2 { font-weight: normal; }
.upcoming-title { color: var(--ion-color-primary); }
ion-badge { margin-left: 8px; }
</style>
