<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Tournois</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Tournois</ion-title>
        </ion-toolbar>
      </ion-header>

      <div v-if="isLoading" class="ion-text-center ion-padding">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Chargement des tournois...</p>
      </div>

      <div v-else-if="error" class="ion-text-center ion-padding">
        <p color="danger">{{ error }}</p>
        <ion-button fill="clear" @click="fetchMenu">Réessayer</ion-button>
      </div>

      <div v-else>
        <ion-card 
          v-for="item in topLevelItems" 
          :key="item.id" 
          class="tournament-card" 
          button 
          @click="goToPage(item.object_id)"
        >
          <ion-card-header>
            <div class="card-icon-container">
              <ion-icon :icon="trophyOutline" color="primary"></ion-icon>
            </div>
            <ion-card-subtitle>Compétition</ion-card-subtitle>
            <ion-card-title v-html="item.title"></ion-card-title>
          </ion-card-header>

          <ion-card-content>
            <p>Découvrez les détails, les horaires et les modalités d'inscription pour ce tournoi.</p>
            <div class="cta-container">
              <span class="cta-text">Voir les détails</span>
              <ion-icon :icon="chevronForwardOutline" size="small"></ion-icon>
            </div>
          </ion-card-content>
        </ion-card>

        <div v-if="topLevelItems.length === 0 && !isLoading" class="ion-text-center ion-padding">
          <p>Aucun tournoi disponible pour le moment.</p>
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
import { trophyOutline, chevronForwardOutline } from 'ionicons/icons';
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

interface MenuItem {
  id: number;
  title: string;
  object_id: number; // ID de la page WordPress
  parent: string | number;
}

const router = useRouter();
const menuItems = ref<MenuItem[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);

/**
 * Filtre pour ne garder que les items de premier niveau
 */
const topLevelItems = computed(() => {
  return menuItems.value.filter(item => String(item.parent) === "0");
});

const fetchMenu = async () => {
  isLoading.value = true;
  error.value = null;

  try {
    const apiUrl = import.meta.env.VITE_API_BASE_URL;
    // URL de l'API personnalisée dame/v1/pwa-menu
    const response = await fetch(`${apiUrl}/dame/v1/pwa-menu`);
    
    if (!response.ok) throw new Error("Impossible de charger le menu.");
    
    menuItems.value = await response.json();
  } catch (err: any) {
    error.value = err.message || "Une erreur est survenue.";
  } finally {
    isLoading.value = false;
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
.tournament-card {
  margin-bottom: 20px;
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
</style>
