<template>
  <div class="exercice-type-partie-heros">
    <!-- Étape Actuelle -->
    <div class="etape-container">
      <!-- Rendu pour le type PGN -->
      <div v-if="etapeActuelle.type === 'pgn'" class="pgn-stage-layout">
        <div class="board-container">
          <eg-chessboard
            :boardConfig="{ fen: 'start', viewOnly: true }"
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

        <div class="action-container">
          <ion-button expand="block" color="success" class="continue-btn" @click="passerALaSuite">
            Passer à la suite
            <ion-icon slot="end" :icon="arrowForwardOutline"></ion-icon>
          </ion-button>
        </div>
      </div>

      <!-- Rendu pour le type QCM -->
      <div v-else-if="etapeActuelle.type === 'qcm'" class="qcm-stage-layout">
        <div class="board-container">
          <eg-chessboard
            :boardConfig="{ fen: etapeActuelle.fen, viewOnly: true }"
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

const etapeActuelle = computed<EtapeBase>(() => {
  return props.config.etapes[etapeCouranteIndex.value];
});

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
  initEtape(etapeActuelle.value);
};

const initEtape = (etape: EtapeBase) => {
  if (!boardApi.value) return;

  if (etape.type === 'pgn' && etape.pgn) {
    boardApi.value.setPosition('start');
    boardApi.value.loadPgn(etape.pgn);
  } else if (etape.type === 'qcm' && etape.fen) {
    boardApi.value.setPosition(etape.fen);
  }
};

watch(etapeActuelle, (newEtape) => {
  if (newEtape) {
    initEtape(newEtape);
  }
});

const viewStart = () => {
  boardApi.value?.viewStart();
};

const viewPrevious = () => {
  boardApi.value?.viewPrevious();
};

const viewNext = () => {
  boardApi.value?.viewNext();
};

const passerALaSuite = async () => {
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
</style>
