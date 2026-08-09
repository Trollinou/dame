<template>
  <div class="exercice-type-echec-eval">
    <ExerciseHeader
      :title="headerMeta.title"
      :typeLabel="headerMeta.typeLabel"
      :chapitreNiveauLabel="headerMeta.chapitreNiveauLabel"
      :consigne="config.questions?.[0]?.texte || `Évaluez la position (${config.theme || 'Échec & Éval'})`"
      :stepBadgeText="`Question 1 / ${config.questions?.length || 1}`"
    />

    <EvalViewer
      :couleurJoueur="config.couleur_joueur"
      :fenDepart="config.fen_depart"
      :pgnExplication="config.pgn_explication"
      :questions="config.questions"
      :shapes="config.shapes || []"
      :solutionMoves="config.solution_moves"
      :theme="config.theme"
      @success="onSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useApprentissageStore } from '@/stores/apprentissage';
import EvalViewer, { type QuestionEval } from '@/components/shared/EvalViewer.vue';
import ExerciseHeader from '@/components/shared/ExerciseHeader.vue';

export interface ConfigEchecEval {
  fen_depart: string;
  couleur_joueur: 'white' | 'black';
  shapes?: any[];
  theme: string;
  questions: QuestionEval[];
  solution_moves: string[];
  pgn_explication: string;
  metaTitre?: string;
  metaTypeLabel?: string;
  metaChapitreNiveauLabel?: string;
}

const props = defineProps<{
  config: ConfigEchecEval;
  id: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const headerMeta = computed(() => {
  return {
    title: props.config?.metaTitre || 'T10 - Échec & Éval',
    typeLabel: props.config?.metaTypeLabel || 'Échec & Éval',
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
.exercice-type-echec-eval {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
