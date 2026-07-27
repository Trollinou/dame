<template>
  <div class="text-order-layout">
    <!-- PHASE 1 : ORDER -->
    <div v-if="phase === 'order'" class="order-stage">
      <!-- Card Consigne -->
      <ion-card v-if="consigne" class="consigne-card">
        <ion-card-header>
          <ion-card-title class="consigne-title">{{ consigne }}</ion-card-title>
        </ion-card-header>
      </ion-card>

      <!-- Échiquier de départ -->
      <div class="board-container">
        <eg-chessboard
          :boardConfig="boardConfig"
          :playerColor="couleurJoueurTyped"
          :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
        />
      </div>

      <!-- Liste des étapes à réordonner -->
      <ion-card class="list-card">
        <ion-list>
          <ion-reorder-group :disabled="false" @ionItemReorder="handleReorder">
            <ion-item v-for="etape in shuffledEtapes" :key="etape.id">
              <ion-label class="ion-text-wrap">{{ etape.texte }}</ion-label>
              <ion-reorder slot="end"></ion-reorder>
            </ion-item>
          </ion-reorder-group>
        </ion-list>
      </ion-card>

      <!-- Bouton de validation -->
      <ion-button
        expand="block"
        color="primary"
        class="action-btn ion-margin-top"
        @click="validerOrdre"
      >
        Valider l'ordre
      </ion-button>
    </div>

    <!-- PHASE 2 : SOLUTION -->
    <div v-else-if="phase === 'solution'" class="solution-stage">
      <ion-card class="consigne-card">
        <ion-card-header>
          <ion-card-title class="consigne-title">💡 Explication détaillée</ion-card-title>
        </ion-card-header>
      </ion-card>

      <PgnViewer
        :orientation="couleurJoueurTyped"
        :pgn="pgnExplication"
        :pgnString="pgnExplication"
      />

      <ion-button
        expand="block"
        color="success"
        class="finish-btn ion-margin-top"
        @click="finishExercise"
      >
        Terminer l'exercice
      </ion-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonList,
  IonReorderGroup,
  IonItem,
  IonLabel,
  IonReorder,
  IonButton,
  toastController
} from '@ionic/vue';
import EgChessboard from 'eg-chessboard/vue';
import PgnViewer from '@/components/shared/PgnViewer.vue';

export interface EtapeTexte {
  id: number;
  texte: string;
}

const props = withDefaults(
  defineProps<{
    consigne?: string;
    fenDepart?: string;
    couleurJoueur?: 'white' | 'black' | string;
    etapesCorrectes?: string[];
    pgnExplication?: string;
  }>(),
  {
    consigne: '',
    fenDepart: '',
    couleurJoueur: 'white',
    etapesCorrectes: () => [],
    pgnExplication: ''
  }
);

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const phase = ref<'order' | 'solution'>('order');
const shuffledEtapes = ref<EtapeTexte[]>([]);

const couleurJoueurTyped = computed<'white' | 'black'>(() => {
  return props.couleurJoueur === 'black' ? 'black' : 'white';
});

const boardConfig = computed(() => ({
  fen: props.fenDepart || 'start',
  orientation: couleurJoueurTyped.value,
  viewOnly: true
}));

const shuffleArray = <T,>(array: T[]): T[] => {
  const arr = [...array];
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  return arr;
};

const initShuffledEtapes = () => {
  if (!props.etapesCorrectes || props.etapesCorrectes.length === 0) {
    shuffledEtapes.value = [];
    return;
  }
  const originalList: EtapeTexte[] = props.etapesCorrectes.map((texte, index) => ({
    id: index,
    texte
  }));

  let shuffled = shuffleArray(originalList);
  // S'assurer que le tableau n'est pas déjà ordonné si plus de 1 élément
  if (originalList.length > 1 && shuffled.every((item, idx) => item.id === idx)) {
    shuffled = shuffleArray(originalList);
  }

  shuffledEtapes.value = shuffled;
};

onMounted(() => {
  initShuffledEtapes();
});

watch(
  () => props.etapesCorrectes,
  () => {
    initShuffledEtapes();
  },
  { deep: true }
);

const handleReorder = (event: any) => {
  const movedItem = shuffledEtapes.value.splice(event.detail.from, 1)[0];
  shuffledEtapes.value.splice(event.detail.to, 0, movedItem);
  event.detail.complete();
};

const validerOrdre = async () => {
  const estCorrect = shuffledEtapes.value.every((etape, index) => etape.id === index);

  if (estCorrect) {
    const toast = await toastController.create({
      message: "Excellente réponse ! L'ordre des étapes est parfait.",
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();
    phase.value = 'solution';
  } else {
    const toast = await toastController.create({
      message: "L'ordre des étapes n'est pas correct, réessayez !",
      duration: 2500,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  }
};

const finishExercise = () => {
  emit('success');
};
</script>

<style scoped>
.text-order-layout {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.order-stage,
.solution-stage {
  width: 100%;
  max-width: 600px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.consigne-card {
  width: 100%;
  margin: 0 0 16px 0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.consigne-title {
  font-size: 1.1rem;
  font-weight: 600;
  text-align: center;
}

.board-container {
  width: 100%;
  aspect-ratio: 1;
  max-width: 500px;
  margin: 0 auto 16px auto;
  border-radius: 0;
  overflow: hidden;
  box-shadow: none;
}

.list-card {
  width: 100%;
  margin: 0 0 16px 0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
}

.action-btn,
.finish-btn {
  width: 100%;
  max-width: 320px;
  font-weight: 600;
}
</style>
