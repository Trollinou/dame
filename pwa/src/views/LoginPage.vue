<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Connexion Echiquier Lédonien</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <div class="login-container">
          <!-- Message de redirection contextuel -->
          <div v-if="redirectMessage" class="redirect-banner">
            <ion-icon :icon="informationCircleOutline" color="primary"></ion-icon>
            <p>{{ redirectMessage }}</p>
          </div>

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
  IonButton,
  IonIcon,
  onIonViewWillLeave
} from '@ionic/vue';
import { informationCircleOutline } from 'ionicons/icons';
import { reactive, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { storeToRefs } from 'pinia';

const route = useRoute();
const authStore = useAuthStore();
const { isLoading } = storeToRefs(authStore);

// Récupération du message de redirection depuis les paramètres d'URL
const redirectMessage = computed(() => route.query.message as string);

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

/**
 * Sécurité Accessibilité : Retire le focus de l'élément actif lors du changement de page
 * Évite l'avertissement "Blocked aria-hidden on an element because its descendant retained focus"
 */
onIonViewWillLeave(() => {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur();
  }
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.login-container {
  max-width: 400px;
  margin: 40px auto;
}

.redirect-banner {
  display: flex;
  align-items: center;
  background: var(--ion-color-primary-contrast, #f4f5f8);
  border: 1px solid var(--ion-color-primary);
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 24px;
  gap: 12px;
}

.redirect-banner ion-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.redirect-banner p {
  margin: 0;
  font-size: 0.9em;
  color: var(--ion-color-dark);
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
