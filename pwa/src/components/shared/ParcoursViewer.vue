<template>
  <div class="parcours-viewer-layout">
    <div class="board-container">
      <eg-chessboard
        :diagram="{
          fen: props.fenDepart,
          shapes: props.shapes as any
        }"
        :player-color="props.couleurJoueur"
        :solo-mode="true"
        :board-config="{
          drawable: { enabled: false }
        }"
        @board-created="onBoardCreated"
        @move="handleMove"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { default as EgChessboard } from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { BoardCore } from 'eg-chessboard';
import { toastController } from '@ionic/vue';

const props = defineProps<{
  fenDepart: string;
  couleurJoueur: 'white' | 'black';
  variante: string;
  caseDepart: string;
  caseArrivee: string;
  shapes: Array<{ orig: string; dest?: string; brush: string; [key: string]: any }>;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const boardApi = ref<BoardCore | null>(null);

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
  api.setSoloMode(true);
  if (props.shapes && props.shapes.length > 0) {
    api.setShapes(props.shapes as any);
  }
};

const handleMove = async (move: any) => {
  if (!boardApi.value) return;

  // Redessiner les formes pour éviter qu'elles ne disparaissent après le déplacement
  if (props.shapes && props.shapes.length > 0) {
    boardApi.value.setShapes(props.shapes as any);
  }

  const oppColor = props.couleurJoueur === 'white' ? 'black' : 'white';
  const oppColorShort = oppColor === 'white' ? 'w' : 'b';

  // 1. Règle absolue : Ne pas s'arrêter sur une case rouge
  const isRedSquare = props.shapes.some(s => s.brush === 'red' && s.orig === move.to);
  if (isRedSquare) {
    const toast = await toastController.create({ message: "Case interdite !", duration: 2000, color: 'danger', position: 'bottom' });
    await toast.present();
    boardApi.value.setPosition(props.fenDepart);
    boardApi.value.setShapes(props.shapes as any);
    return;
  }

  // 2. Variante Stealth (Pas vu, pas pris)
  if (props.variante === 'stealth' && boardApi.value.isSquareAttacked(move.to, oppColor)) {
    const toast = await toastController.create({ message: "Vous avez été repéré !", duration: 2000, color: 'danger', position: 'bottom' });
    await toast.present();
    boardApi.value.setPosition(props.fenDepart);
    boardApi.value.setShapes(props.shapes as any);
    return;
  }

  // 3. Arrivée sur la case cible
  if (move.to === props.caseArrivee) {
    if (props.variante === 'pacman') {
      const allPieces = boardApi.value.getPieces();
      let hasOpponentPieces = false;
      allPieces.forEach(p => { if (p.color === oppColorShort) hasOpponentPieces = true; });
      if (hasOpponentPieces) {
        const toast = await toastController.create({ message: "Il reste des pièces à manger !", duration: 2000, color: 'danger', position: 'bottom' });
        await toast.present();
        boardApi.value.setPosition(props.fenDepart);
        boardApi.value.setShapes(props.shapes as any);
        return;
      }
    }
    // Succès
    const toast = await toastController.create({ message: "Parcours réussi !", duration: 2000, color: 'success', position: 'bottom' });
    await toast.present();
    setTimeout(() => emit('success'), 800);
  }
};
</script>

<style scoped>
.parcours-viewer-layout {
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
