<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>
          <div style="display: flex; align-items: center;">
            <img src="/assets/icon/queen.svg" style="height: 24px; margin-right: 10px;" alt="Logo Dame" />
            Dame
          </div>
        </ion-title>
        <ion-buttons slot="end">
          <ion-button @click="logout">
            <ion-icon slot="icon-only" :icon="logOutOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="dashboard-section">
        <ion-list-header>
          <ion-label>Prochains Anniversaires</ion-label>
        </ion-list-header>

        <!-- État de chargement (Spinner bloquant uniquement si vide) -->
        <div v-if="isLoading && birthdays.length === 0" class="ion-text-center ion-padding">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement des données...</p>
        </div>

        <!-- Liste des anniversaires -->
        <ion-list v-else-if="birthdays.length > 0" :inset="true">
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

        <!-- Liste vide -->
        <div v-else class="ion-text-center ion-padding">
          <p>Aucun anniversaire à venir.</p>
          <ion-button fill="clear" @click="dashboardStore.fetchBirthdays(true)">
            Rafraîchir
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
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';

// Import des stores
import { useDashboardStore } from '@/stores/dashboard';
import { useMemberStore } from '@/stores/members';
import { useAgendaStore } from '@/stores/agenda';
import { useContactStore } from '@/stores/contacts';
import { useMessageStore } from '@/stores/messages';
import { useSondageStore } from '@/stores/sondages';
import { useAuthStore } from '@/stores/auth';

// Initialisation des stores
const dashboardStore = useDashboardStore();
const memberStore = useMemberStore();
const agendaStore = useAgendaStore();
const contactStore = useContactStore();
const messageStore = useMessageStore();
const sondageStore = useSondageStore();
const authStore = useAuthStore();

const { birthdays, isLoading } = storeToRefs(dashboardStore);

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
  authStore.logout();
};

/**
 * Pré-chargement silencieux de TOUTES les données de l'application
 */
const preloadAllData = () => {
  dashboardStore.fetchBirthdays();
  memberStore.fetchMembers();
  memberStore.fetchSeasons();
  agendaStore.fetchAgenda();
  contactStore.fetchContacts();
  contactStore.fetchContactTypes();
  messageStore.fetchMessages();
  sondageStore.fetchSondagesData();
};

// Rafraîchir les données du dashboard à chaque entrée (gestion silencieuse par le store)
onIonViewWillEnter(() => {
  preloadAllData();
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
