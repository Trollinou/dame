<template>
  <div class="placement-viewer-layout">
    <ion-card v-if="consigne" class="consigne-card">
      <ion-card-header>
        <ion-card-title class="consigne-title">{{ consigne }}</ion-card-title>
      </ion-card-header>
    </ion-card>

    <div class="board-container">
      <eg-chessboard
        :boardConfig="{ fen: fenDepart }"
        :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
        @board-created="onBoardCreated"
        @square-click="verifierPlacement"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { IonCard, IonCardHeader, IonCardTitle, toastController } from '@ionic/vue';
import { default as EgChessboard } from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { BoardCore } from 'eg-chessboard';

const props = defineProps<{
  consigne?: string;
  fenDepart: string;
  pieceType: 'p' | 'r' | 'n' | 'b' | 'q' | 'k';
  pieceColor: 'white' | 'black' | 'w' | 'b' | string;
  caseCible: string;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const boardApi = ref<BoardCore | null>(null);
const aTrouve = ref(false);

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
};

const verifierPlacement = async (square: string) => {
  if (aTrouve.value) {
    return;
  }

  // Conversion de la couleur pour eg-chessboard/chess.js (w ou b)
  const colorShort = props.pieceColor === 'white' ? 'w' : (props.pieceColor === 'black' ? 'b' : props.pieceColor);

  // 1. Place visuellement la pièce sur la case cliquée
  boardApi.value?.putPiece(
    { 
      type: props.pieceType as 'p' | 'r' | 'n' | 'b' | 'q' | 'k', 
      color: colorShort as 'w' | 'b' 
    },
    square as any
  );

  // 2. Compare la case cliquée avec la case cible (en ignorant la casse)
  if (square.toLowerCase() === props.caseCible.toLowerCase()) {
    aTrouve.value = true;
    
    // 3. Bonne case : Toast vert et émission du succès
    const toast = await toastController.create({
      message: 'Parfait !',
      duration: 3000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();
    
    // Petit délai avant de passer à la suite pour apprécier le placement
    setTimeout(() => {
      emit('success');
    }, 1000);
    
  } else {
    // 4. Mauvaise case : Toast rouge et retrait de la pièce erronée après 800ms
    const toast = await toastController.create({
      message: "Ce n'est pas la bonne case !",
      duration: 2000,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();

    setTimeout(() => {
      boardApi.value?.removePiece(square as any);
    }, 800);
  }
};
</script>

<style scoped>
.placement-viewer-layout {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.consigne-card {
  width: 100%;
  max-width: 500px;
  margin-bottom: 16px;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.consigne-title {
  font-size: 1.1rem;
  line-height: 1.4;
  text-align: center;
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
