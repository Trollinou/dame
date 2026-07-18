<template>
  <ion-page>
    <ion-split-pane content-id="main-content">
      <!-- Menu Latéral Admin -->
      <ion-menu content-id="main-content" type="overlay">
        <ion-header>
          <ion-toolbar color="primary">
            <ion-title>Administration</ion-title>
          </ion-toolbar>
        </ion-header>

        <ion-content>
          <ion-list>
            <ion-menu-toggle :auto-hide="false">
              <ion-item 
                v-for="page in adminPages" 
                :key="page.title" 
                :router-link="page.url" 
                router-direction="root" 
                :detail="false"
                class="menu-item"
                :class="{ active: currentPath.startsWith(page.url) }"
              >
                <ion-icon slot="start" :icon="page.icon"></ion-icon>
                <ion-label>{{ page.title }}</ion-label>
              </ion-item>
            </ion-menu-toggle>
          </ion-list>

          <div class="menu-footer">
            <ion-menu-toggle :auto-hide="false">
              <ion-button expand="block" fill="outline" color="primary" @click="goToPublic">
                <ion-icon slot="start" :icon="eyeOutline"></ion-icon>
                Retour Espace Public
              </ion-button>
            </ion-menu-toggle>
          </div>
        </ion-content>
      </ion-menu>

      <!-- Contenu Principal -->
      <div class="ion-page" id="main-content">
        <ion-router-outlet></ion-router-outlet>
      </div>
    </ion-split-pane>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonSplitPane,
  IonMenu,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonList,
  IonItem,
  IonIcon,
  IonLabel,
  IonMenuToggle,
  IonButton,
  IonRouterOutlet,
  onIonViewWillEnter
} from '@ionic/vue';
import {
  homeOutline,
  peopleOutline,
  callOutline,
  handRightOutline,
  chatbubbleOutline,
  eyeOutline
} from 'ionicons/icons';
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const currentPath = computed(() => route.path);

const adminPages = [
  { title: 'Tableau de bord', url: '/admin/dashboard', icon: homeOutline },
  { title: 'Adhérents', url: '/admin/members', icon: peopleOutline },
  { title: 'Contacts', url: '/admin/contact', icon: callOutline },
  { title: 'Appels à bénévoles', url: '/admin/benevolat', icon: handRightOutline },
  { title: 'Messages', url: '/admin/message', icon: chatbubbleOutline }
];

const goToPublic = () => {
  authStore.adminMode = false;
  window.location.hash = '/tabs/home';
  window.location.reload();
};

onIonViewWillEnter(() => {
  if (route.path) {
    router.replace(route.path);
  }
});
</script>

<style scoped>
ion-menu {
  --width: 280px;
}

.menu-item {
  margin: 8px;
  --border-radius: 8px;
}

.menu-item.active {
  --background: var(--ion-color-primary-light, #d2e3fc);
  color: var(--ion-color-primary, #3880ff);
}

.menu-footer {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px;
  background: var(--ion-background-color, #fff);
  border-top: 1px solid var(--ion-color-light, #f4f5f8);
}
</style>
