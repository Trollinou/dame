<template>
  <div class="jugement-final-layout">
    <!-- Card Consigne -->
    <ion-card v-if="consigne" class="consigne-card">
      <ion-card-header>
        <ion-card-title class="consigne-title">{{ consigne }}</ion-card-title>
      </ion-card-header>
    </ion-card>

    <!-- PHASE 1 : OBSERVATION -->
    <div v-if="phase === 'observation'" class="observation-stage">
      <!-- Grande FEN de départ -->
      <div class="main-board-wrapper">
        <div class="main-board-container">
          <eg-chessboard
            :boardConfig="mainBoardConfig"
            :playerColor="playerColorTyped"
            :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
            @board-created="onMainBoardCreated"
          />
        </div>
        <div class="board-caption">Position de départ</div>
      </div>

      <!-- Les 3 Scénarios -->
      <div class="scenarios-container">
        <div
          v-for="(scenario, index) in scenarios"
          :key="index"
          class="scenario-card"
          @click="selectScenario(index)"
        >
          <div class="scenario-header">
            <span class="scenario-badge">Plan {{ index + 1 }}</span>
            <span class="scenario-hint">Toucher pour choisir</span>
          </div>
          <div class="scenario-board-container">
            <eg-chessboard
              :boardConfig="getScenarioBoardConfig()"
              :playerColor="playerColorTyped"
              :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
              @board-created="(api: any) => onScenarioBoardCreated(index, api)"
              style="pointer-events: none;"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- PHASE 2 : EXPLICATION -->
    <div v-else-if="phase === 'explication'" class="explication-stage">
      <ion-card class="consigne-card">
        <ion-card-header>
          <ion-card-title class="consigne-title">💡 Explication du plan gagnant</ion-card-title>
        </ion-card-header>
      </ion-card>

      <PgnViewer
        :pgnString="pgnExplication"
        :autoCompleteDelay="0"
        @finished="onSuccess"
      />

      <ion-button
        expand="block"
        color="success"
        class="finish-btn"
        @click="onSuccess"
      >
        Terminer l'exercice
      </ion-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonButton,
  toastController
} from '@ionic/vue';
import EgChessboard from 'eg-chessboard/vue';
import { Chess } from 'chess.js';
import PgnViewer from '@/components/shared/PgnViewer.vue';

export interface ScenarioJugementFinal {
  pgn: string;
  is_correct: boolean;
}

const props = withDefaults(
  defineProps<{
    consigne?: string;
    fenDepart?: string;
    couleurJoueur?: 'white' | 'black';
    scenarios?: ScenarioJugementFinal[];
    pgnExplication?: string;
  }>(),
  {
    consigne: '',
    fenDepart: '',
    couleurJoueur: 'white',
    scenarios: () => [],
    pgnExplication: ''
  }
);

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const phase = ref<'observation' | 'explication'>('observation');
const mainBoardApi = ref<any>(null);
const boardApis = ref<Map<number, any>>(new Map());
const playbackTimers = ref<Map<number, ReturnType<typeof setTimeout>>>(new Map());

const playerColorTyped = computed<'white' | 'black'>(() => {
  return props.couleurJoueur === 'black' ? 'black' : 'white';
});

const mainBoardConfig = computed(() => ({
  fen: props.fenDepart || 'start',
  orientation: playerColorTyped.value,
  viewOnly: true
}));

const getScenarioBoardConfig = () => ({
  fen: props.fenDepart || 'start',
  orientation: playerColorTyped.value,
  viewOnly: true
});

const onMainBoardCreated = (api: any) => {
  mainBoardApi.value = api;
  if (props.fenDepart) {
    api.setPosition(props.fenDepart);
  }
};

const clearScenarioTimer = (index: number) => {
  if (playbackTimers.value.has(index)) {
    clearTimeout(playbackTimers.value.get(index)!);
    playbackTimers.value.delete(index);
  }
};

const clearAllTimers = () => {
  playbackTimers.value.forEach((timer) => clearTimeout(timer));
  playbackTimers.value.clear();
};

onBeforeUnmount(() => {
  clearAllTimers();
});

const getMoveSequence = (fenDepart: string, pgnString: string) => {
  if (!pgnString) return [];
  const cleaned = pgnString.trim().replace(/(\.\.\.|\.\.|\.)$/, '').trim();

  // Primary parsing using chess.js
  try {
    const chess = new Chess(fenDepart && fenDepart !== 'start' ? fenDepart : undefined);
    if (fenDepart && fenDepart !== 'start' && !cleaned.includes('[FEN ')) {
      const pgnWithHeader = `[SetUp "1"]\n[FEN "${fenDepart}"]\n\n${cleaned}`;
      chess.loadPgn(pgnWithHeader);
    } else {
      chess.loadPgn(cleaned);
    }
    const moves = chess.history({ verbose: true });
    if (moves.length > 0) return moves;
  } catch (e) {
    console.warn('loadPgn standard parse failed, fallback to token parsing', e);
  }

  // Token fallback parsing for partial or custom SAN streams
  try {
    const chess = new Chess(fenDepart && fenDepart !== 'start' ? fenDepart : undefined);
    const textWithoutComments = cleaned.replace(/\{[^}]*\}/g, '').replace(/\d+\.\.\.|\d+\./g, '').trim();
    const tokens = textWithoutComments.split(/\s+/).filter(t => t && t !== '...');
    const moves = [];
    for (const token of tokens) {
      const m = chess.move(token);
      if (m) {
        moves.push(m);
      } else {
        break;
      }
    }
    return moves;
  } catch {
    return [];
  }
};

const startScenarioPlayback = (index: number, api: any) => {
  clearScenarioTimer(index);

  const scenario = props.scenarios[index];
  if (!scenario || !scenario.pgn) return;

  const moves = getMoveSequence(props.fenDepart, scenario.pgn);
  if (moves.length === 0) return;

  let currentMoveIndex = 0;

  const playStep = () => {
    if (phase.value !== 'observation') return;

    if (currentMoveIndex < moves.length) {
      const moveObj = moves[currentMoveIndex];
      try {
        api.move(moveObj.san);
      } catch {
        // Ignorer si le coup échoue
      }
      currentMoveIndex++;
      const timer = setTimeout(playStep, 1000);
      playbackTimers.value.set(index, timer);
    } else {
      // Fin de la séquence : pause de 2500ms, réinitialisation FEN, puis reprise
      const timer = setTimeout(() => {
        if (phase.value !== 'observation') return;
        api.setPosition(props.fenDepart || 'start');
        currentMoveIndex = 0;
        const restartTimer = setTimeout(playStep, 1000);
        playbackTimers.value.set(index, restartTimer);
      }, 2500);
      playbackTimers.value.set(index, timer);
    }
  };

  api.setPosition(props.fenDepart || 'start');
  const initialTimer = setTimeout(playStep, 1000);
  playbackTimers.value.set(index, initialTimer);
};

const onScenarioBoardCreated = (index: number, api: any) => {
  boardApis.value.set(index, api);
  startScenarioPlayback(index, api);
};

watch(() => props.fenDepart, (newFen) => {
  if (mainBoardApi.value && newFen) {
    mainBoardApi.value.setPosition(newFen);
  }
  boardApis.value.forEach((api, index) => {
    startScenarioPlayback(index, api);
  });
});

const selectScenario = async (index: number) => {
  if (phase.value !== 'observation') return;

  const scenario = props.scenarios[index];
  if (!scenario) return;

  if (scenario.is_correct) {
    clearAllTimers();
    phase.value = 'explication';
    const toast = await toastController.create({
      message: "Excellente analyse ! Voici l'explication détaillée.",
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();
  } else {
    const toast = await toastController.create({
      message: "Ce n'est pas le meilleur plan, observez encore !",
      duration: 2500,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  }
};

const onSuccess = () => {
  emit('success');
};
</script>

<style scoped>
.jugement-final-layout {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.consigne-card {
  width: 100%;
  max-width: 900px;
  margin: 0 0 16px 0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.consigne-title {
  font-size: 1.15rem;
  font-weight: 600;
  text-align: center;
}

/* Phase Observation Stage */
.observation-stage {
  width: 100%;
  max-width: 900px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

@media (min-width: 769px) {
  .observation-stage {
    flex-direction: row;
    align-items: flex-start;
  }
}

/* Main FEN Section */
.main-board-wrapper {
  flex: 1 1 50%;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.main-board-container {
  width: 100%;
  aspect-ratio: 1 / 1;
  max-width: 480px;
  margin: 0 auto;
  border-radius: 0;
  overflow: hidden;
  box-shadow: none;
}

.board-caption {
  margin-top: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  color: var(--ion-color-primary, #3880ff);
  text-align: center;
}

/* Scenarios Section */
.scenarios-container {
  flex: 1 1 50%;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Individual Scenario Card Item */
.scenario-card {
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
  border-radius: 12px;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 2px solid transparent;
  background: var(--ion-card-background, var(--ion-item-background, #fff));
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
}

.scenario-card:hover,
.scenario-card:active {
  transform: translateY(-2px);
  border-color: var(--ion-color-primary, #3880ff);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
}

.scenario-header {
  padding: 10px 14px 6px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.scenario-badge {
  font-weight: 700;
  font-size: 0.9rem;
  color: var(--ion-color-primary, #3880ff);
}

.scenario-hint {
  font-size: 0.8rem;
  color: var(--ion-color-step-600, #666);
}

.scenario-board-container {
  width: 100%;
  aspect-ratio: 1 / 1;
  max-width: 320px;
  margin: 0 auto 10px auto;
  border-radius: 0;
  overflow: hidden;
  box-shadow: none;
}

/* Phase Explication */
.explication-stage {
  width: 100%;
  max-width: 600px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.finish-btn {
  margin-top: 16px;
  width: 100%;
  max-width: 320px;
  font-weight: 600;
}
</style>
