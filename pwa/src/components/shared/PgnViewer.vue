<template>
  <div class="pgn-stage-layout">
    <div class="board-container">
      <eg-chessboard
        mode="study"
        :boardConfig="pgnBoardConfig"
        :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
        @board-created="onBoardCreated"
      />
    </div>

    <!-- Navigation Controls -->
    <div class="navigation-controls">
      <ion-button fill="outline" color="primary" class="nav-btn" @click="viewStart">
        <ion-icon slot="icon-only" :icon="playBackOutline"></ion-icon>
      </ion-button>
      <ion-button fill="outline" color="primary" class="nav-btn" @click="viewPrevious">
        <ion-icon slot="icon-only" :icon="chevronBackOutline"></ion-icon>
      </ion-button>
      <ion-button fill="outline" color="primary" class="nav-btn" @click="viewNext">
        <ion-icon slot="icon-only" :icon="chevronForwardOutline"></ion-icon>
      </ion-button>
    </div>

    <!-- Comment Display -->
    <div class="comment-container">
      <p class="comment-text" :class="{ 'placeholder-text': !currentComment }">
        {{ currentComment ? '💬 ' + currentComment : 'Aucun commentaire pour cette position.' }}
      </p>
    </div>

    <!-- Footer de Navigation par Carte (optionnel si en série) -->
    <SeriesCardFooter
      v-if="props.totalCards && props.totalCards > 1 && props.currentCard"
      :currentCard="props.currentCard"
      :totalCards="props.totalCards"
      :isSolved="isCompleted"
      pendingHint="Visionnez tous les coups du PGN pour continuer"
      @next="emit('finished')"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { IonButton, IonIcon } from '@ionic/vue';
import { playBackOutline, chevronBackOutline, chevronForwardOutline } from 'ionicons/icons';
import EgChessboard from 'eg-chessboard/vue';
import type { BoardCore } from 'eg-chessboard';
import SeriesCardFooter from '@/components/shared/SeriesCardFooter.vue';

const props = defineProps<{
  pgnString?: string;
  pgn?: string;
  orientation?: 'white' | 'black' | string;
  autoCompleteDelay?: number;
  currentCard?: number;
  totalCards?: number;
}>();

const emit = defineEmits<{
  (e: 'finished'): void;
}>();

const boardApi = ref<BoardCore | null>(null);
const currentComment = ref('');
const isCompleted = ref(false);

const activePgn = computed(() => props.pgn || props.pgnString || '');
const pgnBoardConfig = computed(() => ({
  viewOnly: true,
  orientation: (props.orientation === 'black' ? 'black' : 'white') as 'white' | 'black'
}));

const syncComment = () => {
  if (boardApi.value) {
    currentComment.value = boardApi.value.getCurrentComment() || '';
  }
};

const loadPgnData = () => {
  if (boardApi.value && activePgn.value) {
    boardApi.value.setPosition('start');
    boardApi.value.loadPgn(activePgn.value);
    boardApi.value.viewStart();
    syncComment();

    const historyState = boardApi.value.getHistoryViewerState();
    // Si le PGN n'a pas de coups ou 1 seul coup déjà atteint
    if (!historyState.isEnabled) {
      isCompleted.value = true;
    } else {
      isCompleted.value = false;
    }
  }
};

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
  loadPgnData();
};

watch(activePgn, () => {
  loadPgnData();
});

const viewStart = () => {
  if (boardApi.value) {
    boardApi.value.viewStart();
    syncComment();
  }
};

const viewPrevious = () => {
  if (boardApi.value) {
    boardApi.value.viewPrevious();
    syncComment();
  }
};

const viewNext = () => {
  if (!boardApi.value) return;
  const historyState = boardApi.value.getHistoryViewerState();

  if (!historyState.isEnabled) {
    isCompleted.value = true;
    if (!props.totalCards) {
      emit('finished');
    }
    return;
  }

  boardApi.value.viewNext();
  syncComment();

  const newHistoryState = boardApi.value.getHistoryViewerState();
  if (!newHistoryState.isEnabled) {
    isCompleted.value = true;
    if (!props.totalCards && props.autoCompleteDelay && props.autoCompleteDelay > 0) {
      setTimeout(() => {
        emit('finished');
      }, props.autoCompleteDelay);
    } else if (!props.totalCards) {
      emit('finished');
    }
  }
};
</script>

<style scoped>
.board-container {
  width: 100%;
  aspect-ratio: 1;
  max-width: 500px;
  margin: 0 auto;
  border-radius: 0;
  overflow: hidden;
  box-shadow: none;
}
.navigation-controls {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin-top: 8px;
}
.nav-btn {
  --border-radius: 50%;
  width: 48px;
  height: 48px;
}
.comment-container {
  width: 100%;
  margin-top: 12px;
  background: var(--ion-color-step-100, #f4f5f8);
  border-radius: 8px;
  border-left: 4px solid var(--ion-color-primary, #3880ff);
  padding: 12px 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  height: 72px;
  overflow-y: auto;
}
.comment-text {
  margin: 0;
  font-size: 0.92rem;
  line-height: 1.4;
  color: var(--ion-color-step-800, #444);
}
.placeholder-text {
  color: var(--ion-color-step-400, #989aa2);
  font-style: italic;
}
:deep(.main-wrap.viewingHistory) {
  filter: none !important;
}
</style>
