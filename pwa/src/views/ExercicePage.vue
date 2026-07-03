<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/apprentissage"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ exerciceActuel?.titre || 'Exercice' }}</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">{{ exerciceActuel?.titre || 'Exercice' }}</ion-title>
          </ion-toolbar>
        </ion-header>

        <div v-if="isLoading" class="ion-text-center ion-padding spinner-container">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement de l'exercice...</p>
        </div>

        <div v-else-if="exerciceActuel" class="exercice-container">
          <component 
            v-if="getComposantExercice(exerciceActuel.type)"
            :is="getComposantExercice(exerciceActuel.type)" 
            :config="exerciceActuel.config" 
          />
          <div v-else class="ion-text-center ion-padding error-container">
            <p>Type d'exercice non supporté (Type {{ exerciceActuel.type }}).</p>
          </div>
        </div>

        <div v-else class="ion-text-center ion-padding error-container">
          <p>Impossible de charger cet exercice.</p>
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
  IonButtons,
  IonBackButton,
  IonSpinner
} from '@ionic/vue';
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useApprentissageStore } from '@/stores/apprentissage';
import TypeABCDaire from './types/TypeABCDaire.vue';

const route = useRoute();
const apprentissageStore = useApprentissageStore();
const isLoading = ref(true);

const exerciceActuel = computed(() => apprentissageStore.exerciceActuel);

const getComposantExercice = (type: number) => {
  if (type === 3) {
    return TypeABCDaire;
  }
  return null;
};

onMounted(async () => {
  const idStr = route.params.id;
  const id = parseInt(Array.isArray(idStr) ? idStr[0] : idStr, 10);
  if (!isNaN(id)) {
    await apprentissageStore.fetchExercice(id);
  }
  isLoading.value = false;
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

.exercice-container {
  max-width: 600px;
  margin: 0 auto;
}
</style>
