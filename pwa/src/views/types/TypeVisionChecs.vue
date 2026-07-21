<template>
  <ion-card class="ion-margin-bottom">
    <ion-card-header>
      <ion-card-title>Analyse à l'aveugle</ion-card-title>
    </ion-card-header>
    <ion-card-content>
      <p style="font-size: 1.1rem; line-height: 1.5; margin: 0; white-space: pre-line;">
        {{ config.description }}
      </p>
    </ion-card-content>
  </ion-card>

  <VisionViewer
    :fen-depart="config.fen_depart"
    :couleur-joueur="config.couleur_joueur"
    :case-depart="config.case_depart"
    :case-arrivee="config.case_arrivee"
    :solution-san="config.solution_san"
    @success="$emit('success')"
  />
</template>

<script setup lang="ts">
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent
} from '@ionic/vue';
import VisionViewer from '@/components/shared/VisionViewer.vue';

defineProps<{
  config: {
    fen_depart: string;
    couleur_joueur: 'white' | 'black';
    description: string;
    case_depart: string;
    case_arrivee: string;
    solution_san: string;
  };
  id: number;
}>();

defineEmits<{
  (e: 'success'): void;
}>();
</script>
