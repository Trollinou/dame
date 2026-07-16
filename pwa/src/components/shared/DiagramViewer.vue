<template>
  <div ref="boardEl" class="diagram-viewer-board"></div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { BoardCore, type BoardCoreState } from 'eg-chessboard';
import 'eg-chessboard/style.css';

const props = withDefaults(
  defineProps<{
    fen: string;
    orientation?: 'white' | 'black';
    shapes?: any[];
  }>(),
  {
    orientation: 'white',
    shapes: () => []
  }
);

const boardEl = ref<HTMLElement | null>(null);
const boardCoreInstance = ref<BoardCore | null>(null);

onMounted(() => {
  if (boardEl.value) {
    const state: BoardCoreState = {
      showThreats: false,
      freeMode: false,
      soloMode: false,
      promotionDialogState: { isEnabled: false },
      historyViewerState: { isEnabled: false },
      currentComment: ''
    };

    boardCoreInstance.value = new BoardCore(
      boardEl.value,
      state,
      () => {},
      () => {},
      {
        fen: props.fen,
        orientation: props.orientation,
        viewOnly: true,
        drawable: {
          shapes: props.shapes
        }
      }
    );
  }
});

onBeforeUnmount(() => {
  if (boardCoreInstance.value) {
    if (boardCoreInstance.value.board) {
      boardCoreInstance.value.board.destroy();
    }
    boardCoreInstance.value = null;
  }
});
</script>

<style scoped>
.diagram-viewer-board {
  width: 100%;
  max-width: 400px;
  aspect-ratio: 1;
  margin: 0 auto;
}
</style>
