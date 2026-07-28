<template>
  <div class="eval-viewer-layout">
    <!-- Phase 1 : Questions -->
    <div v-if="phase === 'questions'" class="phase-questions">
      <div class="board-container">
        <eg-chessboard
          :boardConfig="{
            fen: fenDepart,
            viewOnly: true,
            orientation: couleurJoueur,
            drawable: { shapes: shapes }
          }"
          :playerColor="couleurJoueur"
        />
      </div>

      <ion-card class="question-card">
        <ion-card-header>
          <ion-card-title class="theme-title">{{ theme }}</ion-card-title>
        </ion-card-header>
        <ion-card-content v-if="questionActuelle">
          <p class="question-text">{{ questionActuelle.texte }}</p>

          <div class="buttons-container">
            <template v-if="questionActuelle.type_reponse === 'yesno'">
              <ion-button expand="block" color="primary" @click="repondre('oui')">Oui</ion-button>
              <ion-button expand="block" color="primary" @click="repondre('non')">Non</ion-button>
            </template>
            <template v-else-if="questionActuelle.type_reponse === 'evaluation'">
              <ion-button expand="block" color="success" @click="repondre('bonne')">Bonne</ion-button>
              <ion-button expand="block" color="warning" @click="repondre('neutre')">Neutre</ion-button>
              <ion-button expand="block" color="danger" @click="repondre('mauvaise')">Mauvaise</ion-button>
            </template>
          </div>
        </ion-card-content>
      </ion-card>
    </div>

    <!-- Phase 2 : Tactique -->
    <div v-else-if="phase === 'tactique'" class="phase-tactique">
      <h3 class="phase-title">À vous de jouer ! Trouvez la séquence.</h3>
      <PuzzleViewer
        :couleurJoueur="couleurJoueur"
        :fen="fenDepart"
        :shapes="shapes"
        :solution="solutionMoves"
        @success="phase = 'solution'"
      />
    </div>

    <!-- Phase 3 : Solution / PGN -->
    <div v-else class="phase-solution">
      <PgnViewer
        :orientation="couleurJoueur"
        :pgn="pgnExplication"
      />
      <div class="finish-container">
        <ion-button expand="block" color="success" class="finish-btn" @click="$emit('success')">
          Terminer l'exercice
        </ion-button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { toastController, IonButton, IonCard, IonCardHeader, IonCardTitle, IonCardContent } from '@ionic/vue';
import EgChessboard from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { DrawShape } from 'eg-chessboard';
import PuzzleViewer from './PuzzleViewer.vue';
import PgnViewer from './PgnViewer.vue';

export interface QuestionEval {
  texte: string;
  type_reponse: 'yesno' | 'evaluation';
  reponse_attendue: string;
  explication: string;
}

const props = withDefaults(
  defineProps<{
    fenDepart: string;
    couleurJoueur: 'white' | 'black';
    shapes?: DrawShape[];
    theme: string;
    questions: QuestionEval[];
    solutionMoves: string[];
    pgnExplication: string;
  }>(),
  {
    shapes: () => []
  }
);

defineEmits<{
  (e: 'success'): void;
}>();

const phase = ref<'questions' | 'tactique' | 'solution'>('questions');
const questionIndex = ref(0);

const questionActuelle = computed(() => props.questions[questionIndex.value]);

const repondre = async (valeur: string) => {
  if (!questionActuelle.value) return;

  const attendu = questionActuelle.value.reponse_attendue.trim().toLowerCase();
  const donne = valeur.trim().toLowerCase();

  if (donne === attendu) {
    const toast = await toastController.create({
      message: 'Bonne réponse !',
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();

    if (questionIndex.value < props.questions.length - 1) {
      questionIndex.value++;
    } else {
      phase.value = 'tactique';
    }
  } else {
    const toast = await toastController.create({
      message: questionActuelle.value.explication || 'Mauvaise réponse, essayez à nouveau.',
      duration: 3500,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  }
};
</script>

<style scoped>
.eval-viewer-layout {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.phase-questions,
.phase-tactique,
.phase-solution {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.board-container {
  width: 100%;
  aspect-ratio: 1;
  max-width: 500px;
  margin: 0 auto;
  border-radius: 0;
  overflow: hidden;
  box-shadow: none;
}

.question-card {
  width: 100%;
  max-width: 500px;
  margin-top: 16px;
}

.theme-title {
  font-size: 1.1rem;
  font-weight: bold;
  color: var(--ion-color-primary);
}

.question-text {
  font-size: 1rem;
  margin-bottom: 16px;
  line-height: 1.4;
}

.buttons-container {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.phase-title {
  margin-bottom: 12px;
  text-align: center;
  font-size: 1.1rem;
  font-weight: 600;
}

.finish-container {
  width: 100%;
  max-width: 500px;
  margin-top: 16px;
}
</style>
