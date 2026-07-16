<template>
  <div class="puzzle-viewer-layout">
    <div class="board-container">
      <TheChessboard 
        :boardConfig="{ fen: fen, drawable: { shapes: shapes } }"
        :playerColor="couleurJoueur"
        :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
        @board-created="onBoardCreated"
        @move="verifierCoup"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { toastController } from '@ionic/vue';
import { default as TheChessboard } from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { BoardCore } from 'eg-chessboard';

const props = withDefaults(
  defineProps<{
    fen: string;
    couleurJoueur: 'white' | 'black';
    solution: string[];
    shapes?: any[];
  }>(),
  {
    shapes: () => []
  }
);

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const boardApi = ref<BoardCore | null>(null);
const etapeActuelle = ref(0);

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
};

watch(() => props.shapes, (newShapes) => {
  if (boardApi.value && newShapes) {
    boardApi.value.setShapes(newShapes);
  }
}, { deep: true });

const verifierCoup = async (move: any) => {
  const playerColorShort = props.couleurJoueur === 'white' ? 'w' : 'b';
  
  // Ignore les déplacements si ce n'est pas la bonne couleur
  if (move.color !== playerColorShort) {
    return;
  }

  const coupAttendu = props.solution[etapeActuelle.value];

  if (move.san === coupAttendu) {
    // Le coup est correct
    etapeActuelle.value++;

    // Vérifie si l'exercice est terminé
    if (etapeActuelle.value === props.solution.length) {
      const toast = await toastController.create({
        message: 'Félicitations ! Exercice réussi.',
        duration: 3000,
        color: 'success',
        position: 'bottom'
      });
      await toast.present();
      
      // Petit délai pour laisser l'utilisateur apprécier son dernier coup
      setTimeout(() => {
        emit('success');
      }, 1000);
      
    } else {
      // L'exercice continue : l'ordinateur joue sa réponse scriptée
      setTimeout(() => {
        if (boardApi.value) {
          boardApi.value.move(props.solution[etapeActuelle.value]);
          etapeActuelle.value++;
        }
      }, 600);
    }
  } else {
    // Le coup est incorrect : on l'annule
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
.puzzle-viewer-layout {
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
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}
</style>
