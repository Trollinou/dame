<template>
  <div class="exercice-type-100commandements">
    <div v-if="qcmsList.length > 1" class="step-indicator ion-margin-bottom">
      <ion-badge color="primary" class="qcm-badge">
        Question {{ qcmIndex + 1 }} / {{ qcmsList.length }}
      </ion-badge>
    </div>

    <QcmViewer
      v-if="qcmActuel"
      :key="qcmIndex"
      :question="qcmActuel.question"
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
import { IonBadge } from '@ionic/vue';
import QcmViewer from '@/components/shared/QcmViewer.vue';
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
}

const props = defineProps<{
  config: Config100Commandements;
  id?: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

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

.step-indicator {
  display: flex;
  justify-content: center;
  align-items: center;
}

.qcm-badge {
  font-size: 0.9rem;
  padding: 6px 12px;
  border-radius: 12px;
  letter-spacing: 0.5px;
}
</style>
