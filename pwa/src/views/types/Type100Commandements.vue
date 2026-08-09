<template>
  <div class="exercice-type-100commandements">
    <ExerciseHeader
      :title="headerMeta.title"
      :typeLabel="headerMeta.typeLabel"
      :chapitreNiveauLabel="headerMeta.chapitreNiveauLabel"
      :consigne="qcmActuel?.question"
      :stepBadgeText="`Question ${qcmIndex + 1} / ${qcmsList.length}`"
    />

    <QcmViewer
      v-if="qcmActuel"
      :key="qcmIndex"
      :question="qcmActuel.question"
      :hideQuestion="true"
      :choix="qcmActuel.reponses || qcmActuel.choix || []"
      :bonneReponse="qcmActuel.bonne_reponse ?? qcmActuel.bonneReponse ?? 0"
      :shapes="qcmActuel.shapes || props.config?.shapes"
      :fen="qcmActuel.fen || props.config?.fen"
      @success="gererSucces"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import QcmViewer from '@/components/shared/QcmViewer.vue';
import ExerciseHeader from '@/components/shared/ExerciseHeader.vue';
import { useApprentissageStore } from '@/stores/apprentissage';

export interface QcmItem {
  question: string;
  reponses?: string[];
  choix?: string[];
  bonne_reponse?: number;
  bonneReponse?: number;
  shapes?: any[];
  fen?: string;
}

export interface Config100Commandements {
  qcms?: QcmItem[];
  // Rétrocompatibilité pour QCM unique
  question?: string;
  reponses?: string[];
  choix?: string[];
  bonne_reponse?: number;
  bonneReponse?: number;
  shapes?: any[];
  fen?: string;
  id?: number;
  metaTitre?: string;
  metaTypeLabel?: string;
  metaChapitreNiveauLabel?: string;
}

const props = defineProps<{
  config: Config100Commandements;
  id?: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const headerMeta = computed(() => {
  return {
    title: props.config?.metaTitre || 'T1 - 100 Commandements',
    typeLabel: props.config?.metaTypeLabel || '100 Commandements',
    chapitreNiveauLabel: props.config?.metaChapitreNiveauLabel || '',
  };
});

const store = useApprentissageStore();
const qcmIndex = ref(0);

const qcmsList = computed<QcmItem[]>(() => {
  if (props.config?.qcms && Array.isArray(props.config.qcms) && props.config.qcms.length > 0) {
    return props.config.qcms;
  }
  if (props.config?.question) {
    return [
      {
        question: props.config.question,
        reponses: props.config.reponses || props.config.choix || [],
        bonne_reponse: props.config.bonne_reponse ?? props.config.bonneReponse ?? 0,
        shapes: props.config.shapes,
        fen: props.config.fen
      }
    ];
  }
  return [];
});

const qcmActuel = computed<QcmItem | null>(() => {
  if (qcmsList.value.length === 0) return null;
  return qcmsList.value[qcmIndex.value] || qcmsList.value[0];
});

const estDernierQcm = computed(() => {
  return qcmIndex.value >= qcmsList.value.length - 1;
});

watch(
  () => props.config,
  () => {
    qcmIndex.value = 0;
  },
  { deep: true }
);

const gererSucces = () => {
  if (!estDernierQcm.value) {
    qcmIndex.value++;
  } else {
    const targetId = props.id || props.config?.id;
    if (targetId) {
      store.validerElement(targetId);
    }
    emit('success');
  }
};
</script>

<style scoped>
.exercice-type-100commandements {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
}
</style>
