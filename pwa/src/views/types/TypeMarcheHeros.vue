<template>
  <div class="exercice-type-marche-heros">
    <!-- Étape 1 : Reconstitution -->
    <div v-if="etapeJeu === 'reconstitution'" class="reconstitution-container">
      <h2 class="section-title">Reconstituez la marche du héros</h2>
      <p class="section-subtitle">Ordonnez les positions de chaque série chronologiquement (de gauche à droite).</p>

      <!-- Zone Grille -->
      <div class="grid-container">
        <div 
          v-for="(rowItems, rowIndex) in grillePositions" 
          :key="rowIndex" 
          class="grid-row"
        >
          <div class="row-label">Série {{ rowIndex + 1 }}</div>
          <div class="row-slots">
            <div 
              v-for="(slot, colIndex) in rowItems" 
              :key="colIndex"
              class="grid-slot"
              :class="{ 'is-empty': !slot, 'is-selected': selectionGrille?.row === rowIndex && selectionGrille?.col === colIndex }"
              @mousedown="handlePressStart($event, () => selectGrilleSlot(rowIndex, colIndex), slot)"
              @touchstart="handlePressStart($event, () => selectGrilleSlot(rowIndex, colIndex), slot)"
              @touchmove="handlePressMove"
              @mouseup="handlePressEnd"
              @touchend="handlePressEnd"
              @mouseleave="handlePressEnd"
            >
              <template v-if="slot">
                <DiagramViewer 
                  :fen="slot.fen" 
                  :orientation="slot.orientation"
                  class="board-miniature"
                />
              </template>
              <div v-else class="empty-placeholder">
                <span class="placeholder-icon">+</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Zone Banque -->
      <div class="bank-container">
        <h3 class="bank-title">Banque de positions (Appui long pour zoomer)</h3>
        <div class="bank-scroll">
          <div 
            v-for="(item, index) in diagrammesBank" 
            :key="item.id"
            class="bank-item-wrapper"
            :class="{ 'is-selected': selectionBank === index }"
            @mousedown="handlePressStart($event, () => selectBankItem(index), item)"
            @touchstart="handlePressStart($event, () => selectBankItem(index), item)"
            @touchmove="handlePressMove"
            @mouseup="handlePressEnd"
            @touchend="handlePressEnd"
            @mouseleave="handlePressEnd"
          >
            <div class="miniature-wrapper">
              <DiagramViewer 
                :fen="item.fen" 
                :orientation="item.orientation"
                class="board-miniature"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Bouton de validation -->
      <div class="action-bar">
        <ion-button 
          expand="block" 
          @click="validerReconstitution" 
          :disabled="diagrammesBank.length > 0"
        >
          Valider l'ordre
        </ion-button>
      </div>
    </div>

    <!-- Étape 2 : Résolution -->
    <div v-else class="resolution-container">
      <h2 class="section-title">Trouvez le coup final !</h2>
      <p class="section-subtitle">
        Série {{ serieCouranteIndex + 1 }} sur {{ solutionsResolution.length }} — Trouvez le coup décisif
      </p>

      <PuzzleViewer
        v-if="solutionCourante"
        :key="serieCouranteIndex"
        :fen="solutionCourante.fenDepart"
        :couleur-joueur="solutionCourante.orientation"
        :solution="solutionCourante.solution"
        :shapes="solutionCourante.shapes"
        :lastMoveHighlight="(solutionCourante as any).lastMoveHighlight"
        @success="handlePuzzleSuccess"
      />
    </div>

    <!-- Modal de Zoom -->
    <div v-if="zoomedDiagram" class="zoom-overlay" @click="closeZoom">
      <div class="zoom-modal" @click.stop>
        <div class="zoom-header">
          <span>Aperçu de la position</span>
          <button class="close-btn" @click="closeZoom">&times;</button>
        </div>
        <div class="zoom-board-container">
          <DiagramViewer 
            :fen="zoomedDiagram.fen" 
            :orientation="zoomedDiagram.orientation"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Chess } from 'chess.js';
import { toastController, IonButton } from '@ionic/vue';
import DiagramViewer from '@/components/shared/DiagramViewer.vue';
import PuzzleViewer from '@/components/shared/PuzzleViewer.vue';

interface Serie {
  pgn_data: string;
  couleur_joueur: 'white' | 'black';
  shapes?: any[];
}

interface ConfigMarcheHeros {
  mode: '3x5' | '5x3';
  series: Serie[];
}

const props = defineProps<{
  config: ConfigMarcheHeros;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

// Tâche 2 : État réactif (State)
const etapeJeu = ref<'reconstitution' | 'resolution'>('reconstitution');

interface Diagramme {
  id: string;
  fen: string;
  orientation: 'white' | 'black';
  serieIndex: number;
  ordre: number;
}

const diagrammesBank = ref<Diagramme[]>([]);
const grillePositions = ref<(Diagramme | null)[][]>([]);

interface Solution {
  fenDepart: string;
  orientation: 'white' | 'black';
  solution: string[];
  shapes: any[];
}

const solutionsResolution = ref<Solution[]>([]);
let solutionsOriginalBank: Solution[] = [];

const selectionBank = ref<number | null>(null);
const selectionGrille = ref<{ row: number; col: number } | null>(null);

// Tâche 1 : Logique de Résolution
const serieCouranteIndex = ref(0);

const solutionCourante = computed(() => {
  return solutionsResolution.value[serieCouranteIndex.value] || null;
});

const handlePuzzleSuccess = () => {
  if (serieCouranteIndex.value === solutionsResolution.value.length - 1) {
    emit('success');
  } else {
    serieCouranteIndex.value++;
  }
};

// Logique d'Appui Long (Long Press) et Zoom
const zoomedDiagram = ref<Diagramme | null>(null);
let pressTimer: any = null;
let isLongPress = false;
let preventDefaultClick = false;
let activeTapCallback: (() => void) | null = null;
let touchStartX = 0;
let touchStartY = 0;

const handlePressStart = (event: Event, callbackTap: () => void, diagram: Diagramme | null) => {
  // Empêche la simulation des clics souris sur mobile pour éviter les double taps parasites
  if (event.type === 'touchstart') {
    event.preventDefault();
  }

  isLongPress = false;
  preventDefaultClick = false;
  activeTapCallback = callbackTap;

  if (event instanceof TouchEvent && event.touches.length > 0) {
    touchStartX = event.touches[0].clientX;
    touchStartY = event.touches[0].clientY;
  }

  // Si pas de diagramme sur la case (par ex. slot vide dans la grille), on effectue le tap immédiatement
  if (!diagram) {
    callbackTap();
    activeTapCallback = null;
    return;
  }

  // Lance un timer de 500ms pour l'appui long
  pressTimer = setTimeout(() => {
    isLongPress = true;
    zoomedDiagram.value = diagram;
    preventDefaultClick = true;
  }, 500);
};

const handlePressMove = (event: TouchEvent) => {
  // Si l'utilisateur bouge son doigt de plus de 10px, on considère qu'il souhaite faire défiler (scroll) la zone.
  // Dans ce cas, on annule immédiatement le timer d'appui long pour laisser le scroll natif se faire.
  if (pressTimer && event.touches.length > 0) {
    const diffX = Math.abs(event.touches[0].clientX - touchStartX);
    const diffY = Math.abs(event.touches[0].clientY - touchStartY);
    if (diffX > 10 || diffY > 10) {
      clearTimeout(pressTimer);
      pressTimer = null;
      activeTapCallback = null;
    }
  }
};

const handlePressEnd = (event: Event) => {
  if (event.type === 'touchend') {
    event.preventDefault();
  }

  if (pressTimer) {
    clearTimeout(pressTimer);
    pressTimer = null;
  }

  // Si ce n'est pas un appui long (c'est un tap normal), on exécute l'action de clic associée
  if (!isLongPress && !preventDefaultClick && activeTapCallback) {
    activeTapCallback();
  }
  activeTapCallback = null;
  
  // Important : réinitialiser les flags après la fin du contact pour autoriser les taps suivants
  isLongPress = false;
  preventDefaultClick = false;
};

const closeZoom = () => {
  zoomedDiagram.value = null;
};

// Tâche 1 : Logique d'interaction Tap & Tap
const selectBankItem = (index: number) => {
  if (preventDefaultClick || isLongPress) return;
  selectionGrille.value = null; // Désélectionne la grille si on choisit la banque
  selectionBank.value = index;
};

const selectGrilleSlot = (row: number, col: number) => {
  if (preventDefaultClick || isLongPress) return;
  const currentSlotContent = grillePositions.value[row][col];

  if (!currentSlotContent) {
    // Si vide et banque sélectionnée -> déplace le diagramme vers la grille
    if (selectionBank.value !== null) {
      const itemToPlace = diagrammesBank.value[selectionBank.value];
      grillePositions.value[row][col] = itemToPlace;
      
      // Retire de la banque
      diagrammesBank.value.splice(selectionBank.value, 1);
      
      // Reset la sélection
      selectionBank.value = null;
    }
  } else {
    // Si plein -> renvoie le diagramme dans la banque
    diagrammesBank.value.push(currentSlotContent);
    grillePositions.value[row][col] = null;
    
    // Reset la sélection
    selectionBank.value = null;
    selectionGrille.value = null;
  }
};

const validerReconstitution = async () => {
  let hasError = false;
  const nbRows = grillePositions.value.length;
  const nbCols = grillePositions.value[0]?.length || 0;

  // Tableau pour stocker l'index réel de la série pour chaque ligne de la grille utilisateur
  const mappingLigneVersSerie = new Array(nbRows).fill(-1);

  for (let r = 0; r < nbRows; r++) {
    // Identifier la série de la ligne à partir de la première case remplie
    const premierSlot = grillePositions.value[r][0];
    if (!premierSlot) {
      hasError = true;
      continue;
    }

    const serieIndexLigne = premierSlot.serieIndex;
    mappingLigneVersSerie[r] = serieIndexLigne;

    for (let c = 0; c < nbCols; c++) {
      const slot = grillePositions.value[r][c];
      if (!slot) {
        hasError = true;
        continue;
      }

      // 1. Tous les diagrammes de cette ligne doivent appartenir à la même série (serieIndexLigne)
      // 2. L'ordre (colIndex) doit être chronologiquement exact (slot.ordre === c)
      if (slot.serieIndex !== serieIndexLigne || slot.ordre !== c) {
        hasError = true;
        // Remet le diagramme incorrect dans la banque de positions
        diagrammesBank.value.push(slot);
        grillePositions.value[r][c] = null;
      }
    }
  }

  // Vérifier qu'on n'a pas mis deux fois la même série sur deux lignes différentes
  if (!hasError) {
    const seriesUniques = new Set(mappingLigneVersSerie);
    if (seriesUniques.size !== nbRows) {
      hasError = true;
      // On vide tout pour sécurité et retour banque
      for (let r = 0; r < nbRows; r++) {
        for (let c = 0; c < nbCols; c++) {
          const slot = grillePositions.value[r][c];
          if (slot) {
            diagrammesBank.value.push(slot);
            grillePositions.value[r][c] = null;
          }
        }
      }
    }
  }

  if (hasError) {
    const toast = await toastController.create({
      message: 'Certaines positions sont incorrectes, manquantes ou en doublon. Réessayez !',
      duration: 3000,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  } else {
    // Si la reconstitution est valide, on ordonne solutionsResolution pour que la résolution finale
    // se fasse séquentiellement dans l'ordre choisi/placé par l'élève sur la grille.
    // Pour cela, on utilise le tableau initialement créé solutionsOriginalBank.
    const solutionsOrdonnees = mappingLigneVersSerie.map(indexSerie => solutionsOriginalBank[indexSerie]);
    solutionsResolution.value = solutionsOrdonnees;

    etapeJeu.value = 'resolution';
  }
};

// Tâche 3 : Fonction d'initialisation (initExercice)
const initExercice = () => {
  if (!props.config || !props.config.mode || !props.config.series) return;

  const mode = props.config.mode;
  let nbSeries = 3;
  let nbDiagrammesPerSerie = 5;

  if (mode === '5x3') {
    nbSeries = 5;
    nbDiagrammesPerSerie = 3;
  }

  // Initialise grillePositions.value avec des tableaux de null
  grillePositions.value = Array.from({ length: nbSeries }, () =>
    Array(nbDiagrammesPerSerie).fill(null)
  );

  const bank: Diagramme[] = [];
  const solutions: Solution[] = [];

  // Boucle sur config.series
  for (let serieIndex = 0; serieIndex < Math.min(props.config.series.length, nbSeries); serieIndex++) {
    const serie = props.config.series[serieIndex];
    
    // Instancie et charge le PGN pour lire l'historique
    const mainChess = new Chess();
    mainChess.loadPgn(serie.pgn_data);
    const history = mainChess.history();

    // Déterminer la FEN initiale du PGN
    const headers = mainChess.header();
    const fenInitiale = headers.FEN || 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    // Instancier un échiquier temporaire pour analyser à qui est le tour à la FEN de départ
    const tempChess = new Chess(fenInitiale);
    const tourInitial = tempChess.turn(); // 'w' ou 'b'
    // La clé dans le JSON transmis est 'orientation' et non 'couleur_joueur'
    const orientation = (serie as any).orientation || serie.couleur_joueur || 'white'; 

    // Le joueur doit toujours observer l'échiquier au moment où c'est à lui de jouer.
    // Si c'est au tour du joueur à la FEN initiale, le premier diagramme (ordre 0) est la FEN de départ.
    // Sinon (si c'est le tour de l'adversaire), il faut jouer 1 demi-coup de l'adversaire d'abord pour que ce soit au tour du joueur.
    let startIndex = 0;
    const replayChess = new Chess(fenInitiale);

    const joueurDoitJouer = (orientation === 'white' && tourInitial === 'w') || (orientation === 'black' && tourInitial === 'b');
    if (!joueurDoitJouer) {
      // On doit avancer de 1 demi-coup de l'adversaire si possible
      if (history.length > 0) {
        replayChess.move(history[0]);
        startIndex = 1;
      }
    }

    // Le premier diagramme (ordre 0)
    bank.push({
      id: `${serieIndex}-0`,
      fen: replayChess.fen(),
      orientation,
      serieIndex,
      ordre: 0,
    });

    // Nous devons extraire (nbDiagrammesPerSerie - 1) positions intermédiaires de la marche
    // en avançant de 2 demi-coups (1 coup complet) à chaque fois, pour que ce soit toujours au tour du joueur.
    // Le dernier coup de l'historique complet (history.length - 1) est le coup tactique final à deviner.
    // Donc la boucle s'arrête avant le tout dernier demi-coup.
    const maxIndexPourReconstitution = history.length - 1;

    let ordre = 1;
    let i = startIndex;
    while (ordre < nbDiagrammesPerSerie && i < maxIndexPourReconstitution) {
      // Avancer d'un coup complet (le coup du joueur + la réponse de l'adversaire)
      if (i < maxIndexPourReconstitution) {
        replayChess.move(history[i]);
      }
      if (i + 1 < maxIndexPourReconstitution) {
        replayChess.move(history[i + 1]);
      }
      i += 2;

      bank.push({
        id: `${serieIndex}-${ordre}`,
        fen: replayChess.fen(),
        orientation,
        serieIndex,
        ordre,
      });
      ordre++;
    }

    // IMPORTANT : D'après les spécifications, la position de départ pour la tactique finale (la résolution)
    // est EXACTEMENT la FEN de la dernière séquence (ordre N-1, soit nbDiagrammesPerSerie-1) calculée.
    // Le tout premier coup (demi-coup) qui suit cette position dans l'historique est le coup à trouver.
    // C'est-à-dire le coup à l'index `i` de l'historique de la partie.
    const indexCoupATrouver = i;
    const dernierCoup = history[indexCoupATrouver] || (history.length > 0 ? history[history.length - 1] : '');

    // Pour mettre en évidence le dernier demi-coup adverse (le coup qui a mené à cette position de départ) :
    // C'est le coup juste avant indexCoupATrouver, soit l'index `indexCoupATrouver - 1`.
    let lastMoveHighlight: string[] | undefined = undefined;
    if (indexCoupATrouver - 1 >= 0 && indexCoupATrouver - 1 < history.length) {
      const helperChess = new Chess();
      if (headers.FEN) {
        helperChess.load(headers.FEN);
      }
      for (let k = 0; k < indexCoupATrouver - 1; k++) {
        helperChess.move(history[k]);
      }
      try {
        const mv = helperChess.move(history[indexCoupATrouver - 1]);
        lastMoveHighlight = [mv.from, mv.to];
      } catch (err) {
        console.warn('Erreur lors du calcul de la case du dernier coup adverse:', err);
      }
    }

    console.log(`[DEBUG TypeMarcheHeros] SérieIndex ${serieIndex + 1}:`);
    console.log(` - FEN de départ du puzzle (Séquence ${ordre - 1}): ${replayChess.fen()}`);
    console.log(` - Index du coup dans l'historique: ${indexCoupATrouver} sur ${history.length}`);
    console.log(` - Coup attendu (solution): ${dernierCoup}`);
    if (lastMoveHighlight) {
      console.log(` - Surlignage du dernier coup adverse: ${lastMoveHighlight.join(' -> ')}`);
    }

    solutions.push({
      fenDepart: replayChess.fen(),
      orientation,
      solution: [dernierCoup],
      shapes: serie.shapes || [],
      ...(lastMoveHighlight ? { lastMoveHighlight } : {})
    } as any);
  }

  // Mélange (shuffle) la banque de diagrammes finale
  for (let i = bank.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [bank[i], bank[j]] = [bank[j], bank[i]];
  }

  diagrammesBank.value = bank;
  solutionsResolution.value = solutions;
  solutionsOriginalBank = [...solutions];
};

onMounted(() => {
  initExercice();
});
</script>

<style scoped>
.exercice-type-marche-heros {
  width: 100%;
  padding: 16px;
  box-sizing: border-box;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 8px;
  color: var(--ion-color-dark);
}

.section-subtitle {
  font-size: 0.95rem;
  color: var(--ion-color-step-600);
  margin-bottom: 24px;
}

/* Grille */
.grid-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-bottom: 32px;
}

.grid-row {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.row-label {
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--ion-color-step-700);
}

.row-slots {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  padding-bottom: 8px;
}

.grid-slot {
  width: 100px;
  height: 100px;
  flex-shrink: 0;
  border: 2px solid var(--ion-color-step-200);
  border-radius: 8px;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: var(--ion-color-step-50);
}

.grid-slot.is-empty {
  border-style: dashed;
}

.grid-slot.is-selected {
  border-color: var(--ion-color-primary);
}

.empty-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--ion-color-step-300);
  font-size: 1.8rem;
}

/* Banque */
.bank-container {
  margin-bottom: 32px;
}

.bank-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 12px;
}

.bank-scroll {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  padding: 8px 0;
}

.bank-item-wrapper {
  width: 100px;
  height: 100px;
  flex-shrink: 0;
  border: 2px solid transparent;
  border-radius: 8px;
  cursor: pointer;
  transition: transform 0.2s, border-color 0.2s;
}

.miniature-wrapper {
  width: 100px;
  height: 100px;
  overflow: hidden;
  border-radius: 6px;
}

.bank-item-wrapper.is-selected {
  border-color: var(--ion-color-primary);
  transform: scale(1.05);
}

/* Miniatures Échiquier */
.board-miniature {
  transform: scale(1);
  width: 100%;
  height: 100%;
  margin: 0;
}

.action-bar {
  margin-top: 24px;
}

/* Modal de Zoom Premium */
.zoom-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.zoom-modal {
  background: var(--ion-background-color, #ffffff);
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
  width: 90%;
  max-width: 400px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.zoom-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: var(--ion-color-step-100, #f4f5f8);
  font-weight: 600;
  border-bottom: 1px solid var(--ion-color-step-200);
}

.close-btn {
  background: transparent;
  border: none;
  font-size: 1.8rem;
  line-height: 1;
  color: var(--ion-color-step-600);
  cursor: pointer;
  padding: 0 4px;
}

.zoom-board-container {
  width: 100%;
  aspect-ratio: 1;
  padding: 12px;
  box-sizing: border-box;
}
</style>
