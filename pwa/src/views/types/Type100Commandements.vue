<template>
  <ion-card class="exercice-type-100commandements">
    <ion-card-header>
      <ion-card-title>{{ config.question }}</ion-card-title>
    </ion-card-header>

    <ion-card-content>
      <ion-list lines="none">
        <ion-button
          v-for="(reponse, index) in config.reponses"
          :key="index"
          expand="block"
          class="ion-margin-bottom reponse-btn"
          :color="couleurBouton(index)"
          :disabled="repondu"
          @click="verifierReponse(index)"
        >
          {{ reponse }}
        </ion-button>
      </ion-list>
    </ion-card-content>
  </ion-card>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonList,
  IonButton,
  toastController
} from '@ionic/vue';
import { useApprentissageStore } from '@/stores/apprentissage';

interface Config100Commandements {
  question: string;
  reponses: string[];
  bonne_reponse: number;
  id?: number;
}

const props = defineProps<{
  config: Config100Commandements;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const store = useApprentissageStore();

const repondu = ref(false);
const indexChoisi = ref<number | null>(null);

/**
 * Détermine la couleur du bouton après réponse.
 * Vert pour la bonne réponse, rouge pour la mauvaise réponse choisie.
 */
const couleurBouton = (index: number): string => {
  if (!repondu.value) {
    return 'primary';
  }
  if (index === props.config.bonne_reponse) {
    return 'success';
  }
  if (index === indexChoisi.value) {
    return 'danger';
  }
  return 'medium';
};

/**
 * Vérifie si la réponse sélectionnée est correcte.
 * Affiche un Toast et enregistre la progression si bonne réponse.
 */
const verifierReponse = async (index: number) => {
  if (repondu.value) {
    return;
  }

  indexChoisi.value = index;

  if (index === props.config.bonne_reponse) {
    repondu.value = true;

    // Enregistrer la progression via le store
    if (props.config.id) {
      store.validerExercice(props.config.id);
    }

    const toast = await toastController.create({
      message: 'Bonne réponse !',
      duration: 3000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();

    emit('success');
  } else {
    const toast = await toastController.create({
      message: 'Mauvaise réponse, essaie encore !',
      duration: 2000,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  }
};
</script>

<style scoped>
.exercice-type-100commandements {
  border-radius: 12px;
}

.exercice-type-100commandements ion-card-title {
  font-size: 1.15rem;
  line-height: 1.5;
}

.reponse-btn {
  text-transform: none;
  font-size: 1rem;
  --border-radius: 8px;
  min-height: 48px;
  white-space: normal;
}

.reponse-btn::part(native) {
  white-space: normal;
  text-align: left;
  padding: 12px 16px;
}
</style>
