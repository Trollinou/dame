<template>
  <div class="exercice-type-echec-eval">
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
import { useApprentissageStore } from '@/stores/apprentissage';
import EvalViewer, { type QuestionEval } from '@/components/shared/EvalViewer.vue';

export interface ConfigEchecEval {
  fen_depart: string;
  couleur_joueur: 'white' | 'black';
  shapes?: any[];
  theme: string;
  questions: QuestionEval[];
  solution_moves: string[];
  pgn_explication: string;
}

const props = defineProps<{
  config: ConfigEchecEval;
  id: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

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
