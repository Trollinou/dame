/**
 * StockfishManager - Manages two Stockfish Web Workers (Evaluation & Opponent).
 */
export class StockfishManager {
  private workerUrl: string;
  private evalWorker: Worker | null = null;
  private opponentWorker: Worker | null = null;
  
  // Callbacks
  private onBestMove: ((bestMove: string) => void) | null = null;
  private onEvaluation: ((scoreType: string, scoreValue: number) => void) | null = null;
  private onHint: ((bestMove: string) => void) | null = null;

  // Stabilité du moteur d'évaluation
  private lastBestMove = '';
  private lastScoreType = 'cp';
  private lastScoreValue = 0;
  private stabilityCounter = 0;
  private evalStartTime = 0;
  private isEvalRunning = false;
  private opponentTimeout: any = null;

  constructor(workerUrl: string) {
    this.workerUrl = workerUrl;
  }

  setCallbacks(callbacks: {
    onBestMove?: (bestMove: string) => void;
    onEvaluation?: (scoreType: string, scoreValue: number) => void;
    onHint?: (bestMove: string) => void;
  }) {
    if (callbacks.onBestMove) this.onBestMove = callbacks.onBestMove;
    if (callbacks.onEvaluation) this.onEvaluation = callbacks.onEvaluation;
    if (callbacks.onHint) this.onHint = callbacks.onHint;
  }

  /**
   * Initialise le moteur d'évaluation (Worker principal, très performant)
   */
  initEvaluationWorker(): void {
    if (this.evalWorker) return;
    try {
      this.evalWorker = new Worker(this.workerUrl);
      this.evalWorker.onmessage = (e: MessageEvent) => this.handleEvalMessage(e.data);
      
      this.evalWorker.postMessage('uci');
      this.evalWorker.postMessage('setoption name Threads value 1');
      this.evalWorker.postMessage('setoption name Hash value 256');
      this.evalWorker.postMessage('ucinewgame');
      this.evalWorker.postMessage('isready');
    } catch (err) {
      console.error('Failed to initialize Stockfish Evaluation Worker:', err);
    }
  }

  /**
   * Initialise le moteur d'opposition (Worker adverse, limitation de force ELO)
   */
  initOpponentWorker(elo: number): void {
    if (this.opponentWorker) {
      this.setOpponentElo(elo);
      return;
    }
    try {
      this.opponentWorker = new Worker(this.workerUrl);
      this.opponentWorker.onmessage = (e: MessageEvent) => this.handleOpponentMessage(e.data);

      this.opponentWorker.postMessage('uci');
      this.opponentWorker.postMessage('setoption name UCI_LimitStrength value true');
      this.opponentWorker.postMessage(`setoption name UCI_Elo value ${elo}`);
      this.opponentWorker.postMessage('ucinewgame');
      this.opponentWorker.postMessage('isready');
    } catch (err) {
      console.error('Failed to initialize Stockfish Opponent Worker:', err);
    }
  }

  setOpponentElo(elo: number): void {
    if (this.opponentWorker) {
      this.opponentWorker.postMessage('setoption name UCI_LimitStrength value true');
      this.opponentWorker.postMessage(`setoption name UCI_Elo value ${elo}`);
    }
  }

  private lastPositionCommand = '';

  private evalTimeout: any = null;

  /**
   * Démarre l'analyse d'évaluation sur une position (go infinite)
   */
  startEvaluation(positionCommand: string): void {
    if (this.isEvalRunning && this.lastPositionCommand === positionCommand) {
      console.log("[StockfishManager] Évaluation déjà en cours pour cette position, pas de redémarrage.");
      return;
    }
    console.log("[StockfishManager] startEvaluation appelé pour :", positionCommand);
    this.lastPositionCommand = positionCommand;
    this.initEvaluationWorker();
    this.stopEvaluation(); // Arrêter tout calcul précédent

    this.lastBestMove = '';
    this.stabilityCounter = 0;
    this.evalStartTime = Date.now();
    this.isEvalRunning = true;

    if (this.evalWorker) {
      console.log("[StockfishManager] PostMessage au evalWorker :", positionCommand, "puis 'go infinite'");
      this.evalWorker.postMessage(positionCommand);
      this.evalWorker.postMessage('go infinite');

      // Limiter le temps de calcul à 5 secondes
      this.evalTimeout = setTimeout(() => {
        if (this.isEvalRunning) {
          console.log("[StockfishManager] Timeout de 5s atteint pour l'évaluation. Arrêt.");
          this.stopEvaluation();
        }
      }, 5000);
    } else {
      console.error("[StockfishManager] evalWorker non disponible");
    }
  }

  /**
   * Arrête le moteur d'évaluation
   */
  stopEvaluation(): void {
    console.log("[StockfishManager] stopEvaluation demandé");
    if (this.evalTimeout) {
      clearTimeout(this.evalTimeout);
      this.evalTimeout = null;
    }
    if (this.evalWorker) {
      this.evalWorker.postMessage('stop');
    }
    this.isEvalRunning = false;
  }

  /**
   * Démarre la recherche du coup d'opposition (IA adverse)
   */
  startOpponentMove(positionCommand: string, searchParams: number | string = 5000): void {
    console.log("[StockfishManager] startOpponentMove pour l'IA adverse. Params :", searchParams);
    if (this.opponentWorker) {
      this.stopEvaluation(); // Arrêter le moteur de conseil/évaluation pendant le tour de l'IA adverse
      
      if (this.opponentTimeout) {
        clearTimeout(this.opponentTimeout);
        this.opponentTimeout = null;
      }

      this.opponentWorker.postMessage(positionCommand);
      if (typeof searchParams === 'string') {
        console.log(`[StockfishManager] PostMessage au opponentWorker : go ${searchParams}`);
        this.opponentWorker.postMessage(`go ${searchParams}`);
        
        // Cap opponent think time at 15 seconds
        this.opponentTimeout = setTimeout(() => {
          if (this.opponentWorker) {
            console.log("[StockfishManager] Timeout IA adverse atteint (15s), envoi de 'stop'");
            this.opponentWorker.postMessage('stop');
          }
        }, 15000);
      } else {
        console.log(`[StockfishManager] PostMessage au opponentWorker : go movetime ${searchParams}`);
        this.opponentWorker.postMessage(`go movetime ${searchParams}`);
      }
    } else {
      console.error("[StockfishManager] opponentWorker non disponible");
    }
  }

  /**
   * Nettoie et détruit les workers
   */
  terminate(): void {
    if (this.evalWorker) {
      this.evalWorker.terminate();
      this.evalWorker = null;
    }
    if (this.opponentWorker) {
      this.opponentWorker.terminate();
      this.opponentWorker = null;
    }
  }

  private handleEvalMessage(line: string): void {
    line = line.trim();

    // Parse info
    if (line.startsWith('info ')) {
      const parts = line.split(' ');
      
      // Extraction du score
      const scoreIndex = parts.indexOf('score');
      if (scoreIndex !== -1 && scoreIndex + 2 < parts.length) {
        const scoreType = parts[scoreIndex + 1]; // 'cp' ou 'mate'
        const scoreValue = parseInt(parts[scoreIndex + 2], 10);
        this.lastScoreType = scoreType;
        this.lastScoreValue = scoreValue;
        
        if (this.onEvaluation) {
          this.onEvaluation(scoreType, scoreValue);
        }
      }

      // Extraction du coup temporaire "pv" pour tester la stabilité
      const pvIndex = parts.indexOf('pv');
      if (pvIndex !== -1 && pvIndex + 1 < parts.length) {
        const currentBestMove = parts[pvIndex + 1];
        if (currentBestMove === this.lastBestMove) {
          this.stabilityCounter++;
        } else {
          this.lastBestMove = currentBestMove;
          this.stabilityCounter = 0;
        }

        // Critères de stabilité et de timeout (30s)
        const elapsed = Date.now() - this.evalStartTime;
        const isStableCp = this.lastScoreType === 'cp' && this.stabilityCounter >= 8;
        const isStableMate = this.lastScoreType === 'mate' && this.stabilityCounter >= 3;

        if (this.isEvalRunning && (isStableCp || isStableMate || elapsed >= 30000)) {
          console.log(`[StockfishManager] Stabilité atteinte (stab=${this.stabilityCounter}, score=${this.lastScoreType} ${this.lastScoreValue}, elapsed=${elapsed}ms). Arrêt évaluation.`);
          this.stopEvaluation();
        }
      }
    }

    if (line.startsWith('bestmove')) {
      const parts = line.split(' ');
      const bestMove = parts[1];
      console.log("[StockfishManager] bestmove reçu du evalWorker :", bestMove);
      if (bestMove && bestMove !== '(none)' && this.onHint) {
        this.onHint(bestMove);
      }
    }
  }

  private handleOpponentMessage(line: string): void {
    line = line.trim();
    if (line.startsWith('bestmove')) {
      if (this.opponentTimeout) {
        clearTimeout(this.opponentTimeout);
        this.opponentTimeout = null;
      }
      const parts = line.split(' ');
      const bestMove = parts[1];
      if (bestMove && bestMove !== '(none)' && this.onBestMove) {
        this.onBestMove(bestMove);
      }
    }
  }
}
