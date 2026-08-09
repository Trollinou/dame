export interface PieceInfo {
  role: 'king' | 'queen' | 'rook' | 'bishop' | 'knight' | 'pawn';
  color: 'white' | 'black';
  square: string;
  code: string; // e.g. 'wP', 'bK'
  rank: number;
  file: number;
}

export interface PieceColumns {
  col1: PieceInfo[];
  col2: PieceInfo[];
  isTwoColumns: boolean;
}

const ROLE_ORDER: Record<string, number> = {
  king: 1,
  queen: 2,
  rook: 3,
  bishop: 4,
  knight: 5,
  pawn: 6,
};

const CHAR_TO_PIECE: Record<string, { role: PieceInfo['role']; color: PieceInfo['color']; code: string }> = {
  K: { role: 'king', color: 'white', code: 'wK' },
  Q: { role: 'queen', color: 'white', code: 'wQ' },
  R: { role: 'rook', color: 'white', code: 'wR' },
  B: { role: 'bishop', color: 'white', code: 'wB' },
  N: { role: 'knight', color: 'white', code: 'wN' },
  P: { role: 'pawn', color: 'white', code: 'wP' },
  k: { role: 'king', color: 'black', code: 'bK' },
  q: { role: 'queen', color: 'black', code: 'bQ' },
  r: { role: 'rook', color: 'black', code: 'bR' },
  b: { role: 'bishop', color: 'black', code: 'bB' },
  n: { role: 'knight', color: 'black', code: 'bN' },
  p: { role: 'pawn', color: 'black', code: 'bP' },
};

/**
 * Parses a FEN position string and extracts all pieces with their role, color, and square.
 */
export function parseFenPieces(fen: string): PieceInfo[] {
  if (!fen || typeof fen !== 'string') {
    return [];
  }

  const parts = fen.trim().split(/\s+/);
  const placement = parts[0];
  if (!placement) {
    return [];
  }

  const ranks = placement.split('/');
  if (ranks.length !== 8) {
    return [];
  }

  const files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
  const pieces: PieceInfo[] = [];

  for (let r = 0; r < 8; r++) {
    const rankStr = ranks[r];
    const rankNumber = 8 - r;
    let fileIdx = 0;

    for (let i = 0; i < rankStr.length; i++) {
      const char = rankStr[i];
      if (char >= '1' && char <= '8') {
        fileIdx += parseInt(char, 10);
      } else if (CHAR_TO_PIECE[char]) {
        const fileChar = files[fileIdx] || 'a';
        const square = `${fileChar}${rankNumber}`;
        const meta = CHAR_TO_PIECE[char];

        pieces.push({
          role: meta.role,
          color: meta.color,
          square,
          code: meta.code,
          rank: rankNumber,
          file: fileIdx,
        });

        fileIdx += 1;
      }
    }
  }

  // Sort pieces: White first, then Black. Within each color, sort by role order then square.
  pieces.sort((a, b) => {
    if (a.color !== b.color) {
      return a.color === 'white' ? -1 : 1;
    }
    const roleDiff = ROLE_ORDER[a.role] - ROLE_ORDER[b.role];
    if (roleDiff !== 0) {
      return roleDiff;
    }
    return a.square.localeCompare(b.square);
  });

  return pieces;
}

/**
 * Determines the column distribution for pieces in the description panel.
 * - Total <= 4 pieces: 1 column.
 * - Total > 4 pieces: 2 columns.
 *   - If white <= 4 AND black <= 4: Col 1 = White, Col 2 = Black.
 *   - If any color > 4: Col 1 = first 4 pieces (white first), Col 2 = remaining pieces.
 */
export function getPieceColumns(pieces: PieceInfo[]): PieceColumns {
  const total = pieces.length;

  if (total <= 4) {
    return {
      col1: pieces,
      col2: [],
      isTwoColumns: false,
    };
  }

  const whitePieces = pieces.filter((p) => p.color === 'white');
  const blackPieces = pieces.filter((p) => p.color === 'black');

  if (whitePieces.length <= 4 && blackPieces.length <= 4) {
    return {
      col1: whitePieces,
      col2: blackPieces,
      isTwoColumns: true,
    };
  }

  // If one color has > 4 pieces, flow white first then black across the 2 columns
  const sortedAll = [...whitePieces, ...blackPieces];
  return {
    col1: sortedAll.slice(0, 4),
    col2: sortedAll.slice(4),
    isTwoColumns: true,
  };
}

/**
 * Extracts active color from FEN ('white' or 'black').
 */
export function getActiveColorFromFen(fen: string): 'white' | 'black' {
  if (!fen || typeof fen !== 'string') {
    return 'white';
  }
  const parts = fen.trim().split(/\s+/);
  if (parts.length >= 2 && parts[1].toLowerCase() === 'b') {
    return 'black';
  }
  return 'white';
}
