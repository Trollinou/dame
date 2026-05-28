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
        
        <div class="main-container" :class="{ 'landscape-mode': isLandscape }">
          <div class="board-section">
            <div class="board-container">
              <!-- Échiquier (Mode Lecture Seule) -->
              <TheChessboard 
                v-if="isReady"
                :key="`board-${isLandscape ? 'l' : 'p'}-${renderKey}`"
                :board-config="boardConfig" 
                @board-created="handleBoardCreated"
                @move="handleMove"
              />
            </div>
          </div>

          <!-- Section Historique et Navigation -->
          <div class="side-section" v-if="isReady">
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

                <!-- Historique structuré en tableau -->
                <div class="move-history-container" ref="historyScrollContainer">
                  <div class="move-table">
                    <div v-for="(row, rIdx) in groupedHistory" :key="rIdx" class="move-table-row">
                      <div v-for="move in row" :key="move.number" class="move-group">
                        <span class="move-num">{{ move.number }}.</span>
                        <span 
                          :class="['move-san', { 'active-move': move.white.ply === currentPly }]"
                          @click="viewPly(move.white.ply)"
                          v-safe-html="formatSan(move.white.san)"
                        ></span>
                        <span 
                          v-if="move.black"
                          :class="['move-san', { 'active-move': move.black.ply === currentPly }]"
                          @click="viewPly(move.black.ply)"
                          v-safe-html="formatSan(move.black.san)"
                        ></span>
                        <span v-else class="move-san empty"></span>
                      </div>
                      <!-- Cellules fantômes pour garder l'alignement sur la dernière ligne -->
                      <div 
                        v-for="i in (movesPerRow - row.length)" 
                        :key="'empty-' + i" 
                        class="move-group empty-group"
                      ></div>
                    </div>
                    <div v-if="historyMoves.length === 0" class="status-placeholder">
                      Aucun coup à analyser.
                    </div>
                  </div>
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
import { ref, onMounted, onUnmounted, reactive, watch, computed } from 'vue';
import { TheChessboard } from 'vue3-chessboard';
import 'vue3-chessboard/style.css';
import { useChessStore } from '@/stores/chess';

const chessStore = useChessStore();
let boardApi: any = null;
const isReady = ref(false);
const currentPly = ref(0);
const historyMoves = ref<any[]>([]);
const historyScrollContainer = ref<HTMLElement | null>(null);

const isLandscape = ref(window.innerWidth > window.innerHeight);
const renderKey = ref(0);
let resizeTimeout: any = null;

const updateOrientation = () => {
  if (resizeTimeout) clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(() => {
    isLandscape.value = window.innerWidth > window.innerHeight;
    renderKey.value++;
  }, 200);
};

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
 * Nombre de colonnes (tours) par ligne
 */
const movesPerRow = computed(() => isLandscape.value ? 2 : 3);

/**
 * Groupe les coups pour l'affichage en tableau (économise de l'espace vertical)
 */
const groupedHistory = computed(() => {
  const groups = [];
  for (let i = 0; i < historyMoves.value.length; i += movesPerRow.value) {
    groups.push(historyMoves.value.slice(i, i + movesPerRow.value));
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
        
        // Calcul plus robuste utilisant getBoundingClientRect
        const containerRect = container.getBoundingClientRect();
        const activeRect = activeEl.getBoundingClientRect();
        
        // Calcule la position relative de l'élément par rapport au conteneur
        const relativeTop = activeRect.top - containerRect.top + container.scrollTop;
        
        // Centre l'élément dans le conteneur
        const topPos = relativeTop - (container.clientHeight / 2) + (activeRect.height / 2);
        
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
  window.addEventListener('resize', updateOrientation);
  isReady.value = true;
});

onUnmounted(() => {
  window.removeEventListener('resize', updateOrientation);
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
  padding-bottom: 10px; /* Ajoute un léger espace au-dessus de la barre d'onglets */
}

.main-container {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  width: 100%;
  margin: 0 auto;
}

.main-container.landscape-mode {
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 30px;
  max-width: none;
}

.board-section {
  display: block; /* Stabilité des clics en mode portrait */
  width: 100%;
}

.landscape-mode .board-section {
  flex: 0 0 auto;
}

.board-container {
  width: 100%;
  max-width: 500px;
  margin: 0 auto; /* Centrage horizontal natif */
  flex-shrink: 0;
  position: relative;
}

/* Agrandissement tablettes Portrait */
@media (min-width: 768px) and (orientation: portrait) {
  .board-container {
    max-width: 760px; /* Augmenté pour mieux remplir l'iPad */
  }
}

/* --- STABILITÉ LANDSCAPE --- */
.landscape-mode :deep(.main-wrap) {
  width: 100% !important;
  height: 100% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  max-width: none !important;
}

.landscape-mode :deep(.main-board) {
  width: 100% !important;
  height: 100% !important;
  max-width: none !important;
}

.landscape-mode :deep(.cg-wrap) {
  width: 100% !important;
  height: 100% !important;
  aspect-ratio: 1 / 1 !important;
}

.landscape-mode .board-container {
  /* En paysage classique (iPad), on maximise la taille */
  width: 80vh !important;
  height: 80vh !important;
  max-width: 100%;
  margin: 0 auto !important;
  display: flex;
  flex-direction: column;
}

.landscape-mode .board-section {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  justify-content: center;
  height: 100%;
  width: auto; /* Empêche l'échiquier de pousser l'historique dehors */
}

.main-container.landscape-mode {
  display: flex;
  flex-direction: row;
  align-items: center; /* Centrage vertical */
  justify-content: center;
  gap: 30px;
  flex: 1;
  min-height: 0;
  width: 100%;
  max-width: none;
  margin: 0 !important;
}

.side-section {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 250px;
  min-height: 0; /* CRUCIAL: Permet au conteneur de rétrécir et d'activer le scroll interne */
  height: 100%;
}

.landscape-mode .side-section {
  max-width: 400px;
  max-height: 85vh; /* S'aligne avec la taille de l'échiquier sur iPad */
}

/* Optimisation iPhone Paysage */
@media (max-height: 500px) and (orientation: landscape) {
  ion-header { display: none !important; }
  .analysis-layout { padding-top: 2px !important; height: 100% !important; }
  
  .main-container.landscape-mode {
    align-items: center !important; /* Centrage vertical au lieu de stretch */
    padding-bottom: 2px !important;
    gap: 15px;
  }

  .landscape-mode .board-container { 
    width: 75vh !important; 
    height: 75vh !important; 
  }
  
  .side-section { 
    min-width: 180px !important;
    height: 75vh !important; /* Calé EXACTEMENT sur la taille de l'échiquier */
    max-height: none;
  }

  .history-controls {
    padding: 2px 2px !important; /* Donne de l'espace en haut pour les flèches */
  }

  .history-controls ion-grid {
    flex: 0 0 32px !important; 
    margin-bottom: 2px !important;
  }

  .history-controls ion-button {
    height: 28px !important;
    margin: 0 !important;
  }

  .move-history-container {
    font-size: 0.75rem !important;
    margin-top: 2px;
  }

  .move-group { padding: 4px 2px !important; font-size: 0.8rem !important; }
  .move-num { 
    width: 26px !important; /* Augmenté pour éviter le retour à la ligne du point */
    white-space: nowrap !important;
  }
}

.analysis-controls {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
  padding: 0; 
}

.landscape-mode .analysis-controls {
  padding: 0;
}

.history-controls {
  background: var(--ion-color-light);
  border-radius: 12px;
  padding: 5px;
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

/* Barre de navigation verrouillée en haut */
.history-controls ion-grid {
  flex: 0 0 auto !important;
  padding: 0 !important;
  margin: 0 auto !important; /* Centrage de la grille elle-même */
  max-width: 300px; /* Limite la largeur des flèches uniquement */
  width: 100%;
}

.history-controls ion-row {
  justify-content: center;
}

.history-controls ion-col {
  padding: 0 2px !important; /* Ajoute un écart de 4px au total entre les flèches */
  flex: 1; /* Distribue l'espace équitablement dans la limite des 300px */
  display: flex;
  justify-content: center;
}

.history-controls ion-button {
  height: 36px !important;
  margin: 0;
  --padding-top: 0;
  --padding-bottom: 0;
}

/* Optimisation spécifique mobile portrait */
@media (max-width: 767px) and (orientation: portrait) {
  .board-section {
    padding-bottom: 5px !important;
  }
  .analysis-controls {
    padding-top: 0 !important;
  }
}

.move-history-container {
  flex: 1 !important;
  min-height: 0; /* Crucial pour le défilement */
  overflow-y: auto;
  background: #fff;
  border-radius: 8px;
  padding: 5px;
  border: 1px solid var(--ion-color-light-shade);
  margin-top: 5px;
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
