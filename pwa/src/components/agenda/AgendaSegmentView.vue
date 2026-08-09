<template>
  <div>
    <!-- Infinite Scroll TOP (Historique) -->
    <ion-infinite-scroll
      v-if="!searchQuery && hasMorePast"
      position="top"
      @ionInfinite="$emit('load-more-past', $event)"
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
        @click="$emit('go-to-detail', event.id)"
        :class="{ 'past-event': isPast(event) }"
      >
        <ion-label>
          <h2 :class="{ 'upcoming-title': !isPast(event) }" v-safe-html="event.title.rendered"></h2>
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
      v-if="!searchQuery && hasMoreUpcoming"
      @ionInfinite="$emit('load-more-upcoming', $event)"
      :disabled="!hasMoreUpcoming || isLoading"
    >
      <ion-infinite-scroll-content
        loading-spinner="dots"
        loading-text="Chargement des événements futurs..."
      >
      </ion-infinite-scroll-content>
    </ion-infinite-scroll>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  IonSpinner,
  IonList,
  IonItem,
  IonLabel,
  IonBadge,
  IonInfiniteScroll,
  IonInfiniteScrollContent,
} from '@ionic/vue';
import type { AgendaEvent } from '@/stores/agenda';
import { removeAccents } from '@/utils/stringUtils';

const props = defineProps<{
  searchQuery: string;
  events: AgendaEvent[];
  isLoading: boolean;
  hasMorePast: boolean;
  hasMoreUpcoming: boolean;
  todayStr: string;
}>();

defineEmits<{
  (e: 'go-to-detail', id: number): void;
  (e: 'load-more-past', event: any): void;
  (e: 'load-more-upcoming', event: any): void;
}>();

const filteredEvents = computed(() => {
  if (!props.searchQuery.trim()) return props.events;
  const query = removeAccents(props.searchQuery.toLowerCase());
  return props.events.filter((event) =>
    removeAccents((event.title?.raw || '').toLowerCase()).includes(query)
  );
});

const isPast = (event: AgendaEvent): boolean => {
  const referenceDate = event.meta?._dame_end_date || event.meta?._dame_start_date || '';
  return referenceDate < props.todayStr;
};

const isToday = (event: AgendaEvent): boolean => {
  const startDate = event.meta?._dame_start_date;
  const endDate = event.meta?._dame_end_date;

  if (!startDate) return false;

  if (endDate && startDate !== endDate) {
    return props.todayStr >= startDate && props.todayStr <= endDate;
  }

  return startDate === props.todayStr;
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
