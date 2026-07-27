<template>
  <div class="exercice-type-destination-finale">
    <TextOrderViewer
      :consigne="config.consigne"
      :couleurJoueur="config.couleur_joueur"
      :etapesCorrectes="config.etapes_texte"
      :fenDepart="config.fen_depart"
      :pgnExplication="config.pgn_explication"
      @success="onSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { useApprentissageStore } from '@/stores/apprentissage';
import TextOrderViewer from '@/components/shared/TextOrderViewer.vue';

export interface ConfigDestinationFinale {
  consigne: string;
  fen_depart: string;
  couleur_joueur: 'white' | 'black';
  etapes_texte: string[];
  pgn_explication: string;
}

const props = defineProps<{
  config: ConfigDestinationFinale;
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
.exercice-type-destination-finale {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
