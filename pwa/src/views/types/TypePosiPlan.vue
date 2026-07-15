<template>
  <div class="exercice-type-posi-plan">
    <InteractiveQcmViewer
      :fenDepart="config.fen_depart"
      :couleurJoueur="config.couleur_joueur"
      :etapes="config.etapes"
      @success="onSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { useApprentissageStore } from '@/stores/apprentissage';
import InteractiveQcmViewer from '@/components/shared/InteractiveQcmViewer.vue';

interface Choix {
  texte: string;
  san: string;
  explication: string;
}

interface Etape {
  question: string;
  choix: Choix[];
  bonne_reponse: number;
  reponse_ordinateur?: string;
}

interface ConfigPosiPlan {
  fen_depart: string;
  couleur_joueur: 'white' | 'black';
  etapes: Etape[];
}

const props = defineProps<{
  config: ConfigPosiPlan;
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
.exercice-type-posi-plan {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
