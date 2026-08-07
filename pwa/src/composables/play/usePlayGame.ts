import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useChessStore } from '@/stores/chess';
import { undoMove as apiUndoMove, getGameOverReason } from '@/utils/boardApiWrapper';

export function usePlayGame() {
  const chessStore = useChessStore();
  const router = useRouter();

  let boardApi: any = null;
  const engineLoaded = ref(false);
  const showSettings = ref(false);
  const isHintEnabled = ref(false);
  const currentPly = ref(0);
  const oupsCount = ref(0);
  const helpCount = ref(0);
  const lastSuggestedMove = ref('');
  const showMaterialIndicator = ref(true);

  const gameStatus = reactive({
    message: '',
    color: 'medium',
  });

  const gameSettings = reactive({
    playerColor: 'white' as 'white' | 'black' | 'random',
    clockPreset: 'none' as 'none' | '1+0' | '3+2' | '5+0' | '10+5' | '15+10',
    level: 1320,
  });

  const boardConfig = reactive({
    coordinates: true,
    autoCastling: true,
    orientation: 'white' as 'white' | 'black',
    playerColor: 'white' as 'white' | 'black' | 'both',
    movable: {
      color: 'white' as 'white' | 'black' | 'both',
    },
    viewOnly: false,
  });

  const playerColor = computed<'white' | 'black'>(() => boardConfig.orientation);
  const opponentColor = computed<'white' | 'black'>(() =>
    boardConfig.orientation === 'white' ? 'black' : 'white'
  );

  const toggleHint = () => {
    isHintEnabled.value = !isHintEnabled.value;
    if (isHintEnabled.value) {
      helpCount.value++;
      if (boardApi && lastSuggestedMove.value) {
        const from = lastSuggestedMove.value.substring(0, 2);
        const to = lastSuggestedMove.value.substring(2, 4);
        boardApi.drawMove(from, to, 'green');
      }
    } else {
      if (boardApi) {
        boardApi.hideMoves();
      }
    }
  };

  const handleStockfishHint = (bestMove: string) => {
    lastSuggestedMove.value = bestMove;
    if (isHintEnabled.value && boardApi) {
      const from = bestMove.substring(0, 2);
      const to = bestMove.substring(2, 4);
      boardApi.drawMove(from, to, 'green');
    }
  };

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

  const refreshDisplay = () => {
    if (boardApi) {
      currentPly.value = boardApi.getCurrentPlyNumber();
    }
  };

  const setBoardApi = (api: any) => {
    boardApi = api;
    if (chessStore.currentPgn) {
      boardApi.loadPgn(chessStore.currentPgn);
      boardConfig.orientation = chessStore.orientation;
      boardConfig.playerColor = chessStore.orientation;
      boardConfig.movable.color = chessStore.orientation;
      gameSettings.level = chessStore.engineElo;
      gameSettings.playerColor = chessStore.orientation;

      helpCount.value = chessStore.helpCount || 0;
      oupsCount.value = chessStore.oupsCount || 0;
    }
    refreshDisplay();
  };

  const undoMove = () => {
    if (boardConfig.viewOnly) return;
    if (!boardApi || boardApi.getCurrentPlyNumber() === 0) return;

    oupsCount.value++;
    boardApi.hideMoves();

    apiUndoMove(boardApi, true, boardConfig.orientation);
    boardConfig.viewOnly = false;
    if (boardApi.getIsCheck()) {
      const checkColor = boardApi.getInCheckColor();
      if (checkColor) {
        gameStatus.message = `⚠️ Échec au Roi ${checkColor === 'white' ? 'Blanc' : 'Noir'} !`;
        gameStatus.color = 'warning';
      }
    } else {
      gameStatus.message = '';
      gameStatus.color = 'medium';
    }
    refreshDisplay();

    chessStore.saveGame(
      boardApi.getPgn(),
      boardConfig.orientation,
      gameSettings.level,
      helpCount.value,
      oupsCount.value
    );
  };

  const handleCheck = (color: string) => {
    gameStatus.message = `⚠️ Échec au Roi ${color === 'white' ? 'Blanc' : 'Noir'} !`;
    gameStatus.color = 'warning';
  };

  const handleGameOver = (type: 'checkmate' | 'stalemate' | 'draw', timerSeconds: number) => {
    if (boardConfig.viewOnly) return;
    const isDraw = type === 'stalemate' || type === 'draw';
    const symbol = isDraw ? '🤝' : '🏁';
    gameStatus.message = `${symbol} ${getGameOverReason(boardApi)}`;
    gameStatus.color = isDraw ? 'medium' : 'danger';
    boardConfig.viewOnly = true;
    chessStore.saveCompletedGame(timerSeconds);
  };

  return {
    getBoardApi: () => boardApi,
    setBoardApi,
    engineLoaded,
    showSettings,
    isHintEnabled,
    currentPly,
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
  };
}
