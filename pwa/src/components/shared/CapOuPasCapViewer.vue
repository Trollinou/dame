<template>
  <div class="cap-ou-pas-cap-layout">
    <!-- Card de consigne -->
    <ion-card v-if="consigne" class="consigne-card ion-margin-bottom">
      <ion-card-header>
        <ion-card-title class="consigne-title">{{ consigne }}</ion-card-title>
      </ion-card-header>
    </ion-card>

    <!-- Échiquier -->
    <div class="board-container">
      <eg-chessboard
        :boardConfig="boardConfig"
        :playerColor="diagrammeActuel?.couleur_joueur || 'white'"
        :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
        @board-created="onBoardCreated"
        @move="verifierCoup"
      />
    </div>

    <!-- Choix QCM (uniquement si typeReponse === 'qcm') -->
    <ion-card v-if="typeReponse === 'qcm' && diagrammeActuel?.qcm_choix" class="choices-card">
      <ion-card-content>
        <div class="qcm-choices">
          <ion-button
            v-for="(choix, index) in diagrammeActuel.qcm_choix"
            :key="index"
            expand="block"
            fill="solid"
            color="primary"
            class="choice-btn"
            @click="validerQcm(index)"
          >
            {{ choix.texte }}
          </ion-button>
        </div>
      </ion-card-content>
    </ion-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonButton,
  toastController
} from '@ionic/vue';
import { default as EgChessboard } from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { BoardCore } from 'eg-chessboard';

export interface QcmChoix {
  texte: string;
  explication: string;
}

export interface DiagrammeCapOuPasCap {
  fen: string;
  couleur_joueur: 'white' | 'black';
  shapes?: any[];
  qcm_choix?: QcmChoix[];
  qcm_bonne_reponse?: number;
  move_san?: string;
  move_explication?: string;
}

const props = withDefaults(
  defineProps<{
    consigne?: string;
    typeReponse?: 'qcm' | 'move' | string;
    diagrammes?: DiagrammeCapOuPasCap[];
  }>(),
  {
    consigne: '',
    typeReponse: 'qcm',
    diagrammes: () => []
  }
);

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const etapeCourante = ref(0);
const boardApi = ref<BoardCore | null>(null);

const diagrammeActuel = computed<DiagrammeCapOuPasCap | null>(() => {
  if (props.diagrammes && props.diagrammes.length > etapeCourante.value) {
    return props.diagrammes[etapeCourante.value];
  }
  return null;
});

const boardConfig = computed(() => {
  const diag = diagrammeActuel.value;
  return {
    fen: diag?.fen || '',
    orientation: diag?.couleur_joueur || 'white',
    viewOnly: props.typeReponse === 'qcm',
    drawable: { shapes: diag?.shapes || [] },
    highlight: { lastMove: true }
  };
});

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
  if (diagrammeActuel.value?.fen) {
    boardApi.value.setPosition(diagrammeActuel.value.fen);
  }
};

watch(
  diagrammeActuel,
  (newDiag) => {
    if (boardApi.value && newDiag?.fen) {
      boardApi.value.setPosition(newDiag.fen);
      if (newDiag.shapes) {
        boardApi.value.setShapes(newDiag.shapes);
      }
    }
  },
  { immediate: true }
);

const avancerOuReussir = async (message: string) => {
  const toast = await toastController.create({
    message,
    duration: 2000,
    color: 'success',
    position: 'bottom'
  });
  await toast.present();

  if (etapeCourante.value + 1 < props.diagrammes.length) {
    etapeCourante.value++;
  } else {
    setTimeout(() => {
      emit('success');
    }, 800);
  }
};

const validerQcm = async (index: number) => {
  const diag = diagrammeActuel.value;
  if (!diag) return;

  const choix = diag.qcm_choix?.[index];
  const estBonneReponse = index === diag.qcm_bonne_reponse;

  if (estBonneReponse) {
    const explication = choix?.explication || 'Bien joué !';
    await avancerOuReussir(explication);
  } else {
    const explication = choix?.explication || 'Mauvaise réponse !';
    const toast = await toastController.create({
      message: explication,
      duration: 2500,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  }
};

const verifierCoup = async (move: any) => {
  const diag = diagrammeActuel.value;
  if (!diag || props.typeReponse !== 'move') return;

  const playerColorShort = (diag.couleur_joueur || 'white') === 'white' ? 'w' : 'b';
  if (move.color !== playerColorShort) {
    return;
  }

  if (move.san === diag.move_san) {
    await avancerOuReussir('Bien joué !');
  } else {
    boardApi.value?.undoLastMove();
    const messageErreur = diag.move_explication || 'Ce n\'est pas le bon coup. Cherchez le mat !';
    const toast = await toastController.create({
      message: messageErreur,
      duration: 2500,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  }
};
</script>

<style scoped>
.cap-ou-pas-cap-layout {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.consigne-card {
  width: 100%;
  max-width: 500px;
  margin: 0 0 16px 0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.consigne-title {
  font-size: 1.1rem;
  font-weight: 600;
  text-align: center;
}

.board-container {
  width: 100%;
  aspect-ratio: 1 / 1;
  max-width: 500px;
  margin: 0 auto 16px auto;
  border-radius: 0;
  overflow: hidden;
  box-shadow: none;
}

.choices-card {
  width: 100%;
  max-width: 500px;
  margin: 0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.qcm-choices {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.choice-btn {
  text-transform: none;
  font-size: 1rem;
  font-weight: 500;
  --border-radius: 8px;
  min-height: 48px;
}

.choice-btn::part(native) {
  white-space: normal;
  text-align: center;
  padding: 12px 16px;
}
</style>
