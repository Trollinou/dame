<template>
  <VisionViewer
    :consigne="config.consigne"
    :diagrammes="config.diagrammes"
    :legacy-config="config"
    @success="$emit('success')"
  />
</template>

<script setup lang="ts">
import VisionViewer, { type DiagrammeConfig } from '@/components/shared/VisionViewer.vue';

defineProps<{
  config: {
    consigne?: string;
    diagrammes?: DiagrammeConfig[];
    // Propriétés legacy pour rétrocompatibilité
    fen_depart?: string;
    couleur_joueur?: 'white' | 'black';
    description?: string;
    case_depart?: string;
    case_arrivee?: string;
    solution_san?: string;
  };
  id: number;
}>();

defineEmits<{
  (e: 'success'): void;
}>();
</script>
