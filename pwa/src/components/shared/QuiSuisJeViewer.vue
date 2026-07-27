<template>
  <div class="qui-suis-je-viewer-layout">
    <!-- Card des indices -->
    <ion-card class="indices-card">
      <ion-card-header>
        <ion-card-title class="indices-title">
          <ion-icon :icon="helpCircleOutline" class="title-icon"></ion-icon>
          Qui suis-je ?
        </ion-card-title>
      </ion-card-header>
      <ion-card-content>
        <ul class="indices-list">
          <li v-for="(indice, index) in indicesVisibles" :key="index" class="indice-item">
            <span class="indice-badge">Indice {{ index + 1 }}</span>
            <span class="indice-texte">{{ indice }}</span>
          </li>
        </ul>

        <div v-if="!toutDevoile" class="ion-text-center ion-margin-top">
          <ion-button fill="outline" size="small" color="secondary" @click="afficherIndiceSuivant">
            <ion-icon slot="start" :icon="bulbOutline"></ion-icon>
            Indice suivant ({{ indicesAffichesCount }}/{{ indices.length }})
          </ion-button>
        </div>
      </ion-card-content>
    </ion-card>

    <!-- Zone de réponse selon typeReponse -->
    <div class="reponse-zone ion-margin-top">
      <!-- Mode PIECE: Palette flex 2x6 -->
      <div v-if="typeReponse === 'piece'" class="palette-wrapper">
        <p class="section-instruction">Sélectionnez la bonne pièce :</p>
        <div class="palette-container cg-board">
          <button
            v-for="code in pieceCodes"
            :key="code"
            type="button"
            class="piece-btn"
            :aria-label="getPieceLabel(code)"
            @click="verifierPiece(code)"
          >
            <div :class="getPieceClasses(code)"></div>
          </button>
        </div>
      </div>

      <!-- Mode SQUARE: Échiquier vide interactif -->
      <div v-else-if="typeReponse === 'square'" class="square-wrapper">
        <p class="section-instruction">Cliquez sur la bonne case de l'échiquier :</p>
        <div class="board-container">
          <eg-chessboard
            :boardConfig="{ fen: '8/8/8/8/8/8/8/8 w - - 0 1', viewOnly: true }"
            :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
            @square-click="verifierCase"
          />
        </div>
      </div>

      <!-- Mode QCM: Liste de boutons de choix -->
      <div v-else-if="typeReponse === 'qcm'" class="qcm-wrapper">
        <p class="section-instruction">Choisissez la bonne réponse :</p>
        <div class="qcm-choices">
          <ion-button
            v-for="(item, index) in (reponseQcm?.choix || [])"
            :key="index"
            expand="block"
            fill="solid"
            color="primary"
            class="qcm-btn"
            @click="verifierQcm(index)"
          >
            {{ item.texte }}
          </ion-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonButton,
  IonIcon,
  toastController
} from '@ionic/vue';
import { helpCircleOutline, bulbOutline } from 'ionicons/icons';
import { default as EgChessboard } from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';

export interface QcmChoix {
  texte: string;
  explication?: string;
}

export interface QcmConfig {
  choix: QcmChoix[];
  bonne_reponse: number;
}

const props = withDefaults(
  defineProps<{
    indices?: string[];
    typeReponse?: 'piece' | 'square' | 'qcm' | string;
    reponsePiece?: string;
    reponseCase?: string;
    reponseQcm?: QcmConfig;
  }>(),
  {
    indices: () => [],
    typeReponse: 'piece',
    reponsePiece: '',
    reponseCase: '',
    reponseQcm: () => ({ choix: [], bonne_reponse: 0 })
  }
);

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const aTrouve = ref(false);
const indicesAffichesCount = ref(1);

const indicesVisibles = computed(() => {
  if (!props.indices || props.indices.length === 0) return [];
  return props.indices.slice(0, indicesAffichesCount.value);
});

const toutDevoile = computed(() => {
  if (!props.indices || props.indices.length === 0) return true;
  return indicesAffichesCount.value >= props.indices.length;
});

const afficherIndiceSuivant = () => {
  if (props.indices && indicesAffichesCount.value < props.indices.length) {
    indicesAffichesCount.value++;
  }
};

// Liste des 12 pièces (Blanc puis Noir: Roi, Dame, Tour, Fou, Cavalier, Pion)
const pieceCodes = ['wK', 'wQ', 'wR', 'wB', 'wN', 'wP', 'bK', 'bQ', 'bR', 'bB', 'bN', 'bP'];

const getPieceClasses = (code: string) => {
  const colorChar = code.charAt(0);
  const typeChar = code.charAt(1);

  const color = colorChar === 'w' ? 'white' : 'black';
  const typeMap: Record<string, string> = {
    K: 'king',
    Q: 'queen',
    R: 'rook',
    B: 'bishop',
    N: 'knight',
    P: 'pawn'
  };
  const type = typeMap[typeChar] || 'pawn';
  return ['piece', type, color];
};

const getPieceLabel = (code: string) => {
  const colorChar = code.charAt(0);
  const typeChar = code.charAt(1);
  const colorName = colorChar === 'w' ? 'Blanc' : 'Noir';
  const typeNames: Record<string, string> = {
    K: 'Roi',
    Q: 'Dame',
    R: 'Tour',
    B: 'Fou',
    N: 'Cavalier',
    P: 'Pion'
  };
  return `${typeNames[typeChar] || 'Pièce'} ${colorName}`;
};

const verifierPiece = async (code: string) => {
  if (aTrouve.value) return;

  if (code.toLowerCase() === props.reponsePiece.trim().toLowerCase()) {
    aTrouve.value = true;
    const toast = await toastController.create({
      message: 'Bravo ! C\'est la bonne pièce.',
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();
    setTimeout(() => {
      emit('success');
    }, 800);
  } else {
    const toast = await toastController.create({
      message: 'Ce n\'est pas la bonne pièce, réessaie !',
      duration: 2000,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  }
};

const verifierCase = async (square: string) => {
  if (aTrouve.value) return;

  if (square.toLowerCase() === props.reponseCase.trim().toLowerCase()) {
    aTrouve.value = true;
    const toast = await toastController.create({
      message: `Bravo ! La case ${square.toUpperCase()} est la bonne réponse.`,
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();
    setTimeout(() => {
      emit('success');
    }, 800);
  } else {
    const toast = await toastController.create({
      message: `La case ${square.toUpperCase()} n'est pas la bonne réponse, réessaie !`,
      duration: 2000,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  }
};

const verifierQcm = async (index: number) => {
  if (aTrouve.value) return;

  const bonneIndex = props.reponseQcm?.bonne_reponse ?? 0;
  if (index === bonneIndex) {
    aTrouve.value = true;
    const toast = await toastController.create({
      message: 'Bravo ! Bonne réponse.',
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();
    setTimeout(() => {
      emit('success');
    }, 800);
  } else {
    const explication = props.reponseQcm?.choix?.[index]?.explication || 'Mauvaise réponse, réessaie !';
    const toast = await toastController.create({
      message: explication,
      duration: 2500,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  }
};
</script>

<style scoped>
.qui-suis-je-viewer-layout {
  width: 100%;
  max-width: 560px;
  margin: 0 auto;
}

.indices-card {
  margin: 0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.indices-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--ion-color-primary);
}

.title-icon {
  font-size: 1.4rem;
}

.indices-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.indice-item {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--ion-color-light, #f4f5f8);
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 1rem;
}

.indice-badge {
  background: var(--ion-color-primary);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 12px;
  white-space: nowrap;
}

.indice-texte {
  color: var(--ion-color-step-800, #333);
  font-weight: 500;
}

.section-instruction {
  font-size: 1.05rem;
  font-weight: 600;
  text-align: center;
  margin-bottom: 12px;
  color: var(--ion-color-step-800, #333);
}

/* Palette 2x6 */
.palette-container {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 8px;
  max-width: 480px;
  margin: 0 auto;
  padding: 12px;
  background: var(--ion-card-background, var(--ion-item-background, #fff));
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.piece-btn {
  aspect-ratio: 1 / 1;
  border-radius: 8px;
  border: 1px solid var(--ion-color-light-shade, #ddd);
  background: var(--ion-color-light, #fafafa);
  cursor: pointer;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
  transition: transform 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
}

.piece-btn:hover {
  transform: scale(1.08);
  border-color: var(--ion-color-primary);
  background: var(--ion-color-primary-tint, #e8f0fe);
}

.palette-container.cg-board .piece {
  position: relative !important;
  width: 100% !important;
  height: 100% !important;
  top: 0 !important;
  left: 0 !important;
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
}

/* Échiquier strict flat design (AGENTS.md) */
.board-container {
  width: 100%;
  aspect-ratio: 1 / 1;
  max-width: 500px;
  margin: 0 auto;
  border-radius: 0;
  overflow: hidden;
  box-shadow: none;
}

/* QCM buttons */
.qcm-choices {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 500px;
  margin: 0 auto;
}

.qcm-btn {
  text-transform: none;
  font-size: 1rem;
  font-weight: 500;
  --border-radius: 8px;
  min-height: 48px;
}

.qcm-btn::part(native) {
  white-space: normal;
  text-align: left;
  padding: 12px 16px;
}
</style>
