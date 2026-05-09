<template>
  <ion-page>
    <ion-tabs>
      <ion-router-outlet></ion-router-outlet>
      <ion-tab-bar slot="bottom">
        <ion-tab-button tab="home" href="/tabs/home">
          <ion-icon :icon="homeOutline" />
          <ion-label>Home</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="members" href="/tabs/members">
          <ion-icon :icon="peopleOutline" />
          <ion-label>Adhérent</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="contact" href="/tabs/contact">
          <ion-icon :icon="callOutline" />
          <ion-label>Contact</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="agenda" href="/tabs/agenda">
          <ion-icon :icon="calendarOutline" />
          <ion-label>Agenda</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="survey" href="/tabs/survey">
          <ion-icon :icon="statsChartOutline" />
          <ion-label>Sondage</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="message" href="/tabs/message">
          <ion-icon :icon="chatbubbleOutline" />
          <ion-label>Message</ion-label>
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
} from '@ionic/vue';
import {
  homeOutline,
  peopleOutline,
  callOutline,
  calendarOutline,
  statsChartOutline,
  chatbubbleOutline,
} from 'ionicons/icons';
</script>
