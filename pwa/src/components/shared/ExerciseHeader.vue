<template>
  <div class="exercise-unified-header">
    <!-- Panel 1: Métadonnées Compactes -->
    <ion-card class="header-card meta-card ion-no-margin ion-margin-bottom">
      <ion-card-content class="meta-card-content">
        <div class="meta-title">{{ title }}</div>
        <div class="meta-bottom-row">
          <span class="meta-type">{{ typeLabel }}</span>
          <span v-if="chapitreNiveauLabel" class="meta-chapitre-niveau">{{ chapitreNiveauLabel }}</span>
        </div>
      </ion-card-content>
    </ion-card>

    <!-- Panel 2: Consigne / Question + Badge Compact -->
    <ion-card v-if="consigne || stepBadgeText" class="header-card consigne-card ion-no-margin ion-margin-bottom">
      <ion-card-content class="consigne-card-content">
        <div class="consigne-row">
          <div class="consigne-text">{{ consigne }}</div>
          <ion-badge v-if="stepBadgeText" color="primary" class="step-badge">
            {{ stepBadgeText }}
          </ion-badge>
        </div>
      </ion-card-content>
    </ion-card>
  </div>
</template>

<script setup lang="ts">
import { IonCard, IonCardContent, IonBadge } from '@ionic/vue';

defineProps<{
  title: string;
  typeLabel?: string;
  chapitreNiveauLabel?: string;
  consigne?: string;
  stepBadgeText?: string;
}>();
</script>

<style scoped>
.exercise-unified-header {
  width: 100%;
}

.header-card {
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Panel 1: Meta Card */
.meta-card-content {
  padding: 6px 10px;
}

.meta-title {
  font-size: 0.95rem;
  font-weight: 700;
  line-height: 1.2;
  color: var(--ion-color-dark, #111827);
  margin-bottom: 2px;
}

.meta-bottom-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.meta-type {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--ion-color-primary, #3880ff);
}

.meta-chapitre-niveau {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--ion-color-medium-shade, #666666);
}

/* Panel 2: Consigne Card */
.consigne-card-content {
  padding: 6px 10px;
}

.consigne-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.consigne-text {
  font-size: 0.88rem;
  font-weight: 500;
  line-height: 1.25;
  color: var(--ion-color-dark, #222222);
  flex: 1;
}

.step-badge {
  font-size: 0.78rem;
  padding: 3px 8px;
  border-radius: 10px;
  flex-shrink: 0;
  white-space: nowrap;
}
</style>
