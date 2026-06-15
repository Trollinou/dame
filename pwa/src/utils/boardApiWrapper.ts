import { Chess } from 'chess.js';

/**
 * Undo moves on the eg-chessboard api.
 * If vsComputer is true, it undos two moves if it is the player's turn to revert both the computer's response and player's move.
 */
export function undoMove(boardApi: any, vsComputer = false, playerColor: 'white' | 'black'): void {
  if (!boardApi) return;
  if (vsComputer) {
    const turnColor = boardApi.getTurnColor();
    if (turnColor === playerColor) {
      boardApi.undoLastMove();
      boardApi.undoLastMove();
    } else {
      boardApi.undoLastMove();
    }
  } else {
    boardApi.undoLastMove();
  }
}

/**
 * Returns captured pieces mapped directly to Unicode symbols.
 */
export function getFormattedCapturedPieces(boardApi: any, enabled = true): { white: string[]; black: string[] } {
  if (!boardApi || !enabled) return { white: [], black: [] };
  
  const captured = boardApi.getCapturedPieces() || { white: [], black: [] };
  const pieceToSymbol = (p: any) => {
    const type = typeof p === 'string' ? p : p?.type;
    if (!type) return '';
    const map: Record<string, string> = { p: '♟', n: '♞', b: '♝', r: '♜', q: '♛', k: '♚' };
    return map[type.toLowerCase()] || '';
  };

  return {
    white: (captured.black || []).map(pieceToSymbol),
    black: (captured.white || []).map(pieceToSymbol),
  };
}

/**
 * Calculates material difference display from player's perspective.
 */
export function getMaterialDiffDisplay(boardApi: any, playerColor: 'white' | 'black', enabled = true): { player: number | null; opponent: number | null } {
  if (!boardApi || !enabled) return { player: null, opponent: null };
  
  const diff = boardApi.getMaterialCount()?.materialDiff ?? 0;
  if (diff === 0) return { player: null, opponent: null };
  const playerWins = playerColor === 'white' ? diff > 0 : diff < 0;
  return {
    player: playerWins ? Math.abs(diff) : null,
    opponent: !playerWins ? Math.abs(diff) : null,
  };
}

/**
 * Returns a user-friendly explanation of the game over reason in French.
 */
export function getGameOverReason(boardApi: any): string {
  if (!boardApi) return '';
  
  // Use chess.js directly to evaluate the state based on the current FEN
  const game = new Chess(boardApi.getFen());
  if (!game.isGameOver()) return '';
  
  if (game.isCheckmate()) {
    const winner = game.turn() === 'w' ? 'Noirs' : 'Blancs';
    return `Échec et mat ! Les ${winner} ont gagné.`;
  }
  if (game.isStalemate()) return 'Match nul par Pat.';
  if (game.isThreefoldRepetition()) return 'Match nul par triple répétition.';
  if (game.isInsufficientMaterial()) return 'Match nul par matériel insuffisant.';
  if (game.isDraw()) return 'Match nul (règle des 50 coups).';
  return 'Match nul.';
}
