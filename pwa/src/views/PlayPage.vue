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
                <div class="material-wrapper">
                  <div v-if="materialDiffDisplay.opponent" class="material-count">
                    +{{ materialDiffDisplay.opponent }}
                  </div>
                </div>
                <div class="player-info">Adversaire</div>
                <div class="captured-pieces">
                  <span v-for="(p, i) in capturedByOpponent" :key="i" class="captured-piece">
                    {{ p }}
                  </span>
                </div>
                <!-- Horloge Adversaire -->
                <div v-if="clockSettings.preset !== 'none'" class="game-clock opponent-clock" :class="{ active: activeClockColor === opponentColor }">
                  {{ opponentFormattedTime }}
                </div>
              </div>

              <!-- Échiquier et Evaluation Bar -->
              <div class="board-wrapper-with-bar">
                <TheChessboard 
                  v-if="engineLoaded"
                  :key="`board-${isLandscape ? 'l' : 'p'}-${renderKey}`"
                  :board-config="boardConfig" 
                  :player-color="boardConfig.playerColor"
                  @board-created="handleBoardCreated" 
                  @move="handleMove"
                  @check="handleCheck"
                  @checkmate="handleCheckmate"
                  @stalemate="handleStalemate"
                  @draw="handleDraw"
                />
                
                <!-- Barre d'Évaluation -->
                <div class="evaluation-bar" :title="computedEvalTooltip">
                  <div
                    class="evaluation-bar-fill"
                    :style="computedEvalStyle"
                  ></div>
                </div>
              </div>

              <!-- Bande inférieure (Joueur) -->
              <div class="captured-bar bottom">
                <div class="material-wrapper">
                  <div v-if="materialDiffDisplay.player" class="material-count">
                    +{{ materialDiffDisplay.player }}
                  </div>
                </div>
                <div class="player-info">Toi</div>
                <div class="captured-pieces">
                  <span v-for="(p, i) in capturedByPlayer" :key="i" class="captured-piece">
                    {{ p }}
                  </span>
                </div>
                <!-- Horloge Joueur -->
                <div v-if="clockSettings.preset !== 'none'" class="game-clock player-clock" :class="{ active: activeClockColor === playerColor }">
                  {{ playerFormattedTime }}
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
                    <ion-button expand="block" @click="undoMove" :disabled="boardConfig.viewOnly" color="warning" style="font-size: 0.7rem; --padding-start: 2px; --padding-end: 2px;">
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
                <ion-label position="stacked">Cadence (Pendule)</ion-label>
                <ion-select v-model="gameSettings.clockPreset" interface="popover">
                  <ion-select-option value="none">Sans pendule</ion-select-option>
                  <ion-select-option value="1+0">1 min (Bullet)</ion-select-option>
                  <ion-select-option value="3+2">3 min + 2 s (Blitz)</ion-select-option>
                  <ion-select-option value="5+0">5 min KO (Blitz)</ion-select-option>
                  <ion-select-option value="10+5">10 min + 5 s (Rapide)</ion-select-option>
                  <ion-select-option value="15+10">15 min + 10 s (Rapide)</ion-select-option>
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

              <ion-item class="ion-margin-top">
                <ion-label>Indicateur matériel</ion-label>
                <ion-toggle v-model="showMaterialIndicator"></ion-toggle>
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
  IonToggle,
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
import { undoMove as apiUndoMove, getFormattedCapturedPieces, getMaterialDiffDisplay, getGameOverReason } from '@/utils/boardApiWrapper';
import { StockfishManager } from '@/utils/stockfishManager';
import { ChessClock } from '@/utils/ChessClock';

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
let stockfishManager: StockfishManager | null = null;
const engineLoaded = ref(false);
const showSettings = ref(false);
const isHintEnabled = ref(false);
const currentPly = ref(0);
const oupsCount = ref(0);
const helpCount = ref(0);
const lastSuggestedMove = ref('');

// Score de la barre d'évaluation
const evalScoreType = ref('cp');
const evalScoreValue = ref(0);

const computedEvalTooltip = computed(() => {
  let scoreFromWhite = 0;
  const currentStockfishColor = boardConfig.orientation === 'white' ? 'black' : 'white';
  
  if (evalScoreType.value === 'cp') {
    scoreFromWhite = currentStockfishColor === 'white' ? evalScoreValue.value : -evalScoreValue.value;
    const evalFromWhite = scoreFromWhite / 100;
    const sign = evalFromWhite > 0 ? '+' : '';
    return `${sign}${evalFromWhite.toFixed(2)}`;
  } else if (evalScoreType.value === 'mate') {
    const isWhiteAdvantage =
      (currentStockfishColor === 'white' && evalScoreValue.value > 0) ||
      (currentStockfishColor === 'black' && evalScoreValue.value < 0);
    const absMoves = Math.abs(evalScoreValue.value);
    const sideChar = isWhiteAdvantage ? 'B' : 'N';
    return `Mat #${absMoves}${sideChar}`;
  }
  return '0.00';
});

const computedEvalStyle = computed(() => {
  let scoreFromWhite = 0;
  const currentStockfishColor = boardConfig.orientation === 'white' ? 'black' : 'white';

  if (evalScoreType.value === 'cp') {
    scoreFromWhite = currentStockfishColor === 'white' ? evalScoreValue.value : -evalScoreValue.value;
  } else if (evalScoreType.value === 'mate') {
    const isWhiteAdvantage =
      (currentStockfishColor === 'white' && evalScoreValue.value > 0) ||
      (currentStockfishColor === 'black' && evalScoreValue.value < 0);
    scoreFromWhite = isWhiteAdvantage ? 1000 : -1000;
  }

  const clampedScore = Math.max(-1000, Math.min(1000, scoreFromWhite));
  const percentageWhite = 50 + (clampedScore / 1000) * 50;

  if (boardConfig.orientation === 'white') {
    return {
      height: `${percentageWhite}%`,
      marginTop: 'auto',
      marginBottom: '0'
    };
  } else {
    return {
      height: `${percentageWhite}%`,
      marginTop: '0',
      marginBottom: 'auto'
    };
  }
});

// Instanciation de la pendule partagée
const clock = new ChessClock();

// Pendule d'échecs professionnelle
const clockSettings = reactive({
  preset: 'none' as 'none' | '1+0' | '3+2' | '5+0' | '10+5' | '15+10',
  wtime: 0, // ms
  btime: 0, // ms
  winc: 0,  // ms
  binc: 0,  // ms
});

const activeClockColor = ref<'white' | 'black' | null>(null);
const playerColor = computed<'white' | 'black'>(() => boardConfig.orientation);
const opponentColor = computed<'white' | 'black'>(() => boardConfig.orientation === 'white' ? 'black' : 'white');

const playerFormattedTime = computed(() => {
  return playerColor.value === 'white' ? ChessClock.formatTime(clockSettings.wtime) : ChessClock.formatTime(clockSettings.btime);
});

const opponentFormattedTime = computed(() => {
  return opponentColor.value === 'white' ? ChessClock.formatTime(clockSettings.wtime) : ChessClock.formatTime(clockSettings.btime);
});

// Timer global de la partie (historique)
const timerTenths = ref(0);

// Liaison des callbacks de la pendule
clock.onTick = (wtime, btime) => {
  clockSettings.wtime = wtime;
  clockSettings.btime = btime;
  timerTenths.value = clock.timerTenths;
};

clock.onTimeOut = (flaggedColor) => {
  handleTimeOut(flaggedColor);
};

const timerSeconds = computed(() => Math.floor(timerTenths.value / 10));

const formattedTime = computed(() => {
  const totalSecs = timerSeconds.value;
  const min = Math.floor(totalSecs / 60);
  const sec = totalSecs % 60;
  return `${min.toString().padStart(2, '0')}:${sec.toString().padStart(2, '0')}`;
});

/**
 * Démarre le chrono
 */
const startTimer = () => {
  clock.start();
};

/**
 * Arrête le chrono
 */
const stopTimer = () => {
  clock.stop();
};

// États pour les pièces capturées et matériel (gérés par le wrapper)

const gameStatus = reactive({
  message: '',
  color: 'medium'
});

// Réglages de la partie
const gameSettings = reactive({
  playerColor: 'white' as 'white' | 'black' | 'random',
  clockPreset: 'none' as 'none' | '1+0' | '3+2' | '5+0' | '10+5' | '15+10',
  level: 1320
});

const getEnginePositionCommand = (): string => {
  if (!boardApi) return 'position startpos';
  try {
    const pgn = boardApi.getPgn();
    if (!pgn) return 'position startpos';
    const tempGame = new Chess();
    tempGame.loadPgn(pgn);
    const moves = tempGame.history({ verbose: true });
    if (moves.length === 0) return 'position startpos';
    const movesStr = moves.map(m => m.from + m.to + (m.promotion || '')).join(' ');
    return `position startpos moves ${movesStr}`;
  } catch (err) {
    console.error("Erreur lors de la génération de la commande position UCI, repli sur le FEN :", err);
    return `position fen ${boardApi.getFen()}`;
  }
};

const boardConfig = reactive({
  coordinates: true,
  autoCastling: true,
  orientation: 'white' as 'white' | 'black',
  playerColor: 'white' as 'white' | 'black' | 'both',
  viewOnly: false,
});

const getWorkerUrl = () => {
  const base = authStore.stockfishUrl;
  return base ? `${base}stockfish.js` : 'stockfish/stockfish.js';
};

/**
 * Reconstruit la commande de position UCI avec l'historique complet des coups
 * pour permettre la détection de la triple répétition par le moteur.
 */
/**
 * Demande une suggestion au moteur d'aide
 */
const requestHint = () => {
  if (!boardApi || !stockfishManager || !isHintEnabled.value) return;
  const playerColor = boardConfig.orientation;
  if (boardApi.getTurnColor() === playerColor) {
    stockfishManager.startEvaluation(getEnginePositionCommand());
  }
};

/**
 * Bascule l'activation de l'aide
 */
const toggleHint = () => {
  isHintEnabled.value = !isHintEnabled.value;
  if (isHintEnabled.value) {
    if (boardApi && stockfishManager) {
      const playerColor = boardConfig.orientation;
      if (boardApi.getTurnColor() === playerColor) {
        stockfishManager.startEvaluation(getEnginePositionCommand());
      }
    }
  } else {
    if (boardApi) {
      boardApi.hideMoves();
    }
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

const showMaterialIndicator = ref(true);

/**
 * Pièces capturées par le joueur
 */
const capturedByPlayer = computed(() => {
  if (!boardApi) return [];
  const captures = getFormattedCapturedPieces(boardApi, showMaterialIndicator.value);
  return boardConfig.orientation === 'white' ? captures.white : captures.black;
});

/**
 * Pièces capturées par l'ordinateur
 */
const capturedByOpponent = computed(() => {
  if (!boardApi) return [];
  const captures = getFormattedCapturedPieces(boardApi, showMaterialIndicator.value);
  return boardConfig.orientation === 'white' ? captures.black : captures.white;
});

/**
 * Différence matérielle affichée
 */
const materialDiffDisplay = computed(() => {
  if (!boardApi) return { player: null, opponent: null };
  return getMaterialDiffDisplay(boardApi, boardConfig.orientation, showMaterialIndicator.value);
});

/**
 * Met à jour les informations d'affichage
 */
const refreshDisplay = () => {
  if (boardApi) {
    currentPly.value = boardApi.getCurrentPlyNumber();
  }
};

const handleBoardCreated = (api: any) => {
  boardApi = api;
  
  // RECHARGEMENT DE LA PARTIE EN COURS ET DES COMPTEURS
  if (chessStore.currentPgn) {
    boardApi.loadPgn(chessStore.currentPgn);
    boardConfig.orientation = chessStore.orientation;
    boardConfig.playerColor = chessStore.orientation;
    gameSettings.level = chessStore.engineElo;
    gameSettings.playerColor = chessStore.orientation;
    
    // Restauration des statistiques
    helpCount.value = chessStore.helpCount || 0;
    oupsCount.value = chessStore.oupsCount || 0;
  }
  
  refreshDisplay();
};

const handleTimeOut = (flaggedColor: 'white' | 'black') => {
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

const handleMove = (moveInfo?: any) => {
  if (!boardApi || !stockfishManager) return;
  
  // Sécurité : si la partie est terminée, on annule immédiatement tout coup tenté
  if (boardConfig.viewOnly) {
    boardApi.undoLastMove();
    return;
  }
  
  refreshDisplay();

  // Détection du coup suggéré (Aide) avant sauvegarde pour incrémenter le compteur
  if (moveInfo && lastSuggestedMove.value && isHintEnabled.value) {
    const playedMove = moveInfo.from + moveInfo.to;
    if (playedMove === lastSuggestedMove.value) {
      helpCount.value++;
    }
  }

  // Application des incréments (Fischer) sur le coup qui vient de se terminer
  const justFinishedColor = boardApi.getTurnColor() === 'white' ? 'black' : 'white';
  const plyCount = boardApi.getCurrentPlyNumber();

  clock.applyIncrement(justFinishedColor, plyCount);
  clockSettings.wtime = clock.wtime;
  clockSettings.btime = clock.btime;

  // Sauvegarde automatique avec les compteurs à jour
  chessStore.saveGame(
    boardApi.getPgn(),
    boardConfig.orientation,
    gameSettings.level,
    helpCount.value,
    oupsCount.value
  );

  // On efface la suggestion après chaque coup
  lastSuggestedMove.value = '';

  // Démarre le chrono au premier coup joué de la partie
  if (plyCount === 1) {
    startTimer();
  }

  // Changement de l'horloge active
  clock.setActiveColor(boardApi.getTurnColor());
  activeClockColor.value = clock.activeColor;

  // Efface les flèches d'aide
  boardApi.hideMoves();

  if (gameStatus.color === 'warning') {
    gameStatus.message = '';
    gameStatus.color = 'medium';
  }

  const computerColor = boardConfig.orientation === 'white' ? 'black' : 'white';
  const positionCmd = getEnginePositionCommand();

  if (boardApi.getTurnColor() === computerColor) {
    if (clockSettings.preset !== 'none') {
      const timeParams = `wtime ${clockSettings.wtime} winc ${clockSettings.winc} btime ${clockSettings.btime} binc ${clockSettings.binc}`;
      stockfishManager.startOpponentMove(positionCmd, timeParams);
    } else {
      stockfishManager.startOpponentMove(positionCmd, 5000);
    }
  } else {
    // Si c'est au tour du joueur, on commence les calculs d'évaluation / conseil
    stockfishManager.startEvaluation(positionCmd);
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
  clock.reset();
  timerTenths.value = 0;
  oupsCount.value = 0;
  helpCount.value = 0;
  lastSuggestedMove.value = '';
  activeClockColor.value = null;

  // Configuration de la cadence
  clock.setPreset(gameSettings.clockPreset);
  clockSettings.preset = clock.preset;
  clockSettings.wtime = clock.wtime;
  clockSettings.btime = clock.btime;
  clockSettings.winc = clock.winc;
  clockSettings.binc = clock.binc;

  // Reset aide lors d'une nouvelle partie
  isHintEnabled.value = false;

  // Nettoyage de la partie précédente dans le store
  chessStore.clearGame();

  let finalColor: 'white' | 'black' = 'white';
  if (gameSettings.playerColor === 'random') {
    finalColor = Math.random() > 0.5 ? 'white' : 'black';
  } else {
    finalColor = gameSettings.playerColor;
  }
  boardConfig.orientation = finalColor;
  boardConfig.playerColor = finalColor;
  if (boardApi) {
    boardApi.resetBoard();
    refreshDisplay();
  }
  if (stockfishManager) {
    stockfishManager.initOpponentWorker(gameSettings.level);
    stockfishManager.setOpponentElo(gameSettings.level);
    
    const positionCmd = 'position startpos';
    if (finalColor === 'black') {
      clock.setActiveColor('white'); // Les Blancs (IA) commencent
      activeClockColor.value = clock.activeColor;
      startTimer(); // Démarre le chrono
      
      if (clockSettings.preset !== 'none') {
        const timeParams = `wtime ${clockSettings.wtime} winc ${clockSettings.winc} btime ${clockSettings.btime} binc ${clockSettings.binc}`;
        stockfishManager.startOpponentMove(positionCmd, timeParams);
      } else {
        stockfishManager.startOpponentMove(positionCmd, 5000);
      }
    } else {
      stockfishManager.startEvaluation(positionCmd);
    }
  }
};

const handleCheck = (color: string) => {
  gameStatus.message = `⚠️ Échec au Roi ${color === 'white' ? 'Blanc' : 'Noir'} !`;
  gameStatus.color = 'warning';
};

const handleCheckmate = (color: string) => {
  if (boardConfig.viewOnly) return;
  gameStatus.message = `🏁 ${getGameOverReason(boardApi)}`;
  gameStatus.color = 'danger';
  boardConfig.viewOnly = true;
  stopTimer();
  chessStore.saveCompletedGame(timerSeconds.value);
  if (stockfishManager) {
    stockfishManager.stopEvaluation();
  }
  setTimeout(() => {
    renderKey.value++;
  }, 100);
};

const handleStalemate = () => {
  if (boardConfig.viewOnly) return;
  gameStatus.message = `🤝 ${getGameOverReason(boardApi)}`;
  gameStatus.color = 'medium';
  boardConfig.viewOnly = true;
  stopTimer();
  chessStore.saveCompletedGame(timerSeconds.value);
  if (stockfishManager) {
    stockfishManager.stopEvaluation();
  }
  setTimeout(() => {
    renderKey.value++;
  }, 100);
};

const handleDraw = () => {
  if (boardConfig.viewOnly) return;
  gameStatus.message = `🤝 ${getGameOverReason(boardApi)}`;
  gameStatus.color = 'medium';
  boardConfig.viewOnly = true;
  stopTimer();
  chessStore.saveCompletedGame(timerSeconds.value);
  if (stockfishManager) {
    stockfishManager.stopEvaluation();
  }
  setTimeout(() => {
    renderKey.value++;
  }, 100);
};

const resetGame = () => {
  openNewGameMenu();
};

const undoMove = () => {
  if (boardConfig.viewOnly) return;
  if (!boardApi || boardApi.getCurrentPlyNumber() === 0) return;

  oupsCount.value++;

  // Efface les flèches d'aide
  boardApi.hideMoves();

  apiUndoMove(boardApi, true, boardConfig.orientation);
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

  const computerColor = boardConfig.orientation === 'white' ? 'black' : 'white';
  const positionCmd = getEnginePositionCommand();
  if (stockfishManager) {
    if (boardApi.getTurnColor() === computerColor) {
      stockfishManager.startOpponentMove(positionCmd, 5000);
    } else {
      stockfishManager.startEvaluation(positionCmd);
    }
  }
};

onMounted(() => {
  window.addEventListener('resize', updateOrientation);
  gameSettings.level = getInitialElo();
  
  if (!authStore.isRoiActive) {
    console.warn("Le module ROI n'est pas actif, impossible d'initialiser le moteur Stockfish.");
    engineLoaded.value = true; // Évite le loader infini
    return;
  }

  try {
    stockfishManager = new StockfishManager(getWorkerUrl());
    stockfishManager.setCallbacks({
      onBestMove: (bestMove) => {
        if (boardApi) {
          boardApi.move(bestMove);
          refreshDisplay();
        }
      },
      onEvaluation: (scoreType, scoreValue) => {
        evalScoreType.value = scoreType;
        evalScoreValue.value = scoreValue;
      },
      onHint: (bestMove) => {
        lastSuggestedMove.value = bestMove;
        if (isHintEnabled.value && boardApi) {
          const from = bestMove.substring(0, 2);
          const to = bestMove.substring(2, 4);
          boardApi.drawMove(from as any, to as any, 'green');
        }
      }
    });

    // evalWorker est initialisé à la demande, pas besoin de le lancer ici
    stockfishManager.initOpponentWorker(gameSettings.level);
    engineLoaded.value = true;
  } catch (err) {
    console.error("Erreur d'initialisation du StockfishManager :", err);
    engineLoaded.value = true;
  }
});

onUnmounted(() => {
  window.removeEventListener('resize', updateOrientation);
  if (stockfishManager) {
    stockfishManager.terminate();
  }
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
.captured-piece { font-size: 1.2rem; line-height: 1; color: #333; font-family: "Lucida Sans Unicode", "Arial Unicode MS", "Noto Sans Chess", sans-serif; }
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

/* CSS pour l'intégration de la barre d'évaluation */
.board-wrapper-with-bar {
  display: flex;
  flex-direction: row;
  align-items: stretch;
  position: relative;
  width: 100%;
}

.board-wrapper-with-bar :deep(.cg-wrap) {
  flex: 1;
}

.evaluation-bar {
  width: 12px;
  background-color: #404040;
  position: relative;
  display: flex;
  flex-direction: column;
  margin-left: 4px;
  border-radius: 4px;
  overflow: hidden;
  border: 1px solid var(--ion-color-light-shade);
}

.evaluation-bar-fill {
  width: 100%;
  background-color: #ececec;
  transition: height 0.3s ease;
}

/* Styles pour les horloges de la pendule */
.game-clock {
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.95rem;
  font-weight: bold;
  padding: 2px 6px;
  background-color: #2b2b2b;
  color: #a0a0a0;
  border-radius: 4px;
  margin-left: auto;
  min-width: 60px;
  text-align: center;
  border: 1px solid #444;
  transition: all 0.2s ease;
}

.game-clock.active {
  background-color: var(--ion-color-primary);
  color: #ffffff;
  border-color: var(--ion-color-primary-tint);
  box-shadow: 0 0 6px var(--ion-color-primary-tint);
}
</style>
