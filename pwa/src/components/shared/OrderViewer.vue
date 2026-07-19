<!-- src/components/shared/OrderViewer.vue -->
<template>
  <div class="order-viewer-container">
    
    <div class="columns-layout">
      <!-- Colonne Gauche : La Banque (À classer) -->
      <div class="column bank-column">
        <div class="column-header">
          <h3 class="column-title">À trier</h3>
          <span class="column-subtitle">Appui long pour zoomer ou clic droit</span>
        </div>
        
        <div class="column-content">
          <div 
            v-for="(item, index) in bank" 
            :key="'bank-' + item.id"
            class="item-wrapper"
            :class="{ 'is-selected': selectionBank === index }"
            @click="selectBank(index)"
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

          <div v-if="bank.length === 0" class="empty-msg">
            <span class="empty-icon">✓</span>
            <p>Tout est placé</p>
          </div>
        </div>
      </div>

      <!-- Colonne Droite : Les Emplacements (Ordre) -->
      <div class="column slots-column">
        <div class="column-header">
          <h3 class="column-title">Classement final</h3>
          <span class="column-subtitle">De 1 à {{ correctItems.length }}</span>
        </div>
        
        <div class="column-content">
          <div 
            v-for="(slot, index) in slots" 
            :key="'slot-' + index"
            class="item-wrapper slot-wrapper"
            :class="{ 'is-empty': !slot, 'is-selected': selectionSlot === index }"
            @click="selectSlot(index)"
            @contextmenu="handleLongPress($event, slot)"
            @touchstart="startTouchTimer($event, slot)"
            @touchmove="moveTouch"
            @touchend="endTouchTimer"
            @touchcancel="endTouchTimer"
          >
            <!-- Badge de numérotation universel (Ordre ou Force) -->
            <div class="slot-badge" :class="{ 'filled': slot }">{{ index + 1 }}</div>

            <template v-if="slot">
              <div class="miniature-wrapper">
                <DiagramViewer :fen="slot.fen" :orientation="slot.orientation" class="board-miniature" />
              </div>
            </template>
            <div v-else class="empty-placeholder">
              <span>Libre</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bouton d'action -->
    <div class="action-bar">
      <ion-button expand="block" @click="validerOrdre" :disabled="bank.length > 0">
        Valider l'ordre
      </ion-button>
    </div>

    <!-- Modal de Zoom (Appui long) -->
    <div v-if="zoomedItem" class="zoom-overlay" @click="closeZoom">
      <div class="zoom-modal" @click.stop>
        <div class="zoom-header">
          <span>Aperçu de la position</span>
          <button class="close-btn" @click="closeZoom">&times;</button>
        </div>
        <div class="zoom-board-container">
          <DiagramViewer :fen="zoomedItem.fen" :orientation="zoomedItem.orientation" />
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { toastController, IonButton } from '@ionic/vue';
import DiagramViewer from '@/components/shared/DiagramViewer.vue';

interface OrderItem {
  id: string | number;
  fen: string;
  orientation: 'white' | 'black';
  [key: string]: any;
}

const props = defineProps<{
  correctItems: OrderItem[]; // Le tableau des éléments dans l'ordre EXACT attendu
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const bank = ref<OrderItem[]>([]);
const slots = ref<(OrderItem | null)[]>([]);
const selectionBank = ref<number | null>(null);
const selectionSlot = ref<number | null>(null);

// Initialisation et mélange
const initOrder = () => {
  const itemsCopy = [...props.correctItems];
  
  // Mélange de Fisher-Yates
  for (let i = itemsCopy.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [itemsCopy[i], itemsCopy[j]] = [itemsCopy[j], itemsCopy[i]];
  }

  bank.value = itemsCopy;
  // Génère dynamiquement le bon nombre de cases vides (3 ou 5)
  slots.value = Array(props.correctItems.length).fill(null);
  selectionBank.value = null;
  selectionSlot.value = null;
};

onMounted(() => { initOrder(); });
watch(() => props.correctItems, () => { initOrder(); }, { deep: true });

// Logique Tap & Tap
const selectBank = (index: number) => {
  selectionSlot.value = null;
  selectionBank.value = selectionBank.value === index ? null : index;
};

const selectSlot = (index: number) => {
  const currentContent = slots.value[index];

  if (!currentContent && selectionBank.value !== null) {
    // Placement depuis la banque vers la case
    slots.value[index] = bank.value[selectionBank.value];
    bank.value.splice(selectionBank.value, 1);
    selectionBank.value = null;
  } else if (currentContent) {
    // Retour de la case vers la banque
    bank.value.push(currentContent);
    slots.value[index] = null;
    selectionBank.value = null;
    selectionSlot.value = null;
  }
};

// Validation
const validerOrdre = async () => {
  let hasError = false;

  for (let i = 0; i < slots.value.length; i++) {
    const slot = slots.value[i];
    // On compare l'ID de l'élément placé avec l'ID de l'élément correct attendu à cet index
    if (!slot || slot.id !== props.correctItems[i].id) {
      hasError = true;
      if (slot) {
        bank.value.push(slot); // Renvoie automatiquement l'erreur dans la banque
        slots.value[i] = null;
      }
    }
  }

  if (hasError) {
    const toast = await toastController.create({
      message: 'L\'ordre est incorrect. Observez bien les positions !',
      duration: 3000, color: 'danger', position: 'bottom'
    });
    await toast.present();
  } else {
    emit('success');
  }
};

// Logique de Zoom (Appui long)
const zoomedItem = ref<OrderItem | null>(null);
let pressTimer: any = null;
let touchStartX = 0;
let touchStartY = 0;
let isScrolling = false;

const startTouchTimer = (event: TouchEvent, item: OrderItem | null) => {
  if (!item) return;
  isScrolling = false;
  if (event.touches.length > 0) {
    touchStartX = event.touches[0].clientX;
    touchStartY = event.touches[0].clientY;
  }
  if (pressTimer) clearTimeout(pressTimer);
  pressTimer = setTimeout(() => { if (!isScrolling) zoomedItem.value = item; }, 500);
};

const moveTouch = (event: TouchEvent) => {
  if (event.touches.length > 0) {
    const diffX = Math.abs(event.touches[0].clientX - touchStartX);
    const diffY = Math.abs(event.touches[0].clientY - touchStartY);
    if (diffX > 10 || diffY > 10) {
      isScrolling = true;
      if (pressTimer) { clearTimeout(pressTimer); pressTimer = null; }
    }
  }
};

const endTouchTimer = () => { if (pressTimer) { clearTimeout(pressTimer); pressTimer = null; } };
const handleLongPress = (event: Event, item: OrderItem | null) => {
  if (item) { event.preventDefault(); zoomedItem.value = item; }
};
const closeZoom = () => { zoomedItem.value = null; };
</script>

<style scoped>
.order-viewer-container {
  width: 100%;
}

.columns-layout {
  display: flex;
  gap: 16px;
  width: 100%;
  margin-bottom: 24px;
}

.column {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0; /* Empêche le débordement sur les petits écrans */
}

.column-header {
  margin-bottom: 12px;
  text-align: center;
}

.column-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--ion-color-dark);
  margin: 0 0 4px 0;
}

.column-subtitle {
  font-size: 0.75rem;
  color: var(--ion-color-step-500);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.column-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Items et Slots (Échiquiers) */
.item-wrapper {
  width: 100%;
  aspect-ratio: 1; /* Garantit que la div reste un carré parfait */
  border: 2px solid transparent;
  border-radius: 8px;
  cursor: pointer;
  transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
  position: relative;
  background: var(--ion-color-step-100);
}

.miniature-wrapper {
  width: 100%;
  height: 100%;
  border-radius: 6px;
  overflow: hidden;
  pointer-events: none;
}

.board-miniature {
  width: 100%;
  height: 100%;
  margin: 0;
}

.item-wrapper.is-selected {
  border-color: var(--ion-color-primary);
  box-shadow: 0 4px 12px rgba(56, 128, 255, 0.3);
  transform: scale(1.03);
}

/* Spécifique aux Slots */
.slot-wrapper {
  border-color: var(--ion-color-step-300);
  background: var(--ion-color-step-50);
}

.slot-wrapper.is-empty {
  border-style: dashed;
}

.slot-wrapper.is-selected {
  border-color: var(--ion-color-primary);
  border-style: solid;
}

/* Badge du numéro de slot (1, 2, 3...) */
.slot-badge {
  position: absolute;
  top: -8px;
  left: -8px;
  background: var(--ion-color-step-300);
  color: white;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.9rem;
  z-index: 10;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  transition: background-color 0.3s;
}

.slot-badge.filled {
  background: var(--ion-color-primary);
}

.empty-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--ion-color-step-400);
  font-weight: 600;
  font-size: 0.95rem;
  text-transform: uppercase;
}

/* Message Banque vide */
.empty-msg {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  aspect-ratio: 1;
  background: var(--ion-color-success-tint);
  border-radius: 8px;
  color: var(--ion-color-success-shade);
  border: 2px dashed var(--ion-color-success);
}

.empty-icon {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 8px;
}

.empty-msg p {
  margin: 0;
  font-weight: 600;
  font-size: 0.9rem;
}

.action-bar {
  margin-top: 16px;
}

/* Modal Zoom */
.zoom-overlay {
  position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
  display: flex; justify-content: center; align-items: center; z-index: 9999;
}
.zoom-modal {
  background: var(--ion-background-color, #fff); border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.35); width: 90%; max-width: 400px; overflow: hidden; display: flex; flex-direction: column;
}
.zoom-header {
  display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: var(--ion-color-step-100); font-weight: 600;
}
.close-btn { background: transparent; border: none; font-size: 1.8rem; color: var(--ion-color-step-600); cursor: pointer; }
.zoom-board-container { width: 100%; aspect-ratio: 1; padding: 12px; box-sizing: border-box; }
</style>