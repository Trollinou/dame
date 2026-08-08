<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar color="primary">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/home"></ion-back-button>
        </ion-buttons>
        <ion-title>Espace de Jeu</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" :scroll-y="false">
      <div class="game-layout safe-area-wrapper ion-padding-horizontal">
        <!-- Barre d'Information (Haut) -->
        <PlayInfoBar
          v-if="engineLoaded"
          :player-name="authStore.selectedIdentity?.firstname || 'Adhérent'"
          :level="gameSettings.level"
          :formatted-time="formattedTime"
        />

        <div class="main-container" :class="{ 'landscape-mode': isLandscape }">
          <div class="board-section">
            <div class="board-container">
              <!-- Bande supérieure (Adversaire) -->
              <CapturedPiecesBar
                position="top"
                side="opponent"
                player-label="Adversaire"
                :captured-pieces="capturedByOpponent"
                :material-diff="materialDiffDisplay.opponent"
                :clock-preset="clockSettings.preset"
                :formatted-time="opponentFormattedTime"
                :is-clock-active="activeClockColor === opponentColor"
              />

              <!-- Échiquier -->
              <div class="board-wrapper-with-bar">
                <TheChessboard
                  v-if="engineLoaded"
                  :key="`board-${isLandscape ? 'l' : 'p'}-${renderKey}`"
                  fit-container
                  :board-config="boardConfig"
                  :player-color="boardConfig.playerColor"
                  :stockfish-config="stockfishConfig"
                  @board-created="handleBoardCreated"
                  @move="handleMove"
                  @check="handleCheck"
                  @checkmate="() => handleGameOver('checkmate', timerSeconds)"
                  @stalemate="() => handleGameOver('stalemate', timerSeconds)"
                  @draw="() => handleGameOver('draw', timerSeconds)"
                  @stockfish-hint="handleStockfishHint"
                />
              </div>

              <!-- Bande inférieure (Joueur) -->
              <CapturedPiecesBar
                position="bottom"
                side="player"
                player-label="Toi"
                :captured-pieces="capturedByPlayer"
                :material-diff="materialDiffDisplay.player"
                :clock-preset="clockSettings.preset"
                :formatted-time="playerFormattedTime"
                :is-clock-active="activeClockColor === playerColor"
              />
            </div>
          </div>

          <!-- Panneau d'actions & statuts -->
          <PlayActionsPanel
            v-if="engineLoaded"
            :is-landscape="isLandscape"
            :game-status-message="gameStatus.message"
            :game-status-color="gameStatus.color"
            :turnColor="getTurnColor()"
            :is-hint-enabled="isHintEnabled"
            :help-count="helpCount"
            :oups-count="oupsCount"
            :view-only="boardConfig.viewOnly"
            @reset-game="resetGame"
            @toggle-hint="toggleHint"
            @undo-move="undoMove"
            @go-to-analysis="goToAnalysis"
          />

          <div v-if="!engineLoaded" class="ion-text-center ion-padding board-section">
            <ion-spinner name="crescent"></ion-spinner>
            <p>Initialisation de l'IA...</p>
          </div>
        </div>

        <!-- Modal de réglages -->
        <PlaySettingsModal
          v-model:is-open="showSettings"
          v-model:show-material-indicator="showMaterialIndicator"
          @start-game="startNewGame"
        />
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { computed, onMounted, provide } from 'vue';
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonBackButton,
  IonSpinner,
  onIonViewWillLeave
} from '@ionic/vue';
import TheChessboard from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { StockfishConfig } from 'eg-chessboard';
import { useAuthStore } from '@/stores/auth';
import { useChessStore } from '@/stores/chess';
import { getFormattedCapturedPieces, getMaterialDiffDisplay } from '@/utils/boardApiWrapper';
import { useBoardOrientation } from '@/composables/play/useBoardOrientation';
import { usePlayClock } from '@/composables/play/usePlayClock';
import { usePlayGame } from '@/composables/play/usePlayGame';
import PlayInfoBar from '@/components/play/PlayInfoBar.vue';
import CapturedPiecesBar from '@/components/play/CapturedPiecesBar.vue';
import PlayActionsPanel from '@/components/play/PlayActionsPanel.vue';
import PlaySettingsModal from '@/components/play/PlaySettingsModal.vue';

const authStore = useAuthStore();
const chessStore = useChessStore();

const { isLandscape, renderKey } = useBoardOrientation();

const {
  clock,
  clockSettings,
  activeClockColor,
  timerSeconds,
  formattedTime,
  getPlayerFormattedTime,
  getOpponentFormattedTime,
  startTimer,
  stopTimer,
  resetClock,
} = usePlayClock();

const {
  getBoardApi,
  setBoardApi,
  engineLoaded,
  showSettings,
  isHintEnabled,
  oupsCount,
  helpCount,
  lastSuggestedMove,
  showMaterialIndicator,
  gameStatus,
  gameSettings,
  boardConfig,
  playerColor,
  opponentColor,
  toggleHint,
  handleStockfishHint,
  goToAnalysis,
  refreshDisplay,
  undoMove,
  handleCheck,
  handleGameOver,
} = usePlayGame();

provide('gameSettings', gameSettings);

const playerFormattedTime = computed(() => getPlayerFormattedTime(playerColor.value));
const opponentFormattedTime = computed(() => getOpponentFormattedTime(opponentColor.value));

clock.onTimeOut = (flaggedColor) => {
  stopTimer();
  boardConfig.viewOnly = true;
  clock.setActiveColor(null);
  activeClockColor.value = null;

  const winner = flaggedColor === 'white' ? 'Noirs' : 'Blancs';
  gameStatus.message = `🏁 Perdu au temps ! Les ${winner} ont gagné.`;
  gameStatus.color = 'danger';

  chessStore.saveCompletedGame(timerSeconds.value);
  setTimeout(() => {
    renderKey.value++;
  }, 100);
};

const getWorkerUrl = () => {
  const base = authStore.stockfishUrl;
  if (base) return `${base}stockfish.js`;
  return import.meta.env.DEV
    ? '/stockfish/stockfish.js'
    : new URL(/* @vite-ignore */ '../stockfish/stockfish.js', import.meta.url).href;
};

const stockfishConfig = computed<StockfishConfig>(() => {
  const playerCol = boardConfig.orientation;
  return {
    whiteMode: playerCol === 'white' ? 'hint' : 'elo',
    whiteElo: playerCol === 'black' ? gameSettings.level : undefined,
    blackMode: playerCol === 'black' ? 'hint' : 'elo',
    blackElo: playerCol === 'white' ? gameSettings.level : undefined,
    stockfishMoveTime: 2000,
    workerUrl: getWorkerUrl(),
    wasmUrl: authStore.wasmUrl || undefined,
  };
});

const getTurnColor = (): 'white' | 'black' => {
  const api = getBoardApi();
  return api ? api.getTurnColor() : 'white';
};

const capturedByPlayer = computed(() => {
  const api = getBoardApi();
  if (!api) return [];
  const captures = getFormattedCapturedPieces(api, showMaterialIndicator.value);
  return boardConfig.orientation === 'white' ? captures.white : captures.black;
});

const capturedByOpponent = computed(() => {
  const api = getBoardApi();
  if (!api) return [];
  const captures = getFormattedCapturedPieces(api, showMaterialIndicator.value);
  return boardConfig.orientation === 'white' ? captures.black : captures.white;
});

const materialDiffDisplay = computed(() => {
  const api = getBoardApi();
  if (!api) return { player: null, opponent: null };
  return getMaterialDiffDisplay(api, boardConfig.orientation, showMaterialIndicator.value);
});

const handleBoardCreated = (api: any) => {
  setBoardApi(api);
};

const handleMove = (moveInfo?: any) => {
  const api = getBoardApi();
  if (!api) return;

  if (boardConfig.viewOnly) {
    api.undoLastMove();
    return;
  }

  refreshDisplay();

  if (moveInfo && lastSuggestedMove.value && isHintEnabled.value) {
    const playedMove = moveInfo.from + moveInfo.to;
    if (playedMove === lastSuggestedMove.value) {
      helpCount.value++;
    }
  }

  const justFinishedColor = api.getTurnColor() === 'white' ? 'black' : 'white';
  const plyCount = api.getCurrentPlyNumber();

  clock.applyIncrement(justFinishedColor, plyCount);
  clockSettings.wtime = clock.wtime;
  clockSettings.btime = clock.btime;

  chessStore.saveGame(
    api.getPgn(),
    boardConfig.orientation,
    gameSettings.level,
    helpCount.value,
    oupsCount.value
  );

  lastSuggestedMove.value = '';

  if (plyCount === 1) {
    startTimer();
  }

  clock.setActiveColor(api.getTurnColor());
  activeClockColor.value = clock.activeColor;
  api.hideMoves();

  if (gameStatus.color === 'warning') {
    gameStatus.message = '';
    gameStatus.color = 'medium';
  }
};

const resetGame = () => {
  showSettings.value = true;
};

const startNewGame = () => {
  showSettings.value = false;
  gameStatus.message = '';
  gameStatus.color = 'medium';
  boardConfig.viewOnly = false;

  resetClock(gameSettings.clockPreset);
  oupsCount.value = 0;
  helpCount.value = 0;
  lastSuggestedMove.value = '';
  activeClockColor.value = null;

  chessStore.clearGame();

  let finalColor: 'white' | 'black' = 'white';
  if (gameSettings.playerColor === 'random') {
    finalColor = Math.random() > 0.5 ? 'white' : 'black';
  } else {
    finalColor = gameSettings.playerColor;
  }
  boardConfig.orientation = finalColor;
  boardConfig.playerColor = finalColor;
  boardConfig.movable.color = finalColor;
  renderKey.value++;

  const api = getBoardApi();
  if (api) {
    api.resetBoard();
    refreshDisplay();
  }

  if (finalColor === 'black') {
    clock.setActiveColor('white');
    activeClockColor.value = clock.activeColor;
    startTimer();
  }
};

onMounted(() => {
  const eloRaw = String(authStore.selectedIdentity?.elo_rapide || '1320');
  const match = eloRaw.match(/\d+/);
  const eloNum = match ? parseInt(match[0], 10) : 1320;
  gameSettings.level = isNaN(eloNum) || eloNum < 1320 ? 1320 : eloNum > 2800 ? 2800 : eloNum;
  engineLoaded.value = true;
});

onIonViewWillLeave(() => {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur();
  }
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.game-layout {
  display: flex;
  flex-direction: column;
  height: 100%;
  justify-content: flex-start;
  overflow: hidden;
}

.main-container {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  width: 100%;
  max-width: 800px;
  margin: 0 auto;
}

.main-container.landscape-mode {
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 20px;
  max-width: 100%;
}

.board-section {
  display: block;
  width: 100%;
}

.landscape-mode .board-section {
  flex: 0 0 auto;
  width: auto;
}

.board-container {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
  flex-shrink: 0;
  position: relative;
}

@media (min-width: 768px) and (orientation: portrait) {
  .board-container {
    max-width: min(720px, 60vh);
  }

  .board-section {
    padding: 10px 0;
  }
}

.landscape-mode :deep(.main-wrap) {
  width: 100% !important;
  height: 100% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.landscape-mode :deep(.main-board) {
  width: 100% !important;
  height: 100% !important;
}

.landscape-mode .board-container {
  width: min(65vh, 48vw) !important;
  height: auto !important;
  aspect-ratio: 1 / 1 !important;
  max-width: 100%;
  margin: 0 auto !important;
  padding: 0 !important;
  display: flex;
  flex-direction: column;
}
</style>
