<!-- src/views/types/TypeMarcheHeros.vue -->
<template>
  <div class="exercice-type-marche-heros">
    
    <!-- PHASE 1a : Regroupement -->
    <div v-if="etapeJeu === 'regroupement'" class="phase-container">
      <h2 class="section-title">Étape 1 : Le tri par séries</h2>
      <p class="section-subtitle">Associez chaque position à la bonne partie (l'ordre n'a pas d'importance ici).</p>

      <!-- Zone Banque Commune -->
      <div class="bank-container">
        <h3 class="bank-title">Positions mélangées (Appui long pour zoomer ou clic droit)</h3>
        <div class="bank-scroll" v-if="diagrammesBank.length > 0">
          <div 
            v-for="(item, index) in diagrammesBank" 
            :key="'bank-' + item.id"
            class="bank-item-wrapper"
            :class="{ 'is-selected': selectionBank === index }"
            @click="selectBankItem(index)"
            @contextmenu="handleLongPress($event, item)"
            @touchstart="startTouchTimer($event, item)"
            @touchmove="moveTouch"
            @touchend="endTouchTimer"
            @touchcancel="endTouchTimer"
          >
            <div class="miniature-wrapper">
              <DiagramViewer :fen="item.fen" :orientation="item.orientation" class="board-miniature" />
            </div>
          </div>
        </div>
        <div v-else class="empty-bank-msg">
          Toutes les positions sont triées !
        </div>
      </div>

      <!-- Paniers de Séries -->
      <div class="groups-container">
        <div 
          v-for="(groupe, gIndex) in groupesSeries" 
          :key="'groupe-' + gIndex" 
          class="group-basket"
          :class="{ 'highlight-target': selectionBank !== null }"
          @click="addToGroupe(gIndex)"
        >
          <div class="group-header">Série {{ gIndex + 1 }} ({{ groupe.length }}/{{ nbDiagrammesPerSerie }})</div>
          <div class="group-content">
            <div 
              v-for="(item, itemIndex) in groupe" 
              :key="'groupe-item-' + item.id"
              class="bank-item-wrapper in-group"
              @click.stop="removeFromGroupe(gIndex, itemIndex)"
            >
              <div class="miniature-wrapper">
                <DiagramViewer :fen="item.fen" :orientation="item.orientation" class="board-miniature" />
              </div>
              <div class="remove-badge">×</div>
            </div>
            <div v-if="groupe.length === 0" class="empty-group-text">
              Touchez une position en haut, puis touchez ici pour l'ajouter.
            </div>
          </div>
        </div>
      </div>

      <div class="action-bar">
        <ion-button expand="block" @click="validerRegroupement" :disabled="diagrammesBank.length > 0">
          Valider les groupes
        </ion-button>
      </div>
    </div>

    <!-- PHASE 1b : Ordonnancement via OrderViewer -->
    <div v-else-if="etapeJeu === 'ordonnancement'" class="phase-container">
      <h2 class="section-title">Étape 2 : La chronologie</h2>
      <p class="section-subtitle">
        Série {{ serieAOrdonnerIndex + 1 }} sur {{ nbSeries }} — Remettez les positions dans l'ordre de la partie.
      </p>

      <OrderViewer 
        v-if="correctItemsPourSerieCourante"
        :key="'order-' + serieAOrdonnerIndex"
        :correct-items="correctItemsPourSerieCourante"
        @success="handleOrdonnancementSuccess"
      />
    </div>

    <!-- PHASE 2 : Résolution -->
    <div v-else class="resolution-container">
      <h2 class="section-title">Étape 3 : Le coup final !</h2>
      <p class="section-subtitle">
        Série {{ serieAOrdonnerIndex + 1 }} sur {{ solutionsResolution.length }} — Trouvez le coup décisif
      </p>

      <PuzzleViewer
        v-if="solutionCourante"
        :key="'puzzle-' + serieAOrdonnerIndex"
        :fen="solutionCourante.fenDepart"
        :couleur-joueur="solutionCourante.orientation"
        :solution="solutionCourante.solution"
        :shapes="solutionCourante.shapes"
        :lastMoveHighlight="(solutionCourante as any).lastMoveHighlight"
        @success="handlePuzzleSuccess"
      />
    </div>

    <!-- Modal de Zoom (Uniquement utilisé pour la Phase 1a ici, OrderViewer gère le sien) -->
    <div v-if="zoomedDiagram" class="zoom-overlay" @click="closeZoom">
      <div class="zoom-modal" @click.stop>
        <div class="zoom-header">
          <span>Aperçu de la position</span>
          <button class="close-btn" @click="closeZoom">&times;</button>
        </div>
        <div class="zoom-board-container">
          <DiagramViewer :fen="zoomedDiagram.fen" :orientation="zoomedDiagram.orientation" />
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
import OrderViewer from '@/components/shared/OrderViewer.vue'; // <-- Import du nouveau composant partagé

interface Serie { pgn_data: string; couleur_joueur: 'white' | 'black'; shapes?: any[]; }
interface ConfigMarcheHeros { mode: '3x5' | '5x3'; series: Serie[]; }
const props = defineProps<{ config: ConfigMarcheHeros; }>();
const emit = defineEmits<{ (e: 'success'): void; }>();

interface Diagramme { id: string; fen: string; orientation: 'white' | 'black'; serieIndex: number; ordre: number; }
interface Solution { fenDepart: string; orientation: 'white' | 'black'; solution: string[]; shapes: any[]; }

// État global
const etapeJeu = ref<'regroupement' | 'ordonnancement' | 'resolution'>('regroupement');
const nbSeries = ref(3);
const nbDiagrammesPerSerie = ref(5);

// Variables internes
const diagrammesBank = ref<Diagramme[]>([]);
const groupesSeries = ref<Diagramme[][]>([]);
const selectionBank = ref<number | null>(null);

let diagrammesOriginalParSerie: Diagramme[][] = []; // Pour passer les bons items à OrderViewer
let solutionsOriginalBank: Solution[] = [];
const solutionsResolution = ref<Solution[]>([]);
let mappingGroupeVersSerie: number[] = []; 

const serieAOrdonnerIndex = ref(0); // Sert à parcourir les groupes pour l'Étape 1b et l'Étape 2

const correctItemsPourSerieCourante = computed(() => {
  if (mappingGroupeVersSerie.length === 0) return null;
  const indexDeLaSerieDOrigine = mappingGroupeVersSerie[serieAOrdonnerIndex.value];
  return diagrammesOriginalParSerie[indexDeLaSerieDOrigine];
});

const solutionCourante = computed(() => {
  return solutionsResolution.value[serieAOrdonnerIndex.value] || null;
});

// ==========================================
// Zoom (Phase 1a)
// ==========================================
const zoomedDiagram = ref<Diagramme | null>(null);
let pressTimer: any = null;
let touchStartX = 0; let touchStartY = 0; let isScrolling = false;

const startTouchTimer = (event: TouchEvent, diagram: Diagramme | null) => {
  if (!diagram) return;
  isScrolling = false;
  if (event.touches.length > 0) { touchStartX = event.touches[0].clientX; touchStartY = event.touches[0].clientY; }
  if (pressTimer) clearTimeout(pressTimer);
  pressTimer = setTimeout(() => { if (!isScrolling) zoomedDiagram.value = diagram; }, 500);
};

const moveTouch = (event: TouchEvent) => {
  if (event.touches.length > 0) {
    if (Math.abs(event.touches[0].clientX - touchStartX) > 10 || Math.abs(event.touches[0].clientY - touchStartY) > 10) {
      isScrolling = true;
      if (pressTimer) { clearTimeout(pressTimer); pressTimer = null; }
    }
  }
};

const endTouchTimer = () => { if (pressTimer) { clearTimeout(pressTimer); pressTimer = null; } };
const handleLongPress = (event: Event, diagram: Diagramme | null) => { if (diagram) { event.preventDefault(); zoomedDiagram.value = diagram; } };
const closeZoom = () => { zoomedDiagram.value = null; };

// ==========================================
// PHASE 1a : REGROUPEMENT
// ==========================================
const selectBankItem = (index: number) => { selectionBank.value = selectionBank.value === index ? null : index; };

const addToGroupe = (gIndex: number) => {
  if (selectionBank.value !== null) {
    if (groupesSeries.value[gIndex].length >= nbDiagrammesPerSerie.value) return;
    groupesSeries.value[gIndex].push(diagrammesBank.value[selectionBank.value]);
    diagrammesBank.value.splice(selectionBank.value, 1);
    selectionBank.value = null;
  }
};

const removeFromGroupe = (gIndex: number, itemIndex: number) => {
  diagrammesBank.value.push(groupesSeries.value[gIndex][itemIndex]);
  groupesSeries.value[gIndex].splice(itemIndex, 1);
};

const validerRegroupement = async () => {
  let hasError = false;
  mappingGroupeVersSerie = [];

  for (let gIndex = 0; gIndex < groupesSeries.value.length; gIndex++) {
    const groupe = groupesSeries.value[gIndex];
    if (groupe.length !== nbDiagrammesPerSerie.value) { hasError = true; break; }

    const expectedSerieIndex = groupe[0].serieIndex;
    const isHomogeneous = groupe.every(d => d.serieIndex === expectedSerieIndex);
    
    if (!isHomogeneous) {
      hasError = true;
      groupe.forEach(item => diagrammesBank.value.push(item));
      groupesSeries.value[gIndex] = [];
    } else {
      mappingGroupeVersSerie.push(expectedSerieIndex);
    }
  }

  if (!hasError && new Set(mappingGroupeVersSerie).size !== nbSeries.value) {
    hasError = true;
    for (let gIndex = 0; gIndex < groupesSeries.value.length; gIndex++) {
      groupesSeries.value[gIndex].forEach(item => diagrammesBank.value.push(item));
      groupesSeries.value[gIndex] = [];
    }
    mappingGroupeVersSerie = [];
  }

  if (hasError) {
    const toast = await toastController.create({ message: 'Certaines séries sont mélangées. Observez bien les pièces !', duration: 3000, color: 'danger', position: 'bottom' });
    await toast.present();
  } else {
    serieAOrdonnerIndex.value = 0;
    etapeJeu.value = 'ordonnancement'; // On passe le relais à OrderViewer
  }
};

// ==========================================
// PHASE 1b : ORDONNANCEMENT (Callback du OrderViewer)
// ==========================================
const handleOrdonnancementSuccess = () => {
  if (serieAOrdonnerIndex.value >= nbSeries.value - 1) {
    // Toutes les séries sont ordonnées. On prépare la phase 3
    solutionsResolution.value = mappingGroupeVersSerie.map(idx => solutionsOriginalBank[idx]);
    serieAOrdonnerIndex.value = 0; 
    etapeJeu.value = 'resolution';
  } else {
    // On passe à la série suivante
    serieAOrdonnerIndex.value++;
  }
};

// ==========================================
// PHASE 2 : RÉSOLUTION
// ==========================================
const handlePuzzleSuccess = () => {
  if (serieAOrdonnerIndex.value === solutionsResolution.value.length - 1) {
    emit('success');
  } else {
    serieAOrdonnerIndex.value++;
  }
};

// ==========================================
// INITIALISATION GLOBALE
// ==========================================
const initExercice = () => {
  if (!props.config || !props.config.mode || !props.config.series) return;

  const mode = props.config.mode;
  nbSeries.value = mode === '5x3' ? 5 : 3;
  nbDiagrammesPerSerie.value = mode === '5x3' ? 3 : 5;

  groupesSeries.value = Array.from({ length: nbSeries.value }, () => []);
  diagrammesOriginalParSerie = Array.from({ length: nbSeries.value }, () => []);

  const bank: Diagramme[] = [];
  const solutions: Solution[] = [];

  for (let serieIndex = 0; serieIndex < Math.min(props.config.series.length, nbSeries.value); serieIndex++) {
    const serie = props.config.series[serieIndex];
    const mainChess = new Chess();
    mainChess.loadPgn(serie.pgn_data);
    const history = mainChess.history();

    const headers = mainChess.header();
    const fenInitiale = headers.FEN || 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
    
    const tempChess = new Chess(fenInitiale);
    const tourInitial = tempChess.turn(); 
    const orientation = (serie as any).orientation || serie.couleur_joueur || 'white'; 

    let startIndex = 0;
    const replayChess = new Chess(fenInitiale);

    const joueurDoitJouer = (orientation === 'white' && tourInitial === 'w') || (orientation === 'black' && tourInitial === 'b');
    if (!joueurDoitJouer && history.length > 0) {
      replayChess.move(history[0]);
      startIndex = 1;
    }

    const diag0: Diagramme = { id: `${serieIndex}-0`, fen: replayChess.fen(), orientation, serieIndex, ordre: 0 };
    bank.push(diag0);
    diagrammesOriginalParSerie[serieIndex].push(diag0);

    const maxIndexPourReconstitution = history.length - 1;
    let ordre = 1;
    let i = startIndex;

    while (ordre < nbDiagrammesPerSerie.value && i < maxIndexPourReconstitution) {
      if (i < maxIndexPourReconstitution) replayChess.move(history[i]);
      if (i + 1 < maxIndexPourReconstitution) replayChess.move(history[i + 1]);
      i += 2;

      const diagN: Diagramme = { id: `${serieIndex}-${ordre}`, fen: replayChess.fen(), orientation, serieIndex, ordre };
      bank.push(diagN);
      diagrammesOriginalParSerie[serieIndex].push(diagN);
      ordre++;
    }

    const indexCoupATrouver = i;
    const dernierCoup = history[indexCoupATrouver] || (history.length > 0 ? history[history.length - 1] : '');

    let lastMoveHighlight: string[] | undefined = undefined;
    if (indexCoupATrouver - 1 >= 0 && indexCoupATrouver - 1 < history.length) {
      const helperChess = new Chess();
      if (headers.FEN) helperChess.load(headers.FEN);
      for (let k = 0; k < indexCoupATrouver - 1; k++) helperChess.move(history[k]);
      try {
        const mv = helperChess.move(history[indexCoupATrouver - 1]);
        lastMoveHighlight = [mv.from, mv.to];
      } catch {
        // Ignorer si le coup ne peut pas être joué
      }
    }

    solutions.push({
      fenDepart: replayChess.fen(),
      orientation,
      solution: [dernierCoup],
      shapes: serie.shapes || [],
      ...(lastMoveHighlight ? { lastMoveHighlight } : {})
    } as any);
  }

  // Mélange de la banque initiale pour le regroupement
  for (let i = bank.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [bank[i], bank[j]] = [bank[j], bank[i]];
  }

  diagrammesBank.value = bank;
  solutionsOriginalBank = [...solutions];
};

onMounted(() => { initExercice(); });
</script>

<style scoped>
.exercice-type-marche-heros { width: 100%; padding: 16px; box-sizing: border-box; }
.phase-container, .resolution-container { animation: fadeIn 0.4s ease; }
.section-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 8px; color: var(--ion-color-dark); }
.section-subtitle { font-size: 0.95rem; color: var(--ion-color-step-600); margin-bottom: 24px; }

/* Zone Banque Commune (Phase 1a) */
.bank-container { margin-bottom: 24px; }
.bank-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 12px; }
.bank-scroll { display: flex; gap: 12px; overflow-x: auto; padding: 8px 0; }
.empty-bank-msg { padding: 16px; text-align: center; background: var(--ion-color-success-tint); color: var(--ion-color-success-shade); border-radius: 8px; font-weight: 600; }

.bank-item-wrapper { width: 100px; height: 100px; flex-shrink: 0; border: 2px solid transparent; border-radius: 8px; cursor: pointer; transition: transform 0.2s, border-color 0.2s; position: relative; }
.miniature-wrapper { width: 100%; height: 100%; overflow: hidden; border-radius: 6px; pointer-events: none; }
.bank-item-wrapper.is-selected { border-color: var(--ion-color-primary); transform: scale(1.05); }
.board-miniature { transform: scale(1); width: 100%; height: 100%; margin: 0; }

/* Phase 1a : Groupes */
.groups-container { display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px; }
.group-basket { border: 2px dashed var(--ion-color-step-300); border-radius: 12px; background: var(--ion-color-step-50); padding: 12px; transition: border-color 0.3s, background-color 0.3s; }
.group-basket.highlight-target { border-color: var(--ion-color-primary); background: var(--ion-color-primary-tint); cursor: pointer; }
.group-header { font-weight: 700; color: var(--ion-color-step-700); margin-bottom: 12px; font-size: 0.95rem; }
.group-content { display: flex; flex-wrap: wrap; gap: 12px; min-height: 100px; align-items: center; }
.empty-group-text { color: var(--ion-color-step-400); font-size: 0.85rem; font-style: italic; width: 100%; text-align: center; }
.in-group { border: 1px solid var(--ion-color-step-200); }
.remove-badge { position: absolute; top: -6px; right: -6px; background: var(--ion-color-danger); color: white; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }

.action-bar { margin-top: 24px; }

/* Modal Zoom */
.zoom-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: center; z-index: 9999; }
.zoom-modal { background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.35); width: 90%; max-width: 400px; overflow: hidden; display: flex; flex-direction: column; }
.zoom-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: var(--ion-color-step-100); font-weight: 600; }
.close-btn { background: transparent; border: none; font-size: 1.8rem; color: var(--ion-color-step-600); cursor: pointer; }
.zoom-board-container { width: 100%; aspect-ratio: 1; padding: 12px; box-sizing: border-box; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>