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

        <ion-tab-button tab="apprentissage" href="/tabs/apprentissage">
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
  homeOutline,
  calendarOutline,
  schoolOutline,
  personOutline
} from 'ionicons/icons';
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
