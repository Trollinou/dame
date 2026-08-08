<template>
  <div class="diagram-viewer-container">
    <div class="main-board">
      <eg-chessboard
        :diagram="{
          fen: props.fen,
          shapes: props.shapes
        }"
        :boardConfig="{
          orientation: props.orientation,
          viewOnly: true
        }"
        :stockfishConfig="{ whiteMode: 'disabled', blackMode: 'disabled' }"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { default as EgChessboard } from 'eg-chessboard/vue';
import type { DrawShape } from 'eg-chessboard';
import 'eg-chessboard/style.css';

const props = withDefaults(
  defineProps<{
    fen: string;
    orientation?: 'white' | 'black';
    shapes?: DrawShape[];
  }>(),
  {
    orientation: 'white',
    shapes: () => []
  }
);
</script>

<style scoped>
.diagram-viewer-container {
  width: 100%;
  max-width: 400px;
  margin: 16px auto;
}
</style>
