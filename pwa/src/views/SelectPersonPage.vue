<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start" v-if="authStore.selectedIdentity">
          <ion-back-button default-href="/tabs/home"></ion-back-button>
        </ion-buttons>
        <ion-title>Qui êtes-vous ?</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <div class="selection-container">
          <div class="selection-header">
            <ion-icon :icon="peopleOutline" color="primary" style="font-size: 3rem;"></ion-icon>
            <h2>Plusieurs profils trouvés</h2>
            <p>Cet email est lié à plusieurs membres. Veuillez sélectionner la personne qui souhaite agir ou voter.</p>
          </div>

          <div v-if="isLoading" class="ion-text-center ion-padding">
            <ion-spinner name="crescent"></ion-spinner>
            <p>Récupération des profils...</p>
          </div>

          <ion-list v-else lines="none">
            <ion-item 
              v-for="identity in identities" 
              :key="identity.id" 
              button 
              @click="handleSelect(identity)"
              class="identity-card"
            >
              <ion-icon slot="start" :icon="personOutline" color="primary"></ion-icon>
              <ion-label>
                <h2>{{ identity.name }}</h2>
                <p>{{ identity.type === 'representative' ? 'Responsable Légal' : 'Adhérent' }}</p>
              </ion-label>
              <ion-icon slot="end" :icon="chevronForwardOutline" color="medium"></ion-icon>
            </ion-item>
          </ion-list>

          <div class="ion-margin-top ion-text-center">
            <ion-button fill="clear" color="medium" @click="authStore.logout()">
              Annuler et se déconnecter
            </ion-button>
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
  IonList,
  IonItem,
  IonLabel,
  IonIcon,
  IonSpinner,
  IonButton,
  IonButtons,
  IonBackButton
} from '@ionic/vue';
import { 
  peopleOutline, 
  personOutline, 
  chevronForwardOutline 
} from 'ionicons/icons';
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore, type Identity } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const identities = ref<Identity[]>([]);
const isLoading = ref(true);

const fetchIdentities = async () => {
  isLoading.value = true;
  try {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/dame/v1/my-identities`, {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    });
    if (response.ok) {
      identities.value = await response.json();
      // Si par hasard on arrive ici alors qu'il n'y a plus qu'un profil
      if (identities.value.length === 1) {
        handleSelect(identities.value[0]);
      }
    } else {
      router.push('/tabs/home');
    }
  } catch (error) {
    console.error("Erreur chargement identités:", error);
    router.push('/tabs/home');
  } finally {
    isLoading.value = false;
  }
};

const handleSelect = (identity: Identity) => {
  authStore.selectIdentity(identity);
  router.push('/tabs/home');
};

onMounted(() => {
  if (!authStore.isAuthenticated) {
    router.push('/login');
    return;
  }
  fetchIdentities();
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.selection-container {
  max-width: 500px;
  margin: 0 auto;
  padding-top: 20px;
}

.selection-header {
  text-align: center;
  margin-bottom: 30px;
}

.selection-header h2 {
  font-weight: 700;
}

.selection-header p {
  color: var(--ion-color-medium);
}

.identity-card {
  --background: var(--ion-color-light);
  --border-radius: 12px;
  margin-bottom: 12px;
  --padding-start: 16px;
  --padding-end: 16px;
  --inner-padding-end: 0;
}

.identity-card h2 {
  font-weight: bold;
}
</style>
