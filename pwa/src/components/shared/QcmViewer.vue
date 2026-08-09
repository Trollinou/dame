<template>
  <div class="qcm-stage-layout">
    <!-- Échiquier affiché uniquement si une FEN est fournie -->
    <div v-if="fen" class="board-container">
      <eg-chessboard
        :diagram="{
          fen: fen || '',
          shapes: props.shapes
        }"
        :boardConfig="{ viewOnly: true }"
        :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
        @board-created="onBoardCreated"
      />
    </div>

    <ion-card class="question-card">
      <ion-card-header v-if="!hideQuestion">
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

    <!-- Footer de Navigation par Carte -->
    <SeriesCardFooter
      v-if="props.totalCards && props.totalCards > 1 && props.currentCard"
      :currentCard="props.currentCard"
      :totalCards="props.totalCards"
      :isSolved="repondu"
      :feedback="feedback"
      @next="passerCarteSuivante"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonButton
} from '@ionic/vue';
import EgChessboard from 'eg-chessboard/vue';
import type { BoardCore } from 'eg-chessboard';
import SeriesCardFooter, { type CardFeedback } from '@/components/shared/SeriesCardFooter.vue';

const props = withDefaults(
  defineProps<{
    fen?: string; // Rendue optionnelle
    question: string;
    hideQuestion?: boolean;
    choix: string[];
    bonneReponse: number;
    shapes?: any[];
    currentCard?: number;
    totalCards?: number;
  }>(),
  {
    shapes: () => []
  }
);

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const boardApi = ref<BoardCore | null>(null);

const repondu = ref(false);
const indexChoisi = ref<number | null>(null);
const feedback = ref<CardFeedback | null>(null);

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

watch(() => props.shapes, (newShapes) => {
  if (boardApi.value && newShapes) {
    boardApi.value.setShapes(newShapes);
  }
}, { deep: true });

// Logique visuelle
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

const validerChoix = (index: number) => {
  if (repondu.value) {
    return;
  }

  indexChoisi.value = index;

  if (index === props.bonneReponse) {
    repondu.value = true;
    feedback.value = {
      type: 'success',
      message: 'Bien joué ! Bonne réponse.'
    };

    // Si pas de série de cartes, émettre la réussite après 800ms
    if (!props.totalCards || props.totalCards <= 1) {
      setTimeout(() => {
        emit('success');
      }, 800);
    }
  } else {
    feedback.value = {
      type: 'danger',
      message: 'Mauvaise réponse, essaie encore !'
    };
  }
};

const passerCarteSuivante = () => {
  emit('success');
};
</script>

<style scoped>
.board-container {
  width: 100%;
  aspect-ratio: 1;
  max-width: 500px;
  margin: 0 auto;
  border-radius: 0;
  overflow: hidden;
  box-shadow: none;
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
