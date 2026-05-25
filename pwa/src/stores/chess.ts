import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useChessStore = defineStore('chess', () => {
  const currentPgn = ref('');
  const orientation = ref<'white' | 'black'>('white');
  const engineElo = ref(1320);

  const saveGame = (pgn: string, playerOrientation: 'white' | 'black', elo: number) => {
    currentPgn.value = pgn;
    orientation.value = playerOrientation;
    engineElo.value = elo;
  };

  const clearGame = () => {
    currentPgn.value = '';
  };

  return {
    currentPgn,
    orientation,
    engineElo,
    saveGame,
    clearGame
  };
});
