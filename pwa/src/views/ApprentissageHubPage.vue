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

        <!-- ÉTAT CONNECTÉ ET AUTORISÉ -->
        <div v-if="authStore.isAuthenticated && authStore.canAccessApprentissage">
          <div v-if="apprentissageStore.isLoading" class="ion-text-center ion-padding spinner-container">
            <ion-spinner name="crescent"></ion-spinner>
            <p>Chargement des parcours...</p>
          </div>

          <div v-else-if="apprentissageStore.parcours.length === 0" class="ion-text-center ion-padding empty-container">
            <ion-icon :icon="schoolOutline" size="large" color="medium"></ion-icon>
            <p class="ion-margin-top">Aucun cours disponible pour le moment.</p>
          </div>

          <div v-else class="list-container">
            <ion-card
              v-for="(cours, index) in apprentissageStore.parcours"
              :key="cours.id"
              :color="cours.chapitre_couleur"
              :class="{ 'locked': !apprentissageStore.isCoursUnlocked(index) }"
              :button="apprentissageStore.isCoursUnlocked(index)"
              :router-link="apprentissageStore.isCoursUnlocked(index) ? `/cours/${cours.id}` : undefined"
              class="cours-card"
            >
              <ion-card-header>
                <div class="cours-header-wrapper">
                  <div>
                    <ion-card-subtitle>Niveau {{ cours.niveau }} — {{ cours.chapitre_nom }}</ion-card-subtitle>
                    <ion-card-title>{{ cours.titre }}</ion-card-title>
                  </div>
                  <ion-icon
                    v-if="!apprentissageStore.isCoursUnlocked(index)"
                    :icon="lockClosedOutline"
                    class="lock-icon"
                  ></ion-icon>
                </div>
              </ion-card-header>
            </ion-card>
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
  IonIcon,
  IonButton,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle
} from '@ionic/vue';
import { onMounted, watch } from 'vue';
import { useApprentissageStore } from '@/stores/apprentissage';
import { useAuthStore } from '@/stores/auth';
import {
  schoolOutline,
  checkmarkCircleOutline,
  logInOutline,
  shieldOutline,
  personOutline,
  lockClosedOutline
} from 'ionicons/icons';

const authStore = useAuthStore();
const apprentissageStore = useApprentissageStore();

const loadData = async () => {
  if (authStore.isAuthenticated && authStore.canAccessApprentissage) {
    await Promise.all([
      apprentissageStore.fetchParcours(),
      apprentissageStore.fetchProgression()
    ]);
  }
};

// Recharger les données si l'état de connexion ou les droits d'accès changent
watch(() => [authStore.isAuthenticated, authStore.canAccessApprentissage], () => {
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

.cours-card {
  margin-bottom: 16px;
}

.cours-card.locked {
  opacity: 0.75;
  pointer-events: none;
  cursor: not-allowed;
}

.cours-card.locked ion-card-header {
  opacity: 0.6;
}

.cours-header-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.lock-icon {
  font-size: 24px;
  color: var(--ion-color-medium);
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
