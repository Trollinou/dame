<template>
  <ion-page>
    <ion-tabs>
      <ion-router-outlet></ion-router-outlet>
      <ion-tab-bar slot="bottom">
        
        <!-- MODE UTILISATEUR / PUBLIC (Si adminMode est FALSE) -->
        <template v-if="!authStore.adminMode">
          <ion-tab-button tab="home" href="/tabs/home">
            <ion-icon :icon="homeOutline" />
            <ion-label>Accueil</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="news" href="/tabs/news">
            <ion-icon :icon="newspaperOutline" />
            <ion-label>Actualités</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="agenda" href="/tabs/agenda">
            <ion-icon :icon="calendarOutline" />
            <ion-label>Agenda</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="tournoi" href="/tabs/tournoi">
            <ion-icon :icon="trophyOutline" />
            <ion-label>Tournoi</ion-label>
          </ion-tab-button>
        </template>

        <!-- MODE ADMINISTRATION (Si adminMode est TRUE) -->
        <template v-else>
          <ion-tab-button tab="admin-dashboard" href="/tabs/admin/dashboard">
            <ion-icon :icon="homeOutline" />
            <ion-label>Dashboard</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="admin-members" href="/tabs/admin/members">
            <ion-icon :icon="peopleOutline" />
            <ion-label>Adhérents</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="admin-contact" href="/tabs/admin/contact">
            <ion-icon :icon="callOutline" />
            <ion-label>Contacts</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="admin-survey" href="/tabs/admin/survey">
            <ion-icon :icon="statsChartOutline" />
            <ion-label>Sondages</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="admin-message" href="/tabs/admin/message">
            <ion-icon :icon="chatbubbleOutline" />
            <ion-label>Messages</ion-label>
          </ion-tab-button>
        </template>

        <!-- Switch Admin / Public (Visible uniquement si Staff connecté) -->
        <ion-tab-button 
          v-if="authStore.isAuthenticated && authStore.isAdmin" 
          @click="toggleMode"
          class="admin-switch-button"
        >
          <!-- L'icône change dynamiquement selon le mode actuel -->
          <ion-icon :icon="authStore.adminMode ? eyeOffOutline : eyeOutline" color="primary" />
          <ion-label>{{ authStore.adminMode ? 'Public' : 'Admin' }}</ion-label>
        </ion-tab-button>
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonTabs,
  IonRouterOutlet,
  IonTabBar,
  IonTabButton,
  IonIcon,
  IonLabel,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonButtons,
  IonButton,
  IonToggle
} from '@ionic/vue';
import {
  newspaperOutline,
  calendarOutline,
  trophyOutline,
  peopleOutline,
  callOutline,
  chatbubbleOutline,
  personOutline,
  settingsOutline,
  logOutOutline,
  exitOutline,
  statsChartOutline,
  homeOutline,
  personCircleOutline,
  eyeOutline,
  eyeOffOutline,
  powerOutline
} from 'ionicons/icons';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const router = useRouter();

/**
 * Gère le changement de mode Admin via le toggle
 */
const onToggleAdminMode = () => {
  if (authStore.adminMode) {
    router.push('/tabs/admin/dashboard');
  } else {
    router.push('/tabs/home');
  }
};

const toggleMode = () => {
  // On inverse le mode
  authStore.adminMode = !authStore.adminMode;
  
  // Redirection automatique
  if (authStore.adminMode) {
    router.push('/tabs/admin/dashboard');
  } else {
    router.push('/tabs/home');
  }
};

/**
 * Gère la déconnexion
 */
const handleLogout = async () => {
  await authStore.logout();
};
</script>

<style scoped>
.tab-admin-switch {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  min-width: 50px;
  height: 100%;
  cursor: pointer;
  /* Retrait du padding safe-area qui décalait l'alignement sur navigateur */
}

ion-toggle {
  --handle-width: 12px;
  --handle-height: 12px;
  --track-background: var(--ion-color-light-shade);
  --track-background-checked: var(--ion-color-primary);
  margin: 0;
  padding: 0;
  /* On remonte le toggle pour qu'il soit au niveau des icônes */
  margin-top: -4px; 
}

.toggle-label {
  font-size: 10px;
  font-weight: 500;
  color: var(--ion-color-medium);
  margin-top: 2px;
  /* Assure que le texte est bien sur une seule ligne */
  white-space: nowrap;
}

/* Ajustement précis du rail du toggle */
ion-toggle::part(track) {
  width: 26px;
  height: 14px;
}

.admin-toggle-btn {
  --color-selected: var(--ion-color-warning); /* Une couleur distincte comme l'ambre */
  --color: var(--ion-color-primary);
  font-weight: bold;
}
</style>

