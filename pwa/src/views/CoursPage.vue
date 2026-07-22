<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/apprentissage"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ cours?.titre || 'Cours' }}</ion-title>
        <ion-buttons slot="end">
          <ion-button router-link="/tabs/apprentissage" router-direction="back">
            <ion-icon slot="icon-only" :icon="homeOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">{{ cours?.titre || 'Cours' }}</ion-title>
          </ion-toolbar>
        </ion-header>

        <div v-if="apprentissageStore.isLoading" class="ion-text-center ion-padding spinner-container">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement du cours...</p>
        </div>

        <div v-else-if="!cours" class="ion-text-center ion-padding error-container">
          <p>Cours introuvable.</p>
        </div>

        <div v-else class="playlist-container">
          <div class="cours-meta-card ion-padding">
            <h2>{{ cours.titre }}</h2>
            <p class="chapitre-badge" :style="{ backgroundColor: 'var(--ion-color-' + cours.chapitre_couleur + ')' }">
              Niveau {{ cours.niveau }} — {{ cours.chapitre_nom }}
            </p>
          </div>

          <ion-list lines="inset" class="playlist-list ion-margin-top">
            <ion-item
              v-for="(item, i) in cours.playlist"
              :key="item.id"
              :button="isUnlocked(i)"
              :disabled="!isUnlocked(i)"
              :router-link="isUnlocked(i) ? `/contenu/${item.id}` : undefined"
              class="playlist-item"
            >
              <ion-icon
                slot="start"
                :icon="item.type === 'roi_lecon' ? bookOutline : extensionPuzzleOutline"
                color="primary"
              ></ion-icon>
              
              <ion-label>
                <h2>{{ item.titre || (item.type === 'roi_lecon' ? 'Leçon' : 'Exercice') + ' #' + (i + 1) }}</h2>
                <p v-if="!isUnlocked(i)">Verrouillé</p>
                <p v-else-if="isValidated(item.id)" class="success-text">Complété</p>
                <p v-else>Disponible</p>
              </ion-label>

              <ion-icon
                v-if="!isUnlocked(i)"
                slot="end"
                :icon="lockClosedOutline"
                color="medium"
              ></ion-icon>
              <ion-icon
                v-else-if="isValidated(item.id)"
                slot="end"
                :icon="checkmarkCircleOutline"
                color="success"
              ></ion-icon>
              <ion-icon
                v-else
                slot="end"
                :icon="chevronForwardOutline"
                color="medium"
              ></ion-icon>
            </ion-item>
          </ion-list>
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
  IonButtons,
  IonBackButton,
  IonTitle,
  IonContent,
  IonSpinner,
  IonList,
  IonItem,
  IonLabel,
  IonIcon,
  IonButton
} from '@ionic/vue';
import { computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useApprentissageStore } from '@/stores/apprentissage';
import {
  lockClosedOutline,
  checkmarkCircleOutline,
  chevronForwardOutline,
  bookOutline,
  extensionPuzzleOutline,
  homeOutline
} from 'ionicons/icons';

const route = useRoute();
const apprentissageStore = useApprentissageStore();

const coursId = computed(() => {
  const idVal = route.params.id;
  return parseInt(Array.isArray(idVal) ? idVal[0] : idVal, 10);
});

const coursIndex = computed(() => {
  return apprentissageStore.parcours.findIndex(c => c.id === coursId.value);
});

const cours = computed(() => {
  if (coursIndex.value !== -1) {
    return apprentissageStore.parcours[coursIndex.value];
  }
  return null;
});

watch(
  () => cours.value,
  (newCours) => {
    if (newCours?.id) {
      apprentissageStore.prefetchCoursContenus(newCours.id);
    }
  },
  { immediate: true }
);

const isUnlocked = (playlistIndex: number): boolean => {
  if (coursIndex.value === -1) return false;
  return apprentissageStore.isElementUnlocked(coursIndex.value, playlistIndex);
};

const isValidated = (itemId: number): boolean => {
  return apprentissageStore.elementsValides.includes(itemId);
};

onMounted(async () => {
  if (apprentissageStore.parcours.length === 0) {
    await apprentissageStore.fetchParcours();
  }
  if (apprentissageStore.elementsValides.length === 0) {
    await apprentissageStore.fetchProgression();
  }
  if (coursId.value) {
    apprentissageStore.prefetchCoursContenus(coursId.value);
  }
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.spinner-container, .error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
}

.playlist-container {
  max-width: 600px;
  margin: 0 auto;
}

.cours-meta-card {
  background: var(--ion-color-light);
  border-radius: 12px;
  text-align: center;
}

.cours-meta-card h2 {
  margin: 0 0 10px 0;
  font-weight: bold;
}

.chapitre-badge {
  display: inline-block;
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 600;
}

.playlist-list {
  background: transparent;
}

.playlist-item {
  --background: var(--ion-background-color);
  --border-radius: 8px;
  margin-bottom: 8px;
}

.success-text {
  color: var(--ion-color-success);
}
</style>
