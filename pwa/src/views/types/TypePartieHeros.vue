<template>
  <div class="exercice-type-partie-heros">
    <!-- Étape Actuelle -->
    <div class="etape-container">
      <!-- Rendu pour le type PGN -->
      <div v-if="etapeActuelle.type === 'pgn'" class="pgn-stage-layout">
        <div class="board-container">
          <eg-chessboard
            :boardConfig="pgnBoardConfig"
            :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
            @board-created="onBoardCreated"
          />
        </div>

        <!-- Navigation Controls -->
        <div class="navigation-controls">
          <ion-button fill="outline" color="primary" class="nav-btn" @click="viewStart">
            <ion-icon slot="icon-only" :icon="playBackOutline"></ion-icon>
          </ion-button>
          <ion-button fill="outline" color="primary" class="nav-btn" @click="viewPrevious">
            <ion-icon slot="icon-only" :icon="chevronBackOutline"></ion-icon>
          </ion-button>
          <ion-button fill="outline" color="primary" class="nav-btn" @click="viewNext">
            <ion-icon slot="icon-only" :icon="chevronForwardOutline"></ion-icon>
          </ion-button>
        </div>

        <!-- Comment Display -->
        <div class="comment-container">
          <p class="comment-text" :class="{ 'placeholder-text': !currentComment }">
            {{ currentComment ? '💬 ' + currentComment : 'Aucun commentaire pour cette position.' }}
          </p>
        </div>

        <div class="action-container">
          <ion-button expand="block" color="success" class="continue-btn" @click="passerALaSuite">
            {{ estDerniereEtape ? 'Terminer le scénario' : 'Passer à la suite' }}
            <ion-icon slot="end" :icon="arrowForwardOutline"></ion-icon>
          </ion-button>
        </div>
      </div>

      <!-- Rendu pour le type QCM -->
      <div v-else-if="etapeActuelle.type === 'qcm'" class="qcm-stage-layout">
        <div class="board-container">
          <eg-chessboard
            :boardConfig="qcmBoardConfig"
            :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
            @board-created="onBoardCreated"
          />
        </div>

        <ion-card class="question-card">
          <ion-card-header>
            <ion-card-title class="question-title">{{ etapeActuelle.question }}</ion-card-title>
          </ion-card-header>

          <ion-card-content>
            <div class="qcm-choices">
              <ion-button
                v-for="(choix, index) in etapeActuelle.choix"
                :key="index"
                expand="block"
                fill="solid"
                color="primary"
                class="choice-btn"
                @click="validerChoix(index)"
              >
                {{ choix }}
              </ion-button>
            </div>
          </ion-card-content>
        </ion-card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
  IonButton,
  IonIcon,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  toastController
} from '@ionic/vue';
import {
  playBackOutline,
  chevronBackOutline,
  chevronForwardOutline,
  arrowForwardOutline
} from 'ionicons/icons';
import EgChessboard from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { BoardCore } from 'eg-chessboard';
import { useApprentissageStore } from '@/stores/apprentissage';

interface EtapeBase {
  type: 'pgn' | 'qcm';
  pgn?: string;
  pgn_data?: string;
  fen?: string;
  question?: string;
  choix?: string[];
  bonne_reponse?: number;
}

interface ConfigPartieHeros {
  etapes: EtapeBase[];
}

const props = defineProps<{
  config: ConfigPartieHeros;
  id: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const store = useApprentissageStore();
const boardApi = ref<BoardCore | null>(null);
const etapeCouranteIndex = ref(0);
const currentComment = ref('');

const pgnBoardConfig = {
  viewOnly: true
};

const qcmBoardConfig = {
  viewOnly: true
};

console.log('[TypePartieHeros] Props received:', { config: props.config, id: props.id });

const estDerniereEtape = computed(() => {
  if (!props.config || !props.config.etapes) return true;
  return etapeCouranteIndex.value === props.config.etapes.length - 1;
});

const etapeActuelle = computed<EtapeBase>(() => {
  if (!props.config || !props.config.etapes) {
    console.warn('[TypePartieHeros] Warning: props.config.etapes is undefined or null');
    return { type: 'pgn' } as EtapeBase; // Fallback to avoid crash
  }
  return props.config.etapes[etapeCouranteIndex.value] || ({ type: 'pgn' } as EtapeBase);
});

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
  initEtape(etapeActuelle.value);
};

const syncComment = () => {
  if (boardApi.value) {
    currentComment.value = (boardApi.value as any).state.currentComment || '';
  }
};

const initEtape = (etape: EtapeBase) => {
  console.log('[TypePartieHeros] initEtape called with:', etape);
  if (!boardApi.value || !etape) {
    console.log('[TypePartieHeros] boardApi or etape is missing');
    return;
  }

  const pgnString = etape.pgn_data || etape.pgn;
  if (etape.type === 'pgn' && pgnString) {
    console.log('[TypePartieHeros] Loading PGN...');
    boardApi.value.setPosition('start');
    boardApi.value.loadPgn(pgnString);
    const history = boardApi.value.getHistory(true);
    console.log('[TypePartieHeros] Loaded PGN history length:', history.length);
    boardApi.value.viewStart();
    console.log('[TypePartieHeros] Called viewStart()');
    syncComment();
  } else if (etape.type === 'qcm' && etape.fen) {
    console.log('[TypePartieHeros] Loading QCM FEN:', etape.fen);
    boardApi.value.setPosition(etape.fen);
    currentComment.value = '';
  }
};

watch(etapeActuelle, (newEtape) => {
  if (newEtape) {
    initEtape(newEtape);
  }
});

const viewStart = () => {
  console.log('[TypePartieHeros] viewStart clicked');
  if (boardApi.value) {
    boardApi.value.viewStart();
    console.log('[TypePartieHeros] current history state:', (boardApi.value as any).state.historyViewerState);
    syncComment();
  }
};

const viewPrevious = () => {
  console.log('[TypePartieHeros] viewPrevious clicked');
  if (boardApi.value) {
    boardApi.value.viewPrevious();
    console.log('[TypePartieHeros] current history state:', (boardApi.value as any).state.historyViewerState);
    syncComment();
  }
};

const viewNext = () => {
  console.log('[TypePartieHeros] viewNext clicked');
  if (boardApi.value) {
    boardApi.value.viewNext();
    console.log('[TypePartieHeros] current history state:', (boardApi.value as any).state.historyViewerState);
    syncComment();
  }
};

const passerALaSuite = async () => {
  if (!props.config || !props.config.etapes) return;
  if (etapeCouranteIndex.value === props.config.etapes.length - 1) {
    const victoryToast = await toastController.create({
      message: "Félicitations ! Vous avez terminé ce scénario !",
      duration: 3000,
      color: 'success',
      position: 'bottom'
    });
    await victoryToast.present();
    store.validerExercice(props.id);
    emit('success');
  } else {
    etapeCouranteIndex.value++;
  }
};

const validerChoix = async (index: number) => {
  if (!props.config || !props.config.etapes) return;
  const etape = etapeActuelle.value;
  if (index !== etape.bonne_reponse) {
    const toast = await toastController.create({
      message: "Mauvais choix, analyse bien la position",
      duration: 2000,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  } else {
    const toast = await toastController.create({
      message: "Bien joué !",
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();

    if (etapeCouranteIndex.value === props.config.etapes.length - 1) {
      const victoryToast = await toastController.create({
        message: "Félicitations ! Vous avez résolu tout le scénario !",
        duration: 3000,
        color: 'success',
        position: 'bottom'
      });
      await victoryToast.present();
      store.validerExercice(props.id);
      emit('success');
    } else {
      etapeCouranteIndex.value++;
    }
  }
};
</script>

<style scoped>
.exercice-type-partie-heros {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.etape-container {
  width: 100%;
  max-width: 500px;
  display: flex;
  flex-direction: column;
  gap: 16px;
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

.navigation-controls {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin-top: 8px;
}

.nav-btn {
  --border-radius: 50%;
  width: 48px;
  height: 48px;
}

.action-container {
  width: 100%;
  margin-top: 12px;
}

.continue-btn {
  font-weight: 600;
  text-transform: none;
  --border-radius: 8px;
}

.question-card {
  margin: 12px 0 0 0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.question-title {
  font-size: 1.1rem;
  font-weight: 600;
  line-height: 1.4;
  text-align: center;
  color: var(--ion-color-step-900, #222);
}

.qcm-choices {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 8px;
}

.choice-btn {
  text-transform: none;
  font-weight: 500;
  --border-radius: 8px;
  min-height: 44px;
}

.comment-container {
  width: 100%;
  margin-top: 12px;
  background: var(--ion-color-step-100, #f4f5f8);
  border-radius: 8px;
  border-left: 4px solid var(--ion-color-primary, #3880ff);
  padding: 12px 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  height: 72px; /* Hauteur fixe d'environ 3 lignes */
  overflow-y: auto;
}

.comment-text {
  margin: 0;
  font-size: 0.92rem;
  line-height: 1.4;
  color: var(--ion-color-step-800, #444);
}

.placeholder-text {
  color: var(--ion-color-step-400, #989aa2);
  font-style: italic;
}
</style>
