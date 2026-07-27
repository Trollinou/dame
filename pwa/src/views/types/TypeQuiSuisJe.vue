<template>
  <div class="exercice-type-qui-suis-je">
    <ion-card v-if="config.consigne" class="ion-margin-bottom consigne-card">
      <ion-card-header>
        <ion-card-title class="consigne-title">{{ config.consigne }}</ion-card-title>
      </ion-card-header>
    </ion-card>

    <QuiSuisJeViewer
      :indices="config.indices"
      :reponse-case="config.reponse_case"
      :reponse-piece="config.reponse_piece"
      :reponse-qcm="config.reponse_qcm"
      :type-reponse="config.type_reponse"
      @success="$emit('success')"
    />
  </div>
</template>

<script setup lang="ts">
import {
  IonCard,
  IonCardHeader,
  IonCardTitle
} from '@ionic/vue';
import QuiSuisJeViewer, { type QcmConfig } from '@/components/shared/QuiSuisJeViewer.vue';

interface ConfigQuiSuisJe {
  consigne?: string;
  indices?: string[];
  type_reponse?: 'piece' | 'square' | 'qcm' | string;
  reponse_piece?: string;
  reponse_case?: string;
  reponse_qcm?: QcmConfig;
}

defineProps<{
  config: ConfigQuiSuisJe;
  id?: number;
}>();

defineEmits<{
  (e: 'success'): void;
}>();
</script>

<style scoped>
.exercice-type-qui-suis-je {
  width: 100%;
}

.consigne-card {
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.consigne-title {
  font-size: 1.1rem;
  font-weight: 600;
}
</style>
