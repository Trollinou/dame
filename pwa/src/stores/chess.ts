import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useChessStore = defineStore('chess', () => {
  const currentPgn = ref('');
  const orientation = ref<'white' | 'black'>('white');
  const engineElo = ref(1320);
  const helpCount = ref(0);
  const oupsCount = ref(0);

  const saveGame = (pgn: string, playerOrientation: 'white' | 'black', elo: number, help: number = 0, oups: number = 0) => {
    currentPgn.value = pgn;
    orientation.value = playerOrientation;
    engineElo.value = elo;
    helpCount.value = help;
    oupsCount.value = oups;
  };

  const clearGame = () => {
    currentPgn.value = '';
    helpCount.value = 0;
    oupsCount.value = 0;
  };

  return {
    currentPgn,
    orientation,
    engineElo,
    helpCount,
    oupsCount,
    saveGame,
    clearGame
  };
}, {
  persist: true
});
