<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar color="tertiary">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/play"></ion-back-button>
        </ion-buttons>
        <ion-title>Analyse de la partie</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" :scroll-y="false">
      <div class="analysis-layout safe-area-wrapper ion-padding-horizontal">
        <div class="board-container ion-margin-top">
          <!-- Échiquier (Mode Lecture Seule) -->
          <TheChessboard 
            v-if="isReady"
            :board-config="boardConfig" 
            @board-created="handleBoardCreated"
            @move="handleMove"
          />
        </div>

        <!-- Section Historique et Navigation -->
        <div class="analysis-controls">
          <div class="history-controls">
            <!-- Navigation toolbar -->
            <ion-grid class="ion-no-padding">
              <ion-row>
                <ion-col size="3">
                  <ion-button fill="clear" @click="viewFirst">
                    <ion-icon slot="icon-only" :icon="playBackOutline"></ion-icon>
                  </ion-button>
                </ion-col>
                <ion-col size="3">
                  <ion-button fill="clear" @click="viewPrev">
                    <ion-icon slot="icon-only" :icon="chevronBackOutline"></ion-icon>
                  </ion-button>
                </ion-col>
                <ion-col size="3">
                  <ion-button fill="clear" @click="viewNext">
                    <ion-icon slot="icon-only" :icon="chevronForwardOutline"></ion-icon>
                  </ion-button>
                </ion-col>
                <ion-col size="3">
                  <ion-button fill="clear" @click="viewLast">
                    <ion-icon slot="icon-only" :icon="playForwardOutline"></ion-icon>
                  </ion-button>
                </ion-col>
              </ion-row>
            </ion-grid>

            <!-- Historique structuré en tableau (3 tours par ligne) -->
            <div class="move-history-container" ref="historyScrollContainer">
              <div class="move-table">
                <div v-for="(row, rIdx) in groupedHistory" :key="rIdx" class="move-table-row">
                  <div v-for="move in row" :key="move.number" class="move-group">
                    <span class="move-num">{{ move.number }}.</span>
                    <span 
                      :class="['move-san', { 'active-move': move.white.ply === currentPly }]"
                      @click="viewPly(move.white.ply)"
                      v-html="formatSan(move.white.san)"
                    ></span>
                    <span 
                      v-if="move.black"
                      :class="['move-san', { 'active-move': move.black.ply === currentPly }]"
                      @click="viewPly(move.black.ply)"
                      v-html="formatSan(move.black.san)"
                    ></span>
                    <span v-else class="move-san empty"></span>
                  </div>
                </div>
                <div v-if="historyMoves.length === 0" class="status-placeholder">
                  Aucun coup à analyser.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonBackButton,
  IonGrid,
  IonRow,
  IonCol,
  IonButton,
  IonIcon,
  onIonViewWillLeave
} from '@ionic/vue';
import { 
  playBackOutline, 
  chevronBackOutline, 
  chevronForwardOutline, 
  playForwardOutline 
} from 'ionicons/icons';
import { ref, onMounted, reactive, watch, computed } from 'vue';
import { TheChessboard } from 'vue3-chessboard';
import 'vue3-chessboard/style.css';
import { useChessStore } from '@/stores/chess';

const chessStore = useChessStore();
let boardApi: any = null;
const isReady = ref(false);
const currentPly = ref(0);
const historyMoves = ref<any[]>([]);
const historyScrollContainer = ref<HTMLElement | null>(null);

const boardConfig = reactive({
  coordinates: true,
  orientation: chessStore.orientation,
  viewOnly: true,
});

/**
 * Formate un coup SAN avec symbole de pièce agrandi
 */
const formatSan = (san: string) => {
  const symbols: Record<string, string> = {
    'K': '♚', 'Q': '♛', 'R': '♜', 'B': '♝', 'N': '♞'
  };
  return san.replace(/([KQRBN])/g, (match) => `<span class="chess-piece-symbol">${symbols[match]}</span>`);
};

/**
 * Organise l'historique brut en liste structurée
 */
const updateMoveHistory = () => {
  if (!boardApi) return;
  const rawHistory = boardApi.getHistory();
  const moves: any[] = [];
  for (let i = 0; i < rawHistory.length; i += 2) {
    moves.push({
      number: Math.floor(i / 2) + 1,
      white: { san: rawHistory[i], ply: i + 1 },
      black: rawHistory[i + 1] ? { san: rawHistory[i + 1], ply: i + 2 } : null
    });
  }
  historyMoves.value = moves;
};

/**
 * Groupe les coups par 3 pour l'affichage en tableau (économise de l'espace vertical)
 */
const groupedHistory = computed(() => {
  const groups = [];
  for (let i = 0; i < historyMoves.value.length; i += 3) {
    groups.push(historyMoves.value.slice(i, i + 3));
  }
  return groups;
});

/**
 * Scroll automatique du conteneur d'historique lors du changement de coup
 */
watch(currentPly, () => {
  setTimeout(() => {
    if (historyScrollContainer.value) {
      const activeEl = historyScrollContainer.value.querySelector('.active-move') as HTMLElement;
      if (activeEl) {
        const container = historyScrollContainer.value;
        const topPos = activeEl.offsetTop - (container.offsetHeight / 2) + (activeEl.offsetHeight / 2);
        container.scrollTo({ top: topPos, behavior: 'smooth' });
      }
    }
  }, 100);
});

/**
 * Met à jour les informations d'affichage réactives
 */
const refreshDisplay = () => {
  if (boardApi) {
    currentPly.value = boardApi.getCurrentPlyNumber();
    updateMoveHistory();
  }
};

const viewPly = (ply: number) => {
  boardApi?.viewHistory(ply);
  currentPly.value = ply;
};

const handleBoardCreated = (api: any) => {
  boardApi = api;
  if (chessStore.currentPgn) {
    boardApi.loadPgn(chessStore.currentPgn);
  }
  refreshDisplay();
};

/**
 * Nécessaire pour mettre à jour lors de la navigation
 */
const handleMove = () => {
  refreshDisplay();
};

const viewFirst = () => { 
  boardApi?.viewHistory(0); 
  currentPly.value = 0; 
};

const viewPrev = () => { 
  boardApi?.viewPrevious(); 
  if (currentPly.value > 0) currentPly.value--; 
};

const viewNext = () => { 
  boardApi?.viewNext(); 
  if (boardApi && currentPly.value < boardApi.getCurrentPlyNumber()) {
    currentPly.value++;
  }
};

const viewLast = () => { 
  boardApi?.stopViewingHistory(); 
  if (boardApi) currentPly.value = boardApi.getCurrentPlyNumber(); 
};
onMounted(() => {
  isReady.value = true;
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

.analysis-layout {
  display: flex;
  flex-direction: column;
  height: 100%;
  justify-content: flex-start;
  overflow: hidden;
}

.board-container {
  max-width: 100%;
  width: 500px;
  margin: 0 auto;
  flex-shrink: 0;
}

.analysis-controls {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
  padding: 10px 0;
}

.history-controls {
  background: var(--ion-color-light);
  border-radius: 12px;
  padding: 8px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.move-history-container {
  flex-grow: 1;
  overflow-y: auto;
  background: #fff;
  border-radius: 8px;
  padding: 5px;
  border: 1px solid var(--ion-color-light-shade);
  position: relative;
}

.move-table {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.move-table-row {
  display: flex;
  border-bottom: 1px solid var(--ion-color-light);
}

.move-table-row:last-child {
  border-bottom: none;
}

.move-group {
  display: flex;
  flex: 1;
  padding: 8px 4px;
  align-items: center;
  font-family: monospace;
  font-size: 0.9rem;
  border-right: 1px solid var(--ion-color-light);
}

.move-group:last-child {
  border-right: none;
}

.move-num {
  color: var(--ion-color-medium);
  font-weight: bold;
  width: 25px;
  text-align: right;
  margin-right: 4px;
  font-size: 0.75rem;
}

.move-san {
  cursor: pointer;
  padding: 2px 4px;
  border-radius: 4px;
  flex: 1;
  text-align: center;
  white-space: nowrap;
}

.move-san.active-move {
  background: var(--ion-color-tertiary);
  color: #fff;
  font-weight: bold;
}

.move-san.empty {
  cursor: default;
}

:deep(.chess-piece-symbol) {
  font-size: 1.3em !important;
  font-weight: bold !important;
  vertical-align: middle;
}

.status-placeholder {
  color: var(--ion-color-medium);
  text-align: center;
  margin-top: 20px;
}
</style>
