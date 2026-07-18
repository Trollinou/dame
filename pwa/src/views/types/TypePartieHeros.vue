<template>
  <div class="exercice-type-partie-heros">
    <div class="etape-container">
      
      <PgnViewer 
        v-if="etapeActuelle.type === 'pgn'"
        :pgnString="etapeActuelle.pgn_data || ''"
        :autoCompleteDelay="estDerniereEtape ? 1200 : 0"
        @finished="transitionToNextStage"
      />

      <QcmViewer 
        v-else-if="etapeActuelle.type === 'qcm'"
        :fen="etapeActuelle.fen || ''"
        :question="etapeActuelle.question || ''"
        :choix="etapeActuelle.choix || []"
        :bonneReponse="etapeActuelle.bonne_reponse || 0"
        :shapes="etapeActuelle.shapes"
        @success="transitionToNextStage"
      />

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { toastController } from '@ionic/vue';
import { useApprentissageStore } from '@/stores/apprentissage';
import PgnViewer from '@/components/shared/PgnViewer.vue';
import QcmViewer from '@/components/shared/QcmViewer.vue';

interface EtapeBase {
  type: 'pgn' | 'qcm';
  pgn_data?: string;
  fen?: string;
  question?: string;
  choix?: string[];
  bonne_reponse?: number;
  shapes?: any[];
}

interface ConfigPartieHeros {
  etapes: EtapeBase[];
}

const props = defineProps<{
  config: ConfigPartieHeros;
  id: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const store = useApprentissageStore();
const etapeCouranteIndex = ref(0);

const estDerniereEtape = computed(() => {
  if (!props.config || !props.config.etapes) return true;
  return etapeCouranteIndex.value === props.config.etapes.length - 1;
});

const etapeActuelle = computed<EtapeBase>(() => {
  if (!props.config || !props.config.etapes) {
    return { type: 'pgn' } as EtapeBase;
  }
  return props.config.etapes[etapeCouranteIndex.value] || ({ type: 'pgn' } as EtapeBase);
});

const transitionToNextStage = async () => {
  if (estDerniereEtape.value) {
    const victoryToast = await toastController.create({
      message: "Félicitations ! Vous avez terminé ce scénario !",
      duration: 3000,
      color: 'success',
      position: 'bottom'
    });
    await victoryToast.present();
    store.validerElement(props.id);
    emit('success');
  } else {
    etapeCouranteIndex.value++;
  }
};
</script>

<style scoped>
.exercice-type-partie-heros {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.etape-container {
  width: 100%;
  max-width: 500px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
</style>
