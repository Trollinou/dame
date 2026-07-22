<template>
  <ion-page>
    <ion-tabs>
      <ion-router-outlet></ion-router-outlet>
      <ion-tab-bar slot="bottom">
        <ion-tab-button tab="home" href="/tabs/home">
          <ion-icon :icon="homeOutline" />
          <ion-label>Accueil</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="agenda" href="/tabs/agenda">
          <ion-icon :icon="calendarOutline" />
          <ion-label>Le Club</ion-label>
        </ion-tab-button>

        <ion-tab-button v-if="authStore.canAccessApprentissage" tab="apprentissage" href="/tabs/apprentissage">
          <ion-icon :icon="schoolOutline" />
          <ion-label>Apprentissage</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="profil" href="/tabs/profil">
          <ion-icon :icon="personOutline" />
          <ion-label>Profil</ion-label>
        </ion-tab-button>
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import {
  IonPage,
  IonTabs,
  IonRouterOutlet,
  IonTabBar,
  IonTabButton,
  IonIcon,
  IonLabel,
  onIonViewWillEnter
} from '@ionic/vue';
import {
  homeOutline,
  calendarOutline,
  schoolOutline,
  personOutline
} from 'ionicons/icons';

const route = useRoute();
const authStore = useAuthStore();

const syncState = () => {
  if (route.path.startsWith('/tabs')) {
    authStore.adminMode = false;
  }
};

onIonViewWillEnter(() => {
  syncState();
});

watch(
  () => route.path,
  () => {
    syncState();
  },
  { immediate: true }
);
</script>

<style scoped>
ion-tab-bar {
  --border: 0;
}

ion-tab-button {
  --padding-start: 0px;
  --padding-end: 0px;
  min-width: 0;
}

ion-icon {
  font-size: 20px;
}

ion-label {
  font-size: 9px;
  letter-spacing: -0.2px;
  white-space: nowrap;
}
</style>
