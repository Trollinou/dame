<template>
  <div class="qcm-stage-layout">
    <!-- Échiquier affiché uniquement si une FEN est fournie -->
    <div v-if="fen" class="board-container">
      <eg-chessboard
        :boardConfig="qcmBoardConfig"
        :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
        @board-created="onBoardCreated"
      />
    </div>

    <ion-card class="question-card">
      <ion-card-header>
        <ion-card-title class="question-title">{{ question }}</ion-card-title>
      </ion-card-header>

      <ion-card-content>
        <div class="qcm-choices">
          <ion-button
            v-for="(choixTexte, index) in choix"
            :key="index"
            expand="block"
            fill="solid"
            :color="couleurBouton(index)"
            :disabled="repondu"
            class="choice-btn"
            @click="validerChoix(index)"
          >
            {{ choixTexte }}
          </ion-button>
        </div>
      </ion-card-content>
    </ion-card>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonButton,
  toastController
} from '@ionic/vue';
import EgChessboard from 'eg-chessboard/vue';
import type { BoardCore } from 'eg-chessboard';

const props = defineProps<{
  fen?: string; // Rendue optionnelle
  question: string;
  choix: string[];
  bonneReponse: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const boardApi = ref<BoardCore | null>(null);
const qcmBoardConfig = { viewOnly: true };

const repondu = ref(false);
const indexChoisi = ref<number | null>(null);

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
  if (props.fen) {
    boardApi.value.setPosition(props.fen);
  }
};

watch(() => props.fen, (newFen) => {
  if (boardApi.value && newFen) {
    boardApi.value.setPosition(newFen);
  }
});

// Logique visuelle importée de l'ancien composant
const couleurBouton = (index: number): string => {
  if (!repondu.value) {
    return 'primary';
  }
  if (index === props.bonneReponse) {
    return 'success';
  }
  if (index === indexChoisi.value) {
    return 'danger';
  }
  return 'medium';
};

const validerChoix = async (index: number) => {
  if (repondu.value) {
    return;
  }

  indexChoisi.value = index;

  if (index === props.bonneReponse) {
    repondu.value = true;
    const toast = await toastController.create({
      message: 'Bien joué !',
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();

    setTimeout(() => {
      emit('success');
    }, 1000);
  } else {
    // Ne verrouille pas 'repondu' en cas d'erreur pour permettre de réessayer
    const toast = await toastController.create({
      message: 'Mauvaise réponse, essaie encore !',
      duration: 2000,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
    indexChoisi.value = null; // Réinitialise l'erreur visuelle si on veut laisser réessayer
  }
};
</script>

<style scoped>
.board-container {
  width: 100%;
  aspect-ratio: 1;
  max-width: 500px;
  margin: 0 auto;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  margin-bottom: 12px;
}

.question-card {
  margin: 0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.question-title {
  font-size: 1.15rem;
  font-weight: 600;
  line-height: 1.5;
  text-align: center;
  color: var(--ion-color-step-900, #222);
}

.choice-btn {
  text-transform: none;
  font-size: 1rem;
  font-weight: 500;
  --border-radius: 8px;
  min-height: 48px;
  white-space: normal;
}

.choice-btn::part(native) {
  white-space: normal;
  text-align: left;
  padding: 12px 16px;
}
</style>
