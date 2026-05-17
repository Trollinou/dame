<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Connexion Dame</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="login-container">
        <div class="login-header">
          <h2>Bienvenue</h2>
          <p>Connectez-vous à votre dossier administratif</p>
        </div>

        <form @submit.prevent="handleSubmit">
          <ion-item lines="full" class="ion-margin-bottom">
            <ion-label position="stacked">Identifiant</ion-label>
            <ion-input
              v-model="credentials.username"
              type="text"
              placeholder="Nom d'utilisateur"
              required
            ></ion-input>
          </ion-item>

          <ion-item lines="full" class="ion-margin-bottom">
            <ion-label position="stacked">Mot de passe</ion-label>
            <ion-input
              v-model="credentials.password"
              type="password"
              placeholder="••••••••"
              required
            ></ion-input>
          </ion-item>

          <ion-button
            expand="block"
            type="submit"
            class="ion-margin-top"
            :disabled="isLoading"
          >
            {{ isLoading ? 'Connexion en cours...' : 'Se connecter' }}
          </ion-button>
          
          <div class="ion-text-center ion-margin-top">
            <ion-button fill="clear" router-link="/tabs/register" color="medium">
              Pas encore de compte ? S'inscrire
            </ion-button>
          </div>
        </form>
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
  IonItem,
  IonLabel,
  IonInput,
  IonButton
} from '@ionic/vue';
import { reactive } from 'vue';
import { useAuthStore } from '../stores/auth';
import { storeToRefs } from 'pinia';

const authStore = useAuthStore();
const { isLoading } = storeToRefs(authStore);

// État du formulaire
const credentials = reactive({
  username: '',
  password: ''
});

/**
 * Gère la soumission du formulaire
 */
const handleSubmit = () => {
  authStore.login(credentials.username, credentials.password);
};
</script>

<style scoped>
.login-container {
  max-width: 400px;
  margin: 40px auto;
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.login-header h2 {
  font-weight: 700;
  font-size: 24px;
}

.login-header p {
  color: var(--ion-color-medium);
}

ion-item {
  --padding-start: 0;
  --inner-padding-end: 0;
}
</style>
