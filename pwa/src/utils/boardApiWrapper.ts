import type { BoardCore } from 'eg-chessboard';

/**
 * Undo moves on the eg-chessboard api.
 * If vsComputer is true, it undos two moves if it is the player's turn to revert both the computer's response and player's move.
 * @param boardApi
 * @param vsComputer
 * @param playerColor
 */
export function undoMove(
	boardApi: BoardCore | null,
	vsComputer = false,
	playerColor: 'white' | 'black'
): void {
	if ( ! boardApi ) {
		return;
	}
	if ( vsComputer ) {
		const turnColor = boardApi.getTurnColor();
		if ( turnColor === playerColor ) {
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
 * @param boardApi
 * @param enabled
 */
export function getFormattedCapturedPieces(
	boardApi: BoardCore | null,
	enabled = true
): { white: string[]; black: string[] } {
	if ( ! boardApi || ! enabled ) {
		return { white: [], black: [] };
	}

	const captured = boardApi.getCapturedPieces() || { white: [], black: [] };
	const pieceToSymbol = ( p: unknown ) => {
		const type = typeof p === 'string' ? p : ( p as { type?: string } )?.type;
		if ( ! type ) {
			return '';
		}
		const map: Record< string, string > = {
			p: '♟',
			n: '♞',
			b: '♝',
			r: '♜',
			q: '♛',
			k: '♚',
		};
		return map[ type.toLowerCase() ] || '';
	};

	return {
		white: ( captured.black || [] ).map( pieceToSymbol ),
		black: ( captured.white || [] ).map( pieceToSymbol ),
	};
}

/**
 * Calculates material difference display from player's perspective.
 * @param boardApi
 * @param playerColor
 * @param enabled
 */
export function getMaterialDiffDisplay(
	boardApi: BoardCore | null,
	playerColor: 'white' | 'black',
	enabled = true
): { player: number | null; opponent: number | null } {
	if ( ! boardApi || ! enabled ) {
		return { player: null, opponent: null };
	}

	const diff = boardApi.getMaterialCount()?.materialDiff ?? 0;
	if ( diff === 0 ) {
		return { player: null, opponent: null };
	}
	const playerWins = playerColor === 'white' ? diff > 0 : diff < 0;
	return {
		player: playerWins ? Math.abs( diff ) : null,
		opponent: ! playerWins ? Math.abs( diff ) : null,
	};
}

/**
 * Returns a user-friendly explanation of the game over reason in French.
 * @param boardApi
 */
export function getGameOverReason( boardApi: BoardCore | null ): string {
	if ( ! boardApi ) {
		return '';
	}

	return boardApi.getGameOverReason('fr');
}
