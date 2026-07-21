<template>
  <div class="vision-viewer-layout">
    <div class="board-container">
      <eg-chessboard
        :boardConfig="{
          fen: fenActuelle,
          drawable: { shapes }
        }"
        :playerColor="props.couleurJoueur"
        :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
        @board-created="onBoardCreated"
        @square-click="gererClicCase"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { toastController } from '@ionic/vue';
import { default as EgChessboard } from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { BoardCore } from 'eg-chessboard';

const props = defineProps<{
  fenDepart: string;
  couleurJoueur: 'white' | 'black';
  caseDepart: string;
  caseArrivee: string;
  solutionSan: string;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const etapeJeu = ref<'reflexion' | 'revelation'>('reflexion');
const caseSelectionnee = ref<string | null>(null);
const fenActuelle = ref<string>('8/8/8/8/8/8/8/8 w - - 0 1');
const shapes = ref<any[]>([]);
const boardApi = ref<BoardCore | null>(null);

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
};

const gererClicCase = async (square: string) => {
  if (etapeJeu.value !== 'reflexion') {
    return;
  }

  // 1er clic (case de départ)
  if (!caseSelectionnee.value) {
    caseSelectionnee.value = square;
    shapes.value = [{ orig: square, brush: 'blue' }];
    return;
  }

  // 2ème clic (case d'arrivée)
  if (caseSelectionnee.value) {
    if (
      caseSelectionnee.value.toLowerCase() === props.caseDepart.toLowerCase() &&
      square.toLowerCase() === props.caseArrivee.toLowerCase()
    ) {
      // C'est gagné !
      etapeJeu.value = 'revelation';
      shapes.value = [];
      fenActuelle.value = props.fenDepart;

      // Attends 800ms, puis joue le coup visuellement
      setTimeout(() => {
        if (boardApi.value) {
          boardApi.value.move(props.solutionSan);
        }
      }, 800);

      // Attends encore 1000ms (1800ms au total), puis déclenche le succès
      setTimeout(() => {
        emit('success');
      }, 1800);
    } else {
      // C'est faux !
      const toast = await toastController.create({
        message: "Ce n'est pas le bon coup !",
        duration: 2000,
        color: 'danger',
        position: 'bottom'
      });
      await toast.present();

      // Réinitialise l'état
      caseSelectionnee.value = null;
      shapes.value = [];
    }
  }
};
</script>

<style scoped>
.vision-viewer-layout {
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
