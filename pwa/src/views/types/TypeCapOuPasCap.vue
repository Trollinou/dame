<template>
  <div class="exercice-type-cap-ou-pas-cap">
    <ExerciseHeader
      :title="headerMeta.title"
      :typeLabel="headerMeta.typeLabel"
      :chapitreNiveauLabel="headerMeta.chapitreNiveauLabel"
      :consigne="config.consigne || 'Relevez le défi Cap ou pas Cap ?'"
      :stepBadgeText="`Diagramme 1 / ${config.diagrammes?.length || 1}`"
    />

    <CapOuPasCapViewer
      :consigne="''"
      :typeReponse="config.type_reponse"
      :diagrammes="config.diagrammes"
      @success="onSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useApprentissageStore } from '@/stores/apprentissage';
import CapOuPasCapViewer, { type DiagrammeCapOuPasCap } from '@/components/shared/CapOuPasCapViewer.vue';
import ExerciseHeader from '@/components/shared/ExerciseHeader.vue';

interface ConfigCapOuPasCap {
  consigne: string;
  type_reponse: 'qcm' | 'move' | string;
  diagrammes: DiagrammeCapOuPasCap[];
  metaTitre?: string;
  metaTypeLabel?: string;
  metaChapitreNiveauLabel?: string;
}

const props = defineProps<{
  config: ConfigCapOuPasCap;
  id: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const headerMeta = computed(() => {
  return {
    title: props.config?.metaTitre || 'T14 - Cap ou pas Cap ?',
    typeLabel: props.config?.metaTypeLabel || 'Cap ou pas Cap ?',
    chapitreNiveauLabel: props.config?.metaChapitreNiveauLabel || '',
  };
});

const store = useApprentissageStore();

const onSuccess = () => {
  store.validerElement(props.id);
  emit('success');
};
</script>

<style scoped>
.exercice-type-cap-ou-pas-cap {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
