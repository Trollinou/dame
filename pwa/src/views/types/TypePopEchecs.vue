<template>
  <div class="exercice-type-popechecs">
    <ExerciseHeader
      :title="headerMeta.title"
      :typeLabel="headerMeta.typeLabel"
      :chapitreNiveauLabel="headerMeta.chapitreNiveauLabel"
      :consigne="config.consigne"
      stepBadgeText="1 / 1"
    />

    <PlacementViewer
      v-if="config.fen_depart"
      :consigne="''"
      :fenDepart="config.fen_depart"
      :pieceType="config.piece_type"
      :pieceColor="config.piece_color"
      :caseCible="config.case_cible"
      @success="gererSucces"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import PlacementViewer from '@/components/shared/PlacementViewer.vue';
import ExerciseHeader from '@/components/shared/ExerciseHeader.vue';
import { useApprentissageStore } from '@/stores/apprentissage';

interface ConfigPopEchecs {
  consigne: string;
  fen_depart: string;
  piece_type: 'p' | 'r' | 'n' | 'b' | 'q' | 'k';
  piece_color: 'white' | 'black' | 'w' | 'b' | string;
  case_cible: string;
  metaTitre?: string;
  metaTypeLabel?: string;
  metaChapitreNiveauLabel?: string;
}

const props = defineProps<{
  config: ConfigPopEchecs;
  id: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const headerMeta = computed(() => {
  return {
    title: props.config?.metaTitre || 'T2 - Pop-échecs',
    typeLabel: props.config?.metaTypeLabel || 'Pop-échecs',
    chapitreNiveauLabel: props.config?.metaChapitreNiveauLabel || '',
  };
});

const store = useApprentissageStore();

const gererSucces = () => {
  if (props.id) {
    store.validerElement(props.id);
  }
  emit('success');
};
</script>

<style scoped>
.exercice-type-popechecs {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
}
</style>
