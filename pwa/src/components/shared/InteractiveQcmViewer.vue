<template>
  <div class="interactive-qcm-stage-layout">
    <!-- Échiquier en haut -->
    <div class="board-container">
      <eg-chessboard
        :boardConfig="boardConfig"
        :playerColor="couleurJoueur"
        :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
        @board-created="onBoardCreated"
      />
    </div>

    <!-- Carte QCM en bas -->
    <ion-card class="question-card">
      <ion-card-header>
        <ion-card-title class="question-title">
          {{ etapeActuelle.question }}
        </ion-card-title>
      </ion-card-header>

      <ion-card-content>
        <div class="qcm-choices">
          <ion-button
            v-for="(choixObj, index) in etapeActuelle.choix"
            :key="index"
            expand="block"
            fill="solid"
            :color="couleurBouton(index)"
            :disabled="repondu"
            class="choice-btn"
            @click="validerChoix(index)"
          >
            {{ choixObj.texte }}
          </ion-button>
        </div>
      </ion-card-content>
    </ion-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonButton,
  toastController
} from '@ionic/vue';
import EgChessboard from 'eg-chessboard/vue';
import type { BoardCore } from 'eg-chessboard';

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

const props = withDefaults(
  defineProps<{
    fenDepart: string;
    couleurJoueur: 'white' | 'black';
    etapes: Etape[];
    shapes?: any[];
  }>(),
  {
    shapes: () => []
  }
);

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const etapeCouranteIndex = ref(0);
const boardApi = ref<BoardCore | null>(null);
const repondu = ref(false);
const indexChoisi = ref<number | null>(null);

const boardConfig = computed(() => ({
  fen: props.fenDepart,
  viewOnly: true,
  drawable: { shapes: props.shapes }
}));

const etapeActuelle = computed<Etape>(() => {
  return props.etapes[etapeCouranteIndex.value] || {
    question: '',
    choix: [],
    bonne_reponse: 0
  };
});

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
};

watch(() => props.shapes, (newShapes) => {
  if (boardApi.value && newShapes) {
    boardApi.value.setShapes(newShapes);
  }
}, { deep: true });

// Réinitialiser en cas de changement de la FEN de départ
watch(() => props.fenDepart, (newFen) => {
  etapeCouranteIndex.value = 0;
  repondu.value = false;
  indexChoisi.value = null;
  if (boardApi.value && newFen) {
    boardApi.value.setPosition(newFen);
  }
});

const couleurBouton = (index: number): string => {
  if (!repondu.value) {
    return 'primary';
  }
  if (index === etapeActuelle.value.bonne_reponse) {
    return 'success';
  }
  if (index === indexChoisi.value) {
    return 'danger';
  }
  return 'medium';
};

const validerChoix = async (index: number) => {
  if (repondu.value) {
    return;
  }

  indexChoisi.value = index;

  if (index !== etapeActuelle.value.bonne_reponse) {
    // Si mauvaise réponse, affiche un toast rouge avec l'explication et n'active pas repondu (permet de réessayer)
    const toast = await toastController.create({
      message: etapeActuelle.value.choix[index].explication || 'Mauvaise réponse, essaie encore !',
      duration: 2500,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
    indexChoisi.value = null;
  } else {
    // Si bonne réponse
    repondu.value = true;
    const toast = await toastController.create({
      message: etapeActuelle.value.choix[index].explication || 'Bien joué !',
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();

    // Joue le coup de l'utilisateur
    if (boardApi.value) {
      boardApi.value.move(etapeActuelle.value.choix[index].san);
    }

    // Attendre 600ms
    await new Promise((resolve) => setTimeout(resolve, 600));

    // Si réponse de l'ordinateur, on la joue
    if (etapeActuelle.value.reponse_ordinateur && boardApi.value) {
      boardApi.value.move(etapeActuelle.value.reponse_ordinateur);
    }

    // Attendre 800ms
    await new Promise((resolve) => setTimeout(resolve, 800));

    // Vérifier si dernière étape
    if (etapeCouranteIndex.value === props.etapes.length - 1) {
      emit('success');
    } else {
      etapeCouranteIndex.value++;
      repondu.value = false;
      indexChoisi.value = null;
    }
  }
};
</script>

<style scoped>
.interactive-qcm-stage-layout {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.board-container {
  width: 100%;
  aspect-ratio: 1;
  max-width: 500px;
  margin: 0 auto;
  border-radius: 0;
  overflow: hidden;
  box-shadow: none;
  margin-bottom: 12px;
}

.question-card {
  width: 100%;
  margin: 0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.question-title {
  font-size: 1.15rem;
  font-weight: 600;
  line-height: 1.5;
  text-align: center;
  color: var(--ion-color-step-900, #222);
}

.qcm-choices {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.choice-btn {
  text-transform: none;
  font-size: 1rem;
  font-weight: 500;
  --border-radius: 8px;
  min-height: 48px;
  white-space: normal;
}

.choice-btn::part(native) {
  white-space: normal;
  text-align: left;
  padding: 12px 16px;
}
</style>
