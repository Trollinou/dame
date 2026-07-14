<template>
  <div class="exercice-type-popechecs">
    <PlacementViewer
      v-if="config.fen_depart"
      :consigne="config.consigne"
      :fenDepart="config.fen_depart"
      :pieceType="config.piece_type"
      :pieceColor="config.piece_color"
      :caseCible="config.case_cible"
      @success="gererSucces"
    />
  </div>
</template>

<script setup lang="ts">
import PlacementViewer from '@/components/shared/PlacementViewer.vue';
import { useApprentissageStore } from '@/stores/apprentissage';

interface ConfigPopEchecs {
  consigne: string;
  fen_depart: string;
  piece_type: 'p' | 'r' | 'n' | 'b' | 'q' | 'k';
  piece_color: 'white' | 'black' | 'w' | 'b' | string;
  case_cible: string;
}

const props = defineProps<{
  config: ConfigPopEchecs;
  id: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const store = useApprentissageStore();

const gererSucces = () => {
  // Enregistrement de la progression
  if (props.id) {
    store.validerExercice(props.id);
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
