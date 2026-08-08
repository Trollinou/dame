<template>
  <div class="side-section">
    <div class="message-zone ion-text-center">
      <div v-if="gameStatusMessage" :class="['status-banner', gameStatusColor]">
        {{ gameStatusMessage }}
      </div>
      <div v-else class="status-placeholder">
        C'est au tour des {{ turnColor === 'white' ? 'Blancs' : 'Noirs' }}
      </div>
    </div>

    <div class="actions-container">
      <ion-grid class="ion-no-padding">
        <ion-row>
          <ion-col :size="isLandscape ? '12' : '3'" class="ion-margin-bottom">
            <ion-button expand="block" @click="$emit('reset-game')" color="secondary" class="action-btn">
              Nouv.
            </ion-button>
          </ion-col>
          <ion-col :size="isLandscape ? '12' : '3'" class="ion-margin-bottom">
            <ion-button expand="block" @click="$emit('toggle-hint')" :color="isHintEnabled ? 'success' : 'medium'" class="action-btn">
              {{ helpCount > 0 ? `Aide : ${helpCount}` : 'Aide' }}
            </ion-button>
          </ion-col>
          <ion-col :size="isLandscape ? '12' : '3'" class="ion-margin-bottom">
            <ion-button expand="block" @click="$emit('undo-move')" :disabled="viewOnly" color="warning" class="action-btn">
              {{ oupsCount > 0 ? `Oups : ${oupsCount}` : 'Oups !' }}
            </ion-button>
          </ion-col>
          <ion-col :size="isLandscape ? '12' : '3'" class="ion-margin-bottom">
            <ion-button expand="block" @click="$emit('go-to-analysis')" color="tertiary" class="action-btn">
              Analyse
            </ion-button>
          </ion-col>
        </ion-row>
      </ion-grid>
    </div>
  </div>
</template>

<script setup lang="ts">
import { IonGrid, IonRow, IonCol, IonButton } from '@ionic/vue';

defineProps<{
  isLandscape: boolean;
  gameStatusMessage: string;
  gameStatusColor: string;
  turnColor: 'white' | 'black';
  isHintEnabled: boolean;
  helpCount: number;
  oupsCount: number;
  viewOnly: boolean;
}>();

defineEmits<{
  (e: 'reset-game'): void;
  (e: 'toggle-hint'): void;
  (e: 'undo-move'): void;
  (e: 'go-to-analysis'): void;
}>();
</script>

<style scoped>
.side-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.message-zone {
  min-height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-banner {
  padding: 6px 12px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9em;
}

.status-banner.warning {
  background: var(--ion-color-warning);
  color: var(--ion-color-warning-contrast);
}

.status-banner.danger {
  background: var(--ion-color-danger);
  color: var(--ion-color-danger-contrast);
}

.status-placeholder {
  color: var(--ion-color-medium);
  font-size: 0.9em;
}

.action-btn {
  font-size: 0.7rem;
  --padding-start: 2px;
  --padding-end: 2px;
}
</style>
