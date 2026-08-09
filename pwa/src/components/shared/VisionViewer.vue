<template>
  <div class="vision-viewer-wrapper">
    <!-- Header: Consigne & Progression -->
    <ion-card class="consigne-card ion-no-margin ion-margin-bottom">
      <ion-card-header>
        <div class="header-top-row">
          <ion-card-title class="exercise-title">Vision'checs</ion-card-title>
          <ion-badge color="primary" class="diagram-badge">
            Diagramme {{ indexCourant + 1 }} / {{ diagrammesListe.length }}
          </ion-badge>
        </div>
      </ion-card-header>
      <ion-card-content>
        <p class="consigne-text">
          {{ consigneTexte }}
        </p>
      </ion-card-content>
    </ion-card>

    <!-- Main Dual Panel Layout -->
    <div class="vision-main-layout">
      <!-- Description Panel: Pièces & Positions -->
      <div class="description-panel">
        <div class="panel-header">
          <span class="panel-title">Pièces sur l'échiquier</span>
        </div>

        <div
          class="pieces-container"
          :class="{ 'two-columns': pieceColumns.isTwoColumns }"
        >
          <!-- Colonne 1 -->
          <div class="pieces-column">
            <div
              v-for="(item, idx) in pieceColumns.col1"
              :key="`c1-${idx}-${item.square}`"
              class="piece-row"
            >
              <cg-board class="piece-icon-box">
                <div :class="['piece', item.role, item.color]"></div>
              </cg-board>
              <span class="piece-square">{{ item.square }}</span>
            </div>
          </div>

          <!-- Colonne 2 (si > 4 pièces) -->
          <div
            v-if="pieceColumns.isTwoColumns"
            class="pieces-column"
          >
            <div
              v-for="(item, idx) in pieceColumns.col2"
              :key="`c2-${idx}-${item.square}`"
              class="piece-row"
            >
              <cg-board class="piece-icon-box">
                <div :class="['piece', item.role, item.color]"></div>
              </cg-board>
              <span class="piece-square">{{ item.square }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Chessboard Panel -->
      <div class="board-panel">
        <div class="board-container">
          <eg-chessboard
            :diagram="{
              fen: fenActuelle,
              shapes: shapesActuelles
            }"
            :playerColor="couleurJoueur"
            :preserve-shapes-on-position-change="true"
            :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
            @board-created="onBoardCreated"
            @square-click="gererClicCase"
          />
        </div>
      </div>
    </div>

    <!-- Feedback Message Banner -->
    <div v-if="feedback" class="feedback-banner ion-margin-top">
      <ion-card :color="feedback.type" class="ion-no-margin">
        <ion-card-content class="ion-text-center feedback-text">
          {{ feedback.message }}
        </ion-card-content>
      </ion-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonBadge
} from '@ionic/vue';
import { default as EgChessboard } from 'eg-chessboard/vue';
import 'eg-chessboard/style.css';
import type { BoardCore, DrawShape, Key } from 'eg-chessboard';
import { parseFenPieces, getPieceColumns, getActiveColorFromFen } from '@/utils/fenUtils';

export interface DiagrammeConfig {
  fen: string;
  couleur_joueur?: 'white' | 'black';
  shapes?: DrawShape[];
}

const props = defineProps<{
  consigne?: string;
  diagrammes?: DiagrammeConfig[];
  // Props de rétrocompatibilité pour ancien format
  fenDepart?: string;
  legacyCouleurJoueur?: 'white' | 'black';
  caseDepart?: string;
  caseArrivee?: string;
  solutionSan?: string;
  legacyConfig?: {
    description?: string;
    fen_depart?: string;
    couleur_joueur?: 'white' | 'black';
    case_depart?: string;
    case_arrivee?: string;
    solution_san?: string;
  };
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const indexCourant = ref<number>(0);
const etapeJeu = ref<'reflexion' | 'revelation'>('reflexion');
const caseSelectionnee = ref<string | null>(null);
const shapesActuelles = ref<DrawShape[]>([]);
const boardApi = ref<BoardCore | null>(null);
const feedback = ref<{ message: string; type: 'success' | 'danger' } | null>(null);

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
};

// Normalisation de la liste des 4 diagrammes
const diagrammesListe = computed<DiagrammeConfig[]>(() => {
  if (props.diagrammes && Array.isArray(props.diagrammes) && props.diagrammes.length > 0) {
    return props.diagrammes;
  }
  // Rétrocompatibilité legacy
  const fenLegacy = props.fenDepart || props.legacyConfig?.fen_depart || 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
  const colorLegacy = props.legacyCouleurJoueur || props.legacyConfig?.couleur_joueur || getActiveColorFromFen(fenLegacy);
  const caseDep = props.caseDepart || props.legacyConfig?.case_depart || '';
  const caseArr = props.caseArrivee || props.legacyConfig?.case_arrivee || '';

  const shapesLegacy: DrawShape[] = [];
  if (caseDep && caseArr) {
    shapesLegacy.push({ orig: caseDep as Key, dest: caseArr as Key, brush: 'blue' });
  }

  return [
    {
      fen: fenLegacy,
      couleur_joueur: colorLegacy,
      shapes: shapesLegacy,
    }
  ];
});

const consigneTexte = computed<string>(() => {
  return props.consigne || props.legacyConfig?.description || 'Observez les 4 diagrammes ci-dessous.';
});

const diagrammeCourant = computed<DiagrammeConfig>(() => {
  return diagrammesListe.value[indexCourant.value] || diagrammesListe.value[0];
});

const couleurJoueur = computed<'white' | 'black'>(() => {
  return getActiveColorFromFen(diagrammeCourant.value.fen);
});

const solutionMove = computed<{ orig: string; dest: string }>(() => {
  const shapes = diagrammeCourant.value.shapes || [];
  const blueArrow = shapes.find(
    (s) =>
      ((s as any).brush === 'blue' || (s as any).color === 'blue') &&
      s.orig &&
      s.dest &&
      s.orig !== s.dest
  );

  if (blueArrow && blueArrow.orig && blueArrow.dest) {
    return {
      orig: blueArrow.orig.toLowerCase(),
      dest: blueArrow.dest.toLowerCase(),
    };
  }

  // Fallback legacy
  const caseDep = (props.caseDepart || props.legacyConfig?.case_depart || '').toLowerCase();
  const caseArr = (props.caseArrivee || props.legacyConfig?.case_arrivee || '').toLowerCase();
  return { orig: caseDep, dest: caseArr };
});

const piecesExtraites = computed(() => {
  return parseFenPieces(diagrammeCourant.value.fen);
});

const pieceColumns = computed(() => {
  return getPieceColumns(piecesExtraites.value);
});

const fenActuelle = computed<string>(() => {
  if (etapeJeu.value === 'revelation') {
    return diagrammeCourant.value.fen;
  }
  return couleurJoueur.value === 'black'
    ? '8/8/8/8/8/8/8/8 b - - 0 1'
    : '8/8/8/8/8/8/8/8 w - - 0 1';
});

// Réinitialisation lors du changement de diagramme
watch(indexCourant, () => {
  etapeJeu.value = 'reflexion';
  caseSelectionnee.value = null;
  shapesActuelles.value = [];
  feedback.value = null;
});

const gererClicCase = async (square: string) => {
  if (etapeJeu.value !== 'reflexion') {
    return;
  }

  const squareLower = square.toLowerCase();

  // 1er clic (case de départ)
  if (!caseSelectionnee.value) {
    caseSelectionnee.value = squareLower;
    shapesActuelles.value = [{ orig: squareLower as Key, brush: 'blue' }];
    return;
  }

  // 2ème clic (case d'arrivée)
  const dep = caseSelectionnee.value;
  const arr = squareLower;

  if (dep === solutionMove.value.orig && arr === solutionMove.value.dest) {
    // Succès sur ce diagramme !
    etapeJeu.value = 'revelation';
    shapesActuelles.value = diagrammeCourant.value.shapes && diagrammeCourant.value.shapes.length > 0
      ? diagrammeCourant.value.shapes
      : [{ orig: dep as Key, dest: arr as Key, brush: 'blue' }];

    feedback.value = {
      type: 'success',
      message: 'Bravo ! Bon coup trouvé.'
    };

    // Animation du coup après 400ms en conservant les shapes
    setTimeout(() => {
      if (boardApi.value) {
        boardApi.value.move({ from: dep, to: arr });
      }
    }, 400);

    // Passage au diagramme suivant ou fin de l'exercice après 1800ms
    setTimeout(() => {
      feedback.value = null;
      if (indexCourant.value < diagrammesListe.value.length - 1) {
        indexCourant.value += 1;
      } else {
        emit('success');
      }
    }, 1800);
  } else {
    // Erreur !
    feedback.value = {
      type: 'danger',
      message: "Ce n'est pas le bon coup ! Réessayez."
    };
    caseSelectionnee.value = null;
    shapesActuelles.value = [];

    setTimeout(() => {
      if (feedback.value?.type === 'danger') {
        feedback.value = null;
      }
    }, 2000);
  }
};
</script>

<style scoped>
.vision-viewer-wrapper {
  width: 100%;
}

.consigne-card {
  border-radius: 8px;
}

.header-top-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.exercise-title {
  font-size: 1.25rem;
  font-weight: 700;
}

.diagram-badge {
  font-size: 0.9rem;
  padding: 6px 10px;
  border-radius: 12px;
}

.consigne-text {
  font-size: 1rem;
  line-height: 1.5;
  margin: 0;
  white-space: pre-line;
}

/* Dual Panel Layout */
.vision-main-layout {
  display: flex;
  gap: 16px;
  width: 100%;
}

@media (max-width: 768px) {
  .vision-main-layout {
    flex-direction: column;
  }
}

@media (min-width: 769px) {
  .vision-main-layout {
    flex-direction: row;
    align-items: flex-start;
  }
}

/* Description Panel */
.description-panel {
  border: 1px solid var(--ion-color-light-shade, #e0e0e0);
  border-radius: 8px;
  padding: 12px;
  background: var(--ion-card-background, #ffffff);
  box-sizing: border-box;
}

@media (min-width: 769px) {
  .description-panel {
    flex: 0 0 40%;
    max-width: 440px;
  }
}

@media (max-width: 768px) {
  .description-panel {
    width: 100%;
  }
}

.panel-header {
  margin-bottom: 10px;
  border-bottom: 1px solid var(--ion-color-light-shade, #eeeeee);
  padding-bottom: 6px;
}

.panel-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--ion-color-medium-shade, #666666);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.pieces-container {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pieces-container.two-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.pieces-column {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.piece-row {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 36px;
  padding: 2px 6px;
  border-radius: 4px;
  background: var(--ion-color-light-tint, #f9f9f9);
}

cg-board.piece-icon-box {
  width: 32px;
  height: 32px;
  position: relative;
  display: block;
  flex-shrink: 0;
}

cg-board.piece-icon-box .piece {
  position: absolute !important;
  width: 100% !important;
  height: 100% !important;
  top: 0 !important;
  left: 0 !important;
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
}

.piece-square {
  font-size: 1.15rem;
  font-weight: 700;
  font-family: monospace, Courier, monospace;
  color: var(--ion-color-dark, #222222);
}

/* Board Panel */
.board-panel {
  flex: 1;
  min-width: 0;
  width: 100%;
}

/* Échiquier strict flat design (AGENTS.md) */
.board-container {
  width: 100%;
  aspect-ratio: 1 / 1;
  max-width: 500px;
  margin: 0 auto;
  border-radius: 0;
  overflow: hidden;
  box-shadow: none;
}

.feedback-banner {
  border-radius: 8px;
}

.feedback-text {
  font-size: 1.05rem;
  font-weight: 600;
  padding: 12px;
}
</style>
