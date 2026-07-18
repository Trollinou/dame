<template>
  <div class="diagram-viewer-container">
    <div class="main-board">
      <div ref="boardEl"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
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
        orientation: props.orientation,
        viewOnly: true,
      },
      {},
      {
        fen: props.fen,
        shapes: props.shapes
      }
    );
  }
});

watch(
  () => [props.fen, props.shapes],
  ([newFen, newShapes]) => {
    if (boardCoreInstance.value) {
      boardCoreInstance.value.setDiagram({
        fen: newFen as string,
        shapes: newShapes as any[]
      });
    }
  },
  { deep: true }
);

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
.diagram-viewer-container {
  width: 100%;
  max-width: 400px;
  margin: 16px auto;
}
</style>
