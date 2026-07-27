<template>
  <div class="exercice-type-class-echecs">
    <ion-card class="ion-margin-bottom">
      <ion-card-header>
        <ion-card-title>Class'échecs</ion-card-title>
      </ion-card-header>
      <p v-if="config.consigne" class="ion-padding-horizontal" style="font-size: 1.05rem; font-weight: 500;">
        {{ config.consigne }}
      </p>
    </ion-card>

    <OrderViewer
      v-if="itemsAOrdonner && itemsAOrdonner.length > 0"
      :correct-items="itemsAOrdonner"
      @success="$emit('success')"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  IonCard,
  IonCardHeader,
  IonCardTitle
} from '@ionic/vue';
import OrderViewer from '@/components/shared/OrderViewer.vue';

interface PositionItem {
  fen: string;
  couleur_joueur: 'white' | 'black';
  shapes?: any[];
}

interface ConfigClassEchecs {
  consigne?: string;
  positions: PositionItem[];
}

const props = defineProps<{
  config: ConfigClassEchecs;
  id: number;
}>();

defineEmits<{
  (e: 'success'): void;
}>();

const itemsAOrdonner = computed(() => {
  if (!props.config?.positions || !Array.isArray(props.config.positions)) {
    return [];
  }
  return props.config.positions.map((pos, index) => ({
    id: index,
    fen: pos.fen,
    orientation: pos.couleur_joueur,
    shapes: pos.shapes || []
  }));
});
</script>

<style scoped>
.exercice-type-class-echecs {
  width: 100%;
}
</style>
