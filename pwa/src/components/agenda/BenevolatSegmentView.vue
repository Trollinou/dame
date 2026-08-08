<template>
  <div>
    <div v-if="benevolatStore.isLoading" class="ion-text-center ion-padding">
      <ion-spinner name="crescent"></ion-spinner>
      <p>Chargement des appels à bénévoles...</p>
    </div>

    <div v-else>
      <!-- SECTION : APPELS EN COURS -->
      <ion-list v-if="openBenevolats.length > 0" lines="full">
        <ion-list-header>
          <ion-label color="primary">Appels en cours</ion-label>
        </ion-list-header>

        <ion-item v-for="benevolat in openBenevolats" :key="benevolat.id" button @click="$emit('view-benevolat', benevolat)">
          <ion-icon slot="start" :icon="handRightOutline" color="primary" class="ion-margin-end"></ion-icon>
          <ion-label>
            <h2 v-safe-html="benevolat.title.rendered"></h2>
            <p>{{ formatBenevolatDates(benevolat) }}</p>
          </ion-label>
          <div slot="end" style="display: flex; align-items: center; gap: 8px;">
            <ion-badge
              v-if="benevolatStore.hasUserVoted(benevolat.id) && !authStore.adminMode"
              color="success"
            >
              Inscrit
            </ion-badge>
            <ion-badge
              v-if="authStore.adminMode"
              color="primary"
            >
              {{ benevolatStore.getResponseCount(benevolat.id) }} rép.
            </ion-badge>
          </div>
        </ion-item>
      </ion-list>

      <!-- SECTION : APPELS TERMINÉS -->
      <ion-list v-if="finishedBenevolats.length > 0" lines="full" class="ion-margin-top">
        <ion-list-header>
          <ion-label color="medium">Appels terminés</ion-label>
        </ion-list-header>

        <ion-item v-for="benevolat in finishedBenevolats" :key="benevolat.id" button class="finished-item" @click="$emit('view-benevolat', benevolat)">
          <ion-icon slot="start" :icon="handRightOutline" color="medium" class="ion-margin-end" style="opacity: 0.6;"></ion-icon>
          <ion-label>
            <h2 v-safe-html="benevolat.title.rendered"></h2>
            <p>{{ formatBenevolatDates(benevolat) }}</p>
          </ion-label>
          <div slot="end" style="display: flex; align-items: center; gap: 8px;">
            <ion-badge
              v-if="benevolatStore.hasUserVoted(benevolat.id) && !authStore.adminMode"
              color="success"
              style="opacity: 0.7;"
            >
              Inscrit
            </ion-badge>
            <ion-badge
              v-if="authStore.adminMode"
              color="medium"
            >
              {{ benevolatStore.getResponseCount(benevolat.id) }} rép.
            </ion-badge>
          </div>
        </ion-item>
      </ion-list>

      <div v-if="openBenevolats.length === 0 && finishedBenevolats.length === 0 && !benevolatStore.isLoading" class="ion-text-center ion-padding">
        <p v-if="searchQuery">Aucun appel ne correspond à "{{ searchQuery }}".</p>
        <p v-else>Aucun appel à bénévoles disponible.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  IonSpinner,
  IonList,
  IonListHeader,
  IonItem,
  IonLabel,
  IonBadge,
  IonIcon
} from '@ionic/vue';
import { handRightOutline } from 'ionicons/icons';
import { useBenevolatStore, type Benevolat } from '@/stores/benevolat';
import { useAuthStore } from '@/stores/auth';
import { removeAccents } from '@/utils/stringUtils';

const benevolatStore = useBenevolatStore();
const authStore = useAuthStore();

const props = defineProps<{
  searchQuery: string;
  todayStr: string;
}>();

defineEmits<{
  (e: 'view-benevolat', benevolat: Benevolat): void;
}>();

const isBenevolatExpired = (benevolat: Benevolat): boolean => {
  const data = benevolat.dame_benevolat_data;
  if (Array.isArray(data) && data.length > 0) {
    const dates = data.map((d) => d.date).filter(Boolean);
    if (dates.length === 0) return false;
    const maxDate = dates.reduce((max, d) => (d > max ? d : max), dates[0]);
    return maxDate < props.todayStr;
  }
  return false;
};

const formatBenevolatDates = (benevolat: Benevolat): string => {
  const data = benevolat.dame_benevolat_data;
  if (Array.isArray(data) && data.length > 0) {
    const firstDate = data[0].date;
    const lastDate = data[data.length - 1].date;
    const format = (d: string) => (d ? d.split('-').reverse().join('/') : '?');
    return `Du ${format(firstDate)} au ${format(lastDate)}`;
  }
  return 'Dates non définies';
};

const sortedBenevolats = computed(() => {
  let list = benevolatStore.benevolats;
  if (props.searchQuery.trim()) {
    const query = removeAccents(props.searchQuery.toLowerCase());
    list = list.filter((b) =>
      removeAccents(b.title.rendered.toLowerCase()).includes(query)
    );
  }
  return [...list].sort((a, b) => {
    const dateA = a.dame_benevolat_data?.[0]?.date || '';
    const dateB = b.dame_benevolat_data?.[0]?.date || '';
    return dateA.localeCompare(dateB);
  });
});

const openBenevolats = computed(() => {
  return sortedBenevolats.value.filter((b) => !isBenevolatExpired(b));
});

const finishedBenevolats = computed(() => {
  const list = sortedBenevolats.value.filter((b) => isBenevolatExpired(b));
  return [...list].sort((a, b) => {
    const datesA = a.dame_benevolat_data?.map((d) => d.date).filter(Boolean) || [];
    const datesB = b.dame_benevolat_data?.map((d) => d.date).filter(Boolean) || [];
    const maxDateA = datesA.reduce((max, d) => (d > max ? d : max), '');
    const maxDateB = datesB.reduce((max, d) => (d > max ? d : max), '');
    return maxDateB.localeCompare(maxDateA);
  });
});
</script>

<style scoped>
ion-list-header {
  --color: var(--ion-color-primary);
  font-weight: bold;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  font-size: 0.85em;
  margin-bottom: 4px;
}

.finished-item {
  --opacity: 0.8;
}

.finished-item h2 {
  color: var(--ion-color-medium);
}
</style>
