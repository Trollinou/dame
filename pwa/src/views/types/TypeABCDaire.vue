<template>
  <div class="exercice-type-abcdaire">
    <div class="board-container">
      <TheChessboard 
        :boardConfig="{ fen: config.fen }"
        :playerColor="config.couleur_joueur"
        :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
        @board-created="onBoardCreated"
        @move="verifierCoup"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { toastController } from '@ionic/vue';
import TheChessboard from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { ExerciceConfig } from '@/stores/apprentissage';
import type { BoardCore } from 'eg-chessboard';

const props = defineProps<{
  config: ExerciceConfig;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const boardApi = ref<BoardCore | null>(null);
const etapeActuelle = ref(0);

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
};

const verifierCoup = async (move: any) => {
  const playerColorShort = props.config.couleur_joueur === 'white' ? 'w' : 'b';
  if (move.color !== playerColorShort) {
    return;
  }

  const solution = props.config.solution;
  const coupAttendu = solution[etapeActuelle.value];

  if (move.san === coupAttendu) {
    // Si le coup est correct
    etapeActuelle.value++;

    // Vérifie si l'exercice est terminé
    if (etapeActuelle.value === solution.length) {
      const toast = await toastController.create({
        message: 'Félicitations ! Exercice réussi.',
        duration: 3000,
        color: 'success',
        position: 'bottom'
      });
      await toast.present();
      emit('success');
    } else {
      // Si l'exercice continue, c'est à l'ordinateur de jouer sa réponse scriptée.
      setTimeout(() => {
        if (boardApi.value) {
          boardApi.value.move(solution[etapeActuelle.value]);
          etapeActuelle.value++;
        }
      }, 600);
    }
  } else {
    // Si le coup est incorrect
    boardApi.value?.undoLastMove();

    const toast = await toastController.create({
      message: 'Mauvais coup, cherche encore !',
      duration: 2000,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  }
};
</script>

<style scoped>
.exercice-type-abcdaire {
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
}
</style>
