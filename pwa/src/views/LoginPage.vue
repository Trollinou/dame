<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Connexion DAME</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="login-container">
        <div class="login-header">
          <h2>Bienvenue</h2>
          <p>Connectez-vous à votre dossier administratif</p>
        </div>

        <form @submit.prevent="handleLogin">
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
  IonButton,
  toastController,
  alertController
} from '@ionic/vue';
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

// État du formulaire
const credentials = reactive({
  username: '',
  password: ''
});

const isLoading = ref(false);

/**
 * Gère la soumission du formulaire de connexion
 */
const handleLogin = async () => {
  if (!credentials.username || !credentials.password) return;

  isLoading.value = true;

  try {
    const response = await fetch('http://echecs.local/wp-json/jwt-auth/v1/token', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        username: credentials.username,
        password: credentials.password
      })
    });

    const data = await response.json();

    if (response.ok && data.token) {
      // Stockage du token
      localStorage.setItem('dame_jwt_token', data.token);
      
      // Notification de succès
      const toast = await toastController.create({
        message: "Connexion réussie ! Bienvenue.",
        duration: 2000,
        color: 'success',
        position: 'bottom'
      });
      await toast.present();
      
      // Redirection vers le tableau de bord
      router.push('/tabs/home');
      
    } else {
      // Gestion des erreurs API (ex: mauvais identifiants)
      throw new Error(data.message || "Identifiants incorrects.");
    }
  } catch (error: any) {
    // Notification d'erreur
    const alert = await alertController.create({
      header: 'Échec de connexion',
      message: error.message || "Impossible de contacter le serveur.",
      buttons: ['OK']
    });
    await alert.present();
  } finally {
    isLoading.value = false;
  }
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
