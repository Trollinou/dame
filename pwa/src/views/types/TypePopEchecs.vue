<template>
  <div class="exercice-type-popechecs">
    <ion-card class="consigne-card">
      <ion-card-header>
        <ion-card-title class="consigne-title">{{ config.consigne }}</ion-card-title>
      </ion-card-header>
    </ion-card>

    <div class="board-container">
      <eg-chessboard
        :boardConfig="{ fen: config.fen_depart }"
        :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
        @board-created="onBoardCreated"
        @square-click="verifierPlacement"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  toastController
} from '@ionic/vue';
import EgChessboard from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { BoardCore } from 'eg-chessboard';
import { useApprentissageStore } from '@/stores/apprentissage';

// Interface spécifique pour le type d'exercice Pop'Echecs (Type 2)
interface ConfigPopEchecs {
  consigne: string;
  fen_depart: string;
  piece_type: 'p' | 'r' | 'n' | 'b' | 'q' | 'k';
  piece_color: 'white' | 'black' | 'w' | 'b';
  case_cible: string;
}

const props = defineProps<{
  config: ConfigPopEchecs;
  id: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const store = useApprentissageStore();
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
  const colorShort = props.config.piece_color === 'white' ? 'w' : (props.config.piece_color === 'black' ? 'b' : props.config.piece_color);

  // 1. Place visuellement la pièce sur la case cliquée
  boardApi.value?.putPiece(
    { type: props.config.piece_type, color: colorShort as 'w' | 'b' },
    square as any
  );

  // 2. Compare square avec props.config.case_cible (en ignorant la casse)
  if (square.toLowerCase() === props.config.case_cible.toLowerCase()) {
    aTrouve.value = true;
    
    // 3. Si c'est la bonne case : Toast vert ("Parfait !") et validation de l'exercice
    const toast = await toastController.create({
      message: 'Parfait !',
      duration: 3000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();
    
    store.validerExercice(props.id);
    emit('success');
  } else {
    // 4. Si c'est la mauvaise case : Toast rouge ("Ce n'est pas la bonne case !") et retrait de la pièce erronée après 800ms
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
.exercice-type-popechecs {
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
}
</style>
