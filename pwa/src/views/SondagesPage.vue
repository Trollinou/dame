<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Sondages</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          v-model="searchQuery"
          placeholder="Rechercher un sondage..."
          animated
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <!-- État de chargement (Silent Refresh) -->
      <div v-if="isLoading && sondages.length === 0" class="ion-text-center ion-padding">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Chargement des sondages...</p>
      </div>

      <!-- Liste des sondages -->
      <ion-list v-else-if="filteredSondages.length > 0">
        <ion-item v-for="sondage in filteredSondages" :key="sondage.id" button>
          <ion-label @click="viewSondage(sondage)">
            <h2 v-html="sondage.title.rendered"></h2>
            <p>{{ formatSondageDates(sondage) }}</p>
          </ion-label>
          <ion-badge 
            slot="end" 
            color="primary" 
            @click.stop="viewResults(sondage)"
          >
            {{ getResponseCount(sondage.id) }} rép.
          </ion-badge>
        </ion-item>
      </ion-list>

      <!-- Aucun résultat -->
      <div v-else class="ion-text-center ion-padding">
        <p v-if="searchQuery">Aucun sondage trouvé pour "{{ searchQuery }}".</p>
        <p v-else>Aucun sondage disponible.</p>
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
  IonBadge,
  IonSpinner,
  onIonViewWillEnter
} from '@ionic/vue';
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useSondageStore, type Sondage } from '../stores/sondages';
import { storeToRefs } from 'pinia';

const router = useRouter();
const sondageStore = useSondageStore();
const { sondages, isLoading } = storeToRefs(sondageStore);
const { getResponseCount } = sondageStore;

const searchQuery = ref('');

/**
 * Supprime les accents d'une chaîne
 */
const removeAccents = (str: string): string => {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

/**
 * Formate la plage de dates en utilisant le premier et le dernier élément du tableau dame_sondage_data
 */
const formatSondageDates = (sondage: Sondage): string => {
  const data = sondage.dame_sondage_data;
  
  if (Array.isArray(data) && data.length > 0) {
    const firstDate = data[0].date;
    const lastDate = data[data.length - 1].date;

    const format = (d: string) => d ? d.split('-').reverse().join('/') : '?';
    
    return `Du ${format(firstDate)} au ${format(lastDate)}`;
  }

  return 'Dates non définies';
};

/**
 * Filtrage local (Recherche textuelle)
 */
const filteredSondages = computed(() => {
  if (!searchQuery.value.trim()) return sondages.value;
  
  const query = removeAccents(searchQuery.value.toLowerCase());
  return sondages.value.filter(s => 
    removeAccents(s.title.rendered.toLowerCase()).includes(query)
  );
});

/**
 * Actions
 */
const viewSondage = (sondage: Sondage) => {
  router.push('/tabs/admin/survey/' + sondage.id);
};

const viewResults = (sondage: Sondage) => {
  router.push('/tabs/admin/survey/' + sondage.id);
};

// Chargement des données au montage/entrée
onIonViewWillEnter(() => {
  sondageStore.fetchSondagesData();
});
</script>

<style scoped>
ion-list { margin-top: 8px; }
ion-badge { padding: 6px 10px; border-radius: 8px; cursor: pointer; }
h2 { font-weight: bold; }
</style>
