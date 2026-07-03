<template>
  <div class="exercice-type-abcdaire">
    <div class="board-container">
      <TheChessboard 
        ref="boardRef"
        :board-config="boardConfig" 
        :player-color="config.couleur_joueur"
        :stockfish-config="stockfishConfig"
        @move="verifierCoup"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { toastController } from '@ionic/vue';
import TheChessboard from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { ExerciceConfig } from '@/stores/apprentissage';
import type { StockfishConfig } from 'eg-chessboard';

const props = defineProps<{
  config: ExerciceConfig;
}>();

const boardRef = ref<any>(null);
const etapeActuelle = ref(0);

// Disable stockfish for guided exercise
const stockfishConfig: StockfishConfig = {
  whiteMode: 'disabled',
  blackMode: 'disabled'
};

const boardConfig = computed(() => {
  return {
    fen: props.config.fen,
    orientation: props.config.couleur_joueur,
    coordinates: true,
    movable: {
      color: props.config.couleur_joueur
    }
  };
});

const verifierCoup = async (coup: any) => {
  const solution = props.config.solution;
  const coupAttendu = solution[etapeActuelle.value];

  // Si correct
  if (coup.san === coupAttendu) {
    etapeActuelle.value++;

    // Si c'est le dernier coup
    if (etapeActuelle.value === solution.length) {
      const toast = await toastController.create({
        message: 'Félicitations, exercice réussi !',
        duration: 3000,
        color: 'success',
        position: 'bottom'
      });
      await toast.present();
    } else {
      // Sinon, coup de l'ordinateur après 500ms
      setTimeout(() => {
        if (boardRef.value?.core) {
          const coupOrdi = solution[etapeActuelle.value];
          boardRef.value.core.move(coupOrdi);
          etapeActuelle.value++;
        }
      }, 500);
    }
  } else {
    // Si incorrect, on annule et affiche une erreur
    if (boardRef.value?.core) {
      boardRef.value.core.undoLastMove();
    }
    const toast = await toastController.create({
      message: 'Mauvais coup, essaie encore !',
      duration: 2500,
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
