<template>
  <div class="exercice-type-qui-suis-je">
    <ExerciseHeader
      :title="headerMeta.title"
      :typeLabel="headerMeta.typeLabel"
      :chapitreNiveauLabel="headerMeta.chapitreNiveauLabel"
      :consigne="config.consigne || 'Devinez la pièce ou la case mystère d\'après les indices.'"
      stepBadgeText="1 / 1"
    />

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
import { computed } from 'vue';
import QuiSuisJeViewer, { type QcmConfig } from '@/components/shared/QuiSuisJeViewer.vue';
import ExerciseHeader from '@/components/shared/ExerciseHeader.vue';

interface ConfigQuiSuisJe {
  consigne?: string;
  indices?: string[];
  type_reponse?: 'piece' | 'square' | 'qcm' | string;
  reponse_piece?: string;
  reponse_case?: string;
  reponse_qcm?: QcmConfig;
  metaTitre?: string;
  metaTypeLabel?: string;
  metaChapitreNiveauLabel?: string;
}

const props = defineProps<{
  config: ConfigQuiSuisJe;
  id?: number;
}>();

defineEmits<{
  (e: 'success'): void;
}>();

const headerMeta = computed(() => {
  return {
    title: props.config?.metaTitre || 'T12 - Qui suis-je ?',
    typeLabel: props.config?.metaTypeLabel || 'Qui suis-je ?',
    chapitreNiveauLabel: props.config?.metaChapitreNiveauLabel || '',
  };
});
</script>

<style scoped>
.exercice-type-qui-suis-je {
  width: 100%;
}
</style>
