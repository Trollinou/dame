<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Tournois</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <!-- Wrapper respectant la Dynamic Island sans ajouter de marges excessives -->
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">Tournois</ion-title>
          </ion-toolbar>
        </ion-header>

        <div v-if="tournamentStore.isLoading" class="ion-text-center ion-padding">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement des tournois...</p>
        </div>

        <div v-else-if="error && topLevelItems.length === 0" class="ion-text-center ion-padding offline-container">
          <ion-icon :icon="cloudOfflineOutline" size="large" color="medium"></ion-icon>
          <p class="ion-margin-top">{{ error }}</p>
          <ion-button fill="solid" class="ion-margin-top" @click="fetchMenu">Réessayer</ion-button>
        </div>

        <div v-else>
          <!-- Message d'avertissement si on affiche des données en cache alors qu'on est hors-ligne -->
          <div v-if="error && topLevelItems.length > 0" class="offline-banner">
            <ion-icon :icon="cloudOfflineOutline"></ion-icon>
            <span>Mode hors-ligne : Affichage des données en cache</span>
          </div>
          <ion-card 
            v-for="item in topLevelItems" 
            :key="item.id" 
            class="tournament-card ion-no-margin ion-margin-bottom" 
            button 
            @click="goToPage(item.object_id)"
          >
            <ion-card-header>
              <div class="card-icon-container">
                <ion-icon :icon="trophyOutline" color="primary"></ion-icon>
              </div>
              <ion-card-subtitle>Compétition</ion-card-subtitle>
              <ion-card-title v-safe-html="item.title"></ion-card-title>
            </ion-card-header>

            <ion-card-content>
              <p>Découvrez les détails, les horaires et les modalités d'inscription pour ce tournoi.</p>
              <div class="cta-container">
                <span class="cta-text">Voir les détails</span>
                <ion-icon :icon="chevronForwardOutline" size="small"></ion-icon>
              </div>
            </ion-card-content>
          </ion-card>

          <div v-if="topLevelItems.length === 0 && !tournamentStore.isLoading" class="ion-text-center ion-padding">
            <p>Aucun tournoi disponible pour le moment.</p>
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
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent,
  IonIcon,
  IonSpinner,
  IonButton,
  onIonViewWillEnter
} from '@ionic/vue';
import { trophyOutline, chevronForwardOutline, cloudOfflineOutline } from 'ionicons/icons';
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useTournamentStore } from '@/stores/tournament';

const router = useRouter();
const tournamentStore = useTournamentStore();
const error = ref<string | null>(null);

/**
 * Filtre pour ne garder que les items de premier niveau
 */
const topLevelItems = computed(() => {
  return tournamentStore.menuItems.filter(item => String(item.parent) === "0");
});

const fetchMenu = async () => {
  error.value = null;

  try {
    await tournamentStore.fetchMenu();
  } catch (err: any) {
    if (!navigator.onLine) {
      error.value = "Vous êtes hors-ligne. Les informations sur les tournois nécessitent une connexion internet pour être mises à jour.";
    } else {
      error.value = "Impossible de charger les tournois. Veuillez vérifier votre connexion.";
    }
  }
};

const goToPage = (id: number) => {
  router.push(`/tabs/page/${id}`);
};

onIonViewWillEnter(() => {
  fetchMenu();
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.tournament-card {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-icon-container {
  font-size: 2.5rem;
  margin-bottom: 10px;
}

ion-card-title {
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1.2;
}

ion-card-subtitle {
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--ion-color-medium);
}

.cta-container {
  display: flex;
  align-items: center;
  margin-top: 15px;
  color: var(--ion-color-primary);
  font-weight: 600;
}

.cta-text {
  margin-right: 5px;
}

.offline-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-top: 50px;
}

.offline-banner {
  background: var(--ion-color-warning);
  color: var(--ion-color-warning-contrast);
  padding: 8px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 15px;
}
</style>
