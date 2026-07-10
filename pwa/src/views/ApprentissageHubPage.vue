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

        <!-- ÉTAT CONNECTÉ ET ADHÉRENT -->
        <div v-if="authStore.isAuthenticated && authStore.isAdherent">
          <div v-if="apprentissageStore.isLoadingListe" class="ion-text-center ion-padding spinner-container">
            <ion-spinner name="crescent"></ion-spinner>
            <p>Chargement des exercices...</p>
          </div>

          <div v-else-if="apprentissageStore.listeExercices.length === 0" class="ion-text-center ion-padding empty-container">
            <ion-icon :icon="extensionPuzzleOutline" size="large" color="medium"></ion-icon>
            <p class="ion-margin-top">Aucun exercice disponible pour le moment.</p>
          </div>

          <div v-else class="list-container">
            <div v-for="(chapitres, niveau) in apprentissageStore.exercicesGroupes" :key="niveau" class="niveau-section">
              <h2 class="niveau-title">Niveau {{ niveau }}</h2>
              <ion-accordion-group>
                <ion-accordion
                  v-for="(group, chapitreNom) in chapitres"
                  :key="chapitreNom"
                  :value="chapitreNom"
                  :toggle-icon="chevronDownOutline"
                >
                  <ion-item slot="header" :color="group.couleur" class="chapitre-header">
                    <ion-label class="chapitre-label">{{ chapitreNom }}</ion-label>
                  </ion-item>
                  <div slot="content" class="chapitre-content">
                    <ion-list lines="none">
                      <ion-item
                        v-for="exercice in group.exercices"
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
                </ion-accordion>
              </ion-accordion-group>
            </div>
          </div>
        </div>

        <!-- ÉTAT CONNECTÉ MAIS NON ADHÉRENT -->
        <div v-else-if="authStore.isAuthenticated" class="empty-state-container">
          <div class="empty-state-content">
            <ion-icon :icon="shieldOutline" class="illustration-icon warning-color"></ion-icon>
            <h2>Accès Adhérent requis</h2>
            <p>Le module d'apprentissage et les exercices tactiques sont réservés aux membres adhérents du club Échiquier Lédonien.</p>
            <div class="benefits-list">
              <div class="benefit-item">
                <ion-icon :icon="checkmarkCircleOutline" color="success"></ion-icon>
                <span>Sélectionnez un profil adhérent actif depuis votre compte.</span>
              </div>
            </div>
            <ion-button expand="block" router-link="/tabs/profil" class="ion-margin-top">
              <ion-icon slot="start" :icon="personOutline"></ion-icon>
              Gérer mon profil
            </ion-button>
          </div>
        </div>

        <!-- ÉTAT DÉCONNECTÉ (Divulgation progressive / Empty State) -->
        <div v-else class="empty-state-container">
          <div class="empty-state-content">
            <ion-icon :icon="schoolOutline" class="illustration-icon"></ion-icon>
            <h2>Progressez aux Échecs</h2>
            <p>Accédez à notre plateforme d'entraînement interactive pour développer votre vision de jeu et parfaire votre technique.</p>
            
            <div class="benefits-list">
              <div class="benefit-item">
                <ion-icon :icon="checkmarkCircleOutline" color="success"></ion-icon>
                <span>Plus de 15 types d'exercices tactiques originaux</span>
              </div>
              <div class="benefit-item">
                <ion-icon :icon="checkmarkCircleOutline" color="success"></ion-icon>
                <span>Exercices progressifs organisés par niveau et par chapitre</span>
              </div>
              <div class="benefit-item">
                <ion-icon :icon="checkmarkCircleOutline" color="success"></ion-icon>
                <span>Suivi de votre apprentissage en temps réel</span>
              </div>
            </div>

            <ion-button expand="block" router-link="/tabs/profil" class="ion-margin-top">
              <ion-icon slot="start" :icon="logInOutline"></ion-icon>
              Se connecter pour s'entraîner
            </ion-button>
          </div>
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
  IonIcon,
  IonAccordion,
  IonAccordionGroup,
  IonButton
} from '@ionic/vue';
import { onMounted, watch } from 'vue';
import { useApprentissageStore } from '@/stores/apprentissage';
import { useAuthStore } from '@/stores/auth';
import {
  extensionPuzzleOutline,
  chevronDownOutline,
  schoolOutline,
  checkmarkCircleOutline,
  logInOutline,
  shieldOutline,
  personOutline
} from 'ionicons/icons';

const authStore = useAuthStore();
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
		13: "Ouvre'boite",
		14: "Cap ou pas cap ?",
		15: "Jugement final",
		16: "Destination finale",
	};
	return types[ type ] || "Exercice standard";
};

const loadData = async () => {
  if (authStore.isAuthenticated && authStore.isAdherent) {
    await apprentissageStore.fetchListeExercices();
  }
};

// Recharger les données si l'état de connexion ou le profil change
watch(() => [authStore.isAuthenticated, authStore.isAdherent], () => {
  loadData();
});

onMounted(async () => {
  await loadData();
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

.niveau-section {
  margin-bottom: 24px;
}

.niveau-title {
  font-size: 1.4rem;
  font-weight: 800;
  margin-top: 20px;
  margin-bottom: 12px;
  color: var(--ion-color-dark);
  padding-left: 4px;
}

.chapitre-header {
  --font-weight: bold;
  border-radius: 8px;
  overflow: hidden;
  margin-bottom: 4px;
}

.chapitre-label {
  font-weight: bold;
}

.chapitre-content {
  padding: 8px;
  background: var(--ion-background-color);
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

/* Styles Empty State */
.empty-state-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 200px);
  padding: 24px;
  box-sizing: border-box;
}

.empty-state-content {
  max-width: 400px;
  width: 100%;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.illustration-icon {
  font-size: 96px;
  color: var(--ion-color-primary);
  margin-bottom: 24px;
}

.illustration-icon.warning-color {
  color: var(--ion-color-warning);
}

.empty-state-content h2 {
  font-size: 24px;
  font-weight: 800;
  margin: 0 0 12px 0;
  color: var(--ion-color-dark);
}

.empty-state-content p {
  font-size: 15px;
  line-height: 1.5;
  color: var(--ion-color-step-600, #666);
  margin: 0 0 24px 0;
}

.benefits-list {
  text-align: left;
  background: var(--ion-color-light, #f4f5f8);
  border-radius: 12px;
  padding: 16px;
  width: 100%;
  box-sizing: border-box;
  margin-bottom: 24px;
}

.benefit-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 12px;
}

.benefit-item:last-child {
  margin-bottom: 0;
}

.benefit-item ion-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.benefit-item span {
  font-size: 14px;
  color: var(--ion-color-step-800, #333);
  line-height: 1.4;
}
</style>
