<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Administration</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="logout">
            <ion-icon slot="icon-only" :icon="logOutOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Administration</ion-title>
        </ion-toolbar>
      </ion-header>

      <div class="dashboard-section">
        <ion-list-header>
          <ion-label>Prochains Anniversaires</ion-label>
        </ion-list-header>

        <!-- État de chargement -->
        <div v-if="isLoading" class="ion-text-center ion-padding">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement des données...</p>
        </div>

        <!-- Liste des anniversaires -->
        <ion-list v-else-if="birthdays.length > 0" inset="true">
          <ion-item v-for="birthday in birthdays" :key="birthday.id">
            <ion-icon slot="start" :icon="giftOutline" color="primary"></ion-icon>
            <ion-label>
              <h2>{{ birthday.name }}</h2>
              <p>{{ formatDate(birthday.date) }}</p>
            </ion-label>
            <ion-badge slot="end" color="secondary">
              {{ birthday.next_age }} ans
            </ion-badge>
          </ion-item>
        </ion-list>

        <!-- Liste vide ou Erreur -->
        <div v-else class="ion-text-center ion-padding">
          <p>{{ errorMessage || 'Aucun anniversaire à venir.' }}</p>
          <ion-button v-if="errorMessage" fill="clear" @click="fetchBirthdays">
            Réessayer
          </ion-button>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonList,
  IonItem,
  IonLabel,
  IonListHeader,
  IonIcon,
  IonBadge,
  IonSpinner,
  IonButtons,
  IonButton,
  onIonViewWillEnter
} from '@ionic/vue';
import { giftOutline, logOutOutline } from 'ionicons/icons';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

// Types
interface Birthday {
  id: number;
  name: string;
  date: string;
  days_until: number;
  next_age: number;
}

const router = useRouter();
const birthdays = ref<Birthday[]>([]);
const isLoading = ref(true);
const errorMessage = ref('');

/**
 * Récupère les prochains anniversaires depuis l'API WordPress
 */
const fetchBirthdays = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  
  const token = localStorage.getItem('dame_jwt_token');

  if (!token) {
    errorMessage.value = "Session expirée. Veuillez vous reconnecter.";
    router.push('/login');
    return;
  }

  try {
    const response = await fetch('http://echecs.local/wp-json/dame/v1/birthdays/upcoming?limit=5', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    if (response.status === 401 || response.status === 403) {
      throw new Error("Votre session a expiré.");
    }

    if (!response.ok) {
      throw new Error("Erreur lors de la récupération des données.");
    }

    birthdays.value = await response.json();
  } catch (error: any) {
    errorMessage.value = error.message;
    if (error.message.includes("expiré")) {
       logout();
    }
  } finally {
    isLoading.value = false;
  }
};

/**
 * Formatage simple de la date (YYYY-MM-DD -> DD/MM/YYYY)
 */
const formatDate = (dateString: string) => {
  const [year, month, day] = dateString.split('-');
  return `${day}/${month}/${year}`;
};

/**
 * Déconnexion
 */
const logout = () => {
  localStorage.removeItem('dame_jwt_token');
  router.push('/login');
};

// Charger les données à chaque entrée sur la vue
onIonViewWillEnter(() => {
  fetchBirthdays();
});
</script>

<style scoped>
.dashboard-section {
  margin-top: 10px;
}

ion-list-header {
  --color: var(--ion-color-primary);
  font-weight: bold;
  font-size: 1.1em;
}

ion-badge {
  font-size: 0.9em;
  padding: 5px 10px;
}
</style>
