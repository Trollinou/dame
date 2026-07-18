<template>
  <div ref="contentRef" class="lecon-content-wrapper" v-html="contenuHtml"></div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick, render, createVNode } from 'vue';
import DiagramViewer from '@/components/shared/DiagramViewer.vue';
import PgnViewer from '@/components/shared/PgnViewer.vue';

const props = defineProps<{
  contenuHtml: string;
}>();

const contentRef = ref<HTMLElement | null>(null);
let mountedVNodes: HTMLElement[] = [];

const hydraterBlocs = () => {
  if (!contentRef.value) return;

  // 1. Hydrater les FEN
  const fenElements = contentRef.value.querySelectorAll('.roi-bloc-fen');
  fenElements.forEach((el) => {
    const htmlEl = el as HTMLElement;
    const fen = htmlEl.dataset.fen || '';
    const orientation = (htmlEl.dataset.orientation || 'white') as 'white' | 'black';
    let shapes: any[] = [];
    if (htmlEl.dataset.shapes) {
      try {
        shapes = JSON.parse(htmlEl.dataset.shapes);
      } catch (e) {
        console.error('Erreur lors du parsing des shapes :', e);
        shapes = [];
      }
    }

    const vnode = createVNode(DiagramViewer, { fen, orientation, shapes });
    render(vnode, htmlEl);
    mountedVNodes.push(htmlEl);
  });

  // 2. Hydrater les PGN
  const pgnElements = contentRef.value.querySelectorAll('.roi-bloc-pgn');
  pgnElements.forEach((el) => {
    const htmlEl = el as HTMLElement;
    const pgn = htmlEl.dataset.pgn || '';
    const vnode = createVNode(PgnViewer, { pgnString: pgn });
    render(vnode, htmlEl);
    mountedVNodes.push(htmlEl);
  });
};

const nettoyerVNodes = () => {
  mountedVNodes.forEach((el) => {
    render(null, el);
  });
  mountedVNodes = [];
};

onMounted(() => {
  hydraterBlocs();
});

watch(() => props.contenuHtml, async () => {
  nettoyerVNodes();
  await nextTick();
  hydraterBlocs();
});

onBeforeUnmount(() => {
  nettoyerVNodes();
});
</script>

<style>
/* Style global pour les blocs de la leçon */
.roi-bloc-fen, .roi-bloc-pgn {
  margin: 16px auto;
  display: block;
  max-width: 400px;
  width: 100%;
}
</style>
