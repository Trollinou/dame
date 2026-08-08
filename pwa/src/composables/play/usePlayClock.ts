import { ref, reactive, computed } from 'vue';
import { ChessClock } from '@/utils/ChessClock';

export type ClockPreset = 'none' | '1+0' | '3+2' | '5+0' | '10+5' | '15+10';

export function usePlayClock() {
  const clock = new ChessClock();

  const clockSettings = reactive({
    preset: 'none' as ClockPreset,
    wtime: 0,
    btime: 0,
    winc: 0,
    binc: 0,
  });

  const activeClockColor = ref<'white' | 'black' | null>(null);
  const timerTenths = ref(0);

  const timerSeconds = computed(() => Math.floor(timerTenths.value / 10));

  const formattedTime = computed(() => {
    const totalSecs = timerSeconds.value;
    const min = Math.floor(totalSecs / 60);
    const sec = totalSecs % 60;
    return `${min.toString().padStart(2, '0')}:${sec.toString().padStart(2, '0')}`;
  });

  const getPlayerFormattedTime = (playerColor: 'white' | 'black') => {
    return playerColor === 'white'
      ? ChessClock.formatTime(clockSettings.wtime)
      : ChessClock.formatTime(clockSettings.btime);
  };

  const getOpponentFormattedTime = (opponentColor: 'white' | 'black') => {
    return opponentColor === 'white'
      ? ChessClock.formatTime(clockSettings.wtime)
      : ChessClock.formatTime(clockSettings.btime);
  };

  clock.onTick = (wtime, btime) => {
    clockSettings.wtime = wtime;
    clockSettings.btime = btime;
    timerTenths.value = clock.timerTenths;
  };

  const startTimer = () => {
    clock.start();
  };

  const stopTimer = () => {
    clock.stop();
  };

  const resetClock = (preset: ClockPreset) => {
    clock.reset();
    timerTenths.value = 0;
    activeClockColor.value = null;
    clock.setPreset(preset);
    clockSettings.preset = clock.preset as ClockPreset;
    clockSettings.wtime = clock.wtime;
    clockSettings.btime = clock.btime;
    clockSettings.winc = clock.winc;
    clockSettings.binc = clock.binc;
  };

  return {
    clock,
    clockSettings,
    activeClockColor,
    timerTenths,
    timerSeconds,
    formattedTime,
    getPlayerFormattedTime,
    getOpponentFormattedTime,
    startTimer,
    stopTimer,
    resetClock,
  };
}
