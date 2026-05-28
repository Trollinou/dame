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
        
        <!-- Nouvelle Barre d'Information (Haut) -->
        <div class="game-info-bar" v-if="engineLoaded">
          <div class="matchup-info">
            {{ authStore.selectedIdentity?.firstname || 'Adhérent' }} vs Stockfish ({{ gameSettings.level }} ELO)
          </div>
          <div class="game-timer">
            {{ formattedTime }}
          </div>
        </div>

        <div class="main-container" :class="{ 'landscape-mode': isLandscape }">
          <div class="board-section">
            <div class="board-container">
              
              <!-- Bande supérieure (Adversaire) -->
              <div class="captured-bar top">
                <div class="player-info">Adversaire</div>
                <div class="captured-pieces">
                  <span v-for="(p, i) in capturedByOpponent" :key="i" class="captured-piece">
                    {{ pieceToSymbol(p) }}
                  </span>
                </div>
                <div class="material-wrapper">
                  <div v-if="materialDiffDisplay.opponent" class="material-count">
                    +{{ materialDiffDisplay.opponent }}
                  </div>
                </div>
              </div>

              <!-- Échiquier -->
              <TheChessboard 
                v-if="engineLoaded"
                :key="`board-${isLandscape ? 'l' : 'p'}-${renderKey}`"
                :board-config="boardConfig" 
                @board-created="handleBoardCreated" 
                @move="handleMove"
                @check="handleCheck"
                @checkmate="handleCheckmate"
                @stalemate="handleStalemate"
                @draw="handleDraw"
              />

              <!-- Bande inférieure (Joueur) -->
              <div class="captured-bar bottom">
                <div class="player-info">Toi</div>
                <div class="captured-pieces">
                  <span v-for="(p, i) in capturedByPlayer" :key="i" class="captured-piece">
                    {{ pieceToSymbol(p) }}
                  </span>
                </div>
                <div v-if="materialDiffDisplay.player" class="material-count">
                  +{{ materialDiffDisplay.player }}
                </div>
              </div>
            </div>
          </div>

          <div class="side-section" v-if="engineLoaded">
            <!-- Zone de messages fixe -->
            <div class="message-zone ion-text-center">
              <div v-if="gameStatus.message" :class="['status-banner', gameStatus.color]">
                {{ gameStatus.message }}
              </div>
              <div v-else class="status-placeholder">
                C'est au tour des {{ boardApi?.getTurnColor() === 'white' ? 'Blancs' : 'Noirs' }}
              </div>
            </div>
            
            <div class="actions-container">
              <ion-grid class="ion-no-padding">
                <ion-row>
                  <ion-col :size="isLandscape ? '12' : '3'" class="ion-margin-bottom">
                    <ion-button expand="block" @click="resetGame" color="secondary" style="font-size: 0.7rem; --padding-start: 2px; --padding-end: 2px;">
                      Nouv.
                    </ion-button>
                  </ion-col>
                  <ion-col :size="isLandscape ? '12' : '3'" class="ion-margin-bottom">
                    <ion-button expand="block" @click="toggleHint" :color="isHintEnabled ? 'success' : 'medium'" style="font-size: 0.7rem; --padding-start: 2px; --padding-end: 2px;">
                      {{ helpCount > 0 ? `Aide : ${helpCount}` : 'Aide' }}
                    </ion-button>
                  </ion-col>
                  <ion-col :size="isLandscape ? '12' : '3'" class="ion-margin-bottom">
                    <ion-button expand="block" @click="undoMove" color="warning" style="font-size: 0.7rem; --padding-start: 2px; --padding-end: 2px;">
                      {{ oupsCount > 0 ? `Oups : ${oupsCount}` : 'Oups !' }}
                    </ion-button>
                  </ion-col>
                  <ion-col :size="isLandscape ? '12' : '3'" class="ion-margin-bottom">
                    <ion-button expand="block" @click="goToAnalysis" color="tertiary" style="font-size: 0.7rem; --padding-start: 2px; --padding-end: 2px;">
                      Analyse
                    </ion-button>
                  </ion-col>
                </ion-row>
              </ion-grid>
            </div>
          </div>
          
          <div v-if="!engineLoaded" class="ion-text-center ion-padding board-section">
            <ion-spinner name="crescent"></ion-spinner>
            <p>Initialisation de l'IA...</p>
          </div>
        </div>

        <!-- Modal de réglages pour nouvelle partie -->
        <ion-modal :is-open="showSettings" @didDismiss="showSettings = false" :initial-breakpoint="0.5" :breakpoints="[0, 0.5, 0.8]">
          <ion-header>
            <ion-toolbar>
              <ion-title>Nouvelle Partie</ion-title>
              <ion-buttons slot="end">
                <ion-button @click="showSettings = false">Annuler</ion-button>
              </ion-buttons>
            </ion-toolbar>
          </ion-header>
          <ion-content class="ion-padding">
            <ion-list lines="none">
              <ion-item>
                <ion-label position="stacked">Ta couleur</ion-label>
                <ion-select v-model="gameSettings.playerColor" interface="popover">
                  <ion-select-option value="white">Blancs</ion-select-option>
                  <ion-select-option value="black">Noirs</ion-select-option>
                  <ion-select-option value="random">Aléatoire</ion-select-option>
                </ion-select>
              </ion-item>

              <ion-item class="ion-margin-top">
                <ion-label position="stacked">Niveau de l'IA (ELO : {{ gameSettings.level }})</ion-label>
                <ion-range 
                  v-model="gameSettings.level" 
                  :min="1320" 
                  :max="2800" 
                  :step="10"
                  snaps
                  ticks
                  color="secondary"
                >
                  <ion-icon slot="start" :icon="handRightOutline" size="small"></ion-icon>
                  <ion-icon slot="end" :icon="handRightOutline"></ion-icon>
                </ion-range>
              </ion-item>

              <ion-button expand="block" class="ion-margin-top" @click="startNewGame">
                Lancer la partie
              </ion-button>
            </ion-list>
          </ion-content>
        </ion-modal>
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
  IonButton,
  IonSpinner,
  IonModal,
  IonList,
  IonItem,
  IonLabel,
  IonSelect,
  IonSelectOption,
  IonRange,
  IonIcon,
  IonGrid,
  IonRow,
  IonCol,
  onIonViewWillLeave
} from '@ionic/vue';
import { 
  handRightOutline, 
  playBackOutline, 
  chevronBackOutline, 
  chevronForwardOutline, 
  playForwardOutline 
} from 'ionicons/icons';
import { ref, onMounted, onUnmounted, reactive, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { TheChessboard } from 'vue3-chessboard';
import 'vue3-chessboard/style.css';
import { useAuthStore } from '@/stores/auth';
import { useChessStore } from '@/stores/chess';
import { Chess } from 'chess.js';

/**
 * États et API
 */
const authStore = useAuthStore();
const chessStore = useChessStore();
const router = useRouter();

const isLandscape = ref(window.innerWidth > window.innerHeight);
const renderKey = ref(0);
let resizeTimeout: any = null;

const updateOrientation = () => {
  if (resizeTimeout) clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(() => {
    isLandscape.value = window.innerWidth > window.innerHeight;
    renderKey.value++; // Force la recréation de l'échiquier une fois le layout stabilisé
  }, 200); // Attendre 200ms que la rotation soit finie
};

let boardApi: any = null;
let engine: Worker | null = null;
let hintEngine: Worker | null = null;
const engineLoaded = ref(false);
const showSettings = ref(false);
const isHintEnabled = ref(false);
const currentPly = ref(0);
const oupsCount = ref(0);
const helpCount = ref(0);
let lastSuggestedMove = '';

// Timer de la partie
const timerSeconds = ref(0);
let timerInterval: any = null;
const isTimerRunning = ref(false);

/**
 * Formate le temps en MI:SS
 */
const formattedTime = computed(() => {
  const min = Math.floor(timerSeconds.value / 60);
  const sec = timerSeconds.value % 60;
  return `${min.toString().padStart(2, '0')}:${sec.toString().padStart(2, '0')}`;
});

/**
 * Démarre le chrono
 */
const startTimer = () => {
  if (isTimerRunning.value) return;
  isTimerRunning.value = true;
  timerInterval = setInterval(() => {
    timerSeconds.value++;
  }, 1000);
};

/**
 * Arrête le chrono
 */
const stopTimer = () => {
  isTimerRunning.value = false;
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
};

// États pour les pièces capturées et matériel
const capturedPieces = reactive<{ white: any[], black: any[] }>({ white: [], black: [] });
const materialDiff = ref(0);

const gameStatus = reactive({
  message: '',
  color: 'medium'
});

// Réglages de la partie
const gameSettings = reactive({
  playerColor: 'white' as 'white' | 'black' | 'random',
  level: 1320
});

const boardConfig = reactive({
  coordinates: true,
  autoCastling: true,
  orientation: 'white' as 'white' | 'black',
  viewOnly: false,
});

/**
 * Initialise le moteur de suggestion (Aide)
 */
const initHintEngine = () => {
  if (hintEngine) return;
  try {
    hintEngine = new Worker('stockfish/stockfish.js');
    hintEngine.onmessage = (event) => {
      const line = event.data.trim();
      if (line.startsWith('bestmove')) {
        const move = line.split(' ')[1]; // ex: "e2e4"
        if (move && move !== '(none)' && boardApi && isHintEnabled.value) {
          lastSuggestedMove = move;
          const from = move.substring(0, 2);
          const to = move.substring(2, 4);
          boardApi.drawMove(from as any, to as any, 'green');
        }
      }
    };
    hintEngine.postMessage('uci');
    hintEngine.postMessage('setoption name UCI_LimitStrength value false');
    hintEngine.postMessage('setoption name Hash value 32');
    hintEngine.postMessage('isready');
  } catch (err) {
    console.error("Échec moteur aide :", err);
  }
};

/**
 * Termine le moteur de suggestion
 */
const terminateHintEngine = () => {
  if (hintEngine) {
    hintEngine.terminate();
    hintEngine = null;
  }
};

/**
 * Demande une suggestion au moteur d'aide
 */
const requestHint = () => {
  if (!boardApi || !hintEngine || !isHintEnabled.value) return;
  const playerColor = boardConfig.orientation;
  if (boardApi.getTurnColor() === playerColor) {
    const fen = boardApi.getFen();
    hintEngine.postMessage(`position fen ${fen}`);
    hintEngine.postMessage('go movetime 2000');
  }
};

/**
 * Bascule l'activation de l'aide
 */
const toggleHint = () => {
  isHintEnabled.value = !isHintEnabled.value;
  if (isHintEnabled.value) {
    initHintEngine();
    setTimeout(requestHint, 500);
  } else {
    terminateHintEngine();
    if (boardApi) boardApi.hideMoves();
  }
};

/**
 * Redirige vers la page d'analyse
 */
const goToAnalysis = () => {
  if (boardApi) {
    chessStore.saveGame(
      boardApi.getPgn(),
      boardConfig.orientation,
      gameSettings.level
    );
    router.push('/tabs/analysis');
  }
};

/**
 * Analyse la raison du match nul via chess.js
 */
const getDrawReason = () => {
  if (!boardApi) return 'Match Nul.';
  const game = new Chess(boardApi.getFen());
  if (game.isStalemate()) return 'Match Nul par Pat.';
  if (game.isInsufficientMaterial()) return 'Match Nul par matériel insuffisant.';
  if (game.isThreefoldRepetition()) return 'Match Nul par triple répétition.';
  if (game.isDraw()) return 'Match Nul (règle des 50 coups).';
  return 'Match Nul.';
};

/**
 * Détermine l'ELO par défaut du joueur (Rapide)
 */
const getInitialElo = () => {
  const eloRaw = String(authStore.selectedIdentity?.elo_rapide || '1320');
  const match = eloRaw.match(/\d+/);
  const eloNum = match ? parseInt(match[0], 10) : 1320;
  if (isNaN(eloNum) || eloNum < 1320) return 1320;
  if (eloNum > 2800) return 2800;
  return eloNum;
};

/**
 * Applique la force du moteur
 */
const applyEngineStrength = (level: number) => {
  if (!engine) return;
  const safeLevel = Math.floor(Number(level));
  if (!isNaN(safeLevel)) {
    engine.postMessage('setoption name UCI_LimitStrength value true');
    engine.postMessage(`setoption name UCI_Elo value ${safeLevel}`);
  }
};

/**
 * Traduit le type de pièce en symbole unicode
 */
const pieceToSymbol = (p: any) => {
  const type = typeof p === 'string' ? p : p?.type;
  if (!type) return '';
  const map: any = { 'p': '♟', 'n': '♞', 'b': '♝', 'r': '♜', 'q': '♛', 'k': '♚' };
  return map[type.toLowerCase()] || '';
};

/**
 * Pièces capturées par le joueur
 */
const capturedByPlayer = computed(() => {
  return boardConfig.orientation === 'white' ? capturedPieces.black : capturedPieces.white;
});

/**
 * Pièces capturées par l'ordinateur
 */
const capturedByOpponent = computed(() => {
  return boardConfig.orientation === 'white' ? capturedPieces.white : capturedPieces.black;
});

/**
 * Différence matérielle affichée
 */
const materialDiffDisplay = computed(() => {
  const diff = Number(materialDiff.value) || 0;
  if (diff === 0) return { player: null, opponent: null };
  const isWhite = boardConfig.orientation === 'white';
  const playerWins = isWhite ? diff > 0 : diff < 0;
  return {
    player: playerWins ? Math.abs(diff) : null,
    opponent: !playerWins ? Math.abs(diff) : null
  };
});

/**
 * Met à jour les informations d'affichage
 */
const refreshDisplay = () => {
  if (boardApi) {
    currentPly.value = boardApi.getCurrentPlyNumber();
    const material = boardApi.getMaterialCount();
    materialDiff.value = material?.materialDiff ?? 0;
    const captures = boardApi.getCapturedPieces();
    capturedPieces.white = captures.white || [];
    capturedPieces.black = captures.black || [];
  }
};

const handleBoardCreated = (api: any) => {
  boardApi = api;
  
  // RECHARGEMENT DE LA PARTIE EN COURS ET DES COMPTEURS
  if (chessStore.currentPgn) {
    boardApi.loadPgn(chessStore.currentPgn);
    boardConfig.orientation = chessStore.orientation;
    gameSettings.level = chessStore.engineElo;
    gameSettings.playerColor = chessStore.orientation;
    
    // Restauration des statistiques
    helpCount.value = chessStore.helpCount || 0;
    oupsCount.value = chessStore.oupsCount || 0;
  }
  
  refreshDisplay();
};

const handleMove = (moveInfo?: any) => {
  if (!boardApi || !engine) return;
  refreshDisplay();

  // Détection du coup suggéré (Aide) avant sauvegarde pour incrémenter le compteur
  if (moveInfo && lastSuggestedMove) {
    const playedMove = moveInfo.from + moveInfo.to;
    if (playedMove === lastSuggestedMove) {
      helpCount.value++;
    }
  }

  // Sauvegarde automatique avec les compteurs à jour
  chessStore.saveGame(
    boardApi.getPgn(),
    boardConfig.orientation,
    gameSettings.level,
    helpCount.value,
    oupsCount.value
  );

  // On efface la suggestion après chaque coup
  lastSuggestedMove = '';

  // Démarre le chrono au premier coup joué de la partie
  if (boardApi.getCurrentPlyNumber() === 1 && !isTimerRunning.value) {
    startTimer();
  }

  // Efface les flèches d'aide
  boardApi.hideMoves();

  if (gameStatus.color === 'warning') {
    gameStatus.message = '';
    gameStatus.color = 'medium';
  }
  const computerColor = boardConfig.orientation === 'white' ? 'black' : 'white';
  if (boardApi.getTurnColor() === computerColor) {
    const fen = boardApi.getFen();
    engine.postMessage(`position fen ${fen}`);
    engine.postMessage('go movetime 2000');
  } else {
    // Si c'est au tour du joueur, on demande une aide si activée
    if (isHintEnabled.value) {
      requestHint();
    }
  }
};

const openNewGameMenu = () => {
  showSettings.value = true;
};

const startNewGame = () => {
  showSettings.value = false;
  gameStatus.message = '';
  gameStatus.color = 'medium';
  boardConfig.viewOnly = false;
  
  // Reset compteurs et chrono
  stopTimer();
  timerSeconds.value = 0;
  oupsCount.value = 0;
  helpCount.value = 0;
  lastSuggestedMove = '';

  // Reset aide lors d'une nouvelle partie
  isHintEnabled.value = false;
  terminateHintEngine();

  // Nettoyage de la partie précédente dans le store
  chessStore.clearGame();

  let finalColor: 'white' | 'black' = 'white';
  if (gameSettings.playerColor === 'random') {
    finalColor = Math.random() > 0.5 ? 'white' : 'black';
  } else {
    finalColor = gameSettings.playerColor;
  }
  boardConfig.orientation = finalColor;
  if (boardApi) {
    boardApi.resetBoard();
    refreshDisplay();
  }
  if (engine) {
    applyEngineStrength(gameSettings.level);
    engine.postMessage('ucinewgame');
    engine.postMessage('isready');
    if (finalColor === 'black') {
      engine.postMessage('position startpos');
      engine.postMessage('go movetime 2000');
    }
  }
};

const handleCheck = (color: string) => {
  gameStatus.message = `⚠️ Échec au Roi ${color === 'white' ? 'Blanc' : 'Noir'} !`;
  gameStatus.color = 'warning';
};

const handleCheckmate = (color: string) => {
  const winner = color === 'white' ? 'Les Noirs gagnent' : 'Les Blancs gagnent';
  gameStatus.message = `🏁 MAT ! ${winner}.`;
  gameStatus.color = 'danger';
  boardConfig.viewOnly = true;
  stopTimer();
};

const handleStalemate = () => {
  gameStatus.message = '🤝 Match Nul par Pat.';
  gameStatus.color = 'medium';
  boardConfig.viewOnly = true;
  stopTimer();
};

const handleDraw = () => {
  gameStatus.message = `🤝 ${getDrawReason()}`;
  gameStatus.color = 'medium';
  boardConfig.viewOnly = true;
  stopTimer();
};

const resetGame = () => {
  openNewGameMenu();
};

const undoMove = () => {
  if (!boardApi || boardApi.getCurrentPlyNumber() === 0) return;

  oupsCount.value++;

  // Efface les flèches d'aide
  boardApi.hideMoves();

  const playerColor = boardConfig.orientation;
  if (boardApi.getTurnColor() === playerColor) {
    boardApi.undoLastMove();
    boardApi.undoLastMove();
  } else {
    boardApi.undoLastMove();
  }
  boardConfig.viewOnly = false;
  const game = new Chess(boardApi.getFen());
  if (game.inCheck()) {
    handleCheck(game.turn() === 'w' ? 'white' : 'black');
  } else {
    gameStatus.message = '';
    gameStatus.color = 'medium';
  }
  refreshDisplay();

  // Sauvegarde après annulation (avec les compteurs d'aide et de oups)
  chessStore.saveGame(
    boardApi.getPgn(),
    boardConfig.orientation,
    gameSettings.level,
    helpCount.value,
    oupsCount.value
  );

  // Recalcule l'aide si activée
  if (isHintEnabled.value) {
    requestHint();
  }
};

onMounted(() => {
  window.addEventListener('resize', updateOrientation);
  gameSettings.level = getInitialElo();
  try {
    engine = new Worker('stockfish/stockfish.js');
    engine.onmessage = (event) => {
      const line = event.data.trim();
      if (line === 'uciok' || line.includes('id author')) {
        if (!engineLoaded.value) {
          applyEngineStrength(gameSettings.level);
          engine?.postMessage('isready');
          engineLoaded.value = true;
        }
        return;
      }
      if (line.startsWith('bestmove')) {
        const move = line.split(' ')[1];
        if (move && boardApi) {
          boardApi.move(move);
          refreshDisplay();
        }
      }
    };
    engine.postMessage('uci');
    setTimeout(() => {
      if (!engineLoaded.value) engineLoaded.value = true;
    }, 3000);
  } catch (err) {
    console.error("Erreur moteur :", err);
    engineLoaded.value = true;
  }
});

onUnmounted(() => {
  window.removeEventListener('resize', updateOrientation);
  if (engine) engine.terminate();
  if (hintEngine) hintEngine.terminate();
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
  display: block; /* Évite de casser les coordonnées de clics de l'échiquier */
  width: 100%;
}

.landscape-mode .board-section {
  flex: 0 0 auto;
  width: auto; /* CRUCIAL: Empêche la section de prendre 100% de la largeur en paysage, ce qui poussait les boutons dehors */
}

.board-container {
  width: 100%;
  max-width: 500px; /* Taille par défaut pour téléphones */
  margin: 0 auto; /* Centrage horizontal stable */
  flex-shrink: 0;
  position: relative;
}

/* Agrandissement spécifique pour tablettes en portrait (iPad) */
@media (min-width: 768px) and (orientation: portrait) {
  .board-container {
    max-width: 750px; /* Utilise mieux la largeur de l'iPad */
  }
  
  .board-section {
    padding: 20px 0;
  }
}

/* --- CORRECTIONS FINALES DE STABILITÉ --- */

/* On n'applique ces contraintes QUE en mode paysage pour préserver le mode portrait */
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

.landscape-mode :deep(.cg-wrap) {
  width: 100% !important;
  height: 100% !important;
  aspect-ratio: 1 / 1 !important;
}

.landscape-mode .board-container {
  /* On définit une largeur stable qui sera aussi la hauteur de l'échiquier seul */
  width: 68vh !important; 
  height: auto !important;
  max-width: 100%;
  margin: 0 auto !important;
  padding: 0 !important;
  display: flex;
  flex-direction: column;
}

.landscape-mode .board-section {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  justify-content: center; /* Centrage vertical du bloc jeu */
}

.main-container.landscape-mode {
  display: flex;
  flex-direction: row;
  align-items: center; /* Centrage vertical global */
  justify-content: center;
  gap: 30px; /* Écart réduit pour laisser plus de place aux boutons */
  flex: 1;
  min-height: 0;
  width: 100%;
  max-width: none;
  margin: 0 !important;
}

.side-section {
  display: flex;
  flex-direction: column;
  justify-content: center;
  z-index: 10;
}

.landscape-mode .side-section {
  flex: 1; /* Permet au panneau de s'étirer ou se contracter */
  min-width: 150px; /* Largeur minimale garantie */
  max-width: 250px; /* Ne prend pas trop de place si l'écran est très large */
  padding-left: 10px;
}

/* Boutons uniformes : on force la même largeur pour tous */
.actions-container ion-col {
  padding: 0 2px !important; /* Ajoute 5px d'espace total entre les boutons */
}

.actions-container ion-button {
  width: 100%;
  margin: 0;
  --border-radius: 8px;
}

.landscape-mode .actions-container ion-col {
  padding: 0 0 4px 0 !important; /* En paysage, espacement vertical plutôt qu'horizontal */
}


/* Optimisation spécifique pour les téléphones en paysage (petite hauteur) */
@media (max-height: 500px) and (orientation: landscape) {
  /* On cache le header Ionic pour gagner 56px de hauteur précieux */
  ion-header {
    display: none !important;
  }

  .game-layout {
    padding-top: 5px !important;
  }

  .landscape-mode .board-container {
    width: 62vh !important; /* Taille ajustée pour tenir avec les barres fines */
  }
  
  .game-info-bar { 
    padding: 2px 10px !important; 
    margin-bottom: 2px !important; 
    border: none !important;
  }
  .matchup-info { font-size: 0.7rem !important; }
  .game-timer { font-size: 0.8rem !important; }
  
  .captured-bar { 
    height: 18px !important; 
    padding: 0 5px !important; 
    gap: 4px !important; 
  }
  .player-info { font-size: 0.55rem !important; min-width: 45px !important; }
  .captured-piece { font-size: 0.9rem !important; }
  
  .status-banner { font-size: 0.75rem !important; padding: 3px !important; }
  .actions-container ion-button { height: 28px !important; font-size: 0.65rem !important; }
  
  .side-section { min-width: 150px !important; padding-left: 10px !important; }
  .message-zone { margin-bottom: 8px !important; min-height: 28px !important; }
}

.game-info-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 4px;
  margin-bottom: 2px;
  border-bottom: 1px solid var(--ion-color-light-shade);
}

.matchup-info {
  font-size: 0.85rem;
  font-weight: bold;
  color: var(--ion-color-dark);
}

.game-timer {
  font-family: monospace;
  font-size: 1rem;
  font-weight: bold;
  color: var(--ion-color-primary);
}

.captured-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 26px;
  padding: 0 10px;
  background: #eee;
  border: 1px solid var(--ion-color-light-shade);
}

.player-info {
  font-size: 0.7rem;
  font-weight: bold;
  text-transform: uppercase;
  color: var(--ion-color-medium);
  min-width: 70px;
}

.captured-bar.top { border-radius: 8px 8px 0 0; border-bottom: none; }
.captured-bar.bottom { border-radius: 0 0 8px 8px; border-top: none; margin-bottom: 12px; } /* Espace sous barre Toi */

.captured-pieces { display: flex; flex: 1; flex-wrap: nowrap; overflow: hidden; gap: 1px; }
.captured-piece { font-size: 1.2rem; line-height: 1; color: #333; }
.material-wrapper { min-width: 30px; text-align: right; }
.material-count { font-size: 0.75rem; font-weight: bold; color: var(--ion-color-primary); }

.actions-container { padding: 0 4px; margin-top: 12px; } /* Espace au-dessus des boutons */

.message-zone {
  min-height: 34px;
  margin-top: 4px;
  margin-bottom: 12px; /* Espace sous la barre de message */
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-banner {
  width: 100%;
  padding: 6px;
  border-radius: 6px;
  font-weight: bold;
  font-size: 0.95rem;
}

.status-banner.warning { background-color: var(--ion-color-warning-tint); color: var(--ion-color-warning-shade); }
.status-banner.danger { background-color: var(--ion-color-danger); color: #fff; }
.status-banner.medium { background-color: var(--ion-color-light); color: var(--ion-color-medium-shade); }
.status-placeholder { color: var(--ion-color-medium); font-style: italic; font-size: 0.9rem; }
</style>
