<template>
  <div :class="['captured-bar', position]">
    <div class="material-wrapper">
      <div v-if="materialDiff" class="material-count">
        +{{ materialDiff }}
      </div>
    </div>
    <div class="player-info">{{ playerLabel }}</div>
    <div class="captured-pieces">
      <span v-for="(p, i) in capturedPieces" :key="i" class="captured-piece">
        {{ p }}
      </span>
    </div>
    <div
      v-if="clockPreset !== 'none'"
      :class="['game-clock', `${side}-clock`, { active: isClockActive }]"
    >
      {{ formattedTime }}
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  position: 'top' | 'bottom';
  side: 'player' | 'opponent';
  playerLabel: string;
  capturedPieces: string[];
  materialDiff: number | null;
  clockPreset: string;
  formattedTime: string;
  isClockActive: boolean;
}>();
</script>

<style scoped>
.captured-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 8px;
  background: var(--ion-color-step-100, #eee);
  border-radius: 4px;
  font-size: 0.85em;
}

.captured-bar.top {
  margin-bottom: 4px;
}

.captured-bar.bottom {
  margin-top: 4px;
}

.player-info {
  font-weight: 600;
  min-width: 70px;
}

.captured-pieces {
  flex: 1;
  display: flex;
  gap: 2px;
  overflow-x: auto;
}

.captured-piece {
  font-size: 1.1em;
}

.material-wrapper {
  min-width: 24px;
}

.material-count {
  font-weight: 700;
  color: var(--ion-color-primary);
}

.game-clock {
  font-family: monospace;
  font-size: 1em;
  padding: 2px 6px;
  border-radius: 4px;
  background: var(--ion-color-step-200, #ddd);
}

.game-clock.active {
  background: var(--ion-color-primary);
  color: var(--ion-color-primary-contrast);
  font-weight: 700;
}
</style>
