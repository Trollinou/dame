<template>
  <div class="exercice-type-parcours">
    <ExerciseHeader
      :title="headerMeta.title"
      :typeLabel="headerMeta.typeLabel"
      :chapitreNiveauLabel="headerMeta.chapitreNiveauLabel"
      :consigne="config.description || `Atteignez la case ${config.case_arrivee} (${config.variante})`"
      stepBadgeText="1 / 1"
    />

    <ParcoursViewer
      :fenDepart="config.fen_depart"
      :couleurJoueur="config.couleur_joueur"
      :variante="config.variante"
      :caseDepart="config.case_depart"
      :caseArrivee="config.case_arrivee"
      :shapes="config.shapes || []"
      @success="$emit('success')"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import ParcoursViewer from '@/components/shared/ParcoursViewer.vue';
import ExerciseHeader from '@/components/shared/ExerciseHeader.vue';

const props = defineProps<{
  config: {
    fen_depart: string;
    couleur_joueur: 'white' | 'black';
    description?: string;
    case_depart: string;
    case_arrivee: string;
    variante: string;
    shapes?: Array<{ orig: string; dest?: string; brush: string; [key: string]: any }>;
    metaTitre?: string;
    metaTypeLabel?: string;
    metaChapitreNiveauLabel?: string;
  };
  id: number;
}>();

defineEmits<{
  (e: 'success'): void;
}>();

const headerMeta = computed(() => {
  return {
    title: props.config?.metaTitre || 'T9 - Parcours',
    typeLabel: props.config?.metaTypeLabel || 'Parcours',
    chapitreNiveauLabel: props.config?.metaChapitreNiveauLabel || '',
  };
});
</script>

<style scoped>
.exercice-type-parcours {
  width: 100%;
}
</style>
