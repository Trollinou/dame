<template>
  <div class="exercice-type-ouvre-boite">
    <ExerciseHeader
      :title="headerMeta.title"
      :typeLabel="headerMeta.typeLabel"
      :chapitreNiveauLabel="headerMeta.chapitreNiveauLabel"
      :consigne="config.question || 'Trouvez la faille dans la position.'"
      stepBadgeText="1 / 1"
    />

    <InteractiveQcmViewer
      :fenDepart="config.fen_depart"
      :couleurJoueur="config.couleur_joueur"
      :etapes="etapesFormatees"
      :shapes="config.shapes || []"
      @success="onSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useApprentissageStore } from '@/stores/apprentissage';
import InteractiveQcmViewer from '@/components/shared/InteractiveQcmViewer.vue';
import ExerciseHeader from '@/components/shared/ExerciseHeader.vue';

interface Choix {
  texte: string;
  san: string;
  explication: string;
}

interface ConfigOuvreBoite {
  fen_depart: string;
  couleur_joueur: 'white' | 'black';
  question: string;
  choix: Choix[];
  bonne_reponse: number;
  shapes?: any[];
  metaTitre?: string;
  metaTypeLabel?: string;
  metaChapitreNiveauLabel?: string;
}

const props = defineProps<{
  config: ConfigOuvreBoite;
  id: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const headerMeta = computed(() => {
  return {
    title: props.config?.metaTitre || 'T13 - Ouvre-Boîte',
    typeLabel: props.config?.metaTypeLabel || 'Ouvre-Boîte',
    chapitreNiveauLabel: props.config?.metaChapitreNiveauLabel || '',
  };
});

const store = useApprentissageStore();

const etapesFormatees = computed(() => {
  return [
    {
      question: props.config.question,
      choix: props.config.choix,
      bonne_reponse: props.config.bonne_reponse
    }
  ];
});

const onSuccess = () => {
  store.validerElement(props.id);
  emit('success');
};
</script>

<style scoped>
.exercice-type-ouvre-boite {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
