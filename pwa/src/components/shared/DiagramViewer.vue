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
        fen: props.fen,
        orientation: props.orientation,
        viewOnly: true,
        drawable: {
          shapes: props.shapes
        }
      }
    );

    if (props.shapes && props.shapes.length > 0) {
      boardCoreInstance.value.setShapes(props.shapes);
    }
  }
});

watch(() => props.shapes, (newShapes) => {
  if (boardCoreInstance.value && newShapes) {
    boardCoreInstance.value.setShapes(newShapes);
  }
}, { deep: true });

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
