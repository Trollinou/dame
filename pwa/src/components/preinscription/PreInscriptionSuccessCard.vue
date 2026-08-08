<template>
  <div class="success-card ion-padding ion-text-center animate-fade-in">
    <ion-icon :icon="checkmarkCircleOutline" color="success" class="success-icon"></ion-icon>
    <h2>Préinscription Enregistrée !</h2>
    <p class="ion-margin-bottom">{{ successData.message }}</p>

    <ion-card class="pdf-card ion-no-margin ion-margin-bottom">
      <ion-card-header>
        <ion-card-title style="font-size: 1.1em;">Documents à télécharger</ion-card-title>
      </ion-card-header>
      <ion-card-content>
        <div class="pdf-buttons">
          <ion-button expand="block" color="secondary" @click="$emit('download-pdf', 'health')">
            <ion-icon slot="start" :icon="documentTextOutline"></ion-icon>
            Attestation de Santé
          </ion-button>
          <ion-button v-if="successData.is_minor" expand="block" color="secondary" @click="$emit('download-pdf', 'parental')">
            <ion-icon slot="start" :icon="documentTextOutline"></ion-icon>
            Autorisation Parentale
          </ion-button>
        </div>
      </ion-card-content>
    </ion-card>

    <div v-if="successData.payment_url" class="ion-margin-bottom">
      <p>Pour finaliser l'inscription, vous pouvez procéder au paiement en ligne :</p>
      <ion-button :href="successData.payment_url" target="_blank" color="primary" expand="block">
        Payer mon adhésion
      </ion-button>
    </div>

    <ion-button fill="outline" color="medium" @click="$emit('reset')" expand="block">
      Faire une nouvelle préinscription
    </ion-button>
  </div>
</template>

<script setup lang="ts">
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonButton,
  IonIcon
} from '@ionic/vue';
import { checkmarkCircleOutline, documentTextOutline } from 'ionicons/icons';

defineProps<{
  successData: {
    message: string;
    is_minor?: boolean;
    payment_url?: string;
  };
}>();

defineEmits<{
  (e: 'download-pdf', type: 'health' | 'parental'): void;
  (e: 'reset'): void;
}>();
</script>

<style scoped>
.success-card {
  background: var(--ion-color-step-0, #fff);
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.success-icon {
  font-size: 4rem;
  margin-bottom: 15px;
}

.pdf-buttons {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
