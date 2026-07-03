<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Apprentissage</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">Apprentissage</ion-title>
          </ion-toolbar>
        </ion-header>

        <div v-if="apprentissageStore.isLoadingListe" class="ion-text-center ion-padding spinner-container">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement des exercices...</p>
        </div>

        <div v-else-if="apprentissageStore.listeExercices.length === 0" class="ion-text-center ion-padding empty-container">
          <ion-icon :icon="extensionPuzzleOutline" size="large" color="medium"></ion-icon>
          <p class="ion-margin-top">Aucun exercice disponible pour le moment.</p>
        </div>

        <div v-else class="list-container">
          <ion-list lines="none">
            <ion-item
              v-for="exercice in apprentissageStore.listeExercices"
              :key="exercice.id"
              button
              :detail="true"
              :router-link="`/exercice/${exercice.id}`"
              class="exercise-item"
            >
              <ion-icon slot="start" :icon="extensionPuzzleOutline" color="primary"></ion-icon>
              <ion-label>
                <h2>{{ exercice.titre || 'Exercice sans titre' }}</h2>
                <p>{{ getNomTypeExercice(exercice.type) }}</p>
              </ion-label>
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
  IonTitle,
  IonContent,
  IonSpinner,
  IonList,
  IonItem,
  IonLabel,
  IonIcon
} from '@ionic/vue';
import { onMounted } from 'vue';
import { useApprentissageStore } from '@/stores/apprentissage';
import { extensionPuzzleOutline, chevronForwardOutline } from 'ionicons/icons';

const apprentissageStore = useApprentissageStore();

const getNomTypeExercice = ( type: number ): string => {
	const types: Record< number, string > = {
		1: "100 Commandements",
		2: "Pop'Echecs",
		3: "ABCDaire Tactique",
		4: "La Partie dont tu es le Héros",
		5: "Posi'Plan",
		6: "Associ'Plan",
		7: "Marche du Héros",
		8: "Vision'checs",
		9: "Parcours",
		10: "Echec'éval",
		11: "Class'échecs",
		12: "Qui-suis-je ?",
		13: "Ouvre'boite / Cap / Jugement",
	};
	return types[ type ] || "Exercice standard";
};

onMounted(async () => {
  await apprentissageStore.fetchListeExercices();
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.spinner-container, .empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
}

.list-container {
  max-width: 600px;
  margin: 0 auto;
  padding-top: 10px;
}

.exercise-item {
  --background: var(--ion-color-light);
  --border-radius: 12px;
  margin-bottom: 12px;
  --padding-start: 16px;
  --padding-end: 16px;
  --inner-padding-end: 0;
}

.exercise-item h2 {
  font-weight: bold;
}
</style>
