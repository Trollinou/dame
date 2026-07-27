<template>
  <div class="exercice-type-jugement-final">
    <JugementFinalViewer
      :consigne="config.consigne"
      :couleurJoueur="config.couleur_joueur"
      :fenDepart="config.fen_depart"
      :pgnExplication="config.pgn_explication"
      :scenarios="config.scenarios"
      @success="onSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { useApprentissageStore } from '@/stores/apprentissage';
import JugementFinalViewer, { type ScenarioJugementFinal } from '@/components/shared/JugementFinalViewer.vue';

export interface ConfigJugementFinal {
  consigne: string;
  fen_depart: string;
  couleur_joueur: 'white' | 'black';
  scenarios: ScenarioJugementFinal[];
  pgn_explication: string;
}

const props = defineProps<{
  config: ConfigJugementFinal;
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
.exercice-type-jugement-final {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
