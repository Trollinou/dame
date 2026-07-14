<template>
  <div class="exercice-type-100commandements">
    <QcmViewer
      :question="config.question"
      :choix="config.reponses"
      :bonneReponse="config.bonne_reponse"
      @success="gererSucces"
    />
  </div>
</template>

<script setup lang="ts">
import QcmViewer from '@/components/shared/QcmViewer.vue';
import { useApprentissageStore } from '@/stores/apprentissage';

interface Config100Commandements {
  question: string;
  reponses: string[];
  bonne_reponse: number;
  id?: number;
}

const props = defineProps<{
  config: Config100Commandements;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const store = useApprentissageStore();

const gererSucces = () => {
  // Enregistrer la progression via le store
  if (props.config.id) {
    store.validerExercice(props.config.id);
  }
  
  emit('success');
};
</script>

<style scoped>
.exercice-type-100commandements {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
}
</style>
