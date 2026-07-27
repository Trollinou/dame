<template>
  <div class="exercice-type-cap-ou-pas-cap">
    <CapOuPasCapViewer
      :consigne="config.consigne"
      :typeReponse="config.type_reponse"
      :diagrammes="config.diagrammes"
      @success="onSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { useApprentissageStore } from '@/stores/apprentissage';
import CapOuPasCapViewer, { type DiagrammeCapOuPasCap } from '@/components/shared/CapOuPasCapViewer.vue';

interface ConfigCapOuPasCap {
  consigne: string;
  type_reponse: 'qcm' | 'move' | string;
  diagrammes: DiagrammeCapOuPasCap[];
}

const props = defineProps<{
  config: ConfigCapOuPasCap;
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
.exercice-type-cap-ou-pas-cap {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
