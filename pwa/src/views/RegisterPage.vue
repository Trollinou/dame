<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/login"></ion-back-button>
        </ion-buttons>
        <ion-title>Inscription</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="register-container">
        <div class="register-header">
          <img src="/assets/icon/queen.svg" alt="Logo Dame" style="height: 60px; margin-bottom: 10px;" />
          <h2>Créer un compte</h2>
          <p>Réservé aux adhérents et représentants légaux</p>
        </div>

        <div v-if="successMessage" class="success-message ion-padding ion-text-center">
          <ion-icon :icon="checkmarkCircleOutline" color="success" style="font-size: 3rem;"></ion-icon>
          <h3>Inscription réussie !</h3>
          <p>{{ successMessage }}</p>
          <ion-button expand="block" router-link="/tabs/login" class="ion-margin-top">
            Retour à la connexion
          </ion-button>
        </div>

        <form v-else @submit.prevent="handleRegister" class="register-form">
          <ion-list lines="none" class="form-list">
            <ion-item class="input-item">
              <ion-icon slot="start" :icon="personOutline" color="medium"></ion-icon>
              <ion-input 
                v-model="username" 
                type="text" 
                placeholder="Identifiant (pour la connexion)" 
                required
              ></ion-input>
            </ion-item>

            <ion-item class="input-item">
              <ion-icon slot="start" :icon="mailOutline" color="medium"></ion-icon>
              <ion-input 
                v-model="email" 
                type="email" 
                placeholder="Votre adresse email" 
                required
              ></ion-input>
            </ion-item>

            <ion-item class="input-item">
              <ion-icon slot="start" :icon="lockClosedOutline" color="medium"></ion-icon>
              <ion-input 
                v-model="password" 
                type="password" 
                placeholder="Mot de passe" 
                required
              ></ion-input>
            </ion-item>
            
            <ion-item class="input-item">
              <ion-icon slot="start" :icon="lockClosedOutline" color="medium"></ion-icon>
              <ion-input 
                v-model="confirmPassword" 
                type="password" 
                placeholder="Confirmer le mot de passe" 
                required
              ></ion-input>
            </ion-item>
          </ion-list>

          <ion-text color="danger" v-if="errorMessage" class="error-message">
            <p>{{ errorMessage }}</p>
          </ion-text>

          <ion-button 
            expand="block" 
            type="submit" 
            class="ion-margin-top" 
            :disabled="isLoading"
          >
            <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
            <span v-else>S'inscrire</span>
          </ion-button>
        </form>
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
  IonInput,
  IonButton,
  IonIcon,
  IonText,
  IonSpinner,
  IonButtons,
  IonBackButton,
  onIonViewWillEnter
} from '@ionic/vue';
import { 
  mailOutline, 
  lockClosedOutline, 
  personOutline,
  checkmarkCircleOutline 
} from 'ionicons/icons';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const username = ref('');
const email = ref('');
const password = ref('');
const confirmPassword = ref('');
const errorMessage = ref('');
const successMessage = ref('');
const isLoading = ref(false);

/**
 * Réinitialise le formulaire à chaque entrée sur la page
 * (Évite de rester sur l'écran de succès lors d'une nouvelle inscription)
 */
onIonViewWillEnter(() => {
  username.value = '';
  email.value = '';
  password.value = '';
  confirmPassword.value = '';
  errorMessage.value = '';
  successMessage.value = '';
  isLoading.value = false;
});

const handleRegister = async () => {
  errorMessage.value = '';
  successMessage.value = '';

  if (password.value !== confirmPassword.value) {
    errorMessage.value = 'Les mots de passe ne correspondent pas.';
    return;
  }

  isLoading.value = true;

  try {
    const apiUrl = import.meta.env.VITE_API_BASE_URL;
    const response = await fetch(`${apiUrl}/dame/v1/register`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        username: username.value,
        email: email.value,
        password: password.value,
      }),
    });

    const data = await response.json();

    if (!response.ok) {
      // Gestion des erreurs spécifiques renvoyées par le backend
      if (data.code === 'user_exists') {
        errorMessage.value = 'Un compte existe déjà avec cette adresse email. Veuillez vous connecter.';
        setTimeout(() => router.push('/tabs/login'), 3000);
      } else if (data.code === 'not_member') {
        errorMessage.value = "Cette adresse email n'est pas reconnue comme appartenant à un adhérent ou un représentant légal.";
      } else {
        errorMessage.value = data.message || 'Une erreur est survenue lors de l\'inscription.';
      }
      return;
    }

    // Succès
    successMessage.value = 'Un email de validation vous a été envoyé. Veuillez cliquer sur le lien qu\'il contient pour activer votre compte.';
    
  } catch (err) {
    console.error('Erreur Inscription:', err);
    errorMessage.value = 'Erreur de connexion au serveur.';
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.register-container {
  max-width: 400px;
  margin: 0 auto;
  padding: 20px 10px;
}

.register-header {
  text-align: center;
  margin-bottom: 30px;
}

.register-header h2 {
  font-weight: 700;
  font-size: 24px;
}

.register-header p {
  color: var(--ion-color-medium);
}

.form-list {
  background: transparent;
}

.input-item {
  --background: var(--ion-color-light);
  --border-radius: 8px;
  margin-bottom: 15px;
  border-radius: 8px;
}

.error-message {
  text-align: center;
  font-size: 0.9em;
  margin-top: 10px;
}

.success-message {
  background: var(--ion-color-success-tint);
  border-radius: 12px;
  padding: 20px;
}
</style>
