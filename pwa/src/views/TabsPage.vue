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

          <ion-tab-button tab="benevolat" href="/tabs/benevolat">
            <ion-icon :icon="handRightOutline" />
            <ion-label>Bénévolat</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="tournoi" href="/tabs/tournoi">
            <ion-icon :icon="trophyOutline" />
            <ion-label>Tournois</ion-label>
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
            <ion-label>Membres</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="admin-contact" href="/tabs/admin/contact">
            <ion-icon :icon="callOutline" />
            <ion-label>Contacts</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="benevolat" href="/tabs/benevolat">
            <ion-icon :icon="handRightOutline" />
            <ion-label>Bénévolat</ion-label>
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
  IonLabel
} from '@ionic/vue';
import {
  newspaperOutline,
  calendarOutline,
  trophyOutline,
  peopleOutline,
  callOutline,
  chatbubbleOutline,
  handRightOutline,
  homeOutline,
  eyeOutline,
  eyeOffOutline
} from 'ionicons/icons';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const toggleMode = () => {
  authStore.adminMode = !authStore.adminMode;
  if (authStore.adminMode) {
    router.push('/tabs/admin/dashboard');
  } else {
    router.push('/tabs/home');
  }
};
</script>

<style scoped>
ion-tab-bar {
  --border: 0;
  /* On laisse Ionic gérer le flex par défaut pour éviter les bugs iOS */
}

ion-tab-button {
  --padding-start: 0px;
  --padding-end: 0px;
  min-width: 0;
}

ion-icon {
  font-size: 20px; /* Taille intermédiaire */
}

ion-label {
  font-size: 9px;
  letter-spacing: -0.2px;
  white-space: nowrap;
}

.admin-switch-button {
  min-width: 45px;
}

/* On garde le reste des styles pour le toggle s'il est utilisé ailleurs, 
   mais ici on utilise des ion-tab-button standard */
</style>
