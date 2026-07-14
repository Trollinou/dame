<template>
  <div class="pgn-stage-layout">
    <div class="board-container">
      <eg-chessboard
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
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { IonButton, IonIcon } from '@ionic/vue';
import { playBackOutline, chevronBackOutline, chevronForwardOutline } from 'ionicons/icons';
import EgChessboard from 'eg-chessboard/vue';
import type { BoardCore } from 'eg-chessboard';

const props = defineProps<{
  pgnString: string;
  autoCompleteDelay?: number;
}>();

const emit = defineEmits<{
  (e: 'finished'): void;
}>();

const boardApi = ref<BoardCore | null>(null);
const currentComment = ref('');
const pgnBoardConfig = { viewOnly: true };

const syncComment = () => {
  if (boardApi.value) {
    currentComment.value = (boardApi.value as any).state.currentComment || '';
  }
};

const loadPgnData = () => {
  if (boardApi.value && props.pgnString) {
    boardApi.value.setPosition('start');
    boardApi.value.loadPgn(props.pgnString);
    boardApi.value.viewStart();
    syncComment();
  }
};

const onBoardCreated = (api: BoardCore) => {
  boardApi.value = api;
  loadPgnData();
};

watch(() => props.pgnString, () => {
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
  const historyState = (boardApi.value as any).state.historyViewerState;

  if (!historyState.isEnabled) {
    emit('finished');
    return;
  }

  boardApi.value.viewNext();
  syncComment();

  const newHistoryState = (boardApi.value as any).state.historyViewerState;
  if (!newHistoryState.isEnabled && props.autoCompleteDelay && props.autoCompleteDelay > 0) {
    setTimeout(() => {
      emit('finished');
    }, props.autoCompleteDelay);
  }
};
</script>

<style scoped>
.board-container {
  width: 100%;
  aspect-ratio: 1;
  max-width: 500px;
  margin: 0 auto;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
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
