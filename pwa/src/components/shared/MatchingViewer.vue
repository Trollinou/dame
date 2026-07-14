<template>
  <div class="matching-container">
    <div class="matching-rows-list">
      <div
        v-for="(paire, index) in paires"
        :key="index"
        class="matching-row"
      >
        <!-- Échiquier (Gauche, mélangé) -->
        <div class="board-column">
          <div
            v-if="echiquiersMelanges[index]"
            class="board-wrapper-card"
            :class="{
              'selected': selectionEchiquier === index,
              'linked': liaisons[index] !== undefined,
              'correct-locked': boardsCorrects.includes(index)
            }"
            :style="getBoardStyle(index)"
            @click="selectBoard(index)"
          >
            <div class="board-header-bar">
              <span class="board-num">Échiquier {{ index + 1 }}</span>
              <span
                v-if="liaisons[index] !== undefined"
                class="link-indicator"
                :style="{
                  'background-color': colors[liaisons[index]].badgeBg,
                  'color': colors[liaisons[index]].text,
                  'border': `1px solid ${colors[liaisons[index]].border}`
                }"
              >
                <span v-if="boardsCorrects.includes(index)">✓ </span>Option {{ getLetter(liaisons[index]) }}
              </span>
            </div>
            <div class="board-mini-container">
              <eg-chessboard
                :boardConfig="{ fen: echiquiersMelanges[index].fen, viewOnly: true, coordinates: false }"
                :playerColor="echiquiersMelanges[index].couleur_joueur"
                :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
              />
            </div>
          </div>
        </div>

        <!-- Description (Droite, fixe dans l'ordre d'origine) -->
        <div class="desc-column">
          <ion-card
            class="description-card"
            :class="{
              'linked': isDescLinked(index),
              'correct-locked': isDescLocked(index),
              'active-for-selection': selectionEchiquier !== null && !isDescLinked(index) && !isDescLocked(index)
            }"
            :style="{
              'border-color': colors[index].border,
              'background-color': isDescLinked(index) || isDescLocked(index) ? colors[index].bg : 'var(--ion-color-step-50, #fcfcfc)'
            }"
            @click="linkDescription(index)"
          >
            <ion-card-content class="desc-card-content">
              <div
                class="desc-letter-badge"
                :style="{
                  'background-color': colors[index].badgeBg,
                  'color': colors[index].text,
                  'border': `1px solid ${colors[index].border}`
                }"
              >
                Option {{ getLetter(index) }}
              </div>
              <div class="desc-text-content">
                {{ paire.description }}
              </div>
              <div v-if="isDescLocked(index)" class="desc-link-badge" :style="{ 'color': colors[index].border }">
                ✓ Correct (Échiquier {{ getLinkedBoardIndex(index)! + 1 }})
              </div>
              <div v-else-if="getLinkedBoardIndex(index) !== null" class="desc-link-badge" :style="{ 'color': colors[index].border }">
                Associé à l'Échiquier {{ getLinkedBoardIndex(index)! + 1 }}
              </div>
            </ion-card-content>
          </ion-card>
        </div>
      </div>
    </div>

    <!-- Bouton de Validation -->
    <div v-if="toutesLiaisonsFaites" class="validation-container">
      <ion-button
        expand="block"
        color="success"
        class="validate-btn"
        @click="validerAssociations"
      >
        Valider mes choix
      </ion-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import {
  IonCard,
  IonCardContent,
  IonButton,
  toastController
} from '@ionic/vue';
import EgChessboard from 'eg-chessboard/vue';

interface Paire {
  fen: string;
  couleur_joueur: 'white' | 'black';
  description: string;
}

interface EchiquierMelange {
  fen: string;
  couleur_joueur: 'white' | 'black';
  originalIndex: number;
}

const props = defineProps<{
  paires: Paire[];
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

// Palette de couleurs premium pour différencier les descriptions
const colors = [
  { name: 'blue', border: '#3880ff', bg: 'rgba(56, 128, 255, 0.04)', text: '#3880ff', badgeBg: 'rgba(56, 128, 255, 0.1)' },
  { name: 'purple', border: '#af47ff', bg: 'rgba(175, 71, 255, 0.04)', text: '#af47ff', badgeBg: 'rgba(175, 71, 255, 0.1)' },
  { name: 'orange', border: '#e67e22', bg: 'rgba(230, 126, 34, 0.04)', text: '#e67e22', badgeBg: 'rgba(230, 126, 34, 0.1)' },
  { name: 'pink', border: '#ff375f', bg: 'rgba(255, 55, 95, 0.04)', text: '#ff375f', badgeBg: 'rgba(255, 55, 95, 0.1)' }
];

const echiquiersMelanges = ref<EchiquierMelange[]>([]);
const selectionEchiquier = ref<number | null>(null);
const liaisons = ref<Record<number, number>>({});
const boardsCorrects = ref<number[]>([]);

const getBoardStyle = (boardIdx: number) => {
  const descIdx = liaisons.value[boardIdx];
  if (descIdx !== undefined) {
    const color = colors[descIdx];
    return {
      'border-color': color.border,
      'background-color': color.bg
    };
  }
  if (selectionEchiquier.value === boardIdx) {
    return {
      'border-color': 'var(--ion-color-dark, #222428)',
      'box-shadow': '0 0 0 3px rgba(34, 36, 40, 0.25), 0 8px 20px rgba(34, 36, 40, 0.12)',
      'transform': 'scale(1.02)'
    };
  }
  return {};
};

// Initialise et mélange les échiquiers
const initEchiquiers = () => {
  if (!props.paires || props.paires.length === 0) return;

  const items = props.paires.map((p, idx) => ({
    fen: p.fen,
    couleur_joueur: p.couleur_joueur,
    originalIndex: idx
  }));

  // Fisher-Yates shuffle
  for (let i = items.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [items[i], items[j]] = [items[j], items[i]];
  }

  echiquiersMelanges.value = items;
  liaisons.value = {};
  selectionEchiquier.value = null;
  boardsCorrects.value = [];
};

onMounted(() => {
  initEchiquiers();
});

watch(() => props.paires, () => {
  initEchiquiers();
}, { deep: true });

// Aide pour obtenir les lettres A, B, C, D
const getLetter = (index: number): string => {
  return String.fromCharCode(65 + index); // 65 est 'A'
};

const selectBoard = (index: number) => {
  if (boardsCorrects.value.includes(index)) return;
  selectionEchiquier.value = selectionEchiquier.value === index ? null : index;
};

const linkDescription = (descIdx: number) => {
  if (selectionEchiquier.value === null || isDescLocked(descIdx)) {
    return;
  }

  const boardIdx = selectionEchiquier.value;

  // Supprime l'ancienne liaison si cette description était déjà liée à un autre échiquier
  for (const key in liaisons.value) {
    if (liaisons.value[Number(key)] === descIdx) {
      delete liaisons.value[Number(key)];
    }
  }

  // Crée la nouvelle liaison
  liaisons.value[boardIdx] = descIdx;

  // Sélection automatique du prochain échiquier non lié et non validé
  const nextUnlinked = [0, 1, 2, 3].find(
    (idx) => idx !== boardIdx && liaisons.value[idx] === undefined && !boardsCorrects.value.includes(idx)
  );
  selectionEchiquier.value = nextUnlinked !== undefined ? nextUnlinked : null;
};

const getLinkedBoardIndex = (descIdx: number): number | null => {
  const boardKey = Object.keys(liaisons.value).find(
    (key) => liaisons.value[Number(key)] === descIdx
  );
  return boardKey !== undefined ? Number(boardKey) : null;
};

const isDescLinked = (descIdx: number): boolean => {
  return getLinkedBoardIndex(descIdx) !== null;
};

const isDescLocked = (descIdx: number): boolean => {
  const boardIdx = getLinkedBoardIndex(descIdx);
  return boardIdx !== null && boardsCorrects.value.includes(boardIdx);
};

const toutesLiaisonsFaites = computed(() => {
  return Object.keys(liaisons.value).length === props.paires.length && props.paires.length > 0;
});

const validerAssociations = async () => {
  let errorsCount = 0;
  const newlyCorrect: number[] = [];

  for (let i = 0; i < props.paires.length; i++) {
    if (boardsCorrects.value.includes(i)) continue;

    const linkedDescIdx = liaisons.value[i];
    if (
      linkedDescIdx === undefined ||
      echiquiersMelanges.value[i].originalIndex !== linkedDescIdx
    ) {
      errorsCount++;
      // Supprime l'association erronée pour permettre de la corriger
      delete liaisons.value[i];
    } else {
      newlyCorrect.push(i);
    }
  }

  boardsCorrects.value.push(...newlyCorrect);

  if (errorsCount > 0) {
    const toast = await toastController.create({
      message: 'Certaines associations sont incorrectes, réessayez !',
      duration: 2500,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
    // Reprendre la sélection sur le premier échiquier vide et non correct
    const firstEmpty = [0, 1, 2, 3].find(
      (idx) => liaisons.value[idx] === undefined && !boardsCorrects.value.includes(idx)
    );
    if (firstEmpty !== undefined) {
      selectionEchiquier.value = firstEmpty;
    }
  } else {
    const toast = await toastController.create({
      message: 'Parfait !',
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();
    emit('success');
  }
};
</script>

<style scoped>
.matching-container {
  width: 100%;
  padding: 8px;
}

.matching-rows-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
}

.matching-row {
  display: flex;
  align-items: stretch;
  gap: 16px;
  width: 100%;
}

.board-column,
.desc-column {
  flex: 1;
  width: 50%;
  display: flex;
  flex-direction: column;
}

.board-wrapper-card {
  background: var(--ion-color-step-50, #fcfcfc);
  border: 2px solid var(--ion-color-step-200, #e0e0e0);
  border-radius: 12px;
  padding: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  position: relative;
  overflow: hidden;
  height: 100%;
}

.board-wrapper-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}

.board-wrapper-card.selected {
  border-color: var(--ion-color-dark, #222428);
  box-shadow: 0 0 0 3px rgba(34, 36, 40, 0.25), 0 8px 20px rgba(34, 36, 40, 0.12);
  transform: scale(1.02);
}

.board-wrapper-card.linked {
  border-color: var(--ion-color-success, #2dd36f);
}

.board-wrapper-card.correct-locked,
.description-card.correct-locked {
  opacity: 0.85;
  cursor: not-allowed;
}

.correct-badge {
  background-color: var(--ion-color-success, #2dd36f) !important;
  color: white !important;
}

.correct-text {
  color: var(--ion-color-success-shade, #24a858) !important;
}

.board-header-bar {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
  padding: 0 4px;
}

.board-num {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--ion-color-step-600, #666);
}

.link-indicator {
  font-size: 0.75rem;
  font-weight: 600;
  background-color: var(--ion-color-success-tiny, rgba(45, 211, 111, 0.15));
  color: var(--ion-color-success-shade, #24a858);
  padding: 2px 6px;
  border-radius: 4px;
}

.board-mini-container {
  width: 100%;
  aspect-ratio: 1;
  pointer-events: none;
  border-radius: 6px;
  overflow: hidden;
}

/* Liste des Descriptions */
.description-card {
  margin: 0;
  border: 2px solid var(--ion-color-step-200, #e0e0e0);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.25s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  background: var(--ion-color-step-50, #fcfcfc);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.description-card.active-for-selection:hover {
  transform: translateX(4px);
  border-color: var(--ion-color-primary-tint, #50c8ff);
}

.description-card.linked {
  border-color: var(--ion-color-success, #2dd36f);
  background: var(--ion-color-success-tiny, rgba(45, 211, 111, 0.03));
}

.desc-card-content {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  flex-grow: 1;
  gap: 8px;
  position: relative;
  padding: 16px;
}

.desc-letter-badge {
  align-self: flex-start;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  background-color: var(--ion-color-step-200, #e0e0e0);
  color: var(--ion-color-step-800, #333);
  padding: 2px 8px;
  border-radius: 12px;
  letter-spacing: 0.5px;
}

.description-card.linked .desc-letter-badge {
  background-color: var(--ion-color-success, #2dd36f);
  color: white;
}

.desc-text-content {
  font-size: 0.95rem;
  line-height: 1.4;
  color: var(--ion-color-step-800, #333);
  font-weight: 500;
  flex-grow: 1;
  display: flex;
  align-items: center;
  margin: 4px 0;
}

.desc-link-badge {
  align-self: flex-end;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--ion-color-success-shade, #24a858);
  margin-top: 4px;
}

/* Validation Container */
.validation-container {
  display: flex;
  justify-content: center;
  margin-top: 24px;
  animation: fadeIn 0.4s ease;
}

.validate-btn {
  --border-radius: 10px;
  font-weight: 600;
  font-size: 1.05rem;
  height: 48px;
  width: 100%;
  max-width: 320px;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsiveness adjustments */
@media (max-width: 768px) {
  .matching-row {
    gap: 8px;
  }
  .desc-card-content {
    padding: 10px;
  }
  .desc-text-content {
    font-size: 0.85rem;
  }
}
</style>
