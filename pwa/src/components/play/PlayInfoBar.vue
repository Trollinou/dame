<template>
  <div class="game-info-bar">
    <div class="matchup-info">
      {{ playerName }} vs Stockfish ({{ level }} ELO)
    </div>
    <div class="game-timer">
      {{ formattedTime }}
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  playerName: string;
  level: number;
  formattedTime: string;
}>();
</script>

<style scoped>
.game-info-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  background: var(--ion-color-step-50, #f4f5f8);
  border-radius: 8px;
  margin-bottom: 8px;
  font-size: 0.9em;
  font-weight: 600;
}

.matchup-info {
  color: var(--ion-color-dark);
}

.game-timer {
  font-family: monospace;
  font-size: 1.1em;
  color: var(--ion-color-primary);
}
</style>
