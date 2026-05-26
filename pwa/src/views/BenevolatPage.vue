<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Appel à bénévoles</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          v-model="searchQuery"
          placeholder="Rechercher..."
          animated
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <!-- État de chargement (Silent Refresh) -->
        <div v-if="isLoading && benevolats.length === 0" class="ion-text-center ion-padding">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement...</p>
        </div>

        <div v-else-if="openBenevolats.length > 0 || finishedBenevolats.length > 0">
          <!-- SECTION : APPELS EN COURS -->
          <ion-list v-if="openBenevolats.length > 0">
            <ion-list-header>
              <ion-label color="primary">Appels en cours</ion-label>
            </ion-list-header>
            
            <ion-item v-for="benevolat in openBenevolats" :key="benevolat.id" button>
              <ion-label @click="viewBenevolat(benevolat)">
                <h2 v-safe-html="benevolat.title.rendered"></h2>
                <p>{{ formatBenevolatDates(benevolat) }}</p>
              </ion-label>
              <div slot="end" style="display: flex; align-items: center; gap: 8px;">
                <ion-badge 
                  v-if="hasUserVoted(benevolat.id) && !authStore.adminMode" 
                  color="success"
                >
                  Inscrit
                </ion-badge>
                <ion-badge 
                  v-if="authStore.adminMode"
                  color="primary" 
                  @click.stop="viewResults(benevolat)"
                >
                  {{ getResponseCount(benevolat.id) }} rép.
                </ion-badge>
              </div>
            </ion-item>
          </ion-list>

          <!-- SECTION : APPELS TERMINÉS -->
          <ion-list v-if="finishedBenevolats.length > 0" class="ion-margin-top">
            <ion-list-header>
              <ion-label color="medium">Appels terminés</ion-label>
            </ion-list-header>
            
            <ion-item v-for="benevolat in finishedBenevolats" :key="benevolat.id" button class="finished-item">
              <ion-label @click="viewBenevolat(benevolat)">
                <h2 v-safe-html="benevolat.title.rendered"></h2>
                <p>{{ formatBenevolatDates(benevolat) }}</p>
              </ion-label>
              <div slot="end" style="display: flex; align-items: center; gap: 8px;">
                <ion-badge 
                  v-if="hasUserVoted(benevolat.id) && !authStore.adminMode" 
                  color="success"
                  style="opacity: 0.7;"
                >
                  Inscrit
                </ion-badge>
                <ion-badge 
                  v-if="authStore.adminMode"
                  color="medium" 
                  @click.stop="viewResults(benevolat)"
                >
                  {{ getResponseCount(benevolat.id) }} rép.
                </ion-badge>
              </div>
            </ion-item>
          </ion-list>
        </div>

        <!-- Aucun résultat -->
        <div v-else class="ion-text-center ion-padding">
          <p v-if="searchQuery">Aucun résultat trouvé pour "{{ searchQuery }}".</p>
          <p v-else>Aucun appel à bénévoles disponible.</p>
        </div>
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
  IonListHeader,
  IonItem,
  IonLabel,
  IonBadge,
  IonSpinner,
  onIonViewWillEnter
} from '@ionic/vue';
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useBenevolatStore, type Benevolat } from '../stores/benevolat';
import { useAuthStore } from '../stores/auth';
import { storeToRefs } from 'pinia';

const router = useRouter();
const benevolatStore = useBenevolatStore();
const authStore = useAuthStore();
const { benevolats, isLoading } = storeToRefs(benevolatStore);
const { getResponseCount, hasUserVoted } = benevolatStore;

const searchQuery = ref('');

// Date du jour locale au format YYYY-MM-DD
const todayStr = (() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
})();

/**
 * Vérifie si un appel est expiré
 */
const isBenevolatExpired = (benevolat: Benevolat): boolean => {
  const data = benevolat.dame_benevolat_data;
  if (Array.isArray(data) && data.length > 0) {
    const dates = data.map(d => d.date).filter(Boolean);
    if (dates.length === 0) return false;
    const maxDate = dates.reduce((max, d) => d > max ? d : max, dates[0]);
    return maxDate < todayStr;
  }
  return false;
};

/**
 * Supprime les accents d'une chaîne
 */
const removeAccents = (str: string): string => {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

/**
 * Formate la plage de dates
 */
const formatBenevolatDates = (benevolat: Benevolat): string => {
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
 * Liste de base filtrée par la recherche et triée
 */
const sortedList = computed(() => {
  let list = benevolats.value;

  if (searchQuery.value.trim()) {
    const query = removeAccents(searchQuery.value.toLowerCase());
    list = list.filter(b => 
      removeAccents(b.title.rendered.toLowerCase()).includes(query)
    );
  }

  return [...list].sort((a, b) => {
    const dateA = a.dame_benevolat_data?.[0]?.date || '';
    const dateB = b.dame_benevolat_data?.[0]?.date || '';
    return dateA.localeCompare(dateB);
  });
});

/**
 * Appels ouverts (non expirés) - Tri Chronologique (Plus proche d'abord)
 */
const openBenevolats = computed(() => {
  return sortedList.value.filter(b => !isBenevolatExpired(b));
});

/**
 * Appels terminés (expirés) - Tri Antéchronologique (Plus récent terminé d'abord)
 */
const finishedBenevolats = computed(() => {
  const list = sortedList.value.filter(b => isBenevolatExpired(b));
  
  return [...list].sort((a, b) => {
    // On trie par la date de fin la plus récente en premier
    const datesA = a.dame_benevolat_data?.map(d => d.date).filter(Boolean) || [];
    const datesB = b.dame_benevolat_data?.map(d => d.date).filter(Boolean) || [];
    
    const maxDateA = datesA.reduce((max, d) => d > max ? d : max, '');
    const maxDateB = datesB.reduce((max, d) => d > max ? d : max, '');
    
    return maxDateB.localeCompare(maxDateA);
  });
});

/**
 * Actions
 */
const viewBenevolat = (benevolat: Benevolat) => {
  if (authStore.adminMode) {
    router.push('/tabs/admin/benevolat/' + benevolat.id);
  } else {
    if (authStore.isAuthenticated) {
      router.push('/tabs/benevolat/participation/' + benevolat.id);
    } else {
      router.push({ 
        path: '/tabs/login', 
        query: { message: 'Identification requise pour proposer votre aide.' } 
      });
    }
  }
};

const viewResults = (benevolat: Benevolat) => {
  if (authStore.adminMode) {
    router.push('/tabs/admin/benevolat/' + benevolat.id);
  } else {
    if (authStore.isAuthenticated) {
      router.push('/tabs/benevolat/participation/' + benevolat.id);
    } else {
      router.push({ 
        path: '/tabs/login', 
        query: { message: 'Identification requise pour proposer votre aide.' } 
      });
    }
  }
};

// Chargement des données au montage/entrée
onIonViewWillEnter(() => {
  benevolatStore.fetchBenevolatsData();
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

ion-list { margin-top: 8px; }
ion-list-header { 
  --color: var(--ion-color-primary);
  font-weight: bold;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  font-size: 0.85em;
  margin-bottom: 4px;
}
ion-badge { padding: 6px 10px; border-radius: 8px; cursor: pointer; }
h2 { font-weight: bold; }

.finished-item {
  --opacity: 0.8;
}

.finished-item h2 {
  color: var(--ion-color-medium);
}
</style>
