<template>
  <div class="exercice-type-abcdaire">
    <PuzzleViewer
      v-if="config.fen && config.solution"
      :fen="config.fen"
      :couleurJoueur="config.couleur_joueur || 'white'"
      :solution="config.solution"
      @success="gererSucces"
    />
  </div>
</template>

<script setup lang="ts">
import PuzzleViewer from '@/components/shared/PuzzleViewer.vue';
import { useApprentissageStore } from '@/stores/apprentissage';
import type { ExerciceConfig } from '@/stores/apprentissage';

const props = defineProps<{
  config: ExerciceConfig;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const store = useApprentissageStore();

const gererSucces = () => {
  // Enregistrer la progression globale de cet exercice
  if (props.config.id) {
    store.validerExercice(props.config.id);
  }
  
  // Remonter l'événement au composant parent (ExercicePage)
  emit('success');
};
</script>

<style scoped>
.exercice-type-abcdaire {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
}
</style>
